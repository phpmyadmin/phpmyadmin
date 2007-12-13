<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the classic MySQL extension
 *
 * @version $Id$
 */

/**
 * MySQL client API
 */
if (! defined('PMA_MYSQL_CLIENT_API')) {
    $client_api = explode('.', mysql_get_client_info());
    define('PMA_MYSQL_CLIENT_API', (int)sprintf('%d%02d%02d', $client_api[0], $client_api[1], intval($client_api[2])));
    unset($client_api);
}

function PMA_DBI_real_connect($server, $user, $password, $client_flags)
{
    global $cfg;

    if (empty($client_flags)) {
        if ($cfg['PersistentConnections']) {
            $link = @mysql_pconnect($server, $user, $password);
        } else {
            $link = @mysql_connect($server, $user, $password);
        }
    } else {
        if ($cfg['PersistentConnections']) {
            $link = @mysql_pconnect($server, $user, $password, $client_flags);
        } else {
            $link = @mysql_connect($server, $user, $password, false, $client_flags);
        }
    }

    return $link;
}

function PMA_DBI_connect($user, $password, $is_controluser = false)
{
    global $cfg, $php_errormsg;

    $server_port   = (empty($cfg['Server']['port']))
                   ? ''
                   : ':' . $cfg['Server']['port'];

    if (strtolower($cfg['Server']['connect_type']) == 'tcp') {
        $cfg['Server']['socket'] = '';
    }

    $server_socket = (empty($cfg['Server']['socket']))
                   ? ''
                   : ':' . $cfg['Server']['socket'];

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

    $link = PMA_DBI_real_connect($cfg['Server']['host'] . $server_port . $server_socket, $user, $password, empty($client_flags) ? NULL : $client_flags);

    // Retry with empty password if we're allowed to
    if (empty($link) && $cfg['Server']['nopassword'] && !$is_controluser) {
        $link = PMA_DBI_real_connect($cfg['Server']['host'] . $server_port . $server_socket, $user, '', empty($client_flags) ? NULL : $client_flags);
    }

    if (empty($link)) {
        if ($is_controluser) {
            trigger_error($GLOBALS['strControluserFailed'], E_USER_WARNING);
            return false;
        }
        PMA_auth_fails();
    } // end if

    PMA_DBI_postConnect($link, $is_controluser);

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
function PMA_DBI_try_query($query, $link = null, $options = 0)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }

    if ($GLOBALS['cfg']['DBG']['enable']) {
        $time = microtime(true);
    }
    if ($options == ($options | PMA_DBI_QUERY_STORE)) {
        $r = mysql_query($query, $link);
    } elseif ($options == ($options | PMA_DBI_QUERY_UNBUFFERED)) {
        $r = mysql_unbuffered_query($query, $link);
    } else {
        $r = mysql_query($query, $link);
    }

    if ($GLOBALS['cfg']['DBG']['enable']) {
        $time = microtime(true) - $time;

        $hash = md5($query);

        if (isset($_SESSION['debug']['queries'][$hash])) {
            $_SESSION['debug']['queries'][$hash]['count']++;
        } else {
            $_SESSION['debug']['queries'][$hash] = array();
            $_SESSION['debug']['queries'][$hash]['count'] = 1;
            $_SESSION['debug']['queries'][$hash]['query'] = $query;
            $_SESSION['debug']['queries'][$hash]['time'] = $time;
        }
    }

    return $r;
}

/**
 *  The following function is meant for internal use only.
 * Do not call it from outside this library!
 */
function PMA_mysql_fetch_array($result, $type = false)
{
    global $cfg, $allow_recoding, $charset;

    if ($type != false) {
        $data = mysql_fetch_array($result, $type);
    } else {
        $data = mysql_fetch_array($result);
    }

    return $data;
}

function PMA_DBI_fetch_array($result)
{
    return PMA_mysql_fetch_array($result);
}

function PMA_DBI_fetch_assoc($result) {
    return PMA_mysql_fetch_array($result, MYSQL_ASSOC);
}

function PMA_DBI_fetch_row($result)
{
    return PMA_mysql_fetch_array($result, MYSQL_NUM);
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
 * Returns a string representing the type of connection used
 * @uses    mysql_get_host_info()
 * @uses    $GLOBALS['userlink']    as default for $link
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
 * @uses    mysql_get_proto_info()
 * @uses    $GLOBALS['userlink']    as default for $link
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
 * @uses    mysql_get_client_info()
 * @return  string          MySQL client library version
 */
function PMA_DBI_get_client_info()
{
    return mysql_get_client_info();
}

/**
 * returns last error message or false if no errors occured
 *
 * @uses    PMA_DBI_convert_message()
 * @uses    $GLOBALS['errno']
 * @uses    $GLOBALS['userlink']
 * @uses    $GLOBALS['strServerNotResponding']
 * @uses    $GLOBALS['strSocketProblem']
 * @uses    mysql_errno()
 * @uses    mysql_error()
 * @uses    defined()
 * @param   resource        $link   mysql link
 * @return  string|boolean  $error or false
 */
function PMA_DBI_getError($link = null)
{
    $GLOBALS['errno'] = 0;
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

    // Some errors messages cannot be obtained by mysql_error()
    if ($error_number == 2002) {
        $error = '#' . ((string) $error_number) . ' - ' . $GLOBALS['strServerNotResponding'] . ' ' . $GLOBALS['strSocketProblem'];
    } elseif ($error_number == 2003) {
        $error = '#' . ((string) $error_number) . ' - ' . $GLOBALS['strServerNotResponding'];
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
    //$insert_id = mysql_insert_id($link);
    // if the primary key is BIGINT we get an incorrect result
    // (sometimes negative, sometimes positive)
    // and in the present function we don't know if the PK is BIGINT
    // so better play safe and use LAST_INSERT_ID()
    //
    // by the way, no problem with mysqli_insert_id()
    return PMA_DBI_fetch_value('SELECT LAST_INSERT_ID();', 0, 0, $link);
}

function PMA_DBI_affected_rows($link = null)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    return mysql_affected_rows($link);
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
