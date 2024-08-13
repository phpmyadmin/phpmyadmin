<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Plugins;

/**
 * @covers \PhpMyAdmin\Plugins
 */
class PluginsTest extends AbstractTestCase
{
    public function testGetExport(): void
    {
        global $plugin_param;

        $GLOBALS['server'] = 1;
        $plugins = Plugins::getExport('database', false);
        $this->assertEquals(['export_type' => 'database', 'single_table' => false], $plugin_param);
        $this->assertIsArray($plugins);
        $this->assertCount(14, $plugins);
        $this->assertContainsOnlyInstancesOf(Plugins\ExportPlugin::class, $plugins);
    }

    public function testGetImport(): void
    {
        global $plugin_param;

        $plugins = Plugins::getImport('database');
        $this->assertEquals('database', $plugin_param);
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
     * @param string|int|null $actualConfig
     * @psalm-param 'Export'|'Import'|'Schema' $section
     *
     * @dataProvider providerForTestGetDefault
     */
    public function testGetDefault(
        string $expected,
        $actualConfig,
        ?string $actualGet,
        string $section,
        string $option,
        ?bool $timeoutPassed
    ): void {
        global $cfg, $strLatexContinued, $strLatexStructure, $timeout_passed;

        $_GET = [];
        $_REQUEST = [];
        if ($timeoutPassed !== null) {
            $timeout_passed = $timeoutPassed;
            $_REQUEST[$option] = $actualGet;
        } elseif ($actualGet !== null) {
            $_GET[$option] = $actualGet;
        }

        $strLatexContinued = '(continued)';
        $strLatexStructure = 'Structure of table @TABLE@';
        /** @psalm-suppress InvalidArrayOffset, PossiblyInvalidArrayAssignment */
        $cfg[$section][$option] = $actualConfig;
        $default = Plugins::getDefault($section, $option);
        $this->assertSame($expected, $default);
    }

    /**
     * @return array[]
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
        global $plugin_param;

        $GLOBALS['server'] = 1;
        $plugin_param = ['export_type' => 'database', 'single_table' => false];
        $exportList = [
            new Plugins\Export\ExportJson(),
            new Plugins\Export\ExportOds(),
            new Plugins\Export\ExportSql(),
            new Plugins\Export\ExportXml(),
        ];
        $actual = Plugins::getChoice($exportList, 'xml');
        $expected = [
            ['name' => 'json', 'text' => 'JSON', 'is_selected' => false, 'force_file' => false],
            ['name' => 'ods', 'text' => 'OpenDocument Spreadsheet', 'is_selected' => false, 'force_file' => true],
            ['name' => 'sql', 'text' => 'SQL', 'is_selected' => false, 'force_file' => false],
            ['name' => 'xml', 'text' => 'XML', 'is_selected' => true, 'force_file' => false],
        ];
        $this->assertEquals($expected, $actual);
    }
}
