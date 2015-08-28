<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ExportPhparray class
 *
 * @package PhpMyAdmin-test
 */
require_once 'libraries/plugins/export/ExportPhparray.class.php';
require_once 'libraries/DatabaseInterface.class.php';
require_once 'libraries/export.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';
require_once 'export.php';
/**
 * tests for ExportPhparray class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class PMA_ExportPhparray_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $this->object = new ExportPhparray();
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
     * Test for ExportPhparray::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('ExportPhparray', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('ExportPhparray', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'PHP array',
            $properties->getText()
        );

        $this->assertEquals(
            'php',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/plain',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            'OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(
            'OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'HiddenPropertyItem',
            $property
        );
    }

    /**
     * Test for ExportPhparray::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $GLOBALS['crlf'] = ' ';

        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        $this->assertContains(
            '<?php ',
            $result
        );
    }

    /**
     * Test for ExportPhparray::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for ExportPhparray::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $GLOBALS['crlf'] = ' ';

        ob_start();
        $this->assertTrue(
            $this->object->exportDBHeader("db")
        );
        $result = ob_get_clean();

        $this->assertContains(
            '// Database `db` ',
            $result
        );
    }

    /**
     * Test for ExportPhparray::exportDBFooter
     *
     * @return void
     */
    public function testExportDBFooter()
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    /**
     * Test for ExportPhparray::exportDBCreate
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
     * Test for ExportPhparray::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', null, PMA_DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(2));

        $dbi->expects($this->at(2))
            ->method('fieldName')
            ->with(true, 0)
            ->will($this->returnValue('c1'));

        $dbi->expects($this->at(3))
            ->method('fieldName')
            ->with(true, 1)
            ->will($this->returnValue(''));

        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array(1, 'a')));

        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'db', 'table', "\n", 'phpmyadmin.net/err', 'SELECT'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            "\n" . '// `db`.`table`' . "\n" .
            '$table = array('  . "\n" .
            '  array(\'c1\' => 1,\'\' => \'a\')' . "\n" .
            ');' . "\n",
            $result
        );

        // case 2: test invalid variable name fix
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', null, PMA_DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(0));

        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'db', '0`932table', "\n", 'phpmyadmin.net/err', 'SELECT'
            )
        );
        $result = ob_get_clean();

        $this->assertContains(
            '$_0_932table',
            $result
        );
    }
}
