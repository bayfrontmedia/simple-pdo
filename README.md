## Simple PDO

A simple and secure database abstraction layer which utilizes the PDO interface and supports working with multiple databases.

Simple PDO was designed to provide a collection of functions which ensure safe and secure database queries with a simple to use interface without compromising speed- all the while supporting simultaneous connections of multiple databasess.

Simple PDO also utilizes prepared statements using named bindings to protect against SQL injections.

- [License](#license)
- [Author](#author)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)

## License

This project is open source and available under the [MIT License](LICENSE).

## Author

<img src="https://cdn1.onbayfront.com/bfm/brand/bfm-logo.svg" alt="Bayfront Media" width="250" />

- [Bayfront Media homepage](https://www.bayfrontmedia.com?utm_source=github&amp;utm_medium=direct)
- [Bayfront Media GitHub](https://github.com/bayfrontmedia)

## Requirements

* PHP `^8.0`
* PDO PHP extension

## Installation

```
composer require bayfrontmedia/simple-pdo
```

## Usage

**NOTE:** All exceptions thrown by Simple PDO extend `Bayfront\PDO\Exceptions\SimplePDOException`, so you can choose to catch exceptions as narrowly or broadly as you like.

### Default usage

The first step is to create a PDO instance to use with Simple PDO. You can do this yourself, or you can use one of the included adapters to create it for you.

#### Do it yourself

```
$pdo = new PDO(
    'mysql:host=DB_HOST;dbname=DB_TO_USE',
    'DB_USER',
    'DB_USER_PASSWORD'
);
```

#### Use an adapter

In order to connect to a database, each adapter has its own required configuration array keys, as listed below.

To create a PDO instance, use the adapter's `connect()` static method, which may throw the following exceptions on failure:

- `Bayfront\PDO\Exceptions\ConfigurationException`- Invalid adapter configuration
- `Bayfront\PDO\Exceptions\UnableToConnectException`- Unable to connect to database 

```
use Bayfront\PDO\Adapters\MySql;
use Bayfront\PDO\Exceptions\SimplePDOException;

$config = [
    'host' => 'DB_HOST',
    'port' => 3306,
    'database' => 'DB_TO_USE',
    'user' => 'DB_USER',
    'password' => 'DB_USER_PASSWORD'
];

try {

    $pdo = MySQL::connect($config);

} catch (SimplePDOException $e) {
    echo $e->getMessage();
}
```

The required configuration array keys for each adapter are listed below:

**MySQL**

```
[
    'host' => 'DB_HOST',
    'port' => 3306, // MySQL port
    'database' => 'DB_TO_USE',
    'user' => 'DB_USER',
    'password' => 'DB_USER_PASSWORD',
    'options' => [] // Optional key => value array of connection options
]
```

#### Start using Simple PDO

Once you have a PDO instance, you can then use it as your default database with Simple PDO:

```
use Bayfront\PDO\Db;

$db = new Db($pdo); // $pdo as a PDO instance
```

By default, the PDO instance passed to the constructor will be named "default". If you will only be using one database connection, there would never be a need to change this. If, however, you will be working with multiple databases and wish to reference this connection by a different name, you can assign it any name you like:

```
use Bayfront\PDO\Db;

$db = new Db($pdo, 'custom_name'); // $pdo as a PDO instance
```

### Factory usage 

Alternatively, you can allow the Simple PDO factory build your Simple PDO instance from a configuration array.
The array can define as many database connections as you like, and the factory will use adapters to automatically create and add all of them for you.

The `create` static method may throw the following exceptions on failure:

- `Bayfront\PDO\Exceptions\ConfigurationException`
- `Bayfront\PDO\Exceptions\InvalidDatabaseException`
- `Bayfront\PDO\Exceptions\UnableToConnectException`

**Factory example:**

```
use Bayfront\PDO\DbFactory;
use Bayfront\PDO\Exceptions\SimplePDOException;

$config = [ 
    'primary' => [ // Connection name
        'default' => true, // One connection on the array must be defined as default
        'adapter' => 'MySql', // Adapter to use
        'host' => 'DB_HOST',
        'port' => 3306,
        'database' => 'DB_TO_USE',
        'user' => 'DB_USER',
        'password' => 'DB_USER_PASSWORD'
    ],
    'secondary' => [ 
        'adapter' => 'MySql',
        'host' => 'DB_HOST',
        'port' => 3306,
        'database' => 'DB_TO_USE',
        'user' => 'DB_USER',
        'password' => 'DB_USER_PASSWORD'
    ]
];

try {

    $db = DbFactory::create($config);

} catch (SimplePDOException $e) {
    die($e->getMessage());
}
```

The array keys define the connection names.
Each name must be unique.
One connection must be defined as `default`, and each connection must specify a valid adapter.

The only other values to add would be whatever is required by the adapter.

### Query builder

Simple PDO includes a helpful query builder which you may choose to utilize.
For more information, see [query builder](_docs/query-builder.md).

### Public methods

**Database connections**

- [add](#add)
- [use](#use)
- [get](#get)
- [getDefault](#getdefault)
- [getCurrent](#getcurrent)
- [getConnections](#getconnections)
- [isConnected](#isconnected)

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
- [getQueryTime](#getquerytime)
- [getTotalQueries](#gettotalqueries)

<hr />

### add

**Description:**

Add a PDO instance.

**Parameters:**

- `$pdo` (PDO)
- `$db_name` (string): Name must be unique
- `$make_current = false` (bool)
- `$make_default = false` (bool)

**Returns:**

- (self)

**Throws:**

- `Bayfront\PDO\Exceptions\InvalidDatabaseException`

**Example:**

```
try {

    $db->add($pdo, 'backup'); // Assuming $pdo is a PDO instance

} catch (InvalidDatabaseException $e) {
    echo $e->getMessage();
}
```

<hr />

### use

**Description:**

Set given database name as current. After the next query, the current database will automatically revert to the default database.

**Parameters:**

- `$db_name` (string)
- `$make_default = false` (bool)

**Returns:**

- (self)

**Throws:**

- `Bayfront\PDO\Exceptions\InvalidDatabaseException`

**Example:**

```
try {

    $db->use('backup');

} catch (InvalidDatabaseException $e) {
    echo $e->getMessage();
}
```

<hr />

### get

**Description:**

Returns the raw PDO instance of a given database.

**Parameters:**

- `$db_name = NULL` (string): Not specifying this parameter will return the PDO instance of the current database

**Returns:**

- (PDO)

**Throws:**

- `Bayfront\PDO\Exceptions\InvalidDatabaseException`

**Example:**

```
try {

    $pdo = $db->get('backup');

} catch (InvalidDatabaseException $e) {
    echo $e->getMessage();
}
```

<hr />

### getDefault

**Description:**

Returns name of the default database.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```
echo $db->getDefault();
```

<hr />

### getCurrent

**Description:**

Returns name of the database currently being used.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```
echo $db->getCurrent();
```

<hr />

### getConnections

**Description:**

Returns array of all database connection names.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```
print_r($db->getConnections());
```

<hr />

### isConnected

**Description:**

Checks if connected to a given database name.

**Parameters:**

- `$db_name` (string)

**Returns:**

- (bool)

**Example:**

```
if ($db->isConnected('backup')) {
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $db->query("INSERT INTO items (name, description, color, quantity, price) VALUES (:name, :description, :color, :quantity, :price)", [
        'name' => 'Sample item',
        'description' => 'Sample item description',
        'color' => 'blue',
        'quantity' => 5,
        'price' => 49.99
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $results = $db->select("SELECT * FROM items WHERE price > :min_price", [
        'min_price' => 20
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $result = $db->row("SELECT * FROM items WHERE id = :id", [
        'id' => 1
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $result = $db->single("SELECT description FROM items WHERE id = :id", [
        'id' => 1
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $db->insert('items', [
        'name' => 'Some new item',
        'description' => 'A description of the item',
        'color' => 'red',
        'quantity' => 3,
        'price' => 99.99
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $db->update('items', [
        'price' => 89.99
    ], [
        'id' => 2
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $db->delete('items', [
        'id' => 2
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $count = $db->count('items', [
        'color' => 'blue'
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $exists = $db->exists('items', [
        'color' => 'blue'
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\QueryException`

**Example:**

```
try {

    $sum = $db->sum('items', 'quantity', [
        'color' => 'blue'
    ]);

} catch (QueryException $e) {
    echo $e->getMessage();
}
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

**Throws:**

- `Bayfront\PDO\Exceptions\TransactionException`

**Example:**

```
try {
    
    $db->beginTransaction();
    
    // Multiple queries occur here
    
    $db->commitTransaction();
    
} catch (TransactionException $e) {
    echo $e->getMessage();
}
```

<hr />

### commitTransaction

**Description:**

Commits a transaction.

**Parameters:**

- None

**Returns:**

- (bool)

**Throws:**

- `Bayfront\PDO\Exceptions\TransactionException`

<hr />

### rollbackTransaction

**Description:**

Cancels a transaction which has begun, and rolls back any modifications since the transaction began.

**Parameters:**

- None

**Returns:**

- (bool)

**Throws:**

- `Bayfront\PDO\Exceptions\TransactionException`

<hr />

### getLastQuery

**Description:**

Returns last raw query.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```
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

```
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

```
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

```
echo $db->lastInsertId();
```

<hr />

### getQueryTime

**Description:**

Returns the total time elapsed in seconds for all queries executed for the current database.

**Parameters:**

- `$decimals = 3` (int): Number of decimal points to return

**Returns:**

- (float)

**Example:**

```
echo $db->getQueryTime();
```

<hr />

### getTotalQueries

**Description:**

Returns the total number of queries executed for the current database.

**Parameters:**

- None

**Returns:**

- (int)

**Example:**

```
echo $db->getTotalQueries();
```