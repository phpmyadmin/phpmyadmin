<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\CreateNewColumnController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CreateNewColumnController::class)]
class CreateNewColumnControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;
        $GLOBALS['col_priv'] = false;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbiDummy = $this->createDbiDummy();

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['numFields', null, '1']]);

        $controller = new CreateNewColumnController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($request);

        $this->assertStringContainsString('<table id="table_columns"', $response->getHTMLResult());
    }
}
