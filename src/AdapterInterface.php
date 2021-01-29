<?php
/**
 * @package simple-pdo
 * @link https://github.com/bayfrontmedia/simple-pdo
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\PDO;

use PDO;

interface AdapterInterface
{

    /**
     * Connect to database
     *
     * @param array $config
     *
     * @return PDO
     *
     * @throws Exceptions\ConfigurationException
     * @throws Exceptions\UnableToConnectException
     */

    public static function connect(array $config): PDO;

}