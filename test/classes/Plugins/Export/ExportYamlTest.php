<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Plugins\Export\ExportYaml;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;

use function array_shift;
use function ob_get_clean;
use function ob_start;

/**
 * @group medium
 */
class ExportYamlTest extends AbstractTestCase
{
    /** @var ExportYaml */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['cfgRelation']['relation'] = true;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = '';
        $this->object = new ExportYaml();
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
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportYaml::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            ExportPluginProperties::class,
            $properties
        );

        $this->assertEquals(
            'YAML',
            $properties->getText()
        );

        $this->assertEquals(
            'yml',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/yaml',
            $properties->getMimeType()
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

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            HiddenPropertyItem::class,
            $property
        );
    }

    public function testExportHeader(): void
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "%YAML 1.1\n---\n",
            $result
        );
    }

    public function testExportFooter(): void
    {
        $this->expectOutputString(
            "...\n"
        );
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    public function testExportDBHeader(): void
    {
        $this->assertTrue(
            $this->object->exportDBHeader('&db')
        );
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('&db')
        );
    }

    public function testExportDBCreate(): void
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    public function testExportData(): void
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'test_db',
                'test_table',
                "\n",
                'localhost',
                'SELECT * FROM `test_db`.`test_table_yaml`;'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '# test_db.test_table' . "\n" .
            '-' . "\n" .
            '  id: 1' . "\n" .
            '  name: &quot;abcd&quot;' . "\n" .
            '  datetimefield: &quot;2011-01-20 02:00:02&quot;' . "\n" .
            '-' . "\n" .
            '  id: 2' . "\n" .
            '  name: &quot;foo&quot;' . "\n" .
            '  datetimefield: &quot;2010-01-20 02:00:02&quot;' . "\n" .
            '-' . "\n" .
            '  id: 3' . "\n" .
            '  name: &quot;Abcd&quot;' . "\n" .
            '  datetimefield: &quot;2012-01-20 02:00:02&quot;' . "\n" .
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
            $result
        );
    }
}
