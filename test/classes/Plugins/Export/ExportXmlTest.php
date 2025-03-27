<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportXml;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Table;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;

use function array_shift;
use function ob_get_clean;
use function ob_start;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportXml
 * @group medium
 */
class ExportXmlTest extends AbstractTestCase
{
    /** @var ExportXml */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = [];
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['db'] = 'db';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $this->object = new ExportXml();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * @group medium
     */
    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportXml::class, 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportXml::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame('XML', $properties->getText());

        self::assertSame('xml', $properties->getExtension());

        self::assertSame('text/xml', $properties->getMimeType());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame('Format Specific Options', $options->getName());

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('general_opts', $generalOptions->getName());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('structure', $generalOptions->getName());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $generalOptions = array_shift($generalOptionsArray);

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('data', $generalOptions->getName());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(BoolPropertyItem::class, $property);
    }

    /**
     * @group medium
     */
    public function testExportHeader(): void
    {
        $GLOBALS['xml_export_functions'] = 1;
        $GLOBALS['xml_export_contents'] = 1;
        $GLOBALS['output_charset_conversion'] = 1;
        $GLOBALS['charset'] = 'iso-8859-1';
        $GLOBALS['cfg']['Server']['port'] = 80;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['xml_export_tables'] = 1;
        $GLOBALS['xml_export_triggers'] = 1;
        $GLOBALS['xml_export_procedures'] = 1;
        $GLOBALS['xml_export_functions'] = 1;
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['db'] = 'd<"b';

        $result = [
            0 => [
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'DEFAULT_CHARACTER_SET_NAME' => 'utf-8',

            ],
            'table' => [
                null,
                '"tbl"',
            ],
        ];
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(3))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls($result, $result, []);

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('d<"b', 'table')
            ->will(
                $this->returnValue(
                    [
                        [
                            'create' => 'crt',
                            'name' => 'trname',
                        ],
                    ]
                )
            );

        $dbi->expects($this->exactly(2))
            ->method('getProceduresOrFunctions')
            ->willReturnOnConsecutiveCalls(
                ['fn'],
                ['pr']
            );

        $dbi->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnOnConsecutiveCalls('fndef', 'prdef');

        $dbi->expects($this->once())
            ->method('getTable')
            ->will($this->returnValue(new Table('table', 'd<"b', $dbi)));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['tables'] = [];
        $GLOBALS['table'] = 'table';

        ob_start();
        self::assertTrue($this->object->exportHeader());
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('&lt;pma_xml_export version=&quot;1.0&quot; xmlns:pma=&quot;' .
        'https://www.phpmyadmin.net/some_doc_url/&quot;&gt;', $result);

        self::assertStringContainsString('&lt;pma:structure_schemas&gt;' . "\n" .
        '        &lt;pma:database name=&quot;d&amp;lt;&amp;quot;b&quot; collat' .
        'ion=&quot;utf8_general_ci&quot; charset=&quot;utf-8&quot;&gt;' . "\n" .
        '            &lt;pma:table name=&quot;table&quot;&gt;' . "\n" .
        '                &amp;quot;tbl&amp;quot;;' . "\n" .
        '            &lt;/pma:table&gt;' . "\n" .
        '            &lt;pma:trigger name=&quot;trname&quot;&gt;' . "\n" .
        '                ' . "\n" .
        '            &lt;/pma:trigger&gt;' . "\n" .
        '            &lt;pma:function name=&quot;fn&quot;&gt;' . "\n" .
        '                fndef' . "\n" .
        '            &lt;/pma:function&gt;' . "\n" .
        '            &lt;pma:procedure name=&quot;pr&quot;&gt;' . "\n" .
        '                prdef' . "\n" .
        '            &lt;/pma:procedure&gt;' . "\n" .
        '        &lt;/pma:database&gt;' . "\n" .
        '    &lt;/pma:structure_schemas&gt;', $result);

        // case 2 with isView as true and false

        unset($GLOBALS['xml_export_contents']);
        unset($GLOBALS['xml_export_views']);
        unset($GLOBALS['xml_export_tables']);
        unset($GLOBALS['xml_export_functions']);
        unset($GLOBALS['xml_export_procedures']);
        $GLOBALS['output_charset_conversion'] = 0;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result_1 = [
            [
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'DEFAULT_CHARACTER_SET_NAME' => 'utf-8',

            ],
        ];
        $result_2 = [
            't1' => [
                null,
                '"tbl"',
            ],
        ];

        $result_3 = [
            't2' => [
                null,
                '"tbl"',
            ],
        ];

        $dbi->expects($this->exactly(5))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls($result_1, $result_2, ['table'], $result_3, []);

        $dbi->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue(new Table('table', 'd<"b', $dbi)));

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['tables'] = [
            't1',
            't2',
        ];

        ob_start();
        self::assertTrue($this->object->exportHeader());
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('&lt;pma:structure_schemas&gt;' . "\n" .
        '        &lt;pma:database name=&quot;d&amp;lt;&amp;quot;b&quot; collat' .
        'ion=&quot;utf8_general_ci&quot; charset=&quot;utf-8&quot;&gt;' . "\n" .
        '        &lt;/pma:database&gt;' . "\n" .
        '    &lt;/pma:structure_schemas&gt;', $result);
    }

    public function testExportFooter(): void
    {
        $this->expectOutputString('&lt;/pma_xml_export&gt;');
        self::assertTrue($this->object->exportFooter());
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['xml_export_contents'] = true;

        ob_start();
        self::assertTrue($this->object->exportDBHeader('&db'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('&lt;database name=&quot;&amp;amp;db&quot;&gt;', $result);

        $GLOBALS['xml_export_contents'] = false;

        self::assertTrue($this->object->exportDBHeader('&db'));
    }

    public function testExportDBFooter(): void
    {
        $GLOBALS['xml_export_contents'] = true;

        ob_start();
        self::assertTrue($this->object->exportDBFooter('&db'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('&lt;/database&gt;', $result);

        $GLOBALS['xml_export_contents'] = false;

        self::assertTrue($this->object->exportDBFooter('&db'));
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue($this->object->exportDBCreate('testDB', 'database'));
    }

    public function testExportData(): void
    {
        $GLOBALS['xml_export_contents'] = true;
        $GLOBALS['asfile'] = true;
        $GLOBALS['output_charset_conversion'] = false;

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame('        <!-- Table test_table -->' . "\n"
        . '        <table name="test_table">' . "\n"
        . '            <column name="id">1</column>' . "\n"
        . '            <column name="name">abcd</column>' . "\n"
        . '            <column name="datetimefield">2011-01-20 02:00:02</column>' . "\n"
        . '        </table>' . "\n"
        . '        <table name="test_table">' . "\n"
        . '            <column name="id">2</column>' . "\n"
        . '            <column name="name">foo</column>' . "\n"
        . '            <column name="datetimefield">2010-01-20 02:00:02</column>' . "\n"
        . '        </table>' . "\n"
        . '        <table name="test_table">' . "\n"
        . '            <column name="id">3</column>' . "\n"
        . '            <column name="name">Abcd</column>' . "\n"
        . '            <column name="datetimefield">2012-01-20 02:00:02</column>' . "\n"
        . '        </table>' . "\n", $result);
    }
}
