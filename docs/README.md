# Documentation

**NOTE:** All exceptions thrown by Simple PDO extend `Bayfront\SimplePdo\Exceptions\SimplePDOException`,
so you can choose to catch exceptions as narrowly or broadly as you like.

Whether using the Simple PDO `Db` class, or the query builder's `Query` class,
a `PDO` instance is required.

For more information on creating a `PDO` instance, see [PDO](pdo.md).

## Query builder

Simple PDO includes a helpful query builder which you may choose to utilize.
For more information, see [query builder](query-builder.md).

## Simple PDO

First, see [getting started](getting-started.md) to create a Simple PDO `Db` instance.
Once an instance is created, you can begin using Simple PDO.

## Public methods

**Database connections**

- [addConnection](#addconnection)
- [useConnection](#useconnection)
- [getConnection](#getconnection)
- [getCurrentConnection](#getcurrentconnection)
- [getCurrentConnectionName](#getcurrentconnectionname)
- [getConnectionNames](#getconnectionnames)
- [connectionExists](#connectionexists)

**Queries**

- [query](#query)
- [select](#select)
- [row](#row)
- [single](#single)
- [insert](#insert)
- [update](#update)
- [delete](#delete)
- [count](#count)
- [exists](#exists)
- [sum](#sum)
- [beginTransaction](#begintransaction)
- [commitTransaction](#committransaction)
- [rollbackTransaction](#rollbacktransaction)

**Query information**

- [getLastQuery](#getlastquery)
- [getLastParameters](#getlastparameters)
- [rowCount](#rowcount)
- [lastInsertId](#lastinsertid)
- [setQueryTime](#setquerytime)
- [getQueryTime](#getquerytime)
- [getTotalQueries](#gettotalqueries)

<hr />

### addConnection

**Description:**

Add a PDO instance.

**Parameters:**

- `$pdo` (PDO)
- `$db_name` (string): Name must be unique
- `$make_current = false` (bool): Use this connection for all subsequent queries

**Returns:**

- (self)

**Throws:**

- `Bayfront\SimplePdo\Exceptions\InvalidDatabaseException`

**Example:**

```php
try {

    $db->addConnection($pdo, 'backup'); // Assuming $pdo is a PDO instance

} catch (InvalidDatabaseException $e) {
    echo $e->getMessage();
}
```

<hr />

### useConnection

**Description:**

Use a given database connection for all subsequent queries.

**Parameters:**

- `$db_name` (string)

**Returns:**

- (self)

**Throws:**

- `Bayfront\SimplePdo\Exceptions\InvalidDatabaseException`

**Example:**

```php
try {

    $db->useConnection('backup');

} catch (InvalidDatabaseException $e) {
    echo $e->getMessage();
}
```

<hr />

### getConnection

**Description:**

Returns the raw `PDO` instance of a given database.

**Parameters:**

- `$db_name = ''` (string): Leaving this parameter blank will return the `PDO` instance of the current database

**Returns:**

- (`PDO`)

**Throws:**

- `Bayfront\SimplePdo\Exceptions\InvalidDatabaseException`

**Example:**

```php
try {

    $pdo = $db->getConnection('backup');

} catch (InvalidDatabaseException $e) {
    echo $e->getMessage();
}
```

<hr />

### getCurrentConnection

**Description:**

Returns the raw `PDO` instance of the current database.

**Parameters:**

- None

**Returns:**

- (`PDO`)

**Example:**

```php
$pdo = $db->getCurrentConnection();
```

<hr />

### getCurrentConnectionName

**Description:**

Returns name of the current database connection.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```php
echo $db->getCurrentConnectionName();
```

<hr />

### getConnectionNames

**Description:**

Returns array of all database connection names.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```php
print_r($db->getConnectionNames());
```

<hr />

### connectionExists

**Description:**

Checks if a database connection exists with a given name.

**Parameters:**

- `$db_name` (string)

**Returns:**

- (bool)

**Example:**

```php
if ($db->connectionExists('backup')) {
    // Do something
}
```

<hr />

### query

**Description:**

Execute a query.

**Parameters:**

- `$query` (string)
- `$params = []` (array)

**Returns:**

- (bool)

**Example:**

```php
$db->query("INSERT INTO items (name, description, color, quantity, price) VALUES (:name, :description, :color, :quantity, :price)", [
        'name' => 'Sample item',
        'description' => 'Sample item description',
        'color' => 'blue',
        'quantity' => 5,
        'price' => 49.99
    ]);
```

<hr />

### select

**Description:**

Returns the result set from a table, or `false` on failure.

**Parameters:**

- `$query` (string)
- `$params = []` (array)
- `$return_array = true` (bool): When `false`, the result set will be returned as an object

**Returns:**

- (mixed)

**Example:**

```php
$results = $db->select("SELECT * FROM items WHERE price > :min_price", [
    'min_price' => 20
]);
```

<hr />

### row

**Description:**

Returns a single row from a table, or `false` on failure.

**Parameters:**

- `$query` (string)
- `$params = []` (array)
- `$return_array = true` (bool): When `false`, the result set will be returned as an object

**Returns:**

- (mixed)
-
**Example:**

```php
$result = $db->row("SELECT * FROM items WHERE id = :id", [
    'id' => 1
]);
```

<hr />

### single

**Description:**

Returns a single column from a single row of a table, or `false` if not existing.

**Parameters:**

- `$query` (string)
- `$params = []` (array)

**Returns:**

- (mixed)

**Example:**

```php
$result = $db->single("SELECT description FROM items WHERE id = :id", [
    'id' => 1
]);
```

<hr />

### insert

**Description:**

Inserts a new row.

**Parameters:**

- `$table` (string)
- `$values` (array)
- `$overwrite = true` (bool): Overwrite preexisting values if they exist

**Returns:**

- (bool)

**Example:**

```php
$db->insert('items', [
    'name' => 'Some new item',
    'description' => 'A description of the item',
    'color' => 'red',
    'quantity' => 3,
    'price' => 99.99
]);
```

<hr />

### update

**Description:**

Updates an existing row.

**Parameters:**

- `$table` (string)
- `$values` (array)
- `$conditions` (array): Where key = value

**Returns:**

- (bool)

**Example:**

```php
$db->update('items', [
    'price' => 89.99
], [
    'id' => 2
]);
```

<hr />

### delete

**Description:**

Deletes row(s).

**NOTE:** Leaving the `$conditions` array empty will delete all rows of the table, so use with caution!

**Parameters:**

- `$table` (string)
- `$conditions` (array): Where key = value

**Returns:**

- (bool)

**Example:**

```php
$db->delete('items', [
    'id' => 2
]);
```

<hr />

### count

**Description:**

Returns number of rows in a table that matches given conditions.

**Parameters:**

- `$table` (string)
- `$conditions = []` (array): Where key = value

**Returns:**

- (int)

**Example:**

```php
$count = $db->count('items', [
    'color' => 'blue'
]);
```

<hr />

### exists

**Description:**

Checks if rows exist in a table that matches given conditions.

**Parameters:**

- `$table` (string)
- `$conditions = []` (array): Where key = value

**Returns:**

- (bool)

**Example:**

```php
$exists = $db->exists('items', [
    'color' => 'blue'
]);
```

<hr />

### sum

**Description:**

Returns sum of column in a table that matches given conditions.

**Parameters:**

- `$table` (string)
- `$column` (string)
- `$conditions = []` (array)

**Returns:**

- (int)

**Example:**

```php
$sum = $db->sum('items', 'quantity', [
    'color' => 'blue'
]);
```

<hr />

### beginTransaction

**Description:**

Begins a transaction.

Once a transaction has begun, all database modifications across multiple queries will be rolled back if any fail, or if cancelled by calling `rollbackTransaction()`.

**Parameters:**

- None

**Returns:**

- (bool)

**Example:**

```php
$db->beginTransaction();
    
// Multiple queries occur here
    
$db->commitTransaction();
```

<hr />

### commitTransaction

**Description:**

Commits a transaction.

**Parameters:**

- None

**Returns:**

- (bool)

<hr />

### rollbackTransaction

**Description:**

Cancels a transaction which has begun, and rolls back any modifications since the transaction began.

**Parameters:**

- None

**Returns:**

- (bool)

<hr />

### getLastQuery

**Description:**

Returns last raw query.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```php
echo $db->getLastQuery();
```

<hr />

### getLastParameters

**Description:**

Returns last query parameters.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```php
print_r($db->getLastParameters();
```

<hr />

### rowCount

**Description:**

Returns the number of rows affected by the last statement.

**Parameters:**

- None

**Returns:**

- (int)

**Example:**

```php
echo $db->rowCount();
```

<hr />

### lastInsertId

**Description:**

Returns the ID of the last inserted row.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```php
echo $db->lastInsertId();
```

<hr />

### setQueryTime

**Description:**

Add a query time to be tracked using `getQueryTime` and `getTotalQueries`.
This is helpful to track queries using the [query builder](query-builder.md).

**Parameters:**

- `$db_name` (string)
- `$duration = 0` (float): Microseconds as float

**Returns:**

- (void)

**Example:**

```php
$start_time = microtime(true);

// Perform a query outside the Db class

$db->setQueryTime('backup', microtime(true) - $start_time);
```

### getQueryTime

**Description:**

Returns the total time elapsed in seconds for all queries executed for a given database.

**Parameters:**

- `$decimals = 3` (int): Number of decimal points to return
- `$db_name = ''` (string): Leaving this parameter blank will return the time elapsed for all database connections

**Returns:**

- (float)

**Example:**

```php
echo $db->getQueryTime();
```

<hr />

### getTotalQueries

**Description:**

Returns the total number of queries executed for a given database.

**Parameters:**

- `$db_name = ''` (string): Leaving this parameter blank will return the total queries for all database connections

**Returns:**

- (int)

**Example:**

```php
echo $db->getTotalQueries();
```