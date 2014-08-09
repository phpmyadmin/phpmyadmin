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

require_once './libraries/dbi/DBIExtension.int.php';

/**
 * MySQL client API
 */
PMA_defineClientAPI(mysql_get_client_info());

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
     * @param string $server       host/port/socket
     * @param string $user         mysql user name
     * @param string $password     mysql user password
     * @param int    $client_flags client flags of connection
     * @param bool   $persistent   whether to use persistent connection
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
     * @return boolean false always false since mysql extension not support
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

        $server_port = $GLOBALS['dbi']->getServerPort($server);
        $server_socket = $GLOBALS['dbi']->getServerSocket($server);

        if ($server_port === null) {
            $server_port = '';
        } else {
            $server_port = ':' . $server_port;
        }

        if (is_null($server_socket)) {
            $server_socket = '';
        } else {
            $server_socket = ':' . $server_socket;
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
        return $link;
    }

    /**
     * selects given database
     *
     * @param string        $dbname name of db to select
     * @param resource|null $link   mysql link resource
     *
     * @return bool
     */
    public function selectDb($dbname, $link)
    {
        return mysql_select_db($dbname, $link);
    }

    /**
     * runs a query and returns the result
     *
     * @param string        $query   query to run
     * @param resource|null $link    mysql link resource
     * @param int           $options query options
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
    public function moreResults($link)
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
    public function nextResult($link)
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
     * @param resource|null $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link)
    {
        return mysql_get_host_info($link);
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param resource|null $link mysql link
     *
     * @return int version of the MySQL protocol used
     */
    public function getProtoInfo($link)
    {
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
     * @param resource|null $link mysql link
     *
     * @return string|bool $error or false
     */
    public function getError($link)
    {
        $GLOBALS['errno'] = 0;

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
        if (is_bool($result)) {
            return 0;
        }

        return mysql_num_rows($result);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param resource|null $link the mysql object
     *
     * @return int
     */
    public function affectedRows($link)
    {
        return mysql_affected_rows($link);
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
     * @param resource $result MySQL result
     *
     * @return false
     */
    public function storeResult($result)
    {
        return false;
    }
}
?>
