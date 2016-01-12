<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerVariablesControllerTest class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\di\Container;
use PMA\libraries\Theme;

require_once 'libraries/url_generating.lib.php';

require_once 'libraries/database_interface.inc.php';
require_once 'test/libraries/stubs/ResponseStub.php';
require_once 'test/PMATestCase.php';

require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * Tests for ServerVariablesController class
 *
 * @package PhpMyAdmin-test
 */
class ServerVariablesControllerTest extends PMATestCase
{
    /**
     * @var \PMA\Test\Stubs\Response
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
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['server'] = 1;
        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when ServerStatusData constructs
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
                null,
                0,
                $server_session_variable
            ),
            array(
                "SHOW GLOBAL VARIABLES;",
                0,
                1,
                null,
                0,
                $server_global_variables
            )
        );

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $this->_response = new \PMA\Test\Stubs\Response();
        $container->set('PMA\libraries\Response', $this->_response);
        $container->alias('response', 'PMA\libraries\Response');
    }

    /**
     * Test for _formatVariable()
     *
     * @return void
     */
    public function testFormatVariable()
    {
        $class = new ReflectionClass(
            '\PMA\libraries\controllers\server\ServerVariablesController'
        );
        $method = $class->getMethod('_formatVariable');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->factory(
            'PMA\libraries\controllers\server\ServerVariablesController'
        );
        $container->alias(
            'ServerVariablesController',
            'PMA\libraries\controllers\server\ServerVariablesController'
        );
        $ctrl = $container->get('ServerVariablesController');

        //Call the test function
        $name_for_value_byte = "binlog_cache_size";
        $name_for_value_not_byte = "auto_increment_increment";
        $name_for_value_not_num = "PMA_key";

        //name is_numeric and the value type is byte
        $this->assertEquals(
            '<abbr title="3">3 B</abbr>',
            $method->invoke($ctrl, $name_for_value_byte, "3")
        );

        //name is_numeric and the value type is not byte
        $this->assertEquals(
            '3',
            $method->invoke($ctrl, $name_for_value_not_byte, "3")
        );

        //value is not a number
        $this->assertEquals(
            'value',
            $method->invoke($ctrl, $name_for_value_not_num, "value")
        );
    }

    /**
     * Test for _getHtmlForLinkTemplates()
     *
     * @return void
     */
    public function testGetHtmlForLinkTemplates()
    {
        $class = new ReflectionClass(
            '\PMA\libraries\controllers\server\ServerVariablesController'
        );
        $method = $class->getMethod('_getHtmlForLinkTemplates');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->factory(
            'PMA\libraries\controllers\server\ServerVariablesController'
        );
        $container->alias(
            'ServerVariablesController',
            'PMA\libraries\controllers\server\ServerVariablesController'
        );
        $ctrl = $container->get('ServerVariablesController');

        //Call the test function
        $html = $method->invoke($ctrl);
        $url = 'server_variables.php' . PMA_URL_getCommon(array());

        //validate 1: URL
        $this->assertContains(
            $url,
            $html
        );
        //validate 2: images
        $this->assertContains(
            PMA\libraries\Util::getIcon('b_save.png', __('Save')),
            $html
        );
        $this->assertContains(
            PMA\libraries\Util::getIcon('b_close.png', __('Cancel')),
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
            '\PMA\libraries\controllers\server\ServerVariablesController'
        );
        $method = $class->getMethod('_getHtmlForServerVariables');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->factory(
            'PMA\libraries\controllers\server\ServerVariablesController'
        );
        $container->alias(
            'ServerVariablesController',
            'PMA\libraries\controllers\server\ServerVariablesController'
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
            '<table id="serverVariables" class="data filteredData noclick">',
            $html
        );
        $this->assertContains(
            __('Variable'),
            $html
        );
        $this->assertContains(
            __('Global value'),
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
            '\PMA\libraries\controllers\server\ServerVariablesController'
        );
        $method = $class->getMethod('_getHtmlForServerVariablesItems');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->factory(
            'PMA\libraries\controllers\server\ServerVariablesController'
        );
        $container->alias(
            'ServerVariablesController',
            'PMA\libraries\controllers\server\ServerVariablesController'
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

        $value = $formatVariable->invoke($ctrl, $name, "12");
        $this->assertContains(
            $value,
            $html
        );

        //validate 3: variables
        $this->assertContains(
            __('Session value'),
            $html
        );

        $value = $formatVariable->invoke($ctrl, $name, "13");
        $this->assertContains(
            $value,
            $html
        );
    }
}
