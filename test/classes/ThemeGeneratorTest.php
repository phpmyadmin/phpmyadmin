<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ThemeGenerator class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ThemeGenerator;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PMA_ThemeGenerator class
 *
 * @package PhpMyAdmin-test
 */
class ThemeGeneratorTest extends PmaTestCase
{
    public function testcolorPicker()
    {
        $theme = new ThemeGenerator();
        $output = $theme->colorPicker();
        $this->assertContains('<div id="container">' , $output);
        $this->assertContains('<div id="palette" class="block">' , $output);
        $this->assertContains('<div id="color-palette"></div>' , $output);
        $this->assertContains('<div id="picker" class="block">' , $output);
        $this->assertContains('<div class="ui-color-picker" data-topic="picker" data-mode="HSB"></div>' , $output);
        $this->assertContains('<div id="picker-samples" sample-id="master">' , $output);
    }
}
