<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface to the Drizzle extension
 *
 * WARNING - EXPERIMENTAL, never use in production,
 * drizzle module segfaults often and when you least expect it to
 *
 * TODO: This file and drizzle-wrappers.lib.php should be devoid
 *       of any segfault related hacks.
 * TODO: Crashing versions of drizzle module and/or libdrizzle
 *       should be blacklisted
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Drizzle
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/dbi/drizzle-wrappers.lib.php';
require_once './libraries/dbi/DBIExtension.int.php';

/**
 * MySQL client API
 */
if (! defined('PMA_MYSQL_CLIENT_API')) {
    define('PMA_MYSQL_CLIENT_API', (int)drizzle_version());
}

/**
 * Names of field flags.
 */
if (! defined('DRIZZLE_COLUMN_FLAGS_NUM')) {
    $pma_drizzle_flag_names = array();
} else {
    $pma_drizzle_flag_names = array(
        DRIZZLE_COLUMN_FLAGS_NUM => 'num',
        DRIZZLE_COLUMN_FLAGS_PART_KEY => 'part_key',
        DRIZZLE_COLUMN_FLAGS_SET => 'set',
        DRIZZLE_COLUMN_FLAGS_TIMESTAMP => 'timestamp',
        DRIZZLE_COLUMN_FLAGS_AUTO_INCREMENT => 'auto_increment',
        DRIZZLE_COLUMN_FLAGS_ENUM => 'enum',
        DRIZZLE_COLUMN_FLAGS_ZEROFILL => 'zerofill',
        DRIZZLE_COLUMN_FLAGS_UNSIGNED => 'unsigned',
        DRIZZLE_COLUMN_FLAGS_BLOB => 'blob',
        DRIZZLE_COLUMN_FLAGS_MULTIPLE_KEY => 'multiple_key',
        DRIZZLE_COLUMN_FLAGS_UNIQUE_KEY => 'unique_key',
        DRIZZLE_COLUMN_FLAGS_PRI_KEY => 'primary_key',
        DRIZZLE_COLUMN_FLAGS_NOT_NULL => 'not_null',
    );
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
        $db = null, $options = 0
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

        $server_port = $GLOBALS['dbi']->getServerPort($server);
        $server_socket = $GLOBALS['dbi']->getServerSocket($server);

        if ($server) {
            $server['host'] = (empty($server['host']))
                ? 'localhost'
                : $server['host'];
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

        if ($server) {
            return @$this->_realConnect(
                $drizzle, $server['host'], $server_port, $server_socket,
                $user, $password
            );
        }

        $link = @$this->_realConnect(
            $drizzle, $cfg['Server']['host'], $server_port, $server_socket, $user,
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

        return $link;
    }

    /**
     * selects given database
     *
     * @param string         $dbname database name to select
     * @param PMA_DrizzleCon $link   connection object
     *
     * @return bool
     */
    public function selectDb($dbname, $link)
    {
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
     * @param resource $link  connection object
     * @param string   $query multi query statement to execute
     *
     * @return array|bool
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
     * @param resource $link the connection object
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
     * @param resource $link the connection object
     *
     * @return bool false
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
     * @param PMA_DrizzleCon $link connection object
     *
     * @return string type of connection used
     */
    public function getHostInfo($link)
    {
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
    public function getProtoInfo($link)
    {
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
    public function getError($link)
    {
        $GLOBALS['errno'] = 0;

        if (null !== $link && false !== $link) {
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
     * returns the number of rows affected by last query
     *
     * @param PMA_DrizzleResult $link connection object
     *
     * @return int
     */
    public function affectedRows($link)
    {
        $affectedRows = $link->affectedRows();
        return $affectedRows !== false ? $affectedRows : 0;
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
        // columns in a standardized format
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
        $flags = array();
        foreach ($GLOBALS['pma_drizzle_flag_names'] as $flag => $name) {
            if ($f & $flag) {
                $flags[] = $name;
            }
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
            $flags[] = 'binary';
        }
        return implode(' ', $flags);
    }

    /**
     * Store the result returned from multi query
     *
     * @param PMA_DrizzleResult $link Drizzle result object
     *
     * @return false
     */
    public function storeResult($link)
    {
        return false;
    }
}
