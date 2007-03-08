<?php
/**
 * tests for PMA_get_real_size()
 *
 * @version $Id: $
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once './libraries/common.lib.php';

class FailTest extends PHPUnit_Framework_TestCase
{
    public function testFail()
    {
        $this->assertEquals(0, 1);
    }
}
?>