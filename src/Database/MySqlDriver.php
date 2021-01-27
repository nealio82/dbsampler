<?php

namespace Quidco\DbSampler\Database;

// @todo: add tests for this class
class MySqlDriver extends Driver
{
    public function getName(): string
    {
        return 'mysql';
    }

    public function dropTableSql(string $tableName): string
    {
        return 'DROP TABLE IF EXISTS ' . $this->connection->quoteIdentifier($tableName);
    }

    public function createTableSql(string $tableName): string
    {
        $createSqlRow = $this
            ->connection
            ->query('SHOW CREATE TABLE ' . $this->connection->quoteIdentifier($tableName))
            ->fetch(\PDO::FETCH_ASSOC);

        return $createSqlRow['Create Table'];
    }

    public function migrateTableTriggersSql(string $tableName): iterable
    {
        $triggers = $this->connection->fetchAll('SHOW TRIGGERS WHERE `Table`=' . $this->connection->quote($tableName));
        if ($triggers && count($triggers) > 0) {
            foreach ($triggers as $trigger) {
                yield 'CREATE TRIGGER ' . $trigger['Trigger'] . ' ' . $trigger['Timing'] . ' ' . $trigger['Event'] .
                    ' ON ' . $this->connection->quoteIdentifier($trigger['Table']) . ' FOR EACH ROW ' . PHP_EOL . $trigger['Statement'] . '; ';
            }
        }
    }

    public function dropViewSql(string $viewName): string
    {
        return 'DROP VIEW IF EXISTS ' . $this->connection->quoteIdentifier($viewName);
    }

    public function createViewSql(string $viewName): string
    {
        $createSqlRow = $this->connection
            ->query('SHOW CREATE VIEW ' . $this->connection->quoteIdentifier($viewName))
            ->fetch(\PDO::FETCH_ASSOC);

        $createSql = $createSqlRow['Create View'];

        $currentDestUser = $this->connection->fetchColumn('SELECT CURRENT_USER()');

        if ($currentDestUser) {
            //Because MySQL. SELECT CURRENT_USER() returns an unescaped user
            $currentDestUser = implode('@', array_map(function ($p) {
                return $this->connection->getDatabasePlatform()->quoteSingleIdentifier($p);
            }, explode('@', $currentDestUser)));

            $createSql = preg_replace('/\bDEFINER=`[^`]+`@`[^`]+`(?=\s)/', "DEFINER=$currentDestUser", $createSql);
        }

        return $createSql;
    }
}
