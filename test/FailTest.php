<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_get_real_size()
 *
 * @version $Id$
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';

/**
 * @package phpMyAdmin-test
 */
class FailTest extends PHPUnit_Framework_TestCase
{
    public function testFail()
    {
        $this->assertEquals(0, 1);
    }
}
?>
