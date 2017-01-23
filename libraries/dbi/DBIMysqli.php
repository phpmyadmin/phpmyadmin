<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the improved MySQL extension (MySQLi)
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage MySQLi
 */
namespace PMA\libraries\dbi;

use PMA\libraries\DatabaseInterface;

if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/dbi/DBIExtension.lib.php';

/**
 * MySQL client API
 */
PMA_defineClientAPI(mysqli_get_client_info());

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
 * @see https://bugs.php.net/36007
 */
if (! defined('MYSQLI_TYPE_NEWDECIMAL')) {
    define('MYSQLI_TYPE_NEWDECIMAL', 246);
}
if (! defined('MYSQLI_TYPE_BIT')) {
    define('MYSQLI_TYPE_BIT', 16);
}
if (! defined('MYSQLI_TYPE_JSON')) {
    define('MYSQLI_TYPE_JSON', 245);
}


/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the improved MySQL extension (MySQLi)
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage MySQLi
 */
class DBIMysqli implements DBIExtension
{
    /**
     * Helper function for connecting to the database server
     *
     * @param mysqli $link          connection link
     * @param string $host          mysql hostname
     * @param string $user          mysql user name
     * @param string $password      mysql user password
     * @param int    $server_port   server port
     * @param string $server_socket server socket
     * @param int    $client_flags  client flags of connection
     * @param bool   $persistent    whether to use persistent connection
     *
     * @return bool
     */
    private function _realConnect(
        $link, $host, $user, $password, $server_port,
        $server_socket, $client_flags = null, $persistent = false
    ) {
        global $cfg;

        // mysqli persistent connections
        if ($cfg['PersistentConnections'] || $persistent) {
            $host = 'p:' . $host;
        }

        if ($client_flags === null) {
            return mysqli_real_connect(
                $link,
                $host,
                $user,
                $password,
                '',
                $server_port,
                $server_socket
            );
        } else {
            return mysqli_real_connect(
                $link,
                $host,
                $user,
                $password,
                '',
                $server_port,
                $server_socket,
                $client_flags
            );
        }
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

        if ($server) {
            $server['host'] = (empty($server['host']))
                ? 'localhost'
                : $server['host'];
        }

        // NULL enables connection to the default socket

        $link = mysqli_init();

        if (defined('PMA_ENABLE_LDI')) {
            mysqli_options($link, MYSQLI_OPT_LOCAL_INFILE, true);
        } else {
            mysqli_options($link, MYSQLI_OPT_LOCAL_INFILE, false);
        }

        $client_flags = 0;

        /* Optionally compress connection */
        if ($cfg['Server']['compress'] && defined('MYSQLI_CLIENT_COMPRESS')) {
            $client_flags |= MYSQLI_CLIENT_COMPRESS;
        }

        /* Optionally enable SSL */
        if ($cfg['Server']['ssl']) {
            $client_flags |= MYSQLI_CLIENT_SSL;
            if (! is_null($cfg['Server']['ssl_key']) ||
                ! is_null($cfg['Server']['ssl_cert']) ||
                ! is_null($cfg['Server']['ssl_ca']) ||
                ! is_null($cfg['Server']['ssl_ca_paths']) ||
                ! is_null($cfg['Server']['ssl_ciphers'])
            ) {
                mysqli_ssl_set(
                    $link,
                    $cfg['Server']['ssl_key'],
                    $cfg['Server']['ssl_cert'],
                    $cfg['Server']['ssl_ca'],
                    $cfg['Server']['ssl_ca_path'],
                    $cfg['Server']['ssl_ciphers']
                );
            }
            /*
             * disables SSL certificate validation on mysqlnd for MySQL 5.6 or later
             * @link https://bugs.php.net/bug.php?id=68344
             * @link https://github.com/phpmyadmin/phpmyadmin/pull/11838
             */
            if (! $cfg['Server']['ssl_verify']) {
                mysqli_options(
                    $link,
                    MYSQLI_OPT_SSL_VERIFY_SERVER_CERT,
                    $cfg['Server']['ssl_verify']
                );
                $client_flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
        }

        if (! $server) {
            $return_value = $this->_realConnect(
                $link,
                $cfg['Server']['host'],
                $user,
                $password,
                $server_port,
                $server_socket,
                $client_flags
            );
            // Retry with empty password if we're allowed to
            if ($return_value == false
                && isset($cfg['Server']['nopassword'])
                && $cfg['Server']['nopassword']
                && ! $is_controluser
            ) {
                $return_value = $this->_realConnect(
                    $link,
                    $cfg['Server']['host'],
                    $user,
                    '',
                    $server_port,
                    $server_socket,
                    $client_flags
                );
            }
        } else {
            $return_value = $this->_realConnect(
                $link,
                $server['host'],
                $user,
                $password,
                $server_port,
                $server_socket
            );
        }

        if ($return_value === false || is_null($return_value)) {
            return false;
        }

        return $link;
    }

    /**
     * selects given database
     *
     * @param string $dbname database name to select
     * @param mysqli $link   the mysqli object
     *
     * @return boolean
     */
    public function selectDb($dbname, $link)
    {
        return mysqli_select_db($link, $dbname);
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query   query to execute
     * @param mysqli $link    mysqli object
     * @param int    $options query options
     *
     * @return mysqli_result|bool
     */
    public function realQuery($query, $link, $options)
    {
        if ($options == ($options | DatabaseInterface::QUERY_STORE)) {
            $method = MYSQLI_STORE_RESULT;
        } elseif ($options == ($options | DatabaseInterface::QUERY_UNBUFFERED)) {
            $method = MYSQLI_USE_RESULT;
        } else {
            $method = 0;
        }

        return mysqli_query($link, $query, $method);
    }

    /**
     * Run the multi query and output the results
     *
     * @param mysqli $link  mysqli object
     * @param string $query multi query statement to execute
     *
     * @return mysqli_result collection | boolean(false)
     */
    public function realMultiQuery($link, $query)
    {
        return mysqli_multi_query($link, $query);
    }

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param mysqli_result $result result set identifier
     *
     * @return array
     */
    public function fetchArray($result)
    {
        return mysqli_fetch_array($result, MYSQLI_BOTH);
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param mysqli_result $result result set identifier
     *
     * @return array
     */
    public function fetchAssoc($result)
    {
        return mysqli_fetch_array($result, MYSQLI_ASSOC);
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param mysqli_result $result result set identifier
     *
     * @return array
     */
    public function fetchRow($result)
    {
        return mysqli_fetch_array($result, MYSQLI_NUM);
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param mysqli_result $result database result
     * @param integer       $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, $offset)
    {
        return mysqli_data_seek($result, $offset);
    }

    /**
     * Frees memory associated with the result
     *
     * @param mysqli_result $result database result
     *
     * @return void
     */
    public function freeResult($result)
    {
        if ($result instanceof mysqli_result) {
            mysqli_free_result($result);
        }
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param mysqli $link the mysqli object
     *
     * @return bool true or false
     */
    public function moreResults($link)
    {
        return mysqli_more_results($link);
    }

    /**
     * Prepare next result from multi_query
     *
     * @param mysqli $link the mysqli object
     *
     * @return bool true or false
     */
    public function nextResult($link)
    {
        return mysqli_next_result($link);
    }

    /**
     * Store the result returned from multi query
     *
     * @param mysqli $link the mysqli object
     *
     * @return mixed false when empty results / result set when not empty
     */
    public function storeResult($link)
    {
        return mysqli_store_result($link);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param resource $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link)
    {
        return mysqli_get_host_info($link);
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param resource $link mysql link
     *
     * @return integer version of the MySQL protocol used
     */
    public function getProtoInfo($link)
    {
        return mysqli_get_proto_info($link);
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo()
    {
        return mysqli_get_client_info();
    }

    /**
     * returns last error message or false if no errors occurred
     *
     * @param resource $link mysql link
     *
     * @return string|bool $error or false
     */
    public function getError($link)
    {
        $GLOBALS['errno'] = 0;

        if (null !== $link && false !== $link) {
            $error_number = mysqli_errno($link);
            $error_message = mysqli_error($link);
        } else {
            $error_number = mysqli_connect_errno();
            $error_message = mysqli_connect_error();
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
     * @param mysqli_result $result result set identifier
     *
     * @return string|int
     */
    public function numRows($result)
    {
        // see the note for tryQuery();
        if (is_bool($result)) {
            return 0;
        }

        return @mysqli_num_rows($result);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param mysqli $link the mysqli object
     *
     * @return int
     */
    public function affectedRows($link)
    {
        return mysqli_affected_rows($link);
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param mysqli_result $result result set identifier
     *
     * @return array meta info for fields in $result
     */
    public function getFieldsMeta($result)
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
        // https://sourceforge.net/p/phpmyadmin/bugs/2205/
        //$typeAr[MYSQLI_TYPE_CHAR]        = 'string';
        $typeAr[MYSQLI_TYPE_GEOMETRY]    = 'geometry';
        $typeAr[MYSQLI_TYPE_BIT]         = 'bit';
        $typeAr[MYSQLI_TYPE_JSON]        = 'json';

        $fields = mysqli_fetch_fields($result);

        // this happens sometimes (seen under MySQL 4.0.25)
        if (!is_array($fields)) {
            return false;
        }

        foreach ($fields as $k => $field) {
            $fields[$k]->_type = $field->type;
            $fields[$k]->type = $typeAr[$field->type];
            $fields[$k]->_flags = $field->flags;
            $fields[$k]->flags = $this->fieldFlags($result, $k);

            // Enhance the field objects for mysql-extension compatibility
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
     * @param mysqli_result $result result set identifier
     *
     * @return int field count
     */
    public function numFields($result)
    {
        return mysqli_num_fields($result);
    }

    /**
     * returns the length of the given field $i in $result
     *
     * @param mysqli_result $result result set identifier
     * @param int           $i      field
     *
     * @return int length of field
     */
    public function fieldLen($result, $i)
    {
        return mysqli_fetch_field_direct($result, $i)->length;
    }

    /**
     * returns name of $i. field in $result
     *
     * @param mysqli_result $result result set identifier
     * @param int           $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, $i)
    {
        return mysqli_fetch_field_direct($result, $i)->name;
    }

    /**
     * returns concatenated string of human readable field flags
     *
     * @param mysqli_result $result result set identifier
     * @param int           $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i)
    {
        $f = mysqli_fetch_field_direct($result, $i);
        $type = $f->type;
        $charsetnr = $f->charsetnr;
        $f = $f->flags;
        $flags = array();
        foreach ($GLOBALS['pma_mysqli_flag_names'] as $flag => $name) {
            if ($f & $flag) {
                $flags[] = $name;
            }
        }
        // See https://dev.mysql.com/doc/refman/6.0/en/c-api-datatypes.html:
        // to determine if a string is binary, we should not use MYSQLI_BINARY_FLAG
        // but instead the charsetnr member of the MYSQL_FIELD
        // structure. Watch out: some types like DATE returns 63 in charsetnr
        // so we have to check also the type.
        // Unfortunately there is no equivalent in the mysql extension.
        if (($type == MYSQLI_TYPE_TINY_BLOB || $type == MYSQLI_TYPE_BLOB
            || $type == MYSQLI_TYPE_MEDIUM_BLOB || $type == MYSQLI_TYPE_LONG_BLOB
            || $type == MYSQLI_TYPE_VAR_STRING || $type == MYSQLI_TYPE_STRING)
            && 63 == $charsetnr
        ) {
            $flags[] = 'binary';
        }
        return implode(' ', $flags);
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param mixed  $link database link
     * @param string $str  string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString($link, $str)
    {
        return mysqli_real_escape_string($link, $str);
    }
}
