<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
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
// TODO change to regexp or something, to allow for negative match. To e.g. highlight 'Last_Error'
//
$slave_variables_alerts = array(
    'Slave_IO_Running' => 'No',
    'Slave_SQL_Running' => 'No',
);
$slave_variables_oks = array(
    'Slave_IO_Running' => 'Yes',
    'Slave_SQL_Running' => 'Yes',
);

$serverid = time();

// check which replication is available and set $server_{master/slave}_status and assign values
foreach ($replication_types as $type) {
    if (count(${"server_{$type}_replication"}) > 0) {
        ${"server_{$type}_status"} = true;
    } else {
        ${"server_{$type}_status"} = false;
    }
    if (${"server_{$type}_status"}) {
        if ($type == "master") {
            ${"server_{$type}_Do_DB"} = explode(",", $server_master_replication[0]["Binlog_Do_DB"]);
            ${"server_{$type}_Ignore_DB"} = explode(",", $server_master_replication[0]["Binlog_Ignore_DB"]);
        } elseif ($type == "slave") {
            ${"server_{$type}_Do_DB"} = explode(",", $server_slave_replication[0]["Replicate_Do_DB"]);
            ${"server_{$type}_Ignore_DB"} = explode(",", $server_slave_replication[0]["Replicate_Ignore_DB"]);

            ${"server_{$type}_Do_Table"} = explode(",", $server_slave_replication[0]["Replicate_Do_Table"]);
            ${"server_{$type}_Ignore_Table"} = explode(",", $server_slave_replication[0]["Replicate_Ignore_Table"]);

            ${"server_{$type}_Wild_Do_Table"} = explode(",", $server_slave_replication[0]["Replicate_Wild_Do_Table"]);
            ${"server_{$type}_Wild_Ignore_Table"} = explode(",", $server_slave_replication[0]["Replicate_Wild_Ignore_Table"]);
        }
    }
}


/**
 * @param $string - 
 * @param $table - 
 * @return 
 */
function PMA_replication_strout($string, $table = false) {
    $list = explode(".", $string);

    return $list[(int)$table];
}
/**
 * @param String $action - possible values: START or STOP
 * @param String $control - default: null, possible values: SQL_THREAD or IO_THREAD or null. If it is set to null, it controls both SQL_THREAD and IO_THREAD
 * @param mixed  $link   - mysql link
 *
 * @return mixed output of PMA_DBI_try_query
 */
function PMA_replication_slave_control($action, $control = null, $link = null) {
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
 * @param String $user - replication user on master
 * @param String $password - password for the user
 * @param String $host - master's hostname or IP
 * @param int $port - port, where mysql is running
 * @param array $pos - position of mysql replication, array should contain fields File and Position
 * @param boolean $stop - shall we stop slave?
 * @param boolean $start - shall we start slave?
 * @param mixed $link - mysql link
 *
 * @return output of CHANGE MASTER mysql command
 */
function PMA_replication_slave_change_master($user, $password, $host, $port, $pos, $stop = true, $start = true, $link = null) {
    if ($stop) {
        PMA_replication_slave_control("STOP", null, $link);
    }

    $out = PMA_DBI_try_query('CHANGE MASTER TO ' .
        'MASTER_HOST=\'' . $host . '\',' .
        'MASTER_PORT=' . ($port * 1) . ',' .
        'MASTER_USER=\'' . $user . '\',' .
        'MASTER_PASSWORD=\'' . $password . '\',' .
        'MASTER_LOG_FILE=\'' . $pos["File"] . '\',' .
        'MASTER_LOG_POS=' . $pos["Position"] . ';', $link);

    if ($start) {
        PMA_replication_slave_control("START", null, $link);
    }

    return $out;
}

/**
 * This function provides connection to remote mysql server
 *
 * @param String $user - mysql username
 * @param String $password - password for the user
 * @param String $host - mysql server's hostname or IP
 * @param int $port - mysql remote port
 * @param String $socket - path to unix socket
 * 
 * @return mixed $link mysql link on success
 */
function PMA_replication_connect_to_master($user, $password, $host = null, $port = null, $socket = null) {
    $server = array();
    $server["host"] = $host;
    $server["port"] = $port;
    $server["socket"] = $socket;

    // 5th parameter set to true means that it's an auxiliary connection 
    // and we must not go back to login page if it fails
    return PMA_DBI_connect($user, $password, false, $server, true);
}
/**
 * @param $link - mysql link
 *
 * @return array - containing File and Position in MySQL replication on master server, useful for PMA_replication_slave_change_master
 */
function PMA_replication_slave_bin_log_master($link = null) {
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
 * @param mixed mysql link
 *
 * @return array array of replicated databases
 */

function PMA_replication_master_replicated_dbs($link = null) {
    $data = PMA_DBI_fetch_result('SHOW MASTER STATUS', null, null, $link); // let's find out, which databases are replicated

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
        if ($tmp_row[0] == 'information_schema')
            continue;
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
/**
 * This function provides synchronization of structure and data between two mysql servers. 
 * TODO: improve code sharing between the function and synchronization
 *
 * @param String $db - name of database, which should be synchronized
 * @param mixed $src_link - link of source server, note: if the server is current PMA server, use null
 * @param mixed $trg_link - link of target server, note: if the server is current PMA server, use null
 * @param boolean $data - if true, then data will be copied as well
 */

function PMA_replication_synchronize_db($db, $src_link, $trg_link, $data = true) {
    $src_db = $trg_db = $db;

    $src_connection = PMA_DBI_select_db($src_db, $src_link);
    $trg_connection = PMA_DBI_select_db($trg_db, $trg_link);

    $src_tables = PMA_DBI_get_tables($src_db, $src_link);
    $source_tables_num = sizeof($src_tables);

    $trg_tables = PMA_DBI_get_tables($trg_db, $trg_link);
    $target_tables_num = sizeof($trg_tables);

    /**
     * initializing arrays to save table names 
     */                                      
    $unmatched_num_src = 0;
    $source_tables_uncommon = array();
    $unmatched_num_trg = 0;
    $target_tables_uncommon = array();
    $matching_tables = array();
    $matching_tables_num = 0;

    /**
     * Criterion for matching tables is just their names.
     * Finding the uncommon tables for the source database
     * BY comparing the matching tables with all the tables in the source database
     */                                                                           
    PMA_getMatchingTables($trg_tables, $src_tables, $matching_tables, $source_tables_uncommon);

    /**
     * Finding the uncommon tables for the target database
     * BY comparing the matching tables with all the tables in the target database
     */
    PMA_getNonMatchingTargetTables($trg_tables, $matching_tables, $target_tables_uncommon);

    /**
     * 
     * Comparing Data In the Matching Tables 
     * It is assumed that the matching tables are structurally 
     * and typely exactly the same  
     */
    $fields_num = array();
    $matching_tables_fields = array();
    $matching_tables_keys   = array();
    $insert_array = array(array(array()));
    $update_array = array(array(array()));
    $delete_array = array();
    $row_count = array();
    $uncommon_tables_fields = array();
    $matching_tables_num = sizeof($matching_tables);

    for ($i = 0; $i < sizeof($matching_tables); $i++) {
        PMA_dataDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $matching_tables_fields, $update_array, $insert_array,
            $delete_array, $fields_num, $i, $matching_tables_keys);
    }
    for ($j = 0; $j < sizeof($source_tables_uncommon); $j++) {
        PMA_dataDiffInUncommonTables($source_tables_uncommon, $src_db, $src_link, $j, $row_count);
    }  

    /**
     * INTEGRATION OF STRUCTURE DIFFERENCE CODE
     *
     */
    $source_columns = array();
    $target_columns = array();
    $alter_str_array = array(array());
    $add_column_array = array(array());
    $uncommon_columns = array(); 
    $target_tables_keys = array();
    $source_indexes = array();
    $target_indexes = array();
    $add_indexes_array = array();
    $remove_indexes_array = array();
    $criteria = array('Field', 'Type', 'Null', 'Collation', 'Key', 'Default', 'Comment');

    for ($counter = 0; $counter < $matching_tables_num; $counter++) {
        PMA_structureDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_columns,
            $target_columns, $alter_str_array, $add_column_array, $uncommon_columns, $criteria, $target_tables_keys, $counter);    

        PMA_indexesDiffInTables($src_db, $trg_db, $src_link, $trg_link, $matching_tables, $source_indexes, $target_indexes,
            $add_indexes_array, $alter_indexes_array,$remove_indexes_array, $counter);      
    } 

    $matching_table_data_diff = array();
    $matching_table_structure_diff = array();
    $uncommon_table_structure_diff = array();
    $uncommon_table_data_diff = array();
    $uncommon_tables = $source_tables_uncommon;

    /**
     * Generating Create Table query for all the non-matching tables present in Source but not in Target and populating tables.  
     */   
    for($q = 0; $q < sizeof($source_tables_uncommon); $q++) { 
        if (isset($uncommon_tables[$q])) {
            PMA_createTargetTables($src_db, $trg_db, $src_link, $trg_link, $source_tables_uncommon, $q, $uncommon_tables_fields, false);
        }
        if (isset($row_count[$q]) && $data) {
            PMA_populateTargetTables($src_db, $trg_db, $src_link, $trg_link, $source_tables_uncommon, $q, $uncommon_tables_fields, false);    
        }
    }
}
?>
