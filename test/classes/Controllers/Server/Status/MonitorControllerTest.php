<?php
/**
 * Holds MonitorControllerTest
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\MonitorController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

/**
 * @package PhpMyAdmin\Tests\Controllers\Server\Status
 */
class MonitorControllerTest extends TestCase
{
    /** @var Data */
    private $data;

    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['replication_info']['master']['status'] = true;
        $GLOBALS['replication_info']['slave']['status'] = true;
        $GLOBALS['replication_types'] = [];
        $GLOBALS['pmaThemeImage'] = '';

        $this->data = new Data();
    }

    public function testIndex(): void
    {
        $controller = new MonitorController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Template(),
            $this->data,
            new Monitor($GLOBALS['dbi'])
        );

        $html = $controller->index();

        $this->assertStringContainsString(
            '<div class="tabLinks row">',
            $html
        );
        $this->assertStringContainsString(
            __('Start Monitor'),
            $html
        );
        $this->assertStringContainsString(
            __('Settings'),
            $html
        );
        $this->assertStringContainsString(
            __('Done dragging (rearranging) charts'),
            $html
        );

        $this->assertStringContainsString(
            '<div class="popupContent settingsPopup">',
            $html
        );
        $this->assertStringContainsString(
            '<a href="#settingsPopup" class="popupLink">',
            $html
        );
        $this->assertStringContainsString(
            __('Enable charts dragging'),
            $html
        );
        $this->assertStringContainsString(
            '<option>3</option>',
            $html
        );

        $this->assertStringContainsString(
            __('Monitor Instructions'),
            $html
        );
        $this->assertStringContainsString(
            'monitorInstructionsDialog',
            $html
        );

        $this->assertStringContainsString(
            '<div id="addChartDialog"',
            $html
        );
        $this->assertStringContainsString(
            '<div id="chartVariableSettings">',
            $html
        );
        $this->assertStringContainsString(
            '<option>Processes</option>',
            $html
        );
        $this->assertStringContainsString(
            '<option>Connections</option>',
            $html
        );

        $this->assertStringContainsString(
            '<form id="js_data" class="hide">',
            $html
        );
        $this->assertStringContainsString(
            '<input type="hidden" name="server_time"',
            $html
        );
        //validate 2: inputs
        $this->assertStringContainsString(
            '<input type="hidden" name="is_superuser"',
            $html
        );
        $this->assertStringContainsString(
            '<input type="hidden" name="server_db_isLocal"',
            $html
        );
        $this->assertStringContainsString(
            '<div id="explain_docu" class="hide">',
            $html
        );
    }

    public function testLogDataTypeSlow(): void
    {
        $response = Response::getInstance();
        $response->setAjax(true);

        $controller = new MonitorController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            $this->data,
            new Monitor($GLOBALS['dbi'])
        );

        $ret = $controller->logDataTypeSlow([
            'time_start' => '0',
            'time_end' => '10',
        ]);

        $resultRows = [
            [
                'sql_text' => 'insert sql_text',
                '#' => 11,
            ],
            [
                'sql_text' => 'update sql_text',
                '#' => 10,
            ],
        ];
        $resultSum = [
            'insert' => 11,
            'TOTAL' => 21,
            'update' => 10,
        ];
        $this->assertEquals(
            2,
            $ret['message']['numRows']
        );
        $this->assertEquals(
            $resultRows,
            $ret['message']['rows']
        );
        $this->assertEquals(
            $resultSum,
            $ret['message']['sum']
        );
    }

    public function testLogDataTypeGeneral(): void
    {
        $value = [
            'sql_text' => 'insert sql_text',
            '#' => 10,
            'argument' => 'argument argument2',
        ];

        $value2 = [
            'sql_text' => 'update sql_text',
            '#' => 11,
            'argument' => 'argument3 argument4',
        ];

        $response = Response::getInstance();
        $response->setAjax(true);

        $controller = new MonitorController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            $this->data,
            new Monitor($GLOBALS['dbi'])
        );

        $ret = $controller->logDataTypeGeneral([
            'time_start' => '0',
            'time_end' => '10',
            'limitTypes' => '1',
            'removeVariables' => null,
        ]);

        $resultRows = [
            $value,
            $value2,
        ];
        $resultSum = [
            'argument' => 10,
            'TOTAL' => 21,
            'argument3' => 11,
        ];

        $this->assertEquals(
            2,
            $ret['message']['numRows']
        );
        $this->assertEquals(
            $resultRows,
            $ret['message']['rows']
        );
        $this->assertEquals(
            $resultSum,
            $ret['message']['sum']
        );
    }

    public function testLoggingVars(): void
    {
        $value = [
            'general_log' => 'OFF',
            'log_output' => 'FILE',
            'long_query_time' => '10.000000',
            'slow_query_log' => 'OFF',
        ];

        $response = Response::getInstance();
        $response->setAjax(true);

        $controller = new MonitorController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            $this->data,
            new Monitor($GLOBALS['dbi'])
        );

        $ret = $controller->loggingVars([
            'varName' => 'varName',
            'varValue' => null,
        ]);

        $this->assertEquals(
            $value,
            $ret['message']
        );
    }

    public function testQueryAnalyzer(): void
    {
        global $cached_affected_rows;

        $cached_affected_rows = 'cached_affected_rows';
        Util::cacheSet('profiling_supported', true);

        $value = [
            'sql_text' => 'insert sql_text',
            '#' => 10,
            'argument' => 'argument argument2',
        ];

        $response = Response::getInstance();
        $response->setAjax(true);

        $controller = new MonitorController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            $this->data,
            new Monitor($GLOBALS['dbi'])
        );

        $ret = $controller->queryAnalyzer([
            'database' => 'database',
            'query' => 'query',
        ]);

        $this->assertEquals(
            'cached_affected_rows',
            $ret['message']['affectedRows']
        );
        $this->assertEquals(
            [],
            $ret['message']['profiling']
        );
        $this->assertEquals(
            [$value],
            $ret['message']['explain']
        );
    }
}
