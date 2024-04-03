<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\JavaScriptMessagesController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function strlen;
use function substr;

#[CoversClass(JavaScriptMessagesController::class)]
class JavaScriptMessagesControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $controller = new JavaScriptMessagesController(ResponseFactory::create());
        $response = $controller(self::createStub(ServerRequest::class));
        $actual = (string) $response->getBody();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

        self::assertStringStartsWith('window.Messages = {', $actual);
        self::assertStringEndsWith('};', $actual);

        $json = substr($actual, strlen('window.Messages = '), -1);
        $array = json_decode($json, true);

        self::assertIsArray($array);
        self::assertArrayHasKey('strDoYouReally', $array);
        self::assertSame('Do you really want to execute "%s"?', $array['strDoYouReally']);
    }
}
