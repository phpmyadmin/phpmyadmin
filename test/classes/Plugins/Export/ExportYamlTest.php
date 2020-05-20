<?php
/**
 * tests for PhpMyAdmin\Plugins\Export\ExportYaml class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportYaml;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionMethod;
use ReflectionProperty;
use function array_shift;
use function ob_get_clean;
use function ob_start;

/**
 * tests for PhpMyAdmin\Plugins\Export\ExportYaml class
 *
 * @group medium
 */
class ExportYamlTest extends PmaTestCase
{
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
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
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportYaml::setProperties
     *
     * @return void
     */
    public function testSetProperties()
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

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportYaml::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        $this->assertStringContainsString(
            "%YAML 1.1\n---\n",
            $result
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportYaml::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $this->expectOutputString(
            "...\n"
        );
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportYaml::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $this->assertTrue(
            $this->object->exportDBHeader('&db')
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportYaml::exportDBFooter
     *
     * @return void
     */
    public function testExportDBFooter()
    {
        $this->assertTrue(
            $this->object->exportDBFooter('&db')
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportYaml::exportDBCreate
     *
     * @return void
     */
    public function testExportDBCreate()
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportYaml::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(4));

        $dbi->expects($this->at(2))
            ->method('fieldName')
            ->will($this->returnValue('fName1'));

        $dbi->expects($this->at(3))
            ->method('fieldName')
            ->will($this->returnValue('fNa"me2'));

        $dbi->expects($this->at(4))
            ->method('fieldName')
            ->will($this->returnValue('fNa\\me3'));

        $dbi->expects($this->at(5))
            ->method('fieldName')
            ->will($this->returnValue('fName4'));

        $dbi->expects($this->at(6))
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    [
                        null,
                        '123',
                        "\"c\\a\nb\r",
                    ]
                )
            );

        $dbi->expects($this->at(7))
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    [null]
                )
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
            '-' . "\n",
            $result
        );
    }
}
