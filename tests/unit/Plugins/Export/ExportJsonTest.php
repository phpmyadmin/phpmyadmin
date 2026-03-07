<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Plugins\Export\ExportJson;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(ExportJson::class)]
#[Medium]
final class ExportJsonTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = true;
    }

    public function testSetProperties(): void
    {
        $exportJson = $this->getExportJson();

        $method = new ReflectionMethod(ExportJson::class, 'setProperties');
        $method->invoke($exportJson, null);

        $attrProperties = new ReflectionProperty(ExportJson::class, 'properties');
        $properties = $attrProperties->getValue($exportJson);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'JSON',
            $properties->getText(),
        );

        self::assertSame(
            'json',
            $properties->getExtension(),
        );

        self::assertSame(
            'application/json',
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

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'json_general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertSame(
            'json_structure_or_data',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        $exportJson = $this->getExportJson();
        $this->expectOutputString(
            "[\n"
            . '{"type":"header","version":"' . Version::VERSION
            . '","comment":"Export to JSON plugin for phpMyAdmin"},'
            . "\n",
        );
        $exportJson->exportHeader();
    }

    public function testExportFooter(): void
    {
        $exportJson = $this->getExportJson();
        $this->expectOutputString(']' . "\n");
        $exportJson->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $exportJson = $this->getExportJson();
        $this->expectOutputString('{"type":"database","name":"testDB"},' . "\n");
        $exportJson->exportDBHeader('testDB');
    }

    public function testExportDBFooter(): void
    {
        $exportJson = $this->getExportJson();
        $this->expectNotToPerformAssertions();
        $exportJson->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportJson = $this->getExportJson();
        $this->expectNotToPerformAssertions();
        $exportJson->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $exportJson = $this->getExportJson();
        $this->expectOutputString(
            '{"type":"table","name":"test_table","database":"test_db","data":' . "\n"
            . '[' . "\n"
            . '{"id":"1","name":"abcd","datetimefield":"2011-01-20 02:00:02"},' . "\n"
            . '{"id":"2","name":"foo","datetimefield":"2010-01-20 02:00:02"},' . "\n"
            . '{"id":"3","name":"Abcd","datetimefield":"2012-01-20 02:00:02"}' . "\n"
            . ']' . "\n"
            . '}' . "\n",
        );

        $exportJson->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
    }

    public function testExportComplexData(): void
    {
        $exportJson = $this->getExportJson();
        // normalString binaryField textField blobField
        $this->expectOutputString(
            '{"type":"table","name":"test_table_complex","database":"test_db","data":'
            . "\n[\n"
            . '{"f1":"\"\'\"><iframe onload=alert(1)>\u0448\u0435\u043b\u043b\u044b",'
                . '"f2":"0x3078313233343638353766656665",'
                . '"f3":"My awesome\nText","f4":"0x307861663132333466363863353766656665"},' . "\n"
            . '{"f1":null,"f2":null,"f3":null,"f4":null},' . "\n"
            . '{"f1":"","f2":"0x307831","f3":"\u0448\u0435\u043b\u043b\u044b","f4":"0x307832"}' . "\n"
            . "]\n}\n",
        );

        $exportJson->exportData('test_db', 'test_table_complex', 'SELECT * FROM `test_db`.`test_table_complex`;');
    }

    public function testExportRawComplexData(): void
    {
        $exportJson = $this->getExportJson();
        $this->expectOutputString(
            '{"type":"raw","data":'
            . "\n[\n"
            . '{"f1":"\"\'\"><iframe onload=alert(1)>\u0448\u0435\u043b\u043b\u044b",'
                . '"f2":"0x3078313233343638353766656665",'
                . '"f3":"My awesome\nText","f4":"0x307861663132333466363863353766656665"},' . "\n"
            . '{"f1":null,"f2":null,"f3":null,"f4":null},' . "\n"
            . '{"f1":"","f2":"0x307831","f3":"\u0448\u0435\u043b\u043b\u044b","f4":"0x307832"}' . "\n"
            . "]\n}\n",
        );

        $exportJson->exportRawQuery('', 'SELECT * FROM `test_db`.`test_table_complex`;');
    }

    private function getExportJson(): ExportJson
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportJson($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
