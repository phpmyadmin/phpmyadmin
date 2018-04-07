<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold PhpMyAdmin\Tests\RelationCleanupDbiMock class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;

/**
 * RelationCleanupDbiMock for Mock DBI class
 *
 * this class is for Mock DBI
 *
 * @package PhpMyAdmin-test
 */
class RelationCleanupDbiMock extends DatabaseInterface
{
    var $index;
    var $assocIndex;
    var $totalNum;
    var $values = array();
    var $indexs = array();

    /**
     * Constructor
     *
     */
    public function __construct()
    {
         $this->index = 0;
         $this->assocIndex = 0;
         $this->totalNum = 2;
         $this->values = array(
             'bookmark',
             'relation',
             'table_info',
             'table_coords',
             'column_info',
             'pdf_pages',
             'history',
             'recent',
             'table_uiprefs',
             'tracking',
             'userconfig',
             'users',
             'usergroups',
             'navigationhiding',
         );
         $this->indexs = array(
             'bookmark' => 0,
             'relation' => 1,
             'table_info' => 2,
             'table_coords' => 3,
             'column_info' => 4,
             'pdf_pages' => 5,
             'history' => 6,
             'recent' => 7,
             'table_uiprefs' => 8,
             'tracking' => 9,
             'userconfig' => 10,
             'users' => 11,
             'usergroups' => 12,
             'navigationhiding' => 13,
         );
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    function fetchRow($result)
    {
        $curr_table = array();
        if ($this->index < count($this->values)) {
            $curr_table[0] = $this->values[$this->index];
            $this->index++;
            return $curr_table;
        }

        $this->index = 0;
        return false;
    }


    /**
     * runs a query
     *
     * @param string $sql                 SQL query to execute
     * @param mixed  $link                optional database link to use
     * @param int    $options             optional query options
     * @param bool   $cache_affected_rows whether to cache affected rows
     *
     * @return mixed
     */
    function query($sql, $link = null, $options = 0, $cache_affected_rows = true)
    {
        if (mb_stripos($sql, "column_info") !== false) {
            unset($this->values[$this->indexs['column_info']]);
        }

        if (mb_stripos($sql, "table_info") !== false) {
            unset($this->values[$this->indexs['table_info']]);
        }

        if (mb_stripos($sql, "table_coords") !== false) {
            unset($this->values[$this->indexs['table_coords']]);
        }

        if (mb_stripos($sql, "relation") !== false) {
            unset($this->values[$this->indexs['relation']]);
        }

        if (mb_stripos($sql, "pdf_pages") !== false) {
            unset($GLOBALS [$this->indexs['pdf_pages']]);
        }

        if (mb_stripos($sql, "bookmark") !== false) {
            unset($GLOBALS [$this->indexs['bookmark']]);
        }
        return true;
    }

    /**
     * runs a query and returns the result
     *
     * @param string   $query               query to run
     * @param resource $link                mysql link resource
     * @param integer  $options             query options
     * @param bool     $cache_affected_rows whether to cache affected row
     *
     * @return mixed
     */
    public function tryQuery(
        $query, $link = null, $options = 0, $cache_affected_rows = true
    ) {
        return true;
    }

    /**
     * selects given database
     *
     * @param string $dbname database name to select
     * @param object $link   connection object
     *
     * @return boolean
     */
    public function selectDb($dbname, $link = null)
    {
        return true;
    }

    /**
     * Frees memory associated with the result
     *
     * @param object $result database result
     *
     * @return bool
     */
    public function freeResult($result)
    {
        return true;
    }

    /**
     * returns only the first row from the result
     *
     * <code>
     * $sql = 'SELECT * FROM `user` WHERE `id` = 123';
     * $user = $GLOBALS['dbi']->fetchSingleRow($sql);
     * // produces
     * // $user = array('id' => 123, 'name' => 'John Doe')
     * </code>
     *
     * @param string|mysql_result $result query or mysql result
     * @param string              $type   NUM|ASSOC|BOTH
     *                                    returned array should either numeric
     *                                    associative or booth
     * @param resource            $link   mysql link
     *
     * @return array|boolean first row from result
     *                       or false if result is empty
     */
    public function fetchSingleRow($result, $type = 'ASSOC', $link = null)
    {
        return array(
            'display_field' => "PMA_display_field"
        );
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    public function fetchAssoc($result)
    {
        $assocResult = array();
        if ($this->assocIndex < $this->totalNum) {
            $assocResult['db_name'] = "db_name" . $this->assocIndex;
            $assocResult['comment'] = "comment" . $this->assocIndex;
            $this->assocIndex++;
            return $assocResult;
        }

        $this->assocIndex = 0;
        return false;
    }

    /**
     * returns the number of rows returned by last query
     *
     * @param object $result result set identifier
     *
     * @return string|int
     */
    public function numRows($result)
    {
        return $this->totalNum;
    }
}
