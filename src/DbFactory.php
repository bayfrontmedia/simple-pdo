<?php

namespace Bayfront\SimplePdo;

use Bayfront\SimplePdo\Exceptions\ConfigurationException;
use Bayfront\SimplePdo\Exceptions\InvalidDatabaseException;
use Bayfront\SimplePdo\Exceptions\UnableToConnectException;
use Bayfront\SimplePdo\Interfaces\AdapterInterface;

class DbFactory
{

    /**
     * Create Simple PDO instance from configuration array.
     *
     * @param array $array
     * @return Db
     * @throws ConfigurationException
     * @throws InvalidDatabaseException
     * @throws UnableToConnectException
     */
    public static function create(array $array): Db
    {

        $connections = [];

        $current_db_name = null;

        foreach ($array as $name => $db_config) {

            // Check valid adapter

            if (!isset($db_config['adapter']) || !class_exists('Bayfront\SimplePdo\\Adapters\\' . $db_config['adapter'])) {

                throw new ConfigurationException('Invalid database configuration (' . $name . '): adapter not specified or does not exist');

            }

            /** @var AdapterInterface $adapter */

            $adapter = 'Bayfront\SimplePdo\Adapters\\' . $db_config['adapter'];

            if ($current_db_name === null) { // First listed connection

                $current_db_name = $name;

                // Create connection

                $pdo = $adapter::connect($db_config);

                // Create Db instance

                $db = new Db($pdo, $name);

            } else { // If not default database

                // Create connection

                $connections[$name] = $adapter::connect($db_config);

            }

        }

        if (!isset($db)) { // If no databases were listed
            throw new ConfigurationException('Invalid database configuration: no database specified');
        }

        foreach ($connections as $name => $pdo) {
            $db->addConnection($pdo, $name); // Add all additional connections
        }

        return $db;

    }

}