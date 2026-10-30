<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\LogoutController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LogoutController::class)]
final class LogoutControllerTest extends AbstractTestCase
{
    public function testValidLogout(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('isPost')->willReturn(true);

        $expectedResponse = ResponseFactory::create()->createResponse();

        $authPlugin = $this->createMock(AuthenticationPlugin::class);
        $authPlugin->expects(self::once())->method('logOut')->willReturn($expectedResponse);

        $factory = self::createStub(AuthenticationPluginFactory::class);
        $factory->method('create')->willReturn($authPlugin);

        $response = (new LogoutController($factory, new ResponseRenderer()))($request);

        self::assertSame($expectedResponse, $response);
    }
}
