<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Identifiers\TriggerName;
use PhpMyAdmin\Plugins\Export\ExportTexytext;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Triggers\Event;
use PhpMyAdmin\Triggers\Timing;
use PhpMyAdmin\Triggers\Trigger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportTexytext::class)]
#[Medium]
final class ExportTexytextTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = false;
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = 'en';
    }

    public function testSetProperties(): void
    {
        $exportTexytext = $this->getExportTexytext();

        $method = new ReflectionMethod(ExportTexytext::class, 'setProperties');
        $method->invoke($exportTexytext, null);

        $attrProperties = new ReflectionProperty(ExportTexytext::class, 'properties');
        $properties = $attrProperties->getValue($exportTexytext);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'Texy! text',
            $properties->getText(),
        );

        self::assertSame(
            'txt',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/plain',
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
            'texytext_general_opts',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Dump table',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(RadioPropertyItem::class, $property);

        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'texytext_data',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'texytext_columns',
            $property->getName(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'texytext_null',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        $exportTexytext = $this->getExportTexytext();
        $this->expectNotToPerformAssertions();
        $exportTexytext->exportHeader();
    }

    public function testExportFooter(): void
    {
        $exportTexytext = $this->getExportTexytext();
        $this->expectNotToPerformAssertions();
        $exportTexytext->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $exportTexytext = $this->getExportTexytext();
        $this->expectOutputString("===Database testDb\n\n");
        $exportTexytext->exportDBHeader('testDb');
    }

    public function testExportDBFooter(): void
    {
        $exportTexytext = $this->getExportTexytext();
        $this->expectNotToPerformAssertions();
        $exportTexytext->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportTexytext = $this->getExportTexytext();
        $this->expectNotToPerformAssertions();
        $exportTexytext->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $exportTexytext = $this->getExportTexytext();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['texytext_columns' => 'On']);

        $exportTexytext->setExportOptions($request, new Export());

        ob_start();
        $exportTexytext->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame(
            '== Dumping data for table test_table' . "\n\n"
                . '|------' . "\n"
                . '|id|name|datetimefield' . "\n"
                . '|------' . "\n"
                . '|1|abcd|2011-01-20 02:00:02' . "\n"
                . '|2|foo|2010-01-20 02:00:02' . "\n"
                . '|3|Abcd|2012-01-20 02:00:02' . "\n",
            $result,
        );
    }

    public function testGetTableDefStandIn(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $exportTexytext = $this->getExportTexytext($this->createDatabaseInterface($dbiDummy));

        $dbiDummy->addSelectDb('test_db');
        $result = $exportTexytext->getTableDefStandIn('test_db', 'test_table');
        $dbiDummy->assertAllSelectsConsumed();

        self::assertSame(
            '|------' . "\n"
            . '|Column|Type|Null|Default' . "\n"
            . '|------' . "\n"
            . '|//**id**//|int(11)|No|NULL' . "\n"
            . '|name|varchar(20)|No|NULL' . "\n"
            . '|datetimefield|datetime|No|NULL' . "\n",
            $result,
        );
    }

    public function testGetTableDef(): void
    {
        // case 1
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $keys = [['Non_unique' => 0, 'Column_name' => 'cname'], ['Non_unique' => 1, 'Column_name' => 'cname2']];

        $dbi->expects(self::once())
            ->method('getTableIndexes')
            ->with('db', 'table')
            ->willReturn($keys);

        $dbi->expects(self::exactly(2))
            ->method('fetchResult')
            ->willReturn(
                ['fname' => ['foreign_table' => '<ftable', 'foreign_field' => 'ffield>']],
                ['fname' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
            );

        $dbi->expects(self::once())
            ->method('fetchValue')
            ->willReturn('SELECT a FROM b');

        $columnFull = new Column('fname', '', null, false, '', null, '', '', 'comm');

        $dbi->expects(self::exactly(2))
            ->method('getColumns')
            ->willReturnMap([
                ['db', 'table', ConnectionType::User, [$columnFull]],
            ]);

        $exportTexytext = $this->getExportTexytext($dbi);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['texytext_relation' => 'On', 'texytext_mime' => 'On', 'texytext_comments' => 'On']);

        $exportTexytext->setExportOptions($request, new Export());

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::REL_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $result = $exportTexytext->getTableDef('db', 'table');

        $expected = <<<'TXT'
            |------
            |Column|Type|Null|Default|Links to|Comments|Media type
            |------
            |fname|&amp;nbsp;|No||&lt;ftable (ffield&gt;)|comm|Test&lt;

            TXT;
        self::assertSame($expected, $result);
    }

    public function testGetTriggers(): void
    {
        $triggers = [
            new Trigger(
                TriggerName::from('tna"me'),
                Timing::Before,
                Event::Delete,
                TableName::from('ta<ble'),
                'def',
                'test_user@localhost',
            ),
        ];

        $exportTexytext = $this->getExportTexytext();
        $result = $exportTexytext->getTriggers($triggers);

        self::assertStringContainsString('|tna"me|BEFORE|DELETE|def', $result);

        self::assertStringContainsString('|Name|Time|Event|Definition', $result);
    }

    public function testExportStructure(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dbiDummy = $this->createDbiDummy();
        $exportTexytext = $this->getExportTexytext($this->createDatabaseInterface($dbiDummy));

        // case 1
        ob_start();
        $dbiDummy->addSelectDb('test_db');
        $exportTexytext->exportStructure('test_db', 'test_table', 'create_table');
        $dbiDummy->assertAllSelectsConsumed();
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame(
            '== Table structure for table test_table' . "\n\n"
            . '|------' . "\n"
            . '|Column|Type|Null|Default' . "\n"
            . '|------' . "\n"
            . '|//**id**//|int(11)|No|NULL' . "\n"
            . '|name|varchar(20)|No|NULL' . "\n"
            . '|datetimefield|datetime|No|NULL' . "\n",
            $result,
        );

        // case 2
        ob_start();
        $exportTexytext->exportStructure('test_db', 'test_table', 'triggers');
        $result = ob_get_clean();

        self::assertSame(
            '== Triggers test_table' . "\n\n"
            . '|------' . "\n"
            . '|Name|Time|Event|Definition' . "\n"
            . '|------' . "\n"
            . '|test_trigger|AFTER|INSERT|BEGIN END' . "\n",
            $result,
        );

        // case 3
        ob_start();
        $dbiDummy->addSelectDb('test_db');
        $exportTexytext->exportStructure('test_db', 'test_table', 'create_view');
        $dbiDummy->assertAllSelectsConsumed();
        $result = ob_get_clean();

        self::assertSame(
            '== Structure for view test_table' . "\n\n"
            . '|------' . "\n"
            . '|Column|Type|Null|Default' . "\n"
            . '|------' . "\n"
            . '|//**id**//|int(11)|No|NULL' . "\n"
            . '|name|varchar(20)|No|NULL' . "\n"
            . '|datetimefield|datetime|No|NULL' . "\n",
            $result,
        );

        // case 4
        ob_start();
        $dbiDummy->addSelectDb('test_db');
        $exportTexytext->exportStructure('test_db', 'test_table', 'stand_in');
        $dbiDummy->assertAllSelectsConsumed();
        $result = ob_get_clean();

        self::assertSame(
            '== Stand-in structure for view test_table' . "\n\n"
            . '|------' . "\n"
            . '|Column|Type|Null|Default' . "\n"
            . '|------' . "\n"
            . '|//**id**//|int(11)|No|NULL' . "\n"
            . '|name|varchar(20)|No|NULL' . "\n"
            . '|datetimefield|datetime|No|NULL' . "\n",
            $result,
        );
    }

    public function testFormatOneColumnDefinition(): void
    {
        $cols = new Column('field', 'set(abc)enum123', null, true, 'PRI', null, '', '', '');

        $uniqueKeys = ['field'];

        $exportTexytext = $this->getExportTexytext();

        self::assertSame(
            '|//**field**//|set(abc)|Yes|NULL',
            $exportTexytext->formatOneColumnDefinition($cols, $uniqueKeys),
        );

        $cols = new Column('fields', '', null, false, 'COMP', 'def', '', '', '');

        $uniqueKeys = ['field'];

        self::assertSame(
            '|fields|&amp;nbsp;|No|def',
            $exportTexytext->formatOneColumnDefinition($cols, $uniqueKeys),
        );
    }

    private function getExportTexytext(DatabaseInterface|null $dbi = null): ExportTexytext
    {
        $dbi ??= $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportTexytext($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
