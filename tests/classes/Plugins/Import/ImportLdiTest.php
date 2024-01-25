<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportLdi;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

use function __;

#[CoversClass(ImportLdi::class)]
class ImportLdiTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        ImportSettings::$charsetConversion = false;
        $GLOBALS['ldi_terminated'] = null;
        $GLOBALS['ldi_escaped'] = null;
        $GLOBALS['ldi_columns'] = null;
        $GLOBALS['ldi_enclosed'] = null;
        $GLOBALS['ldi_new_line'] = null;
        ImportSettings::$maxSqlLength = 0;
        $GLOBALS['sql_query'] = '';
        ImportSettings::$executedQueries = 0;
        ImportSettings::$skipQueries = 0;
        $GLOBALS['run_query'] = null;
        ImportSettings::$goSql = false;
        //setting
        $GLOBALS['plugin_param'] = 'table';
        $GLOBALS['finished'] = false;
        ImportSettings::$readLimit = 100000000;
        $GLOBALS['offset'] = 0;
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;

        $GLOBALS['import_file'] = 'tests/test_data/db_test_ldi.csv';
        $GLOBALS['import_text'] = 'ImportLdi_Test';
        ImportSettings::$readMultiply = 10;
        $GLOBALS['import_type'] = 'csv';

        //setting for Ldi
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
    #[Group('medium')]
    public function testGetProperties(): void
    {
        $properties = (new ImportLdi())->getProperties();
        self::assertEquals(
            __('CSV using LOAD DATA'),
            $properties->getText(),
        );
        self::assertEquals(
            'ldi',
            $properties->getExtension(),
        );
    }

    /**
     * Test for getProperties for ldi_local_option = auto
     */
    #[Group('medium')]
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
        self::assertEquals(
            __('CSV using LOAD DATA'),
            $properties->getText(),
        );
        self::assertEquals(
            'ldi',
            $properties->getExtension(),
        );
    }

    /**
     * Test for doImport
     */
    #[Group('medium')]
    public function testDoImport(): void
    {
        //$sql_query_disabled will show the import SQL detail

        ImportSettings::$sqlQueryDisabled = false;
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        DatabaseInterface::$instance = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        (new ImportLdi())->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'LOAD DATA INFILE \'tests/test_data/db_test_ldi.csv\' INTO TABLE `phpmyadmintest`',
            $GLOBALS['sql_query'],
        );

        self::assertTrue($GLOBALS['finished']);
    }

    /**
     * Test for doImport : invalid import file
     */
    #[Group('medium')]
    public function testDoImportInvalidFile(): void
    {
        $GLOBALS['import_file'] = 'none';

        //Test function called
        (new ImportLdi())->doImport();

        // We handle only some kind of data!
        self::assertStringContainsString(
            __('This plugin does not support compressed imports!'),
            $GLOBALS['message']->__toString(),
        );

        self::assertTrue($GLOBALS['error']);
    }

    /**
     * Test for doImport with LDI setting
     */
    #[Group('medium')]
    public function testDoImportLDISetting(): void
    {
        //$sql_query_disabled will show the import SQL detail

        ImportSettings::$sqlQueryDisabled = false;
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        DatabaseInterface::$instance = $dbi;

        $GLOBALS['ldi_local_option'] = true;
        $GLOBALS['ldi_replace'] = true;
        $GLOBALS['ldi_ignore'] = true;
        $GLOBALS['ldi_terminated'] = ',';
        $GLOBALS['ldi_enclosed'] = ')';
        $GLOBALS['ldi_new_line'] = 'newline_mark';
        ImportSettings::$skipQueries = 1;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        (new ImportLdi())->doImport($importHandle);

        //asset that all sql are executed
        //replace
        self::assertStringContainsString(
            'LOAD DATA LOCAL INFILE \'tests/test_data/db_test_ldi.csv\' REPLACE INTO TABLE `phpmyadmintest`',
            $GLOBALS['sql_query'],
        );

        //FIELDS TERMINATED
        self::assertStringContainsString("FIELDS TERMINATED BY ','", $GLOBALS['sql_query']);

        //LINES TERMINATED
        self::assertStringContainsString("LINES TERMINATED BY 'newline_mark'", $GLOBALS['sql_query']);

        //IGNORE
        self::assertStringContainsString('IGNORE 1 LINES', $GLOBALS['sql_query']);

        self::assertTrue($GLOBALS['finished']);
    }
}
