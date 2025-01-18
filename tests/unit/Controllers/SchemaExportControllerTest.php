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
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

use function function_exists;
use function xdebug_get_headers;

#[CoversClass(SchemaExportController::class)]
#[RunTestsInSeparateProcesses]
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

        if (! function_exists('xdebug_get_headers')) {
            return;
        }

        $headersList = xdebug_get_headers();
        self::assertContains('Content-Disposition: attachment; filename="file.svg"', $headersList);
        self::assertContains('Content-Type: image/svg+xml', $headersList);
        self::assertContains('Content-Length: 9', $headersList);
    }
}
