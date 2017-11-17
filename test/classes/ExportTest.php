<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Export
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Export;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\ExportTest class
 *
 * this class is for testing PhpMyAdmin\Export methods
 *
 * @package PhpMyAdmin-test
 * @group large
 */
class ExportTest extends TestCase
{
    /**
     * Test for Export::mergeAliases
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
        $actual = Export::mergeAliases($aliases1, $aliases2);
        $this->assertEquals($expected, $actual);
    }
}
