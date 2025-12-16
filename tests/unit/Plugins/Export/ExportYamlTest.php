<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
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
class ExportYamlTest extends AbstractTestCase
{
    protected ExportYaml $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        OutputHandler::$asFile = false;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = 'en';
        $relation = new Relation($dbi);
        $this->object = new ExportYaml($relation, new OutputHandler(), new Transformations($dbi, $relation));
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportYaml::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportYaml::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

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
            'general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);
    }

    public function testExportHeader(): void
    {
        ob_start();
        self::assertTrue(
            $this->object->exportHeader(),
        );
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("%YAML 1.1\n---\n", $result);
    }

    public function testExportFooter(): void
    {
        $this->expectOutputString("...\n");
        self::assertTrue(
            $this->object->exportFooter(),
        );
    }

    public function testExportDBHeader(): void
    {
        self::assertTrue(
            $this->object->exportDBHeader('&db'),
        );
    }

    public function testExportDBFooter(): void
    {
        self::assertTrue(
            $this->object->exportDBFooter('&db'),
        );
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue(
            $this->object->exportDBCreate('testDB'),
        );
    }

    public function testExportData(): void
    {
        ob_start();
        self::assertTrue(
            $this->object->exportData(
                'test_db',
                'test_table',
                'SELECT * FROM `test_db`.`test_table_yaml`;',
            ),
        );
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
}
