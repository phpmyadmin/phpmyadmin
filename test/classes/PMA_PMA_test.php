<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/PMA.php';

/**
 * Test for PMA class
 *
 * @package PhpMyAdmin-test
 */
class PMA_PMA_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for Get And Set
     *
     * @return void
     */
    public function testGetAndSet()
    {
        $pma = new PMA();
        $pma->__set('userlink', 'PMA_userlink');
        $pma->__set('controllink', 'PMA_controllink');
        $this->assertEquals(
            'PMA_userlink',
            $pma->__get('userlink')
        );
        $this->assertEquals(
            'PMA_controllink',
            $pma->__get('controllink')
        );
    }
}
