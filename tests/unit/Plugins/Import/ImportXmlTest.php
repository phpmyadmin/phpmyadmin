<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportXml;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function __;

#[CoversClass(ImportXml::class)]
#[RequiresPhpExtension('xml')]
#[RequiresPhpExtension('xmlwriter')]
#[Medium]
final class ImportXmlTest extends AbstractTestCase
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
        ImportSettings::$finished = false;
        ImportSettings::$readLimit = 100000000;
        ImportSettings::$offset = 0;
        ImportSettings::$importFile = 'tests/test_data/phpmyadmin_importXML_For_Testing.xml';
        Import::$importText = 'ImportXml_Test';
        ImportSettings::$readMultiply = 10;
    }

    /**
     * Test for getProperties
     */
    public function testGetProperties(): void
    {
        $importXml = $this->getImportXml();
        $properties = $importXml->getProperties();
        self::assertSame(
            __('XML'),
            $properties->getText(),
        );
        self::assertSame(
            'xml',
            $properties->getExtension(),
        );
        self::assertSame(
            'text/xml',
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
    #[RequiresPhpExtension('simplexml')]
    public function testDoImport(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $importXml = $this->getImportXml($dbi);
        $importXml->doImport($importHandle);

        // If import successfully, PMA will show all databases and tables
        // imported as following HTML Page
        /*
           The following structures have either been created or altered. Here you
           can:
           View a structure's contents by clicking on its name
           Change any of its settings by clicking the corresponding "Options" link
           Edit structure by following the "Structure" link

           phpmyadmintest (Options)
           pma_bookmarktest (Structure) (Options)
        */

        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            ImportSettings::$importNotice,
        );
        self::assertStringContainsString('Go to database: `phpmyadmintest`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `phpmyadmintest`', ImportSettings::$importNotice);
        self::assertStringContainsString('Go to table: `pma_bookmarktest`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `pma_bookmarktest`', ImportSettings::$importNotice);
        self::assertTrue(ImportSettings::$finished);
    }

    private function getImportXml(DatabaseInterface|null $dbi = null): ImportXml
    {
        $dbiObject = $dbi ?? $this->createDatabaseInterface();
        $config = new Config();

        return new ImportXml(new Import($dbiObject, new ResponseRenderer(), $config), $dbiObject, $config);
    }
}
