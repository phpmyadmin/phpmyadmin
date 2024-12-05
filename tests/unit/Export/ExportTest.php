<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\Export\ExportPhparray;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;

use function htmlspecialchars;

use const ENT_COMPAT;

#[CoversClass(Export::class)]
#[Large]
class ExportTest extends AbstractTestCase
{
    public function testMergeAliases(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $export = new Export(DatabaseInterface::getInstance());
        $aliases1 = [
            'test_db' => [
                'alias' => 'aliastest',
                'tables' => [
                    'foo' => ['alias' => 'foobar', 'columns' => ['bar' => 'foo', 'baz' => 'barbaz']],
                    'bar' => ['alias' => 'foobaz', 'columns' => ['a' => 'a_alias', 'b' => 'b']],
                ],
            ],
        ];
        $aliases2 = [
            'test_db' => [
                'alias' => 'test',
                'tables' => ['foo' => ['columns' => ['bar' => 'foobar']], 'baz' => ['columns' => ['a' => 'x']]],
            ],
        ];
        $expected = [
            'test_db' => [
                'alias' => 'test',
                'tables' => [
                    'foo' => ['alias' => 'foobar', 'columns' => ['bar' => 'foobar', 'baz' => 'barbaz']],
                    'bar' => ['alias' => 'foobaz', 'columns' => ['a' => 'a_alias', 'b' => 'b']],
                    'baz' => ['columns' => ['a' => 'x']],
                ],
            ],
        ];
        $actual = $export->mergeAliases($aliases1, $aliases2);
        self::assertSame($expected, $actual);
    }

    public function testGetFinalFilename(): void
    {
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $export = new Export($dbi);
        $exportPlugin = new ExportPhparray(
            new Relation($dbi),
            new Export($dbi),
            new Transformations(),
        );
        $finalFileName = $export->getFinalFilename($exportPlugin, 'zip', 'myfilename');
        self::assertSame('myfilename.php.zip', $finalFileName);
        $finalFileName = $export->getFinalFilename($exportPlugin, 'gzip', 'myfilename');
        self::assertSame('myfilename.php.gz', $finalFileName);
        $finalFileName = $export->getFinalFilename($exportPlugin, 'gzip', 'export.db1.table1.file');
        self::assertSame('export.db1.table1.file.php.gz', $finalFileName);
    }

    public function testGetMimeType(): void
    {
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $export = new Export($dbi);
        $exportPlugin = new ExportPhparray(
            new Relation($dbi),
            new Export($dbi),
            new Transformations(),
        );
        $mimeType = $export->getMimeType($exportPlugin, 'zip');
        self::assertSame('application/zip', $mimeType);
        $mimeType = $export->getMimeType($exportPlugin, 'gzip');
        self::assertSame('application/x-gzip', $mimeType);
    }

    public function testExportDatabase(): void
    {
        $GLOBALS['plugin_param'] = ['export_type' => 'database', 'single_table' => false];
        $GLOBALS['sql_create_view'] = 'something';
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['sql_structure_or_data'] = 'structure_and_data';
        Config::getInstance()->selectedServer['DisableIS'] = false;
        $GLOBALS['sql_insert_syntax'] = 'both';
        $GLOBALS['sql_max_query_size'] = '50000';

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'test_db\' AND TABLE_NAME = \'test_table\'',
            [],
            ['TABLE_NAME'],
        );
        $dbiDummy->addResult(
            'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\') AND t.`TABLE_NAME` COLLATE utf8_bin = \'test_table\' ORDER BY Name ASC',
            [['def', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '4', '2011-12-13 14:15:16', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '4', '2011-12-13 14:15:16', null, null, 'utf8mb4_general_ci', null, '', '']],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'TABLE_TYPE', 'ENGINE', 'VERSION', 'ROW_FORMAT', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_COLLATION', 'CHECKSUM', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'MAX_INDEX_LENGTH', 'TEMPORARY', 'Db', 'Name', 'TABLE_TYPE', 'Engine', 'Type', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment'],
        );
        $dbiDummy->addResult(
            'SELECT `id`, `name`, `datetimefield` FROM `test_db`.`test_table`',
            [
                ['1', 'abcd', '2011-01-20 02:00:02'],
                ['2', 'foo', '2010-01-20 02:00:02'],
                ['3', 'Abcd', '2012-01-20 02:00:02'],
            ],
            ['id', 'name', 'datetimefield'],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $export = new Export($dbi);

        $export->exportDatabase(
            DatabaseName::from('test_db'),
            ['test_table'],
            'structure_and_data',
            ['test_table'],
            ['test_table'],
            new ExportSql(new Relation($dbi), $export, new Transformations()),
            'index.php?route=/database/export&db=test_db',
            'database',
            false,
            true,
            false,
            false,
            [],
            '',
        );

        $expected = <<<'SQL'

INSERT INTO test_table (id, name, datetimefield) VALUES
('1', 'abcd', '2011-01-20 02:00:02'),
('2', 'foo', '2010-01-20 02:00:02'),
('3', 'Abcd', '2012-01-20 02:00:02');

SQL;

        self::assertSame(htmlspecialchars($expected, ENT_COMPAT), $this->getActualOutputForAssertion());
    }

    public function testExportServer(): void
    {
        $GLOBALS['plugin_param'] = ['export_type' => 'server', 'single_table' => false];
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['only_db'] = '';
        $GLOBALS['sql_structure_or_data'] = 'structure_and_data';
        $GLOBALS['sql_insert_syntax'] = 'both';
        $GLOBALS['sql_max_query_size'] = '50000';

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['test_db']],
            ['SCHEMA_NAME'],
        );
        $dbiDummy->addResult(
            'SHOW TABLES FROM `test_db`;',
            [['test_table']],
            ['Tables_in_test_db'],
        );
        $dbiDummy->addResult(
            'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = \'test_db\' LIMIT 1',
            [['utf8mb4_general_ci']],
            ['DEFAULT_COLLATION_NAME'],
        );
        $dbiDummy->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'test_db\' AND TABLE_NAME = \'test_table\'',
            [],
            ['TABLE_NAME'],
        );
        $dbiDummy->addResult(
            'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\') AND t.`TABLE_NAME` COLLATE utf8_bin = \'test_table\' ORDER BY Name ASC',
            [['def', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '4', '2011-12-13 14:15:16', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '4', '2011-12-13 14:15:16', null, null, 'utf8mb4_general_ci', null, '', '']],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'TABLE_TYPE', 'ENGINE', 'VERSION', 'ROW_FORMAT', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_COLLATION', 'CHECKSUM', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'MAX_INDEX_LENGTH', 'TEMPORARY', 'Db', 'Name', 'TABLE_TYPE', 'Engine', 'Type', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment'],
        );
        $dbiDummy->addResult(
            'SELECT `id`, `name`, `datetimefield` FROM `test_db`.`test_table`',
            [
                ['1', 'abcd', '2011-01-20 02:00:02'],
                ['2', 'foo', '2010-01-20 02:00:02'],
                ['3', 'Abcd', '2012-01-20 02:00:02'],
            ],
            ['id', 'name', 'datetimefield'],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $export = new Export($dbi);

        $export->exportServer(
            ['test_db'],
            'structure_and_data',
            new ExportSql(new Relation($dbi), $export, new Transformations()),
            'index.php?route=/server/export',
            'server',
            false,
            true,
            false,
            false,
            [],
            '',
        );

        $expected = <<<'SQL'
CREATE DATABASE IF NOT EXISTS test_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE test_db;

INSERT INTO test_table (id, name, datetimefield) VALUES
('1', 'abcd', '2011-01-20 02:00:02'),
('2', 'foo', '2010-01-20 02:00:02'),
('3', 'Abcd', '2012-01-20 02:00:02');

SQL;

        self::assertSame(htmlspecialchars($expected, ENT_COMPAT), $this->getActualOutputForAssertion());
    }

    public function testGetPageLocationAndSaveMessageForServerExportWithError(): void
    {
        $GLOBALS['lang'] = 'en';
        Current::$server = 2;
        $_SESSION = [];
        $dbi = $this->createDatabaseInterface();
        $export = new Export($dbi);
        $location = $export->getPageLocationAndSaveMessage('server', Message::error('Error message!'));
        self::assertSame('index.php?route=/server/export&server=2&lang=en', $location);
        self::assertSame(
            [['context' => 'danger', 'message' => 'Error message!', 'statement' => '']],
            (new FlashMessenger())->getMessages(),
        );
    }

    public function testGetPageLocationAndSaveMessageForServerExportWithSuccess(): void
    {
        $GLOBALS['lang'] = 'en';
        Current::$server = 2;
        $_SESSION = [];
        $dbi = $this->createDatabaseInterface();
        $export = new Export($dbi);
        $location = $export->getPageLocationAndSaveMessage('server', Message::success('Success message!'));
        self::assertSame('index.php?route=/server/export&server=2&lang=en', $location);
        self::assertSame(
            [['context' => 'success', 'message' => 'Success message!', 'statement' => '']],
            (new FlashMessenger())->getMessages(),
        );
    }

    public function testGetPageLocationAndSaveMessageForDatabaseExportWithError(): void
    {
        $GLOBALS['lang'] = 'en';
        Current::$server = 2;
        Current::$database = 'test_db';
        $_SESSION = [];
        $dbi = $this->createDatabaseInterface();
        $export = new Export($dbi);
        $location = $export->getPageLocationAndSaveMessage('database', Message::error('Error message!'));
        self::assertSame('index.php?route=/database/export&db=test_db&server=2&lang=en', $location);
        self::assertSame(
            [['context' => 'danger', 'message' => 'Error message!', 'statement' => '']],
            (new FlashMessenger())->getMessages(),
        );
    }

    public function testGetPageLocationAndSaveMessageForDatabaseExportWithSuccess(): void
    {
        $GLOBALS['lang'] = 'en';
        Current::$server = 2;
        Current::$database = 'test_db';
        $_SESSION = [];
        $dbi = $this->createDatabaseInterface();
        $export = new Export($dbi);
        $location = $export->getPageLocationAndSaveMessage('database', Message::success('Success message!'));
        self::assertSame('index.php?route=/database/export&db=test_db&server=2&lang=en', $location);
        self::assertSame(
            [['context' => 'success', 'message' => 'Success message!', 'statement' => '']],
            (new FlashMessenger())->getMessages(),
        );
    }

    public function testGetPageLocationAndSaveMessageForTableExportWithError(): void
    {
        $GLOBALS['lang'] = 'en';
        Current::$server = 2;
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $_SESSION = [];
        $dbi = $this->createDatabaseInterface();
        $export = new Export($dbi);
        $location = $export->getPageLocationAndSaveMessage('table', Message::error('Error message!'));
        self::assertSame(
            'index.php?route=/table/export&db=test_db&table=test_table&single_table=true&server=2&lang=en',
            $location,
        );
        self::assertSame(
            [['context' => 'danger', 'message' => 'Error message!', 'statement' => '']],
            (new FlashMessenger())->getMessages(),
        );
    }

    public function testGetPageLocationAndSaveMessageForTableExportWithSuccess(): void
    {
        $GLOBALS['lang'] = 'en';
        Current::$server = 2;
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $_SESSION = [];
        $dbi = $this->createDatabaseInterface();
        $export = new Export($dbi);
        $location = $export->getPageLocationAndSaveMessage('table', Message::success('Success message!'));
        self::assertSame(
            'index.php?route=/table/export&db=test_db&table=test_table&single_table=true&server=2&lang=en',
            $location,
        );
        self::assertSame(
            [['context' => 'success', 'message' => 'Success message!', 'statement' => '']],
            (new FlashMessenger())->getMessages(),
        );
    }
}
