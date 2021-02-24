<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Replication;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\Template;

/**
 * PhpMyAdmin\Tests\ReplicationGuiTest class
 *
 * this class is for testing PhpMyAdmin\ReplicationGui methods
 */
class ReplicationGuiTest extends AbstractTestCase
{
    /**
     * ReplicationGui instance
     *
     * @var ReplicationGui
     */
    private $replicationGui;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        //$_POST
        $_POST['mr_adduser'] = 'mr_adduser';

        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = 'server';
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = [];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['ShowHint'] = true;

        $GLOBALS['table'] = 'table';
        $GLOBALS['server'] = 0;
        $GLOBALS['url_params'] = [];

        $this->replicationGui = new ReplicationGui(new Replication(), new Template());
    }

    /**
     * Test for getHtmlForMasterReplication
     *
     * @group medium
     */
    public function testGetHtmlForMasterReplication(): void
    {
        $html = $this->replicationGui->getHtmlForMasterReplication();

        //validate 1: Master replication
        $this->assertStringContainsString(
            '<div class="card-header">Master replication</div>',
            $html
        );
        $this->assertStringContainsString(
            'This server is configured as master in a replication process.',
            $html
        );

        //validate 2: getHtmlForReplicationStatusTable
        $this->assertStringContainsString(
            '<div id="replication_master_section"',
            $html
        );

        $this->assertStringContainsString(
            'Binlog_Do_DB',
            $html
        );
        $this->assertStringContainsString(
            'Binlog_Ignore_DB',
            $html
        );

        $this->assertStringContainsString(
            'master-bin.000030',
            $html
        );

        //validate 3: getHtmlForReplicationSlavesTable
        $this->assertStringContainsString(
            'replication_slaves_section',
            $html
        );
        $this->assertStringContainsString(
            '<th>Server ID</th>',
            $html
        );
        $this->assertStringContainsString(
            '<th>Host</th>',
            $html
        );
        //slave host
        $this->assertStringContainsString(
            '<td class="value">Server_id1</td>',
            $html
        );
        $this->assertStringContainsString(
            '<td class="value">Server_id2</td>',
            $html
        );
        $this->assertStringContainsString(
            '<td class="value">Host1</td>',
            $html
        );
        $this->assertStringContainsString(
            '<td class="value">Host2</td>',
            $html
        );
        //Notice
        $this->assertStringContainsString(
            'Only slaves started with the',
            $html
        );

        //validate 4: navigation URL
        $this->assertStringContainsString(
            '<a href="index.php?route=/server/replication',
            $html
        );
        $this->assertStringContainsString(
            'Add slave replication user',
            $html
        );

        //validate 5: 'Add replication slave user' form
        $this->assertStringContainsString(
            '<div id="master_addslaveuser_gui">',
            $html
        );
    }

    /**
     * Test for getHtmlForSlaveConfiguration
     */
    public function testGetHtmlForSlaveConfiguration(): void
    {
        $replicationInfo = new ReplicationInfo($GLOBALS['dbi']);
        $replicationInfo->load();

        //Call the test function
        $html = $this->replicationGui->getHtmlForSlaveConfiguration(
            true,
            $replicationInfo->getReplicaStatus()
        );

        //legend
        $this->assertStringContainsString(
            '<div class="card-header">Slave replication</div>',
            $html
        );
        $this->assertStringContainsString(
            '<div id="slave_configuration_gui">',
            $html
        );
        //notice
        $this->assertStringContainsString(
            'Server is configured as slave in a replication process.',
            $html
        );
        //slave session
        $this->assertStringContainsString(
            '<div id="replication_slave_section"',
            $html
        );
        //variable
        $this->assertStringContainsString(
            'Master_SSL_CA_Path',
            $html
        );
        $this->assertStringContainsString(
            'Master_SSL_Cert',
            $html
        );
        $this->assertStringContainsString(
            'Master_SSL_Cipher',
            $html
        );
        $this->assertStringContainsString(
            'Seconds_Behind_Master',
            $html
        );
    }

    /**
     * Test for getHtmlForReplicationChangeMaster
     */
    public function testGetHtmlForReplicationChangeMaster(): void
    {
        //Call the test function
        $html = $this->replicationGui->getHtmlForReplicationChangeMaster(
            'slave_changemaster'
        );

        $this->assertStringContainsString(
            '<form method="post" action="index.php?route=/server/replication',
            $html
        );
        $this->assertStringContainsString(
            'Slave configuration',
            $html
        );
        $this->assertStringContainsString(
            'Change or reconfigure master server',
            $html
        );
        $notice = 'Make sure you have a unique server-id '
            . 'in your configuration file (my.cnf)';
        $this->assertStringContainsString(
            $notice,
            $html
        );
    }
}
