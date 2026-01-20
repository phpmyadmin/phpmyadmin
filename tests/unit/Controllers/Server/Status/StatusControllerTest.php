<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\Status\StatusController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Replication\Replication;
use PhpMyAdmin\Replication\ReplicationGui;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StatusController::class)]
class StatusControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['host'] = 'localhost';
    }

    public function testIndex(): void
    {
        $bytesReceived = 100;
        $bytesSent = 200;
        $maxUsedConnections = 500;
        $abortedConnections = 200;
        $connections = 1000;

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $dummyDbi->addResult('SHOW GLOBAL STATUS', [
            ['Uptime' , '36000'],
            ['Bytes_received' , $bytesReceived],
            ['Bytes_sent' , $bytesSent],
            ['Max_used_connections' ,$maxUsedConnections],
            ['Aborted_connects' , $abortedConnections],
            ['Connections' , $connections],
            ['Aborted_clients', '0'],
            ['Com_delete_multi', '0'],
            ['Com_create_function', '0'],
            ['Com_empty_query', '0'],
        ], ['Variable_name', 'Value']);

        $data = new Data($dbi, Config::getInstance());

        $response = new ResponseRenderer();
        $template = new Template();

        $controller = new StatusController(
            $response,
            $template,
            $data,
            new ReplicationGui(new Replication(DatabaseInterface::getInstance()), $template),
            DatabaseInterface::getInstance(),
        );

        $replicationInfo = $data->getReplicationInfo();
        $replicationInfo->primaryVariables = [];
        $replicationInfo->replicaVariables = [];

        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $html = $response->getHTMLResult();

        $traffic = $bytesReceived + $bytesSent;
        $trafficHtml = 'Network traffic since startup: ' . $traffic . ' B';
        self::assertStringContainsString($trafficHtml, $html);
        //updatetime
        $upTimeHtml = 'This MySQL server has been running for 0 days, 10 hours, 0 minutes and 0 seconds';
        self::assertStringContainsString($upTimeHtml, $html);
        //primary state
        $primaryHtml = 'This MySQL server works as <b>primary</b>';
        self::assertStringContainsString($primaryHtml, $html);

        //validate 2: Status::getHtmlForServerStateTraffic
        $trafficHtml = '<table class="table table-striped table-hover col-12 col-md-5 w-auto">';
        self::assertStringContainsString($trafficHtml, $html);
        //traffic hint
        $trafficHtml = 'On a busy server, the byte counters may overrun';
        self::assertStringContainsString($trafficHtml, $html);
        //$bytes_received
        self::assertStringContainsString('<td class="font-monospace text-end">' . $bytesReceived . ' B', $html);
        //$bytes_sent
        self::assertStringContainsString('<td class="font-monospace text-end">' . $bytesSent . ' B', $html);

        //validate 3: Status::getHtmlForServerStateConnections
        self::assertStringContainsString('<th scope="col">Connections</th>', $html);
        self::assertStringContainsString('<th class="text-end" scope="col">ø per hour</th>', $html);
        self::assertStringContainsString(
            '<table class="table table-striped table-hover col-12 col-md-6 w-auto">',
            $html,
        );
        self::assertStringContainsString('<th>Max. concurrent connections</th>', $html);
        //Max_used_connections
        self::assertStringContainsString('<td class="font-monospace text-end">' . $maxUsedConnections, $html);
        self::assertStringContainsString('<th>Failed attempts</th>', $html);
        //Aborted_connects
        self::assertStringContainsString('<td class="font-monospace text-end">' . $abortedConnections, $html);
        self::assertStringContainsString('<th>Aborted</th>', $html);
    }
}
