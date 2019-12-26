<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Replication helpers
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

if (! defined('PHPMYADMIN')) {
    exit;
}

use PhpMyAdmin\Replication;

$replication = new Replication();

/**
 * get master replication from server
 */
$server_master_replication = $GLOBALS['dbi']->fetchResult('SHOW MASTER STATUS');

/**
 * set selected master server
 */
if (! empty($_POST['master_connection'])) {
    /**
     * check for multi-master replication functionality
     */
    $server_slave_multi_replication = $GLOBALS['dbi']->fetchResult(
        'SHOW ALL SLAVES STATUS'
    );
    if ($server_slave_multi_replication) {
        $GLOBALS['dbi']->query(
            "SET @@default_master_connection = '"
            . $GLOBALS['dbi']->escapeString(
                $_POST['master_connection']
            ) . "'"
        );
        $GLOBALS['url_params']['master_connection'] = $_POST['master_connection'];
    }
}

/**
 * get slave replication from server
 */
$server_slave_replication = $GLOBALS['dbi']->fetchResult('SHOW SLAVE STATUS');

/**
 * replication types
 */
$replication_types = [
    'master',
    'slave',
];


/**
 * define variables for master status
 */
$master_variables = [
    'File',
    'Position',
    'Binlog_Do_DB',
    'Binlog_Ignore_DB',
];

/**
 * Define variables for slave status
 */
$slave_variables  = [
    'Slave_IO_State',
    'Master_Host',
    'Master_User',
    'Master_Port',
    'Connect_Retry',
    'Master_Log_File',
    'Read_Master_Log_Pos',
    'Relay_Log_File',
    'Relay_Log_Pos',
    'Relay_Master_Log_File',
    'Slave_IO_Running',
    'Slave_SQL_Running',
    'Replicate_Do_DB',
    'Replicate_Ignore_DB',
    'Replicate_Do_Table',
    'Replicate_Ignore_Table',
    'Replicate_Wild_Do_Table',
    'Replicate_Wild_Ignore_Table',
    'Last_Errno',
    'Last_Error',
    'Skip_Counter',
    'Exec_Master_Log_Pos',
    'Relay_Log_Space',
    'Until_Condition',
    'Until_Log_File',
    'Until_Log_Pos',
    'Master_SSL_Allowed',
    'Master_SSL_CA_File',
    'Master_SSL_CA_Path',
    'Master_SSL_Cert',
    'Master_SSL_Cipher',
    'Master_SSL_Key',
    'Seconds_Behind_Master',
];
/**
 * define important variables, which need to be watched for
 * correct running of replication in slave mode
 *
 * @usedby PhpMyAdmin\ReplicationGui->getHtmlForReplicationStatusTable()
 */
// TODO change to regexp or something, to allow for negative match.
// To e.g. highlight 'Last_Error'
//
$slave_variables_alerts = [
    'Slave_IO_Running' => 'No',
    'Slave_SQL_Running' => 'No',
];
$slave_variables_oks = [
    'Slave_IO_Running' => 'Yes',
    'Slave_SQL_Running' => 'Yes',
];

// check which replication is available and
// set $server_{master/slave}_status and assign values

// replication info is more easily passed to functions
$GLOBALS['replication_info'] = [];

foreach ($replication_types as $type) {
    if (count(${"server_{$type}_replication"}) > 0) {
        $GLOBALS['replication_info'][$type]['status'] = true;
    } else {
        $GLOBALS['replication_info'][$type]['status'] = false;
    }
    if ($GLOBALS['replication_info'][$type]['status']) {
        if ($type == "master") {
            $replication->fillInfo(
                $type,
                'Do_DB',
                $server_master_replication[0],
                'Binlog_Do_DB'
            );

            $replication->fillInfo(
                $type,
                'Ignore_DB',
                $server_master_replication[0],
                'Binlog_Ignore_DB'
            );
        } elseif ($type == "slave") {
            $replication->fillInfo(
                $type,
                'Do_DB',
                $server_slave_replication[0],
                'Replicate_Do_DB'
            );

            $replication->fillInfo(
                $type,
                'Ignore_DB',
                $server_slave_replication[0],
                'Replicate_Ignore_DB'
            );

            $replication->fillInfo(
                $type,
                'Do_Table',
                $server_slave_replication[0],
                'Replicate_Do_Table'
            );

            $replication->fillInfo(
                $type,
                'Ignore_Table',
                $server_slave_replication[0],
                'Replicate_Ignore_Table'
            );

            $replication->fillInfo(
                $type,
                'Wild_Do_Table',
                $server_slave_replication[0],
                'Replicate_Wild_Do_Table'
            );

            $replication->fillInfo(
                $type,
                'Wild_Ignore_Table',
                $server_slave_replication[0],
                'Replicate_Wild_Ignore_Table'
            );
        }
    }
}
