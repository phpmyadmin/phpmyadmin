<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\DesignerController;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DesignerController::class)]
final class DesignerControllerTest extends AbstractTestCase
{
    public function testEditPageDialog(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['db' => 'test_db', 'dialog' => 'edit']);

        $dbi = $this->createDatabaseInterface();
        $template = new Template(new Config());
        $responseRenderer = new ResponseRenderer();
        $controller = new DesignerController(
            $responseRenderer,
            $template,
            new Designer($dbi, new Relation($dbi), $template, new Config()),
            self::createStub(Common::class),
            new DbTableExists($dbi),
        );
        $controller($request);

        $expected = $template->render('database/designer/edit_delete_pages', [
            'db' => 'test_db',
            'operation' => 'editPage',
            'pdfwork' => false,
            'pages' => [],
        ]);

        self::assertSame($expected, $responseRenderer->getHTMLResult());
    }

    public function testDeletePageDialog(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['db' => 'test_db', 'dialog' => 'delete']);

        $dbi = $this->createDatabaseInterface();
        $template = new Template(new Config());
        $responseRenderer = new ResponseRenderer();
        $controller = new DesignerController(
            $responseRenderer,
            $template,
            new Designer($dbi, new Relation($dbi), $template, new Config()),
            self::createStub(Common::class),
            new DbTableExists($dbi),
        );
        $controller($request);

        $expected = $template->render('database/designer/edit_delete_pages', [
            'db' => 'test_db',
            'operation' => 'deletePage',
            'pdfwork' => false,
            'pages' => [],
        ]);

        self::assertSame($expected, $responseRenderer->getHTMLResult());
    }

    public function testSaveAsPageDialog(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['db' => 'test_db', 'dialog' => 'save_as']);

        $dbi = $this->createDatabaseInterface();
        $template = new Template(new Config());
        $responseRenderer = new ResponseRenderer();
        $controller = new DesignerController(
            $responseRenderer,
            $template,
            new Designer($dbi, new Relation($dbi), $template, new Config()),
            self::createStub(Common::class),
            new DbTableExists($dbi),
        );
        $controller($request);

        $expected = $template->render('database/designer/page_save_as', [
            'db' => 'test_db',
            'pdfwork' => false,
            'pages' => [],
        ]);

        self::assertSame($expected, $responseRenderer->getHTMLResult());
    }
}
