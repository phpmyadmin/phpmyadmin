<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\SchemaExportController;
use PhpMyAdmin\Export;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\SchemaExportController */
class SchemaExportControllerTest extends AbstractTestCase
{
    /** @runInSeparateProcess */
    public function testExport(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['db', null, 'test_db'], ['export_type', null, 'svg']]);
        $export = $this->createStub(Export::class);
        $export->method('getExportSchemaInfo')->willReturn([
            'fileName' => 'file.svg',
            'mediaType' => 'image/svg+xml',
            'fileData' => 'file data',
        ]);

        $response = new ResponseRenderer();
        $controller = new SchemaExportController($export, $response);
        $controller($request);
        $output = $this->getActualOutputForAssertion();
        $this->assertSame('file data', $output);
        $this->assertTrue($response->isDisabled());
        $this->assertSame('', $response->getHTMLResult());
        $this->assertSame([], $response->getJSONResult());
    }
}
