<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportXml;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportXml::class)]
#[Medium]
class ExportXmlTest extends AbstractTestCase
{
    protected ExportXml $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        OutputHandler::$asFile = false;
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;
        Current::$database = 'db';
        Config::getInstance()->selectedServer['DisableIS'] = true;
        $relation = new Relation($dbi);
        $this->object = new ExportXml($relation, new OutputHandler(), new Transformations($dbi, $relation));
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        DatabaseInterface::$instance = null;
        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportXml::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportXml::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'XML',
            $properties->getText(),
        );

        self::assertSame(
            'xml',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/xml',
            $properties->getMimeType(),
        );

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'structure',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'data',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);
    }

    public function testExportHeader(): void
    {
        Current::$charset = 'iso-8859-1';
        $config = Config::getInstance();
        $config->selectedServer['port'] = 80;
        $config->selectedServer['host'] = 'localhost';
        $config->selectedServer['DisableIS'] = false;
        Current::$database = 'd<"b';

        $functions = [['fn']];
        $procedures = [['pr']];

        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);

        $dbiDummy->addResult(
            'SELECT `DEFAULT_CHARACTER_SET_NAME`, `DEFAULT_COLLATION_NAME`'
            . " FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME` = 'd<\\\"b' LIMIT 1",
            [['utf-8', 'utf8_general_ci']],
            ['DEFAULT_CHARACTER_SET_NAME', 'DEFAULT_COLLATION_NAME'],
        );
        $dbiDummy->addResult(
            'SELECT SPECIFIC_NAME FROM information_schema.ROUTINES '
            . "WHERE ROUTINE_SCHEMA = 'd<\\\"b' AND ROUTINE_TYPE = 'FUNCTION' AND SPECIFIC_NAME != ''",
            $functions,
            ['Name'],
        );
        $dbiDummy->addResult(
            'SELECT SPECIFIC_NAME FROM information_schema.ROUTINES '
            . "WHERE ROUTINE_SCHEMA = 'd<\\\"b' AND ROUTINE_TYPE = 'PROCEDURE' AND SPECIFIC_NAME != ''",
            $procedures,
            ['Name'],
        );
        $dbiDummy->addResult('SHOW CREATE TABLE `d<"b`.`table`', [['table', '"tbl"']]);
        $dbiDummy->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'d<\"b\' AND TABLE_NAME = \'table\'',
            [],
        );
        $dbiDummy->addResult(
            'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE,'
            . ' ACTION_TIMING, ACTION_STATEMENT, EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER FROM'
            . ' information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA COLLATE utf8_bin= \'d<\"b\' AND'
            . ' EVENT_OBJECT_TABLE COLLATE utf8_bin = \'table\';',
            [
                [
                    'd<"b',
                    'trname',
                    'INSERT',
                    'table',
                    'AFTER',
                    'BEGIN END',
                    'd<"b',
                    'test_user@localhost',
                ],
            ],
            [
                'TRIGGER_SCHEMA',
                'TRIGGER_NAME',
                'EVENT_MANIPULATION',
                'EVENT_OBJECT_TABLE',
                'ACTION_TIMING',
                'ACTION_STATEMENT',
                'EVENT_OBJECT_SCHEMA',
                'DEFINER',
            ],
        );
        $dbiDummy->addResult('SHOW CREATE FUNCTION `d<"b`.`fn`', [['fn', 'fndef']], ['name', 'Create Function']);
        $dbiDummy->addResult('SHOW CREATE PROCEDURE `d<"b`.`pr`', [['pr', 'prdef']], ['name', 'Create Procedure']);

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'xml_export_contents' => 'On',
                'xml_export_functions' => 'On',
                'xml_export_procedures' => 'On',
                'xml_export_tables' => 'On',
                'xml_export_triggers' => 'On',
            ]);

        $this->object->setExportOptions($request, new Export());

        $this->object->setTables([]);
        Current::$table = 'table';

        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString(
            '&lt;pma_xml_export version=&quot;1.0&quot; xmlns:pma=&quot;' .
            'https://www.phpmyadmin.net/some_doc_url/&quot;&gt;',
            $result,
        );

        self::assertStringContainsString(
            '&lt;pma:structure_schemas&gt;' . "\n" .
            '        &lt;pma:database name=&quot;d&amp;lt;&amp;quot;b&quot; collat' .
            'ion=&quot;utf8_general_ci&quot; charset=&quot;utf-8&quot;&gt;' . "\n" .
            '            &lt;pma:table name=&quot;table&quot;&gt;' . "\n" .
            '                &amp;quot;tbl&amp;quot;;' . "\n" .
            '            &lt;/pma:table&gt;' . "\n" .
            '            &lt;pma:trigger name=&quot;trname&quot;&gt;' . "\n" .
            '                CREATE TRIGGER `trname` AFTER INSERT ON `table`' . "\n" .
            '                 FOR EACH ROW BEGIN END' . "\n" .
            '            &lt;/pma:trigger&gt;' . "\n" .
            '            &lt;pma:function name=&quot;fn&quot;&gt;' . "\n" .
            '                fndef' . "\n" .
            '            &lt;/pma:function&gt;' . "\n" .
            '            &lt;pma:procedure name=&quot;pr&quot;&gt;' . "\n" .
            '                prdef' . "\n" .
            '            &lt;/pma:procedure&gt;' . "\n" .
            '        &lt;/pma:database&gt;' . "\n" .
            '    &lt;/pma:structure_schemas&gt;',
            $result,
        );

        // case 2 with isView as true and false

        $dbiDummy->addResult(
            'SELECT `DEFAULT_CHARACTER_SET_NAME`, `DEFAULT_COLLATION_NAME`'
            . " FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME` = 'd<\\\"b' LIMIT 1",
            [['utf-8', 'utf8_general_ci']],
            ['DEFAULT_CHARACTER_SET_NAME', 'DEFAULT_COLLATION_NAME'],
        );
        $dbiDummy->addResult('SHOW CREATE TABLE `d<"b`.`t1`', [['t1', '"tbl"']]);
        $dbiDummy->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'d<\"b\' AND TABLE_NAME = \'t1\'',
            [],
        );
        $dbiDummy->addResult('SHOW CREATE TABLE `d<"b`.`t2`', [['t2', '"tbl"']]);
        $dbiDummy->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'d<\"b\' AND TABLE_NAME = \'t2\'',
            [],
        );

        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['xml_export_triggers' => 'On']);

        $this->object->setExportOptions($request, new Export());

        $this->object->setTables(['t1', 't2']);

        ob_start();
        $this->object->exportHeader();
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString(
            '&lt;pma:structure_schemas&gt;' . "\n" .
            '        &lt;pma:database name=&quot;d&amp;lt;&amp;quot;b&quot; collat' .
            'ion=&quot;utf8_general_ci&quot; charset=&quot;utf-8&quot;&gt;' . "\n" .
            '        &lt;/pma:database&gt;' . "\n" .
            '    &lt;/pma:structure_schemas&gt;',
            $result,
        );
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testExportFooter(): void
    {
        $this->expectOutputString('&lt;/pma_xml_export&gt;');
        $this->object->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['xml_export_contents' => 'On']);

        $this->object->setExportOptions($request, new Export());

        ob_start();
        $this->object->exportDBHeader('&db');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('&lt;database name=&quot;&amp;amp;db&quot;&gt;', $result);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([]);

        $this->object->setExportOptions($request, new Export());

        $this->object->exportDBHeader('&db');
    }

    public function testExportDBFooter(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['xml_export_contents' => 'On']);

        $this->object->setExportOptions($request, new Export());

        ob_start();
        $this->object->exportDBFooter('&db');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('&lt;/database&gt;', $result);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([]);

        $this->object->setExportOptions($request, new Export());

        $this->object->exportDBFooter('&db');
    }

    public function testExportDBCreate(): void
    {
        $this->expectNotToPerformAssertions();
        $this->object->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        OutputHandler::$asFile = true;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['xml_export_contents' => 'On']);

        $this->object->setExportOptions($request, new Export());

        ob_start();
        $this->object->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame(
            '        <!-- Table test_table -->' . "\n"
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
            . '        </table>' . "\n",
            $result,
        );
    }
}
