<?php

namespace Quidco\DbSampler\Database;

class SqliteDriver extends Driver
{
    public function getName(): string
    {
        return 'sqlite';
    }

    public function dropTableSql(string $tableName): string
    {
        return 'DROP TABLE IF EXISTS ' . $this->connection->quoteIdentifier($tableName);
    }

    public function createTableSql(string $tableName): string
    {
        return $this->connection
            ->query('SELECT sql FROM sqlite_master WHERE type="table" AND tbl_name=' . $this->connection->quoteIdentifier($tableName))
            ->fetchColumn();
    }

    public function migrateTableTriggersSql(string $tableName): iterable
    {
        $schemaSql = "select sql from sqlite_master where type = 'trigger' AND tbl_name=" . $this->connection->quote($tableName);
        $triggers = $this->connection->fetchAll($schemaSql);
        if ($triggers && count($triggers) > 0) {
            foreach ($triggers as $trigger) {
                yield $trigger['sql'];
            }
        }
    }

    public function dropViewSql(string $viewName): string
    {
        return 'DROP VIEW IF EXISTS ' . $this->connection->quoteIdentifier($viewName);
    }

    public function createViewSql(string $viewName): string
    {
        $schemaSql = 'SELECT SQL FROM sqlite_master WHERE NAME=' . $this->connection->quoteIdentifier($viewName);

        return $this->connection->query($schemaSql)->fetchColumn();
    }
}
