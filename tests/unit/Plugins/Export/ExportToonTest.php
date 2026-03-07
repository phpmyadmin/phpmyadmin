<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportToon;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportToon::class)]
#[Medium]
class ExportToonTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = false;
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = 'en';
    }

    public function testSetProperties(): void
    {
        $exportToon = $this->getExportToon();

        $method = new ReflectionMethod(ExportToon::class, 'setProperties');
        $method->invoke($exportToon, null);

        $attrProperties = new ReflectionProperty(ExportToon::class, 'properties');
        $properties = $attrProperties->getValue($exportToon);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'TOON',
            $properties->getText(),
        );

        self::assertSame(
            'toon',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/toon',
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
            'toon_general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'toon_separator',
            $property->getName(),
        );

        self::assertSame(
            'Columns separated with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'toon_indent',
            $property->getName(),
        );

        self::assertSame(
            'Indentation:',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertSame(
            'toon_structure_or_data',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        $exportToon = $this->getExportToon();
        $this->expectNotToPerformAssertions();
        $exportToon->exportHeader();
    }

    public function testExportFooter(): void
    {
        $exportToon = $this->getExportToon();
        $this->expectNotToPerformAssertions();
        $exportToon->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $exportToon = $this->getExportToon();
        $this->expectNotToPerformAssertions();
        $exportToon->exportDBHeader('testDB');
    }

    public function testExportDBFooter(): void
    {
        $exportToon = $this->getExportToon();
        $this->expectNotToPerformAssertions();
        $exportToon->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportToon = $this->getExportToon();
        $this->expectNotToPerformAssertions();
        $exportToon->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $exportToon = $this->getExportToon();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([]);

        $exportToon->setExportOptions($request, new Export());

        ob_start();
        $exportToon->exportData(
            'test_db',
            'test_table_export_toon',
            'SELECT * FROM `test_db`.`test_table_export_toon`;',
        );
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame(
            'test_db.test_table_export_toon[3]{id,name,datetimefield,textfield,intfield}:' . "\n" .
            '  1,abcd,2011-01-20 02:00:02,31,null' . "\n" .
            '  2,foo,2010-01-20 02:00:02,null,null' . "\n" .
            '  3,Abcd,2012-01-20 02:00:02,null,8' . "\n\n",
            $result,
        );
    }

    public function testExportDataWithCustomConfig(): void
    {
        $exportToon = $this->getExportToon();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['toon_separator' => '|', 'toon_indent' => '4']);

        $exportToon->setExportOptions($request, new Export());

        ob_start();
        $exportToon->exportData(
            'test_db',
            'test_table_export_toon',
            'SELECT * FROM `test_db`.`test_table_export_toon`;',
        );
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame(
            'test_db.test_table_export_toon[3|]{id|name|datetimefield|textfield|intfield}:' . "\n" .
            '    1|abcd|2011-01-20 02:00:02|31|null' . "\n" .
            '    2|foo|2010-01-20 02:00:02|null|null' . "\n" .
            '    3|Abcd|2012-01-20 02:00:02|null|8' . "\n\n",
            $result,
        );
    }

    private function getExportToon(DatabaseInterface|null $dbi = null): ExportToon
    {
        $dbi ??= $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportToon($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
