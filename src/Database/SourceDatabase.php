<?php

namespace Quidco\DbSampler\Database;

// @todo: add tests for this class
class SourceDatabase extends Database
{
    public function getTableDefinition(string $tableName): string
    {
        return $this->getDriver()->createTableSql($tableName);
    }

    public function getTriggersDefinition(string $tableName): iterable
    {
        return $this->getDriver()->migrateTableTriggersSql($tableName);
    }

    public function getViewDefinition(string $viewName): string
    {
        return $this->getDriver()->createViewSql($viewName);
    }
}
