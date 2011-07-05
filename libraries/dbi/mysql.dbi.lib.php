<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the classic MySQL extension
 *
 * @package phpMyAdmin-DBI-MySQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/logging.lib.php';

/**
 * MySQL client API
 */
if (! defined('PMA_MYSQL_CLIENT_API')) {
    $client_api = explode('.', mysql_get_client_info());
    define('PMA_MYSQL_CLIENT_API', (int)sprintf('%d%02d%02d', $client_api[0], $client_api[1], intval($client_api[2])));
    unset($client_api);
}

function PMA_DBI_real_connect($server, $user, $password, $client_flags, $persistant=false)
{
    global $cfg;

    if (empty($client_flags)) {
        if ($cfg['PersistentConnections'] || $persistant) {
            $link = @mysql_pconnect($server, $user, $password);
        } else {
            $link = @mysql_connect($server, $user, $password);
        }
    } else {
        if ($cfg['PersistentConnections'] || $persistant) {
            $link = @mysql_pconnect($server, $user, $password, $client_flags);
        } else {
            $link = @mysql_connect($server, $user, $password, false, $client_flags);
        }
    }

    return $link;
}
/**
 * @param   string  $user           mysql user name
 * @param   string  $password       mysql user password
 * @param   boolean $is_controluser
 * @param   array   $server host/port/socket/persistant
 * @param   boolean $auxiliary_connection (when true, don't go back to login if connection fails)
 * @return  mixed   false on error or a mysqli object on success
 */
function PMA_DBI_connect($user, $password, $is_controluser = false, $server = null, $auxiliary_connection = false)
{
    global $cfg, $php_errormsg;

    if ($server) {
        $server_port = (empty($server['port']))
            ? ''
            : ':' . (int)$server['port'];
        $server_socket = (empty($server['socket']))
            ? ''
            : ':' . $server['socket'];
        $server_persistant = (empty($server['persistant']))
            ? false
            : true;
    } else {
        $server_port   = (empty($cfg['Server']['port']))
            ? ''
            : ':' . (int)$cfg['Server']['port'];
        $server_socket = (empty($cfg['Server']['socket']))
            ? ''
            : ':' . $cfg['Server']['socket'];
    }

    if (strtolower($cfg['Server']['connect_type']) == 'tcp') {
        $cfg['Server']['socket'] = '';
    }

    $client_flags = 0;

    // always use CLIENT_LOCAL_FILES as defined in mysql_com.h
    // for the case where the client library was not compiled
    // with --enable-local-infile
    $client_flags |= 128;

    /* Optionally compress connection */
    if (defined('MYSQL_CLIENT_COMPRESS') && $cfg['Server']['compress']) {
        $client_flags |= MYSQL_CLIENT_COMPRESS;
    }

    /* Optionally enable SSL */
    if (defined('MYSQL_CLIENT_SSL') && $cfg['Server']['ssl']) {
        $client_flags |= MYSQL_CLIENT_SSL;
    }

    if (!$server) {
        $link = PMA_DBI_real_connect($cfg['Server']['host'] . $server_port . $server_socket, $user, $password, empty($client_flags) ? NULL : $client_flags);

      // Retry with empty password if we're allowed to
        if (empty($link) && $cfg['Server']['nopassword'] && !$is_controluser) {
            $link = PMA_DBI_real_connect($cfg['Server']['host'] . $server_port . $server_socket, $user, '', empty($client_flags) ? NULL : $client_flags);
        }
    } else {
        if (!isset($server['host'])) {
            $link = PMA_DBI_real_connect($server_socket, $user, $password, NULL, $server_persistant);
        } else {
            $link = PMA_DBI_real_connect($server['host'] . $server_port . $server_socket, $user, $password, NULL, $server_persistant);
        }
    }
    if (empty($link)) {
        if ($is_controluser) {
            trigger_error(__('Connection for controluser as defined in your configuration failed.'), E_USER_WARNING);
            return false;
        }
        // we could be calling PMA_DBI_connect() to connect to another
        // server, for example in the Synchronize feature, so do not
        // go back to main login if it fails
        if (! $auxiliary_connection) {
            PMA_log_user($user, 'mysql-denied');
            PMA_auth_fails();
        } else {
            return false;
        }
    } // end if
    if (! $server) {
        PMA_DBI_postConnect($link, $is_controluser);
    }
    return $link;
}

/**
 * select a db
 *
 * @param string $dbname name of db to select
 * @param resource $link mysql link resource
 * @return boolean success
 */
function PMA_DBI_select_db($dbname, $link = null)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    return mysql_select_db($dbname, $link);
}

/**
 * runs a query and returns the result
 *
 * @param string $query query to run
 * @param resource $link mysql link resource
 * @param integer $options
 * @return mixed
 */
function PMA_DBI_real_query($query, $link, $options)
{
    if ($options == ($options | PMA_DBI_QUERY_STORE)) {
        return mysql_query($query, $link);
    } elseif ($options == ($options | PMA_DBI_QUERY_UNBUFFERED)) {
        return mysql_unbuffered_query($query, $link);
    } else {
        return mysql_query($query, $link);
    }
}

function PMA_DBI_fetch_array($result)
{
    return mysql_fetch_array($result, MYSQL_BOTH);
}

function PMA_DBI_fetch_assoc($result) {
    return mysql_fetch_array($result, MYSQL_ASSOC);
}

function PMA_DBI_fetch_row($result)
{
    return mysql_fetch_array($result, MYSQL_NUM);
}

/*
 * Adjusts the result pointer to an arbitrary row in the result
 *
 * @param   $result
 * @param   $offset
 * @return  boolean true on success, false on failure
 */
function PMA_DBI_data_seek($result, $offset)
{
    return mysql_data_seek($result, $offset);
}

/**
 * Frees the memory associated with the results
 *
 * @param result    $result,...     one or more mysql result resources
 */
function PMA_DBI_free_result()
{
    foreach (func_get_args() as $result) {
        if (is_resource($result)
         && get_resource_type($result) === 'mysql result') {
            mysql_free_result($result);
        }
    }
}

/**
 * Check if there are any more query results from a multi query
 *
 * @return  boolean         false
 */
function PMA_DBI_more_results() {
    // N.B.: PHP's 'mysql' extension does not support
    // multi_queries so this function will always
    // return false. Use the 'mysqli' extension, if
    // you need support for multi_queries.
    return false;
}

/**
 * Prepare next result from multi_query
 *
 * @return  boolean         false
 */
function PMA_DBI_next_result() {
    // N.B.: PHP's 'mysql' extension does not support
    // multi_queries so this function will always
    // return false. Use the 'mysqli' extension, if
    // you need support for multi_queries.
    return false;
}

/**
 * Returns a string representing the type of connection used
 * @param   resource        $link   mysql link
 * @return  string          type of connection used
 */
function PMA_DBI_get_host_info($link = null)
{
    if (null === $link) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    return mysql_get_host_info($link);
}

/**
 * Returns the version of the MySQL protocol used
 * @param   resource        $link   mysql link
 * @return  integer         version of the MySQL protocol used
 */
function PMA_DBI_get_proto_info($link = null)
{
    if (null === $link) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    return mysql_get_proto_info($link);
}

/**
 * returns a string that represents the client library version
 * @return  string          MySQL client library version
 */
function PMA_DBI_get_client_info()
{
    return mysql_get_client_info();
}

/**
 * returns last error message or false if no errors occured
 *
 * @param   resource        $link   mysql link
 * @return  string|boolean  $error or false
 */
function PMA_DBI_getError($link = null)
{
    $GLOBALS['errno'] = 0;

    /* Treat false same as null because of controllink */
    if ($link === false) {
        $link = null;
    }

    if (null === $link && isset($GLOBALS['userlink'])) {
        $link =& $GLOBALS['userlink'];

// Do not stop now. On the initial connection, we don't have a $link,
// we don't have a $GLOBALS['userlink'], but we can catch the error code
//    } else {
//            return false;
    }

    if (null !== $link && false !== $link) {
        $error_number = mysql_errno($link);
        $error_message = mysql_error($link);
    } else {
        $error_number = mysql_errno();
        $error_message = mysql_error();
    }
    if (0 == $error_number) {
        return false;
    }

    // keep the error number for further check after the call to PMA_DBI_getError()
    $GLOBALS['errno'] = $error_number;

    if (! empty($error_message)) {
        $error_message = PMA_DBI_convert_message($error_message);
    }

    $error_message = htmlspecialchars($error_message);

    // Some errors messages cannot be obtained by mysql_error()
    if ($error_number == 2002) {
        $error = '#' . ((string) $error_number) . ' - ' . __('The server is not responding') . ' ' . __('(or the local MySQL server\'s socket is not correctly configured)');
    } elseif ($error_number == 2003) {
        $error = '#' . ((string) $error_number) . ' - ' . __('The server is not responding');
    } elseif ($error_number == 1005) {
        /* InnoDB contraints, see
         * http://dev.mysql.com/doc/refman/5.0/en/innodb-foreign-key-constraints.html
         */
        $error = '#' . ((string) $error_number) . ' - ' . $error_message .
            ' (<a href="server_engines.php' . PMA_generate_common_url(array('engine' => 'InnoDB', 'page' => 'Status')).
            '">' . __('Details...') . '</a>)';
    } else {
        $error = '#' . ((string) $error_number) . ' - ' . $error_message;
    }
    return $error;
}

function PMA_DBI_num_rows($result)
{
    if (!is_bool($result)) {
        return mysql_num_rows($result);
    } else {
        return 0;
    }
}

function PMA_DBI_insert_id($link = null)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    // If the primary key is BIGINT we get an incorrect result
    // (sometimes negative, sometimes positive)
    // and in the present function we don't know if the PK is BIGINT
    // so better play safe and use LAST_INSERT_ID()
    //
    return PMA_DBI_fetch_value('SELECT LAST_INSERT_ID();', 0, 0, $link);
}

/**
 * returns the number of rows affected by last query
 *
 * @param   object mysql   $link   the mysql object
 * @param   boolean        $get_from_cache
 * @return  string integer
 */
function PMA_DBI_affected_rows($link = null, $get_from_cache = true)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }

    if ($get_from_cache) {
        return $GLOBALS['cached_affected_rows'];
    } else {
        return mysql_affected_rows($link);
    }
}

/**
 * @todo add missing keys like in from mysqli_query (orgname, orgtable, flags, decimals)
 */
function PMA_DBI_get_fields_meta($result)
{
    $fields       = array();
    $num_fields   = mysql_num_fields($result);
    for ($i = 0; $i < $num_fields; $i++) {
        $fields[] = mysql_fetch_field($result, $i);
    }
    return $fields;
}

function PMA_DBI_num_fields($result)
{
    return mysql_num_fields($result);
}

function PMA_DBI_field_len($result, $i)
{
    return mysql_field_len($result, $i);
}

function PMA_DBI_field_name($result, $i)
{
    return mysql_field_name($result, $i);
}

function PMA_DBI_field_flags($result, $i)
{
    return mysql_field_flags($result, $i);
}

?>
