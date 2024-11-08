<?php /** @noinspection PhpUnused */

namespace Bayfront\SimplePdo;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\SimplePdo\Exceptions\QueryException;
use PDO;

class Query
{

    protected PDO $pdo; // Instance

    public function __construct(PDO $pdo)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Throw exceptions
        $this->pdo = $pdo;
    }

    /*
     * $query array keys
     */
    private const QUERY_FROM = 'from';
    private const QUERY_DISTINCT = 'distinct';
    private const QUERY_INNER_JOIN = 'inner_join';
    private const QUERY_LEFT_JOIN = 'left_join';
    private const QUERY_RIGHT_JOIN = 'right_join';
    private const QUERY_COLUMNS = 'columns';
    private const QUERY_WHERE = 'where';
    private const QUERY_GROUP = 'group';
    private const QUERY_SORT = 'sort';
    private const QUERY_LIMIT = 'limit';
    private const QUERY_OFFSET = 'offset';

    private array $query = [];

    private array $placeholders = [];

    private string $table = '';

    /**
     * Define the table to query.
     * @param string $table
     * @return self
     */
    public function table(string $table): self
    {
        $this->query[self::QUERY_FROM] = ' FROM ' . $table;

        $this->table = $table;

        return $this;
    }

    /**
     * Add a DISTINCT clause to the query.
     *
     * @return self
     */
    public function distinct(): self
    {
        $this->query[self::QUERY_DISTINCT] = 'DISTINCT ';
        return $this;
    }

    /**
     * Add INNER JOIN clause to the query.
     *
     * @param string $table
     * @param string $col1
     * @param string $col2
     * @return self
     */
    public function innerJoin(string $table, string $col1, string $col2): self
    {
        $this->query[self::QUERY_INNER_JOIN][] = ' INNER JOIN ' . $table . ' ON ' . $col1 . ' = ' . $col2;
        return $this;
    }

    /**
     * Add LEFT JOIN clause to the query.
     *
     * @param string $table
     * @param string $col1
     * @param string $col2
     * @return self
     */
    public function leftJoin(string $table, string $col1, string $col2): self
    {
        $this->query[self::QUERY_LEFT_JOIN][] = ' LEFT JOIN ' . $table . ' ON ' . $col1 . ' = ' . $col2;
        return $this;
    }

    /**
     * Add RIGHT JOIN clause to the query.
     *
     * @param string $table
     * @param string $col1
     * @param string $col2
     * @return self
     */
    public function rightJoin(string $table, string $col1, string $col2): self
    {
        $this->query[self::QUERY_RIGHT_JOIN][] = ' RIGHT JOIN ' . $table . ' ON ' . $col1 . ' = ' . $col2;
        return $this;
    }

    /**
     * Define column(s) to select.
     *
     * If the column type is JSON, keys from within the JSON string can be selected with the format of COLUMN->KEY.
     * The field will be returned as a multidimensional array.
     * JSON fields which do not exist are returned with a value of null.
     *
     * @param array|string $columns
     * @return self
     */
    public function select(array|string $columns): self
    {

        foreach ((array)$columns as $column) {

            if (!str_contains($column, '.') && $this->table !== '') { // Support for joins
                $column = $this->table . '.' . $column;
            }

            if (str_contains($column, '->')) { // JSON

                $column_parts = explode(' ', $column, 2); // Allow for spaces, such as with "... AS x"

                $json = explode('->', $column_parts[0], 2);

                $column = $json[0] . "->>'$." . str_replace('->', '.', $json[1]) . "'";

                if (isset($column_parts[1])) {
                    $column = $column . " " . $column_parts[1];
                }

            }

            $this->query[self::QUERY_COLUMNS][] = $column;

        }

        return $this;

    }

    /**
     * Is value a MySQL function?
     *
     * @param string $value
     * @return bool
     */
    private function is_function(string $value): bool
    {

        // See: https://dev.mysql.com/doc/refman/8.0/en/built-in-function-reference.html

        $mysql_fxs = [
            'ABS',
            'ADDDATE',
            'ADDTIME',
            'AVG',
            'BIN',
            'BIN_TO_UUID',
            'CAST',
            'CEIL',
            'CEILING',
            'CONVERT',
            'CONVERT_TZ',
            'COUNT',
            'CURDATE',
            'CURRENT_DATE',
            'CURRENT_TIME',
            'CURRENT_TIMESTAMP',
            'CURTIME',
            'DATE',
            'DATE_ADD',
            'DATE_FORMAT',
            'DATE_SUB',
            'DATEDIFF',
            'EXTRACT',
            'FLOOR',
            'FORMAT',
            'GREATEST',
            'HEX',
            'IN',
            'JSON_CONTAINS',
            'JSON_CONTAINS_PATH',
            'JSON_EXTRACT',
            'JSON_INSERT',
            'JSON_REMOVE',
            'JSON_SEARCH',
            'JSON_SET',
            'MATCH',
            'MAX',
            'MD5',
            'NOW',
            'RAND',
            'ROUND',
            'SUM',
            'TIME',
            'TIME_FORMAT',
            'TIME_TO_SEC',
            'TIMEDIFF',
            'TIMESTAMP',
            'TIMESTAMPADD',
            'TIMESTAMPDIFF',
            'UUID',
            'UUID_SHORT',
            'UUID_TO_BIN'
        ];

        foreach ($mysql_fxs as $fn) {
            if (str_starts_with($value . '(', $fn)) {
                return true;
            }
        }

        return false;

    }

    /**
     * Parse column.
     *
     * @param string $column
     * @return string
     */
    private function parseConditionColumn(string $column): string
    {

        if (!str_contains($column, '.') && $this->table !== '') { // Support for joins
            $column = $this->table . '.' . $column;
        }

        if (str_contains($column, '->')) { // JSON

            $json = explode('->', $column, 2);
            $column = $json[0] . "->>'$." . str_replace('->', '.', $json[1]) . "'";

        }

        return $column;

    }

    /*
     * Operators
     */
    public const OPERATOR_EQUALS = 'eq';
    public const OPERATOR_DOES_NOT_EQUAL = '!eq';
    public const OPERATOR_LESS_THAN = 'lt';
    public const OPERATOR_GREATER_THAN = 'gt';
    public const OPERATOR_LESS_THAN_OR_EQUAL = 'le';
    public const OPERATOR_GREATER_THAN_OR_EQUAL = 'ge';
    public const OPERATOR_STARTS_WITH = 'sw';
    public const OPERATOR_DOES_NOT_START_WITH = '!sw';
    public const OPERATOR_STARTS_WITH_INSENSITIVE = 'isw';
    public const OPERATOR_DOES_NOT_START_WITH_INSENSITIVE = '!isw';
    public const OPERATOR_ENDS_WITH = 'ew';
    public const OPERATOR_DOES_NOT_END_WITH = '!ew';
    public const OPERATOR_ENDS_WITH_INSENSITIVE = 'iew';
    public const OPERATOR_DOES_NOT_END_WITH_INSENSITIVE = '!iew';
    public const OPERATOR_HAS = 'has';
    public const OPERATOR_DOES_NOT_HAVE = '!has';
    public const OPERATOR_HAS_INSENSITIVE = 'ihas';
    public const OPERATOR_DOES_NOT_HAVE_INSENSITIVE = '!ihas';
    public const OPERATOR_IN = 'in';
    public const OPERATOR_NOT_IN = '!in';
    public const OPERATOR_NULL = 'null';
    public const OPERATOR_NOT_NULL = '!null';

    public const VALUE_TRUE = 'true';
    public const VALUE_FALSE = 'false';

    public const CONDITION_AND = 'AND';
    public const CONDITION_OR = 'OR';

    /**
     * @param string $condition (and/or)
     * @param string $column
     * @param string $operator
     * @param $value
     * @return void
     * @throws QueryException
     */
    private function addCondition(string $condition, string $column, string $operator, $value): void
    {

        if (!isset($this->query[self::QUERY_WHERE])) {
            $condition = ' WHERE ';
        } else if (str_ends_with($this->query[self::QUERY_WHERE], '(')) {
            $condition = '';
        } else {
            $condition = ' ' . $condition . ' ';
        }

        if (!in_array($operator, [
            self::OPERATOR_EQUALS,
            self::OPERATOR_DOES_NOT_EQUAL,
            self::OPERATOR_LESS_THAN,
            self::OPERATOR_GREATER_THAN,
            self::OPERATOR_LESS_THAN_OR_EQUAL,
            self::OPERATOR_GREATER_THAN_OR_EQUAL,
            self::OPERATOR_STARTS_WITH,
            self::OPERATOR_DOES_NOT_START_WITH,
            self::OPERATOR_STARTS_WITH_INSENSITIVE,
            self::OPERATOR_DOES_NOT_START_WITH_INSENSITIVE,
            self::OPERATOR_ENDS_WITH,
            self::OPERATOR_DOES_NOT_END_WITH,
            self::OPERATOR_ENDS_WITH_INSENSITIVE,
            self::OPERATOR_DOES_NOT_END_WITH_INSENSITIVE,
            self::OPERATOR_HAS,
            self::OPERATOR_DOES_NOT_HAVE,
            self::OPERATOR_HAS_INSENSITIVE,
            self::OPERATOR_DOES_NOT_HAVE_INSENSITIVE,
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
            self::OPERATOR_NULL,
            self::OPERATOR_NOT_NULL
        ])) {
            throw new QueryException('Unable to build query: invalid operator (' . $operator . ') for column (' . $column . ')');
        }

        $operator = str_replace([
            self::OPERATOR_EQUALS,
            self::OPERATOR_DOES_NOT_EQUAL,
            self::OPERATOR_LESS_THAN,
            self::OPERATOR_GREATER_THAN,
            self::OPERATOR_LESS_THAN_OR_EQUAL,
            self::OPERATOR_GREATER_THAN_OR_EQUAL
        ], [
            '=',
            '!=',
            '<',
            '>',
            '<=',
            '>='
        ], $operator);

        $column = $this->parseConditionColumn($column);

        // Check operators

        $placeholders = [];

        switch ($operator) {

            case self::OPERATOR_STARTS_WITH:

                if ($this->is_function($value)) {
                    $condition .= 'BINARY ' . $column . ' LIKE ' . $value;
                    break;
                }

                $placeholders[] = $value . '%';
                $condition .= 'BINARY ' . $column . ' LIKE ?';
                break;

            case self::OPERATOR_DOES_NOT_START_WITH:

                if ($this->is_function($value)) {
                    $condition .= 'BINARY ' . $column . ' NOT LIKE ' . $value;
                    break;
                }

                $placeholders[] = $value . '%';
                $condition .= 'BINARY ' . $column . ' NOT LIKE ?';
                break;

            case self::OPERATOR_STARTS_WITH_INSENSITIVE:

                if ($this->is_function($value)) {
                    $condition .= $column . ' LIKE ' . $value;
                    break;
                }

                $placeholders[] = $value . '%';
                $condition .= $column . ' LIKE ?';
                break;

            case self::OPERATOR_DOES_NOT_START_WITH_INSENSITIVE:

                if ($this->is_function($value)) {
                    $condition .= $column . ' NOT LIKE ' . $value;
                    break;
                }

                $placeholders[] = $value . '%';
                $condition .= $column . ' NOT LIKE ?';
                break;

            case self::OPERATOR_ENDS_WITH:

                if ($this->is_function($value)) {
                    $condition .= 'BINARY ' . $column . ' LIKE ' . $value;
                    break;
                }

                $placeholders[] = '%' . $value;
                $condition .= 'BINARY ' . $column . ' LIKE ?';
                break;

            case self::OPERATOR_DOES_NOT_END_WITH:

                if ($this->is_function($value)) {
                    $condition .= 'BINARY ' . $column . ' NOT LIKE ' . $value;
                    break;
                }

                $placeholders[] = '%' . $value;
                $condition .= 'BINARY ' . $column . ' NOT LIKE ?';
                break;

            case self::OPERATOR_ENDS_WITH_INSENSITIVE:

                if ($this->is_function($value)) {
                    $condition .= $column . ' LIKE ' . $value;
                    break;
                }

                $placeholders[] = '%' . $value;
                $condition .= $column . ' LIKE ?';
                break;

            case self::OPERATOR_DOES_NOT_END_WITH_INSENSITIVE:

                if ($this->is_function($value)) {
                    $condition .= $column . ' NOT LIKE ' . $value;
                    break;
                }

                $placeholders[] = '%' . $value;
                $condition .= $column . ' NOT LIKE ?';
                break;

            case self::OPERATOR_HAS:

                if ($this->is_function($value)) {
                    $condition .= 'BINARY ' . $column . ' LIKE ' . $value;
                    break;
                }

                $placeholders[] = '%' . $value . '%';
                $condition .= 'BINARY ' . $column . ' LIKE ?';
                break;

            case self::OPERATOR_DOES_NOT_HAVE:

                if ($this->is_function($value)) {
                    $condition .= 'BINARY ' . $column . ' NOT LIKE ' . $value;
                    break;
                }

                $placeholders[] = '%' . $value . '%';
                $condition .= 'BINARY ' . $column . ' NOT LIKE ?';
                break;

            case self::OPERATOR_HAS_INSENSITIVE:

                if ($this->is_function($value)) {
                    $condition .= $column . ' LIKE ' . $value;
                    break;
                }

                $placeholders[] = '%' . $value . '%';
                $condition .= $column . ' LIKE ?';
                break;

            case self::OPERATOR_DOES_NOT_HAVE_INSENSITIVE:

                if ($this->is_function($value)) {
                    $condition .= $column . ' NOT LIKE ' . $value;
                    break;
                }

                $placeholders[] = '%' . $value . '%';
                $condition .= $column . ' NOT LIKE ?';
                break;

            case self::OPERATOR_IN:

                if ($this->is_function($value)) {
                    $condition .= $column . ' IN (' . $value . ')';
                    break;
                }

                $in_values = explode(',', $value);

                $in = str_repeat('?,', count($in_values) - 1) . '?';

                foreach ($in_values as $val) {

                    $placeholders[] = $val;

                }

                $condition .= $column . ' IN (' . $in . ')';

                break;

            case self::OPERATOR_NOT_IN:

                if ($this->is_function($value)) {
                    $condition .= $column . ' NOT IN (' . $value . ')';
                    break;
                }

                $in_values = explode(',', $value);

                $in = str_repeat('?,', count($in_values) - 1) . '?';

                foreach ($in_values as $val) {

                    $placeholders[] = $val;

                }

                $condition .= $column . ' NOT IN (' . $in . ')';

                break;

            case self::OPERATOR_NULL:

                if ($value == self::VALUE_TRUE) {

                    $condition .= $column . ' IS NULL';

                } else if ($value == self::VALUE_FALSE) {

                    $condition .= $column . ' IS NOT NULL';

                } else {
                    throw new QueryException('Unable to build query: invalid value (' . $value . ') for operator (' . self::OPERATOR_NULL . ')');
                }

                break;

            case self::OPERATOR_NOT_NULL:

                if ($value == self::VALUE_TRUE) {

                    $condition .= $column . ' IS NOT NULL';

                } else if ($value == self::VALUE_FALSE) {

                    $condition .= $column . ' IS NULL';

                } else {
                    throw new QueryException('Unable to build query: invalid value (' . $value . ') for operator (' . self::OPERATOR_NOT_NULL . ')');
                }

                break;

            default:

                if ($value == '') { // Empty string needs no placeholder

                    $condition .= $column . " " . $operator . " ''";

                } else if ($this->is_function($value)) {

                    $condition .= $column . ' ' . $operator . ' ' . $value;

                } else {

                    $placeholders[] = $value;
                    $condition .= $column . ' ' . $operator . ' ?';

                }

        }

        if (!isset($this->query[self::QUERY_WHERE])) {
            //$this->query[self::QUERY_WHERE] = $condition . ')';
            $this->query[self::QUERY_WHERE] = $condition;
        } else {
            //$this->query[self::QUERY_WHERE] .= $condition . ')';
            $this->query[self::QUERY_WHERE] .= $condition;
        }

        $this->placeholders = array_merge($this->placeholders, $placeholders);

    }

    /**
     * Adds a WHERE/AND WHERE clause to the query.
     *
     * If the column type is JSON, keys from within the JSON string can be searched with the format of COLUMN->KEY.
     * JSON fields which do not exist are treated as null.
     *
     * Available operators are:
     *
     * - eq (equals)
     * - !eq (does not equal)
     * - lt (less than)
     * - gt (greater than)
     * - le (less than or equal to)
     * - ge (greater than or equal to)
     * - sw (starts with)
     * - !sw (does not start with)
     * - isw (starts with - case-insensitive)
     * - !isw (does not start with - case-insensitive)
     * - ew (ends with)
     * - !ew (does not end with)
     * - iew (ends with - case-insensitive)
     * - !iew (does not end with - case-insensitive)
     * - has (has)
     * - !has (does not have)
     * - ihas (has - case-insensitive)
     * - !ihas (does not have - case-insensitive)
     * - in (in)
     * - !in (not in)
     * - null (null)
     * - !null (not null)
     *
     * The OPERATOR_* constants can be used for this purpose.
     *
     * The in and !in operators accept multiple comma-separated values.
     *
     * The "null" and "!null" operators accept one of two values: "true" and "false".
     * The VALUE_* constants can be used for this purpose.
     *
     * NOTE: Some native MySQL functions can be used as the $value, however, they will be
     * injected into the query as strings, so they can be vulnerable to SQL injection.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return self
     * @throws QueryException
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->addCondition(self::CONDITION_AND, $column, $operator, $value);
        return $this;
    }

    /**
     * Adds an OR/AND OR clause to the query.
     *
     * See where().
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     * @throws QueryException
     */
    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->addCondition(self::CONDITION_OR, $column, $operator, $value);
        return $this;
    }

    /**
     * Start new clause with opening parentheses.
     *
     * @param string $condition
     * @return $this
     * @throws QueryException
     */
    public function startGroup(string $condition): self
    {

        if ($condition !== self::CONDITION_AND && $condition !== self::CONDITION_OR) {
            throw new QueryException('Unable to build query: invalid condition (' . $condition . ')');
        }

        if (!isset($this->query[self::QUERY_WHERE])) {
            $this->query[self::QUERY_WHERE] = ' WHERE (';
        } else {
            $this->query[self::QUERY_WHERE] .= ' ' . $condition . ' (';
        }

        return $this;

    }

    /**
     * End clause with closing parentheses.
     *
     * @return $this
     */
    public function endGroup(): self
    {

        if (isset($this->query[self::QUERY_WHERE])) {
            $this->query[self::QUERY_WHERE] .= ')';
        }

        return $this;

    }

    /**
     * Adds a GROUP BY clause.
     *
     * @param array $columns
     * @return self
     */
    public function groupBy(array $columns): self
    {

        if (empty($columns)) {
            return $this;
        }

        $string = ' GROUP BY ' . implode(', ', $columns);

        $this->query[self::QUERY_GROUP] = rtrim($string, ', ');

        return $this;

    }

    /**
     * Adds an ORDER BY clause.
     *
     * Values in the $columns array without a prefix or prefixed with a "+" will be ordered ascending.
     * Values in the $columns array prefixed with a "-" will be ordered descending.
     *
     * If the column type is JSON, keys from within the JSON string can be ordered with the format of COLUMN->KEY.
     * JSON fields which do not exist are treated as null.
     *
     * @param array $columns
     * @return self
     */
    public function orderBy(array $columns): self
    {

        if (empty($columns)) {
            return $this;
        }

        $string = ' ORDER BY ';

        foreach ($columns as $column) {

            if (str_starts_with($column, '-')) {

                $column = ltrim($column, '-');

                $column = $this->parseConditionColumn($column);

                $string .= $column . ' DESC, ';

            } else {

                /*
                 * The + character may have been interpreted as a space if the URL
                 * was not encoded. Therefore, spaces must be trimmed from this string.
                 */

                $column = ltrim(ltrim($column, '+'), ' ');

                $column = $this->parseConditionColumn($column);

                $string .= $column . ' ASC, ';

            }

        }

        $this->query[self::QUERY_SORT] = rtrim($string, ', ');

        return $this;

    }

    /**
     * Adds an ORDER BY RAND() clause.
     *
     * @return self
     */
    public function orderByRand(): self
    {
        $this->query[self::QUERY_SORT] = ' ORDER BY RAND()';
        return $this;
    }

    /**
     * Adds a LIMIT clause.
     *
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->query[self::QUERY_LIMIT] = ' LIMIT ' . $limit;
        return $this;
    }

    /**
     * Adds an OFFSET clause.
     *
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->query[self::QUERY_OFFSET] = ' OFFSET ' . $offset;
        return $this;
    }

    /**
     * Build the query.
     *
     * @return string
     */
    protected function getQuery(): string
    {

        return 'SELECT ' . Arr::get($this->query, self::QUERY_DISTINCT, '')
            . implode(', ', Arr::get($this->query, self::QUERY_COLUMNS, []))
            . Arr::get($this->query, self::QUERY_FROM, '')
            . implode('', Arr::get($this->query, self::QUERY_INNER_JOIN, []))
            . implode('', Arr::get($this->query, self::QUERY_LEFT_JOIN, []))
            . implode('', Arr::get($this->query, self::QUERY_RIGHT_JOIN, []))
            . Arr::get($this->query, self::QUERY_WHERE, '')
            . Arr::get($this->query, self::QUERY_GROUP, '')
            . Arr::get($this->query, self::QUERY_SORT, '')
            . Arr::get($this->query, self::QUERY_LIMIT, '')
            . Arr::get($this->query, self::QUERY_OFFSET, '');

    }

    /**
     * Format the result of a query.
     *
     * @param array $result
     * @return array
     */
    private function formatResult(array $result): array
    {

        foreach ($result as $k => $v) {

            if (str_contains($k, "->>'$.")) { // JSON

                $arr = explode("->>'$.", rtrim($k, "'"), 2);
                $col = $arr[0];

                $col_exp = explode('.', $col, 2);

                if (isset($col_exp[1])) {
                    $col = $col_exp[1];
                }

                Arr::set($result, $col . '.' . $arr[1], $v);
                unset($result[$k]);

            }

        }

        return $result;

    }

    /**
     * Get the result set from a table.
     *
     * @return array
     */
    public function get(): array
    {
        $stmt = $this->pdo->prepare($this->getQuery());

        $stmt->execute($this->placeholders);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (is_array($result)) {

            foreach ($result as $k => $v) {
                $result[$k] = $this->formatResult($v);
            }

        }

        return $result;

    }

    /**
     * Get a single row from a table, or false on failure.
     *
     * @return mixed
     */
    public function row(): mixed
    {
        $stmt = $this->pdo->prepare($this->getQuery());

        $stmt->execute($this->placeholders);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (is_array($result)) {
            return $this->formatResult($result);
        }

        return $result;

    }

    /**
     * Get a single column of a single row of a table, or false if not existing.
     *
     * @return mixed
     */
    public function single(): mixed
    {
        $stmt = $this->pdo->prepare($this->getQuery());

        $stmt->execute($this->placeholders);

        return $stmt->fetchColumn();
    }

    /**
     * Returns last raw query.
     *
     * @return string
     */
    public function getLastQuery(): string
    {
        return $this->getQuery();
    }

    /**
     * Returns last query parameters.
     *
     * @return array
     */
    public function getLastParameters(): array
    {
        return $this->placeholders;
    }

    public const AGGREGATE_AVG = 'AVG';
    public const AGGREGATE_AVG_DISTINCT = 'AVG_DISTINCT';
    public const AGGREGATE_COUNT = 'COUNT';
    public const AGGREGATE_COUNT_DISTINCT = 'COUNT_DISTINCT';
    public const AGGREGATE_MAX = 'MAX';
    public const AGGREGATE_MIN = 'MIN';
    public const AGGREGATE_SUM = 'SUM';
    public const AGGREGATE_SUM_DISTINCT = 'SUM_DISTINCT';

    /**
     * Return calculation of an aggregate function.
     *
     * @param string $aggregate (Any AGGREGATE_* constant)
     * @param string $column
     * @param int $decimals
     * @return float
     */
    public function aggregate(string $aggregate, string $column = '*', int $decimals = 2): float
    {

        $exp = explode('_', $aggregate, 2);

        if (isset($exp[1])) {
            $select = $exp[0] . '(' . $exp[1] . ' ' . $column . ')';
        } else {
            $select = $exp[0] . '(' . $column . ')';
        }

        $query = 'SELECT ' . $select
            . Arr::get($this->query, self::QUERY_FROM, '')
            . implode('', Arr::get($this->query, self::QUERY_INNER_JOIN, []))
            . implode('', Arr::get($this->query, self::QUERY_LEFT_JOIN, []))
            . implode('', Arr::get($this->query, self::QUERY_RIGHT_JOIN, []))
            . Arr::get($this->query, self::QUERY_WHERE, '')
            . Arr::get($this->query, self::QUERY_GROUP, '')
            . Arr::get($this->query, self::QUERY_SORT, '');

        $stmt = $this->pdo->prepare($query);

        $stmt->execute($this->placeholders);

        return round((float)$stmt->fetchColumn(), $decimals);

    }

    /**
     * Returns total number of rows found for the query without limit restrictions.
     *
     * NOTE: To get the number of rows affected by a DELETE, use the Bayfront\SimplePdo\Db->rowCount() method.
     *
     * @deprecated Depreciated in favor of aggregate()
     * @return int
     */
    public function getTotalRows(): int
    {
        return (int)$this->aggregate(self::AGGREGATE_COUNT);
    }

}