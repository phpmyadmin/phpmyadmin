<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\GetColumnsController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\GetColumnsController */
class GetColumnsControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $controller = new GetColumnsController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($this->createStub(ServerRequest::class));

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertSame(
            '<option selected disabled>Select one…</option><option value="no_such_col">No such column</option><option value="name">name [ varchar(20) ]</option>',
            $response->getHTMLResult(),
        );
        // phpcs:enable
    }
}
