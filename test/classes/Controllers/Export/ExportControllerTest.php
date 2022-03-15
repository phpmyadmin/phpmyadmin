<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Export;

use PhpMyAdmin\Controllers\Export\ExportController;
use PhpMyAdmin\Export;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function htmlspecialchars;

use const MYSQLI_NUM_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_STRING;

/**
 * @covers \PhpMyAdmin\Controllers\Export\ExportController
 */
class ExportControllerTest extends AbstractTestCase
{
    public function testExportController(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['sql_indexes'] = null;
        $GLOBALS['sql_auto_increments'] = null;
        $GLOBALS['dblist'] = (object) ['databases' => ['test_db']];
        $GLOBALS['config']->selectServer();
        $GLOBALS['cfg'] = $GLOBALS['config']->settings;

        $this->dummyDbi->addResult('SET SQL_MODE=""', [[]]);
        $this->dummyDbi->addResult('SET time_zone = "+00:00"', [[]]);
        $this->dummyDbi->addResult('SELECT @@session.time_zone', [['SYSTEM']]);
        $this->dummyDbi->addResult('SET time_zone = "SYSTEM"', [[]]);
        $this->dummyDbi->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $this->dummyDbi->addResult(
            'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = \'test_db\' LIMIT 1',
            [['utf8mb4_general_ci']],
            ['DEFAULT_COLLATION_NAME']
        );
        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->dummyDbi->addResult(
            'SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'test_db\' AND TABLE_NAME = \'test_table\'',
            [],
            ['TABLE_NAME']
        );
        $this->dummyDbi->addResult(
            'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` IN (\'test_db\') AND t.`TABLE_NAME` = \'test_table\' ORDER BY Name ASC',
            [['ref', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '']],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'TABLE_TYPE', 'ENGINE', 'VERSION', 'ROW_FORMAT', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_COLLATION', 'CHECKSUM', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'MAX_INDEX_LENGTH', 'TEMPORARY', 'Db', 'Name', 'TABLE_TYPE', 'Engine', 'Type', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment']
        );
        $this->dummyDbi->addResult(
            'SELECT `id`, `name`, `datetimefield` FROM `test_db`.`test_table`',
            [
                ['1', 'abcd', '2011-01-20 02:00:02'],
                ['2', 'foo', '2010-01-20 02:00:02'],
                ['3', 'Abcd', '2012-01-20 02:00:02'],
            ],
            ['id', 'name', 'datetimefield'],
            [
                new FieldMetadata(MYSQLI_TYPE_DECIMAL, MYSQLI_PRI_KEY_FLAG | MYSQLI_NUM_FLAG, (object) ['name' => 'id']),
                new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) ['name' => 'name']),
                new FieldMetadata(MYSQLI_TYPE_DATETIME, 0, (object) ['name' => 'datetimefield']),
            ]
        );
        $this->dummyDbi->addResult(
            'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT, EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA = \'test_db\' AND EVENT_OBJECT_TABLE = \'test_table\';',
            [],
            ['TRIGGER_SCHEMA', 'TRIGGER_NAME', 'EVENT_MANIPULATION', 'EVENT_OBJECT_TABLE', 'ACTION_TIMING', 'ACTION_STATEMENT', 'EVENT_OBJECT_SCHEMA', 'EVENT_OBJECT_TABLE', 'DEFINER']
        );
        // phpcs:enable

        $request = $this->createPartialMock(ServerRequest::class, ['getParsedBody']);
        $request->method('getParsedBody')->willReturn([
            'db' => '',
            'table' => '',
            'export_type' => 'server',
            'export_method' => 'quick',
            'template_id' => '',
            'quick_or_custom' => 'custom',
            'what' => 'sql',
            'db_select' => ['test_db'],
            'aliases_new' => '',
            'output_format' => 'astext',
            'filename_template' => '@SERVER@',
            'remember_template' => 'on',
            'charset' => 'utf-8',
            'compression' => 'none',
            'maxsize' => '',
            'sql_include_comments' => 'something',
            'sql_header_comment' => '',
            'sql_use_transaction' => 'something',
            'sql_compatibility' => 'NONE',
            'sql_structure_or_data' => 'structure_and_data',
            'sql_create_table' => 'something',
            'sql_auto_increment' => 'something',
            'sql_create_view' => 'something',
            'sql_create_trigger' => 'something',
            'sql_backquotes' => 'something',
            'sql_type' => 'INSERT',
            'sql_insert_syntax' => 'both',
            'sql_max_query_size' => '50000',
            'sql_hex_for_binary' => 'something',
            'sql_utc_time' => 'something',
        ]);

        $expectedOutput = <<<'SQL'
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `test_db`
--
CREATE DATABASE IF NOT EXISTS `test_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `test_db`;

-- --------------------------------------------------------

--
-- Table structure for table `test_table`
--

CREATE TABLE `test_table` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `datetimefield` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `test_table`
--

INSERT INTO `test_table` (`id`, `name`, `datetimefield`) VALUES
(1, 'abcd', '2011-01-20 02:00:02'),
(2, 'foo', '2010-01-20 02:00:02'),
(3, 'Abcd', '2012-01-20 02:00:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `test_table`
--
ALTER TABLE `test_table`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `test_table`
--
ALTER TABLE `test_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;
SQL;

        $exportController = new ExportController(new ResponseRenderer(), new Template(), new Export($this->dbi));
        $exportController($request);
        $output = $this->getActualOutputForAssertion();

        $this->assertStringNotContainsString('Missing parameter: what', $output);
        $this->assertStringNotContainsString('Missing parameter: export_type', $output);
        $this->assertStringContainsString(htmlspecialchars($expectedOutput), $output);
    }
}
