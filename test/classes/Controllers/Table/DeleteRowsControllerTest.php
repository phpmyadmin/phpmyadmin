<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\DeleteRowsController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Table\DeleteRowsController
 */
class DeleteRowsControllerTest extends AbstractTestCase
{
    public function testDeleteRowsController(): void
    {
        $this->setTheme();
        $GLOBALS['goto'] = null;
        $GLOBALS['showtable'] = null;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['urlParams'] = [];
        $GLOBALS['cfg']['Server'] = $GLOBALS['config']->defaultServer;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $_POST = [
            'db' => 'test_db',
            'table' => 'test_table',
            'selected' => [2 => '`test_table`.`id` = 3'],
            'original_sql_query' => 'SELECT * FROM `test_db`.`test_table`',
            'fk_checks' => '1',
            'mult_btn' => 'Yes',
        ];

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('DELETE FROM `test_table` WHERE `test_table`.`id` = 3 LIMIT 1;', []);
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SELECT * FROM `test_db`.`test_table` LIMIT 0, 25',
            [['1', 'abcd', '2011-01-20 02:00:02'], ['2', 'foo', '2010-01-20 02:00:02']],
            ['id', 'name', 'datetimefield']
        );
        $dummyDbi->addResult(
            'SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'test_db\''
            . ' AND TABLE_NAME = \'test_table\' AND IS_UPDATABLE = \'YES\'',
            [],
            ['TABLE_NAME']
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $response = new ResponseRenderer();
        (new DeleteRowsController($response, new Template(), $dbi))();
        $actual = $response->getHTMLResult();
        $this->assertStringContainsString(
            '<div class="alert alert-success" role="alert">Your SQL query has been executed successfully.</div>',
            $actual
        );
        $this->assertStringContainsString('DELETE FROM `test_table` WHERE `test_table`.`id` = 3 LIMIT 1;', $actual);
        $this->assertStringContainsString('Showing rows 0 -  1 (2 total, Query took', $actual);
        $this->assertStringContainsString('SELECT * FROM `test_db`.`test_table`', $actual);
        $this->assertStringContainsString(
            '<td data-decimals="0" data-type="string" data-originallength="4" class="data text pre_wrap">abcd</td>',
            $actual
        );
    }
}
