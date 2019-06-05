<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Export
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

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
     * @var Export
     */
    private $export;

    /**
     * Sets up the fixture
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->export = new Export($GLOBALS['dbi']);
    }

    /**
     * Test for mergeAliases
     *
     * @return void
     */
    public function testMergeAliases()
    {
        $aliases1 = [
            'test_db' => [
                'alias' => 'aliastest',
                'tables' => [
                    'foo' => [
                        'alias' => 'foobar',
                        'columns' => [
                            'bar' => 'foo',
                            'baz' => 'barbaz',
                        ],
                    ],
                    'bar' => [
                        'alias' => 'foobaz',
                        'columns' => [
                            'a' => 'a_alias',
                            'b' => 'b',
                        ],
                    ],
                ],
            ],
        ];
        $aliases2 = [
            'test_db' => [
                'alias' => 'test',
                'tables' => [
                    'foo' => [
                        'columns' => [
                            'bar' => 'foobar',
                        ],
                    ],
                    'baz' => [
                        'columns' => [
                            'a' => 'x',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'test_db' => [
                'alias' => 'test',
                'tables' => [
                    'foo' => [
                        'alias' => 'foobar',
                        'columns' => [
                            'bar' => 'foobar',
                            'baz' => 'barbaz',
                        ],
                    ],
                    'bar' => [
                        'alias' => 'foobaz',
                        'columns' => [
                            'a' => 'a_alias',
                            'b' => 'b',
                        ],
                    ],
                    'baz' => [
                        'columns' => [
                            'a' => 'x',
                        ],
                    ],
                ],
            ],
        ];
        $actual = $this->export->mergeAliases($aliases1, $aliases2);
        $this->assertEquals($expected, $actual);
    }
}
