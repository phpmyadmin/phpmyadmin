<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\CheckRelationsController */
class CheckRelationsControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    public function testCheckRelationsController(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['text_dir'] = 'ltr';

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['create_pmadb', null, null],
            ['fixall_pmadb', null, null],
            ['fix_pmadb', null, null],
        ]);

        $response = new ResponseRenderer();
        $controller = new CheckRelationsController($response, new Template(), new Relation($this->dbi));
        $controller($request);

        $actual = $response->getHTMLResult();

        $this->assertStringContainsString('phpMyAdmin configuration storage', $actual);
        $this->assertStringContainsString(
            'Configuration of pmadb…      <span class="text-danger"><strong>not OK</strong></span>',
            $actual,
        );
        $this->assertStringContainsString(
            'Create</a> a database named \'phpmyadmin\' and setup the phpMyAdmin configuration storage there.',
            $actual,
        );
    }
}
