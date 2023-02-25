<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\SecondNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\SecondNormalForm\NewTablesController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function json_encode;

/** @covers \PhpMyAdmin\Controllers\Normalization\SecondNormalForm\NewTablesController */
class NewTablesControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['pd', null, json_encode(['ID, task' => [], 'task' => ['timestamp']])],
        ]);
        $controller = new NewTablesController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($request);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertSame(
            '<p><b>In order to put the original table \'test_table\' into Second normal form we need to create the following tables:</b></p><p><input type="text" name="ID, task" value="test_table">( <u>ID, task</u> )<p><input type="text" name="task" value="table2">( <u>task</u>, timestamp )',
            $response->getHTMLResult(),
        );
        // phpcs:enable
    }
}
