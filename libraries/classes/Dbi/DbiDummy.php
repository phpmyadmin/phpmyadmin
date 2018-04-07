<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Fake database driver for testing purposes
 *
 * It has hardcoded results for given queries what makes easy to use it
 * in testsuite. Feel free to include other queries which your test will
 * need.
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Dummy
 */
namespace PhpMyAdmin\Dbi;

require_once 'libraries/dbi/dbi_dummy.inc.php';

/**
 * Fake database driver for testing purposes
 *
 * It has hardcoded results for given queries what makes easy to use it
 * in testsuite. Feel free to include other queries which your test will
 * need.
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Dummy
 */
class DbiDummy implements DbiExtension
{
    private $_queries = array();
    const OFFSET_GLOBAL = 1000;

    /**
     * connects to the database server
     *
     * @param string $user     mysql user name
     * @param string $password mysql user password
     * @param array  $server   host/port/socket/persistent
     *
     * @return mixed false on error or a mysqli object on success
     */
    public function connect(
        $user,
        $password,
        array $server = []
    ) {
        return true;
    }

    /**
     * selects given database
     *
     * @param string   $dbname name of db to select
     * @param resource $link   mysql link resource
     *
     * @return bool
     */
    public function selectDb($dbname, $link)
    {
        $GLOBALS['dummy_db'] = $dbname;

        return true;
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
    public function realQuery($query, $link = null, $options = 0)
    {
        $query = trim(preg_replace('/  */', ' ', str_replace("\n", ' ', $query)));
        for ($i = 0, $nb = count($this->_queries); $i < $nb; $i++) {
            if ($this->_queries[$i]['query'] != $query) {
                continue;
            }

            $this->_queries[$i]['pos'] = 0;
            if (!is_array($this->_queries[$i]['result'])) {
                return false;
            }

            return $i;
        }
        for ($i = 0, $nb = count($GLOBALS['dummy_queries']); $i < $nb; $i++) {
            if ($GLOBALS['dummy_queries'][$i]['query'] != $query) {
                continue;
            }

            $GLOBALS['dummy_queries'][$i]['pos'] = 0;
            if (!is_array($GLOBALS['dummy_queries'][$i]['result'])) {
                return false;
            }

            return $i + self::OFFSET_GLOBAL;
        }
        echo "Not supported query: $query\n";

        return false;
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
     * returns result data from $result
     *
     * @param object $result MySQL result
     *
     * @return array
     */
    public function fetchAny($result)
    {
        $query_data = &$this->getQueryData($result);
        if ($query_data['pos'] >= count($query_data['result'])) {
            return false;
        }
        $ret = $query_data['result'][$query_data['pos']];
        $query_data['pos'] += 1;

        return $ret;
    }

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param object $result result  MySQL result
     *
     * @return array
     */
    public function fetchArray($result)
    {
        $query_data = &$this->getQueryData($result);
        $data = $this->fetchAny($result);
        if (!is_array($data)
            || !isset($query_data['columns'])
        ) {
            return $data;
        }

        foreach ($data as $key => $val) {
            $data[$query_data['columns'][$key]] = $val;
        }

        return $data;
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param object $result MySQL result
     *
     * @return array
     */
    public function fetchAssoc($result)
    {
        $data = $this->fetchAny($result);
        $query_data = &$this->getQueryData($result);
        if (!is_array($data) || !isset($query_data['columns'])) {
            return $data;
        }

        $ret = array();
        foreach ($data as $key => $val) {
            $ret[$query_data['columns'][$key]] = $val;
        }

        return $ret;
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param object $result MySQL result
     *
     * @return array
     */
    public function fetchRow($result)
    {
        $data = $this->fetchAny($result);

        return $data;
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param object  $result database result
     * @param integer $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, $offset)
    {
        $query_data = &$this->getQueryData($result);
        if ($offset > count($query_data['result'])) {
            return false;
        }
        $query_data['pos'] = $offset;

        return true;
    }

    /**
     * Frees memory associated with the result
     *
     * @param object $result database result
     *
     * @return void
     */
    public function freeResult($result)
    {
        return;
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
        return false;
    }

    /**
     * Prepare next result from multi_query
     *
     * @param resource $link the connection object
     *
     * @return boolean false
     */
    public function nextResult($link)
    {
        return false;
    }

    /**
     * Store the result returned from multi query
     *
     * @param resource $link the connection object
     *
     * @return mixed false when empty results / result set when not empty
     */
    public function storeResult($link)
    {
        return false;
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
        return '';
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
        return -1;
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo()
    {
        return '';
    }

    /**
     * returns last error message or false if no errors occurred
     *
     * @param resource $link connection link
     *
     * @return string|bool $error or false
     */
    public function getError($link)
    {
        return false;
    }

    /**
     * returns the number of rows returned by last query
     *
     * @param object $result MySQL result
     *
     * @return string|int
     */
    public function numRows($result)
    {
        if (is_bool($result)) {
            return 0;
        }

        $query_data = &$this->getQueryData($result);

        return count($query_data['result']);
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
        return 0;
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param object $result result set identifier
     *
     * @return array meta info for fields in $result
     */
    public function getFieldsMeta($result)
    {
        return array();
    }

    /**
     * return number of fields in given $result
     *
     * @param object $result MySQL result
     *
     * @return int  field count
     */
    public function numFields($result)
    {
        $query_data = &$this->getQueryData($result);
        if (!isset($query_data['columns'])) {
            return 0;
        }

        return count($query_data['columns']);
    }

    /**
     * returns the length of the given field $i in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return int length of field
     */
    public function fieldLen($result, $i)
    {
        return -1;
    }

    /**
     * returns name of $i. field in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, $i)
    {
        return '';
    }

    /**
     * returns concatenated string of human readable field flags
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i)
    {
        return '';
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
        return $str;
    }

    /**
     * Adds query result for testing
     *
     * @param string $query  SQL
     * @param array  $result Expected result
     *
     * @return void
     */
    public function setResult($query, $result)
    {
        $this->_queries[] = array(
            'query' => $query,
            'result' => $result,
        );
    }

    /**
     * Return query data for ID
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    private function &getQueryData($result)
    {
        if ($result >= self::OFFSET_GLOBAL) {
            return $GLOBALS['dummy_queries'][$result - self::OFFSET_GLOBAL];
        } else {
            return $this->_queries[$result];
        }
    }
}
