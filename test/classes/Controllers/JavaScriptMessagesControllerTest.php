<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\JavaScriptMessagesController;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function strlen;
use function substr;

/** @covers \PhpMyAdmin\Controllers\JavaScriptMessagesController */
class JavaScriptMessagesControllerTest extends TestCase
{
    /** @runInSeparateProcess */
    public function testIndex(): void
    {
        (new JavaScriptMessagesController())();
        $actual = $this->getActualOutputForAssertion();

        $this->assertStringStartsWith('window.Messages = {', $actual);
        $this->assertStringEndsWith('};', $actual);

        $json = substr($actual, strlen('window.Messages = '), -1);
        $array = json_decode($json, true);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('strDoYouReally', $array);
        $this->assertEquals('Do you really want to execute "%s"?', $array['strDoYouReally']);
    }
}
