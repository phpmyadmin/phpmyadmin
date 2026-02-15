<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Plugins\Export\ExportYaml;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
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

#[CoversClass(ExportYaml::class)]
#[Medium]
final class ExportYamlTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = false;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = 'en';
    }

    public function testSetProperties(): void
    {
        $exportYaml = $this->getExportYaml();

        $method = new ReflectionMethod(ExportYaml::class, 'setProperties');
        $method->invoke($exportYaml, null);

        $attrProperties = new ReflectionProperty(ExportYaml::class, 'properties');
        $properties = $attrProperties->getValue($exportYaml);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'YAML',
            $properties->getText(),
        );

        self::assertSame(
            'yml',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/yaml',
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
            'yaml_general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);
    }

    public function testExportHeader(): void
    {
        $exportYaml = $this->getExportYaml();

        ob_start();
        $exportYaml->exportHeader();
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("%YAML 1.1\n---\n", $result);
    }

    public function testExportFooter(): void
    {
        $exportYaml = $this->getExportYaml();
        $this->expectOutputString("...\n");
        $exportYaml->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $exportYaml = $this->getExportYaml();
        $this->expectNotToPerformAssertions();
        $exportYaml->exportDBHeader('&db');
    }

    public function testExportDBFooter(): void
    {
        $exportYaml = $this->getExportYaml();
        $this->expectNotToPerformAssertions();
        $exportYaml->exportDBFooter('&db');
    }

    public function testExportDBCreate(): void
    {
        $exportYaml = $this->getExportYaml();
        $this->expectNotToPerformAssertions();
        $exportYaml->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $exportYaml = $this->getExportYaml();

        ob_start();
        $exportYaml->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table_yaml`;');
        $result = ob_get_clean();

        self::assertSame(
            '# test_db.test_table' . "\n" .
            '-' . "\n" .
            '  id: 1' . "\n" .
            '  name: &quot;abcd&quot;' . "\n" .
            '  datetimefield: &quot;2011-01-20 02:00:02&quot;' . "\n" .
            '  textfield: null' . "\n" .
            '-' . "\n" .
            '  id: 2' . "\n" .
            '  name: &quot;foo&quot;' . "\n" .
            '  datetimefield: &quot;2010-01-20 02:00:02&quot;' . "\n" .
            '  textfield: null' . "\n" .
            '-' . "\n" .
            '  id: 3' . "\n" .
            '  name: &quot;Abcd&quot;' . "\n" .
            '  datetimefield: &quot;2012-01-20 02:00:02&quot;' . "\n" .
            '  textfield: null' . "\n" .
            '-' . "\n" .
            '  id: 4' . "\n" .
            '  name: &quot;Abcd&quot;' . "\n" .
            '  datetimefield: &quot;2012-01-20 02:00:02&quot;' . "\n" .
            '  textfield: &quot;123&quot;' . "\n" .
            '-' . "\n" .
            '  id: 5' . "\n" .
            '  name: &quot;Abcd&quot;' . "\n" .
            '  datetimefield: &quot;2012-01-20 02:00:02&quot;' . "\n" .
            '  textfield: &quot;+30.2103210000&quot;' . "\n",
            $result,
        );
    }

    private function getExportYaml(): ExportYaml
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportYaml($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
