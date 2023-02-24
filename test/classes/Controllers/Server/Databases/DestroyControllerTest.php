<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Databases;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Server\Databases\DestroyController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function __;

/** @covers \PhpMyAdmin\Controllers\Server\Databases\DestroyController */
class DestroyControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
    }

    public function testDropDatabases(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = new ResponseRenderer();
        $response->setAjax(true);

        $GLOBALS['cfg']['AllowUserDropDatabase'] = true;

        $controller = new DestroyController(
            $response,
            new Template(),
            $dbi,
            new Transformations(),
            new RelationCleanup($dbi, new Relation($dbi)),
        );

        $controller($this->createStub(ServerRequest::class));
        $actual = $response->getJSONResult();

        $this->assertArrayHasKey('message', $actual);
        $this->assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual['message']);
        $this->assertStringContainsString(__('No databases selected.'), $actual['message']);
    }
}
