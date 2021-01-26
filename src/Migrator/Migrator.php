<?php

namespace Quidco\DbSampler\Migrator;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Quidco\DbSampler\Cleaner\FieldCleaner;
use Quidco\DbSampler\Cleaner\RowCleaner;
use Quidco\DbSampler\Collection\TableCollection;
use Quidco\DbSampler\Collection\ViewCollection;
use Quidco\DbSampler\Database\DestinationDatabase;
use Quidco\DbSampler\Database\SourceDatabase;
use Quidco\DbSampler\ReferenceStore;
use Quidco\DbSampler\Sampler\Sampler;
use Quidco\DbSampler\SamplerMap\SamplerMap;
use Quidco\DbSampler\Writer\Writer;

/**
 * Migrator class to handle all migrations in a set
 */
class Migrator
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Connection
     */
    private $sourceConnection;

    /**
     * @var Connection
     */
    private $destConnection;

    /**
     * @var ReferenceStore
     */
    private $referenceStore;

    private $customCleaners = [];

    public function __construct(
        Connection $sourceConnection,
        Connection $destConnection,
        LoggerInterface $logger
    )
    {
        $this->sourceConnection = $sourceConnection;
        $this->destConnection = $destConnection;
        $this->logger = $logger;
        $this->referenceStore = new ReferenceStore();
    }

    /**
     * Perform the configured migrations
     *
     * @throws \Exception Rethrows any exceptions after logging
     */
    public function execute(string $setName, TableCollection $tableCollection, ViewCollection $viewCollection): void
    {
        $source = new SourceDatabase($this->sourceConnection);
        $destination = new DestinationDatabase($this->destConnection);

        if ($source->getDriver()->getName() !== $destination->getDriver()->getName()) {
            throw new \RuntimeException('Source and destination must use the same driver!');
        }

        foreach ($tableCollection->getTables() as $table => $migrationSpec) {
            // @todo: it'd probably be better to have a proper `migrationspec` config object
            // rather than relying on properties being present in the json / stdClass object

            $sampler = $this->buildTableSampler($migrationSpec, $table);
            $writer = new Writer($migrationSpec, $this->destConnection);
            $cleaner = new RowCleaner($migrationSpec);

            foreach ($this->customCleaners as $alias => $customCleaner) {
                $cleaner->registerCleaner($customCleaner, $alias);
            }

            try {
                $this->ensureEmptyTargetTable($table, $source, $destination);
                $rows = $sampler->execute();

                foreach ($rows as $row) {
                    $writer->write($table, $cleaner->cleanRow($row));
                }
                $writer->postWrite();

                $this->logger->info("$setName: migrated '$table' with '" . $sampler->getName() . "': " . \count($rows) . " rows");
            } catch (\Exception $e) {
                $this->logger->error(
                    "$setName: failed to migrate '$table' with '" . $sampler->getName() . "': " . $e->getMessage()
                );
                throw $e;
            }
        }

        foreach ($viewCollection->getViews() as $view) {
            $this->migrateView($view, $setName, $source, $destination);
        }

        $this->migrateTableTriggers($setName, $tableCollection, $source, $destination);
    }

    public function registerCustomCleaner(FieldCleaner $cleaner, string $alias): void
    {
        $this->customCleaners[$alias] = $cleaner;
    }

    /**
     * Ensure that the specified table is present in the destination DB as an empty copy of the source
     *
     * @param string $table Table name
     */
    private function ensureEmptyTargetTable(string $table, SourceDatabase $source, DestinationDatabase $destination): void
    {
        $destination->dropTable($table);
        $destination->createTable($source->getTableDefinition($table));
    }

    /**
     * Ensure that all table triggers from source are recreated in the destination
     *
     * @return void
     * @throws \RuntimeException If DB type not supported
     * @throws \Doctrine\DBAL\DBALException If target trigger cannot be recreated
     */
    private function migrateTableTriggers(
        string $setName,
        TableCollection $tableCollection,
        SourceDatabase $source,
        DestinationDatabase $destination
    ): void
    {
        try {
            foreach ($tableCollection->getTables() as $table => $sampler) {
                $destination->migrateTableTriggers($source->getTriggersDefinition($table));
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "$setName: failed to migrate '$table' with '" . $sampler->getName() . "': " . $e->getMessage()
            );
        }
    }

    /**
     * Migrate a view from source to dest DB
     *
     * @param string $view Name of view to migrate
     * @param string $setName Name of migration set being executed
     */
    protected function migrateView(
        string $view,
        string $setName,
        SourceDatabase $source,
        DestinationDatabase $destination
    ): void
    {
        $destination->dropView($view);
        $destination->createView($source->getViewDefinition($view));

        $this->logger->info("$setName: migrated view '$view'");
    }

    /**
     * Build a Sampler object from configuration
     *
     * @param \stdClass $migrationSpec
     * @param string $tableName
     */
    private function buildTableSampler(\stdClass $migrationSpec, string $tableName): Sampler
    {
        $sampler = null;

        // @todo: $migrationSpec should be an object with a getSampler() method
        $samplerType = strtolower($migrationSpec->sampler);
        if (array_key_exists($samplerType, SamplerMap::MAP)) {
            $samplerClass = SamplerMap::MAP[$samplerType];
            $sampler = new $samplerClass(
                $migrationSpec,
                $this->referenceStore,
                $this->sourceConnection,
                $tableName
            );
        } else {
            throw new \RuntimeException("Unrecognised sampler type '$samplerType' required");
        }

        return $sampler;
    }
}
