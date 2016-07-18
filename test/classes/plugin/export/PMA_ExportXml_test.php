<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ExportXml class
 *
 * @package PhpMyAdmin-test
 */
$GLOBALS['db'] = 'db';
require_once 'libraries/plugins/export/ExportXml.class.php';
require_once 'libraries/DatabaseInterface.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/export.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';
require_once 'export.php';
/**
 * tests for ExportXml class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class PMA_ExportXml_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        if (!defined("PMA_DRIZZLE")) {
            define("PMA_DRIZZLE", false);
        }

        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = array();
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['relation'] = true;
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
     * Test for ExportXml::setProperties
     *
     * @return void
     * @group medium
     */
    public function testSetProperties()
    {
        $restoreDrizzle = 'PMANORESTORE';

        if (PMA_DRIZZLE) {
            if (!PMA_HAS_RUNKIT) {
                $this->markTestSkipped(
                    "Cannot redefine constant. Missing runkit extension"
                );
            } else {
                $restoreDrizzle = PMA_DRIZZLE;
                runkit_constant_redefine('PMA_DRIZZLE', false);
            }
        }

        $method = new ReflectionMethod('ExportXml', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('ExportXml', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'ExportPluginProperties',
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
            'OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'HiddenPropertyItem',
            $property
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'BoolPropertyItem',
            $property
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'BoolPropertyItem',
            $property
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'BoolPropertyItem',
            $property
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'BoolPropertyItem',
            $property
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'BoolPropertyItem',
            $property
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'data',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'BoolPropertyItem',
            $property
        );

        if ($restoreDrizzle !== "PMANORESTORE") {
            runkit_constant_redefine('PMA_DRIZZLE', $restoreDrizzle);
        }
    }

    /**
     * Test for ExportXml::exportHeader
     *
     * @return void
     * @group medium
     */
    public function testExportHeaderWithoutDrizzle()
    {
        if (!defined("PMA_MYSQL_STR_VERSION")) {
            define("PMA_MYSQL_STR_VERSION", "5.0.0");
        }

        $restoreDrizzle = 'PMANORESTORE';

        if (PMA_DRIZZLE) {
            if (!PMA_HAS_RUNKIT) {
                $this->markTestSkipped(
                    "Cannot redefine constant. Missing runkit extension"
                );
            } else {
                $restoreDrizzle = PMA_DRIZZLE;
                runkit_constant_redefine('PMA_DRIZZLE', false);
            }
        }

        $GLOBALS['xml_export_functions'] = 1;
        $GLOBALS['xml_export_contents'] = 1;
        $GLOBALS['output_charset_conversion'] = 1;
        $GLOBALS['charset_of_file'] = 'iso-8859-1';
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
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('fetchResult')
            ->with(
                'SELECT `DEFAULT_CHARACTER_SET_NAME`, `DEFAULT_COLLATION_NAME`'
                . ' FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME`'
                . ' = \'d<"b\' LIMIT 1'
            )
            ->will($this->returnValue($result));

        $dbi->expects($this->at(1))
            ->method('fetchResult')
            ->with(
                'SHOW CREATE TABLE `d<"b`.`table`',
                0
            )
            ->will($this->returnValue($result));

        // isView
        $dbi->expects($this->at(2))
            ->method('fetchResult')
            ->will($this->returnValue(false));

        $dbi->expects($this->at(3))
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

        $dbi->expects($this->at(4))
            ->method('getProceduresOrFunctions')
            ->with('d<"b', 'FUNCTION')
            ->will(
                $this->returnValue(
                    array(
                        'fn'
                    )
                )
            );

        $dbi->expects($this->at(5))
            ->method('getDefinition')
            ->with('d<"b', 'FUNCTION', 'fn')
            ->will(
                $this->returnValue(
                    'fndef'
                )
            );

        $dbi->expects($this->at(6))
            ->method('getProceduresOrFunctions')
            ->with('d<"b', 'PROCEDURE')
            ->will(
                $this->returnValue(
                    array(
                        'pr'
                    )
                )
            );

        $dbi->expects($this->at(7))
            ->method('getDefinition')
            ->with('d<"b', 'PROCEDURE', 'pr')
            ->will(
                $this->returnValue(
                    'prdef'
                )
            );

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

        $result = array(
            array(
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'DEFAULT_CHARACTER_SET_NAME' => 'utf-8',

            )
        );
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('fetchResult')
            ->with(
                'SELECT `DEFAULT_CHARACTER_SET_NAME`, `DEFAULT_COLLATION_NAME`'
                . ' FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME`'
                . ' = \'d<"b\' LIMIT 1'
            )
            ->will($this->returnValue($result));

        $result = array(
            't1' => array(null, '"tbl"')
        );

        $dbi->expects($this->at(1))
            ->method('fetchResult')
            ->with(
                'SHOW CREATE TABLE `d<"b`.`t1`',
                0
            )
            ->will($this->returnValue($result));

        // isView
        $dbi->expects($this->at(2))
            ->method('fetchResult')
            ->will($this->returnValue(true));

        $result = array(
            't2' => array(null, '"tbl"')
        );

        $dbi->expects($this->at(3))
            ->method('fetchResult')
            ->with(
                'SHOW CREATE TABLE `d<"b`.`t2`',
                0
            )
            ->will($this->returnValue($result));

        // isView
        $dbi->expects($this->at(4))
            ->method('fetchResult')
            ->will($this->returnValue(false));

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

        if ($restoreDrizzle !== "PMANORESTORE") {
            runkit_constant_redefine('PMA_DRIZZLE', $restoreDrizzle);
        }
    }

    /**
     * Test for ExportXml::exportHeader
     *
     * @return void
     */
    public function testExportHeaderWithDrizzle()
    {
        $restoreDrizzle = 'PMANORESTORE';

        if (!PMA_DRIZZLE) {
            if (!PMA_HAS_RUNKIT) {
                $this->markTestSkipped(
                    "Cannot redefine constant. Missing runkit extension"
                );
            } else {
                $restoreDrizzle = PMA_DRIZZLE;
                runkit_constant_redefine('PMA_DRIZZLE', true);
            }
        }

        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['xml_export_triggers'] = true;
        $GLOBALS['cfg']['Server']['port'] = 80;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['db'] = 'd<b';

        $result = array(
            0 => array(
                'DEFAULT_COLLATION_NAME' => 'utf8_general_ci',
                'DEFAULT_CHARACTER_SET_NAME' => 'utf-8',

            ),
            'table' => array(null, '"tbl"')
        );
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('fetchResult')
            ->with(
                "SELECT
                        'utf8' AS DEFAULT_CHARACTER_SET_NAME,
                        DEFAULT_COLLATION_NAME
                    FROM data_dictionary.SCHEMAS
                    WHERE SCHEMA_NAME = 'd<b'"
            )
            ->will($this->returnValue($result));

        $dbi->expects($this->at(1))
            ->method('fetchResult')
            ->with(
                'SHOW CREATE TABLE `d<b`.`table`',
                0
            )
            ->will($this->returnValue($result));

        // isView
        $dbi->expects($this->at(2))
            ->method('fetchResult')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['tables'] = array();
        $GLOBALS['table'] = 'table';

        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        if ($restoreDrizzle !== "PMANORESTORE") {
            runkit_constant_redefine('PMA_DRIZZLE', $restoreDrizzle);
        }
    }

    /**
     * Test for ExportXml::exportFooter
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
     * Test for ExportXml::exportDBHeader
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
     * Test for ExportXml::exportDBFooter
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
     * Test for ExportXml::exportDBCreate
     *
     * @return void
     */
    public function testExportDBCreate()
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB')
        );
    }

    /**
     * Test for ExportXml::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $GLOBALS['xml_export_contents'] = true;

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', null, PMA_DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(3));

        $dbi->expects($this->at(2))
            ->method('fieldName')
            ->will($this->returnValue('fName1'));

        $dbi->expects($this->at(3))
            ->method('fieldName')
            ->will($this->returnValue('fNa"me2'));

        $dbi->expects($this->at(4))
            ->method('fieldName')
            ->will($this->returnValue('fNa\\me3'));

        $dbi->expects($this->at(5))
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
            "&lt;!-- Table ta&amp;lt;ble --&gt;",
            $result
        );

        $this->assertContains(
            "&lt;table name=&quot;ta&amp;lt;ble&quot;&gt;",
            $result
        );

        $this->assertContains(
            "&lt;column name=&quot;fName1&quot;&gt;NULL&lt;/column&gt;",
            $result
        );

        $this->assertContains(
            "&lt;column name=&quot;fNa&amp;quot;me2&quot;&gt;&amp;lt;a&amp;gt;" .
            "&lt;/column&gt;",
            $result
        );

        $this->assertContains(
            "&lt;column name=&quot;fName3&quot;&gt;NULL&lt;/column&gt;",
            $result
        );

        $this->assertContains(
            "&lt;/table&gt;",
            $result
        );
    }
}
?>
