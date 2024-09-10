# Documentation > Query builder

Simple PDO includes a helpful query builder which you may choose to utilize.
A query builder is useful to dynamically create queries, 
such as for an API to create a query based on the URL query string.

- [Usage](#usage)
- [Examples](#examples)

## Usage

The query builder requires a `PDO` instance to be passed to the constructor.

**Example:**

```php
use Bayfront\SimplePdo\Query;

$pdo = new PDO(
    'mysql:host=DB_HOST;dbname=DB_TO_USE',
    'DB_USER',
    'DB_USER_PASSWORD'
);

$query = new Query($pdo);
``` 

### Public methods

**Build query**

- [table](#table)
- [distinct](#distinct)
- [innerJoin](#innerjoin)
- [leftjoin](#leftjoin)
- [rightjoin](#rightjoin)
- [select](#select)
- [where](#where)
- [orderBy](#orderby)
- [orderByRand](#orderbyrand)
- [limit](#limit)
- [offset](#offset)

**Fetch results**

- [get](#get)
- [row](#row)
- [single](#single)
- [getLastQuery](#getlastquery)
- [getLastParameters](#getlastparameters)
- [getTotalRows](#gettotalrows)

<hr />

### table

**Description:**

Define the table to query.

**Parameters:**

- `$table` (string)

**Returns:**

- (self)

<hr />

### distinct

**Description:**

Add a `DISTINCT` clause to the query.

**Parameters:**

- (none)

**Returns:**

- (self)

<hr />

### innerJoin

**Description:**

Add `INNER JOIN` clause to the query.

**Parameters:**

- `$table` (string)
- `$col1` (string)
- `$col2` (string)

**Returns:**

- (self)

<hr />

### leftJoin

**Description:**

Add `LEFT JOIN` clause to the query.

**Parameters:**

- `$table` (string)
- `$col1` (string)
- `$col2` (string)

**Returns:**

- (self)

<hr />

### rightJoin

**Description:**

Add `RIGHT JOIN` clause to the query.

**Parameters:**

- `$table` (string)
- `$col1` (string)
- `$col2` (string)

**Returns:**

- (self)

<hr />

### select

**Description:**

Define column(s) to select.

If the column type is `json`, keys from within the JSON string can be selected with the format of `{column}->{key}`.
The field will be returned as a multidimensional array.
JSON fields which do not exist are returned with a value of `null`.

**Parameters:**

- `$columns` (string|array)

**Returns:**

- (self)

<hr />

### where

**Description:**

Adds a `WHERE` clause to the query.

If the column type is `json`, keys from within the JSON string can be searched with the format of `{column}->{key}`.
JSON fields which do not exist are treated as `null`.

Available operators are:

- `eq` (equals)
- `!eq` (does not equal)
- `lt` (less than)
- `gt` (greater than)
- `le` (less than or equal to)
- `ge` (greater than or equal to)
- `sw` (starts with)
- `!sw` (does not start with)
- `ew` (ends with)
- `!ew` (does not end with)
- `has` (has)
- `!has` (does not have)
- `in` (in)
- `!in` (not in)
- `null` (is or is not `null`)

The `in` and `!in` operators accept multiple comma-separated values.

The `null` operator accepts two values: `true` and `false` for `is null` or `is not null`.

> **NOTE:** Some native MySQL functions can be used as the `$value`, however they will be
> injected into the query as strings, so they are vulnerable to SQL injection. 

**Parameters:**

- `$column` (string)
- `$operator` (string)
- `$value` (mixed)

**Returns:**

- (self)

**Throws:**

- `Bayfront\SimplePdo\Exceptions\QueryException`

<hr />

### orderBy

**Description:**

Adds an `ORDER BY` clause.

Values in the `$columns` array without a prefix or prefixed with a `+` will be ordered ascending.
Values in the `$columns` array prefixed with a `-` will be ordered descending.

If the column type is `json`, keys from within the JSON string can be ordered with the format of `{column}->{key}`.
JSON fields which do not exist are treated as `null`.

**Parameters:**

- `$columns` (array)

**Returns:**

- (self)

<hr />

### orderByRand

**Description:**

Adds an `ORDER BY RAND()` clause.

**Parameters:**

- (None)

**Returns:**

- (self)

<hr />

### limit

**Description:**

Adds a `LIMIT` clause.

**Parameters:**

- `$limit` (int)

**Returns:**

- (self)

<hr />

### offset

**Description:**

Adds an `OFFSET` clause.

**Parameters:**

- `$offset` (int)

**Returns:**

- (self)

<hr />

### get

**Description:**

Get the result set from a table.

**Parameters:**

- (None)

**Returns:**

- (array)

<hr />

### row

**Description:**

Get a single row from a table, or `false` on failure.

**Parameters:**

- (None)

**Returns:**

- (mixed)

<hr />

### single

**Description:**

Get a single column of a single row of a table, or `false` if not existing.
If more than one field was defined by [select](#select), the first field will be returned.

**Parameters:**

- (None)

**Returns:**

- (mixed)

<hr />

### getLastQuery

**Description:**

Returns last raw query.

**Parameters:**

- (None)

**Returns:**

- (string)

<hr />

### getLastParameters

**Description:**

Returns last query parameters.

**Parameters:**

- None

**Returns:**

- (array)

<hr />

### getTotalRows

**Description:**

Returns total number of rows found for the query without limit restrictions.

**Parameters:**

- (None)

**Returns:**

- (int)

## Examples

Select all records from `items` table:

```php
$results = $query->table('items')
    ->select('*')
    ->get();
```

<hr />

Select all records from `items` table where `price` is greater than `20.00`:

```php
$results = $query->table('items')
    ->select('*')
    ->where('price', 'gt', '20.00')
    ->get();
```

<hr />

Select `name`, `color`, `quantity`, `supplier->location` and `supplier->email` as `supplier_email` records from `items` table where `price` is greater than `20.00` and `supplier->name` starts with `a`:

```php
$results = $query->table('items')
    ->select([
        'name',
        'color',
        'quantity',
        'supplier->location',
        'supplier->email AS supplier_email'
    ])
    ->where('price', 'gt', '20.00')
    ->where('supplier->name', 'sw', 'a')
    ->get();
```

This example represents a column named `supplier` with type of `json`.

<hr />

Select up to 10 results for `name`, `color`, `quantity` from `items` table where `description` contains the word "fluffy", and the price is less than `50.00`, ordered by `name` descending.
Also, get the total number of rows found for the query without limit restrictions.

```php
$results = $query->table('items')
    ->select([
        'name',
        'color',
        'quantity'
    ])
    ->where('description', 'has', 'fluffy')
    ->where('price', 'lt', '50.00')
    ->orderBy([
        '-name'
    ])
    ->limit(10)
    ->get();

$total_count = $query->getTotalRows();
```

<hr />

Example using `LEFT JOIN`:

```php
$results = $query->table('items')
    ->leftJoin('vendors', 'items.vendor_id', 'vendors.id')
    ->select([
        'vendors.name',
        'items.name',
        'items.color',
        'items.quantity'
    ])
    ->where('items.description', 'has', 'fluffy')
    ->where('items.price', 'lt', '50.00')
    ->orderBy([
        'vendors.name',
        '-items.name'
    ])
    ->limit(10)
    ->get();

$total_count = $query->getTotalRows();
```