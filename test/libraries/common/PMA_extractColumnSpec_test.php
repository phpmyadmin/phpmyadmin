<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_extractColumnSpec from common.lib.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

/**
 * Test for PMA_extractColumnSpec function.
 */
class PMA_extractColumnSpec_test extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $GLOBALS['cfg']['LimitChars'] = 1000;
    }

    /**
     * Test case for parsing SHOW COLUMNS output
     *
     * @dataProvider provider
     */
    public function testParsing($in, $out)
    {
        $this->assertEquals($out, PMA_extractColumnSpec($in));
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array(
                "SET('a','b')",
                array(
                    'type' => 'set',
                    'print_type' => "set('a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a','b'",
                    'enum_set_values' => array('a', 'b'),
                    'attribute' => ' ',
                    ),
                ),
            array(
                "SET('\'a','b')",
                array(
                    'type' => 'set',
                    'print_type' => "set('\'a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'\'a','b'",
                    'enum_set_values' => array("'a", 'b'),
                    'attribute' => ' ',
                    ),
                ),
            array(
                "SET('''a','b')",
                array(
                    'type' => 'set',
                    'print_type' => "set('''a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'''a','b'",
                    'enum_set_values' => array("'a", 'b'),
                    'attribute' => ' ',
                    ),
                ),
            array(
                "INT UNSIGNED zerofill",
                array(
                    'type' => 'int unsigned zerofill',
                    'print_type' => 'int',
                    'binary' => false,
                    'unsigned' => true,
                    'zerofill' => true,
                    'spec_in_brackets' => '',
                    'enum_set_values' => array(),
                    'attribute' => 'UNSIGNED ZEROFILL',
                    ),
                ),
            array(
                "VARCHAR(255)",
                array(
                    'type' => 'varchar',
                    'print_type' => 'varchar(255)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '255',
                    'enum_set_values' => array(),
                    'attribute' => ' ',
                    ),
                ),
            array(
                "VARBINARY(255)",
                array(
                    'type' => 'varbinary',
                    'print_type' => 'varbinary(255)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '255',
                    'enum_set_values' => array(),
                    'attribute' => ' ',
                    ),
                ),
            );
    }
}
