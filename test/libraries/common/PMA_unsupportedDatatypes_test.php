<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_unsupportedDatatypes from common.lib
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_unsupportedDatatypes_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_unsupportedDatatypes_test extends PHPUnit_Framework_TestCase
{

    function testNotSupportedDataTypes()
    {
        $no_support_types = array('geometry',
                                  'point',
                                  'linestring',
                                  'polygon',
                                  'multipoint',
                                  'multilinestring',
                                  'multipolygon',
                                  'geometrycollection'
        );
        $this->assertEquals($no_support_types, PMA_unsupportedDatatypes());
    }
}