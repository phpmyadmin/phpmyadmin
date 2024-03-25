<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SchemaExportController::class)]
class SchemaExportControllerTest extends AbstractTestCase
{
    public function testExport(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['db', null, 'test_db'], ['export_type', null, 'svg']]);
        $export = self::createStub(Export::class);
        $export->method('getExportSchemaInfo')->willReturn([
            'fileName' => 'file.svg',
            'mediaType' => 'image/svg+xml',
            'fileData' => 'file data',
        ]);

        $response = new ResponseRenderer();
        $controller = new SchemaExportController($export, $response);
        $controller($request);
        $output = $this->getActualOutputForAssertion();
        self::assertSame('file data', $output);
        self::assertTrue($response->isDisabled());
        self::assertSame('', $response->getHTMLResult());
        self::assertSame([], $response->getJSONResult());
    }
}
