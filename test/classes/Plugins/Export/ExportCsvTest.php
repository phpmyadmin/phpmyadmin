<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Plugins\Export\ExportCsv;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;

use function array_shift;
use function ob_get_clean;
use function ob_start;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportCsv
 * @group medium
 */
class ExportCsvTest extends AbstractTestCase
{
    /** @var ExportCsv */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = '';
        $GLOBALS['text_dir'] = '';
        $GLOBALS['PMA_PHP_SELF'] = '';
        $this->object = new ExportCsv();
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
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportCsv::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertEquals('CSV', $properties->getText());

        self::assertEquals('csv', $properties->getExtension());

        self::assertEquals('text/comma-separated-values', $properties->getMimeType());

        self::assertEquals('Options', $properties->getOptionsText());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertEquals('Format Specific Options', $options->getName());

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertEquals('general_opts', $generalOptions->getName());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertEquals('separator', $property->getName());

        self::assertEquals('Columns separated with:', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertEquals('enclosed', $property->getName());

        self::assertEquals('Columns enclosed with:', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertEquals('escaped', $property->getName());

        self::assertEquals('Columns escaped with:', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertEquals('terminated', $property->getName());

        self::assertEquals('Lines terminated with:', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertEquals('null', $property->getName());

        self::assertEquals('Replace NULL with:', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertEquals('removeCRLF', $property->getName());

        self::assertEquals('Remove carriage return/line feed characters within columns', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertEquals('columns', $property->getName());

        self::assertEquals('Put columns names in the first row', $property->getText());

        $property = array_shift($generalProperties);

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertEquals('structure_or_data', $property->getName());
    }

    public function testExportHeader(): void
    {
        // case 1

        $GLOBALS['what'] = 'excel';
        $GLOBALS['excel_edition'] = 'win';
        $GLOBALS['excel_columns'] = true;

        self::assertTrue($this->object->exportHeader());

        self::assertEquals("\015\012", $GLOBALS['csv_terminated']);

        self::assertEquals(';', $GLOBALS['csv_separator']);

        self::assertEquals('"', $GLOBALS['csv_enclosed']);

        self::assertEquals('"', $GLOBALS['csv_escaped']);

        self::assertEquals(true, $GLOBALS['csv_columns']);

        // case 2

        $GLOBALS['excel_edition'] = 'mac_excel2003';
        unset($GLOBALS['excel_columns']);
        $GLOBALS['csv_columns'] = false;

        self::assertTrue($this->object->exportHeader());

        self::assertEquals("\015\012", $GLOBALS['csv_terminated']);

        self::assertEquals(';', $GLOBALS['csv_separator']);

        self::assertEquals('"', $GLOBALS['csv_enclosed']);

        self::assertEquals('"', $GLOBALS['csv_escaped']);

        self::assertEquals(false, $GLOBALS['csv_columns']);

        // case 3

        $GLOBALS['excel_edition'] = 'mac_excel2008';

        self::assertTrue($this->object->exportHeader());

        self::assertEquals("\015\012", $GLOBALS['csv_terminated']);

        self::assertEquals(',', $GLOBALS['csv_separator']);

        self::assertEquals('"', $GLOBALS['csv_enclosed']);

        self::assertEquals('"', $GLOBALS['csv_escaped']);

        self::assertEquals(false, $GLOBALS['csv_columns']);

        // case 4

        $GLOBALS['excel_edition'] = 'testBlank';
        $GLOBALS['csv_separator'] = '#';

        self::assertTrue($this->object->exportHeader());

        self::assertEquals('#', $GLOBALS['csv_separator']);

        // case 5

        $GLOBALS['what'] = 'notExcel';
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['csv_terminated'] = '';
        $GLOBALS['csv_separator'] = 'a\\t';

        self::assertTrue($this->object->exportHeader());

        self::assertEquals($GLOBALS['csv_terminated'], "\n");

        self::assertEquals($GLOBALS['csv_separator'], "a\011");
        // case 6

        $GLOBALS['csv_terminated'] = 'AUTO';

        self::assertTrue($this->object->exportHeader());

        self::assertEquals($GLOBALS['csv_terminated'], "\n");

        // case 7

        $GLOBALS['csv_terminated'] = 'a\\rb\\nc\\t';
        $GLOBALS['csv_separator'] = 'a\\t';

        self::assertTrue($this->object->exportHeader());

        self::assertEquals($GLOBALS['csv_terminated'], "a\015b\012c\011");

        self::assertEquals($GLOBALS['csv_separator'], "a\011");
    }

    public function testExportFooter(): void
    {
        self::assertTrue($this->object->exportFooter());
    }

    public function testExportDBHeader(): void
    {
        self::assertTrue($this->object->exportDBHeader('testDB'));
    }

    public function testExportDBFooter(): void
    {
        self::assertTrue($this->object->exportDBFooter('testDB'));
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue($this->object->exportDBCreate('testDB', 'database'));
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
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
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
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        self::assertEquals(
            'idnamedatetimefiel;1abcd2011-01-20 02:00:02;2foo2010-01-20 02:00:02;3Abcd2012-01-20 02:00:02;',
            $result
        );

        // case 3
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = '';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        self::assertEquals('"id""name""datetimefield;"1""abcd""2011-01-20 02:00:02";'
        . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";', $result);

        // case 4
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['what'] = 'excel';
        $GLOBALS['excel_removeCRLF'] = true;
        $GLOBALS['csv_escaped'] = '"';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        self::assertEquals('"id""name""datetimefield;"1""abcd""2011-01-20 02:00:02";'
        . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";', $result);

        // case 5
        $GLOBALS['csv_enclosed'] = '"';
        unset($GLOBALS['excel_removeCRLF']);
        $GLOBALS['csv_escaped'] = ';';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        self::assertEquals('"id""name""datetimefield;"1""abcd""2011-01-20 02:00:02";'
        . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";', $result);

        // case 6
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = ';';
        $GLOBALS['csv_escaped'] = '#';

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        self::assertEquals('"id""name""datetimefield;"1""abcd""2011-01-20 02:00:02";'
        . '"2""foo""2010-01-20 02:00:02";"3""Abcd""2012-01-20 02:00:02";', $result);
    }
}
