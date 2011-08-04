
<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_checkParameters from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_checkParameters_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_extractFieldSpec_test extends PHPUnit_Extensions_OutputTestCase
{
    /**
     * @dataProvider provider
     */
    public function testParsing($in, $out)
    {
        $this->assertEquals($out, PMA_extractFieldSpec($in));
    }

    public function provider()
    {
        return array(
            array(
                "SET('a', 'b')",
                array(
                    'type' => 'set',
                    'short_type' => 'set',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a', 'b'",
                    'enum_set_values' => array('a', 'b'),
                    ),
                ),
            array(
                "SET('\'a', 'b')",
                array(
                    'type' => 'set',
                    'short_type' => 'set',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'\'a', 'b'",
                    'enum_set_values' => array("'a", 'b'),
                    ),
                ),
            array(
                "INT UNSIGNED zerofill",
                array(
                    'type' => 'INT UNSIGNED zerofill',
                    'short_type' => 'INT',
                    'binary' => false,
                    'unsigned' => true,
                    'zerofill' => true,
                    'spec_in_brackets' => '',
                    'enum_set_values' => array(),
                    ),
                ),
            );
    }
}
