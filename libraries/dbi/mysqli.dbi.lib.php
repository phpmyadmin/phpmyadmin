<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the improved MySQL extension (MySQLi)
 *
 * @package phpMyAdmin-DBI-MySQLi
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/logging.lib.php';

/**
 * MySQL client API
 */
if (!defined('PMA_MYSQL_CLIENT_API')) {
    $client_api = explode('.', mysqli_get_client_info());
    define('PMA_MYSQL_CLIENT_API', (int)sprintf('%d%02d%02d', $client_api[0], $client_api[1], intval($client_api[2])));
    unset($client_api);
}

/**
 * some PHP versions are reporting extra messages like "No index used in query"
 */

mysqli_report(MYSQLI_REPORT_OFF);

/**
 * some older mysql client libs are missing these constants ...
 */
if (! defined('MYSQLI_BINARY_FLAG')) {
   define('MYSQLI_BINARY_FLAG', 128);
}

/**
 * @see http://bugs.php.net/36007
 */
if (! defined('MYSQLI_TYPE_NEWDECIMAL')) {
    define('MYSQLI_TYPE_NEWDECIMAL', 246);
}
if (! defined('MYSQLI_TYPE_BIT')) {
    define('MYSQLI_TYPE_BIT', 16);
}

/**
 * connects to the database server
 *
 * @uses    $GLOBALS['cfg']['Server']
 * @uses    PMA_auth_fails()
 * @uses    PMA_DBI_postConnect()
 * @uses    MYSQLI_CLIENT_COMPRESS
 * @uses    MYSQLI_OPT_LOCAL_INFILE
 * @uses    strtolower()
 * @uses    mysqli_init()
 * @uses    mysqli_options()
 * @uses    mysqli_real_connect()
 * @uses    defined()
 * @param   string  $user           mysql user name
 * @param   string  $password       mysql user password
 * @param   boolean $is_controluser
 * @param   array   $server host/port/socket 
 * @param   boolean $auxiliary_connection (when true, don't go back to login if connection fails)
 * @return  mixed   false on error or a mysqli object on success
 */
function PMA_DBI_connect($user, $password, $is_controluser = false, $server = null, $auxiliary_connection = false)
{
    if ($server) {
          $server_port   = (empty($server['port']))
                   ? ''
                   : (int)$server['port'];
	  $server_socket = (empty($server['socket']))
                   ? ''
                   : $server['socket'];
	  $server['host'] = (empty($server['host']))
		   ? 'localhost'
		   : $server['host'];
    } else {
	  $server_port   = (empty($GLOBALS['cfg']['Server']['port']))
			 ? false
			 : (int) $GLOBALS['cfg']['Server']['port'];
	  $server_socket = (empty($GLOBALS['cfg']['Server']['socket']))
			 ? null
			 : $GLOBALS['cfg']['Server']['socket'];
    }


    if (strtolower($GLOBALS['cfg']['Server']['connect_type']) == 'tcp') {
        $GLOBALS['cfg']['Server']['socket'] = '';
    }

    // NULL enables connection to the default socket

    $link = mysqli_init();

    mysqli_options($link, MYSQLI_OPT_LOCAL_INFILE, true);

    $client_flags = 0;

    /* Optionally compress connection */
    if ($GLOBALS['cfg']['Server']['compress'] && defined('MYSQLI_CLIENT_COMPRESS')) {
        $client_flags |= MYSQLI_CLIENT_COMPRESS;
    }

    /* Optionally enable SSL */
    if ($GLOBALS['cfg']['Server']['ssl'] && defined('MYSQLI_CLIENT_SSL')) {
        $client_flags |= MYSQLI_CLIENT_SSL;
    }
    
    if (!$server) {
      $return_value = @mysqli_real_connect($link, $GLOBALS['cfg']['Server']['host'], $user, $password, false, $server_port, $server_socket, $client_flags);
      // Retry with empty password if we're allowed to
      if ($return_value == false && isset($GLOBALS['cfg']['Server']['nopassword']) && $GLOBALS['cfg']['Server']['nopassword'] && !$is_controluser) {
	  $return_value = @mysqli_real_connect($link, $GLOBALS['cfg']['Server']['host'], $user, '', false, $server_port, $server_socket, $client_flags);
      }
    } else {
      $return_value = @mysqli_real_connect($link, $server['host'], $user, $password, false, $server_port, $server_socket);
    }

    if ($return_value == false) {
	    if ($is_controluser) {
	        trigger_error($GLOBALS['strControluserFailed'], E_USER_WARNING);
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
    } else {
        PMA_DBI_postConnect($link, $is_controluser);
    }

    return $link;
}

/**
 * selects given database
 *
 * @uses    $GLOBALS['userlink']
 * @uses    PMA_convert_charset()
 * @uses    mysqli_select_db()
 * @param   string          $dbname database name to select
 * @param   object mysqli   $link   the mysqli object
 * @return  boolean         true or false
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
    return mysqli_select_db($link, $dbname);
}

/**
 * runs a query and returns the result
 *
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_DBI_QUERY_UNBUFFERED
 * @uses    $GLOBALS['userlink']
 * @uses    PMA_convert_charset()
 * @uses    MYSQLI_STORE_RESULT
 * @uses    MYSQLI_USE_RESULT
 * @uses    mysqli_query()
 * @uses    defined()
 * @param   string          $query      query to execute
 * @param   object mysqli   $link       mysqli object
 * @param   integer         $options
 * @return  mixed           true, false or result object
 */
function PMA_DBI_try_query($query, $link = null, $options = 0)
{
    if ($options == ($options | PMA_DBI_QUERY_STORE)) {
        $method = MYSQLI_STORE_RESULT;
    } elseif ($options == ($options | PMA_DBI_QUERY_UNBUFFERED)) {
        $method = MYSQLI_USE_RESULT;
    } else {
        $method = 0;
    }

    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }

    if ($GLOBALS['cfg']['DBG']['sql']) {
        $time = microtime(true);
    }
    $r = mysqli_query($link, $query, $method);
    if ($GLOBALS['cfg']['DBG']['sql']) {
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

        $trace = array();
        foreach (debug_backtrace() as $trace_step) {
            $trace[] = PMA_Error::relPath($trace_step['file']) . '#'
                . $trace_step['line'] . ': '
                . (isset($trace_step['class']) ? $trace_step['class'] : '')
                //. (isset($trace_step['object']) ? get_class($trace_step['object']) : '')
                . (isset($trace_step['type']) ? $trace_step['type'] : '')
                . (isset($trace_step['function']) ? $trace_step['function'] : '')
                . '('
                . (isset($trace_step['params']) ? implode(', ', $trace_step['params']) : '')
                . ')'
                ;
        }
        $_SESSION['debug']['queries'][$hash]['trace'][] = $trace;
    }

    if ($r != FALSE && PMA_Tracker::isActive() == TRUE ) {
        PMA_Tracker::handleQuery($query); 
    }

    return $r;

    // From the PHP manual:
    // "note: returns true on success or false on failure. For SELECT,
    // SHOW, DESCRIBE or EXPLAIN, mysqli_query() will return a result object"
    // so, do not use the return value to feed mysqli_num_rows() if it's
    // a boolean
}

/**
 * returns array of rows with associative and numeric keys from $result
 *
 * @uses    mysqli_fetch_array()
 * @uses    MYSQLI_BOTH
 * @param   object mysqli result    $result
 * @return  array                   result rows
 */
function PMA_DBI_fetch_array($result)
{
    return mysqli_fetch_array($result, MYSQLI_BOTH);
}

/**
 * returns array of rows with associative keys from $result
 *
 * @uses    mysqli_fetch_array()
 * @uses    MYSQLI_ASSOC
 * @param   object mysqli result    $result
 * @return  array                   result rows
 */
function PMA_DBI_fetch_assoc($result)
{
    return mysqli_fetch_array($result, MYSQLI_ASSOC);
}

/**
 * returns array of rows with numeric keys from $result
 *
 * @uses    mysqli_fetch_array()
 * @uses    MYSQLI_NUM
 * @param   object mysqli result    $result
 * @return  array                   result rows
 */
function PMA_DBI_fetch_row($result)
{
    return mysqli_fetch_array($result, MYSQLI_NUM);
}

/*
 * Adjusts the result pointer to an arbitrary row in the result
 *
 * @uses    mysqli_data_seek()
 * @param   $result
 * @param   $offset
 * @return  boolean true on success, false on failure
 */
function PMA_DBI_data_seek($result, $offset)
{
    return mysqli_data_seek($result, $offset);
}

/**
 * Frees the memory associated with the results
 *
 * @uses    mysqli_result
 * @uses    func_get_args()
 * @uses    mysqli_free_result()
 * @param   result  $result,...     one or more mysql result resources
 */
function PMA_DBI_free_result()
{
    foreach (func_get_args() as $result) {
        if ($result instanceof mysqli_result) {
            mysqli_free_result($result);
        }
    }
}

/**
 * Returns a string representing the type of connection used
 * @uses    mysqli_get_host_info()
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
    return mysqli_get_host_info($link);
}

/**
 * Returns the version of the MySQL protocol used
 * @uses    mysqli_get_proto_info()
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
    return mysqli_get_proto_info($link);
}

/**
 * returns a string that represents the client library version
 * @uses    mysqli_get_client_info()
 * @return  string          MySQL client library version
 */
function PMA_DBI_get_client_info()
{
    return mysqli_get_client_info();
}

/**
 * returns last error message or false if no errors occured
 *
 * @uses    PMA_DBI_convert_message()
 * @uses    $GLOBALS['errno']
 * @uses    $GLOBALS['userlink']
 * @uses    $GLOBALS['strServerNotResponding']
 * @uses    $GLOBALS['strSocketProblem']
 * @uses    mysqli_errno()
 * @uses    mysqli_error()
 * @uses    mysqli_connect_errno()
 * @uses    mysqli_connect_error()
 * @uses    defined()
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
        // Do not stop now. We still can get the error code
        // with mysqli_connect_errno()
//    } else {
//        return false;
    }

    if (null !== $link) {
        $error_number = mysqli_errno($link);
        $error_message = mysqli_error($link);
    } else {
        $error_number = mysqli_connect_errno();
        $error_message = mysqli_connect_error();
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

    if ($error_number == 2002) {
        $error = '#' . ((string) $error_number) . ' - ' . $GLOBALS['strServerNotResponding'] . ' ' . $GLOBALS['strSocketProblem'];
    } else {
        $error = '#' . ((string) $error_number) . ' - ' . $error_message;
    }
    return $error;
}

/**
 *
 * @param   object mysqli result    $result
 */
function PMA_DBI_num_rows($result)
{
    // see the note for PMA_DBI_try_query();
    if (!is_bool($result)) {
        return @mysqli_num_rows($result);
    } else {
        return 0;
    }
}

/**
 * returns last inserted auto_increment id for given $link or $GLOBALS['userlink']
 *
 * @uses    $GLOBALS['userlink']
 * @uses    mysqli_insert_id()
 * @param   object mysqli   $link   the mysqli object
 * @return  string ineteger
 */
function PMA_DBI_insert_id($link = '')
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    return mysqli_insert_id($link);
}

/**
 * returns the number of rows affected by last query
 *
 * @uses    $GLOBALS['userlink']
 * @uses    mysqli_affected_rows()
 * @param   object mysqli   $link   the mysqli object
 * @return  string integer
 */
function PMA_DBI_affected_rows($link = null)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    return mysqli_affected_rows($link);
}

/**
 * returns metainfo for fields in $result
 *
 * @todo preserve orignal flags value
 * @uses    PMA_DBI_field_flags()
 * @uses    MYSQLI_TYPE_*
 * @uses    MYSQLI_MULTIPLE_KEY_FLAG
 * @uses    MYSQLI_PRI_KEY_FLAG
 * @uses    MYSQLI_UNIQUE_KEY_FLAG
 * @uses    MYSQLI_NOT_NULL_FLAG
 * @uses    MYSQLI_UNSIGNED_FLAG
 * @uses    MYSQLI_ZEROFILL_FLAG
 * @uses    MYSQLI_NUM_FLAG
 * @uses    MYSQLI_TYPE_BLOB
 * @uses    MYSQLI_BLOB_FLAG
 * @uses    defined()
 * @uses    mysqli_fetch_fields()
 * @uses    is_array()
 * @param   object mysqli result    $result
 * @return  array                   meta info for fields in $result
 */
function PMA_DBI_get_fields_meta($result)
{
    // Build an associative array for a type look up
    $typeAr = array();
    $typeAr[MYSQLI_TYPE_DECIMAL]     = 'real';
    $typeAr[MYSQLI_TYPE_NEWDECIMAL]  = 'real';
    $typeAr[MYSQLI_TYPE_BIT]         = 'int';
    $typeAr[MYSQLI_TYPE_TINY]        = 'int';
    $typeAr[MYSQLI_TYPE_SHORT]       = 'int';
    $typeAr[MYSQLI_TYPE_LONG]        = 'int';
    $typeAr[MYSQLI_TYPE_FLOAT]       = 'real';
    $typeAr[MYSQLI_TYPE_DOUBLE]      = 'real';
    $typeAr[MYSQLI_TYPE_NULL]        = 'null';
    $typeAr[MYSQLI_TYPE_TIMESTAMP]   = 'timestamp';
    $typeAr[MYSQLI_TYPE_LONGLONG]    = 'int';
    $typeAr[MYSQLI_TYPE_INT24]       = 'int';
    $typeAr[MYSQLI_TYPE_DATE]        = 'date';
    $typeAr[MYSQLI_TYPE_TIME]        = 'time';
    $typeAr[MYSQLI_TYPE_DATETIME]    = 'datetime';
    $typeAr[MYSQLI_TYPE_YEAR]        = 'year';
    $typeAr[MYSQLI_TYPE_NEWDATE]     = 'date';
    $typeAr[MYSQLI_TYPE_ENUM]        = 'unknown';
    $typeAr[MYSQLI_TYPE_SET]         = 'unknown';
    $typeAr[MYSQLI_TYPE_TINY_BLOB]   = 'blob';
    $typeAr[MYSQLI_TYPE_MEDIUM_BLOB] = 'blob';
    $typeAr[MYSQLI_TYPE_LONG_BLOB]   = 'blob';
    $typeAr[MYSQLI_TYPE_BLOB]        = 'blob';
    $typeAr[MYSQLI_TYPE_VAR_STRING]  = 'string';
    $typeAr[MYSQLI_TYPE_STRING]      = 'string';
    // MySQL returns MYSQLI_TYPE_STRING for CHAR
    // and MYSQLI_TYPE_CHAR === MYSQLI_TYPE_TINY
    // so this would override TINYINT and mark all TINYINT as string
    // https://sf.net/tracker/?func=detail&aid=1532111&group_id=23067&atid=377408
    //$typeAr[MYSQLI_TYPE_CHAR]        = 'string';
    $typeAr[MYSQLI_TYPE_GEOMETRY]    = 'unknown';
    $typeAr[MYSQLI_TYPE_BIT]         = 'bit';

    $fields = mysqli_fetch_fields($result);

    // this happens sometimes (seen under MySQL 4.0.25)
    if (!is_array($fields)) {
        return false;
    }

    foreach ($fields as $k => $field) {
        $fields[$k]->_type = $field->type;
        $fields[$k]->type = $typeAr[$field->type];
        $fields[$k]->_flags = $field->flags;
        $fields[$k]->flags = PMA_DBI_field_flags($result, $k);

        // Enhance the field objects for mysql-extension compatibilty
        //$flags = explode(' ', $fields[$k]->flags);
        //array_unshift($flags, 'dummy');
        $fields[$k]->multiple_key
            = (int) (bool) ($fields[$k]->_flags & MYSQLI_MULTIPLE_KEY_FLAG);
        $fields[$k]->primary_key
            = (int) (bool) ($fields[$k]->_flags & MYSQLI_PRI_KEY_FLAG);
        $fields[$k]->unique_key
            = (int) (bool) ($fields[$k]->_flags & MYSQLI_UNIQUE_KEY_FLAG);
        $fields[$k]->not_null
            = (int) (bool) ($fields[$k]->_flags & MYSQLI_NOT_NULL_FLAG);
        $fields[$k]->unsigned
            = (int) (bool) ($fields[$k]->_flags & MYSQLI_UNSIGNED_FLAG);
        $fields[$k]->zerofill
            = (int) (bool) ($fields[$k]->_flags & MYSQLI_ZEROFILL_FLAG);
        $fields[$k]->numeric
            = (int) (bool) ($fields[$k]->_flags & MYSQLI_NUM_FLAG);
        $fields[$k]->blob
            = (int) (bool) ($fields[$k]->_flags & MYSQLI_BLOB_FLAG);
    }
    return $fields;
}

/**
 * return number of fields in given $result
 *
 * @param   object mysqli result    $result
 * @return  integer                 field count
 */
function PMA_DBI_num_fields($result)
{
    return mysqli_num_fields($result);
}

/**
 * returns the length of the given field $i in $result
 *
 * @uses    mysqli_fetch_field_direct()
 * @param   object mysqli result    $result
 * @param   integer                 $i      field
 * @return  integer                 length of field
 */
function PMA_DBI_field_len($result, $i)
{
    return mysqli_fetch_field_direct($result, $i)->length;
}

/**
 * returns name of $i. field in $result
 *
 * @uses    mysqli_fetch_field_direct()
 * @param   object mysqli result    $result
 * @param   integer                 $i      field
 * @return  string                  name of $i. field in $result
 */
function PMA_DBI_field_name($result, $i)
{
    return mysqli_fetch_field_direct($result, $i)->name;
}

/**
 * returns concatenated string of human readable field flags
 *
 * @uses    MYSQLI_UNIQUE_KEY_FLAG
 * @uses    MYSQLI_NUM_FLAG
 * @uses    MYSQLI_PART_KEY_FLAG
 * @uses    MYSQLI_TYPE_SET
 * @uses    MYSQLI_TIMESTAMP_FLAG
 * @uses    MYSQLI_AUTO_INCREMENT_FLAG
 * @uses    MYSQLI_TYPE_ENUM
 * @uses    MYSQLI_ZEROFILL_FLAG
 * @uses    MYSQLI_UNSIGNED_FLAG
 * @uses    MYSQLI_BLOB_FLAG
 * @uses    MYSQLI_MULTIPLE_KEY_FLAG
 * @uses    MYSQLI_UNIQUE_KEY_FLAG
 * @uses    MYSQLI_PRI_KEY_FLAG
 * @uses    MYSQLI_NOT_NULL_FLAG
 * @uses    MYSQLI_TYPE_BLOB
 * @uses    MYSQLI_TYPE_MEDIUM_BLOB
 * @uses    MYSQLI_TYPE_LONG_BLOB
 * @uses    MYSQLI_TYPE_VAR_STRING
 * @uses    MYSQLI_TYPE_STRING
 * @uses    mysqli_fetch_field_direct()
 * @param   object mysqli result    $result
 * @param   integer                 $i      field
 * @return  string                  field flags
 */
function PMA_DBI_field_flags($result, $i)
{
    // This is missing from PHP 5.2.5, see http://bugs.php.net/bug.php?id=44846
    if (! defined('MYSQLI_ENUM_FLAG')) {
        define('MYSQLI_ENUM_FLAG', 256); // see MySQL source include/mysql_com.h
    }
    $f = mysqli_fetch_field_direct($result, $i);
    $type = $f->type;
    $charsetnr = $f->charsetnr;
    $f = $f->flags;
    $flags = '';
    if ($f & MYSQLI_UNIQUE_KEY_FLAG)     { $flags .= 'unique ';}
    if ($f & MYSQLI_NUM_FLAG)            { $flags .= 'num ';}
    if ($f & MYSQLI_PART_KEY_FLAG)       { $flags .= 'part_key ';}
    if ($f & MYSQLI_SET_FLAG)            { $flags .= 'set ';}
    if ($f & MYSQLI_TIMESTAMP_FLAG)      { $flags .= 'timestamp ';}
    if ($f & MYSQLI_AUTO_INCREMENT_FLAG) { $flags .= 'auto_increment ';}
    if ($f & MYSQLI_ENUM_FLAG)           { $flags .= 'enum ';}
    // See http://dev.mysql.com/doc/refman/6.0/en/c-api-datatypes.html:
    // to determine if a string is binary, we should not use MYSQLI_BINARY_FLAG
    // but instead the charsetnr member of the MYSQL_FIELD
    // structure. Watch out: some types like DATE returns 63 in charsetnr
    // so we have to check also the type.
    // Unfortunately there is no equivalent in the mysql extension.
    if (($type == MYSQLI_TYPE_TINY_BLOB || $type == MYSQLI_TYPE_BLOB || $type == MYSQLI_TYPE_MEDIUM_BLOB || $type == MYSQLI_TYPE_LONG_BLOB || $type == MYSQLI_TYPE_VAR_STRING || $type == MYSQLI_TYPE_STRING) && 63 == $charsetnr)                { $flags .= 'binary ';}
    if ($f & MYSQLI_ZEROFILL_FLAG)       { $flags .= 'zerofill ';}
    if ($f & MYSQLI_UNSIGNED_FLAG)       { $flags .= 'unsigned ';}
    if ($f & MYSQLI_BLOB_FLAG)           { $flags .= 'blob ';}
    if ($f & MYSQLI_MULTIPLE_KEY_FLAG)   { $flags .= 'multiple_key ';}
    if ($f & MYSQLI_UNIQUE_KEY_FLAG)     { $flags .= 'unique_key ';}
    if ($f & MYSQLI_PRI_KEY_FLAG)        { $flags .= 'primary_key ';}
    if ($f & MYSQLI_NOT_NULL_FLAG)       { $flags .= 'not_null ';}
    return trim($flags);
}

?>
