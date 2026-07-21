<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\Export\ExportPhparray;

/**
 * @covers \PhpMyAdmin\Export
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
        parent::setUp();
        $this->export = new Export($GLOBALS['dbi']);
    }

    /**
     * Test for mergeAliases
     */
    public function testMergeAliases(): void
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
                        'columns' => ['bar' => 'foobar'],
                    ],
                    'baz' => [
                        'columns' => ['a' => 'x'],
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
                        'columns' => ['a' => 'x'],
                    ],
                ],
            ],
        ];
        $actual = $this->export->mergeAliases($aliases1, $aliases2);
        self::assertSame($expected, $actual);
    }

    /**
     * Test for getFinalFilenameAndMimetypeForFilename
     */
    public function testGetFinalFilenameAndMimetypeForFilename(): void
    {
        $exportPlugin = new ExportPhparray();
        $finalFileName = $this->export->getFinalFilenameAndMimetypeForFilename($exportPlugin, 'zip', 'myfilename');
        self::assertSame([
            'myfilename.php.zip',
            'application/zip',
        ], $finalFileName);
        $finalFileName = $this->export->getFinalFilenameAndMimetypeForFilename($exportPlugin, 'gzip', 'myfilename');
        self::assertSame([
            'myfilename.php.gz',
            'application/x-gzip',
        ], $finalFileName);
        $finalFileName = $this->export->getFinalFilenameAndMimetypeForFilename(
            $exportPlugin,
            'gzip',
            'export.db1.table1.file'
        );
        self::assertSame([
            'export.db1.table1.file.php.gz',
            'application/x-gzip',
        ], $finalFileName);
    }

    /**
     * Test that getFilenameAndMimetype uses database/table aliases in the
     * filename template (see issue #18510).
     */
    public function testGetFilenameAndMimetypeUsesAliases(): void
    {
        // Util::expandUserString() reads the current server host/verbose when
        // building the filename, so provide a minimal server configuration.
        $GLOBALS['cfg']['Server'] = ['host' => 'localhost', 'verbose' => ''];
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $aliases = [
            'test_db' => [
                'alias' => 'aliasdb',
                'tables' => [
                    'test_table' => ['alias' => 'aliastbl'],
                ],
            ],
        ];

        [$filename] = $this->export->getFilenameAndMimetype(
            'table',
            '',
            new ExportPhparray(),
            'none',
            '@DATABASE@-@TABLE@',
            $aliases
        );

        self::assertSame('aliasdb-aliastbl.php', $filename);
    }
}
