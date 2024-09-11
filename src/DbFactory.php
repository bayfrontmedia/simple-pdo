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

        foreach ($array as $name => $db_config) {

            // Check valid adapter

            if (!isset($db_config['adapter']) || !class_exists('Bayfront\SimplePdo\Adapters\\' . $db_config['adapter'])) {

                throw new ConfigurationException('Invalid database configuration (' . $name . '): adapter not specified or does not exist');

            }

            /** @var AdapterInterface $adapter */

            $adapter = 'Bayfront\SimplePdo\Adapters\\' . $db_config['adapter'];

            if (isset($db_config['default']) && true === $db_config['default'] && !isset($db)) { // If default database

                // Create connection

                /*
                 * @throws Bayfront\SimplePdo\Exceptions\ConfigurationException
                 * @throws Bayfront\SimplePdo\Exceptions\UnableToConnectException
                 */

                $pdo = $adapter::connect($db_config);

                // Create Db instance

                $db = new Db($pdo, $name);

            } else { // If not default database

                // Create connection

                /*
                 * @throws Bayfront\SimplePdo\Exceptions\PdoException
                 */

                $connections[$name] = $adapter::connect($db_config);

            }

        }

        if (!isset($db)) { // If default database does not exist

            throw new ConfigurationException('Invalid database configuration: no default database specified');

        }

        foreach ($connections as $name => $pdo) {

            /*
             * @throws Bayfront\SimplePdo\Exceptions\InvalidDatabaseException
             */

            $db->addConnection($pdo, $name); // Add all additional connections

        }

        return $db;

    }

}