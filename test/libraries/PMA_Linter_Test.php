<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Linter.class.php.
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Linter.class.php';

/**
 * Tests for Linter.class.php.
 *
 * @package PhpMyAdmin-test
 */
class PMA_Linter_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Test for PMA_Linter::getLines
     *
     * @return void
     */
    public function testGetLines()
    {
        $this->assertEquals(array(0), PMA_Linter::getLines(''));
        $this->assertEquals(array(0, 2), PMA_Linter::getLines("a\nb"));
        $this->assertEquals(array(0, 4, 7), PMA_Linter::getLines("abc\nde\n"));
    }

    /**
     * Test for PMA_Linter::findLineNumberAndColumn
     *
     * @return void
     */
    public function testFindLineNumberAndColumn()
    {
        // Let the analyzed string be:
        //      ^abc$
        //      ^de$
        //      ^$
        //
        // Where `^` is the beginning of the line and `$` the end of the line.
        //
        // Positions of each character (by line):
        //      ( a, 0), ( b, 1), ( c, 2), (\n, 3),
        //      ( d, 4), ( e, 5), (\n, 6),
        //      (\n, 7).
        $this->assertEquals(
            array(1, 0),
            PMA_Linter::findLineNumberAndColumn(array(0, 4, 7), 4)
        );
        $this->assertEquals(
            array(1, 1),
            PMA_Linter::findLineNumberAndColumn(array(0, 4, 7), 5)
        );
        $this->assertEquals(
            array(1, 2),
            PMA_Linter::findLineNumberAndColumn(array(0, 4, 7), 6)
        );
        $this->assertEquals(
            array(2, 0),
            PMA_Linter::findLineNumberAndColumn(array(0, 4, 7), 7)
        );
    }

    /**
     * Test for PMA_Linter::lint
     *
     * @dataProvider testLintProvider
     *
     * @param array  $expected The expected result.
     * @param string $query    The query to be analyzed.
     *
     * @return void
     */
    public function testLint($expected, $query)
    {
        $this->assertEquals($expected, PMA_Linter::lint($query));
    }

    /**
     * Provides data for `testLint`.
     *
     * @return array
     */
    public static function testLintProvider()
    {
        return array(
            array(
                array(),
                '',
            ),
            array(
                array(),
                'SELECT * FROM tbl'
            ),
            array(
                array(
                    array(
                        'message' => 'Unrecognized data type. (near ' .
                            '<code>IN</code>)',
                        'fromLine' => 0,
                        'fromColumn' => 22,
                        'toLine' => 0,
                        'toColumn' => 24,
                        'severity' => 'error',
                    ),
                    array(
                        'message' => 'A closing bracket was expected. (near ' .
                            '<code>IN</code>)',
                        'fromLine' => 0,
                        'fromColumn' => 22,
                        'toLine' => 0,
                        'toColumn' => 24,
                        'severity' => 'error',
                    )
                ),
                'CREATE TABLE tbl ( id IN'
            ),
            array(
                array(
                    array(
                        'message' => 'Linting is disabled for this query because ' .
                            'it exceeds the maximum length.',
                        'fromLine' => 0,
                        'fromColumn' => 0,
                        'toLine' => 0,
                        'toColumn' => 0,
                        'severity' => 'warning',
                    )
                ),
                str_repeat(";", 10001)
            )
        );
    }
}
