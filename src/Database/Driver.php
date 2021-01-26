<?php

namespace Quidco\DbSampler\Database;

use Doctrine\DBAL\Driver\Connection;

abstract class Driver
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    abstract public function getName(): string;

    abstract public function dropTableSql(string $tableName): string;

    abstract public function createTableSql(string $tableName): string;

    abstract public function migrateTableTriggersSql(string $tableName): iterable;

    abstract public function dropViewSql(string $viewName): string;

    abstract public function createViewSql(string $viewName): string;
}