<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportExcel;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
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

#[CoversClass(ExportExcel::class)]
#[Medium]
final class ExportExcelTest extends AbstractTestCase
{
    public function testSetProperties(): void
    {
        $exportExcel = $this->getExportExcel();

        $method = new ReflectionMethod(ExportExcel::class, 'setProperties');
        $method->invoke($exportExcel, null);

        $attrProperties = new ReflectionProperty(ExportExcel::class, 'properties');
        $properties = $attrProperties->getValue($exportExcel);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'CSV for MS Excel',
            $properties->getText(),
        );

        self::assertSame(
            'csv',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/comma-separated-values',
            $properties->getMimeType(),
        );

        self::assertSame(
            'Options',
            $properties->getOptionsText(),
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
            'excel_general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'excel_null',
            $property->getName(),
        );

        self::assertSame(
            'Replace NULL with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'excel_removeCRLF',
            $property->getName(),
        );

        self::assertSame(
            'Remove carriage return/line feed characters within columns',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'excel_columns',
            $property->getName(),
        );

        self::assertSame(
            'Put columns names in the first row',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(SelectPropertyItem::class, $property);

        self::assertSame(
            'excel_edition',
            $property->getName(),
        );

        self::assertSame(
            [
                'win' => 'Windows',
                'mac_excel2003' => 'Excel 2003 / Macintosh',
                'mac_excel2008' => 'Excel 2008 / Macintosh',
            ],
            $property->getValues(),
        );

        self::assertSame(
            'Excel edition:',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertSame(
            'excel_structure_or_data',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        $exportExcel = $this->getExportExcel();

        // case 1
        $this->expectNotToPerformAssertions();
        $exportExcel->exportHeader();

        // case 2
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['excel_edition' => 'mac_excel2003']);

        $exportExcel->setExportOptions($request, new Export());

        $exportExcel->exportHeader();

        // case 3
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['excel_edition' => 'mac_excel2008']);

        $exportExcel->setExportOptions($request, new Export());

        $exportExcel->exportHeader();
    }

    public function testExportData(): void
    {
        $exportExcel = $this->getExportExcel();

        // case 1
        OutputHandler::$asFile = true;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['excel_columns' => 'On']);

        $exportExcel->setExportOptions($request, new Export());

        ob_start();
        $exportExcel->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertSame(
            '"id";"name";"datetimefield"' . "\015\012"
                . '"1";"abcd";"2011-01-20 02:00:02"' . "\015\012"
                . '"2";"foo";"2010-01-20 02:00:02"' . "\015\012"
                . '"3";"Abcd";"2012-01-20 02:00:02"' . "\015\012",
            $result,
        );
    }

    private function getExportExcel(): ExportExcel
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportExcel($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
