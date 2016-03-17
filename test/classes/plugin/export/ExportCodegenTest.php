<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\export\ExportCodegen class
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\plugins\export\ExportCodegen;

require_once 'libraries/export.lib.php';
require_once 'libraries/config.default.php';
require_once 'export.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\export\ExportCodegen class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportCodegenTest extends PMATestCase
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
        $this->object = new ExportCodegen(null);
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
     * Test for PMA\libraries\plugins\export\ExportCodegen::initSpecificVariables
     *
     * @return void
     */
    public function testInitSpecificVariables()
    {

        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportCodegen', 'initSpecificVariables');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrCgFormats = new ReflectionProperty('PMA\libraries\plugins\export\ExportCodegen', '_cgFormats');
        $attrCgFormats->setAccessible(true);

        $attrCgHandlers = new ReflectionProperty('PMA\libraries\plugins\export\ExportCodegen', '_cgHandlers');
        $attrCgHandlers->setAccessible(true);

        $this->assertEquals(
            array(
                "NHibernate C# DO",
                "NHibernate XML"
            ),
            $attrCgFormats->getValue($this->object)
        );

        $this->assertEquals(
            array(
                "_handleNHibernateCSBody",
                "_handleNHibernateXMLBody"
            ),
            $attrCgHandlers->getValue($this->object)
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCodegen::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportCodegen', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PMA\libraries\plugins\export\ExportCodegen', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PMA\libraries\properties\plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'CodeGen',
            $properties->getText()
        );

        $this->assertEquals(
            'cs',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/cs',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
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
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $hidden = $generalProperties[0];

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\HiddenPropertyItem',
            $hidden
        );

        $this->assertEquals(
            'structure_or_data',
            $hidden->getName()
        );

        $select = $generalProperties[1];

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\SelectPropertyItem',
            $select
        );

        $this->assertEquals(
            'format',
            $select->getName()
        );

        $this->assertEquals(
            'Format:',
            $select->getText()
        );

        $this->assertEquals(
            array(
                "NHibernate C# DO",
                "NHibernate XML"
            ),
            $select->getValues()
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCodegen::exportHeader
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
     * Test for PMA\libraries\plugins\export\ExportCodegen::exportFooter
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
     * Test for PMA\libraries\plugins\export\ExportCodegen::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCodegen::exportDBFooter
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
     * Test for PMA\libraries\plugins\export\ExportCodegen::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $GLOBALS['codegen_format'] = 1;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->object->exportData(
            'testDB', 'testTable', "\n", 'example.com', 'test'
        );
        $result = ob_get_clean();

        $this->assertContains(
            '<?xml version="1.0" encoding="utf-8" ?>',
            $result
        );

        $this->assertContains(
            '<class name="TestTable" table="TestTable">',
            $result
        );

        $this->assertContains(
            '</class>',
            $result
        );

        $this->assertContains(
            '</hibernate-mapping>',
            $result
        );

        $GLOBALS['codegen_format'] = 4;

        $this->object->exportData(
            'testDB', 'testTable', "\n", 'example.com', 'test'
        );

        $this->expectOutputString(
            '4 is not supported.'
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCodegen::cgMakeIdentifier
     *
     * @return void
     */
    public function testCgMakeIdentifier()
    {
        $this->assertEquals(
            '_Ⅲfoo',
            ExportCodegen::cgMakeIdentifier('Ⅲ{}96`{}foo', true)
        );

        $this->assertEquals(
            'TestⅢ',
            ExportCodegen::cgMakeIdentifier('`98testⅢ{}96`{}', true)
        );

        $this->assertEquals(
            'testⅢ',
            ExportCodegen::cgMakeIdentifier('`98testⅢ{}96`{}', false)
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCodegen::_handleNHibernateCSBody
     *
     * @return void
     */
    public function testHandleNHibernateCSBody()
    {
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('DESC `db`.`table`')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array('a', 'b', 'c', false, 'e', 'f')));

        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;
        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportCodegen', '_handleNHibernateCSBody');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'db', 'table', "\n");

        $this->assertEquals(
            "using System;\n" .
            "using System.Collections;\n" .
            "using System.Collections.Generic;\n" .
            "using System.Text;\n" .
            "namespace Db\n" .
            "{\n" .
            "    #region Table\n" .
            "    public class Table\n" .
            "    {\n" .
            "        #region Member Variables\n" .
            "        protected unknown _a;\n" .
            "        #endregion\n" .
            "        #region Constructors\n" .
            "        public Table() { }\n" .
            "        public Table(unknown a)\n" .
            "        {\n" .
            "            this._a=a;\n" .
            "        }\n" .
            "        #endregion\n" .
            "        #region Public Properties\n" .
            "        public virtual unknown A\n" .
            "        {\n" .
            "            get {return _a;}\n" .
            "            set {_a=value;}\n" .
            "        }\n" .
            "        #endregion\n" .
            "    }\n" .
            "    #endregion\n" .
            "}",
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCodegen::_handleNHibernateXMLBody
     *
     * @return void
     */
    public function testHandleNHibernateXMLBody()
    {
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('DESC `db`.`table`')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array('a', 'b', 'c', false, 'e', 'f')));

        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array('g', 'h', 'i', 'PRI', 'j', 'k')));

        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;
        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportCodegen', '_handleNHibernateXMLBody');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'db', 'table', "\n");

        $this->assertEquals(
            '<?xml version="1.0" encoding="utf-8" ?>' . "\n" .
            '<hibernate-mapping xmlns="urn:nhibernate-mapping-2.2" namespace="Db" ' .
            'assembly="Db">' . "\n" .
            '    <class name="Table" table="Table">' . "\n" .
            '        <property name="A" type="Unknown">' . "\n" .
            '            <column name="a" sql-type="b" not-null="false" />' . "\n" .
            '        </property>' . "\n" .
            '        <id name="G" type="Unknown" unsaved-value="0">' . "\n" .
            '            <column name="g" sql-type="h" not-null="false" ' .
            'unique="true" index="PRIMARY"/>' . "\n" .
            '            <generator class="native" />' . "\n" .
            '        </id>' . "\n" .
            '    </class>' . "\n" .
            '</hibernate-mapping>',
            $result
        );
    }

    /**
     * Test for
     *     - PMA\libraries\plugins\export\ExportCodegen::_getCgFormats
     *     - PMA\libraries\plugins\export\ExportCodegen::_setCgFormats
     *
     * @return void
     */
    public function testSetGetCgFormats()
    {
        $reflection = new ReflectionClass('PMA\libraries\plugins\export\ExportCodegen');

        $getter = $reflection->getMethod('_getCgFormats');
        $setter = $reflection->getMethod('_setCgFormats');

        $getter->setAccessible(true);
        $setter->setAccessible(true);

        $setter->invoke($this->object, array(1, 2));

        $this->assertEquals(
            array(1, 2),
            $getter->invoke($this->object)
        );
    }

    /**
     * Test for
     *     - PMA\libraries\plugins\export\ExportCodegen::_getCgHandlers
     *     - PMA\libraries\plugins\export\ExportCodegen::_setCgHandlers
     *
     * @return void
     */
    public function testSetGetCgHandlers()
    {
        $reflection = new ReflectionClass('PMA\libraries\plugins\export\ExportCodegen');

        $getter = $reflection->getMethod('_getCgHandlers');
        $setter = $reflection->getMethod('_setCgHandlers');

        $getter->setAccessible(true);
        $setter->setAccessible(true);

        $setter->invoke($this->object, array(1, 2));

        $this->assertEquals(
            array(1, 2),
            $getter->invoke($this->object)
        );
    }
}
