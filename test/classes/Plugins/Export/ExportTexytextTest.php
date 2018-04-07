<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Export\ExportTexytext class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportTexytext;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * tests for PhpMyAdmin\Plugins\Export\ExportTexytext class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportTexytextTest extends PmaTestCase
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
        $this->object = new ExportTexytext();
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
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportTexytext', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PhpMyAdmin\Plugins\Export\ExportTexytext', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'Texy! text',
            $properties->getText()
        );

        $this->assertEquals(
            'txt',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/plain',
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

        $this->assertEquals(
            "Dump table",
            $generalOptions->getText()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\RadioPropertyItem',
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

        $this->assertEquals(
            'columns',
            $property->getName()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'null',
            $property->getName()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $this->expectOutputString(
            "===Database testDb\n\n"
        );
        $this->assertTrue(
            $this->object->exportDBHeader('testDb')
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::exportDBFooter
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
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::exportDBCreate
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
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
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
            ->will($this->returnValue('fName3'));

        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array(null, '0', 'test')));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_columns'] = "&";
        $GLOBALS['foo_null'] = ">";

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'db', 'ta<ble', "\n", "example.com", "SELECT"
            )
        );
        $result = ob_get_clean();

        $this->assertContains(
            "|fName1|fNa&amp;quot;me2|fName3",
            $result
        );

        $this->assertContains(
            "|&amp;gt;|0|test",
            $result
        );

    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::getTableDefStandIn
     *
     * @return void
     */
    public function testGetTableDefStandIn()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('db', 'view')
            ->will($this->returnValue(array(1, 2)));

        $keys = array(
            array(
                'Non_unique' => 0,
                'Column_name' => 'cname'
            ),
            array(
                'Non_unique' => 1,
                'Column_name' => 'cname2'
            )
        );

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('db', 'view')
            ->will($this->returnValue($keys));

        $dbi->expects($this->once())
            ->method('selectDb')
            ->with('db');

        $GLOBALS['dbi'] = $dbi;

        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Export\ExportTexytext')
            ->disableOriginalConstructor()
            ->setMethods(array('formatOneColumnDefinition'))
            ->getMock();

        $this->object->expects($this->at(0))
            ->method('formatOneColumnDefinition')
            ->with(1, array('cname'))
            ->will($this->returnValue('c1'));

        $this->object->expects($this->at(1))
            ->method('formatOneColumnDefinition')
            ->with(2, array('cname'))
            ->will($this->returnValue('c2'));

        $result = $this->object->getTableDefStandIn('db', 'view', '#');

        $this->assertContains(
            "c1\nc2",
            $result
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::getTableDef
     *
     * @return void
     */
    public function testGetTableDef()
    {
        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Export\ExportTexytext')
            ->setMethods(array('formatOneColumnDefinition'))
            ->getMock();

        // case 1

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $keys = array(
            array(
                'Non_unique' => 0,
                'Column_name' => 'cname'
            ),
            array(
                'Non_unique' => 1,
                'Column_name' => 'cname2'
            )
        );

        $dbi->expects($this->once())
            ->method('getTableIndexes')
            ->with('db', 'table')
            ->will($this->returnValue($keys));

        $dbi->expects($this->exactly(2))
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                array(
                    'fname' => array(
                        'foreign_table' => '<ftable',
                        'foreign_field' => 'ffield>'
                    )
                ),
                array(
                    'fname' => array(
                        'values' => 'test-',
                        'transformation' => 'testfoo',
                        'mimetype' => 'test<'
                    )
                )
            );

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->will(
                $this->returnValue(
                    'SELECT a FROM b'
                )
            );

        $columns = array(
            'Field' => 'fname',
            'Comment' => 'comm'
        );

        $dbi->expects($this->exactly(2))
            ->method('getColumns')
            ->with('db', 'table')
            ->will($this->returnValue(array($columns)));

        $GLOBALS['dbi'] = $dbi;

        $this->object->expects($this->exactly(1))
            ->method('formatOneColumnDefinition')
            ->with(array('Field' => 'fname', 'Comment' => 'comm'), array('cname'))
            ->will($this->returnValue(1));

        $GLOBALS['cfgRelation']['relation'] = true;
        $_SESSION['relation'][0] = array(
            'PMA_VERSION' => PMA_VERSION,
            'relwork' => true,
            'commwork' => true,
            'mimework' => true,
            'db' => 'db',
            'relation' => 'rel',
            'column_info' => 'col'
        );

        $result = $this->object->getTableDef(
            'db',
            'table',
            "\n",
            "example.com",
            true,
            true,
            true
        );

        $this->assertContains(
            '1|&lt;ftable (ffield&gt;)|comm|Test&lt;',
            $result
        );
    }

     /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::getTriggers
     *
     * @return void
     */
    public function testGetTriggers()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

        $result = $this->object->getTriggers('database', 'ta<ble');

        $this->assertContains(
            '|tna"me|ac>t|manip&|def',
            $result
        );

        $this->assertContains(
            '|Name|Time|Event|Definition',
            $result
        );

    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::exportStructure
     *
     * @return void
     */
    public function testExportStructure()
    {

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getTriggers')
            ->with('db', 't&bl')
            ->will($this->returnValue(1));

        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Export\ExportTexytext')
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
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 't&bl', "\n", "example.com", "create_table", "test"
            )
        );
        $result = ob_get_clean();

        $this->assertContains(
            '== Table structure for table t&amp;bl' . "\n\ndumpText1",
            $result
        );

        // case 2
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 't&bl', "\n", "example.com", "triggers", "test"
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '== Triggers t&amp;bl' . "\n\ndumpText2",
            $result
        );

        // case 3
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 't&bl', "\n", "example.com", "create_view", "test"
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '== Structure for view t&amp;bl' . "\n\ndumpText3",
            $result
        );

        // case 4
        ob_start();
        $this->assertTrue(
            $this->object->exportStructure(
                'db', 't&bl', "\n", "example.com", "stand_in", "test"
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '== Stand-in structure for view t&amp;bl' . "\n\ndumpText4",
            $result
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportTexytext::formatOneColumnDefinition
     *
     * @return void
     */
    public function testFormatOneColumnDefinition()
    {
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
            '|//**field**//|set(abc)|Yes|NULL',
            $this->object->formatOneColumnDefinition($cols, $unique_keys)
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
            '|fields|&amp;nbsp;|No|def',
            $this->object->formatOneColumnDefinition($cols, $unique_keys)
        );
    }
}
