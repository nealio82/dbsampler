<?php

namespace Quidco\DbSampler\Database;

use Doctrine\DBAL\Driver\Connection;

abstract class Database
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getDriver(): Driver
    {
        return DriverFactory::getDriver($this->connection);
    }
}