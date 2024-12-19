<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Plugins\Export\ExportCsv;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
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

#[CoversClass(ExportCsv::class)]
#[Medium]
class ExportCsvTest extends AbstractTestCase
{
    protected ExportCsv $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        Current::$database = '';
        Current::$table = '';
        $GLOBALS['lang'] = '';
        $GLOBALS['csv_enclosed'] = null;
        $GLOBALS['csv_separator'] = null;
        $GLOBALS['save_filename'] = null;

        $this->object = new ExportCsv(
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
        $method = new ReflectionMethod(ExportCsv::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportCsv::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'CSV',
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
            'separator',
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
            'enclosed',
            $property->getName(),
        );

        self::assertSame(
            'Columns enclosed with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'escaped',
            $property->getName(),
        );

        self::assertSame(
            'Columns escaped with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'terminated',
            $property->getName(),
        );

        self::assertSame(
            'Lines terminated with:',
            $property->getText(),
        );

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

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertSame(
            'structure_or_data',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        // case 1

        $GLOBALS['csv_terminated'] = '';
        $GLOBALS['csv_separator'] = 'a\\t';

        self::assertTrue(
            $this->object->exportHeader(),
        );

        self::assertSame($GLOBALS['csv_terminated'], "\n");

        self::assertSame($GLOBALS['csv_separator'], "a\011");
        // case 2

        $GLOBALS['csv_terminated'] = 'AUTO';

        self::assertTrue(
            $this->object->exportHeader(),
        );

        self::assertSame($GLOBALS['csv_terminated'], "\n");

        // case 3

        $GLOBALS['csv_terminated'] = 'a\\rb\\nc\\t';
        $GLOBALS['csv_separator'] = 'a\\t';

        self::assertTrue(
            $this->object->exportHeader(),
        );

        self::assertSame($GLOBALS['csv_terminated'], "a\015b\012c\011");

        self::assertSame($GLOBALS['csv_separator'], "a\011");
    }

    public function testExportFooter(): void
    {
        self::assertTrue(
            $this->object->exportFooter(),
        );
    }

    public function testExportDBHeader(): void
    {
        self::assertTrue(
            $this->object->exportDBHeader('testDB'),
        );
    }

    public function testExportDBFooter(): void
    {
        self::assertTrue(
            $this->object->exportDBFooter('testDB'),
        );
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue(
            $this->object->exportDBCreate('testDB'),
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
        self::assertFalse($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        ob_get_clean();

        // case 2
        $GLOBALS['csv_null'] = 'customNull';
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
        $GLOBALS['csv_removeCRLF'] = true;
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
        $GLOBALS['csv_enclosed'] = '"';
        unset($GLOBALS['csv_removeCRLF']);
        $GLOBALS['csv_escaped'] = ';';

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
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = '#';

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
