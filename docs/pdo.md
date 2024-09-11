# Documentation: PDO

Simple PDO requires the use of a `PDO` class instance.
It can be created manually, or by using the included adapter.

- [Manual creation](#manual-creation)
- [Using an adapter](#using-an-adapter)

## Manual creation

To create a `PDO` instance manually,
```php
$pdo = new PDO(
    'mysql:host=DB_HOST;dbname=DB_TO_USE',
    'DB_USER',
    'DB_USER_PASSWORD',
    [] // Optional key => value array of driver-specific connection options. 
);
```

## Using an adapter

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