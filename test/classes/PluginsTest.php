<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Plugins */
class PluginsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadContainerBuilder();
        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        parent::loadDbiIntoContainerBuilder();
    }

    public function testGetExport(): void
    {
        $GLOBALS['server'] = 1;
        $plugins = Plugins::getExport('database', false);
        $this->assertEquals(['export_type' => 'database', 'single_table' => false], $GLOBALS['plugin_param']);
        $this->assertIsArray($plugins);
        $this->assertCount(14, $plugins);
        $this->assertContainsOnlyInstancesOf(Plugins\ExportPlugin::class, $plugins);
    }

    public function testGetImport(): void
    {
        $plugins = Plugins::getImport('database');
        $this->assertEquals('database', $GLOBALS['plugin_param']);
        $this->assertIsArray($plugins);
        $this->assertCount(6, $plugins);
        $this->assertContainsOnlyInstancesOf(Plugins\ImportPlugin::class, $plugins);
    }

    public function testGetSchema(): void
    {
        $plugins = Plugins::getSchema();
        $this->assertIsArray($plugins);
        $this->assertCount(4, $plugins);
        $this->assertContainsOnlyInstancesOf(Plugins\SchemaPlugin::class, $plugins);
    }

    /**
     * @psalm-param 'Export'|'Import'|'Schema' $section
     *
     * @dataProvider providerForTestGetDefault
     */
    public function testGetDefault(
        string $expected,
        string|int|null $actualConfig,
        string|null $actualGet,
        string $section,
        string $option,
        bool|null $timeoutPassed,
    ): void {
        $_GET = [];
        $_REQUEST = [];
        if ($timeoutPassed !== null) {
            $GLOBALS['timeout_passed'] = $timeoutPassed;
            $_REQUEST[$option] = $actualGet;
        } elseif ($actualGet !== null) {
            $_GET[$option] = $actualGet;
        }

        $GLOBALS['strLatexContinued'] = '(continued)';
        $GLOBALS['strLatexStructure'] = 'Structure of table @TABLE@';
        /** @psalm-suppress InvalidArrayOffset, PossiblyInvalidArrayAssignment */
        $GLOBALS['cfg'][$section][$option] = $actualConfig;
        $default = Plugins::getDefault($section, $option);
        $this->assertSame($expected, $default);
    }

    /**
     * @return mixed[][]
     * @psalm-return array{array{string, string|int|null, string|null, 'Export'|'Import'|'Schema', string, bool|null}}
     */
    public static function providerForTestGetDefault(): array
    {
        return [
            ['xml', 'xml', null, 'Export', 'format', null],
            ['xml', 'sql', 'xml', 'Export', 'format', null],
            ['xml', null, 'xml', 'Export', 'format', null],
            ['', null, null, 'Export', 'format', null],
            [
                'Structure of table @TABLE@ strTest (continued)',
                'strLatexStructure strTest strLatexContinued',
                null,
                'Export',
                'latex_structure_continued_caption',
                null,
            ],
            ['xml', 'sql', 'xml', 'Export', 'format', true],
            ['sql', 'sql', 'xml', 'Export', 'format', false],
            ['30', 30, null, 'Import', 'skip_queries', null],
        ];
    }

    public function testGetChoice(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['plugin_param'] = ['export_type' => 'database', 'single_table' => false];
        $exportList = [
            new Plugins\Export\ExportJson(
                new Relation($GLOBALS['dbi']),
                new Export($GLOBALS['dbi']),
                new Transformations(),
            ),
            new Plugins\Export\ExportOds(
                new Relation($GLOBALS['dbi']),
                new Export($GLOBALS['dbi']),
                new Transformations(),
            ),
            new Plugins\Export\ExportSql(
                new Relation($GLOBALS['dbi']),
                new Export($GLOBALS['dbi']),
                new Transformations(),
            ),
            new Plugins\Export\ExportXml(
                new Relation($GLOBALS['dbi']),
                new Export($GLOBALS['dbi']),
                new Transformations(),
            ),
        ];
        $actual = Plugins::getChoice($exportList, 'xml');
        $expected = [
            ['name' => 'json', 'text' => 'JSON', 'is_selected' => false, 'is_binary' => false],
            ['name' => 'ods', 'text' => 'OpenDocument Spreadsheet', 'is_selected' => false, 'is_binary' => true],
            ['name' => 'sql', 'text' => 'SQL', 'is_selected' => false, 'is_binary' => false],
            ['name' => 'xml', 'text' => 'XML', 'is_selected' => true, 'is_binary' => false],
        ];
        $this->assertEquals($expected, $actual);
    }
}
