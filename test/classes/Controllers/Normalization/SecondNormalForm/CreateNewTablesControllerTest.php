<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\SecondNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\SecondNormalForm\CreateNewTablesController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function json_encode;

/** @covers \PhpMyAdmin\Controllers\Normalization\SecondNormalForm\CreateNewTablesController */
class CreateNewTablesControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('CREATE TABLE `batch_log2` SELECT DISTINCT `ID`, `task` FROM `test_table`;', []);
        $dbiDummy->addResult('CREATE TABLE `table2` SELECT DISTINCT `task`, `timestamp` FROM `test_table`;', []);
        $dbiDummy->addResult('DROP TABLE `test_table`', []);

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['pd', null, json_encode(['ID, task' => [], 'task' => ['timestamp']])],
            ['newTablesName', null, json_encode(['ID, task' => 'batch_log2', 'task' => 'table2'])],
        ]);

        $controller = new CreateNewTablesController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($request);

        $this->assertSame([
            'legendText' => 'End of step',
            'headText' => '<h3>The second step of normalization is complete for table \'test_table\'.</h3>',
            'queryError' => false,
            'extra' => '',
        ], $response->getJSONResult());
    }
}
