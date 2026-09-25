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
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;

use function __;

#[CoversClass(ImportLdi::class)]
#[Medium]
#[AllowMockObjectsWithoutExpectations]
final class ImportLdiTest extends AbstractTestCase
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
        ImportSettings::$importFile = 'tests/test_data/db_test_ldi.csv';
        Import::$importText = 'ImportLdi_Test';
        ImportSettings::$readMultiply = 10;
        Current::$table = 'phpmyadmintest';
    }

    /**
     * Test for getProperties
     */
    public function testGetProperties(): void
    {
        $config = new Config();
        $config->settings['Import']['ldi_local_option'] = false;

        $properties = $this->getImportLdi(config: $config)->getProperties();
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
        $dbi = $this->createMock(DatabaseInterface::class);

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->method('tryQuery')->willReturn($resultStub);

        $resultStub->method('numRows')->willReturn(10);

        $resultStub->method('fetchValue')->willReturn('ON');

        $config = new Config();
        $config->settings['Import']['ldi_local_option'] = 'auto';

        $properties = $this->getImportLdi($dbi, $config)->getProperties();
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
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        //Test function called
        $this->getImportLdi($dbi)->doImport($importHandle);

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

        $config = new Config();
        $config->settings['Import']['ldi_local_option'] = false;

        //Test function called
        $this->getImportLdi(config: $config)->doImport();

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
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

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
        $object = $this->getImportLdi($dbi);
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

    #[DataProvider('charsetMappingProvider')]
    public function testDoImportWithCharsetConversion(
        string $charsetOfFile,
        string $expectedCharset,
    ): void {
        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$charsetConversion = true;
        ImportSettings::$charsetOfFile = $charsetOfFile;

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $this->getImportLdi($dbi)->doImport($importHandle);

        self::assertStringContainsString('CHARACTER SET ' . $expectedCharset, Current::$sqlQuery);
    }

    /** @return array<string, array{string, string}> */
    public static function charsetMappingProvider(): array
    {
        return [
            'ISO-8859-1' => ['iso-8859-1', 'latin1'],
            'ISO-8859-2' => ['iso-8859-2', 'latin2'],
            'ISO-8859-7' => ['iso-8859-7', 'greek'],
            'ISO-8859-8' => ['iso-8859-8', 'hebrew'],
            'ISO-8859-9' => ['iso-8859-9', 'latin5'],
            'ISO-8859-13' => ['iso-8859-13', 'latin7'],
            'KOI8-R' => ['koi8-r', 'koi8r'],
            'Windows-1250' => ['windows-1250', 'cp1250'],
            'Windows-1251' => ['windows-1251', 'cp1251'],
            'Windows-1252' => ['windows-1252', 'latin1'],
            'Windows-1256' => ['windows-1256', 'cp1256'],
            'Windows-1257' => ['windows-1257', 'cp1257'],
            'TIS-620' => ['tis-620', 'tis620'],
            'Shift_JIS' => ['SHIFT_JIS', 'sjis'],
            'SJIS' => ['SJIS', 'sjis'],
            'SJIS-win' => ['SJIS-win', 'cp932'],
            'Big5' => ['big5', 'big5'],
            'GB2312' => ['gb2312', 'gb2312'],
            'euc-jp' => ['euc-jp', 'ujis'],
            'ks_c_5601-1987' => ['ks_c_5601-1987', 'euckr'],
        ];
    }

    public function testDoImportWithUtf16OnMariaDb(): void
    {
        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$charsetConversion = true;
        ImportSettings::$charsetOfFile = 'utf-16';

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $dbi->method('isMariaDB')->willReturn(true);

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $this->getImportLdi($dbi)->doImport($importHandle);

        self::assertStringContainsString('CHARACTER SET utf16', Current::$sqlQuery);
    }

    public function testDoImportWithUtf16OnMySql(): void
    {
        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$charsetConversion = true;
        ImportSettings::$charsetOfFile = 'utf-16';

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $dbi->method('isMariaDB')->willReturn(false);

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $importLdi = $this->getImportLdi($dbi);
        $result = $importLdi->doImport($importHandle);

        self::assertSame([], $result);
        self::assertTrue(Import::$hasError);
        self::assertInstanceOf(Message::class, Current::$message);
        self::assertStringContainsString(
            __('The selected character set is not supported by the database system!'),
            Current::$message->__toString(),
        );
    }

    public function testDoImportWithUnsupportedCharsetDoesNotAddCharacterSet(): void
    {
        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$charsetConversion = true;
        ImportSettings::$charsetOfFile = 'iso-8859-3';

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $this->getImportLdi($dbi)->doImport($importHandle);

        self::assertStringNotContainsString('CHARACTER SET', Current::$sqlQuery);
    }

    private function getImportLdi(DatabaseInterface|null $dbi = null, Config|null $config = null): ImportLdi
    {
        $dbiObject = $dbi ?? $this->createDatabaseInterface();
        $configObject = $config ?? new Config();

        return new ImportLdi(new Import($dbiObject, new ResponseRenderer(), $configObject), $dbiObject, $configObject);
    }
}
