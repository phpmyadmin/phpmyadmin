<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Controllers\Database\PrivilegesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;

use function __;

/** @covers \PhpMyAdmin\Controllers\Database\PrivilegesController */
class PrivilegesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        parent::setTheme();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    public function testIndex(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `test_db`;',
            [['test_table']],
            ['Tables_in_test_db'],
        );

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->dummyDbi->addResult(
            'SELECT *, `TABLE_SCHEMA` AS `Db`, `TABLE_NAME` AS `Name`, `TABLE_TYPE` AS `TABLE_TYPE`, `ENGINE` AS `Engine`, `ENGINE` AS `Type`, `VERSION` AS `Version`, `ROW_FORMAT` AS `Row_format`, `TABLE_ROWS` AS `Rows`, `AVG_ROW_LENGTH` AS `Avg_row_length`, `DATA_LENGTH` AS `Data_length`, `MAX_DATA_LENGTH` AS `Max_data_length`, `INDEX_LENGTH` AS `Index_length`, `DATA_FREE` AS `Data_free`, `AUTO_INCREMENT` AS `Auto_increment`, `CREATE_TIME` AS `Create_time`, `UPDATE_TIME` AS `Update_time`, `CHECK_TIME` AS `Check_time`, `TABLE_COLLATION` AS `Collation`, `CHECKSUM` AS `Checksum`, `CREATE_OPTIONS` AS `Create_options`, `TABLE_COMMENT` AS `Comment` FROM `information_schema`.`TABLES` t WHERE `TABLE_SCHEMA` COLLATE utf8_bin IN (\'test_db\') ORDER BY Name ASC LIMIT 250 OFFSET 0',
            [['def', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '4', '2011-12-13 14:15:16', null, null, 'utf8mb4_general_ci', null, '', '', '0', 'N', 'test_db', 'test_table', 'BASE TABLE', 'InnoDB', 'InnoDB', '10', 'Dynamic', '3', '5461', '16384', '0', '0', '0', '4', '2011-12-13 14:15:16', null, null, 'utf8mb4_general_ci', null, '', '']],
            ['TABLE_CATALOG', 'TABLE_SCHEMA', 'TABLE_NAME', 'TABLE_TYPE', 'ENGINE', 'VERSION', 'ROW_FORMAT', 'TABLE_ROWS', 'AVG_ROW_LENGTH', 'DATA_LENGTH', 'MAX_DATA_LENGTH', 'INDEX_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_COLLATION', 'CHECKSUM', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'MAX_INDEX_LENGTH', 'TEMPORARY', 'Db', 'Name', 'TABLE_TYPE', 'Engine', 'Type', 'Version', 'Row_format', 'Rows', 'Avg_row_length', 'Data_length', 'Max_data_length', 'Index_length', 'Data_free', 'Auto_increment', 'Create_time', 'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options', 'Comment'],
        );
        // phpcs:enable

        $privileges = [];

        $serverPrivileges = $this->createMock(Privileges::class);
        $serverPrivileges->method('getAllPrivileges')
            ->willReturn($privileges);

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParam')->willReturnMap([['db', null, 'test_db']]);

        $response = new ResponseRenderer();
        (new PrivilegesController(
            $response,
            new Template(),
            $serverPrivileges,
            $GLOBALS['dbi'],
        ))($request);
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString(
            Url::getCommon(['db' => $GLOBALS['db']], ''),
            $actual,
        );

        $this->assertStringContainsString($GLOBALS['db'], $actual);

        $this->assertStringContainsString(
            __('User'),
            $actual,
        );
        $this->assertStringContainsString(
            __('Host'),
            $actual,
        );
        $this->assertStringContainsString(
            __('Type'),
            $actual,
        );
        $this->assertStringContainsString(
            __('Privileges'),
            $actual,
        );
        $this->assertStringContainsString(
            __('Grant'),
            $actual,
        );
        $this->assertStringContainsString(
            __('Action'),
            $actual,
        );
    }

    public function testWithInvalidDatabaseName(): void
    {
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParam')->willReturnMap([['db', null, '']]);

        $response = new ResponseRenderer();
        (new PrivilegesController(
            $response,
            new Template(),
            $this->createStub(Privileges::class),
            $this->createDatabaseInterface(),
        ))($request);
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual);
        $this->assertStringContainsString('The database name must be a non-empty string.', $actual);
    }
}
