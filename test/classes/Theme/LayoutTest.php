<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Layout class in theme folder
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Theme;

use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PMA_Layout class
 *
 * @package PhpMyAdmin-test
 */
class LayoutTest extends PmaTestCase
{
    /**
     * Test for Layout::createLayoutFile
     *
     * @param string $name   name of new theme
     *
     * @param string $output layout file data
     *
     * @return void
     */
    public function createLayoutFile($post , $output)
    {
        $this->assertFileIsReadable('themes/' . $post['theme_name'] . '/layout.inc.php');
        $this->assertContains('$GLOBALS[\'cfg\'][\'NaviBackground\']           = \'' . $post['Navigation_Panel'] . '\';' , $output);
    }
}
