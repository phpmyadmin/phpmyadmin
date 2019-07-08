<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds StatusControllerTest
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\StatusController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Replication;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PHPUnit\Framework\TestCase;

/**
 * Class StatusControllerTest
 * @package PhpMyAdmin\Tests\Controllers\Server\Status
 */
class StatusControllerTest extends TestCase
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
            'Com_execute_sql' => 2,
            'Com_stmt_execute' => 2,
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
                . "FROM data_dictionary.GLOBAL_STATEMENTS",
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

        $dbi->expects($this->at(0))
            ->method('tryQuery')
            ->with('SHOW GLOBAL STATUS')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->will($this->returnValue(['Aborted_clients', '0']));
        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->will($this->returnValue(['Aborted_connects', '0']));
        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->will($this->returnValue(['Com_delete_multi', '0']));
        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->will($this->returnValue(['Com_create_function', '0']));
        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->will($this->returnValue(['Com_empty_query', '0']));
        $dbi->expects($this->at(6))
            ->method('fetchRow')
            ->will($this->returnValue(false));

        $dbi->expects($this->at(7))->method('freeResult');

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $data = new Data();

        $bytesReceived = 100;
        $bytesSent = 200;
        $maxUsedConnections = 500;
        $abortedConnections = 200;
        $connections = 1000;
        $data->status['Uptime'] = 36000;
        $data->status['Bytes_received'] = $bytesReceived;
        $data->status['Bytes_sent'] = $bytesSent;
        $data->status['Max_used_connections'] = $maxUsedConnections;
        $data->status['Aborted_connects'] = $abortedConnections;
        $data->status['Connections'] = $connections;

        $controller = new StatusController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Template(),
            $data
        );

        $html = $controller->index(new ReplicationGui(new Replication(), new Template()));

        $traffic = $bytesReceived + $bytesSent;
        $trafficHtml = 'Network traffic since startup: ' . $traffic . ' B';
        $this->assertStringContainsString(
            $trafficHtml,
            $html
        );
        //updatetime
        $upTimeHtml = 'This MySQL server has been running for '
            . '0 days, 10 hours, 0 minutes and 0 seconds';
        $this->assertStringContainsString(
            $upTimeHtml,
            $html
        );
        //master state
        $masterHtml = 'This MySQL server works as <b>master</b>';
        $this->assertStringContainsString(
            $masterHtml,
            $html
        );

        //validate 2: Status::getHtmlForServerStateTraffic
        $trafficHtml = '<table id="serverstatustraffic" class="width100 data noclick">';
        $this->assertStringContainsString(
            $trafficHtml,
            $html
        );
        //traffic hint
        $trafficHtml = 'On a busy server, the byte counters may overrun';
        $this->assertStringContainsString(
            $trafficHtml,
            $html
        );
        //$bytes_received
        $this->assertStringContainsString(
            '<td class="value">' . $bytesReceived . ' B',
            $html
        );
        //$bytes_sent
        $this->assertStringContainsString(
            '<td class="value">' . $bytesSent . ' B',
            $html
        );

        //validate 3: Status::getHtmlForServerStateConnections
        $this->assertStringContainsString(
            '<th>Connections</th>',
            $html
        );
        $this->assertStringContainsString(
            '<th>&oslash; per hour</th>',
            $html
        );
        $this->assertStringContainsString(
            '<table id="serverstatusconnections" class="width100 data noclick">',
            $html
        );
        $this->assertStringContainsString(
            '<th class="name">Max. concurrent connections</th>',
            $html
        );
        //Max_used_connections
        $this->assertStringContainsString(
            '<td class="value">' . $maxUsedConnections,
            $html
        );
        $this->assertStringContainsString(
            '<th class="name">Failed attempts</th>',
            $html
        );
        //Aborted_connects
        $this->assertStringContainsString(
            '<td class="value">' . $abortedConnections,
            $html
        );
        $this->assertStringContainsString(
            '<th class="name">Aborted</th>',
            $html
        );
    }
}
