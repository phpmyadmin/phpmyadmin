<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\AddNewPrimaryController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\AddNewPrimaryController */
class AddNewPrimaryControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['col_priv'] = false;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbiDummy = $this->createDbiDummy();

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
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
