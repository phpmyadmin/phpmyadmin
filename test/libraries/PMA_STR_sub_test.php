<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_Util::pow()
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/string.lib.php';

class PMA_STR_Sub_Test extends PHPUnit_Framework_TestCase
{
    public function testMultiByte()
    {
        /* The PDF testing seems to set mb encoding to ASCII */
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('utf-8');
        }
        $this->assertEquals(
            'čšě',
            $GLOBALS['PMA_String']::substr('čšěčščěš', 0, 3)
        );
    }
}
?>
