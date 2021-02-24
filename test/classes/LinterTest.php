<?php
/**
 * Tests for Linter.php.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Linter;
use function str_repeat;

class LinterTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
    }

    /**
     * Test for Linter::getLines
     */
    public function testGetLines(): void
    {
        $this->assertEquals([0], Linter::getLines(''));
        $this->assertEquals([0, 2], Linter::getLines("a\nb"));
        $this->assertEquals([0, 4, 7], Linter::getLines("abc\nde\n"));
    }

    /**
     * Test for Linter::findLineNumberAndColumn
     */
    public function testFindLineNumberAndColumn(): void
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
            [
                1,
                0,
            ],
            Linter::findLineNumberAndColumn([0, 4, 7], 4)
        );
        $this->assertEquals(
            [
                1,
                1,
            ],
            Linter::findLineNumberAndColumn([0, 4, 7], 5)
        );
        $this->assertEquals(
            [
                1,
                2,
            ],
            Linter::findLineNumberAndColumn([0, 4, 7], 6)
        );
        $this->assertEquals(
            [
                2,
                0,
            ],
            Linter::findLineNumberAndColumn([0, 4, 7], 7)
        );
    }

    /**
     * Test for Linter::lint
     *
     * @param array  $expected The expected result.
     * @param string $query    The query to be analyzed.
     *
     * @dataProvider lintProvider
     */
    public function testLint(array $expected, string $query): void
    {
        $this->assertEquals($expected, Linter::lint($query));
    }

    /**
     * Provides data for `testLint`.
     *
     * @return array
     */
    public static function lintProvider(): array
    {
        return [
            [
                [],
                '',
            ],
            [
                [],
                'SELECT * FROM tbl',
            ],
            [
                [
                    [
                        'message' => 'Unrecognized data type. (near ' .
                            '<code>IN</code>)',
                        'fromLine' => 0,
                        'fromColumn' => 22,
                        'toLine' => 0,
                        'toColumn' => 24,
                        'severity' => 'error',
                    ],
                    [
                        'message' => 'A closing bracket was expected. (near ' .
                            '<code>IN</code>)',
                        'fromLine' => 0,
                        'fromColumn' => 22,
                        'toLine' => 0,
                        'toColumn' => 24,
                        'severity' => 'error',
                    ],
                ],
                'CREATE TABLE tbl ( id IN',
            ],
            [
                [
                    [
                        'message' => 'Linting is disabled for this query because ' .
                            'it exceeds the maximum length.',
                        'fromLine' => 0,
                        'fromColumn' => 0,
                        'toLine' => 0,
                        'toColumn' => 0,
                        'severity' => 'warning',
                    ],
                ],
                str_repeat(';', 10001),
            ],
        ];
    }
}
