<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\DropColumnConfirmationController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DropColumnConfirmationController::class)]
class DropColumnConfirmationControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testWithValidParameters(): void
    {
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'test_db'],
            ['table', null, 'test_table'],
            ['selected_fld', null, ['name', 'datetimefield']],
        ]);

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SHOW TABLES LIKE \'test_table\';', [['test_table']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $template = new Template();
        $expected = $template->render('table/structure/drop_confirm', [
            'db' => 'test_db',
            'table' => 'test_table',
            'fields' => ['name', 'datetimefield'],
        ]);

        (new DropColumnConfirmationController($response, $template, new DbTableExists($dbi)))($request);

        $this->assertSame(200, $response->getResponse()->getStatusCode());
        $this->assertTrue($response->hasSuccessState());
        $this->assertSame([], $response->getJSONResult());
        $this->assertSame($expected, $response->getHTMLResult());
    }

    public function testWithoutFields(): void
    {
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'test_db'],
            ['table', null, 'test_table'],
            ['selected_fld', null, null],
        ]);

        $response = new ResponseRenderer();
        $response->setAjax(true);

        (new DropColumnConfirmationController(
            $response,
            new Template(),
            new DbTableExists(DatabaseInterface::getInstance()),
        ))($request);

        $this->assertSame(400, $response->getResponse()->getStatusCode());
        $this->assertFalse($response->hasSuccessState());
        $this->assertSame(['isErrorResponse' => true, 'message' => 'No column selected.'], $response->getJSONResult());
        $this->assertSame('', $response->getHTMLResult());
    }

    public function testWithoutDatabaseAndTable(): void
    {
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, null],
            ['table', null, null],
            ['selected_fld', null, ['name', 'datetimefield']],
        ]);

        $response = new ResponseRenderer();
        $response->setAjax(true);

        (new DropColumnConfirmationController(
            $response,
            new Template(),
            new DbTableExists(DatabaseInterface::getInstance()),
        ))($request);

        $this->assertSame(400, $response->getResponse()->getStatusCode());
        $this->assertFalse($response->hasSuccessState());
        $this->assertSame([
            'isErrorResponse' => true,
            'message' => 'The database name must be a non-empty string.',
        ], $response->getJSONResult());
        $this->assertSame('', $response->getHTMLResult());
    }
}
