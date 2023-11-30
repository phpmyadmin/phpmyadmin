<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\AddNewPrimaryController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AddNewPrimaryController::class)]
class AddNewPrimaryControllerTest extends AbstractTestCase
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

        $controller = new AddNewPrimaryController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($this->createStub(ServerRequest::class));

        $this->assertStringContainsString('<table id="table_columns"', $response->getHTMLResult());
    }
}
