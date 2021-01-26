<?php

namespace Quidco\DbSampler\Writer;

use Quidco\DbSampler\Database\DestinationDatabase;

class Writer
{
    /**
     * Commands to run on the destination table after importing
     *
     * @var array
     */
    protected $postImportSql = [];

    /**
     * @var DestinationDatabase
     */
    private $destination;

    public function __construct(\stdClass $config, DestinationDatabase $destination)
    {
        $this->destination = $destination;

        $this->postImportSql = isset($config->postImportSql) ? $config->postImportSql : [];
    }

    public function write(string $tableName, $row): void
    {
        $this->destination->insert($tableName, $row);
    }

    public function postWrite(): void
    {
        foreach ($this->postImportSql as $sql) {
            $this->destination->query($sql);
        }
    }
}
