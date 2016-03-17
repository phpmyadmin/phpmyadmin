<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\export\ExportYaml class
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\plugins\export\ExportYaml;

require_once 'libraries/export.lib.php';
require_once 'libraries/config.default.php';
require_once 'libraries/database_interface.inc.php';
require_once 'export.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\export\ExportYaml class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportYamlTest extends PMATestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
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
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportYaml::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportYaml', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PMA\libraries\plugins\export\ExportYaml', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PMA\libraries\properties\plugins\ExportPluginProperties',
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
            'PMA\libraries\properties\options\groups\OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();

        $generalOptions = array_shift($generalOptionsArray);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\HiddenPropertyItem',
            $property
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportYaml::exportHeader
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

        $this->assertContains(
            "%YAML 1.1\n---\n",
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportYaml::exportFooter
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
     * Test for PMA\libraries\plugins\export\ExportYaml::exportDBHeader
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
     * Test for PMA\libraries\plugins\export\ExportYaml::exportDBFooter
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
     * Test for PMA\libraries\plugins\export\ExportYaml::exportDBCreate
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
     * Test for PMA\libraries\plugins\export\ExportYaml::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
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
                    array(null, '123', "\"c\\a\nb\r")
                )
            );

        $dbi->expects($this->at(7))
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    array(null)
                )
            );

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'db', 'ta<ble', "\n", "example.com", "SELECT"
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
