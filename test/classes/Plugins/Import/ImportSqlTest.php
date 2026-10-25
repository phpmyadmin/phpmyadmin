<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportSql;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

use function file_put_contents;
use function strlen;
use function tempnam;
use function time;
use function unlink;

/**
 * @covers \PhpMyAdmin\Plugins\Import\ImportSql
 * @medium
 */
#[CoversClass(ImportSql::class)]
#[Medium]
class ImportSqlTest extends AbstractTestCase
{
    /** @var ImportSql */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;

        $this->object = new ImportSql();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/pma_bookmark.sql';
        $GLOBALS['import_text'] = 'ImportSql_Test';
        $GLOBALS['compression'] = 'none';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'Xml';
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
     * Test for doImport
     */
    public function testDoImport(): void
    {
        //$sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        //Mock DBI
        $dbi = $this->createStub(DatabaseInterface::class);
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"', $sql_query);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS `pma_bookmark`', $sql_query);
        self::assertStringContainsString(
            'INSERT INTO `pma_bookmark` (`id`, `dbase`, `user`, `label`, `query`) VALUES',
            $sql_query
        );

        self::assertTrue($GLOBALS['finished']);
    }

    /**
     * Test for doImport when the time limit is reached with multibyte data in the buffer
     */
    public function testDoImportWithTimeoutOnMultibyteData(): void
    {
        $GLOBALS['dbi'] = $this->createStub(DatabaseInterface::class);
        $GLOBALS['charset_conversion'] = false;
        $GLOBALS['timeout_passed'] = false;
        $GLOBALS['maximum_time'] = 30;
        $GLOBALS['timestamp'] = time();

        $content = "SELECT 'ąę';\nSELECT 'źżćńół', 'ąę';\n";
        $GLOBALS['read_limit'] = strlen($content) - 9;

        $fileName = (string) tempnam('./', 'pma');
        file_put_contents($fileName, $content);

        // The time limit is reached while the first chunk is processed.
        $importHandle = new class ($fileName) extends File {
            public function read(int $size): string
            {
                $GLOBALS['timestamp'] = 0;

                return parent::read($size);
            }
        };
        $importHandle->open();

        $this->object->doImport($importHandle);
        $importHandle->close();
        unlink($fileName);

        self::assertTrue($GLOBALS['timeout_passed']);
        // The next run skips "offset" bytes, so it has to resume right after the first statement
        self::assertSame(strlen("SELECT 'ąę';"), $GLOBALS['offset']);
    }
}
