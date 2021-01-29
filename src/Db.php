<?php

/**
 * @package simple-pdo
 * @link https://github.com/bayfrontmedia/simple-pdo
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\PDO;

use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\PDO\Exceptions\TransactionException;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

class Db
{

    private static $db_connections = []; // Db connections as PDO objects

    private $default_db_name;

    private $current_db_name;

    /**
     * Constructor.
     *
     * Sets given PDO instance as current and default database.
     *
     * @param PDO $pdo
     * @param string $db_name
     */

    public function __construct(PDO $pdo, string $db_name = 'default')
    {

        self::$db_connections[$db_name] = $pdo;

        $this->default_db_name = $db_name;

        $this->current_db_name = $db_name;

    }

    public function __destruct()
    {
        $this->_disconnectAll();
    }

    /**
     * Disconnects from all databases.
     *
     * @return void
     */

    private function _disconnectAll(): void
    {

        foreach (self::$db_connections as $k => $connection) {

            self::$db_connections[$k] = NULL;

            unset(self::$db_connections[$k]);

        }

    }

    /**
     * Returns PDO object for current database name and resets current connection to default.
     *
     * @return PDO
     *
     * @throws InvalidDatabaseException
     */

    private function _getConnection(): PDO
    {

        if (!isset(self::$db_connections[$this->current_db_name])) {

            throw new InvalidDatabaseException('Database connection has not been setup');

        }

        $current = self::$db_connections[$this->current_db_name];

        $this->current_db_name = $this->default_db_name; // Reset current connection to default

        return $current;

    }

    /*
     * ############################################################
     * Database connections
     * ############################################################
     */

    /**
     * Add a PDO instance.
     *
     * @param PDO $pdo
     * @param string $db_name (Name must be unique)
     * @param bool $make_current
     * @param bool $make_default
     *
     * @return self
     *
     * @throws InvalidDatabaseException
     */

    public function add(PDO $pdo, string $db_name, bool $make_current = false, bool $make_default = false): self
    {

        if (isset(self::$db_connections[$db_name])) {

            throw new InvalidDatabaseException('Database name already used');

        }

        self::$db_connections[$db_name] = $pdo;

        if (true === $make_current) {

            $this->current_db_name = $db_name;

        }

        if (true === $make_default) {

            $this->default_db_name = $db_name;

        }

        return $this;

    }

    /**
     * Set given database name as current.
     *
     * After the next query, the current database will automatically revert to the default database.
     *
     * @param string $db_name
     * @param bool $make_default
     *
     * @return self
     *
     * @throws InvalidDatabaseException
     */

    public function use(string $db_name, bool $make_default = false): self
    {

        if (!isset(self::$db_connections[$db_name])) {

            throw new InvalidDatabaseException('Database is not defined');

        }

        if (true === $make_default) {

            $this->default_db_name = $db_name;

        }

        $this->current_db_name = $db_name;

        return $this;

    }

    /**
     * Returns the raw PDO instance of a given database.
     *
     * @param string|null $db_name (Not specifying this parameter will return the PDO instance of the current database)
     *
     * @return PDO
     *
     * @throws InvalidDatabaseException
     */

    public function get(string $db_name = NULL): PDO
    {

        if (NULL === $db_name) {
            $db_name = $this->current_db_name;
        }

        if (isset(self::$db_connections[$db_name])) {

            return self::$db_connections[$db_name];

        }

        throw new InvalidDatabaseException('Database is not defined');

    }

    /**
     * Returns name of the default database.
     *
     * @return string
     */

    public function getDefault(): string
    {
        return $this->default_db_name;
    }

    /**
     * Returns name of the database currently being used.
     *
     * @return string
     */

    public function getCurrent(): string
    {
        return $this->current_db_name;
    }

    /**
     * Returns array of all database connection names.
     *
     * @return array
     */

    public function getConnections(): array
    {
        return array_keys(self::$db_connections);
    }

    /**
     * Checks if connected to a given database name.
     *
     * @param string $db_name
     *
     * @return bool
     */

    public function isConnected(string $db_name): bool
    {
        return isset(self::$db_connections[$db_name]);
    }

    /*
     * ############################################################
     * Queries
     * ############################################################
     */

    private $query_start; // Microtime for query start

    private $stmt; // PDOStatement object

    private $raw_query = ''; // Last raw query

    private $query_durations = []; // Records duration to execute each query

    /**
     * Resets all query-specific data and begins timer for current query.
     *
     * @return void
     */

    private function _beginQuery(): void
    {

        $this->query_start = microtime(true);

        $this->stmt = NULL;

        $this->raw_query = '';

    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $query
     *
     * @return PDOStatement
     *
     * @throws InvalidDatabaseException
     */

    private function _prepare(string $query): PDOStatement
    {
        return $this->_getConnection()->prepare($query); // PDOStatement object
    }

    /**
     * Binds multiple values to parameters from placeholders,
     * and sets the most applicable data type for the parameter.
     *
     * @param array $params
     *
     * @return void
     */

    private function _bindParams(array $params): void
    {

        if (!empty($params)) {

            foreach ($params as $placeholder => $value) {

                if (is_int($placeholder)) {
                    $placeholder = $placeholder + 1;
                } else {
                    $placeholder = ':' . $placeholder;
                }

                switch ($value) {

                    case is_bool($value):

                        $type = PDO::PARAM_BOOL;
                        break;

                    case is_null($value):

                        $type = PDO::PARAM_NULL;
                        break;

                    case is_int($value):

                        $type = PDO::PARAM_INT;
                        break;

                    default:

                        $type = PDO::PARAM_STR;

                }

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
     *
     * @return bool
     *
     * @throws QueryException
     *
     */

    public function query(string $query, array $params = []): bool
    {

        try {

            $this->_beginQuery();

            $this->stmt = $this->_prepare($query);

            $this->raw_query = $query;

            $this->_bindParams($params);

            $return = $this->stmt->execute();

            $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

            return $return;

        } catch (Exception $e) {

            throw new QueryException('Unable to execute query method', 0, $e);

        }

    }

    /**
     * Returns the result set from a table, or false on failure.
     *
     * @param string $query
     * @param array $params
     * @param bool $return_array (When false, the result set will be returned as an object)
     *
     * @return mixed
     *
     * @throws QueryException
     */

    public function select(string $query, array $params = [], bool $return_array = true)
    {

        try {

            $this->query($query, $params);

            if (true == $return_array) {

                return $this->stmt->fetchAll(PDO::FETCH_ASSOC);

            }

            return $this->stmt->fetchAll(PDO::FETCH_OBJ);

        } catch (Exception $e) {

            throw new QueryException('Unable to execute select method', 0, $e);

        }

    }

    /**
     * Returns a single row from a table, or false on failure.
     *
     * @param string $query
     * @param array $params
     * @param bool $return_array (When false, the result set will be returned as an object)
     *
     * @return mixed
     *
     * @throws QueryException
     */

    public function row(string $query, array $params = [], bool $return_array = true)
    {

        try {

            $this->query($query, $params);

            if (true == $return_array) {

                return $this->stmt->fetch(PDO::FETCH_ASSOC);

            }

            return $this->stmt->fetch(PDO::FETCH_OBJ);

        } catch (Exception $e) {

            throw new QueryException('Unable to execute row method', 0, $e);

        }

    }

    /**
     * Returns a single column from a single row of a table, or false if not existing.
     *
     * @param string $query
     * @param array $params
     *
     * @return mixed
     *
     * @throws QueryException
     */

    public function single(string $query, array $params = [])
    {

        try {

            $this->query($query, $params);

            return $this->stmt->fetchColumn();

        } catch (Exception $e) {

            throw new QueryException('Unable to execute single method', 0, $e);

        }

    }

    /**
     * Inserts a new row.
     *
     * @param string $table
     * @param array $values
     * @param bool $overwrite (Overwrite preexisting values if they exist)
     *
     * @return bool
     *
     * @throws QueryException
     */

    public function insert(string $table, array $values, bool $overwrite = true): bool
    {

        try {

            $this->_beginQuery();

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

            $this->stmt = $this->_prepare($query);

            $return = $this->stmt->execute(array_values($values));

            $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

            return $return;

        } catch (Exception $e) {

            throw new QueryException('Unable to execute insert method', 0, $e);

        }

    }

    /**
     * Updates an existing row.
     *
     * @param string $table
     * @param array $values
     * @param array $conditions (Where key = value)
     *
     * @return bool
     *
     * @throws QueryException
     */

    public function update(string $table, array $values, array $conditions): bool
    {

        try {

            $this->_beginQuery();

            $query = 'UPDATE ' . $table . ' SET ';

            $raw_query = $query;

            foreach ($values as $k => $v) {

                $query .= $k . '=?, ';

                $raw_query .= $k . '=' . $v . ', ';

            }

            $query = rtrim($query, ', ') . ' WHERE ';

            $raw_query = rtrim($raw_query, ', ') . ' WHERE ';

            foreach ($conditions as $k => $v) {

                $query .= $k . '=? AND ';

                $raw_query .= $k . '=' . $v . ' AND ';

            }

            $query = rtrim($query, ' AND ');

            $raw_query = rtrim($raw_query, ' AND ');

            $this->raw_query = $raw_query; // Save raw query

            $placeholders = array_values($values);

            foreach (array_values($conditions) as $condition) {
                $placeholders[] = $condition;
            }

            $this->stmt = $this->_prepare($query);

            $this->stmt->execute(array_values($placeholders));

            $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

            return $this->stmt->rowCount() > 0; // Return bool if rows were actually updated

        } catch (Exception $e) {

            throw new QueryException('Unable to execute update method', 0, $e);

        }

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
     *
     * @return bool
     *
     * @throws QueryException
     *
     * @noinspection DuplicatedCode
     */

    public function delete(string $table, array $conditions): bool
    {

        try {

            $this->_beginQuery();

            /** @noinspection SqlWithoutWhere */

            $query = 'DELETE FROM ' . $table;

            $raw_query = $query;

            if (!empty($conditions)) {

                $query .= ' WHERE ';

                $raw_query .= ' WHERE ';

                foreach ($conditions as $k => $v) {

                    $query .= $k . '=? AND ';

                    $raw_query .= $k . '=' . $v . ' AND ';

                }

                $query = rtrim($query, ' AND ');

                $raw_query = rtrim($raw_query, ' AND ');

            }

            $this->raw_query = $raw_query; // Save raw query

            $this->stmt = $this->_prepare($query);

            $this->stmt->execute(array_values($conditions));

            $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

            return $this->stmt->rowCount() > 0; // Return bool if rows were actually deleted

        } catch (Exception $e) {

            throw new QueryException('Unable to execute delete method', 0, $e);

        }

    }

    /**
     * Returns number of rows in a table that matches given conditions.
     *
     * @param string $table
     * @param array $conditions (Where key = value)
     *
     * @return int
     *
     * @throws QueryException
     *
     * @noinspection DuplicatedCode
     */

    public function count(string $table, array $conditions = []): int
    {

        try {

            $this->_beginQuery();

            $query = 'SELECT COUNT(*) FROM ' . $table;

            $raw_query = $query;

            if (!empty($conditions)) {

                $query .= ' WHERE ';

                $raw_query .= ' WHERE ';

                foreach ($conditions as $k => $v) {

                    $query .= $k . '=? AND ';

                    $raw_query .= $k . '=' . $v . ' AND ';

                }

                $query = rtrim($query, ' AND ');

                $raw_query = rtrim($raw_query, ' AND ');

            }

            $this->raw_query = $raw_query; // Save raw query

            $this->stmt = $this->_prepare($query);

            $this->stmt->execute(array_values($conditions));

            $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

            return (int)$this->stmt->fetchColumn();

        } catch (Exception $e) {

            throw new QueryException('Unable to execute count method', 0, $e);

        }

    }

    /**
     * Checks if rows exist in a table that matches given conditions.
     *
     * @param string $table
     * @param array $conditions (Where key = value)
     *
     * @return bool
     *
     * @throws QueryException
     */

    public function exists(string $table, array $conditions = []): bool
    {

        if ($this->count($table, $conditions) > 0) { // throws QueryException
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
     *
     * @return int
     *
     * @throws QueryException
     *
     * @noinspection DuplicatedCode
     */

    public function sum(string $table, string $column, array $conditions = []): int
    {

        try {

            $this->_beginQuery();

            $query = 'SELECT SUM(' . $column . ') FROM ' . $table;

            $raw_query = $query;

            if (!empty($conditions)) {

                $query .= ' WHERE ';

                $raw_query .= ' WHERE ';

                foreach ($conditions as $k => $v) {

                    $query .= $k . '=? AND ';

                    $raw_query .= $k . '=' . $v . ' AND ';

                }

                $query = rtrim($query, ' AND ');

                $raw_query = rtrim($raw_query, ' AND ');

            }

            $this->raw_query = $raw_query; // Save raw query

            $this->stmt = $this->_prepare($query);

            $this->stmt->execute(array_values($conditions));

            $this->query_durations[$this->current_db_name][] = microtime(true) - $this->query_start; // Record query duration

            return (int)$this->stmt->fetchColumn();

        } catch (Exception $e) {

            throw new QueryException('Unable to execute sum method', 0, $e);

        }

    }

    /**
     * Begins a transaction.
     *
     * Once a transaction has begun, all database modifications across multiple queries
     * will be rolled back if any fail, or if cancelled by calling cancelTransaction().
     *
     * @return bool
     *
     * @throws TransactionException
     */

    public function beginTransaction(): bool
    {

        try {

            return self::$db_connections[$this->current_db_name]->beginTransaction();

        } catch (PDOException $e) {

            throw new TransactionException('Unable to begin transaction', 0, $e);

        }

    }

    /**
     * Commits a transaction.
     *
     * @return bool
     *
     * @throws TransactionException
     */

    public function commitTransaction(): bool
    {

        try {

            return self::$db_connections[$this->current_db_name]->commit();

        } catch (PDOException $e) {

            throw new TransactionException('Unable to commit transaction', 0, $e);

        }

    }

    /**
     * Cancels a transaction which has begun, and rolls back any modifications
     * since the transaction began.
     *
     * @return bool
     *
     * @throws TransactionException
     */

    public function rollbackTransaction()
    {

        try {

            return self::$db_connections[$this->current_db_name]->rollback();

        } catch (PDOException $e) {

            throw new TransactionException('Unable to rollback transaction', 0, $e);

        }

    }

    /*
     * ############################################################
     * Query information
     * ############################################################
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
     * Returns the ID of the last inserted row
     *
     * @return string
     */

    public function lastInsertId(): string
    {
        return self::$db_connections[$this->current_db_name]->lastInsertId();
    }

    /**
     * Returns the total time elapsed in seconds for all queries executed for the current database.
     *
     * @param int $decimals (Number of decimal points to return)
     *
     * @return float
     */

    public function getQueryTime(int $decimals = 3): float
    {

        if (!isset($this->query_durations[$this->current_db_name])) {
            return 0;
        }

        return number_format((float)array_sum($this->query_durations[$this->current_db_name]), $decimals);
    }

    /**
     * Returns the total number of queries executed for the current database.
     *
     * @return int
     */

    public function getTotalQueries(): int
    {

        if (!isset($this->query_durations[$this->current_db_name])) {
            return 0;
        }

        return sizeof($this->query_durations[$this->current_db_name]);

    }

}