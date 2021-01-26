<?php

namespace Quidco\DbSampler\Database;

class DestinationDatabase extends Database
{
    public function dropTable(string $tableName): void
    {
        $this->connection->exec(
            $this->getDriver()->dropTableSql($tableName)
        );
    }

    public function createTable(string $tableDefinition): void
    {
        $this->connection->exec($tableDefinition);
    }

    public function migrateTableTriggers(iterable $triggerDefinitions): void
    {
        foreach ($triggerDefinitions as $triggerSql) {
            $this->connection->exec($triggerSql);
        }
    }

    public function dropView(string $viewName): void
    {
        $this->connection->exec(
            $this->getDriver()->dropViewSql($viewName)
        );
    }

    public function createView(string $viewDefinition): void
    {
        $this->connection->exec($viewDefinition);
    }

    public function insert(string $tableName, array $row): void
    {
        $this->connection->insert($tableName, $this->sanitiseRowKeys($row));
    }

    public function query(string $sql): void
    {
        $this->connection->exec($sql);
    }

    private function sanitiseRowKeys(array $row): array
    {
        foreach (array_keys($row) as $key) {
            $row[$this->connection->quoteIdentifier($key)] = $row[$key];
            unset($row[$key]);
        }

        return $row;
    }
}
