<?php
/**
 * Contract for every database extension supported by phpMyAdmin
 *
 * @package PhpMyAdmin-DBI
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Contract for every database extension supported by phpMyAdmin
 *
 * @package PhpMyAdmin-DBI
 */
interface PMA_DBI_Extension
{
    /**
     * connects to the database server
     *
     * @param string $user                 user name
     * @param string $password             user password
     * @param bool   $is_controluser       whether this is a control user connection
     * @param array  $server               host/port/socket/persistent
     * @param bool   $auxiliary_connection (when true, don't go back to login if
     *                                     connection fails)
     *
     * @return mixed false on error or a connection object on success
     */
    public function connect(
        $user, $password, $is_controluser = false, $server = null,
        $auxiliary_connection = false
    );

    /**
     * selects given database
     *
     * @param string   $dbname database name to select
     * @param resource $link   connection object
     *
     * @return boolean
     */
    public function selectDb($dbname, $link);

    /**
     * runs a query and returns the result
     *
     * @param string   $query   query to execute
     * @param resource $link    connection object
     * @param int      $options query options
     *
     * @return mixed result
     */
    public function realQuery($query, $link, $options);

    /**
     * Run the multi query and output the results
     *
     * @param resource $link  connection object
     * @param string   $query multi query statement to execute
     *
     * @return array|bool
     */
    public function realMultiQuery($link, $query);

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    public function fetchArray($result);

    /**
     * returns array of rows with associative keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    public function fetchAssoc($result);

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    public function fetchRow($result);

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param object  $result database result
     * @param integer $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, $offset);

    /**
     * Frees memory associated with the result
     *
     * @param object $result database result
     *
     * @return void
     */
    public function freeResult($result);

    /**
     * Check if there are any more query results from a multi query
     *
     * @param resource $link the connection object
     *
     * @return bool true or false
     */
    public function moreResults($link);

    /**
     * Prepare next result from multi_query
     *
     * @param resource $link the connection object
     *
     * @return bool true or false
     */
    public function nextResult($link);

    /**
     * Store the result returned from multi query
     *
     * @param resource $link mysql link
     *
     * @return mixed false when empty results / result set when not empty
     */
    public function storeResult($link);

    /**
     * Returns a string representing the type of connection used
     *
     * @param resource $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link);

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param resource $link mysql link
     *
     * @return integer version of the MySQL protocol used
     */
    public function getProtoInfo($link);

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo();

    /**
     * returns last error message or false if no errors occurred
     *
     * @param resource $link connection link
     *
     * @return string|bool $error or false
     */
    public function getError($link);

    /**
     * returns the number of rows returned by last query
     *
     * @param object $result result set identifier
     *
     * @return string|int
     */
    public function numRows($result);

    /**
     * returns the number of rows affected by last query
     *
     * @param resource $link the connection object
     *
     * @return int
     */
    public function affectedRows($link);

    /**
     * returns metainfo for fields in $result
     *
     * @param object $result result set identifier
     *
     * @return array meta info for fields in $result
     */
    public function getFieldsMeta($result);

    /**
     * return number of fields in given $result
     *
     * @param object $result result set identifier
     *
     * @return int field count
     */
    public function numFields($result);

    /**
     * returns the length of the given field $i in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return int length of field
     */
    public function fieldLen($result, $i);

    /**
     * returns name of $i. field in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, $i);

    /**
     * returns concatenated string of human readable field flags
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i);
}


/**
 * Defines PMA_MYSQL_CLIENT_API if it does not exist based
 * on MySQL client version.
 *
 * @param string $version MySQL version string
 *
 * @return void
 */
function PMA_defineClientAPI($version)
{
    if (! defined('PMA_MYSQL_CLIENT_API')) {
        $client_api = explode('.', $version);
        define(
            'PMA_MYSQL_CLIENT_API',
            (int)sprintf(
                '%d%02d%02d',
                $client_api[0], $client_api[1], intval($client_api[2])
            )
        );
    }
}
