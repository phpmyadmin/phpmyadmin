<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportYaml;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

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
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = false;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['cfgRelation']['relation'] = true;
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
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $a->type = '';
        $flags[] = $a;
        $b = new stdClass();
        $b->type = '';
        $flags[] = $b;
        $c = new stdClass();
        $c->type = '';
        $flags[] = $c;
        $d = new stdClass();
        $d->type = 'string';
        $flags[] = $d;
        $e = new stdClass();
        $e->type = 'string';
        $flags[] = $e;

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(5));

        $dbi->expects($this->exactly(5))
            ->method('fieldName')
            ->willReturn('fName1', 'fNa"me2', 'fNa\\me3', 'fName4', 'fName5');

        $dbi->expects($this->exactly(3))
            ->method('fetchRow')
            ->willReturn(
                [
                    null,
                    '123',
                    "\"c\\a\nb\r",
                    '123',
                    '+30.2103210000',
                ],
                [null],
                null
            );

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'db',
                'ta<ble',
                "\n",
                'example.com',
                'SELECT'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            '# db.ta&lt;ble' . "\n" .
            '-' . "\n" .
            '  fNa&quot;me2: 123' . "\n" .
            '  fName3: &quot;\&quot;c\\\\a\nb\r&quot;' . "\n" .
            '  fName4: &quot;123&quot;' . "\n" .
            '  fName5: &quot;+30.2103210000&quot;' . "\n" .
            '-' . "\n",
            $result
        );
    }
}
