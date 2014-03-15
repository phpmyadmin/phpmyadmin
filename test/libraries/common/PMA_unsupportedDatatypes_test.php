<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::unsupportedDatatypes from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

/**
 ** Test for PMA_Util::unsupportedDatatypes from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_UnsupportedDatatypes_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for unsupportedDatatypes
     *
     * @return void
     */
    function testNotSupportedDataTypes()
    {
        $no_support_types = array();
        $this->assertEquals(
            $no_support_types, PMA_Util::unsupportedDatatypes()
        );
    }
}
