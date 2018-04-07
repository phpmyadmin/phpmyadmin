<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerVariablesControllerTest class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use ReflectionClass;

/**
 * Tests for ServerVariablesController class
 *
 * @package PhpMyAdmin-test
 */
class ServerVariablesControllerTest extends PmaTestCase
{
    /**
     * @var \PhpMyAdmin\Tests\Stubs\Response
     */
    private $_response;

    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when PhpMyAdmin\Server\Status\Data constructs
        $server_session_variable = array(
            "auto_increment_increment" => "1",
            "auto_increment_offset" => "13",
            "automatic_sp_privileges" => "ON",
            "back_log" => "50",
            "big_tables" => "OFF",
        );

        $server_global_variables = array(
            "auto_increment_increment" => "0",
            "auto_increment_offset" => "12"
        );

        $fetchResult = array(
            array(
                "SHOW SESSION VARIABLES;",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $server_session_variable
            ),
            array(
                "SHOW GLOBAL VARIABLES;",
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $server_global_variables
            )
        );

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $this->_response = new ResponseStub();
        $container->set('PhpMyAdmin\Response', $this->_response);
        $container->alias('response', 'PhpMyAdmin\Response');
    }

    /**
     * Test for _formatVariable()
     *
     * @return void
     */
    public function testFormatVariable()
    {
        $class = new ReflectionClass(
            '\PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $method = $class->getMethod('_formatVariable');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->factory(
            'PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $container->alias(
            'ServerVariablesController',
            'PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $ctrl = $container->get('ServerVariablesController');

        //Call the test function
        $name_for_value_byte = "binlog_cache_size";
        $name_for_value_not_byte = "auto_increment_increment";
        $name_for_value_not_num = "PMA_key";

        //name is_numeric and the value type is byte
        $args = array($name_for_value_byte, "3");
        list($formattedValue, $isHtmlFormatted) = $method->invokeArgs($ctrl, $args);
        $this->assertEquals(
            '<abbr title="3">3 B</abbr>',
            $formattedValue
        );
        $this->assertEquals(true, $isHtmlFormatted);

        //name is_numeric and the value type is not byte
        $args = array($name_for_value_not_byte, "3");
        list($formattedValue, $isHtmlFormatted) = $method->invokeArgs($ctrl, $args);
        $this->assertEquals(
            '3',
            $formattedValue
        );
        $this->assertEquals(false, $isHtmlFormatted);

        //value is not a number
        $args = array($name_for_value_not_byte, "value");
        list($formattedValue, $isHtmlFormatted) = $method->invokeArgs($ctrl, $args);
        $this->assertEquals(
            'value',
            $formattedValue
        );
        $this->assertEquals(false, $isHtmlFormatted);
    }

    /**
     * Test for _getHtmlForLinkTemplates()
     *
     * @return void
     */
    public function testGetHtmlForLinkTemplates()
    {
        $class = new ReflectionClass(
            '\PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $method = $class->getMethod('_getHtmlForLinkTemplates');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->factory(
            'PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $container->alias(
            'ServerVariablesController',
            'PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $ctrl = $container->get('ServerVariablesController');

        //Call the test function
        $html = $method->invoke($ctrl);
        $url = 'server_variables.php' . Url::getCommon();

        //validate 1: URL
        $this->assertContains(
            $url,
            $html
        );
        //validate 2: images
        $this->assertContains(
            Util::getIcon('b_save', __('Save')),
            $html
        );
        $this->assertContains(
            Util::getIcon('b_close', __('Cancel')),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForServerVariables()
     *
     * @return void
     */
    public function testPMAGetHtmlForServerVariables()
    {

        $class = new ReflectionClass(
            '\PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $method = $class->getMethod('_getHtmlForServerVariables');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->factory(
            'PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $container->alias(
            'ServerVariablesController',
            'PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $ctrl = $container->get('ServerVariablesController');

        $_REQUEST['filter'] = "auto-commit";
        $serverVarsSession
            = $GLOBALS['dbi']->fetchResult('SHOW SESSION VARIABLES;', 0, 1);
        $serverVars = $GLOBALS['dbi']->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);

        $html = $method->invoke($ctrl, $serverVars, $serverVarsSession);

        //validate 1: Filters
        $this->assertContains(
            '<legend>' . __('Filters') . '</legend>',
            $html
        );
        $this->assertContains(
            __('Containing the word:'),
            $html
        );
        $this->assertContains(
            $_REQUEST['filter'],
            $html
        );

        //validate 2: Server Variables
        $this->assertContains(
            '<table id="serverVariables" class="width100 data filteredData noclick">',
            $html
        );
        $this->assertContains(
            __('Variable'),
            $html
        );
        $this->assertContains(
            __('Value'),
            $html
        );
    }

    /**
     * Test for _getHtmlForServerVariablesItems()
     *
     * @return void
     */
    public function testGetHtmlForServerVariablesItems()
    {
        $class = new ReflectionClass(
            '\PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $method = $class->getMethod('_getHtmlForServerVariablesItems');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->factory(
            'PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $container->alias(
            'ServerVariablesController',
            'PhpMyAdmin\Controllers\Server\ServerVariablesController'
        );
        $ctrl = $container->get('ServerVariablesController');

        $serverVarsSession
            = $GLOBALS['dbi']->fetchResult('SHOW SESSION VARIABLES;', 0, 1);
        $serverVars = $GLOBALS['dbi']->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);

        $html = $method->invoke($ctrl, $serverVars, $serverVarsSession);

        //validate 1: variable: auto_increment_increment
        $name = "auto_increment_increment";
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        $this->assertContains(
            $value,
            $html
        );

        //validate 2: variable: auto_increment_offset
        $name = "auto_increment_offset";
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        $this->assertContains(
            $value,
            $html
        );

        $formatVariable = $class->getMethod('_formatVariable');
        $formatVariable->setAccessible(true);

        $args = array($name, "12");
        list($value, $isHtmlFormatted) = $formatVariable->invokeArgs($ctrl, $args);
        $this->assertContains(
            $value,
            $html
        );

        //validate 3: variables
        $this->assertContains(
            __('Session value'),
            $html
        );

        $args = array($name, "13");
        list($value, $isHtmlFormatted) = $formatVariable->invokeArgs($ctrl, $args);
        $this->assertContains(
            $value,
            $html
        );
    }
}
