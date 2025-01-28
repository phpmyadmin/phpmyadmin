<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SchemaExportController::class)]
final class SchemaExportControllerTest extends AbstractTestCase
{
    public function testExport(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['db' => 'test_db', 'export_type' => 'svg']);
        $export = self::createStub(Export::class);
        $export->method('getExportSchemaInfo')->willReturn([
            'fileName' => 'file.svg',
            'mediaType' => 'image/svg+xml',
            'fileData' => 'file data',
        ]);

        $controller = new SchemaExportController($export, new ResponseRenderer(), ResponseFactory::create());
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('file data', (string) $response->getBody());
    }
}
