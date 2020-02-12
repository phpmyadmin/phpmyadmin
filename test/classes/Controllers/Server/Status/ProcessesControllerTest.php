<?php
/**
 * Holds ProcessesControllerTest
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\ProcessesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;
use function htmlspecialchars;

class ProcessesControllerTest extends TestCase
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

        $serverStatus = [
            'Aborted_clients' => '0',
            'Aborted_connects' => '0',
            'Com_delete_multi' => '0',
            'Com_create_function' => '0',
            'Com_empty_query' => '0',
        ];

        $serverVariables = [
            'auto_increment_increment' => '1',
            'auto_increment_offset' => '1',
            'automatic_sp_privileges' => 'ON',
            'back_log' => '50',
            'big_tables' => 'OFF',
        ];

        $fetchResult = [
            [
                'SHOW GLOBAL STATUS',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverStatus,
            ],
            [
                'SHOW GLOBAL VARIABLES',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverVariables,
            ],
            [
                "SELECT concat('Com_', variable_name), variable_value "
                . 'FROM data_dictionary.GLOBAL_STATEMENTS',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                0,
                $serverStatus,
            ],
        ];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;

        $this->data = new Data();
    }

    public function testIndex(): void
    {
        $response = new Response();

        $controller = new ProcessesController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            $this->data
        );

        $controller->index([
            'showExecuting' => null,
            'full' => null,
            'column_name' => null,
            'order_by_field' => null,
            'sort_order' => null,
        ]);
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'Note: Enabling the auto refresh here might cause '
            . 'heavy traffic between the web server and the MySQL server.',
            $html
        );
        // Test tab links
        $this->assertStringContainsString(
            '<div class="tabLinks row">',
            $html
        );
        $this->assertStringContainsString(
            '<a id="toggleRefresh" href="#">',
            $html
        );
        $this->assertStringContainsString(
            'play',
            $html
        );
        $this->assertStringContainsString(
            'Start auto refresh',
            $html
        );
        $this->assertStringContainsString(
            '<select id="id_refreshRate"',
            $html
        );
        $this->assertStringContainsString(
            '<option value="5" selected>',
            $html
        );
        $this->assertStringContainsString(
            '5 seconds',
            $html
        );

        $this->assertStringContainsString(
            '<table id="tableprocesslist" '
            . 'class="data clearfloat noclick sortable">',
            $html
        );
        $this->assertStringContainsString(
            '<th>Processes</th>',
            $html
        );
        $this->assertStringContainsString(
            'Show full queries',
            $html
        );
        $this->assertStringContainsString(
            'index.php?route=/server/status/processes',
            $html
        );

        $controller->index([
            'showExecuting' => null,
            'full' => '1',
            'column_name' => 'Database',
            'order_by_field' => 'db',
            'sort_order' => 'ASC',
        ]);
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'Truncate shown queries',
            $html
        );
        $this->assertStringContainsString(
            'Database',
            $html
        );
        $this->assertStringContainsString(
            'DESC',
            $html
        );

        $controller->index([
            'showExecuting' => null,
            'full' => '1',
            'column_name' => 'Host',
            'order_by_field' => 'Host',
            'sort_order' => 'DESC',
        ]);
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'Host',
            $html
        );
        $this->assertStringContainsString(
            'ASC',
            $html
        );
    }

    public function testRefresh(): void
    {
        $process = [
            'User' => 'User1',
            'Host' => 'Host1',
            'Id' => 'Id1',
            'db' => 'db1',
            'Command' => 'Command1',
            'Info' => 'Info1',
            'State' => 'State1',
            'Time' => 'Time1',
        ];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 12;
        $GLOBALS['dbi']->expects($this->any())->method('fetchAssoc')
            ->will($this->onConsecutiveCalls($process));

        $response = new Response();
        $response->setAjax(true);

        $controller = new ProcessesController(
            $response,
            $GLOBALS['dbi'],
            new Template(),
            $this->data
        );

        $controller->refresh([
            'showExecuting' => null,
            'full' => '1',
            'column_name' => null,
            'order_by_field' => 'process',
            'sort_order' => 'DESC',
        ]);
        $html = $response->getHTMLResult();

        $this->assertStringContainsString(
            'index.php?route=/server/status/processes',
            $html
        );
        $killProcess = 'data-post="'
            . Url::getCommon(['kill' => $process['Id']], '') . '"';
        $this->assertStringContainsString(
            $killProcess,
            $html
        );
        $this->assertStringContainsString(
            'ajax kill_process',
            $html
        );
        $this->assertStringContainsString(
            __('Kill'),
            $html
        );

        //validate 2: $process['User']
        $this->assertStringContainsString(
            htmlspecialchars($process['User']),
            $html
        );

        //validate 3: $process['Host']
        $this->assertStringContainsString(
            htmlspecialchars($process['Host']),
            $html
        );

        //validate 4: $process['db']
        $this->assertStringContainsString(
            __('None'),
            $html
        );

        //validate 5: $process['Command']
        $this->assertStringContainsString(
            htmlspecialchars($process['Command']),
            $html
        );

        //validate 6: $process['Time']
        $this->assertStringContainsString(
            $process['Time'],
            $html
        );

        //validate 7: $process['state']
        $this->assertStringContainsString(
            $process['State'],
            $html
        );

        //validate 8: $process['info']
        $this->assertStringContainsString(
            $process['Info'],
            $html
        );
    }
}
