<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Databases;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Server\Databases\DestroyController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;

use function __;

#[CoversClass(DestroyController::class)]
class DestroyControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testDropDatabases(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = new ResponseRenderer();

        $config = Config::getInstance();
        $config->settings['AllowUserDropDatabase'] = true;

        $controller = new DestroyController(
            $response,
            $dbi,
            new Transformations(),
            new RelationCleanup($dbi, new Relation($dbi)),
            new UserPrivilegesFactory($dbi),
            $config,
        );

        $request = self::createStub(ServerRequest::class);
        $request->method('isAjax')->willReturn(true);

        $controller($request);
        $actual = $response->getJSONResult();

        self::assertArrayHasKey('message', $actual);
        self::assertStringContainsString('<div class="alert alert-danger" role="alert">', $actual['message']);
        self::assertStringContainsString(__('No databases selected.'), $actual['message']);
    }
}
