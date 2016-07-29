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
$server_master_replication = PMA_DBI_fetch_result('SHOW MASTER STATUS');

/**
 * get slave replication from server
 */
$server_slave_replication = PMA_DBI_fetch_result('SHOW SLAVE STATUS');

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
 * define important variables, which need to be watched for correct running of replication in slave mode
 *
 * @usedby PMA_replication_print_status_table()
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
/*
 * @todo use $replication_info everywhere instead of the generated variable names
 */
$replication_info = array();

foreach ($replication_types as $type) {
    if (count(${"server_{$type}_replication"}) > 0) {
        ${"server_{$type}_status"} = true;
        $replication_info[$type]['status'] = true;
    } else {
        ${"server_{$type}_status"} = false;
        $replication_info[$type]['status'] = false;
    }
    if (${"server_{$type}_status"}) {
        if ($type == "master") {
            ${"server_{$type}_Do_DB"} = explode(",", $server_master_replication[0]["Binlog_Do_DB"]);
            $replication_info[$type]['Do_DB'] = ${"server_{$type}_Do_DB"};

            ${"server_{$type}_Ignore_DB"} = explode(",", $server_master_replication[0]["Binlog_Ignore_DB"]);
            $replication_info[$type]['Ignore_DB'] = ${"server_{$type}_Ignore_DB"};
        } elseif ($type == "slave") {
            ${"server_{$type}_Do_DB"} = explode(",", $server_slave_replication[0]["Replicate_Do_DB"]);
            $replication_info[$type]['Do_DB'] = ${"server_{$type}_Do_DB"};

            ${"server_{$type}_Ignore_DB"} = explode(",", $server_slave_replication[0]["Replicate_Ignore_DB"]);
            $replication_info[$type]['Ignore_DB'] = ${"server_{$type}_Ignore_DB"};

            ${"server_{$type}_Do_Table"} = explode(",", $server_slave_replication[0]["Replicate_Do_Table"]);
            $replication_info[$type]['Do_Table'] = ${"server_{$type}_Do_Table"};

            ${"server_{$type}_Ignore_Table"} = explode(",", $server_slave_replication[0]["Replicate_Ignore_Table"]);
            $replication_info[$type]['Ignore_Table'] = ${"server_{$type}_Ignore_Table"};

            ${"server_{$type}_Wild_Do_Table"} = explode(",", $server_slave_replication[0]["Replicate_Wild_Do_Table"]);
            $replication_info[$type]['Wild_Do_Table'] = ${"server_{$type}_Wild_Do_Table"};

            ${"server_{$type}_Wild_Ignore_Table"} = explode(",", $server_slave_replication[0]["Replicate_Wild_Ignore_Table"]);
            $replication_info[$type]['Wild_Ignore_Table'] = ${"server_{$type}_Wild_Ignore_Table"};
        }
    }
}

/**
 * Extracts database or table name from string
 *
 * @param string $string contains "dbname.tablename"
 * @param string $what   what to extract (db|table)
 *
 * @return $string the extracted part
 */
function PMA_extract_db_or_table($string, $what = 'db')
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
 * @param string $control default: null, possible values: SQL_THREAD or IO_THREAD or null.
 *                        If it is set to null, it controls both SQL_THREAD and IO_THREAD
 * @param mixed  $link    mysql link
 *
 * @return mixed output of PMA_DBI_try_query
 */
function PMA_replication_slave_control($action, $control = null, $link = null)
{
    $action = strtoupper($action);
    $control = strtoupper($control);

    if ($action != "START" && $action != "STOP") {
        return -1;
    }
    if ($control != "SQL_THREAD" && $control != "IO_THREAD" && $control != null) {
        return -1;
    }

    return PMA_DBI_try_query($action . " SLAVE " . $control . ";", $link);
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
 * @return output of CHANGE MASTER mysql command
 */
function PMA_replication_slave_change_master($user, $password, $host, $port,
    $pos, $stop = true, $start = true, $link = null
) {
    if ($stop) {
        PMA_replication_slave_control("STOP", null, $link);
    }

    $out = PMA_DBI_try_query(
        'CHANGE MASTER TO ' .
        'MASTER_HOST=\'' . $host . '\',' .
        'MASTER_PORT=' . ($port * 1) . ',' .
        'MASTER_USER=\'' . $user . '\',' .
        'MASTER_PASSWORD=\'' . $password . '\',' .
        'MASTER_LOG_FILE=\'' . $pos["File"] . '\',' .
        'MASTER_LOG_POS=' . $pos["Position"] . ';', $link
    );

    if ($start) {
        PMA_replication_slave_control("START", null, $link);
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
function PMA_replication_connect_to_master($user, $password, $host = null, $port = null, $socket = null)
{
    $server = array();
    $server["host"] = PMA_sanitizeMySQLHost($host);
    $server["port"] = $port;
    $server["socket"] = $socket;

    // 5th parameter set to true means that it's an auxiliary connection
    // and we must not go back to login page if it fails
    return PMA_DBI_connect($user, $password, false, $server, true);
}
/**
 * Fetches position and file of current binary log on master
 *
 * @param mixed $link mysql link
 *
 * @return array an array containing File and Position in MySQL replication
 * on master server, useful for PMA_replication_slave_change_master
 */
function PMA_replication_slave_bin_log_master($link = null)
{
    $data = PMA_DBI_fetch_result('SHOW MASTER STATUS', null, null, $link);
    $output = array();

    if (! empty($data)) {
        $output["File"] = $data[0]["File"];
        $output["Position"] = $data[0]["Position"];
    }
    return $output;
}

/**
 * Get list of replicated databases on master server
 *
 * @param mixed $link mysql link
 *
 * @return array array of replicated databases
 */

function PMA_replication_master_replicated_dbs($link = null)
{
    // let's find out, which databases are replicated
    $data = PMA_DBI_fetch_result('SHOW MASTER STATUS', null, null, $link);

    $do_db     = array();
    $ignore_db = array();

    if (! empty($data[0]['Binlog_Do_DB'])) {
        $do_db     = explode(',', $data[0]['Binlog_Do_DB']);
    }
    if (! empty($data[0]['Binlog_Ignore_DB'])) {
        $ignore_db = explode(',', $data[0]['Binlog_Ignore_DB']);
    }

    $tmp_alldbs = PMA_DBI_query('SHOW DATABASES;', $link);
    while ($tmp_row = PMA_DBI_fetch_row($tmp_alldbs)) {
        if (PMA_is_system_schema($tmp_row[0])) {
            continue;
        }
        if (count($do_db) == 0) {
            if (array_search($tmp_row[0], $ignore_db) !== false) {
                continue;
            }
            $dblist[] = $tmp_row[0];

        } else {
            if (array_search($tmp_row[0], $do_db) !== false) {
                $dblist[] = $tmp_row[0];
            }
        }
    } // end while

    return $link;
}
?>
