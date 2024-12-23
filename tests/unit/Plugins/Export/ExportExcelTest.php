<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Plugins\Export\ExportCsv;
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
#[CoversClass(ExportCsv::class)]
#[Medium]
class ExportExcelTest extends AbstractTestCase
{
    protected ExportExcel $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $this->object = new ExportExcel(
            new Relation($dbi),
            new Export($dbi),
            new Transformations(),
        );
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportExcel::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportExcel::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

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
            'general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'null',
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
            'removeCRLF',
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
            'columns',
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
            'edition',
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
            'structure_or_data',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        // case 1
        $GLOBALS['excel_edition'] = 'win';
        $GLOBALS['excel_columns'] = true;

        self::assertTrue(
            $this->object->exportHeader(),
        );

        self::assertSame("\015\012", $GLOBALS['csv_terminated']);

        self::assertSame(';', $GLOBALS['csv_separator']);

        self::assertSame('"', $GLOBALS['csv_enclosed']);

        self::assertSame('"', $GLOBALS['csv_escaped']);

        self::assertTrue($GLOBALS['excel_columns']);

        // case 2

        $GLOBALS['excel_edition'] = 'mac_excel2003';
        unset($GLOBALS['excel_columns']);
        $GLOBALS['excel_columns'] = false;

        self::assertTrue(
            $this->object->exportHeader(),
        );

        self::assertSame("\015\012", $GLOBALS['csv_terminated']);

        self::assertSame(';', $GLOBALS['csv_separator']);

        self::assertSame('"', $GLOBALS['csv_enclosed']);

        self::assertSame('"', $GLOBALS['csv_escaped']);

        self::assertFalse($GLOBALS['excel_columns']);

        // case 3

        $GLOBALS['excel_edition'] = 'mac_excel2008';

        self::assertTrue(
            $this->object->exportHeader(),
        );

        self::assertSame("\015\012", $GLOBALS['csv_terminated']);

        self::assertSame(',', $GLOBALS['csv_separator']);

        self::assertSame('"', $GLOBALS['csv_enclosed']);

        self::assertSame('"', $GLOBALS['csv_escaped']);

        self::assertFalse($GLOBALS['excel_columns']);

        // case 4

        $GLOBALS['excel_edition'] = 'testBlank';
        $GLOBALS['excel_separator'] = '#';

        self::assertTrue(
            $this->object->exportHeader(),
        );

        self::assertSame('#', $GLOBALS['excel_separator']);
    }

    public function testExportData(): void
    {
        // case 1
        $GLOBALS['csv_columns'] = true;
        $GLOBALS['csv_terminated'] = ';';

        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = true;
        $GLOBALS['file_handle'] = null;

        ob_start();
        self::assertFalse($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        ob_get_clean();

        // case 2
        $GLOBALS['excel_null'] = 'customNull';
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['csv_enclosed'] = '';
        $GLOBALS['csv_separator'] = '';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        self::assertSame(
            'idnamedatetimefield;1abcd2011-01-20 02:00:02;2foo2010-01-20 02:00:02;3Abcd2012-01-20 02:00:02;',
            $result,
        );

        // case 3
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = '';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        self::assertSame(
            '"id""name""datetimefield";"1""abcd""2011-01-20 02:00:02";'
            . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";',
            $result,
        );

        // case 4
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['excel_removeCRLF'] = true;
        $GLOBALS['csv_escaped'] = '"';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        self::assertSame(
            '"id""name""datetimefield";"1""abcd""2011-01-20 02:00:02";'
            . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";',
            $result,
        );

        // case 5
        $GLOBALS['excel_enclosed'] = '"';
        unset($GLOBALS['excel_removeCRLF']);
        $GLOBALS['excel_escaped'] = ';';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        self::assertSame(
            '"id""name""datetimefield";"1""abcd""2011-01-20 02:00:02";'
            . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";',
            $result,
        );

        // case 6
        $GLOBALS['excel_enclosed'] = '"';
        $GLOBALS['excel_escaped'] = '#';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        self::assertSame(
            '"id""name""datetimefield";"1""abcd""2011-01-20 02:00:02";'
            . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";',
            $result,
        );
    }
}
