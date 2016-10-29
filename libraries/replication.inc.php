<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Replication helpers
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * get master replication from server
 */
$server_master_replication = $GLOBALS['dbi']->fetchResult('SHOW MASTER STATUS');

/**
 * set selected master server
 */
if (! empty($_REQUEST['master_connection'])) {
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
                $_REQUEST['master_connection']
            ) . "'"
        );
        $GLOBALS['url_params']['master_connection'] = $_REQUEST['master_connection'];
    }
}

/**
 * get slave replication from server
 */
$server_slave_replication = $GLOBALS['dbi']->fetchResult('SHOW SLAVE STATUS');

/**
 * replication types
 */
$replication_types = array('master', 'slave');


/**
 * define variables for master status
 */
$master_variables = array(
    'File',
    'Position',
    'Binlog_Do_DB',
    'Binlog_Ignore_DB',
);

/**
 * Define variables for slave status
 */
$slave_variables  = array(
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
);
/**
 * define important variables, which need to be watched for
 * correct running of replication in slave mode
 *
 * @usedby PMA_getHtmlForReplicationStatusTable()
 */
// TODO change to regexp or something, to allow for negative match.
// To e.g. highlight 'Last_Error'
//
$slave_variables_alerts = array(
    'Slave_IO_Running' => 'No',
    'Slave_SQL_Running' => 'No',
);
$slave_variables_oks = array(
    'Slave_IO_Running' => 'Yes',
    'Slave_SQL_Running' => 'Yes',
);

// check which replication is available and
// set $server_{master/slave}_status and assign values

// replication info is more easily passed to functions
$GLOBALS['replication_info'] = array();

foreach ($replication_types as $type) {
    if (count(${"server_{$type}_replication"}) > 0) {
        $GLOBALS['replication_info'][$type]['status'] = true;
    } else {
        $GLOBALS['replication_info'][$type]['status'] = false;
    }
    if ($GLOBALS['replication_info'][$type]['status']) {
        if ($type == "master") {
            PMA_fillReplicationInfo(
                $type, 'Do_DB', $server_master_replication[0],
                'Binlog_Do_DB'
            );

            PMA_fillReplicationInfo(
                $type, 'Ignore_DB', $server_master_replication[0],
                'Binlog_Ignore_DB'
            );
        } elseif ($type == "slave") {
            PMA_fillReplicationInfo(
                $type, 'Do_DB', $server_slave_replication[0],
                'Replicate_Do_DB'
            );

            PMA_fillReplicationInfo(
                $type, 'Ignore_DB', $server_slave_replication[0],
                'Replicate_Ignore_DB'
            );

            PMA_fillReplicationInfo(
                $type, 'Do_Table', $server_slave_replication[0],
                'Replicate_Do_Table'
            );

            PMA_fillReplicationInfo(
                $type, 'Ignore_Table', $server_slave_replication[0],
                'Replicate_Ignore_Table'
            );

            PMA_fillReplicationInfo(
                $type, 'Wild_Do_Table', $server_slave_replication[0],
                'Replicate_Wild_Do_Table'
            );

            PMA_fillReplicationInfo(
                $type, 'Wild_Ignore_Table', $server_slave_replication[0],
                'Replicate_Wild_Ignore_Table'
            );
        }
    }
}

/**
 * Fill global replication_info variable.
 *
 * @param string $type               Type: master, slave
 * @param string $replicationInfoKey Key in replication_info variable
 * @param array  $mysqlInfo          MySQL data about replication
 * @param string $mysqlKey           MySQL key
 *
 * @return array
 */
function PMA_fillReplicationInfo(
    $type, $replicationInfoKey, $mysqlInfo, $mysqlKey
) {
    $GLOBALS['replication_info'][$type][$replicationInfoKey]
        = empty($mysqlInfo[$mysqlKey])
            ? array()
            : explode(
                ",",
                $mysqlInfo[$mysqlKey]
            );

    return $GLOBALS['replication_info'][$type][$replicationInfoKey];
}

/**
 * Extracts database or table name from string
 *
 * @param string $string contains "dbname.tablename"
 * @param string $what   what to extract (db|table)
 *
 * @return string the extracted part
 */
function PMA_extractDbOrTable($string, $what = 'db')
{
    $list = explode(".", $string);
    if ('db' == $what) {
        return $list[0];
    } else {
        return $list[1];
    }
}

/**
 * Configures replication slave
 *
 * @param string $action  possible values: START or STOP
 * @param string $control default: null,
 *                        possible values: SQL_THREAD or IO_THREAD or null.
 *                        If it is set to null, it controls both
 *                        SQL_THREAD and IO_THREAD
 * @param mixed  $link    mysql link
 *
 * @return mixed output of DatabaseInterface::tryQuery
 */
function PMA_Replication_Slave_control($action, $control = null, $link = null)
{
    $action = mb_strtoupper($action);
    $control = mb_strtoupper($control);

    if ($action != "START" && $action != "STOP") {
        return -1;
    }
    if ($control != "SQL_THREAD" && $control != "IO_THREAD" && $control != null) {
        return -1;
    }

    return $GLOBALS['dbi']->tryQuery($action . " SLAVE " . $control . ";", $link);
}

/**
 * Changes master for replication slave
 *
 * @param string $user     replication user on master
 * @param string $password password for the user
 * @param string $host     master's hostname or IP
 * @param int    $port     port, where mysql is running
 * @param array  $pos      position of mysql replication,
 *                         array should contain fields File and Position
 * @param bool   $stop     shall we stop slave?
 * @param bool   $start    shall we start slave?
 * @param mixed  $link     mysql link
 *
 * @return string output of CHANGE MASTER mysql command
 */
function PMA_Replication_Slave_changeMaster($user, $password, $host, $port,
    $pos, $stop = true, $start = true, $link = null
) {
    if ($stop) {
        PMA_Replication_Slave_control("STOP", null, $link);
    }

    $out = $GLOBALS['dbi']->tryQuery(
        'CHANGE MASTER TO ' .
        'MASTER_HOST=\'' . $host . '\',' .
        'MASTER_PORT=' . ($port * 1) . ',' .
        'MASTER_USER=\'' . $user . '\',' .
        'MASTER_PASSWORD=\'' . $password . '\',' .
        'MASTER_LOG_FILE=\'' . $pos["File"] . '\',' .
        'MASTER_LOG_POS=' . $pos["Position"] . ';', $link
    );

    if ($start) {
        PMA_Replication_Slave_control("START", null, $link);
    }

    return $out;
}

/**
 * This function provides connection to remote mysql server
 *
 * @param string $user     mysql username
 * @param string $password password for the user
 * @param string $host     mysql server's hostname or IP
 * @param int    $port     mysql remote port
 * @param string $socket   path to unix socket
 *
 * @return mixed $link mysql link on success
 */
function PMA_Replication_connectToMaster(
    $user, $password, $host = null, $port = null, $socket = null
) {
    $server = array();
    $server["host"] = PMA_sanitizeMySQLHost($host);
    $server["port"] = $port;
    $server["socket"] = $socket;

    // 5th parameter set to true means that it's an auxiliary connection
    // and we must not go back to login page if it fails
    return $GLOBALS['dbi']->connect($user, $password, false, $server, true);
}
/**
 * Fetches position and file of current binary log on master
 *
 * @param mixed $link mysql link
 *
 * @return array an array containing File and Position in MySQL replication
 * on master server, useful for PMA_Replication_Slave_changeMaster
 */
function PMA_Replication_Slave_binLogMaster($link = null)
{
    $data = $GLOBALS['dbi']->fetchResult('SHOW MASTER STATUS', null, null, $link);
    $output = array();

    if (! empty($data)) {
        $output["File"] = $data[0]["File"];
        $output["Position"] = $data[0]["Position"];
    }
    return $output;
}
