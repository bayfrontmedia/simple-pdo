# Documentation > Getting started

Simple PDO requires a `PDO` instance to be passed to its constructor.
This can be done manually, or by using the included `DbFactory` to create the Simple PDO instance for you.

- [Manual setup](#manual-setup)
- [Factory setup](#factory-setup)

## Manual setup

### Create a PDO instance

The first step is to create a PDO instance to use with Simple PDO.
You can do this yourself, or you can use one of the included adapters to create it for you.

#### Do it yourself

```php
$pdo = new PDO(
    'mysql:host=DB_HOST;dbname=DB_TO_USE',
    'DB_USER',
    'DB_USER_PASSWORD'
);
```

#### Use an adapter

A `PDO` connection can be created using an `AdapterInterface`.
Each adapter has its own required configuration array keys, as listed below.

To create a `PDO` instance, use the adapter's `connect()` static method,
which may throw the following exceptions on failure:

- `Bayfront\SimplePdo\Exceptions\ConfigurationException`- Invalid adapter configuration
- `Bayfront\SimplePdo\Exceptions\UnableToConnectException`- Unable to connect to database

```php
use Bayfront\SimplePdo\Adapters\MySql;
use Bayfront\SimplePdo\Exceptions\SimplePDOException;

$config = [
    'host' => 'DB_HOST',
    'port' => 3306,
    'database' => 'DB_TO_USE',
    'user' => 'DB_USER',
    'password' => 'DB_USER_PASSWORD',
    'options' => [] // Optional key => value array of connection options
];

try {

    $pdo = MySQL::connect($config);

} catch (SimplePDOException $e) {
    echo $e->getMessage();
}
```

### Create a Simple PDO instance

Once you have a `PDO` instance created, you can then use it as your default database with Simple PDO:

```php
use Bayfront\SimplePdo\Db;

$db = new Db($pdo); // $pdo as a PDO instance
```

By default, the `PDO` instance passed to the constructor will be named "default".
This name is available as a `Db::DB_DEFAULT` constant.

If you will only be using one database connection, there would never be a need to change this.
If, however, you will be working with multiple databases and wish to reference this connection by a different name,
you can assign it any name you like:

```php
use Bayfront\SimplePdo\Db;

$db = new Db($pdo, 'custom_name'); // $pdo as a PDO instance
```

## Factory setup

Alternatively, you can allow the Simple PDO factory build your Simple PDO instance from a configuration array.
The array can define as many database connections as you like,
and the factory will use adapters to automatically create and add all of them for you.

The `create` static method may throw the following exceptions on failure:

- `Bayfront\SimplePdo\Exceptions\ConfigurationException`
- `Bayfront\SimplePdo\Exceptions\InvalidDatabaseException`
- `Bayfront\SimplePdo\Exceptions\UnableToConnectException`

```php
use Bayfront\SimplePdo\DbFactory;
use Bayfront\SimplePdo\Exceptions\SimplePDOException;

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