<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Controllers\Table\OperationsController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

/** @covers \PhpMyAdmin\Controllers\Table\OperationsController */
class OperationsControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadContainerBuilder();
        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    public function testOperationsController(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $GLOBALS['config']->selectServer('1');
        $GLOBALS['cfg'] = $GLOBALS['config']->settings;
        $GLOBALS['cfg']['MaxDbList'] = 0;

        $this->loadDbiIntoContainerBuilder();
        $this->loadResponseIntoContainerBuilder();

        $GLOBALS['containerBuilder']->setParameter('db', 'test_db');
        $GLOBALS['containerBuilder']->setParameter('table', 'test_table');

        $this->dummyDbi->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['test_db']],
            ['SCHEMA_NAME'],
        );
        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addSelectDb('test_db');
        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']], ['Tables_in_test_db (test_table)']);
        $this->dummyDbi->addResult(
            'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\') AND t.`TABLE_NAME` COLLATE utf8_bin = \'test_table\' ORDER BY Name ASC',
            [['ref', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '']],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'TABLE_TYPE', 'ENGINE', 'VERSION', 'ROW_FORMAT', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_COLLATION', 'CHECKSUM', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'MAX_INDEX_LENGTH', 'TEMPORARY', 'Db', 'Name', 'TABLE_TYPE', 'Engine', 'Type', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment'],
        );
        $this->dummyDbi->addResult(
            'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\') AND t.`TABLE_NAME` COLLATE utf8_bin = \'test_table\' ORDER BY Name ASC',
            [['ref', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '49152', '0', '4', '2021-11-07 15:21:00', null, null, 'utf8mb4_general_ci', null, '', '']],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'TABLE_TYPE', 'ENGINE', 'VERSION', 'ROW_FORMAT', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_COLLATION', 'CHECKSUM', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'MAX_INDEX_LENGTH', 'TEMPORARY', 'Db', 'Name', 'TABLE_TYPE', 'Engine', 'Type', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment'],
        );
        $this->dummyDbi->addResult(
            'SELECT DISTINCT `PARTITION_NAME` FROM `information_schema`.`PARTITIONS` WHERE `TABLE_SCHEMA` = \'test_db\' AND `TABLE_NAME` = \'test_table\'',
            [[null]],
            ['PARTITION_NAME'],
        );
        // phpcs:enable

        $storageEngines = StorageEngine::getArray();
        $charsets = Charsets::getCharsets($this->dbi, false);
        $collations = Charsets::getCollations($this->dbi, false);

        $expectedOutput = (new Template())->render('table/operations/index', [
            'db' => 'test_db',
            'table' => 'test_table',
            'url_params' => [
                'db' => 'test_db',
                'table' => 'test_table',
                'back' => 'index.php?route=/table/operations&lang=en',
                'goto' => 'index.php?route=/table/operations&lang=en',
            ],
            'columns' => $this->dbi->getColumns('test_db', 'test_table'),
            'hide_order_table' => true,
            'table_comment' => '',
            'storage_engine' => 'INNODB',
            'storage_engines' => $storageEngines,
            'charsets' => $charsets,
            'collations' => $collations,
            'tbl_collation' => 'utf8mb4_general_ci',
            'row_formats' => ['COMPACT' => 'COMPACT', 'REDUNDANT' => 'REDUNDANT'],
            'row_format_current' => 'Dynamic',
            'has_auto_increment' => true,
            'auto_increment' => '4',
            'has_pack_keys' => false,
            'pack_keys' => 'DEFAULT',
            'has_transactional_and_page_checksum' => false,
            'has_checksum_and_delay_key_write' => false,
            'delay_key_write' => '0',
            'transactional' => '1',
            'page_checksum' => '',
            'checksum' => '0',
            'database_list' => [],
            'has_foreign_keys' => true,
            'has_privileges' => false,
            'switch_to_new' => false,
            'is_system_schema' => false,
            'is_view' => false,
            'partitions' => [],
            'partitions_choices' => [],
            'foreigners' => [],
        ]);

        /** @var OperationsController $controller */
        $controller = $GLOBALS['containerBuilder']->get(OperationsController::class);
        $controller($this->createStub(ServerRequest::class));

        $this->assertEquals($expectedOutput, $this->getResponseHtmlResult());
    }
}
