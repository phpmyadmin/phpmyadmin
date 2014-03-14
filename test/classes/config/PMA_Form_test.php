<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Form class in config folder
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/config/ConfigFile.class.php';
require_once 'libraries/config/Form.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for PMA_Form class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Form_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var Form
     */
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $this->object = new Form(
            'pma_form_name', array('pma_form1','pma_form2'), new ConfigFile(), 1
        );
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for Form::__constructor
     *
     * @return void
     * @group medium
     */
    public function testContructor()
    {
        $this->assertEquals(
            1,
            $this->object->index
        );
        $this->assertEquals(
            'pma_form_name',
            $this->object->name
        );
        $this->assertArrayHasKey(
            'pma_form1',
            $this->object->fields
        );
    }

    /**
     * Test for Form::getOptionType
     *
     * @return void
     */
    public function testGetOptionType()
    {
        $attrFieldsTypes = new \ReflectionProperty('Form', '_fieldsTypes');
        $attrFieldsTypes->setAccessible(true);
        $attrFieldsTypes->setValue(
            $this->object,
            array("7" => "Seven")
        );

        $this->assertNull(
            $this->object->getOptionType("123/4/5/6")
        );

        $this->assertEquals(
            "Seven",
            $this->object->getOptionType("123/4/5/7")
        );
    }

    /**
     * Test for Form::getOptionValueList
     *
     * @return void
     */
    public function testGetOptionValueList()
    {
        $this->assertEquals(
            array('NHibernate C# DO', 'NHibernate XML'),
            $this->object->getOptionValueList("Export/codegen_format")
        );

        $this->assertEquals(
            array(
                'auto' => 'auto',
                '1' => 1,
                '0' => 0
            ),
            $this->object->getOptionValueList("OBGzip")
        );

        $this->assertEquals(
            array(
                'none' => 'Nowhere',
                'left' => 'Left',
                'right' => 'Right',
                'both' =>   "Both"
            ),
            $this->object->getOptionValueList("RowActionLinks")
        );
    }

    /**
     * Test for Form::_readFormPathsCallback
     *
     * @return void
     */
    public function testReadFormPathsCallBack()
    {
        $reflection = new \ReflectionClass('Form');
        $method = $reflection->getMethod('_readFormPathsCallback');
        $method->setAccessible(true);

        $array = array(
            "foo" => array(
                "bar" => array(
                    'test' => 1,
                    1 => ':group:end'
                )
            )
        );

        $method->invoke($this->object, $array, 'foo', 'pref');

        $result = $this->object->fields;

        $this->assertCount(
            4,
            $result
        );

        $this->assertEquals(
            "pma_form1",
            $result['pma_form1']
        );

        $this->assertEquals(
            "pma_form2",
            $result['pma_form2']
        );

        $this->assertEquals(
            "preffoo/foo/bar/test",
            $result[0]
        );

        // needs regexp because the counter is static

        $this->assertRegExp(
            '/^preffoo\/foo\/bar\/\:group\:end\:\d+$/',
            $result[1]
        );
    }

    /**
     * Test for Form::readFormPaths
     *
     * @return void
     */
    public function testReadFormPaths()
    {
        $reflection = new \ReflectionClass('Form');
        $method = $reflection->getMethod('readFormPaths');
        $method->setAccessible(true);

        $array = array(
            "foo" => array(
                "bar" => array(
                    'test' => 1,
                    1 => ':group:end'
                )
            )
        );

        $method->invoke($this->object, $array);

        $result = $this->object->fields;

        $this->assertCount(
            2,
            $result
        );

        $this->assertEquals(
            "foo/bar/test",
            $result['test']
        );

        unset($result['test']);

        // needs regexp because the counter is static

        $keys = array_keys($result);
        $key = $keys[0];

        $this->assertRegexp(
            "/^\:group\:end\:(\d+)$/",
            $key
        );

        preg_match("/^\:group\:end\:(\d+)$/", $key, $matches);
        $digit = $matches[1];

        $this->assertEquals(
            "foo/bar/:group:end:" . $digit,
            $result[':group:end:' . $digit]
        );
    }

    /**
     * Test for Form::readTypes
     *
     * @return void
     */
    public function testReadTypes()
    {
        $reflection = new \ReflectionClass('Form');
        $method = $reflection->getMethod('readTypes');
        $method->setAccessible(true);

        $this->object->fields = array(
            "pma_form1" => "Servers/1/port",
            "pma_form2" => "Servers/1/connect_type",
            ":group:end:0" => "preffoo/foo/bar/test",
            "1" => "preffoo/foo/bar/:group:end:0"
        );

        $attrFieldsTypes = $reflection->getProperty('_fieldsTypes');
        $attrFieldsTypes->setAccessible(true);

        $method->invoke($this->object, null);

        $this->assertEquals(
            array(
                "pma_form1" => "integer",
                "pma_form2" => "select",
                ":group:end:0" => "group",
                "1" => "NULL"
            ),
            $attrFieldsTypes->getValue($this->object)
        );
    }

    /**
     * Test for Form::loadForm
     *
     * @return void
     */
    public function testLoadForm()
    {
        $this->object = $this->getMockBuilder('Form')
            ->disableOriginalConstructor()
            ->setMethods(array('readFormPaths', 'readTypes'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('readFormPaths')
            ->with('testForm');

        $this->object->expects($this->exactly(1))
            ->method('readTypes');

        $this->object->loadForm('pmaform', 'testForm');

        $this->assertEquals(
            'pmaform',
            $this->object->name
        );
    }
}
?>
