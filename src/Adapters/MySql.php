<?php

/**
 * @package simple-pdo
 * @link https://github.com/bayfrontmedia/simple-pdo
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\PDO\Adapters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\PDO\AdapterInterface;
use Bayfront\PDO\Exceptions\ConfigurationException;
use Bayfront\PDO\Exceptions\UnableToConnectException;
use PDO;
use PDOException;

class MySql implements AdapterInterface
{

    private static $required_config_keys = [
        'host',
        'port',
        'database',
        'user',
        'password'
    ];

    private static $default_options = [
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

        if (isset($config['options'])) {

            $options = array_merge(self::$default_options, $config['options']);

        } else {

            $options = self::$default_options;

        }

        try {

            return new PDO($dsn, $config['user'], $config['password'], $options);

        } catch (PDOException $e) {

            throw new UnableToConnectException($e->getMessage(), 0, $e);

        }

    }

}