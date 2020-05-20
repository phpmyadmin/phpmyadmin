<?php
/**
 * tests for PhpMyAdmin\Export
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\Export\ExportPhparray;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * PhpMyAdmin\ExportTest class
 *
 * this class is for testing PhpMyAdmin\Export methods
 *
 * @group large
 */
class ExportTest extends AbstractTestCase
{
    /** @var Export */
    private $export;

    /**
     * Sets up the fixture
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

    /**
     * Test for getFinalFilenameAndMimetypeForFilename
     *
     * @return void
     */
    public function testGetFinalFilenameAndMimetypeForFilename()
    {
        $exportPlugin = new ExportPhparray();
        $finalFileName = $this->export->getFinalFilenameAndMimetypeForFilename(
            $exportPlugin,
            'zip',
            'myfilename'
        );
        $this->assertSame([
            'myfilename.php.zip',
            'application/zip',
        ], $finalFileName);
        $finalFileName = $this->export->getFinalFilenameAndMimetypeForFilename(
            $exportPlugin,
            'gzip',
            'myfilename'
        );
        $this->assertSame([
            'myfilename.php.gz',
            'application/x-gzip',
        ], $finalFileName);
        $finalFileName = $this->export->getFinalFilenameAndMimetypeForFilename(
            $exportPlugin,
            'gzip',
            'export.db1.table1.file'
        );
        $this->assertSame([
            'export.db1.table1.file.php.gz',
            'application/x-gzip',
        ], $finalFileName);
    }
}
