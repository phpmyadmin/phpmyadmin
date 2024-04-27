<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\CheckRelationsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(CheckRelationsController::class)]
class CheckRelationsControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testCheckRelationsController(): void
    {
        Current::$database = '';
        Current::$table = '';

        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['create_pmadb', null, null],
            ['fixall_pmadb', null, null],
            ['fix_pmadb', null, null],
        ]);

        $response = new ResponseRenderer();
        Config::getInstance()->selectedServer['pmadb'] = '';
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        $controller = new CheckRelationsController($response, new Relation($this->dbi));
        $controller($request);

        $actual = $response->getHTMLResult();

        self::assertStringContainsString('phpMyAdmin configuration storage', $actual);
        self::assertStringContainsString(
            'Configuration of pmadb…' . "\n" . '      <span class="text-danger"><strong>not OK</strong></span>',
            $actual,
        );
        self::assertStringContainsString(
            'Create</a> a database named &#039;phpmyadmin&#039; and setup the phpMyAdmin configuration storage there.',
            $actual,
        );
    }
}
