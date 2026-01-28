<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportSql;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

#[CoversClass(ImportSql::class)]
#[Medium]
final class ImportSqlTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Import::$hasError = false;
        ImportSettings::$timeoutPassed = false;
        ImportSettings::$maximumTime = 0;
        ImportSettings::$charsetConversion = false;
        ImportSettings::$skipQueries = 0;
        ImportSettings::$maxSqlLength = 0;
        Current::$sqlQuery = '';
        ImportSettings::$executedQueries = 0;
        ImportSettings::$runQuery = false;
        ImportSettings::$goSql = false;
        ImportSettings::$finished = false;
        ImportSettings::$readLimit = 100000000;
        ImportSettings::$offset = 0;
        ImportSettings::$importFile = 'tests/test_data/pma_bookmark.sql';
        Import::$importText = 'ImportSql_Test';
        ImportSettings::$readMultiply = 10;
    }

    /**
     * Test for doImport
     */
    public function testDoImport(): void
    {
        ImportSettings::$sqlQueryDisabled = false; // will show the import SQL detail

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $importSql = $this->getImportSql();
        $importSql->doImport($importHandle);

        self::assertStringContainsString('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"', Current::$sqlQuery);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS `pma_bookmark`', Current::$sqlQuery);
        self::assertStringContainsString(
            'INSERT INTO `pma_bookmark` (`id`, `dbase`, `user`, `label`, `query`) VALUES',
            Current::$sqlQuery,
        );

        self::assertTrue(ImportSettings::$finished);
    }

    private function getImportSql(): ImportSql
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();

        return new ImportSql(new Import($dbi, new ResponseRenderer(), $config), $dbi, $config);
    }
}
