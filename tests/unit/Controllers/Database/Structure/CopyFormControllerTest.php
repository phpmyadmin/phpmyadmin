<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\Database\Structure\CopyFormController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CopyFormController::class)]
final class CopyFormControllerTest extends AbstractTestCase
{
    public function testCopyFormModal(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['test_db'], ['test_db_1'], ['test_db_2']],
            ['SCHEMA_NAME'],
        );

        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);
        Current::$database = 'test_db';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db'])
            ->withParsedBody(['selected_tbl' => ['test_table']]);

        $template = new Template();
        $controller = new CopyFormController(new ResponseRenderer(), ResponseFactory::create(), $template);
        $response = $controller($request);

        $expected = $template->render('database/structure/copy_form', [
            'url_params' => ['db' => 'test_db', 'selected' => ['test_table']],
            'options' => [
                ['name' => 'test_db_1', 'is_selected' => false],
                ['name' => 'test_db_2', 'is_selected' => false],
            ],
        ]);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
        self::assertSame($expected, (string) $response->getBody());

        $dbiDummy->assertAllQueriesConsumed();
    }
}
