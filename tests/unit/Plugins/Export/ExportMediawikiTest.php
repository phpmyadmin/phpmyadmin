<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export as SettingsExport;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportMediawiki;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function __;
use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportMediawiki::class)]
#[Medium]
final class ExportMediawikiTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = true;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = 'en';
    }

    public function testSetProperties(): void
    {
        $exportMediawiki = $this->getExportMediawiki();

        $method = new ReflectionMethod(ExportMediawiki::class, 'setProperties');
        $method->invoke($exportMediawiki, null);

        $attrProperties = new ReflectionProperty(ExportMediawiki::class, 'properties');
        $properties = $attrProperties->getValue($exportMediawiki);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'MediaWiki Table',
            $properties->getText(),
        );

        self::assertSame(
            'mediawiki',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/plain',
            $properties->getMimeType(),
        );

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'mediawiki_general_opts',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Dump table',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(OptionsPropertySubgroup::class, $property);

        self::assertSame(
            'mediawiki_dump_table',
            $property->getName(),
        );

        self::assertSame(
            'Dump table',
            $property->getText(),
        );

        $sgHeader = $property->getSubgroupHeader();

        self::assertInstanceOf(RadioPropertyItem::class, $sgHeader);

        self::assertSame(
            'mediawiki_structure_or_data',
            $sgHeader->getName(),
        );

        self::assertSame(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
            $sgHeader->getValues(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'mediawiki_caption',
            $property->getName(),
        );

        self::assertSame(
            'Export table names',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'mediawiki_headers',
            $property->getName(),
        );

        self::assertSame(
            'Export table headers',
            $property->getText(),
        );
    }

    public function testExportHeader(): void
    {
        $exportMediawiki = $this->getExportMediawiki();
        $this->expectNotToPerformAssertions();
        $exportMediawiki->exportHeader();
    }

    public function testExportFooter(): void
    {
        $exportMediawiki = $this->getExportMediawiki();
        $this->expectNotToPerformAssertions();
        $exportMediawiki->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $exportMediawiki = $this->getExportMediawiki();
        $this->expectNotToPerformAssertions();
        $exportMediawiki->exportDBHeader('testDB');
    }

    public function testExportDBFooter(): void
    {
        $exportMediawiki = $this->getExportMediawiki();
        $this->expectNotToPerformAssertions();
        $exportMediawiki->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportMediawiki = $this->getExportMediawiki();
        $this->expectNotToPerformAssertions();
        $exportMediawiki->exportDBCreate('testDB');
    }

    /**
     * Test for ExportMediaWiki::exportStructure
     */
    public function testExportStructure(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $columns = [
            new Column('name1', 'set(abc)enum123', null, true, 'PRI', '', '', '', ''),
            new Column('fields', '', null, false, 'COMP', 'def', 'ext', '', ''),
        ];

        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('db', 'table')
            ->willReturn($columns);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['mediawiki_headers' => 'On', 'mediawiki_caption' => 'On']);

        $exportMediawiki = $this->getExportMediawiki($dbi);
        $exportMediawiki->setExportOptions($request, new SettingsExport());

        ob_start();
        $exportMediawiki->exportStructure('db', 'table', 'create_table');
        $result = ob_get_clean();

        self::assertSame(
            "\n<!--\n" .
            "Table structure for `table`\n" .
            "-->\n" .
            "\n" .
            "{| class=\"wikitable\" style=\"text-align:center;\"\n" .
            "|+'''table'''\n" .
            "|- style=\"background:#ffdead;\"\n" .
            "! style=\"background:#ffffff\" | \n" .
            " | name1\n" .
            " | fields\n" .
            "|-\n" .
            "! Type\n" .
            " | set(abc)enum123\n" .
            " | \n" .
            "|-\n" .
            "! Null\n" .
            " | YES\n" .
            " | NO\n" .
            "|-\n" .
            "! Default\n" .
            " | \n" .
            " | def\n" .
            "|-\n" .
            "! Extra\n" .
            " | \n" .
            " | ext\n" .
            "|}\n\n",
            $result,
        );
    }

    public function testExportData(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['mediawiki_headers' => 'On', 'mediawiki_caption' => 'On']);

        $exportMediawiki = $this->getExportMediawiki();
        $exportMediawiki->setExportOptions($request, new SettingsExport());

        ob_start();
        $exportMediawiki->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertSame(
            "\n<!--\n" .
            "Table data for `test_table`\n" .
            "-->\n" .
            "\n" .
            '{| class="wikitable sortable" style="text-align:' .
            "center;\"\n" .
            "|+'''test_table'''\n" .
            "|-\n" .
            " ! id\n" .
            " ! name\n" .
            " ! datetimefield\n" .
            "|-\n" .
            " | 1\n" .
            " | abcd\n" .
            " | 2011-01-20 02:00:02\n" .
            "|-\n" .
            " | 2\n" .
            " | foo\n" .
            " | 2010-01-20 02:00:02\n" .
            "|-\n" .
            " | 3\n" .
            " | Abcd\n" .
            " | 2012-01-20 02:00:02\n" .
            "|}\n\n",
            $result,
        );
    }

    public function testExportTableCallsExportStructureMethod(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['mediawiki_structure_or_data' => 'structure']);

        $exportMediawiki = $this->getExportMediawiki($dbi);
        $exportMediawiki->setExportOptions($request, new SettingsExport());
        ob_start();
        $export = new Export($dbi, new OutputHandler());
        $export->exportTable(
            'testdb',
            'testtable',
            $exportMediawiki,
            null,
            '0',
            '0',
            '',
            [],
        );
        $result = ob_get_clean();
        self::assertIsString($result);
        self::assertStringContainsString('Table structure for', $result);
        self::assertStringContainsString('testtable', $result);
    }

    private function getExportMediawiki(DatabaseInterface|null $dbi = null): ExportMediawiki
    {
        $dbi ??= $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportMediawiki($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
