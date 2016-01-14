<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\export\ExportHtmlword class
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\plugins\export\ExportHtmlword;

require_once 'libraries/export.lib.php';
require_once 'libraries/config.default.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/transformations.lib.php';
require_once 'export.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\export\ExportHtmlword class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportHtmlwordTest extends PMATestCase
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
        $this->object = new ExportHtmlword();
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
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
     * Test for PMA\libraries\plugins\export\ExportHtmlword::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportHtmlword', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PMA\libraries\plugins\export\ExportHtmlword', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PMA\libraries\properties\plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'Microsoft Word 2000',
            $properties->getText()
        );

        $this->assertEquals(
            'doc',
            $properties->getExtension()
        );

        $this->assertEquals(
            'application/vnd.ms-word',
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
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'dump_what',
            $generalOptions->getName()
        );

        $this->assertEquals(
            'Dump table',
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

        $generalOptions = $generalOptionsArray[1];

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'dump_what',
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
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        $expected
            = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . 'utf-8' . '" />
            </head>
            <body>';

        $this->assertEquals(
            $expected,
            $result
        );

        // case 2

        $GLOBALS['charset'] = 'ISO-8859-1';
        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        $expected
            = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . 'ISO-8859-1' . '" />
            </head>
            <body>';

        $this->assertEquals(
            $expected,
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportFooter()
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '</body></html>',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportDBHeader('d"b')
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h1>Database d&quot;b</h1>',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::exportDBFooter
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
     * Test for PMA\libraries\plugins\export\ExportHtmlword::exportDBCreate
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
     * Test for PMA\libraries\plugins\export\ExportHtmlword::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        // case 1

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('test', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(5));

        $dbi->expects($this->any())
            ->method('fieldName')
            ->will($this->returnValue("foo\\bar"));

        $dbi->expects($this->at(7))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array(null, '0', 'test', false)));

        $dbi->expects($this->at(8))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['htmlword_columns'] = true;
        $GLOBALS['what'] = 'UT';
        $GLOBALS['UT_null'] = 'customNull';
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'testDB', 'testTable', "\n", 'example.com', 'test'
            )
        );
        $result = htmlspecialchars_decode(ob_get_clean());

        $this->assertEquals(
            '<h2>Dumping data for table testTable</h2>' .
            '<table class="width100" cellspacing="1"><tr class="print-category">' .
            '<td class="print"><strong>foobar</strong></td>' .
            '<td class="print"><strong>foobar</strong></td>' .
            '<td class="print"><strong>foobar</strong></td>' .
            '<td class="print"><strong>foobar</strong></td>' .
            '<td class="print"><strong>foobar</strong></td>' .
            '</tr><tr class="print-category"><td class="print">' .
            'customNull</td><td class="print">0</td><td class="print">test</td>' .
            '<td class="print"></td><td class="print">customNull</td></tr></table>',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::getTableDefStandIn
     *
     * @return void
     */
    public function testGetTableDefStandIn()
    {
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\export\ExportHtmlword')
            ->setMethods(array('formatOneColumnDefinition'))
            ->getMock();

        // case 1

        $keys = array(
            array(
                'Non_unique' => 0,
                'Column_name' => 'name1'
            ),
            array(
                'Non_unique' => 1,
                'Column_name' => 'name2'
            )
        );

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', 'view')
            ->will($this->returnValue($keys));

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('database', 'view')
            ->will($this->returnValue(array(array('Field' => 'column'))));

        $GLOBALS['dbi'] = $dbi;

        $this->object->expects($this->once())
            ->method('formatOneColumnDefinition')
            ->with(array('Field' => 'column'), array('name1'), 'column')
            ->will($this->returnValue(1));

        $this->assertEquals(
            '<table class="width100" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>' .
            '1</tr></table>',
            $this->object->getTableDefStandIn('database', 'view', "\n")
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::getTableDef
     *
     * @return void
     */
    public function testGetTableDef()
    {
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\export\ExportHtmlword')
            ->setMethods(array('formatOneColumnDefinition'))
            ->getMock();

        $keys = array(
            array(
                'Non_unique' => 0,
                'Column_name' => 'name1'
            ),
            array(
                'Non_unique' => 1,
                'Column_name' => 'name2'
            )
        );

        // case 1

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(1))
            ->method('fetchResult')
            ->will($this->returnValue(array()));

        $dbi->expects($this->at(6))
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    array(
                        'fieldname' => array(
                            'values' => 'test-',
                            'transformation' => 'testfoo',
                            'mimetype' => 'test<'
                        )
                    )
                )
            );

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

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

        $this->object->expects($this->exactly(3))
            ->method('formatOneColumnDefinition')
            ->with($columns, array('name1'))
            ->will($this->returnValue(1));

        $GLOBALS['cfgRelation']['relation'] = true;
        $GLOBALS['controllink'] = null;
        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col'
        );

        $result = $this->object->getTableDef(
            'database',
            '',
            true,
            true,
            true
        );

        $this->assertEquals(
            '<table class="width100" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td>' .
            '<td class="print"><strong>Comments</strong></td>' .
            '<td class="print"><strong>MIME</strong></td></tr>' .
            '1<td class="print"></td><td class="print">Test&lt;</td></tr></table>',
            $result
        );

        // case 2

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(1))
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    array(
                        'fieldname' => array(
                            'foreign_table' => 'ftable',
                            'foreign_field' => 'ffield'
                        )
                    )
                )
            );

        $dbi->expects($this->at(6))
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    array(
                        'field' => array(
                            'values' => 'test-',
                            'transformation' => 'testfoo',
                            'mimetype' => 'test<'
                        )
                    )
                )
            );

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

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

        $result = $this->object->getTableDef(
            'database',
            '',
            true,
            true,
            true
        );

        $this->assertContains(
            '<td class="print">ftable (ffield)</td>',
            $result
        );

        $this->assertContains(
            '<td class="print"></td><td class="print"></td>',
            $result
        );

         // case 3

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->will($this->returnValue($keys));

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

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => false,
            'commwork' => false,
            'mimework' => false,
            'db' => 'database',
            'relation' => 'rel',
            'column_info' => 'col'
        );

        $result = $this->object->getTableDef(
            'database',
            '',
            false,
            false,
            false
        );

        $this->assertEquals(
            '<table class="width100" cellspacing="1">' .
            '<tr class="print-category"><th class="print">Column</th>' .
            '<td class="print"><strong>Type</strong></td>' .
            '<td class="print"><strong>Null</strong></td>' .
            '<td class="print"><strong>Default</strong></td></tr>1</tr></table>',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::getTriggers
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
            ->with('database', 'table')
            ->will($this->returnValue($triggers));

        $GLOBALS['dbi'] = $dbi;

        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportHtmlword', 'getTriggers');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'database', 'table');

        $this->assertContains(
            '<td class="print">tna&quot;me</td>' .
            '<td class="print">ac&gt;t</td>' .
            '<td class="print">manip&amp;</td>' .
            '<td class="print">def</td>',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::exportStructure
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
            ->with('db', 'tbl')
            ->will($this->returnValue(1));

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\export\ExportHtmlword')
            ->setMethods(array('getTableDef', 'getTriggers', 'getTableDefStandIn'))
            ->getMock();

        $this->object->expects($this->at(0))
            ->method('getTableDef')
            ->with('db', 'tbl', false, false, false, false)
            ->will($this->returnValue('dumpText1'));

        $this->object->expects($this->once())
            ->method('getTriggers')
            ->with('db', 'tbl')
            ->will($this->returnValue('dumpText2'));

        $this->object->expects($this->at(2))
            ->method('getTableDef')
            ->with('db', 'tbl', false, false, false, true, array())
            ->will($this->returnValue('dumpText3'));

        $this->object->expects($this->once())
            ->method('getTableDefStandIn')
            ->with('db', 'tbl', "\n")
            ->will($this->returnValue('dumpText4'));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 'tbl', "\n", "example.com", "create_table", "test"
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Table structure for table tbl</h2>dumpText1',
            $result
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 'tbl', "\n", "example.com", "triggers", "test"
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Triggers tbl</h2>dumpText2',
            $result
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 'tbl', "\n", "example.com", "create_view", "test"
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Structure for view tbl</h2>dumpText3',
            $result
        );

        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 'tbl', "\n", "example.com", "stand_in", "test"
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '<h2>Stand-in structure for view tbl</h2>dumpText4',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportHtmlword::formatOneColumnDefinition
     *
     * @return void
     */
    public function testFormatOneColumnDefinition()
    {
        $method = new ReflectionMethod(
            'PMA\libraries\plugins\export\ExportHtmlword', 'formatOneColumnDefinition'
        );
        $method->setAccessible(true);

        $cols = array(
            'Null' => 'Yes',
            'Field' => 'field',
            'Key' => 'PRI',
            'Type' => 'set(abc)enum123'
        );

        $unique_keys = array(
            'field'
        );

        $this->assertEquals(
            '<tr class="print-category"><td class="print"><em>' .
            '<strong>field</strong></em></td><td class="print">set(abc)</td>' .
            '<td class="print">Yes</td><td class="print">NULL</td>',
            $method->invoke($this->object, $cols, $unique_keys)
        );

        $cols = array(
            'Null' => 'NO',
            'Field' => 'fields',
            'Key' => 'COMP',
            'Type' => '',
            'Default' => 'def'
        );

        $unique_keys = array(
            'field'
        );

        $this->assertEquals(
            '<tr class="print-category"><td class="print">fields</td>' .
            '<td class="print">&amp;nbsp;</td><td class="print">No</td>' .
            '<td class="print">def</td>',
            $method->invoke($this->object, $cols, $unique_keys)
        );
    }
}
