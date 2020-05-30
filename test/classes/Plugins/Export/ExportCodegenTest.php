<?php
/**
 * tests for PhpMyAdmin\Plugins\Export\ExportCodegen class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportCodegen;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use function ob_get_clean;
use function ob_start;

/**
 * tests for PhpMyAdmin\Plugins\Export\ExportCodegen class
 *
 * @group medium
 */
class ExportCodegenTest extends AbstractTestCase
{
    /** @var ExportCodegen */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $this->object = new ExportCodegen();
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
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::initSpecificVariables
     *
     * @return void
     */
    public function testInitSpecificVariables()
    {
        $method = new ReflectionMethod(ExportCodegen::class, 'initSpecificVariables');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrCgFormats = new ReflectionProperty(ExportCodegen::class, '_cgFormats');
        $attrCgFormats->setAccessible(true);

        $this->assertEquals(
            [
                'NHibernate C# DO',
                'NHibernate XML',
            ],
            $attrCgFormats->getValue($this->object)
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod(ExportCodegen::class, 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportCodegen::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            ExportPluginProperties::class,
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
            OptionsPropertyRootGroup::class,
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $hidden = $generalProperties[0];

        $this->assertInstanceOf(
            HiddenPropertyItem::class,
            $hidden
        );

        $this->assertEquals(
            'structure_or_data',
            $hidden->getName()
        );

        $select = $generalProperties[1];

        $this->assertInstanceOf(
            SelectPropertyItem::class,
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
            [
                'NHibernate C# DO',
                'NHibernate XML',
            ],
            $select->getValues()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::exportHeader
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
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::exportFooter
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
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::exportDBHeader
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
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::exportDBFooter
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
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::exportData
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
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->object->exportData(
            'testDB',
            'testTable',
            "\n",
            'example.com',
            'test'
        );
        $result = ob_get_clean();

        $this->assertStringContainsString(
            '<?xml version="1.0" encoding="utf-8" ?>',
            $result
        );

        $this->assertStringContainsString(
            '<class name="TestTable" table="TestTable">',
            $result
        );

        $this->assertStringContainsString(
            '</class>',
            $result
        );

        $this->assertStringContainsString(
            '</hibernate-mapping>',
            $result
        );

        $GLOBALS['codegen_format'] = 4;

        $this->object->exportData(
            'testDB',
            'testTable',
            "\n",
            'example.com',
            'test'
        );

        $this->expectOutputString(
            '4 is not supported.'
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::cgMakeIdentifier
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
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::handleNHibernateCSBody
     *
     * @return void
     */
    public function testHandleNHibernateCSBody()
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('DESC `db`.`table`')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(['a', 'b', 'c', false, 'e', 'f']));

        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;
        $method = new ReflectionMethod(ExportCodegen::class, 'handleNHibernateCSBody');
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
            '}',
            $result
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportCodegen::handleNHibernateXMLBody
     *
     * @return void
     */
    public function testHandleNHibernateXMLBody()
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('DESC `db`.`table`')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(['a', 'b', 'c', false, 'e', 'f']));

        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(['g', 'h', 'i', 'PRI', 'j', 'k']));

        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;
        $method = new ReflectionMethod(ExportCodegen::class, 'handleNHibernateXMLBody');
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
     *     - PhpMyAdmin\Plugins\Export\ExportCodegen::_getCgFormats
     *     - PhpMyAdmin\Plugins\Export\ExportCodegen::_setCgFormats
     *
     * @return void
     */
    public function testSetGetCgFormats()
    {
        $reflection = new ReflectionClass(ExportCodegen::class);

        $getter = $reflection->getMethod('_getCgFormats');
        $setter = $reflection->getMethod('_setCgFormats');

        $getter->setAccessible(true);
        $setter->setAccessible(true);

        $setter->invoke($this->object, [1, 2]);

        $this->assertEquals(
            [
                1,
                2,
            ],
            $getter->invoke($this->object)
        );
    }
}
