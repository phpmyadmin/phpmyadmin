<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\DropColumnConfirmationController;
use PhpMyAdmin\Dbal\DatabaseInterface;
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
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'test_db'],
            ['table', null, 'test_table'],
            ['selected_fld', null, ['name', 'datetimefield']],
        ]);

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
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

        (new DropColumnConfirmationController($response, new DbTableExists($dbi)))($request);

        self::assertSame(200, $response->getResponse()->getStatusCode());
        self::assertTrue($response->hasSuccessState());
        self::assertSame([], $response->getJSONResult());
        self::assertSame($expected, $response->getHTMLResult());
    }

    public function testWithoutFields(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'test_db'],
            ['table', null, 'test_table'],
            ['selected_fld', null, null],
        ]);

        $response = new ResponseRenderer();
        $response->setAjax(true);

        (new DropColumnConfirmationController(
            $response,
            new DbTableExists(DatabaseInterface::getInstance()),
        ))($request);

        self::assertSame(400, $response->getResponse()->getStatusCode());
        self::assertFalse($response->hasSuccessState());
        self::assertSame(['isErrorResponse' => true, 'message' => 'No column selected.'], $response->getJSONResult());
        self::assertSame('', $response->getHTMLResult());
    }

    public function testWithoutDatabaseAndTable(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, null],
            ['table', null, null],
            ['selected_fld', null, ['name', 'datetimefield']],
        ]);

        $response = new ResponseRenderer();
        $response->setAjax(true);

        (new DropColumnConfirmationController(
            $response,
            new DbTableExists(DatabaseInterface::getInstance()),
        ))($request);

        self::assertSame(400, $response->getResponse()->getStatusCode());
        self::assertFalse($response->hasSuccessState());
        self::assertSame([
            'isErrorResponse' => true,
            'message' => 'The database name must be a non-empty string.',
        ], $response->getJSONResult());
        self::assertSame('', $response->getHTMLResult());
    }
}
