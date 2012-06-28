<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_CommonFunctions::unsupportedDatatypes from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/CommonFunctions.class.php';

class PMA_unsupportedDatatypes_test extends PHPUnit_Framework_TestCase
{

    function testNotSupportedDataTypes()
    {
        $no_support_types = array();
        $this->assertEquals(
            $no_support_types, PMA_CommonFunctions::getInstance()->unsupportedDatatypes()
        );
    }
}