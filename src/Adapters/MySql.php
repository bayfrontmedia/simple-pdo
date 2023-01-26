<?php

namespace Bayfront\PDO\Adapters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\PDO\AdapterInterface;
use Bayfront\PDO\Exceptions\ConfigurationException;
use Bayfront\PDO\Exceptions\UnableToConnectException;
use PDO;
use PDOException;

class MySql implements AdapterInterface
{

    private static array $required_config_keys = [
        'host',
        'port',
        'database',
        'user',
        'password'
    ];

    private static array $default_options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false
    ];

    /**
     * Connect.
     *
     * @param array $config
     *
     * @return PDO
     *
     * @throws ConfigurationException
     * @throws UnableToConnectException
     */

    public static function connect(array $config): PDO
    {

        if (Arr::isMissing(Arr::dot($config), self::$required_config_keys)) { // Check for missing array keys

            throw new ConfigurationException('Invalid adapter configuration');

        }

        $dsn = 'mysql:host=' . $config['host'] . ';port= ' . $config['port'] . ';dbname=' . $config['database'];

        $options = self::$default_options;

        if (isset($config['options'])) {

            foreach ($config['options'] as $k => $v) {

                $options[$k] = $v;

            }

        }

        try {

            return new PDO($dsn, $config['user'], $config['password'], $options);

        } catch (PDOException $e) {

            throw new UnableToConnectException($e->getMessage(), 0, $e);

        }

    }

}