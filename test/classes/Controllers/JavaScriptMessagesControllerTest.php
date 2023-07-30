<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\JavaScriptMessagesController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function strlen;
use function substr;

#[CoversClass(JavaScriptMessagesController::class)]
class JavaScriptMessagesControllerTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testIndex(): void
    {
        $response = (new JavaScriptMessagesController(ResponseFactory::create()))();
        $actual = (string) $response->getBody();
        $this->assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

        $this->assertStringStartsWith('window.Messages = {', $actual);
        $this->assertStringEndsWith('};', $actual);

        $json = substr($actual, strlen('window.Messages = '), -1);
        $array = json_decode($json, true);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('strDoYouReally', $array);
        $this->assertEquals('Do you really want to execute "%s"?', $array['strDoYouReally']);
    }
}
