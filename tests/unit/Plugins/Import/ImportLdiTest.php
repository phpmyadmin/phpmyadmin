<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\Import\ImportLdi;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

use function __;

#[CoversClass(ImportLdi::class)]
#[Medium]
class ImportLdiTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Import::$hasError = false;
        ImportSettings::$charsetConversion = false;
        ImportSettings::$maxSqlLength = 0;
        Current::$sqlQuery = '';
        ImportSettings::$executedQueries = 0;
        ImportSettings::$skipQueries = 0;
        ImportSettings::$runQuery = false;
        ImportSettings::$goSql = false;
        ImportSettings::$importType = 'table';
        ImportSettings::$finished = false;
        ImportSettings::$readLimit = 100000000;
        ImportSettings::$offset = 0;
        $config = Config::getInstance();

        ImportSettings::$importFile = 'tests/test_data/db_test_ldi.csv';
        $GLOBALS['import_text'] = 'ImportLdi_Test';
        ImportSettings::$readMultiply = 10;

        $config->settings['Import']['ldi_replace'] = false;
        $config->settings['Import']['ldi_ignore'] = false;
        $config->settings['Import']['ldi_terminated'] = ';';
        $config->settings['Import']['ldi_enclosed'] = '"';
        $config->settings['Import']['ldi_escaped'] = '\\';
        $config->settings['Import']['ldi_new_line'] = 'auto';
        $config->settings['Import']['ldi_columns'] = '';
        $config->settings['Import']['ldi_local_option'] = false;
        Current::$table = 'phpmyadmintest';
    }

    /**
     * Test for getProperties
     */
    public function testGetProperties(): void
    {
        $properties = (new ImportLdi())->getProperties();
        self::assertSame(
            __('CSV using LOAD DATA'),
            $properties->getText(),
        );
        self::assertSame(
            'ldi',
            $properties->getExtension(),
        );
    }

    /**
     * Test for getProperties for ldi_local_option = auto
     */
    public function testGetPropertiesAutoLdi(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        DatabaseInterface::$instance = $dbi;

        $resultStub = self::createMock(DummyResult::class);

        $dbi->expects(self::any())->method('tryQuery')
            ->willReturn($resultStub);

        $resultStub->expects(self::any())->method('numRows')
            ->willReturn(10);

        $resultStub->expects(self::any())->method('fetchValue')
            ->willReturn('ON');

        $config = Config::getInstance();
        $config->settings['Import']['ldi_local_option'] = 'auto';
        $properties = (new ImportLdi())->getProperties();
        self::assertTrue($config->settings['Import']['ldi_local_option']);
        self::assertSame(
            __('CSV using LOAD DATA'),
            $properties->getText(),
        );
        self::assertSame(
            'ldi',
            $properties->getExtension(),
        );
    }

    /**
     * Test for doImport
     */
    public function testDoImport(): void
    {
        ImportSettings::$sqlQueryDisabled = false; //will show the import SQL detail
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        DatabaseInterface::$instance = $dbi;

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        //Test function called
        (new ImportLdi())->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'LOAD DATA INFILE \'tests/test_data/db_test_ldi.csv\' INTO TABLE `phpmyadmintest`',
            Current::$sqlQuery,
        );

        self::assertTrue(ImportSettings::$finished);
    }

    /**
     * Test for doImport : invalid import file
     */
    public function testDoImportInvalidFile(): void
    {
        ImportSettings::$importFile = 'none';

        //Test function called
        (new ImportLdi())->doImport();

        // We handle only some kind of data!
        self::assertInstanceOf(Message::class, Current::$message);
        self::assertStringContainsString(
            __('This plugin does not support compressed imports!'),
            Current::$message->__toString(),
        );

        self::assertTrue(Import::$hasError);
    }

    /**
     * Test for doImport with LDI setting
     */
    public function testDoImportLDISetting(): void
    {
        ImportSettings::$sqlQueryDisabled = false; //will show the import SQL detail
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'ldi_local_option' => '1',
                'ldi_replace' => '1',
                'ldi_ignore' => '1',
                'ldi_terminated' => ',',
                'ldi_enclosed' => ')',
                'ldi_escaped' => null,
                'ldi_new_line' => 'newline_mark',
                'ldi_columns' => null,
            ]);
        ImportSettings::$skipQueries = 1;

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        //Test function called
        $object = new ImportLdi();
        $object->setImportOptions($request);
        $object->doImport($importHandle);

        //asset that all sql are executed
        //replace
        self::assertStringContainsString(
            'LOAD DATA LOCAL INFILE \'tests/test_data/db_test_ldi.csv\' REPLACE INTO TABLE `phpmyadmintest`',
            Current::$sqlQuery,
        );

        //FIELDS TERMINATED
        self::assertStringContainsString("FIELDS TERMINATED BY ','", Current::$sqlQuery);

        //LINES TERMINATED
        self::assertStringContainsString("LINES TERMINATED BY 'newline_mark'", Current::$sqlQuery);

        //IGNORE
        self::assertStringContainsString('IGNORE 1 LINES', Current::$sqlQuery);

        self::assertTrue(ImportSettings::$finished);
    }
}
