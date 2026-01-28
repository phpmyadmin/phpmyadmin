<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportMediawiki;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

use function __;

#[CoversClass(ImportMediawiki::class)]
#[Medium]
final class ImportMediawikiTest extends AbstractTestCase
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
        Current::$database = '';
        ImportSettings::$skipQueries = 0;
        ImportSettings::$maxSqlLength = 0;
        ImportSettings::$sqlQueryDisabled = false;
        Current::$sqlQuery = '';
        ImportSettings::$executedQueries = 0;
        ImportSettings::$runQuery = false;
        ImportSettings::$goSql = false;
        ImportSettings::$importType = 'database';
        ImportSettings::$finished = false;
        ImportSettings::$readLimit = 100000000;
        ImportSettings::$offset = 0;
        ImportSettings::$importFile = 'tests/test_data/phpmyadmin.mediawiki';
        Import::$importText = 'ImportMediawiki_Test';
        ImportSettings::$readMultiply = 10;
    }

    /**
     * Test for getProperties
     */
    public function testGetProperties(): void
    {
        $importMediawiki = $this->getImportMediawiki();
        $properties = $importMediawiki->getProperties();
        self::assertSame(
            __('MediaWiki Table'),
            $properties->getText(),
        );
        self::assertSame(
            'txt',
            $properties->getExtension(),
        );
        self::assertSame(
            'text/plain',
            $properties->getMimeType(),
        );
        self::assertNull($properties->getOptions());
        self::assertSame(
            __('Options'),
            $properties->getOptionsText(),
        );
    }

    /**
     * Test for doImport
     */
    public function testDoImport(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $importMediawiki = $this->getImportMediawiki($dbi);

        //Test function called
        $importMediawiki->doImport($importHandle);

        // If import successfully, PMA will show all databases and
        // tables imported as following HTML Page
        /*
           The following structures have either been created or altered. Here you
           can:
           View a structure's contents by clicking on its name
           Change any of its settings by clicking the corresponding "Options" link
           Edit structure by following the "Structure" link

           mediawiki_DB (Options)
           pma_bookmarktest (Structure) (Options)
        */

        //asset that all databases and tables are imported
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            ImportSettings::$importNotice,
        );
        self::assertStringContainsString('Go to database: `mediawiki_DB`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `mediawiki_DB`', ImportSettings::$importNotice);
        self::assertStringContainsString('Go to table: `pma_bookmarktest`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `pma_bookmarktest`', ImportSettings::$importNotice);
        self::assertTrue(ImportSettings::$finished);
    }

    public function testDoImportWithEmptyTable(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $importHandle = new File('tests/test_data/__slashes.mediawiki');
        $importHandle->open();

        $importMediawiki = $this->getImportMediawiki($dbi);

        //Test function called
        $importMediawiki->doImport($importHandle);

        // If import successfully, PMA will show all databases and
        // tables imported as following HTML Page
        /*
           The following structures have either been created or altered. Here you
           can:
           View a structure's contents by clicking on its name
           Change any of its settings by clicking the corresponding "Options" link
           Edit structure by following the "Structure" link

           mediawiki_DB (Options)
           pma_bookmarktest (Structure) (Options)
        */

        //asset that all databases and tables are imported
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            ImportSettings::$importNotice,
        );
        self::assertStringContainsString('Go to database: `mediawiki_DB`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `mediawiki_DB`', ImportSettings::$importNotice);
        self::assertStringContainsString('Go to table: `empty`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `empty`', ImportSettings::$importNotice);
        self::assertTrue(ImportSettings::$finished);
    }

    private function getImportMediawiki(DatabaseInterface|null $dbi = null): ImportMediawiki
    {
        $dbiObject = $dbi ?? $this->createDatabaseInterface();
        $config = new Config();

        return new ImportMediawiki(new Import($dbiObject, new ResponseRenderer(), $config), $dbiObject, $config);
    }
}
