<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\LogoutController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LogoutController::class)]
class LogoutControllerTest extends AbstractTestCase
{
    public function testValidLogout(): void
    {
        $GLOBALS['token_mismatch'] = false;

        $request = self::createStub(ServerRequest::class);
        $request->method('isPost')->willReturn(true);

        $authPlugin = self::createMock(AuthenticationPlugin::class);
        $authPlugin->expects(self::once())->method('logOut');

        $factory = self::createStub(AuthenticationPluginFactory::class);
        $factory->method('create')->willReturn($authPlugin);

        (new LogoutController($factory))($request);

        unset($GLOBALS['token_mismatch']);
    }
}
