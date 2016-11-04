<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\export\ExportOdt class
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\plugins\export\ExportOdt;

//ExportOdt required because of initialisation inside
require_once 'libraries/plugins/export/ExportOdt.php';
require_once 'libraries/export.lib.php';
require_once 'libraries/config.default.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/transformations.lib.php';
require_once 'export.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\export\ExportOdt class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportOdtTest extends PMATestCase
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
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['plugin_param'] = array();
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['relation'] = true;
        $this->object = new ExportOdt();
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
     * Test for PMA\libraries\plugins\export\ExportOdt::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $GLOBALS['plugin_param']['export_type'] = '';
        $GLOBALS['plugin_param']['single_table'] = false;
        $GLOBALS['cfgRelation']['mimework'] = true;

        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportOdt', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PMA\libraries\plugins\export\ExportOdt', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PMA\libraries\properties\plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'OpenDocument Text',
            $properties->getText()
        );

        $this->assertEquals(
            'odt',
            $properties->getExtension()
        );

        $this->assertEquals(
            'application/vnd.oasis.opendocument.text',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
        );

        $this->assertTrue(
            $properties->getForceFile()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $this->assertEquals(
            "Dump table",
            $generalOptions->getText()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\RadioPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );

        $this->assertEquals(
            array(
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data')
            ),
            $property->getValues()
        );

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Object creation options',
            $generalOptions->getText()
        );

        $this->assertEquals(
            'data',
            $generalOptions->getForce()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'relation',
            $property->getName()
        );

        $this->assertEquals(
            'Display foreign key relationships',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'comments',
            $property->getName()
        );

        $this->assertEquals(
            'Display comments',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'mime',
            $property->getName()
        );

        $this->assertEquals(
            'Display MIME types',
            $property->getText()
        );

        // hide structure
        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'data',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Data dump options',
            $generalOptions->getText()
        );

        $this->assertEquals(
            'structure',
            $generalOptions->getForce()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'columns',
            $property->getName()
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'null',
            $property->getName()
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText()
        );

        // case 2
        $GLOBALS['plugin_param']['export_type'] = 'table';
        $GLOBALS['plugin_param']['single_table'] = false;

        $method->invoke($this->object, null);
        $properties = $attrProperties->getValue($this->object);

        $generalOptionsArray = $options->getProperties();

        $this->assertCount(
            3,
            $generalOptionsArray
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $GLOBALS['OpenDocumentNS'] = "ODNS";

        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertContains(
            "<office:document-content ODNSoffice:version",
            $GLOBALS['odt_buffer']
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $GLOBALS['odt_buffer'] = 'header';

        $this->expectOutputRegex('/^504b.*636f6e74656e742e786d6c/');
        $this->setOutputCallback('bin2hex');

        $this->assertTrue(
            $this->object->exportFooter()
        );

        $this->assertContains(
            "header",
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            "</office:text></office:body></office:document-content>",
            $GLOBALS['odt_buffer']
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $GLOBALS['odt_buffer'] = 'header';

        $this->assertTrue(
            $this->object->exportDBHeader('d&b')
        );

        $this->assertContains(
            "header",
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            "Database d&amp;b</text:h>",
            $GLOBALS['odt_buffer']
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::exportDBFooter
     *
     * @return void
     */
    public function testExportDBFooter()
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::exportDBCreate
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
     * Test for PMA\libraries\plugins\export\ExportOdt::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $flags = array();
        $a = new StdClass;
        $flags[] = $a;

        $a = new StdClass;
        $a->blob = true;
        $flags[] = $a;

        $a = new StdClass;
        $a->numeric = true;
        $a->type = 'real';
        $a->blob = false;
        $flags[] = $a;

        $a = new StdClass;
        $a->type = "timestamp";
        $a->blob = false;
        $a->numeric = false;
        $flags[] = $a;

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->at(4))
            ->method('fieldFlags')
            ->will($this->returnValue('BINARYTEST'));

        $dbi->expects($this->at(5))
            ->method('fieldFlags')
            ->will($this->returnValue('binary'));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(4));

        $dbi->expects($this->at(7))
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    array(
                        null, 'a<b', 'a>b', 'a&b'
                    )
                )
            );

        $dbi->expects($this->at(8))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = "&";

        $this->assertTrue(
            $this->object->exportData(
                'db', 'ta<ble', "\n", "example.com", "SELECT"
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Dumping data for table ta&lt;ble</text:h>' .
            '<table:table table:name="ta&lt;ble_structure"><table:table-column ' .
            'table:number-columns-repeated="4"/><table:table-row>' .
            '<table:table-cell office:value-type="string"><text:p>&amp;</text:p>' .
            '</table:table-cell><table:table-cell office:value-type="string">' .
            '<text:p></text:p></table:table-cell><table:table-cell ' .
            'office:value-type="float" office:value="a>b" ><text:p>a&gt;b</text:p>' .
            '</table:table-cell><table:table-cell office:value-type="string">' .
            '<text:p>a&amp;b</text:p></table:table-cell></table:table-row>' .
            '</table:table>',
            $GLOBALS['odt_buffer']
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::exportData
     *
     * @return void
     */
    public function testExportDataWithFieldNames()
    {
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $flags = array();

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->any())
            ->method('fieldFlags')
            ->will($this->returnValue('BINARYTEST'));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(2));

        $dbi->expects($this->at(5))
            ->method('fieldName')
            ->will($this->returnValue('fna\"me'));

        $dbi->expects($this->at(6))
            ->method('fieldName')
            ->will($this->returnValue('fnam/<e2'));

        $dbi->expects($this->at(7))
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    null
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = "&";
        $GLOBALS['foo_columns'] = true;

        $this->assertTrue(
            $this->object->exportData(
                'db', 'table', "\n", "example.com", "SELECT"
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:' .
            'is-list-header="true">Dumping data for table table</text:h><table:' .
            'table table:name="table_structure"><table:table-column table:number-' .
            'columns-repeated="2"/><table:table-row><table:table-cell office:' .
            'value-type="string"><text:p>fna&quot;me</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p>fnam/&lt;e2' .
            '</text:p></table:table-cell></table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );

        // with no row count
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $flags = array();

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(0));

        $dbi->expects($this->once())
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    null
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['mediawiki_caption'] = true;
        $GLOBALS['mediawiki_headers'] = true;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = "&";
        $GLOBALS['odt_buffer'] = '';

        $this->assertTrue(
            $this->object->exportData(
                'db', 'table', "\n", "example.com", "SELECT"
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Dumping data for table table</text:h>' .
            '<table:table table:name="table_structure"><table:table-column ' .
            'table:number-columns-repeated="0"/><table:table-row>' .
            '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::getTableDefStandIn
     *
     * @return void
     */
    public function testGetTableDefStandIn()
    {
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('db', 'v&w')
            ->will($this->returnValue(array(1, 2)));

        $GLOBALS['dbi'] = $dbi;

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\export\ExportOdt')
            ->disableOriginalConstructor()
            ->setMethods(array('formatOneColumnDefinition'))
            ->getMock();

        $this->object->expects($this->at(0))
            ->method('formatOneColumnDefinition')
            ->with(1)
            ->will($this->returnValue('c1'));

        $this->object->expects($this->at(1))
            ->method('formatOneColumnDefinition')
            ->with(2)
            ->will($this->returnValue('c2'));

        $this->assertTrue(
            $this->object->getTableDefStandIn('db', 'v&w', '#')
        );

        $this->assertContains(
            '<table:table table:name="v&amp;w_data">',
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            '</table:table-row>c1</table:table-row>c2</table:table-row>' .
            '</table:table>',
            $GLOBALS['odt_buffer']
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::getTableDef
     *
     * @return void
     */
    public function testGetTableDef()
    {
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\export\ExportOdt')
            ->setMethods(array('formatOneColumnDefinition'))
            ->getMock();

        // case 1

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                array(),
                array(
                    'fieldname' => array(
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<'
                    )
                )
            );

        $columns = array(
            'Field' => 'fieldname'
        );
        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue(array($columns)));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    array(
                        'comment' => array('fieldname' => 'testComment')
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;

        $this->object->expects($this->exactly(2))
            ->method('formatOneColumnDefinition')
            ->with(array('Field' => 'fieldname'))
            ->will($this->returnValue(1));

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col'
        );
        $GLOBALS['controllink'] = null;
        $this->assertTrue(
            $this->object->getTableDef(
                'database',
                '',
                "\n",
                "example.com",
                true,
                true,
                true
            )
        );

        $this->assertContains(
            '<table:table table:name="_structure"><table:table-column ' .
            'table:number-columns-repeated="6"/>',
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            '<table:table-cell office:value-type="string"><text:p>Comments' .
            '</text:p></table:table-cell>',
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            '<table:table-cell office:value-type="string"><text:p>MIME type' .
            '</text:p></table:table-cell>',
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            '</table:table-row>1<table:table-cell office:value-type="string">' .
            '<text:p></text:p></table:table-cell><table:table-cell office:value-' .
            'type="string"><text:p>Test&lt;</text:p></table:table-cell>' .
            '</table:table-row></table:table>',
            $GLOBALS['odt_buffer']
        );

        // case 2

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                array(
                    'fieldname' => array(
                        'foreign_table' => 'ftable',
                        'foreign_field' => 'ffield'
                    )
                ),
                array(
                    'field' => array(
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<'
                    )
                )
            );

        $columns = array(
            'Field' => 'fieldname'
        );

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', '')
            ->will($this->returnValue(array($columns)));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnValue(
                    array(
                        'comment' => array('field' => 'testComment')
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['odt_buffer'] = '';
        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col'
        );

        $this->assertTrue(
            $this->object->getTableDef(
                'database',
                '',
                "\n",
                "example.com",
                true,
                true,
                true
            )
        );

        $this->assertContains(
            '<text:p>ftable (ffield)</text:p>',
            $GLOBALS['odt_buffer']
        );
    }

     /**
     * Test for PMA\libraries\plugins\export\ExportOdt::getTriggers
     *
     * @return void
     */
    public function testGetTriggers()
    {
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $triggers = array(
            array(
                'name' => 'tna"me',
                'action_timing' => 'ac>t',
                'event_manipulation' => 'manip&',
                'definition' => 'def'
            )
        );

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('database', 'ta<ble')
            ->will($this->returnValue($triggers));

        $GLOBALS['dbi'] = $dbi;

        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportOdt', 'getTriggers');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'database', 'ta<ble');

        $this->assertTrue(
            $result
        );

        $this->assertContains(
            '<table:table table:name="ta&lt;ble_triggers">',
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            '<text:p>tna&quot;me</text:p>',
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            '<text:p>ac&gt;t</text:p>',
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            '<text:p>manip&amp;</text:p>',
            $GLOBALS['odt_buffer']
        );

        $this->assertContains(
            '<text:p>def</text:p>',
            $GLOBALS['odt_buffer']
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::exportStructure
     *
     * @return void
     */
    public function testExportStructure()
    {

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('db', 't&bl')
            ->will($this->returnValue(1));

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\export\ExportOdt')
            ->setMethods(array('getTableDef', 'getTriggers', 'getTableDefStandIn'))
            ->getMock();

        $this->object->expects($this->at(0))
            ->method('getTableDef')
            ->with('db', 't&bl', "\n", "example.com", false, false, false, false)
            ->will($this->returnValue('dumpText1'));

        $this->object->expects($this->once())
            ->method('getTriggers')
            ->with('db', 't&bl')
            ->will($this->returnValue('dumpText2'));

        $this->object->expects($this->at(2))
            ->method('getTableDef')
            ->with(
                'db', 't&bl', "\n", "example.com",
                false, false, false, false, true, true
            )
            ->will($this->returnValue('dumpText3'));

        $this->object->expects($this->once())
            ->method('getTableDefStandIn')
            ->with('db', 't&bl', "\n")
            ->will($this->returnValue('dumpText4'));

        $GLOBALS['dbi'] = $dbi;

        // case 1
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 't&bl', "\n", "example.com", "create_table", "test"
            )
        );

        $this->assertContains(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Table structure for table t&amp;bl</text:h>',
            $GLOBALS['odt_buffer']
        );

        // case 2
        $GLOBALS['odt_buffer'] = '';

        $this->assertTrue(
            $this->object->exportStructure(
                'db', 't&bl', "\n", "example.com", "triggers", "test"
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Triggers t&amp;bl</text:h>',
            $GLOBALS['odt_buffer']
        );

        // case 3
        $GLOBALS['odt_buffer'] = '';

        $this->assertTrue(
            $this->object->exportStructure(
                'db', 't&bl', "\n", "example.com", "create_view", "test"
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" ' .
            'text:is-list-header="true">Structure for view t&amp;bl</text:h>',
            $GLOBALS['odt_buffer']
        );

        // case 4
        $GLOBALS['odt_buffer'] = '';
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 't&bl', "\n", "example.com", "stand_in", "test"
            )
        );

        $this->assertEquals(
            '<text:h text:outline-level="2" text:style-name="Heading_2" text:is' .
            '-list-header="true">Stand-in structure for view t&amp;bl</text:h>',
            $GLOBALS['odt_buffer']
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportOdt::formatOneColumnDefinition
     *
     * @return void
     */
    public function testFormatOneColumnDefinition()
    {
        $method = new ReflectionMethod(
            'PMA\libraries\plugins\export\ExportOdt', 'formatOneColumnDefinition'
        );
        $method->setAccessible(true);

        $cols = array(
            'Null' => 'Yes',
            'Field' => 'field',
            'Key' => 'PRI',
            'Type' => 'set(abc)enum123'
        );

        $col_alias = 'alias';

        $this->assertEquals(
            '<table:table-row><table:table-cell office:value-type="string">' .
            '<text:p>alias</text:p></table:table-cell><table:table-cell off' .
            'ice:value-type="string"><text:p>set(abc)</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>Yes' .
            '</text:p></table:table-cell><table:table-cell office:value-typ' .
            'e="string"><text:p>NULL</text:p></table:table-cell>',
            $method->invoke($this->object, $cols, $col_alias)
        );

        $cols = array(
            'Null' => 'NO',
            'Field' => 'fields',
            'Key' => 'COMP',
            'Type' => '',
            'Default' => 'def'
        );

        $this->assertEquals(
            '<table:table-row><table:table-cell office:value-type="string">' .
            '<text:p>fields</text:p></table:table-cell><table:table-cell off' .
            'ice:value-type="string"><text:p>&amp;nbsp;</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>No' .
            '</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"string"><text:p>def</text:p></table:table-cell>',
            $method->invoke($this->object, $cols, '')
        );
    }
}
