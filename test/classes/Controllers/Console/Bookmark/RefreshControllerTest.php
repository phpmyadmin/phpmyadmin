<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Console\Bookmark;

use PhpMyAdmin\Controllers\Console\Bookmark\RefreshController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Console\Bookmark\RefreshController */
class RefreshControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $response = new ResponseRenderer();
        $controller = new RefreshController($response, new Template());
        $controller($this->createStub(ServerRequest::class));
        $this->assertSame(['console_message_bookmark' => ''], $response->getJSONResult());
    }
}
