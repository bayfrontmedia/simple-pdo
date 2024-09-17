# Documentation: Getting started

Simple PDO can either be instantiated manually, or via the included Simple PDO factory.

- [Manual setup](#manual-setup)
- [Factory setup](#factory-setup)

## Manual setup

Once you have a [PDO instance created](pdo.md), you can then use it as your default database with Simple PDO:

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
Each name must be unique, and each connection must specify a valid adapter.
The first listed connection name in the array will be set as the current database.