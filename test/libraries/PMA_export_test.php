<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for export.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */


require_once 'libraries/export.lib.php';

/**
 * class PMA_Export_Test
 *
 * this class is for testing export.lib.php functions
 *
 * @package PhpMyAdmin-test
 * @group large
 */
class PMA_Export_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {

    }

    /**
     * Test for PMA_mergeAliases
     *
     * @return void
     */
    public function testPMAMergeAliases()
    {
        $aliases1 = array(
            'test_db' => array(
                'alias' => 'aliastest',
                'tables' => array(
                    'foo' => array(
                        'alias' => 'foobar',
                        'columns' => array(
                            'bar' => 'foo',
                            'baz' => 'barbaz'
                        )
                    ),
                    'bar' => array(
                        'alias' => 'foobaz',
                        'columns' => array(
                            'a' => 'a_alias',
                            'b' => 'b'
                        )
                    )
                )
            )
        );
        $aliases2 = array(
            'test_db' => array(
                'alias' => 'test',
                'tables' => array(
                    'foo' => array(
                        'columns' => array(
                            'bar' => 'foobar'
                        )
                    ),
                    'baz' => array(
                        'columns' => array(
                            'a' => 'x'
                        )
                    )
                )
            )
        );
        $expected = array(
            'test_db' => array(
                'alias' => 'test',
                'tables' => array(
                    'foo' => array(
                        'alias' => 'foobar',
                        'columns' => array(
                            'bar' => 'foobar',
                            'baz' => 'barbaz'
                        )
                    ),
                    'bar' => array(
                        'alias' => 'foobaz',
                        'columns' => array(
                            'a' => 'a_alias',
                            'b' => 'b'
                        )
                    ),
                    'baz' => array(
                        'columns' => array(
                            'a' => 'x'
                        )
                    )
                )
            )
        );
        $actual = PMA_mergeAliases($aliases1, $aliases2);
        $this->assertEquals($expected, $actual);
    }
}
