<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
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
        $relation = new Relation($dbi);
        $this->object = new ExportExcel($relation, new Export($dbi), new Transformations($dbi, $relation));
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
        self::assertTrue(
            $this->object->exportHeader(),
        );

        // case 2
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['excel_edition' => 'mac_excel2003']);

        $this->object->setExportOptions($request, []);

        self::assertTrue(
            $this->object->exportHeader(),
        );

        // case 3
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['excel_edition' => 'mac_excel2008']);

        $this->object->setExportOptions($request, []);

        self::assertTrue(
            $this->object->exportHeader(),
        );
    }

    public function testExportData(): void
    {
        // case 1
        Export::$outputKanjiConversion = false;
        Export::$outputCharsetConversion = false;
        Export::$bufferNeeded = false;
        Export::$asFile = true;
        Export::$saveOnServer = true;
        Export::$fileHandle = null;

        ob_start();
        self::assertFalse($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        ob_get_clean();

        // case 2
        Export::$outputKanjiConversion = false;
        Export::$outputCharsetConversion = false;
        Export::$bufferNeeded = false;
        Export::$asFile = true;
        Export::$saveOnServer = false;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['excel_columns' => 'On', 'excel_terminated' => ';']);

        $this->object->setExportOptions($request, []);

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
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['excel_columns' => 'On', 'excel_enclosed' => '"', 'excel_terminated' => ';']);

        $this->object->setExportOptions($request, []);

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
