<?php

namespace Quidco\DbSampler\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Quidco\DbSampler\Database\DestinationDatabase;
use Quidco\DbSampler\Database\SourceDatabase;

abstract class SqliteBasedTestCase extends TestCase
{
    /**
     * @var string
     */
    protected $fixturesDir;
    /**
     * @var DestinationDatabase
     */
    protected $destination;
    /**
     * @var SourceDatabase
     */
    protected $source;

    /**
     * Create config file and connections
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures';
        $sqliteConfig = ['driver' => 'pdo_sqlite', 'directory' => $this->fixturesDir . '/sqlite-dbs'];
        file_put_contents(
            $this->fixturesDir . '/sqlite-credentials.json',
            json_encode($sqliteConfig, JSON_PRETTY_PRINT)
        );

        $this->setupDbConnections();
        $this->populateSqliteDb();
        parent::setUp();
    }

    /**
     * Remove temporary files
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unlink($this->fixturesDir . '/sqlite-credentials.json');
    }

    /**
     * Create a sqlite DB with known content
     *
     * @return void
     */
    protected function populateSqliteDb()
    {

        $sql = explode(';', file_get_contents($this->fixturesDir . '/small-source.sql'));

        foreach ($sql as $command) {
            if (trim($command)) {
                $this->source->getConnection()->exec($command);
            }
        }
    }

    /**
     * Create DB handles for source & dest DBs
     *
     * @return void
     */
    protected function setupDbConnections()
    {
        $this->source = new SourceDatabase(DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'path' => $this->fixturesDir . '/sqlite-dbs/small-source.sqlite',
            ]
        ));

        $this->destination = new DestinationDatabase(DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'path' => $this->fixturesDir . '/sqlite-dbs/small-dest.sqlite',
            ]
        ));
    }
}
