<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function extension_loaded;

#[CoversClass(Plugins::class)]
class PluginsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testGetExport(): void
    {
        $plugins = Plugins::getExport(ExportType::Database, false);
        $isCurl = extension_loaded('curl');
        self::assertSame(ExportType::Database, ExportPlugin::$exportType);
        self::assertFalse(ExportPlugin::$singleTable);
        $pluginCount = $isCurl ? 14 : 13;
        self::assertCount($pluginCount, $plugins);
        self::assertContainsOnlyInstancesOf(Plugins\ExportPlugin::class, $plugins);
    }

    public function testGetImport(): void
    {
        ImportSettings::$importType = 'database';
        $plugins = Plugins::getImport();
        self::assertCount(6, $plugins);
        self::assertContainsOnlyInstancesOf(Plugins\ImportPlugin::class, $plugins);
    }

    public function testGetSchema(): void
    {
        $plugins = Plugins::getSchema();
        self::assertCount(4, $plugins);
        self::assertContainsOnlyInstancesOf(Plugins\SchemaPlugin::class, $plugins);
    }

    /** @psalm-param 'Export'|'Import'|'Schema' $section */
    #[DataProvider('providerForTestGetDefault')]
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
            ImportSettings::$timeoutPassed = $timeoutPassed;
            $_REQUEST[$option] = $actualGet;
        } elseif ($actualGet !== null) {
            $_GET[$option] = $actualGet;
        }

        Config::getInstance()->settings[$section][$option] = $actualConfig;
        $default = Plugins::getDefault($section, $option);
        self::assertSame($expected, $default);
    }

    /** @return array<array{string, string|int|null, string|null, 'Export'|'Import'|'Schema', string, bool|null}> */
    public static function providerForTestGetDefault(): array
    {
        return [
            ['xml', 'xml', null, 'Export', 'format', null],
            ['xml', 'sql', 'xml', 'Export', 'format', null],
            ['xml', null, 'xml', 'Export', 'format', null],
            ['', null, null, 'Export', 'format', null],
            [
                'strLatexStructure strTest strLatexContinued',
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
        $dbi = DatabaseInterface::getInstance();
        $relation = new Relation($dbi);
        $transformations = new Transformations($dbi, $relation);
        $export = new Export($dbi);
        $exportList = [
            new Plugins\Export\ExportJson($relation, $export, $transformations),
            new Plugins\Export\ExportOds($relation, $export, $transformations),
            new Plugins\Export\ExportSql($relation, $export, $transformations),
            new Plugins\Export\ExportXml($relation, $export, $transformations),
        ];
        $actual = Plugins::getChoice($exportList, 'xml');
        $expected = [
            ['name' => 'json', 'text' => 'JSON', 'is_selected' => false, 'is_binary' => false],
            ['name' => 'ods', 'text' => 'OpenDocument Spreadsheet', 'is_selected' => false, 'is_binary' => true],
            ['name' => 'sql', 'text' => 'SQL', 'is_selected' => false, 'is_binary' => false],
            ['name' => 'xml', 'text' => 'XML', 'is_selected' => true, 'is_binary' => false],
        ];
        self::assertSame($expected, $actual);
    }
}
