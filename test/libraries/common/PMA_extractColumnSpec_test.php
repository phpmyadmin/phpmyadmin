<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::extractColumnSpec from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

/**
 ** Test for PMA_Util::extractColumnSpec function.
 *
 * @package PhpMyAdmin-test
 */
class PMA_ExtractColumnSpec_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['cfg']['LimitChars'] = 1000;
    }

    /**
     * Test case for parsing SHOW COLUMNS output
     *
     * @param string $in  Column specification
     * @param array  $out Expected value
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testParsing($in, $out)
    {
        $this->assertEquals(
            $out, PMA_Util::extractColumnSpec($in)
        );
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
                    'can_contain_collation' => true,
                    'displayed_type' => "set('a', 'b')"
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
                    'can_contain_collation' => true,
                    'displayed_type' => "set('\'a', 'b')"
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
                    'can_contain_collation' => true,
                    'displayed_type' => "set('''a', 'b')"
                ),
            ),
            array(
                "ENUM('a&b', 'b''c\\'d', 'e\\\\f')",
                array(
                    'type' => 'enum',
                    'print_type' => "enum('a&b', 'b''c\\'d', 'e\\\\f')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a&b', 'b''c\\'d', 'e\\\\f'",
                    'enum_set_values' => array('a&b', 'b\'c\'d', 'e\\f'),
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "enum('a&amp;b', 'b''c\\'d', 'e\\\\f')"
                ),
            ),
            array(
                "INT UNSIGNED zerofill",
                array(
                    'type' => 'int',
                    'print_type' => 'int',
                    'binary' => false,
                    'unsigned' => true,
                    'zerofill' => true,
                    'spec_in_brackets' => '',
                    'enum_set_values' => array(),
                    'attribute' => 'UNSIGNED ZEROFILL',
                    'can_contain_collation' => false,
                    'displayed_type' => "int"
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
                    'can_contain_collation' => true,
                    'displayed_type' => "varchar(255)"
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
                    'can_contain_collation' => false,
                    'displayed_type' => "varbinary(255)"
                ),
            ),
        );
    }
}
