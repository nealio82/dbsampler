<?php

namespace Quidco\DbSampler\Database;

use Doctrine\DBAL\Driver\Connection;

class DriverFactory
{
    public static function getDriver(Connection $connection): Driver
    {
        if ('pdo_mysql' === $connection->getDriver()->getName()) {
            return new MySqlDriver($connection);
        }

        if ('pdo_sqlite' === $connection->getDriver()->getName()) {
            return new SqliteDriver($connection);
        }

        throw new \RuntimeException("Unknown database driver");
    }
}
