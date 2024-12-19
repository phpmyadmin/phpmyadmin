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
        self::assertSame(ExportType::Database, ExportPlugin::$exportType);
        self::assertFalse(ExportPlugin::$singleTable);
        self::assertCount(14, $plugins);
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

        $GLOBALS['strLatexContinued'] = '(continued)';
        $GLOBALS['strLatexStructure'] = 'Structure of table @TABLE@';
        /** @psalm-suppress InvalidArrayOffset, PossiblyInvalidArrayAssignment */
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
        $dbi = DatabaseInterface::getInstance();
        $exportList = [
            new Plugins\Export\ExportJson(
                new Relation($dbi),
                new Export($dbi),
                new Transformations(),
            ),
            new Plugins\Export\ExportOds(
                new Relation($dbi),
                new Export($dbi),
                new Transformations(),
            ),
            new Plugins\Export\ExportSql(
                new Relation($dbi),
                new Export($dbi),
                new Transformations(),
            ),
            new Plugins\Export\ExportXml(
                new Relation($dbi),
                new Export($dbi),
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
        self::assertSame($expected, $actual);
    }
}
