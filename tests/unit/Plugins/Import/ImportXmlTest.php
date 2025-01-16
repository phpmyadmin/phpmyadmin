<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportXml;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function __;

#[CoversClass(ImportXml::class)]
#[RequiresPhpExtension('xml')]
#[RequiresPhpExtension('xmlwriter')]
#[Medium]
class ImportXmlTest extends AbstractTestCase
{
    protected ImportXml $object;

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

        $this->object = new ImportXml();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * Test for getProperties
     */
    public function testGetProperties(): void
    {
        $properties = $this->object->getProperties();
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
        DatabaseInterface::$instance = $dbi;

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $this->object->doImport($importHandle);

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
}
