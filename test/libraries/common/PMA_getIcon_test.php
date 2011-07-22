<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getIcon() from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_getIcon_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_getIcon_test extends PHPUnit_Framework_TestCase{

    function testGetIconWithoutPropertiesIconic(){

        $GLOBALS['cfg']['PropertiesIconic'] = false;

        $this->assertEquals(
            '<span class="nowrap"></span>',
            PMA_getIcon('b_comment.png') 
            );
    }

    function testGetIconWithPropertiesIconic(){
        
        $GLOBALS['cfg']['PropertiesIconic'] = true;

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="" alt="" class="icon ic_b_comment" /></span>',
            PMA_getIcon('b_comment.png') 
            );
    }

    function testGetIconAlternate(){

        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $alternate_text = 'alt_str';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="' . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" /></span>',
            PMA_getIcon('b_comment.png', $alternate_text)
            );
    }

    function testGetIconWithContainer(){

        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $alternate_text = 'alt_str';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="' . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" /></span>',
            PMA_getIcon('b_comment.png', $alternate_text, true) 
            );

    }

    function testGetIconWithContainerAndForceText(){

        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $alternate_text = 'alt_str';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="' . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" /> ' . $alternate_text . '</span>',
            PMA_getIcon('b_comment.png', $alternate_text, true, true)
            );

    }
}
