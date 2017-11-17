<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Export\ExportXml class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportXml;
use PhpMyAdmin\Table;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * tests for PhpMyAdmin\Plugins\Export\ExportXml class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportXmlTest extends PmaTestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = array();
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['relation'] = true;
        $GLOBALS['db'] = 'db';
        $this->object = new ExportXml();
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportXml::setProperties
     *
     * @return void
     * @group medium
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportXml', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PhpMyAdmin\Plugins\Export\ExportXml', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'XML',
            $properties->getText()
        );

        $this->assertEquals(
            'xml',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/xml',
            $properties->getMimeType()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem',
            $property
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\BoolPropertyItem',
            $property
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\BoolPropertyItem',
            $property
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\BoolPropertyItem',
            $property
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\BoolPropertyItem',
            $property
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\BoolPropertyItem',
            $property
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'data',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\BoolPropertyItem',
            $property
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportXml::exportHeader
     *
     * @return void
     * @group medium
     */
    public function testExportHeader()
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

        $result = array(
            0 => array(
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'DEFAULT_CHARACTER_SET_NAME' => 'utf-8',

            ),
            'table' => array(null, '"tbl"')
        );
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(3))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                $result,
                $result,
                false
            );

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('d<"b', 'table')
            ->will(
                $this->returnValue(
                    array(
                        array(
                            'create' => 'crt',
                            'name' => 'trname'
                        )
                    )
                )
            );

        $dbi->expects($this->exactly(2))
            ->method('getProceduresOrFunctions')
            ->willReturnOnConsecutiveCalls(
                array(
                    'fn'
                ),
                array(
                    'pr'
                )
            );

        $dbi->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnOnConsecutiveCalls(
                'fndef',
                'prdef'
            );

        $dbi->expects($this->once())
            ->method('getTable')
            ->will($this->returnValue(new Table('table', 'd<"b', $dbi)));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['tables'] = array();
        $GLOBALS['table'] = 'table';

        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        $this->assertContains(
            '&lt;pma_xml_export version=&quot;1.0&quot; xmlns:pma=&quot;' .
            'https://www.phpmyadmin.net/some_doc_url/&quot;&gt;',
            $result
        );

        $this->assertContains(
            '&lt;pma:structure_schemas&gt;' . "\n" .
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
            '    &lt;/pma:structure_schemas&gt;',
            $result
        );

        // case 2 with isView as true and false

        unset($GLOBALS['xml_export_contents']);
        unset($GLOBALS['xml_export_views']);
        unset($GLOBALS['xml_export_tables']);
        unset($GLOBALS['xml_export_functions']);
        unset($GLOBALS['xml_export_procedures']);
        $GLOBALS['output_charset_conversion'] = 0;

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $result_1 = array(
            array(
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'DEFAULT_CHARACTER_SET_NAME' => 'utf-8',

            )
        );
        $result_2 = array(
            't1' => array(null, '"tbl"')
        );

        $result_3 = array(
            't2' => array(null, '"tbl"')
        );

        $dbi->expects($this->exactly(5))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                $result_1,
                $result_2,
                true,
                $result_3,
                false
            );

        $dbi->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue(new Table('table', 'd<"b', $dbi)));

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['tables'] = array('t1', 't2');

        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        //echo $result; die;
        $this->assertContains(
            '&lt;pma:structure_schemas&gt;' . "\n" .
            '        &lt;pma:database name=&quot;d&amp;lt;&amp;quot;b&quot; collat' .
            'ion=&quot;utf8_general_ci&quot; charset=&quot;utf-8&quot;&gt;' . "\n" .
            '        &lt;/pma:database&gt;' . "\n" .
            '    &lt;/pma:structure_schemas&gt;',
            $result
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportXml::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $this->expectOutputString(
            '&lt;/pma_xml_export&gt;'
        );
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportXml::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $GLOBALS['xml_export_contents'] = true;

        ob_start();
        $this->assertTrue(
            $this->object->exportDBHeader('&db')
        );
        $result = ob_get_clean();

        $this->assertContains(
            '&lt;database name=&quot;&amp;amp;db&quot;&gt;',
            $result
        );

        $GLOBALS['xml_export_contents'] = false;

        $this->assertTrue(
            $this->object->exportDBHeader('&db')
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportXml::exportDBFooter
     *
     * @return void
     */
    public function testExportDBFooter()
    {
        $GLOBALS['xml_export_contents'] = true;

        ob_start();
        $this->assertTrue(
            $this->object->exportDBFooter('&db')
        );
        $result = ob_get_clean();

        $this->assertContains(
            '&lt;/database&gt;',
            $result
        );

        $GLOBALS['xml_export_contents'] = false;

        $this->assertTrue(
            $this->object->exportDBFooter('&db')
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportXml::exportDBCreate
     *
     * @return void
     */
    public function testExportDBCreate()
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportXml::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $GLOBALS['xml_export_contents'] = true;
        $GLOBALS['asfile'] = true;
        $GLOBALS['output_charset_conversion'] = false;

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $_table = $this->getMockBuilder('PhpMyAdmin\Table')
            ->disableOriginalConstructor()
            ->getMock();
        $_table->expects($this->once())
            ->method('isMerge')
            ->will($this->returnValue(false));

        $dbi->expects($this->any())
            ->method('getTable')
            ->will($this->returnValue($_table));

        $dbi->expects($this->once())
            ->method('getTable')
            ->will($this->returnValue($_table));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(3));

        $dbi->expects($this->at(3))
            ->method('fieldName')
            ->will($this->returnValue('fName1'));

        $dbi->expects($this->at(4))
            ->method('fieldName')
            ->will($this->returnValue('fNa"me2'));

        $dbi->expects($this->at(5))
            ->method('fieldName')
            ->will($this->returnValue('fNa\\me3'));

        $dbi->expects($this->at(6))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array(null, '<a>')));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'db', 'ta<ble', "\n", "example.com", "SELECT"
            )
        );
        $result = ob_get_clean();

        $this->assertContains(
            "<!-- Table ta&lt;ble -->",
            $result
        );

        $this->assertContains(
            "<table name=\"ta&lt;ble\">",
            $result
        );

        $this->assertContains(
            "<column name=\"fName1\">NULL</column>",
            $result
        );

        $this->assertContains(
            "<column name=\"fNa&quot;me2\">&lt;a&gt;" .
            "</column>",
            $result
        );

        $this->assertContains(
            "<column name=\"fName3\">NULL</column>",
            $result
        );

        $this->assertContains(
            "</table>",
            $result
        );
    }
}
