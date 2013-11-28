<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the Drizzle extension
 *
 * WARNING - EXPERIMENTAL, never use in production,
 * drizzle module segfaults often and when you least expect it to
 *
 * TODO: This file and drizzle-wrappers.lib.php should be devoid
 *       of any segault related hacks.
 * TODO: Crashing versions of drizzle module and/or libdrizzle
 *       should be blacklisted
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Drizzle
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/logging.lib.php';
require_once './libraries/dbi/drizzle-wrappers.lib.php';
require_once './libraries/dbi/DBIExtension.int.php';

/**
 * MySQL client API
 */
if (! defined('PMA_MYSQL_CLIENT_API')) {
    define('PMA_MYSQL_CLIENT_API', (int)drizzle_version());
}

/**
 * Interface to the Drizzle extension
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Drizzle
 */
class PMA_DBI_Drizzle implements PMA_DBI_Extension
{
    /**
     * Helper function for connecting to the database server
     *
     * @param PMA_Drizzle $drizzle  connection handle
     * @param string      $host     Drizzle host
     * @param integer     $port     Drizzle port
     * @param string      $uds      server socket
     * @param string      $user     username
     * @param string      $password password
     * @param string      $db       database name
     * @param integer     $options  connection options
     *
     * @return PMA_DrizzleCon
     */
    private function _realConnect($drizzle, $host, $port, $uds, $user, $password,
        $db = null, $options = DRIZZLE_CON_NONE
    ) {
        if ($uds) {
            $con = $drizzle->addUds($uds, $user, $password, $db, $options);
        } else {
            $con = $drizzle->addTcp($host, $port, $user, $password, $db, $options);
        }

        return $con;
    }

    /**
     * connects to the database server
     *
     * @param string $user                 drizzle user name
     * @param string $password             drizzle user password
     * @param bool   $is_controluser       whether this is a control user connection
     * @param array  $server               host/port/socket/persistent
     * @param bool   $auxiliary_connection (when true, don't go back to login if
     *                                     connection fails)
     *
     * @return mixed false on error or a mysqli object on success
     */
    public function connect($user, $password, $is_controluser = false,
        $server = null, $auxiliary_connection = false
    ) {
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

        if (strtolower($GLOBALS['cfg']['Server']['connect_type']) == 'tcp') {
            $GLOBALS['cfg']['Server']['socket'] = '';
        }

        $drizzle = new PMA_Drizzle();

        $client_flags = 0;

        /* Optionally compress connection */
        if ($GLOBALS['cfg']['Server']['compress']) {
            $client_flags |= DRIZZLE_CAPABILITIES_COMPRESS;
        }

        /* Optionally enable SSL */
        if ($GLOBALS['cfg']['Server']['ssl']) {
            $client_flags |= DRIZZLE_CAPABILITIES_SSL;
        }

        if (! $server) {
            $link = @$this->_realConnect(
                $drizzle, $cfg['Server']['host'],
                $server_port, $server_socket, $user,
                $password, false, $client_flags
            );
            // Retry with empty password if we're allowed to
            if ($link == false && isset($cfg['Server']['nopassword'])
                && $cfg['Server']['nopassword'] && ! $is_controluser
            ) {
                $link = @$this->_realConnect(
                    $drizzle, $cfg['Server']['host'], $server_port, $server_socket,
                    $user, null, false, $client_flags
                );
            }
        } else {
            $link = @$this->_realConnect(
                $drizzle, $server['host'], $server_port, $server_socket,
                $user, $password
            );
        }

        if ($link == false) {
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
                PMA_logUser($user, 'drizzle-denied');
                global $auth_plugin;
                $auth_plugin->authFails();
            } else {
                return false;
            }
        } else {
            $GLOBALS['dbi']->postConnect($link, $is_controluser);
        }

        return $link;
    }

    /**
     * selects given database
     *
     * @param string         $dbname database name to select
     * @param PMA_DrizzleCom $link   connection object
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
        return $link->selectDb($dbname);
    }

    /**
     * runs a query and returns the result
     *
     * @param string         $query   query to execute
     * @param PMA_DrizzleCon $link    connection object
     * @param int            $options query options
     *
     * @return PMA_DrizzleResult
     */
    public function realQuery($query, $link, $options)
    {
        $buffer_mode = $options & PMA_DatabaseInterface::QUERY_UNBUFFERED
            ? PMA_Drizzle::BUFFER_ROW
            : PMA_Drizzle::BUFFER_RESULT;
        $res = $link->query($query, $buffer_mode);
        return $res;
    }

    /**
     * Run the multi query and output the results
     *
     * @param object $link  connection object
     * @param string $query multi query statement to execute
     *
     * @return result collection | boolean(false)
     */
    public function realMultiQuery($link, $query)
    {
        return false;
    }

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     *
     * @return array
     */
    public function fetchArray($result)
    {
        return $result->fetchRow(PMA_Drizzle::FETCH_BOTH);
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     *
     * @return array
     */
    public function fetchAssoc($result)
    {
        return $result->fetchRow(PMA_Drizzle::FETCH_ASSOC);
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     *
     * @return array
     */
    public function fetchRow($result)
    {
        return $result->fetchRow(PMA_Drizzle::FETCH_NUM);
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     * @param int               $offset offset to seek
     *
     * @return boolean true on success, false on failure
     */
    public function dataSeek($result, $offset)
    {
        return $result->seek($offset);
    }

    /**
     * Frees memory associated with the result
     *
     * @param PMA_DrizzleResult $result database result
     *
     * @return void
     */
    public function freeResult($result)
    {
        if ($result instanceof PMA_DrizzleResult) {
            $result->free();
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
     * @return bool false
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
     * @param PMA_DrizzleCon $link connection object
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

        $str = $link->port()
            ? $link->host() . ':' . $link->port() . ' via TCP/IP'
            : 'Localhost via UNIX socket';
        return $str;
    }

    /**
     * Returns the version of the Drizzle protocol used
     *
     * @param PMA_DrizzleCon $link connection object
     *
     * @return int version of the Drizzle protocol used
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

        return $link->protocolVersion();
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string Drizzle client library version
     */
    public function getClientInfo()
    {
        return 'libdrizzle (Drizzle ' . drizzle_version() . ')';
    }

    /**
     * returns last error message or false if no errors occurred
     *
     * @param PMA_DrizzleCon $link connection object
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
            // Do not stop now. We still can get the error code
            // with mysqli_connect_errno()
            // } else {
            //    return false;
        }

        if (null !== $link) {
            $error_number = drizzle_con_errno($link->getConnectionObject());
            $error_message = drizzle_con_error($link->getConnectionObject());
        } else {
            $error_number = drizzle_errno();
            $error_message = drizzle_error();
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
     * @param PMA_DrizzleResult $result Drizzle result object
     *
     * @return string|int
     */
    public function numRows($result)
    {
        // see the note for $GLOBALS['dbi']->tryQuery();
        if (!is_bool($result)) {
            return @$result->numRows();
        } else {
            return 0;
        }
    }

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     *
     * @param PMA_DrizzleCon $link connection object
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

        // copied from mysql and mysqli

        // When no controluser is defined, using mysqli_insert_id($link)
        // does not always return the last insert id due to a mixup with
        // the tracking mechanism, but this works:
        return $GLOBALS['dbi']->fetchValue('SELECT LAST_INSERT_ID();', 0, 0, $link);
        // Curiously, this problem does not happen with the mysql extension but
        // there is another problem with BIGINT primary keys so insertId()
        // in the mysql extension also uses this logic.
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param PMA_DrizzleResult $link           connection object
     * @param bool              $get_from_cache whether to retrieve from cache
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
            return $link->affectedRows();
        }
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     *
     * @return array meta info for fields in $result
     */
    public function getFieldsMeta($result)
    {
        // Build an associative array for a type look up
        $typeAr = array();
        /*$typeAr[DRIZZLE_COLUMN_TYPE_DECIMAL]     = 'real';
        $typeAr[DRIZZLE_COLUMN_TYPE_NEWDECIMAL]  = 'real';
        $typeAr[DRIZZLE_COLUMN_TYPE_BIT]         = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_TINY]        = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_SHORT]       = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_LONG]        = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_FLOAT]       = 'real';
        $typeAr[DRIZZLE_COLUMN_TYPE_DOUBLE]      = 'real';
        $typeAr[DRIZZLE_COLUMN_TYPE_NULL]        = 'null';
        $typeAr[DRIZZLE_COLUMN_TYPE_TIMESTAMP]   = 'timestamp';
        $typeAr[DRIZZLE_COLUMN_TYPE_LONGLONG]    = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_INT24]       = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_DATE]        = 'date';
        $typeAr[DRIZZLE_COLUMN_TYPE_TIME]        = 'date';
        $typeAr[DRIZZLE_COLUMN_TYPE_DATETIME]    = 'datetime';
        $typeAr[DRIZZLE_COLUMN_TYPE_YEAR]        = 'year';
        $typeAr[DRIZZLE_COLUMN_TYPE_NEWDATE]     = 'date';
        $typeAr[DRIZZLE_COLUMN_TYPE_ENUM]        = 'unknown';
        $typeAr[DRIZZLE_COLUMN_TYPE_SET]         = 'unknown';
        $typeAr[DRIZZLE_COLUMN_TYPE_VIRTUAL]     = 'unknown';
        $typeAr[DRIZZLE_COLUMN_TYPE_TINY_BLOB]   = 'blob';
        $typeAr[DRIZZLE_COLUMN_TYPE_MEDIUM_BLOB] = 'blob';
        $typeAr[DRIZZLE_COLUMN_TYPE_LONG_BLOB]   = 'blob';
        $typeAr[DRIZZLE_COLUMN_TYPE_BLOB]        = 'blob';
        $typeAr[DRIZZLE_COLUMN_TYPE_VAR_STRING]  = 'string';
        $typeAr[DRIZZLE_COLUMN_TYPE_VARCHAR]     = 'string';
        $typeAr[DRIZZLE_COLUMN_TYPE_STRING]      = 'string';
        $typeAr[DRIZZLE_COLUMN_TYPE_GEOMETRY]    = 'geometry';*/

        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_BLOB]      = 'blob';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_DATE]      = 'date';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_DATETIME]  = 'datetime';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_DOUBLE]    = 'real';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_ENUM]      = 'unknown';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_LONG]      = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_LONGLONG]  = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_MAX]       = 'unknown';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_NULL]      = 'null';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_TIMESTAMP] = 'timestamp';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_TINY]      = 'int';
        $typeAr[DRIZZLE_COLUMN_TYPE_DRIZZLE_VARCHAR]   = 'string';

        // array of DrizzleColumn
        $columns = $result->getColumns();
        // columns in a standarized format
        $std_columns = array();

        foreach ($columns as $k => $column) {
            $c = new stdClass();
            $c->name = $column->name();
            $c->orgname = $column->origName();
            $c->table = $column->table();
            $c->orgtable = $column->origTable();
            $c->def = $column->defaultValue();
            $c->db = $column->db();
            $c->catalog = $column->catalog();
            // $column->maxSize() returns always 0 while size() seems
            // to return a correct value (drizzle extension v.0.5, API v.7)
            $c->max_length = $column->size();
            $c->decimals = $column->decimals();
            $c->charsetnr = $column->charset();
            $c->type = $typeAr[$column->typeDrizzle()];
            $c->_type = $column->type();
            $c->flags = $this->fieldFlags($result, $k);
            $c->_flags = $column->flags();

            $c->multiple_key = (int) (bool) (
                $c->_flags & DRIZZLE_COLUMN_FLAGS_MULTIPLE_KEY
            );
            $c->primary_key =  (int) (bool) (
                $c->_flags & DRIZZLE_COLUMN_FLAGS_PRI_KEY
            );
            $c->unique_key =   (int) (bool) (
                $c->_flags & DRIZZLE_COLUMN_FLAGS_UNIQUE_KEY
            );
            $c->not_null =     (int) (bool) (
                $c->_flags & DRIZZLE_COLUMN_FLAGS_NOT_NULL
            );
            $c->unsigned =     (int) (bool) (
                $c->_flags & DRIZZLE_COLUMN_FLAGS_UNSIGNED
            );
            $c->zerofill =     (int) (bool) (
                $c->_flags & DRIZZLE_COLUMN_FLAGS_ZEROFILL
            );
            $c->numeric =      (int) (bool) (
                $c->_flags & DRIZZLE_COLUMN_FLAGS_NUM
            );
            $c->blob =         (int) (bool) (
                $c->_flags & DRIZZLE_COLUMN_FLAGS_BLOB
            );

            $std_columns[] = $c;
        }

        return $std_columns;
    }

    /**
     * return number of fields in given $result
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     *
     * @return int field count
     */
    public function numFields($result)
    {
        return $result->numColumns();
    }

    /**
     * returns the length of the given field $i in $result
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     * @param int               $i      field
     *
     * @return int length of field
     */
    public function fieldLen($result, $i)
    {
        $colums = $result->getColumns();
        return $colums[$i]->size();
    }

    /**
     * returns name of $i. field in $result
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     * @param int               $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, $i)
    {
        $colums = $result->getColumns();
        return $colums[$i]->name();
    }

    /**
     * returns concatenated string of human readable field flags
     *
     * @param PMA_DrizzleResult $result Drizzle result object
     * @param int               $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i)
    {
        $columns = $result->getColumns();
        $f = $columns[$i];
        $type = $f->typeDrizzle();
        $charsetnr = $f->charset();
        $f = $f->flags();
        $flags = '';
        if ($f & DRIZZLE_COLUMN_FLAGS_UNIQUE_KEY) {
            $flags .= 'unique ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_NUM) {
            $flags .= 'num ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_PART_KEY) {
            $flags .= 'part_key ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_SET) {
            $flags .= 'set ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_TIMESTAMP) {
            $flags .= 'timestamp ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_AUTO_INCREMENT) {
            $flags .= 'auto_increment ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_ENUM) {
            $flags .= 'enum ';
        }
        // See http://dev.mysql.com/doc/refman/6.0/en/c-api-datatypes.html:
        // to determine if a string is binary, we should not use MYSQLI_BINARY_FLAG
        // but instead the charsetnr member of the MYSQL_FIELD
        // structure. Watch out: some types like DATE returns 63 in charsetnr
        // so we have to check also the type.
        // Unfortunately there is no equivalent in the mysql extension.
        if (($type == DRIZZLE_COLUMN_TYPE_DRIZZLE_BLOB
            || $type == DRIZZLE_COLUMN_TYPE_DRIZZLE_VARCHAR)
            && 63 == $charsetnr
        ) {
            $flags .= 'binary ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_ZEROFILL) {
            $flags .= 'zerofill ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_UNSIGNED) {
            $flags .= 'unsigned ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_BLOB) {
            $flags .= 'blob ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_MULTIPLE_KEY) {
            $flags .= 'multiple_key ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_UNIQUE_KEY) {
            $flags .= 'unique_key ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_PRI_KEY) {
            $flags .= 'primary_key ';
        }
        if ($f & DRIZZLE_COLUMN_FLAGS_NOT_NULL) {
            $flags .= 'not_null ';
        }
        return trim($flags);
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
