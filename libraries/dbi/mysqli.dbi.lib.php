<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the improved MySQL extension (MySQLi)
 *
 * @package PhpMyAdmin-DBI-MySQLi
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

// for Drizzle
if (! defined('MYSQLI_TYPE_VARCHAR')) {
    define('MYSQLI_TYPE_VARCHAR', 15);
}

/**
 * Helper function for connecting to the database server
 *
 * @param   mysqli  $link
 * @param   string  $host
 * @param   string  $user
 * @param   string  $password
 * @param   string  $dbname
 * @param   int     $server_port
 * @param   string  $server_socket
 * @param   int     $client_flags
 * @param   bool    $persistent
 * @return  bool
 */
function PMA_DBI_real_connect($link, $host, $user, $password, $dbname, $server_port, $server_socket, $client_flags = null, $persistent = false)
{
    global $cfg;

    // mysqli persistent connections only on PHP 5.3+
    if (PMA_PHP_INT_VERSION >= 50300) {
        if ($cfg['PersistentConnections'] || $persistent) {
            $host = 'p:' . $host;
        }
    }
    if ($client_flags === null) {
        return @mysqli_real_connect(
            $link,
            $host,
            $user,
            $password,
            $dbname,
            $server_port,
            $server_socket
        );
    } else {
        return @mysqli_real_connect(
            $link,
            $host,
            $user,
            $password,
            $dbname,
            $server_port,
            $server_socket,
            $client_flags
        );
    }
}

/**
 * connects to the database server
 *
 * @param   string  $user           mysql user name
 * @param   string  $password       mysql user password
 * @param   bool    $is_controluser
 * @param   array   $server host/port/socket
 * @param   bool    $auxiliary_connection (when true, don't go back to login if connection fails)
 * @return  mixed   false on error or a mysqli object on success
 */
function PMA_DBI_connect($user, $password, $is_controluser = false, $server = null, $auxiliary_connection = false)
{
    global $cfg;

    if ($server) {
        $server_port   = (empty($server['port']))
            ? false
            : (int)$server['port'];
        $server_socket = (empty($server['socket']))
            ? ''
            : $server['socket'];
        $server['host'] = (empty($server['host']))
            ? 'localhost'
            : $server['host'];
    } else {
        $server_port   = (empty($cfg['Server']['port']))
            ? false
            : (int) $cfg['Server']['port'];
        $server_socket = (empty($cfg['Server']['socket']))
            ? null
            : $cfg['Server']['socket'];
    }

    // NULL enables connection to the default socket

    $link = mysqli_init();

    mysqli_options($link, MYSQLI_OPT_LOCAL_INFILE, true);

    $client_flags = 0;

    /* Optionally compress connection */
    if ($cfg['Server']['compress'] && defined('MYSQLI_CLIENT_COMPRESS')) {
        $client_flags |= MYSQLI_CLIENT_COMPRESS;
    }

    /* Optionally enable SSL */
    if ($cfg['Server']['ssl'] && defined('MYSQLI_CLIENT_SSL')) {
        $client_flags |= MYSQLI_CLIENT_SSL;
    }

    if (!$server) {
        $return_value = @PMA_DBI_real_connect(
            $link,
            $cfg['Server']['host'],
            $user,
            $password,
            false,
            $server_port,
            $server_socket,
            $client_flags
        );
        // Retry with empty password if we're allowed to
        if ($return_value == false && isset($cfg['Server']['nopassword']) && $cfg['Server']['nopassword'] && !$is_controluser) {
            $return_value = @PMA_DBI_real_connect(
                $link,
                $cfg['Server']['host'],
                $user,
                '',
                false,
                $server_port,
                $server_socket,
                $client_flags
            );
        }
    } else {
        $return_value = @PMA_DBI_real_connect(
            $link,
            $server['host'],
            $user,
            $password,
            false,
            $server_port,
            $server_socket
        );
    }

    if ($return_value == false) {
        if ($is_controluser) {
            trigger_error(
                __('Connection for controluser as defined in your configuration failed.'),
                E_USER_WARNING
            );
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
 * @param string  $dbname  database name to select
 * @param mysqli  $link    the mysqli object
 * @return boolean
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
 * @param   string  $query    query to execute
 * @param   mysqli  $link     mysqli object
 * @param   int     $options
 * @return  mysqli_result|bool
 */
function PMA_DBI_real_query($query, $link, $options)
{
    if ($options == ($options | PMA_DBI_QUERY_STORE)) {
        $method = MYSQLI_STORE_RESULT;
    } elseif ($options == ($options | PMA_DBI_QUERY_UNBUFFERED)) {
        $method = MYSQLI_USE_RESULT;
    } else {
        $method = 0;
    }

    return mysqli_query($link, $query, $method);
}

/**
 * returns array of rows with associative and numeric keys from $result
 *
 * @param   mysqli_result  $result
 * @return  array
 */
function PMA_DBI_fetch_array($result)
{
    return mysqli_fetch_array($result, MYSQLI_BOTH);
}

/**
 * returns array of rows with associative keys from $result
 *
 * @param   mysqli_result  $result
 * @return  array
 */
function PMA_DBI_fetch_assoc($result)
{
    return mysqli_fetch_array($result, MYSQLI_ASSOC);
}

/**
 * returns array of rows with numeric keys from $result
 *
 * @param   mysqli_result  $result
 * @return  array
 */
function PMA_DBI_fetch_row($result)
{
    return mysqli_fetch_array($result, MYSQLI_NUM);
}

/**
 * Adjusts the result pointer to an arbitrary row in the result
 *
 * @param   $result
 * @param   $offset
 * @return  bool  true on success, false on failure
 */
function PMA_DBI_data_seek($result, $offset)
{
    return mysqli_data_seek($result, $offset);
}

/**
 * Frees memory associated with the result
 *
 * @param  mysqli_result  $result
 */
function PMA_DBI_free_result($result)
{
    if ($result instanceof mysqli_result) {
        mysqli_free_result($result);
    }
}

/**
 * Check if there are any more query results from a multi query
 *
 * @param   mysqli  $link  the mysqli object
 * @return  bool         true or false
 */
function PMA_DBI_more_results($link = null)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    return mysqli_more_results($link);
}

/**
 * Prepare next result from multi_query
 *
 * @param   mysqli  $link  the mysqli object
 * @return  bool         true or false
 */
function PMA_DBI_next_result($link = null)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    return mysqli_next_result($link);
}

/**
 * Returns a string representing the type of connection used
 *
 * @param   resource  $link  mysql link
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
 *
 * @param   resource  $link  mysql link
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
 *
 * @return  string          MySQL client library version
 */
function PMA_DBI_get_client_info()
{
    return mysqli_get_client_info();
}

/**
 * returns last error message or false if no errors occured
 *
 * @param   resource  $link  mysql link
 * @return  string|bool  $error or false
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

    return PMA_DBI_formatError($error_number, $error_message);
}

/**
 * returns the number of rows returned by last query
 *
 * @param   mysqli_result  $result
 * @return  string|int
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
 * @param   mysqli  $link  the mysqli object
 * @return  string|int
 */
function PMA_DBI_insert_id($link = null)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }
    // When no controluser is defined, using mysqli_insert_id($link)
    // does not always return the last insert id due to a mixup with
    // the tracking mechanism, but this works:
    return PMA_DBI_fetch_value('SELECT LAST_INSERT_ID();', 0, 0, $link);
    // Curiously, this problem does not happen with the mysql extension but
    // there is another problem with BIGINT primary keys so PMA_DBI_insert_id()
    // in the mysql extension also uses this logic.
}

/**
 * returns the number of rows affected by last query
 *
 * @param   mysqli   $link            the mysqli object
 * @param   boolean  $get_from_cache
 * @return  string|int
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
        return mysqli_affected_rows($link);
    }
}

/**
 * returns metainfo for fields in $result
 *
 * @param   mysqli_result  $result
 * @return  array  meta info for fields in $result
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
    $typeAr[MYSQLI_TYPE_VARCHAR]     = 'string'; // for Drizzle
    // MySQL returns MYSQLI_TYPE_STRING for CHAR
    // and MYSQLI_TYPE_CHAR === MYSQLI_TYPE_TINY
    // so this would override TINYINT and mark all TINYINT as string
    // https://sf.net/tracker/?func=detail&aid=1532111&group_id=23067&atid=377408
    //$typeAr[MYSQLI_TYPE_CHAR]        = 'string';
    $typeAr[MYSQLI_TYPE_GEOMETRY]    = 'geometry';
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
 * @param   mysqli_result  $result
 * @return  int  field count
 */
function PMA_DBI_num_fields($result)
{
    return mysqli_num_fields($result);
}

/**
 * returns the length of the given field $i in $result
 *
 * @param   mysqli_result  $result
 * @param   int            $i       field
 * @return  int  length of field
 */
function PMA_DBI_field_len($result, $i)
{
    return mysqli_fetch_field_direct($result, $i)->length;
}

/**
 * returns name of $i. field in $result
 *
 * @param   mysqli_result  $result
 * @param   int            $i       field
 * @return  string  name of $i. field in $result
 */
function PMA_DBI_field_name($result, $i)
{
    return mysqli_fetch_field_direct($result, $i)->name;
}

/**
 * returns concatenated string of human readable field flags
 *
 * @param   mysqli_result  $result
 * @param   int            $i       field
 * @return  string  field flags
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
    if ($f & MYSQLI_UNIQUE_KEY_FLAG) {
        $flags .= 'unique ';
    }
    if ($f & MYSQLI_NUM_FLAG) {
        $flags .= 'num ';
    }
    if ($f & MYSQLI_PART_KEY_FLAG) {
        $flags .= 'part_key ';
    }
    if ($f & MYSQLI_SET_FLAG) {
        $flags .= 'set ';
    }
    if ($f & MYSQLI_TIMESTAMP_FLAG) {
        $flags .= 'timestamp ';
    }
    if ($f & MYSQLI_AUTO_INCREMENT_FLAG) {
        $flags .= 'auto_increment ';
    }
    if ($f & MYSQLI_ENUM_FLAG) {
        $flags .= 'enum ';
    }
    // See http://dev.mysql.com/doc/refman/6.0/en/c-api-datatypes.html:
    // to determine if a string is binary, we should not use MYSQLI_BINARY_FLAG
    // but instead the charsetnr member of the MYSQL_FIELD
    // structure. Watch out: some types like DATE returns 63 in charsetnr
    // so we have to check also the type.
    // Unfortunately there is no equivalent in the mysql extension.
    if (($type == MYSQLI_TYPE_TINY_BLOB || $type == MYSQLI_TYPE_BLOB || $type == MYSQLI_TYPE_MEDIUM_BLOB || $type == MYSQLI_TYPE_LONG_BLOB || $type == MYSQLI_TYPE_VAR_STRING || $type == MYSQLI_TYPE_STRING) && 63 == $charsetnr) {
        $flags .= 'binary ';
    }
    if ($f & MYSQLI_ZEROFILL_FLAG) {
        $flags .= 'zerofill ';
    }
    if ($f & MYSQLI_UNSIGNED_FLAG) {
        $flags .= 'unsigned ';
    }
    if ($f & MYSQLI_BLOB_FLAG) {
        $flags .= 'blob ';
    }
    if ($f & MYSQLI_MULTIPLE_KEY_FLAG) {
        $flags .= 'multiple_key ';
    }
    if ($f & MYSQLI_UNIQUE_KEY_FLAG) {
        $flags .= 'unique_key ';
    }
    if ($f & MYSQLI_PRI_KEY_FLAG) {
        $flags .= 'primary_key ';
    }
    if ($f & MYSQLI_NOT_NULL_FLAG) {
        $flags .= 'not_null ';
    }
    return trim($flags);
}

?>
