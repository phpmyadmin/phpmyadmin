<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\DeleteRowsController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Table\DeleteRowsController */
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
        $GLOBALS['cfg']['Server'] = $GLOBALS['config']->getSettings()->Servers[1]->asArray();
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $_POST = [
            'db' => 'test_db',
            'table' => 'test_table',
            'selected' => [2 => '`test_table`.`id` = 3'],
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
            ['id', 'name', 'datetimefield'],
        );
        $dummyDbi->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'test_db\''
            . ' AND TABLE_NAME = \'test_table\' AND IS_UPDATABLE = \'YES\'',
            [],
            ['TABLE_NAME'],
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $GLOBALS['dbi'] = $dbi;

        $request = $this->createStub(ServerRequest::class);
        $request->method('hasBodyParam')->willReturnMap([['original_sql_query', true]]);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['original_sql_query', '', 'SELECT * FROM `test_db`.`test_table`'],
        ]);

        $response = new ResponseRenderer();
        (new DeleteRowsController($response, new Template(), $dbi))($request);
        $actual = $response->getHTMLResult();
        $this->assertStringContainsString(
            '<div class="alert alert-success" role="alert">' . "\n"
            . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_success">'
            . ' Your SQL query has been executed successfully.' . "\n"
            . '</div>',
            $actual,
        );
        $this->assertStringContainsString('DELETE FROM `test_table` WHERE `test_table`.`id` = 3 LIMIT 1;', $actual);
        $this->assertStringContainsString('Showing rows 0 -  1 (2 total, Query took', $actual);
        $this->assertStringContainsString('SELECT * FROM `test_db`.`test_table`', $actual);
        $this->assertStringContainsString(
            '<td data-decimals="0" data-type="string" data-originallength="4" class="data text pre_wrap">abcd</td>',
            $actual,
        );
    }
}
