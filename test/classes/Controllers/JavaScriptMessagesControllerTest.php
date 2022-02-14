<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\JavaScriptMessagesController;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function strlen;
use function substr;

/**
 * @covers \PhpMyAdmin\Controllers\JavaScriptMessagesController
 */
class JavaScriptMessagesControllerTest extends TestCase
{
    public function testIndex(): void
    {
        ob_start();
        (new JavaScriptMessagesController())();
        $actual = ob_get_contents();
        ob_end_clean();

        $this->assertIsString($actual);
        $this->assertStringStartsWith('var Messages = {', $actual);
        $this->assertStringEndsWith('};', $actual);

        $json = substr($actual, strlen('var Messages = '), -1);
        $array = json_decode($json, true);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('strDoYouReally', $array);
        $this->assertEquals('Do you really want to execute "%s"?', $array['strDoYouReally']);
    }
}
