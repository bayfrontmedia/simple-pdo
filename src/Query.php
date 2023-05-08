<?php

namespace Bayfront\PDO;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\StringHelpers\Str;
use PDO;

class Query
{

    protected PDO $pdo; // Instance

    /*
     * Possible $query keys include:
     *  - from
     *  - distinct
     *  - inner_join
     *  - left_join
     *  - right_join
     *  - columns
     *  - where
     *  - sort
     *  - limit
     *  - offset
     */

    protected array $query = [];

    protected array $placeholders = [];

    public function __construct(PDO $pdo)
    {

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Throw exceptions

        $this->pdo = $pdo;
    }

    /**
     * Define the table to query.
     *
     * @param string $table
     *
     * @return self
     */

    public function table(string $table): self
    {
        $this->query['from'] = ' FROM ' . $table;

        return $this;
    }

    /**
     * Add a DISTINCT clause to the query.
     *
     * @return self
     */

    public function distinct(): self
    {
        $this->query['distinct'] = 'DISTINCT ';

        return $this;
    }

    /**
     * Add INNER JOIN clause to the query.
     *
     * @param string $table
     * @param string $col1
     * @param string $col2
     *
     * @return self
     */

    public function innerJoin(string $table, string $col1, string $col2): self
    {

        $this->query['inner_join'] = ' INNER JOIN ' . $table . ' ON ' . $col1 . ' = ' . $col2;

        return $this;

    }

    /**
     * Add LEFT JOIN clause to the query.
     *
     * @param string $table
     * @param string $col1
     * @param string $col2
     *
     * @return self
     */

    public function leftJoin(string $table, string $col1, string $col2): self
    {

        $this->query['left_join'] = ' LEFT JOIN ' . $table . ' ON ' . $col1 . ' = ' . $col2;

        return $this;

    }

    /**
     * Add RIGHT JOIN clause to the query.
     *
     * @param string $table
     * @param string $col1
     * @param string $col2
     *
     * @return self
     */

    public function rightJoin(string $table, string $col1, string $col2): self
    {

        $this->query['right_join'] = ' RIGHT JOIN ' . $table . ' ON ' . $col1 . ' = ' . $col2;

        return $this;

    }

    /**
     * Define column(s) to select.
     *
     * If the column type is `json`, keys from within the `JSON` string can be selected using dot notation.
     * The field can be selected with the format of `{column}.{key}`.
     * The field will be returned with the format of `{column}_{key}` (Dots are replaced with underscores).
     *
     * @param array|string $columns
     *
     * @return self
     */

    public function select(array|string $columns): self
    {

        foreach ((array)$columns as $column) {

            if (str_contains($column, '.')) { // JSON

                $json = explode('.', $column, 2);

                $column = $json[0] . "->>'$." . $json[1] . "' as " . $json[0] . '_' . str_replace('.', '_', $json[1]);

            }

            $this->query['columns'][] = $column;

        }

        return $this;

    }

    /**
     * Is value a MySQL function?
     *
     * @param string $value
     * @return bool
     */
    protected function is_function(string $value): bool
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
     * Adds a WHERE clause to the query.
     *
     * If the column type is `json`, keys from within the `JSON` string can be searched using dot notation.
     * The field can be searched with the format of `{column}.{key}`.
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
     * - ew (ends with)
     * - !ew (does not end with)
     * - has (has)
     * - !has (does not have)
     * - in (in)
     * - !in (not in)
     * - null (is or is not null)
     *
     * The "null" operator accepts two values: true and false for is null or is not null.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     *
     * @return self
     *
     * @throws QueryException
     */

    public function where(string $column, string $operator, mixed $value): self
    {

        if (!isset($this->query['where'])) {
            $this->query['where'] = ' WHERE ';
        } else {
            $this->query['where'] .= ' AND ';
        }

        if (!in_array($operator, [
            'eq',
            '!eq',
            'lt',
            'gt',
            'le',
            'ge',
            'sw',
            '!sw',
            'ew',
            '!ew',
            'has',
            '!has',
            'in',
            '!in',
            'null'
        ])) {
            throw new QueryException('Unable to build query: invalid operator (' . $operator . ') for column (' . $column . ')');
        }

        $operator = str_replace([
            'eq',
            '!eq',
            'lt',
            'gt',
            'le',
            'ge'
        ], [
            '=',
            '!=',
            '<',
            '>',
            '<=',
            '>='
        ], $operator);

        if (str_contains($column, '.')) { // JSON

            $json = explode('.', $column, 2);

            $column = $json[0] . "->'$." . $json[1] . "'";

        }

        // Check operators

        switch ($operator) {

            case 'sw':

                if ($this->is_function($value)) {
                    $this->query['where'] .= $column . ' LIKE ' . $value;
                    break;
                }

                $this->placeholders[] = $value . '%';
                $this->query['where'] .= $column . ' LIKE ?';
                break;

            case '!sw':

                if ($this->is_function($value)) {
                    $this->query['where'] .= $column . ' NOT LIKE ' . $value;
                    break;
                }

                $this->placeholders[] = $value . '%';
                $this->query['where'] .= $column . ' NOT LIKE ?';
                break;

            case 'ew':

                if ($this->is_function($value)) {
                    $this->query['where'] .= $column . ' LIKE ' . $value;
                    break;
                }

                $this->placeholders[] = '%' . $value;
                $this->query['where'] .= $column . ' LIKE ?';
                break;

            case '!ew':

                if ($this->is_function($value)) {
                    $this->query['where'] .= $column . ' NOT LIKE ' . $value;
                    break;
                }

                $this->placeholders[] = '%' . $value;
                $this->query['where'] .= $column . ' NOT LIKE ?';
                break;

            case 'has':

                if ($this->is_function($value)) {
                    $this->query['where'] .= $column . ' LIKE ' . $value;
                    break;
                }

                $this->placeholders[] = '%' . $value . '%';
                $this->query['where'] .= $column . ' LIKE ?';
                break;

            case '!has':

                if ($this->is_function($value)) {
                    $this->query['where'] .= $column . ' NOT LIKE ' . $value;
                    break;
                }

                $this->placeholders[] = '%' . $value . '%';
                $this->query['where'] .= $column . ' NOT LIKE ?';
                break;

            case 'in':

                if ($this->is_function($value)) {
                    $this->query['where'] .= $column . ' IN (' . $value . ')';
                    break;
                }

                $in_values = explode(',', $value);

                $in = str_repeat('?,', count($in_values) - 1) . '?';

                foreach ($in_values as $val) {

                    $this->placeholders[] = $val;

                }

                $this->query['where'] .= $column . ' IN (' . $in . ')';

                break;

            case '!in':

                if ($this->is_function($value)) {
                    $this->query['where'] .= $column . ' NOT IN (' . $value . ')';
                    break;
                }

                $in_values = explode(',', $value);

                $in = str_repeat('?,', count($in_values) - 1) . '?';

                foreach ($in_values as $val) {

                    $this->placeholders[] = $val;

                }

                $this->query['where'] .= $column . ' NOT IN (' . $in . ')';

                break;

            case 'null':

                if ($value == 'true') {

                    $this->query['where'] .= $column . ' IS NULL';

                } else if ($value == 'false') {

                    $this->query['where'] .= $column . ' IS NOT NULL';

                } else {

                    throw new QueryException('Unable to build query: invalid value (' . $value . ') for operator (null)');

                }

                break;

            default:

                if ($value == '') { // Empty string needs no placeholder

                    $this->query['where'] .= $column . " " . $operator . " ''";

                } else if ($this->is_function($value)) {

                    $this->query['where'] .= $column . ' ' . $operator . ' ' . $value;

                } else {

                    $this->placeholders[] = $value;
                    $this->query['where'] .= $column . ' ' . $operator . ' ?';

                }

        }

        return $this;

    }

    /**
     * Adds an ORDER BY clause.
     *
     * Values in the $columns array without a prefix or prefixed with a "+" will be ordered ascending.
     * Values in the $columns array prefixed with a "-" will be ordered descending.
     *
     * If the column type is `json`, keys from within the `JSON` string can be ordered using dot notation.
     * The field can be ordered with the format of `{column}.{key}`.
     *
     * @param array $columns
     *
     * @return self
     */

    public function orderBy(array $columns): self
    {

        if (empty($columns)) {
            return $this;
        }

        $string = ' ORDER BY ';

        foreach ($columns as $column) {

            if (Str::startsWith($column, '-')) {

                $column = ltrim($column, '-');

                if (str_contains($column, '.')) { // JSON

                    $json = explode('.', $column, 2);
                    $column = $json[0] . "->'$." . $json[1] . "'";

                }

                $string .= $column . ' DESC, ';

            } else {

                /*
                 * The + character may have been interpreted as a space if the URL
                 * was not encoded. Therefore, spaces must be trimmed from this string.
                 */

                $column = ltrim(ltrim($column, '+'), ' ');

                if (str_contains($column, '.')) { // JSON

                    $json = explode('.', $column, 2);
                    $column = $json[0] . "->'$." . $json[1] . "'";

                }

                $string .= $column . ' ASC, ';

            }

        }

        $this->query['sort'] = rtrim($string, ', ');

        return $this;

    }

    /**
     * Adds an ORDER BY RAND() clause.
     *
     * @return self
     */

    public function orderByRand(): self
    {

        $this->query['sort'] = ' ORDER BY RAND()';

        return $this;

    }

    /**
     * Adds a LIMIT clause.
     *
     * @param int $limit
     *
     * @return self
     */

    public function limit(int $limit): self
    {

        $this->query['limit'] = ' LIMIT ' . $limit;

        return $this;

    }

    /**
     * Adds an OFFSET clause.
     *
     * @param int $offset
     *
     * @return self
     */

    public function offset(int $offset): self
    {

        $this->query['offset'] = ' OFFSET ' . $offset;

        return $this;

    }

    /**
     * Build the query.
     *
     * @return string
     */

    protected function _getQuery(): string
    {

        return 'SELECT ' . Arr::get($this->query, 'distinct', '')
            . implode(', ', Arr::get($this->query, 'columns', []))
            . Arr::get($this->query, 'from', '')
            . Arr::get($this->query, 'inner_join', '')
            . Arr::get($this->query, 'left_join', '')
            . Arr::get($this->query, 'right_join', '')
            . Arr::get($this->query, 'where', '')
            . Arr::get($this->query, 'sort', '')
            . Arr::get($this->query, 'limit', '')
            . Arr::get($this->query, 'offset', '');

    }

    /**
     * Get the result set from a table.
     *
     * @return array
     */

    public function get(): array
    {
        $stmt = $this->pdo->prepare($this->_getQuery());

        $stmt->execute($this->placeholders);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single row from a table, or false on failure.
     *
     * @return mixed
     */

    public function row(): mixed
    {
        $stmt = $this->pdo->prepare($this->_getQuery());

        $stmt->execute($this->placeholders);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single column of a single row of a table, or false if not existing.
     *
     * @return mixed
     */

    public function single(): mixed
    {
        $stmt = $this->pdo->prepare($this->_getQuery());

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
        return $this->_getQuery();
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

    /**
     * Returns total number of rows found for the query without limit restrictions.
     *
     * NOTE: To get the number of rows affected by a DELETE, use the Bayfront\PDO\Db->rowCount() method.
     *
     * @return int
     */

    public function getTotalRows(): int
    {
        $query = 'SELECT COUNT(*)'
            . Arr::get($this->query, 'from', '')
            . Arr::get($this->query, 'inner_join', '')
            . Arr::get($this->query, 'left_join', '')
            . Arr::get($this->query, 'right_join', '')
            . Arr::get($this->query, 'where', '');

        $stmt = $this->pdo->prepare($query);

        $stmt->execute($this->placeholders);

        return $stmt->fetchColumn();
    }

}