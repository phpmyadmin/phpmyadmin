<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Replication;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Replication\Replication;
use PhpMyAdmin\Replication\ReplicationGui;
use PhpMyAdmin\Replication\ReplicationInfo;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

#[CoversClass(ReplicationGui::class)]
#[Medium]
class ReplicationGuiTest extends AbstractTestCase
{
    /**
     * ReplicationGui instance
     */
    private ReplicationGui $replicationGui;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        //$_POST
        $_POST['primary_add_user'] = 'primary_add_user';

        $config = Config::getInstance();
        $config->settings['RememberSorting'] = true;
        $config->settings['SQP'] = [];
        $config->settings['MaxCharactersInDisplayedSQL'] = 1000;
        $config->settings['ShowSQL'] = true;
        $config->settings['TableNavigationLinksMode'] = 'icons';
        $config->settings['LimitChars'] = 100;
        $config->settings['ShowHint'] = true;

        Current::$table = 'table';
        UrlParams::$params = [];

        $this->replicationGui = new ReplicationGui(new Replication(DatabaseInterface::getInstance()), new Template());
    }

    public function testGetHtmlForPrimaryReplication(): void
    {
        $html = $this->replicationGui->getHtmlForPrimaryReplication(null, false, 'primary_add_user', null, null);

        //validate 1: Primary replication
        self::assertStringContainsString('<div class="card-header">Primary replication</div>', $html);
        self::assertStringContainsString('This server is configured as primary in a replication process.', $html);

        //validate 2: getHtmlForReplicationStatusTable
        self::assertStringContainsString('<div id="replication_primary_section"', $html);

        self::assertStringContainsString('Binlog_Do_DB', $html);
        self::assertStringContainsString('Binlog_Ignore_DB', $html);

        self::assertStringContainsString('primary-bin.000030', $html);

        //validate 3: getHtmlForReplicationReplicasTable
        self::assertStringContainsString('replication_replicas_section', $html);
        self::assertStringContainsString('<th>Server ID</th>', $html);
        self::assertStringContainsString('<th>Host</th>', $html);
        //replica host
        self::assertStringContainsString('<td class="text-end font-monospace">Server_id1</td>', $html);
        self::assertStringContainsString('<td class="text-end font-monospace">Server_id2</td>', $html);
        self::assertStringContainsString('<td class="text-end font-monospace">Host1</td>', $html);
        self::assertStringContainsString('<td class="text-end font-monospace">Host2</td>', $html);
        //Notice
        self::assertStringContainsString('Only replicas started with the', $html);

        //validate 4: navigation URL
        self::assertStringContainsString('<a href="index.php?route=/server/replication', $html);
        self::assertStringContainsString('Add replica replication user', $html);

        //validate 5: 'Add replication replica user' form
        self::assertStringContainsString('<div id="primary_addreplicauser_gui">', $html);
    }

    public function testGetHtmlForReplicaConfiguration(): void
    {
        $replicationInfo = new ReplicationInfo(DatabaseInterface::getInstance());
        $replicationInfo->load();

        //Call the test function
        $html = $this->replicationGui->getHtmlForReplicaConfiguration(
            null,
            true,
            $replicationInfo->getReplicaStatus(),
            isset($_POST['replica_configure']),
        );

        //legend
        self::assertStringContainsString('<div class="card-header">Replica replication</div>', $html);
        self::assertStringContainsString('<div id="replica_configuration_gui">', $html);
        //notice
        self::assertStringContainsString('Server is configured as replica in a replication process.', $html);
        //replica session
        self::assertStringContainsString('<div id="replication_replica_section"', $html);
        //variable
        self::assertStringContainsString('Master_SSL_CA_Path', $html);
        self::assertStringContainsString('Master_SSL_Cert', $html);
        self::assertStringContainsString('Master_SSL_Cipher', $html);
        self::assertStringContainsString('Seconds_Behind_Master', $html);
    }

    public function testGetHtmlForReplicationChangePrimary(): void
    {
        //Call the test function
        $html = $this->replicationGui->getHtmlForReplicationChangePrimary('replica_changeprimary');

        self::assertStringContainsString('<form method="post" action="index.php?route=/server/replication', $html);
        self::assertStringContainsString('Replica configuration', $html);
        self::assertStringContainsString('Change or reconfigure primary server', $html);
        $notice = 'Make sure you have a unique server-id in your configuration file (my.cnf)';
        self::assertStringContainsString($notice, $html);
    }
}
