<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

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

    public function testInitSpecificVariables(): void
    {
        $method = new ReflectionMethod(ExportCodegen::class, 'initSpecificVariables');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrCgFormats = new ReflectionProperty(ExportCodegen::class, 'cgFormats');
        $attrCgFormats->setAccessible(true);

        $this->assertEquals(
            [
                'NHibernate C# DO',
                'NHibernate XML',
            ],
            $attrCgFormats->getValue($this->object)
        );
    }

    public function testSetProperties(): void
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

    public function testExportHeader(): void
    {
        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    public function testExportFooter(): void
    {
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    public function testExportDBHeader(): void
    {
        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    public function testExportData(): void
    {
        $GLOBALS['codegen_format'] = 1;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;

        ob_start();
        $this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        );
        $result = ob_get_clean();

        $this->assertIsString($result);
        $this->assertEquals(
            '<?xml version="1.0" encoding="utf-8" ?>' . "\n"
            . '<hibernate-mapping xmlns="urn:nhibernate-mapping-2.2" namespace="Test_db" assembly="Test_db">' . "\n"
            . '    <class name="Test_table" table="Test_table">' . "\n"
            . '        <id name="Id" type="Int32" unsaved-value="0">' . "\n"
            . '            <column name="id" sql-type="int" not-null="true" unique="true" index="PRIMARY"/>' . "\n"
            . '            <generator class="native" />' . "\n"
            . '        </id>' . "\n"
            . '        <property name="Name" type="String">' . "\n"
            . '            <column name="name" sql-type="varchar" not-null="true" />' . "\n"
            . '        </property>' . "\n"
            . '        <property name="Datetimefield" type="DateTime">' . "\n"
            . '            <column name="datetimefield" sql-type="datetime" not-null="true" />' . "\n"
            . '        </property>' . "\n"
            . '    </class>' . "\n"
            . '</hibernate-mapping>',
            $result
        );

        $GLOBALS['codegen_format'] = 4;

        $this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        );

        $this->expectOutputString(
            '4 is not supported.'
        );
    }

    public function testCgMakeIdentifier(): void
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

    public function testHandleNHibernateCSBody(): void
    {
        $method = new ReflectionMethod(ExportCodegen::class, 'handleNHibernateCSBody');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'test_db', 'test_table', "\n");

        $this->assertEquals(
            'using System;' . "\n" .
            'using System.Collections;' . "\n" .
            'using System.Collections.Generic;' . "\n" .
            'using System.Text;' . "\n" .
            'namespace Test_db' . "\n" .
            '{' . "\n" .
            '    #region Test_table' . "\n" .
            '    public class Test_table' . "\n" .
            '    {' . "\n" .
            '        #region Member Variables' . "\n" .
            '        protected int _id;' . "\n" .
            '        protected string _name;' . "\n" .
            '        protected DateTime _datetimefield;' . "\n" .
            '        #endregion' . "\n" .
            '        #region Constructors' . "\n" .
            '        public Test_table() { }' . "\n" .
            '        public Test_table(string name, DateTime datetimefield)' . "\n" .
            '        {' . "\n" .
            '            this._name=name;' . "\n" .
            '            this._datetimefield=datetimefield;' . "\n" .
            '        }' . "\n" .
            '        #endregion' . "\n" .
            '        #region Public Properties' . "\n" .
            '        public virtual int Id' . "\n" .
            '        {' . "\n" .
            '            get {return _id;}' . "\n" .
            '            set {_id=value;}' . "\n" .
            '        }' . "\n" .
            '        public virtual string Name' . "\n" .
            '        {' . "\n" .
            '            get {return _name;}' . "\n" .
            '            set {_name=value;}' . "\n" .
            '        }' . "\n" .
            '        public virtual DateTime Datetimefield' . "\n" .
            '        {' . "\n" .
            '            get {return _datetimefield;}' . "\n" .
            '            set {_datetimefield=value;}' . "\n" .
            '        }' . "\n" .
            '        #endregion' . "\n" .
            '    }' . "\n" .
            '    #endregion' . "\n" .
            '}',
            $result
        );
    }

    public function testHandleNHibernateXMLBody(): void
    {
        $method = new ReflectionMethod(ExportCodegen::class, 'handleNHibernateXMLBody');
        $method->setAccessible(true);
        $result = $method->invoke($this->object, 'test_db', 'test_table', "\n");

        $this->assertEquals(
            '<?xml version="1.0" encoding="utf-8" ?>' . "\n" .
            '<hibernate-mapping xmlns="urn:nhibernate-mapping-2.2" namespace="Test_db" assembly="Test_db">' . "\n" .
            '    <class name="Test_table" table="Test_table">' . "\n" .
            '        <id name="Id" type="Int32" unsaved-value="0">' . "\n" .
            '            <column name="id" sql-type="int" not-null="true" unique="true" index="PRIMARY"/>' . "\n" .
            '            <generator class="native" />' . "\n" .
            '        </id>' . "\n" .
            '        <property name="Name" type="String">' . "\n" .
            '            <column name="name" sql-type="varchar" not-null="true" />' . "\n" .
            '        </property>' . "\n" .
            '        <property name="Datetimefield" type="DateTime">' . "\n" .
            '            <column name="datetimefield" sql-type="datetime" not-null="true" />' . "\n" .
            '        </property>' . "\n" .
            '    </class>' . "\n" .
            '</hibernate-mapping>',
            $result
        );
    }

    /**
     * Test for
     *     - PhpMyAdmin\Plugins\Export\ExportCodegen::getCgFormats
     *     - PhpMyAdmin\Plugins\Export\ExportCodegen::setCgFormats
     */
    public function testSetGetCgFormats(): void
    {
        $reflection = new ReflectionClass(ExportCodegen::class);

        $getter = $reflection->getMethod('getCgFormats');
        $setter = $reflection->getMethod('setCgFormats');

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
