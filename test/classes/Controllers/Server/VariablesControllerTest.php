<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds VariablesControllerTest class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\VariablesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Williamdes\MariaDBMySQLKBS\Search as KBSearch;
use Williamdes\MariaDBMySQLKBS\SlimData as KBSlimData;

/**
 * Tests for VariablesController class
 *
 * @package PhpMyAdmin-test
 */
class VariablesControllerTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when PhpMyAdmin\Server\Status\Data constructs
        $serverSessionVariables = [
            'auto_increment_increment' => '1',
            'auto_increment_offset' => '13',
            'automatic_sp_privileges' => 'ON',
            'back_log' => '50',
            'big_tables' => 'OFF',
        ];

        $serverGlobalVariables = [
            'auto_increment_increment' => '0',
            'auto_increment_offset' => '12',
        ];

        $fetchResult = [
            [
                'SHOW SESSION VARIABLES;',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverSessionVariables,
            ],
            [
                'SHOW GLOBAL VARIABLES;',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverGlobalVariables,
            ],
        ];

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $controller = new VariablesController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Template()
        );

        $html = $controller->index([]);

        $this->assertStringContainsString(
            'server_variables.php' . Url::getCommon(),
            $html
        );
        $this->assertStringContainsString(
            Util::getIcon('b_save', __('Save')),
            $html
        );
        $this->assertStringContainsString(
            Util::getIcon('b_close', __('Cancel')),
            $html
        );
        $this->assertStringContainsString(
            '<legend>' . __('Filters') . '</legend>',
            $html
        );
        $this->assertStringContainsString(
            __('Containing the word:'),
            $html
        );
        $this->assertStringContainsString(
            __('Variable'),
            $html
        );
        $this->assertStringContainsString(
            __('Value'),
            $html
        );

        $name = "auto_increment_increment";
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        $this->assertStringContainsString(
            $value,
            $html
        );
        $name = "auto_increment_offset";
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        $this->assertStringContainsString(
            $value,
            $html
        );
    }

    /**
     * Test for formatVariable()
     *
     * @return void
     */
    public function testFormatVariable(): void
    {
        $class = new ReflectionClass(VariablesController::class);
        $method = $class->getMethod('formatVariable');
        $method->setAccessible(true);

        $controller = new VariablesController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Template()
        );

        $nameForValueByte = 'byte_variable';
        $nameForValueNotByte = 'not_a_byte_variable';

        $slimData = new KBSlimData();
        $slimData->addVariable($nameForValueByte, 'byte', null);
        $slimData->addVariable($nameForValueNotByte, 'string', null);
        KBSearch::loadTestData($slimData);

        //name is_numeric and the value type is byte
        $args = [
            $nameForValueByte,
            '3',
        ];
        list($formattedValue, $isHtmlFormatted) = $method->invokeArgs($controller, $args);
        $this->assertEquals(
            '<abbr title="3">3 B</abbr>',
            $formattedValue
        );
        $this->assertEquals(true, $isHtmlFormatted);

        //name is_numeric and the value type is not byte
        $args = [
            $nameForValueNotByte,
            '3',
        ];
        list($formattedValue, $isHtmlFormatted) = $method->invokeArgs($controller, $args);
        $this->assertEquals(
            '3',
            $formattedValue
        );
        $this->assertEquals(false, $isHtmlFormatted);

        //value is not a number
        $args = [
            $nameForValueNotByte,
            'value',
        ];
        list($formattedValue, $isHtmlFormatted) = $method->invokeArgs($controller, $args);
        $this->assertEquals(
            'value',
            $formattedValue
        );
        $this->assertEquals(false, $isHtmlFormatted);
    }
}
