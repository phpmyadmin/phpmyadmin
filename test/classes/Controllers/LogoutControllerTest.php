<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\LogoutController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Controllers\LogoutController */
class LogoutControllerTest extends AbstractTestCase
{
    public function testValidLogout(): void
    {
        $GLOBALS['token_mismatch'] = false;

        $request = $this->createStub(ServerRequest::class);
        $request->method('isPost')->willReturn(true);

        $authPlugin = $this->createMock(AuthenticationPlugin::class);
        $authPlugin->expects($this->once())->method('logOut');

        $factory = $this->createStub(AuthenticationPluginFactory::class);
        $factory->method('create')->willReturn($authPlugin);

        (new LogoutController($factory))($request);

        unset($GLOBALS['token_mismatch']);
    }
}
