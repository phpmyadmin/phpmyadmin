<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\Export\ExportCsv;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportCsv
 * @group medium
 */
class ExportCsvTest extends AbstractTestCase
{
    protected ExportCsv $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = '';
        $GLOBALS['text_dir'] = '';
        $GLOBALS['csv_enclosed'] = null;
        $GLOBALS['csv_separator'] = null;
        $GLOBALS['save_filename'] = null;

        $this->object = new ExportCsv(
            new Relation($GLOBALS['dbi']),
            new Export($GLOBALS['dbi']),
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
        $method = new ReflectionMethod(ExportCsv::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportCsv::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(ExportPluginProperties::class, $properties);

        $this->assertEquals(
            'CSV',
            $properties->getText(),
        );

        $this->assertEquals(
            'csv',
            $properties->getExtension(),
        );

        $this->assertEquals(
            'text/comma-separated-values',
            $properties->getMimeType(),
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText(),
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        $this->assertEquals(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray->current();

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'separator',
            $property->getName(),
        );

        $this->assertEquals(
            'Columns separated with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'enclosed',
            $property->getName(),
        );

        $this->assertEquals(
            'Columns enclosed with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'escaped',
            $property->getName(),
        );

        $this->assertEquals(
            'Columns escaped with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'terminated',
            $property->getName(),
        );

        $this->assertEquals(
            'Lines terminated with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(TextPropertyItem::class, $property);

        $this->assertEquals(
            'null',
            $property->getName(),
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'removeCRLF',
            $property->getName(),
        );

        $this->assertEquals(
            'Remove carriage return/line feed characters within columns',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        $this->assertInstanceOf(BoolPropertyItem::class, $property);

        $this->assertEquals(
            'columns',
            $property->getName(),
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText(),
        );

        $property = $generalProperties->current();

        $this->assertInstanceOf(HiddenPropertyItem::class, $property);

        $this->assertEquals(
            'structure_or_data',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        // case 1

        $GLOBALS['what'] = 'excel';
        $GLOBALS['excel_edition'] = 'win';
        $GLOBALS['excel_columns'] = true;

        $this->assertTrue(
            $this->object->exportHeader(),
        );

        $this->assertEquals("\015\012", $GLOBALS['csv_terminated']);

        $this->assertEquals(';', $GLOBALS['csv_separator']);

        $this->assertEquals('"', $GLOBALS['csv_enclosed']);

        $this->assertEquals('"', $GLOBALS['csv_escaped']);

        $this->assertEquals(true, $GLOBALS['csv_columns']);

        // case 2

        $GLOBALS['excel_edition'] = 'mac_excel2003';
        unset($GLOBALS['excel_columns']);
        $GLOBALS['csv_columns'] = false;

        $this->assertTrue(
            $this->object->exportHeader(),
        );

        $this->assertEquals("\015\012", $GLOBALS['csv_terminated']);

        $this->assertEquals(';', $GLOBALS['csv_separator']);

        $this->assertEquals('"', $GLOBALS['csv_enclosed']);

        $this->assertEquals('"', $GLOBALS['csv_escaped']);

        $this->assertEquals(false, $GLOBALS['csv_columns']);

        // case 3

        $GLOBALS['excel_edition'] = 'mac_excel2008';

        $this->assertTrue(
            $this->object->exportHeader(),
        );

        $this->assertEquals("\015\012", $GLOBALS['csv_terminated']);

        $this->assertEquals(',', $GLOBALS['csv_separator']);

        $this->assertEquals('"', $GLOBALS['csv_enclosed']);

        $this->assertEquals('"', $GLOBALS['csv_escaped']);

        $this->assertEquals(false, $GLOBALS['csv_columns']);

        // case 4

        $GLOBALS['excel_edition'] = 'testBlank';
        $GLOBALS['csv_separator'] = '#';

        $this->assertTrue(
            $this->object->exportHeader(),
        );

        $this->assertEquals('#', $GLOBALS['csv_separator']);

        // case 5

        $GLOBALS['what'] = 'notExcel';
        $GLOBALS['csv_terminated'] = '';
        $GLOBALS['csv_separator'] = 'a\\t';

        $this->assertTrue(
            $this->object->exportHeader(),
        );

        $this->assertEquals($GLOBALS['csv_terminated'], "\n");

        $this->assertEquals($GLOBALS['csv_separator'], "a\011");
        // case 6

        $GLOBALS['csv_terminated'] = 'AUTO';

        $this->assertTrue(
            $this->object->exportHeader(),
        );

        $this->assertEquals($GLOBALS['csv_terminated'], "\n");

        // case 7

        $GLOBALS['csv_terminated'] = 'a\\rb\\nc\\t';
        $GLOBALS['csv_separator'] = 'a\\t';

        $this->assertTrue(
            $this->object->exportHeader(),
        );

        $this->assertEquals($GLOBALS['csv_terminated'], "a\015b\012c\011");

        $this->assertEquals($GLOBALS['csv_separator'], "a\011");
    }

    public function testExportFooter(): void
    {
        $this->assertTrue(
            $this->object->exportFooter(),
        );
    }

    public function testExportDBHeader(): void
    {
        $this->assertTrue(
            $this->object->exportDBHeader('testDB'),
        );
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB'),
        );
    }

    public function testExportDBCreate(): void
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database'),
        );
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
        $this->assertFalse($this->object->exportData(
            'test_db',
            'test_table',
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        ob_get_clean();

        // case 2
        $GLOBALS['what'] = 'UT';
        $GLOBALS['UT_null'] = 'customNull';
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['csv_enclosed'] = '';
        $GLOBALS['csv_separator'] = '';

        ob_start();
        $this->assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        $this->assertEquals(
            'idnamedatetimefield;1abcd2011-01-20 02:00:02;2foo2010-01-20 02:00:02;3Abcd2012-01-20 02:00:02;',
            $result,
        );

        // case 3
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = '';

        ob_start();
        $this->assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        $this->assertEquals(
            '"id""name""datetimefield";"1""abcd""2011-01-20 02:00:02";'
            . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";',
            $result,
        );

        // case 4
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['what'] = 'excel';
        $GLOBALS['excel_removeCRLF'] = true;
        $GLOBALS['csv_escaped'] = '"';

        ob_start();
        $this->assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        $this->assertEquals(
            '"id""name""datetimefield";"1""abcd""2011-01-20 02:00:02";'
            . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";',
            $result,
        );

        // case 5
        $GLOBALS['csv_enclosed'] = '"';
        unset($GLOBALS['excel_removeCRLF']);
        $GLOBALS['csv_escaped'] = ';';

        ob_start();
        $this->assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        $this->assertEquals(
            '"id""name""datetimefield";"1""abcd""2011-01-20 02:00:02";'
            . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";',
            $result,
        );

        // case 6
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = ';';
        $GLOBALS['csv_escaped'] = '#';

        ob_start();
        $this->assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        $this->assertEquals(
            '"id""name""datetimefield";"1""abcd""2011-01-20 02:00:02";'
            . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";',
            $result,
        );
    }
}
