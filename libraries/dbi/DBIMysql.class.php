<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the classic MySQL extension
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage MySQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/logging.lib.php';
require_once './libraries/dbi/DBIExtension.int.php';

/**
 * MySQL client API
 */
if (! defined('PMA_MYSQL_CLIENT_API')) {
    $client_api = explode('.', mysql_get_client_info());
    define(
        'PMA_MYSQL_CLIENT_API',
        (int)sprintf(
            '%d%02d%02d',
            $client_api[0], $client_api[1], intval($client_api[2])
        )
    );
    unset($client_api);
}

/**
 * Interface to the classic MySQL extension
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage MySQL
 */
class PMA_DBI_Mysql implements PMA_DBI_Extension
{
    /**
     * Helper function for connecting to the database server
     *
     * @param array  $server       host/port/socket
     * @param string $user         mysql user name
     * @param string $password     mysql user password
     * @param int    $client_flags client flags of connection
     * @param bool   $persistent   whether to use peristent connection
     *
     * @return mixed   false on error or a mysql connection resource on success
     */
    private function _realConnect($server, $user, $password, $client_flags,
        $persistent = false
    ) {
        global $cfg;

        if (empty($client_flags)) {
            if ($cfg['PersistentConnections'] || $persistent) {
                $link = @mysql_pconnect($server, $user, $password);
            } else {
                $link = @mysql_connect($server, $user, $password);
            }
        } else {
            if ($cfg['PersistentConnections'] || $persistent) {
                $link = @mysql_pconnect($server, $user, $password, $client_flags);
            } else {
                $link = @mysql_connect(
                    $server, $user, $password, false, $client_flags
                );
            }
        }

        return $link;
    }

    /**
     * Run the multi query and output the results
     *
     * @param mysqli $link  mysqli object
     * @param string $query multi query statement to execute
     *
     * @return boolean false always false since mysql extention not support
     *                       for multi query executions
     */
    public function realMultiQuery($link, $query)
    {
        // N.B.: PHP's 'mysql' extension does not support
        // multi_queries so this function will always
        // return false. Use the 'mysqli' extension, if
        // you need support for multi_queries.
        return false;
    }

    /**
     * connects to the database server
     *
     * @param string $user                 mysql user name
     * @param string $password             mysql user password
     * @param bool   $is_controluser       whether this is a control user connection
     * @param array  $server               host/port/socket/persistent
     * @param bool   $auxiliary_connection (when true, don't go back to login if
     *                                     connection fails)
     *
     * @return mixed false on error or a mysqli object on success
     */
    public function connect(
        $user, $password, $is_controluser = false, $server = null,
        $auxiliary_connection = false
    ) {
        global $cfg;

        if ($server) {
            $server_port = (empty($server['port']))
                ? ''
                : ':' . (int)$server['port'];
            $server_socket = (empty($server['socket']))
                ? ''
                : ':' . $server['socket'];
        } else {
            $server_port   = (empty($cfg['Server']['port']))
                ? ''
                : ':' . (int)$cfg['Server']['port'];
            $server_socket = (empty($cfg['Server']['socket']))
                ? ''
                : ':' . $cfg['Server']['socket'];
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

        if (! $server) {
            $link = $this->_realConnect(
                $cfg['Server']['host'] . $server_port . $server_socket,
                $user, $password, empty($client_flags) ? null : $client_flags
            );

            // Retry with empty password if we're allowed to
            if (empty($link) && $cfg['Server']['nopassword'] && ! $is_controluser) {
                $link = $this->_realConnect(
                    $cfg['Server']['host'] . $server_port . $server_socket,
                    $user, '', empty($client_flags) ? null : $client_flags
                );
            }
        } else {
            if (!isset($server['host'])) {
                $link = $this->_realConnect($server_socket, $user, $password, null);
            } else {
                $link = $this->_realConnect(
                    $server['host'] . $server_port . $server_socket,
                    $user, $password, null
                );
            }
        }
        if (empty($link)) {
            if ($is_controluser) {
                trigger_error(
                    __(
                        'Connection for controluser as defined'
                        . ' in your configuration failed.'
                    ),
                    E_USER_WARNING
                );
                return false;
            }
            // we could be calling $GLOBALS['dbi']->connect() to connect to another
            // server, for example in the Synchronize feature, so do not
            // go back to main login if it fails
            if (! $auxiliary_connection) {
                PMA_logUser($user, 'mysql-denied');
                global $auth_plugin;
                $auth_plugin->authFails();
            } else {
                return false;
            }
        } // end if
        if (! $server) {
            $GLOBALS['dbi']->postConnect($link, $is_controluser);
        }
        return $link;
    }

    /**
     * selects given database
     *
     * @param string   $dbname name of db to select
     * @param resource $link   mysql link resource
     *
     * @return bool
     */
    public function selectDb($dbname, $link = null)
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
     * @param string   $query   query to run
     * @param resource $link    mysql link resource
     * @param int      $options query options
     *
     * @return mixed
     */
    public function realQuery($query, $link, $options)
    {
        if ($options == ($options | PMA_DatabaseInterface::QUERY_STORE)) {
            return mysql_query($query, $link);
        } elseif ($options == ($options | PMA_DatabaseInterface::QUERY_UNBUFFERED)) {
            return mysql_unbuffered_query($query, $link);
        } else {
            return mysql_query($query, $link);
        }
    }

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param resource $result result  MySQL result
     *
     * @return array
     */
    public function fetchArray($result)
    {
        return mysql_fetch_array($result, MYSQL_BOTH);
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param resource $result MySQL result
     *
     * @return array
     */
    public function fetchAssoc($result)
    {
        return mysql_fetch_array($result, MYSQL_ASSOC);
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param resource $result MySQL result
     *
     * @return array
     */
    public function fetchRow($result)
    {
        return mysql_fetch_array($result, MYSQL_NUM);
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param resource $result database result
     * @param integer  $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, $offset)
    {
        return mysql_data_seek($result, $offset);
    }

    /**
     * Frees memory associated with the result
     *
     * @param resource $result database result
     *
     * @return void
     */
    public function freeResult($result)
    {
        if (is_resource($result) && get_resource_type($result) === 'mysql result') {
            mysql_free_result($result);
        }
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param object $link the connection object
     *
     * @return bool false
     */
    public function moreResults($link = null)
    {
        // N.B.: PHP's 'mysql' extension does not support
        // multi_queries so this function will always
        // return false. Use the 'mysqli' extension, if
        // you need support for multi_queries.
        return false;
    }

    /**
     * Prepare next result from multi_query
     *
     * @param object $link the connection object
     *
     * @return boolean false
     */
    public function nextResult($link = null)
    {
        // N.B.: PHP's 'mysql' extension does not support
        // multi_queries so this function will always
        // return false. Use the 'mysqli' extension, if
        // you need support for multi_queries.
        return false;
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param resource $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link = null)
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
     *
     * @param resource $link mysql link
     *
     * @return int version of the MySQL protocol used
     */
    public function getProtoInfo($link = null)
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
     *
     * @return string MySQL client library version
     */
    public function getClientInfo()
    {
        return mysql_get_client_info();
    }

    /**
     * returns last error message or false if no errors occurred
     *
     * @param resource $link mysql link
     *
     * @return string|bool $error or false
     */
    public function getError($link = null)
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

        // keep the error number for further check after
        // the call to getError()
        $GLOBALS['errno'] = $error_number;

        return $GLOBALS['dbi']->formatError($error_number, $error_message);
    }

    /**
     * returns the number of rows returned by last query
     *
     * @param resource $result MySQL result
     *
     * @return string|int
     */
    public function numRows($result)
    {
        if (!is_bool($result)) {
            return mysql_num_rows($result);
        } else {
            return 0;
        }
    }

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     *
     * @param resource $link the mysql object
     *
     * @return string|int
     */
    public function insertId($link = null)
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
        return $GLOBALS['dbi']->fetchValue('SELECT LAST_INSERT_ID();', 0, 0, $link);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param resource $link           the mysql object
     * @param bool     $get_from_cache whether to retrieve from cache
     *
     * @return string|int
     */
    public function affectedRows($link = null, $get_from_cache = true)
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
     * returns metainfo for fields in $result
     *
     * @param resource $result MySQL result
     *
     * @return array meta info for fields in $result
     *
     * @todo add missing keys like in mysqli_query (decimals)
     */
    public function getFieldsMeta($result)
    {
        $fields       = array();
        $num_fields   = mysql_num_fields($result);
        for ($i = 0; $i < $num_fields; $i++) {
            $field = mysql_fetch_field($result, $i);
            $field->flags = mysql_field_flags($result, $i);
            $field->orgtable = mysql_field_table($result, $i);
            $field->orgname = mysql_field_name($result, $i);
            $fields[] = $field;
        }
        return $fields;
    }

    /**
     * return number of fields in given $result
     *
     * @param resource $result MySQL result
     *
     * @return int  field count
     */
    public function numFields($result)
    {
        return mysql_num_fields($result);
    }

    /**
     * returns the length of the given field $i in $result
     *
     * @param resource $result MySQL result
     * @param int      $i      field
     *
     * @return int length of field
     */
    public function fieldLen($result, $i)
    {
        return mysql_field_len($result, $i);
    }

    /**
     * returns name of $i. field in $result
     *
     * @param resource $result MySQL result
     * @param int      $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, $i)
    {
        return mysql_field_name($result, $i);
    }

    /**
     * returns concatenated string of human readable field flags
     *
     * @param resource $result MySQL result
     * @param int      $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i)
    {
        return mysql_field_flags($result, $i);
    }

    /**
     * Store the result returned from multi query
     *
     * @return false
     */
    public function storeResult()
    {
        return false;
    }
}
?>