<?php

namespace Bayfront\SimplePdo\Interfaces;

use Bayfront\SimplePdo\Exceptions;
use PDO;

interface AdapterInterface
{

    /**
     * Connect to database
     *
     * @param array $config
     * @return PDO
     * @throws Exceptions\ConfigurationException
     * @throws Exceptions\UnableToConnectException
     */
    public static function connect(array $config): PDO;

}