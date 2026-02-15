<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportCodegen;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportCodegen::class)]
#[Medium]
final class ExportCodegenTest extends AbstractTestCase
{
    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportCodegen::class, 'setProperties');

        $exportCodegen = $this->getExportCodegen();
        $method->invoke($exportCodegen, null);

        $attrProperties = new ReflectionProperty(ExportCodegen::class, 'properties');
        $properties = $attrProperties->getValue($exportCodegen);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'CodeGen',
            $properties->getText(),
        );

        self::assertSame(
            'cs',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/cs',
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
            'codegen_general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $hidden = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(HiddenPropertyItem::class, $hidden);

        self::assertSame(
            'structure_or_data',
            $hidden->getName(),
        );

        $select = $generalProperties->current();

        self::assertInstanceOf(SelectPropertyItem::class, $select);

        self::assertSame(
            'format',
            $select->getName(),
        );

        self::assertSame(
            'Format:',
            $select->getText(),
        );

        self::assertSame(
            ['NHibernate C# DO', 'NHibernate XML'],
            $select->getValues(),
        );
    }

    public function testExportHeader(): void
    {
        $this->expectNotToPerformAssertions();
        $exportCodegen = $this->getExportCodegen();
        $exportCodegen->exportHeader();
    }

    public function testExportFooter(): void
    {
        $this->expectNotToPerformAssertions();
        $exportCodegen = $this->getExportCodegen();
        $exportCodegen->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $this->expectNotToPerformAssertions();
        $exportCodegen = $this->getExportCodegen();
        $exportCodegen->exportDBHeader('testDB');
    }

    public function testExportDBFooter(): void
    {
        $this->expectNotToPerformAssertions();
        $exportCodegen = $this->getExportCodegen();
        $exportCodegen->exportDBFooter('testDB');
    }

    public function testExportData(): void
    {
        OutputHandler::$asFile = true;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['codegen_format' => '1']);

        $exportCodegen = $this->getExportCodegen();
        $exportCodegen->setExportOptions($request, new Export());

        ob_start();
        $exportCodegen->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame(
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
            $result,
        );
    }

    public function testCgMakeIdentifier(): void
    {
        self::assertSame(
            '_Ⅲfoo',
            ExportCodegen::cgMakeIdentifier('Ⅲ{}96`{}foo', true),
        );

        self::assertSame(
            'TestⅢ',
            ExportCodegen::cgMakeIdentifier('`98testⅢ{}96`{}', true),
        );

        self::assertSame(
            'testⅢ',
            ExportCodegen::cgMakeIdentifier('`98testⅢ{}96`{}', false),
        );
    }

    public function testHandleNHibernateCSBody(): void
    {
        $exportCodegen = $this->getExportCodegen();

        $method = new ReflectionMethod(ExportCodegen::class, 'handleNHibernateCSBody');
        $result = $method->invoke($exportCodegen, 'test_db', 'test_table');

        self::assertSame(
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
            $result,
        );
    }

    public function testHandleNHibernateXMLBody(): void
    {
        $exportCodegen = $this->getExportCodegen();

        $method = new ReflectionMethod(ExportCodegen::class, 'handleNHibernateXMLBody');
        $result = $method->invoke($exportCodegen, 'test_db', 'test_table');

        self::assertSame(
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
            $result,
        );
    }

    private function getExportCodegen(): ExportCodegen
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportCodegen($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
