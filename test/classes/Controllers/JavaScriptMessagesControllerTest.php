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

class JavaScriptMessagesControllerTest extends TestCase
{
    public function testIndex(): void
    {
        global $cfg, $PMA_Theme;

        $cfg['GridEditing'] = 'double-click';
        $PMA_Theme = new class {
            public function getImgPath(string $img): string
            {
                return $img;
            }
        };

        $controller = new JavaScriptMessagesController();

        ob_start();
        $controller->index();
        $actual = ob_get_contents();
        ob_end_clean();

        $this->assertIsString($actual);
        $this->assertStringStartsWith('var Messages = {', $actual);
        $this->assertStringEndsWith('};', $actual);

        $json = substr($actual, strlen('var Messages = '), -1);
        $array = json_decode($json, true);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('strConfirm', $array);
        $this->assertEquals(__('Confirm'), $array['strConfirm']);
    }
}
