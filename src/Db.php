<?php

namespace Bayfront\SimplePdo;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\SimplePdo\Exceptions\InvalidDatabaseException;
use PDO;
use PDOStatement;

class Db
{

    private static array $db_connections = []; // Db connections as PDO objects
    private string $current_db_name;

    public const DB_DEFAULT = 'default';

    /**
     * Constructor.
     *
     * Sets given PDO instance as current database connection.
     *
     * @param PDO $pdo
     * @param string $db_name
     */
    public function __construct(PDO $pdo, string $db_name = self::DB_DEFAULT)
    {
        self::$db_connections[$db_name] = $pdo;
        $this->current_db_name = $db_name;
    }

    public function __destruct()
    {
        $this->disconnectAll();
    }

    /**
     * Disconnects from all databases.
     *
     * @return void
     */
    private function disconnectAll(): void
    {
        foreach (self::$db_connections as $k => $connection) {
            self::$db_connections[$k] = NULL;
            unset(self::$db_connections[$k]);
        }
    }

    /*
     * |--------------------------------------------------------------------------
     * | Database connections
     * |--------------------------------------------------------------------------
     */

    /**
     * Add a PDO instance.
     *
     * @param PDO $pdo
     * @param string $db_name (Name must be unique)
     * @param bool $make_current (Use this connection for all subsequent queries)
     * @return self
     * @throws InvalidDatabaseException
     */
    public function addConnection(PDO $pdo, string $db_name, bool $make_current = false): self
    {

        if (isset(self::$db_connections[$db_name])) {
            throw new InvalidDatabaseException('Database name already used');
        }

        self::$db_connections[$db_name] = $pdo;

        if (true === $make_current) {
            $this->current_db_name = $db_name;
        }

        return $this;

    }

    /**
     * Use a given database connection for all subsequent queries.
     *
     * @param string $db_name
     * @return self
     * @throws InvalidDatabaseException
     */
    public function useConnection(string $db_name): self
    {

        if (!isset(self::$db_connections[$db_name])) {
            throw new InvalidDatabaseException('Database is not defined');
        }

        $this->current_db_name = $db_name;

        return $this;

    }

    /**
     * Returns the raw PDO instance of a given database.
     *
     * @param string $db_name (Leaving this parameter blank will return the PDO instance of the current database)
     * @return PDO
     * @throws InvalidDatabaseException
     */
    public function getConnection(string $db_name = ''): PDO
    {

        if ($db_name == '') {
            $db_name = $this->current_db_name;
        }

        if (isset(self::$db_connections[$db_name])) {
            return self::$db_connections[$db_name];
        }

        throw new InvalidDatabaseException('Database is not defined');

    }

    /**
     * Returns the raw PDO instance of the current database.
     *
     * @return PDO
     */
    public function getCurrentConnection(): PDO
    {
        return self::$db_connections[$this->current_db_name];
    }

    /**
     * Returns name of the current database connection.
     *
     * @return string
     */
    public function getCurrentConnectionName(): string
    {
        return $this->current_db_name;
    }

    /**
     * Returns array of all database connection names.
     *
     * @return array
     */
    public function getConnectionNames(): array
    {
        return array_keys(self::$db_connections);
    }

    /**
     * Checks if a database connection exists with a given name.
     *
     * @param string $db_name
     * @return bool
     */
    public function connectionExists(string $db_name): bool
    {
        return isset(self::$db_connections[$db_name]);
    }

    /*
     * |--------------------------------------------------------------------------
     * | Queries
     * |--------------------------------------------------------------------------
     */

    private mixed $query_start; // Microtime for query start
    private mixed $stmt = NULL; // PDOStatement object
    private string $raw_query = ''; // Last raw query
    private array $parameters = [];
    private array $query_durations = []; // Records duration to execute each query

    /**
     * Resets all query-specific data and begins timer for current query.
     *
     * @return void
     */
    private function reset(): void
    {
        $this->query_start = microtime(true);
        $this->stmt = NULL;
        $this->raw_query = '';
    }

    /**
     * Begin, prepare and execute query.
     *
     * @param string $query
     * @param array $params
     * @return bool
     */
    private function executeQuery(string $query, array $params = []): bool
    {
        $this->reset();

        $this->stmt = $this->prepare($query);

        $this->raw_query = $query;

        $this->bindParams($params);

        return $this->stmt->execute();

    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $query
     * @return PDOStatement
     */
    private function prepare(string $query): PDOStatement
    {
        return $this->getCurrentConnection()->prepare($query); // PDOStatement object
    }

    /**
     * Binds multiple values to parameters from placeholders,
     * and sets the most applicable data type for the parameter.
     *
     * @param array $params
     * @return void
     */
    private function bindParams(array $params): void
    {

        $this->parameters = $params;

        if (!empty($params)) {

            foreach ($params as $placeholder => $value) {

                if (is_int($placeholder)) {
                    $placeholder = $placeholder + 1;
                } else {
                    $placeholder = ':' . $placeholder;
                }

                $type = match ($value) {
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    is_int($value) => PDO::PARAM_INT,
                    default => PDO::PARAM_STR,
                };

                $this->stmt->bindValue($placeholder, $value, $type);

                if (is_int($placeholder)) {
                    $this->raw_query = preg_replace('/[?]/', $value, $this->raw_query, 1);
                } else {
                    $this->raw_query = str_replace($placeholder, $value, $this->raw_query);
                }

            }

        }

    }

    /**
     * Execute a query.
     *
     * @param string $query
     * @param array $params
     * @return bool
     */
    public function query(string $query, array $params = []): bool
    {

        $return = $this->executeQuery($query, $params);

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $return;

    }

    /**
     * Returns the result set from a table, or false on failure.
     *
     * @param string $query
     * @param array $params
     * @param bool $return_array (When false, the result set will be returned as an object)
     * @return mixed
     */
    public function select(string $query, array $params = [], bool $return_array = true): mixed
    {

        $this->executeQuery($query, $params);

        if ($return_array) {
            $return = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $return = $this->stmt->fetchAll(PDO::FETCH_OBJ);
        }

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $return;

    }

    /**
     * Returns a single row from a table, or false on failure.
     *
     * @param string $query
     * @param array $params
     * @param bool $return_array (When false, the result set will be returned as an object)
     * @return mixed
     */
    public function row(string $query, array $params = [], bool $return_array = true): mixed
    {

        $this->executeQuery($query, $params);

        if ($return_array) {
            $return = $this->stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $return = $this->stmt->fetch(PDO::FETCH_OBJ);
        }

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $return;

    }

    /**
     * Returns a single column from a single row of a table, or false if not existing.
     *
     * @param string $query
     * @param array $params
     * @return mixed
     */
    public function single(string $query, array $params = []): mixed
    {

        $this->executeQuery($query, $params);

        $return = $this->stmt->fetchColumn();

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $return;

    }

    /**
     * Inserts a new row.
     *
     * @param string $table
     * @param array $values
     * @param bool $overwrite (Overwrite preexisting values if they exist)
     * @return bool
     */
    public function insert(string $table, array $values, bool $overwrite = true): bool
    {

        $this->reset();

        $query = 'INSERT INTO ' . $table . ' (';

        $query .= implode(', ', array_keys($values));

        $query .= ') VALUES (';

        $raw_query = $query . implode(', ', $values);

        $query .= implode(', ', array_fill(0, count($values), '?'));

        $query .= ')';

        $raw_query .= ')';

        if (true === $overwrite) {

            $append = ' ON DUPLICATE KEY UPDATE ';

            foreach (array_keys($values) as $value) {

                $append .= $value . ' = VALUES(' . $value . '), ';

            }

            $append = rtrim($append, ', ');

            $query .= $append;

            $raw_query .= $append;

        }

        $this->raw_query = $raw_query; // Save raw query

        $this->stmt = $this->prepare($query);

        $return = $this->stmt->execute(array_values($values));

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $return;

    }

    /**
     * Updates an existing row.
     *
     * @param string $table
     * @param array $values
     * @param array $conditions (Where key = value)
     * @return bool
     */
    public function update(string $table, array $values, array $conditions): bool
    {

        $this->reset();

        $query = 'UPDATE ' . $table . ' SET ';

        $raw_query = $query;

        foreach ($values as $k => $v) {

            if ($v === null) {

                $query .= $k . '= NULL, ';
                $raw_query .= $k . '= NULL, ';
                unset($values[$k]);

            } else {

                $query .= $k . '=?, ';
                $raw_query .= $k . '=' . $v . ', ';

            }

        }

        $query = rtrim($query, ', ') . ' WHERE ';

        $raw_query = rtrim($raw_query, ', ') . ' WHERE ';

        foreach ($conditions as $k => $v) {

            if ($v === null) {

                $query .= $k . ' IS NULL AND ';
                $raw_query .= $k . ' IS NULL AND ';
                unset($conditions[$k]);

            } else {

                $query .= $k . '=? AND ';
                $raw_query .= $k . '=' . $v . ' AND ';

            }

        }

        $query = rtrim($query, ' AND');

        $raw_query = rtrim($raw_query, ' AND');

        $this->raw_query = $raw_query; // Save raw query

        $placeholders = array_values($values);

        foreach ($conditions as $condition) {
            $placeholders[] = $condition;
        }

        $this->stmt = $this->prepare($query);

        $this->stmt->execute(array_values($placeholders));

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $this->stmt->rowCount() > 0; // Return bool if rows were actually updated

    }

    /**
     * Deletes row(s).
     *
     * NOTE: Leaving the $conditions array empty will delete all rows of the table, so use with caution!
     *
     * For this reason, the $conditions array must be passed to this method as an added precaution.
     *
     * @param string $table
     * @param array $conditions (Where key = value)
     * @return bool
     * @noinspection DuplicatedCode
     */
    public function delete(string $table, array $conditions): bool
    {

        $this->reset();

        /** @noinspection SqlWithoutWhere */

        $query = 'DELETE FROM ' . $table;

        $raw_query = $query;

        if (!empty($conditions)) {

            $query .= ' WHERE ';

            $raw_query .= ' WHERE ';

            foreach ($conditions as $k => $v) {

                if ($v === null) {

                    $query .= $k . ' IS NULL AND ';
                    $raw_query .= $k . ' IS NULL AND ';
                    unset($conditions[$k]);

                } else {

                    $query .= $k . '=? AND ';
                    $raw_query .= $k . '=' . $v . ' AND ';

                }

            }

            $query = rtrim($query, ' AND');

            $raw_query = rtrim($raw_query, ' AND');

        }

        $this->raw_query = $raw_query; // Save raw query

        $this->stmt = $this->prepare($query);

        $this->stmt->execute(array_values($conditions));

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $this->stmt->rowCount() > 0; // Return bool if rows were actually deleted

    }

    /**
     * Returns number of rows in a table that matches given conditions.
     *
     * @param string $table
     * @param array $conditions (Where key = value)
     * @return int
     *
     * @noinspection DuplicatedCode
     */
    public function count(string $table, array $conditions = []): int
    {

        $this->reset();

        $query = 'SELECT COUNT(*) FROM ' . $table;

        $raw_query = $query;

        if (!empty($conditions)) {

            $query .= ' WHERE ';

            $raw_query .= ' WHERE ';

            foreach ($conditions as $k => $v) {

                if ($v === null) {

                    $query .= $k . ' IS NULL AND ';
                    $raw_query .= $k . ' IS NULL AND ';
                    unset($conditions[$k]);

                } else {

                    $query .= $k . '=? AND ';
                    $raw_query .= $k . '=' . $v . ' AND ';

                }

            }

            $query = rtrim($query, ' AND');

            $raw_query = rtrim($raw_query, ' AND');

        }

        $this->raw_query = $raw_query; // Save raw query

        $this->stmt = $this->prepare($query);

        $this->stmt->execute(array_values($conditions));

        $return = (int)$this->stmt->fetchColumn();

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $return;

    }

    /**
     * Checks if rows exist in a table that matches given conditions.
     *
     * @param string $table
     * @param array $conditions (Where key = value)
     * @return bool
     *
     */
    public function exists(string $table, array $conditions = []): bool
    {

        if ($this->count($table, $conditions) > 0) {
            return true;
        }

        return false;

    }

    /**
     * Returns sum of column in a table that matches given conditions.
     *
     * @param string $table
     * @param string $column
     * @param array $conditions (Where key = value)
     * @return int
     * @noinspection DuplicatedCode
     */
    public function sum(string $table, string $column, array $conditions = []): int
    {

        $this->reset();

        $query = 'SELECT SUM(' . $column . ') FROM ' . $table;

        $raw_query = $query;

        if (!empty($conditions)) {

            $query .= ' WHERE ';

            $raw_query .= ' WHERE ';

            foreach ($conditions as $k => $v) {

                if ($v === null) {

                    $query .= $k . ' IS NULL AND ';
                    $raw_query .= $k . ' IS NULL AND ';
                    unset($conditions[$k]);

                } else {

                    $query .= $k . '=? AND ';
                    $raw_query .= $k . '=' . $v . ' AND ';

                }

            }

            $query = rtrim($query, ' AND');

            $raw_query = rtrim($raw_query, ' AND');

        }

        $this->raw_query = $raw_query; // Save raw query

        $this->stmt = $this->prepare($query);

        $this->stmt->execute(array_values($conditions));

        $return = (int)$this->stmt->fetchColumn();

        $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

        return $return;

    }

    /**
     * Begins a transaction.
     *
     * Once a transaction has begun, all database modifications across multiple queries
     * will be rolled back if any fail, or if cancelled by calling cancelTransaction().
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return self::$db_connections[$this->current_db_name]->beginTransaction();
    }

    /**
     * Commits a transaction.
     *
     * @return bool
     */
    public function commitTransaction(): bool
    {
        return self::$db_connections[$this->current_db_name]->commit();
    }

    /**
     * Cancels a transaction which has begun, and rolls back any modifications
     * since the transaction began.
     *
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        return self::$db_connections[$this->current_db_name]->rollback();
    }

    /*
     * |--------------------------------------------------------------------------
     * | Query information
     * |--------------------------------------------------------------------------
     */

    /**
     * Returns last raw query.
     *
     * @return string
     */
    public function getLastQuery(): string
    {
        return $this->raw_query;
    }

    /**
     * Returns last query parameters.
     *
     * @return array
     */
    public function getLastParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Returns the number of rows affected by the last statement.
     *
     * @return int
     */
    public function rowCount(): int
    {

        if (NULL === $this->stmt) {
            return 0;
        }

        return $this->stmt->rowCount();

    }

    /**
     * Returns the ID of the last inserted row.
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return self::$db_connections[$this->current_db_name]->lastInsertId();
    }

    /**
     * Add query time to be tracked using getQueryTime and getTotalQueries.
     * This is helpful to track queries using the query builder.
     *
     * @param string $db_name
     * @param float $duration (Microseconds as float)
     * @return void
     */
    public function setQueryTime(string $db_name, float $duration = 0): void
    {
        $this->query_durations[$db_name][] = $duration;
    }

    /**
     * Returns the total time elapsed in seconds for all queries executed for a given database.
     *
     * @param int $decimals (Number of decimal points to return)
     * @param string $db_name (Leaving this parameter blank will return the time elapsed for all database connections)
     * @return float
     */
    public function getQueryTime(int $decimals = 3, string $db_name = ''): float
    {

        if ($db_name == '') {
            return number_format((float)array_sum(Arr::dot($this->query_durations)), $decimals);
        } else if (!isset($this->query_durations[$db_name])) {
            return 0;
        }

        return number_format((float)array_sum($this->query_durations[$db_name]), $decimals);
    }

    /**
     * Returns the total number of queries executed for a given database.
     *
     * @param string $db_name (Leaving this parameter blank will return the total queries for all database connections)
     * @return int
     */
    public function getTotalQueries(string $db_name = ''): int
    {

        if ($db_name == '') {
            return count(Arr::dot($this->query_durations));
        } else if (!isset($this->query_durations[$db_name])) {
            return 0;
        }

        return count($this->query_durations[$db_name]);

    }

}