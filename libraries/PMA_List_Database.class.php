<?php
/**
 * holds the PMA_List_Database class
 *
 * possible at a later stage we could abstratc this a little bit more:
 *
 * PMA
 *  -> PMA_Config
 *  -> PMA_Theme
 *  -> ...
 *  -> PMA_List
 *    -> PMA_List_Table
 *    -> PMA_List_Server
 *    -> PMA_List_Database
 *      -> PMA_List_Database_Mysql
 *        -> PMA_List_Database_Mysql_3
 *        -> PMA_List_Database_Mysql_4
 *        -> PMA_List_Database_Mysql_5
 *      -> PMA_List_Database_OtherDbms
 *      -> ...
 *
 */
//require_once 'PMA_List.class.php';

/**
 * handles database lists
 *
 * @todo this object should be attached to the server object
 * @todo make use of INFORMATION_SCHEMA !?
 * @todo support --skip-showdatabases and user has only global rights?
 * @todo add caching
 * @access public
 */
/*public*/ class PMA_List_Database /* extends PMA_List */ {

    /**
     * @var array   the list items
     * @access public
     * @todo move into PMA_List
     */
    var $items = array();

    /**
     * @var mixed   database link resource|object to be used
     * @access protected
     */
    var $_db_link = null;

    /**
     * @var mixed   user database link resource|object
     * @access protected
     */
    var $_db_link_user = null;

    /**
     * @var mixed   controluser database link resource|object
     * @access protected
     */
    var $_db_link_control = null;

    /**
     * @var bool    whether we need to re-index the database list for consistency keys
     * @access protected
     */
    var $_need_to_reindex = false;

    /**
     * @var boolean whether SHOW DATABASES is disabled or not
     * @access protected
     */
    var $_show_databases_disabled = false;

    /**
     * Constructor
     *
     * @uses    PMA_List_Database::$_db_link
     * @uses    PMA_List_Database::$_db_link_user
     * @uses    PMA_List_Database::$_db_link_control
     * @uses    PMA_List_Database::build()
     * @param   mixed   $db_link_user       user database link resource|object
     * @param   mixed   $db_link_control    control database link resource|object
     */
    function __construct($db_link_user = null, $db_link_control = null) {
        $this->_db_link = $db_link_user;
        $this->_db_link_user = $db_link_user;
        $this->_db_link_control = $db_link_control;

        $this->build();
    }

    /**
     * old PHP 4 style constructor
     *
     * @see PMA_List_Database::__construct()
     */
    function PMA_List_Database($db_link_user = null, $db_link_control = null) {
        $this->__construct($db_link_user, $db_link_control);
    }

    /**
     * removes all databases not accessible by current user from list
     *
     * @access  protected
     * @uses    PMA_List_Database::$items
     * @uses    PMA_List_Database::$_db_link_user
     * @uses    PMA_List_Database::$_need_to_reindex to set it if reuqired
     * @uses    PMA_DBI_select_db()
     */
    function _checkAccess()
    {
        foreach ($this->items as $key => $db) {
            if (! @PMA_DBI_select_db($db, $this->_db_link_user)) {
                unset($this->items[$key]);
            }
        }

        // re-index values
        $this->_need_to_reindex = true;
    }

    /**
     * checks if the configuration wants to hide some databases
     *
     * @access  protected
     * @uses    PMA_List_Database::$items
     * @uses    PMA_List_Database::$_need_to_reindex to set it if reuqired
     * @uses    preg_match()
     * @global  $cfg
     */
    function _checkHideDatabase()
    {
        if (empty($GLOBALS['cfg']['Server']['hide_db'])) {
            return;
        }

        foreach ($this->items as $key => $db) {
            if (preg_match('/' . $GLOBALS['cfg']['Server']['hide_db'] . '/', $db)) {
                unset($this->items[$key]);
            }
        }
        // re-index values
        $this->_need_to_reindex = true;
    }

    /**
     * retrieves database list from server
     *
     * @todo    we could also search mysql tables if all fail?
     * @access  protected
     * @uses    PMA_List_Database::$_show_databases_disabled for not retrying if SHOW DATABASES is disabled
     * @uses    PMA_List_Database::$_db_link
     * @uses    PMA_List_Database::$_db_link_control in case of SHOW DATABASES is disabled for userlink
     * @uses    PMA_DBI_fetch_result()
     * @uses    PMA_DBI_getError()
     * @global  $error_showdatabases to alert not allowed SHOW DATABASE
     * @global  $errno from PMA_DBI_getError()
     * @param   string  $like_db_name   usally a db_name containing wildcards
     */
    function _retrieve($like_db_name = '')
    {
        if ($this->_show_databases_disabled) {
            return array();
        }

        if (! empty($like_db_name)) {
            $like = " LIKE '" . $like_db_name . "';";
        } else {
            $like = ";";
        }

        $database_list = PMA_DBI_fetch_result('SHOW DATABASES' . $like, null, null, $this->_db_link);
        PMA_DBI_getError();

        if ($GLOBALS['errno'] !== 0) {
            // failed to get database list, try the control user
            // (hopefully there is one and he has SHOW DATABASES right)
            $this->_db_link = $this->_db_link_control;
            $database_list = PMA_DBI_fetch_result('SHOW DATABASES' . $like, null, null, $this->_db_link);

            PMA_DBI_getError();

            if ($GLOBALS['errno'] !== 0) {
                // failed! we will display a warning that phpMyAdmin could not safely
                // retrieve database list, the admin has to setup a control user or
                // allow SHOW DATABASES
                $GLOBALS['error_showdatabases'] = true;
                $this->_show_databases_disabled = true;
            }
        }

        return $database_list;
    }

    /**
     * builds up the list
     *
     * @uses    PMA_List_Database::$items to initialize it
     * @uses    PMA_List_Database::$_need_to_reindex
     * @uses    PMA_List_Database::_checkOnlyDatabase()
     * @uses    PMA_List_Database::_retrieve()
     * @uses    PMA_List_Database::_checkHideDatabase()
     * @uses    PMA_List_Database::_checkAccess()
     * @uses    PMA_MYSQL_INT_VERSION
     * @uses    array_values()
     * @uses    natsort()
     * @global  $cfg
     */
    function build()
    {
        $this->items = array();

        if (! $this->_checkOnlyDatabase()) {
            $this->items = $this->_retrieve();

            if ($GLOBALS['cfg']['NaturalOrder']) {
                natsort($this->items);
                $this->_need_to_reindex = true;
            }
        }

        $this->_checkHideDatabase();

        // Before MySQL 4.0.2, SHOW DATABASES could send the
        // whole list, so check if we really have access:
        if (PMA_MYSQL_INT_VERSION < 40002) {
            $this->_checkAccess();
        }

        if ($this->_need_to_reindex) {
            $this->items = array_values($this->items);
        }
    }

    /**
     * checks the only_db configuration
     *
     * @uses    PMA_List_Database::$_show_databases_disabled
     * @uses    PMA_List_Database::$items
     * @uses    PMA_List_Database::_retrieve()
     * @uses    PMA_unescape_mysql_wildcards()
     * @uses    preg_match()
     * @uses    array_diff()
     * @uses    array_merge()
     * @uses    is_array()
     * @uses    strlen()
     * @uses    is_string()
     * @global  $cfg
     * @return  boolean false if there is no only_db, otherwise true
     */
    function _checkOnlyDatabase()
    {
        if (is_string($GLOBALS['cfg']['Server']['only_db'])
         && strlen($GLOBALS['cfg']['Server']['only_db'])) {
            $GLOBALS['cfg']['Server']['only_db'] = array(
                $GLOBALS['cfg']['Server']['only_db']
            );
        }

        if (! is_array($GLOBALS['cfg']['Server']['only_db'])) {
            return false;
        }

        foreach ($GLOBALS['cfg']['Server']['only_db'] as $each_only_db) {
            if ($each_only_db === '*' && ! $this->_show_databases_disabled) {
                // append all not already listed dbs to the list
                $this->items = array_merge($this->items,
                    array_diff($this->_retrieve(), $this->items));
                // there can only be one '*', and this can only be last
                break;
            }

            // check if the db name contains wildcard,
            // thus containing not escaped _ or %
            if (! preg_match('/(^|[^\\\\])(_|%)/', $each_only_db)) {
                // ... not contains wildcard
                $this->items[] = PMA_unescape_mysql_wildcards($each_only_db);
                continue;
            }

            if (! $this->_show_databases_disabled) {
                $this->items = array_merge($this->items, $this->_retrieve($each_only_db));
                continue;
            }

            // @todo induce error, about not using wildcards with SHOW DATABASE disabled?
        }

        return true;
    }

    /**
     * returns first item from list
     *
     * @todo move into PMA_List
     * @uses    PMA_List_Database::$items
     * @uses    reset()
     * @return  string  value of first item
     */
    function getFirst()
    {
        return reset($this->items);
    }

    /**
     * returns item only if there is only one in the list
     *
     * @todo move into PMA_List
     * @uses    PMA_List_Database::count()
     * @uses    PMA_List_Database::getFirst()
     * @uses    PMA_List_Database::emptyItem()
     * @return  single item
     */
    function getSingleItem()
    {
        if ($this->count() === 1) {
            return $this->getFirst();
        }

        return $this->emptyItem();
    }

    /**
     * returns list item count
     *
     * @todo move into PMA_List
     * @uses    PMA_List_Database::$items
     * @uses    count()
     */
    function count()
    {
        return count($this->items);
    }

    /**
     * defines what is an empty item (0, '', false or null)
     *
     * @todo add as abstract into PMA_List
     */
    function emptyItem()
    {
        return '';
    }

    /**
     * checks if the given db names exists in the current list, if there is
     * missing at least one item it reutrns false other wise true
     *
     * @uses    PMA_List_Database::$items
     * @uses    func_get_args()
     * @uses    in_array()
     * @param   string  $db_name,..     one or more mysql result resources
     * @return  boolean true if all items exists, otheriwse false
     */
    function exists()
    {
        foreach (func_get_args() as $result) {
            if (! in_array($result, $this->items)) {
                return false;
            }
        }

        return true;
    }

    /**
     * returns HTML <option>-tags to be used inside <select></select>
     *
     * @uses    PMA_List_Database::$items
     * @uses    htmlspecialchars()
     * @uses    strlen()
     * @global  $db
     * @param   mixed   $selected   the selected db or true for selecting current db
     * @return  string  HTML option tags
     */
    function getHtmlOptions($selected = '')
    {
        if (true === $selected && strlen($GLOBALS['db'])) {
            $selected = $GLOBALS['db'];
        }
        $options = '';
        foreach ($this->items as $each_db) {
            $options .= '<option value="' . htmlspecialchars($each_db) . '"';
            if ($selected === $each_db) {
                $options .= ' selected="selected"';
            }
            $options .= '>' . htmlspecialchars($each_db) . '</option>' . "\n";
        }

        return $options;
    }

    /**
     * this is just a backup, if all is fine this can be deleted later
     *
     * @deprecated
     * @access protected
     */
    function _checkAgainstPrivTables()
    {
        // 1. get allowed dbs from the "mysql.db" table
        // lem9: User can be blank (anonymous user)
        $local_query = "
            SELECT DISTINCT `Db` FROM `mysql`.`db`
            WHERE `Select_priv` = 'Y'
            AND `User`
            IN ('" . PMA_sqlAddslashes($GLOBALS['cfg']['Server']['user']) . "', '')";
        $uva_mydbs = PMA_DBI_fetch_result($local_query, null, null,
            $GLOBALS['controllink']);
        if ($uva_mydbs) {
            // Will use as associative array of the following 2 code
            // lines:
            //   the 1st is the only line intact from before
            //     correction,
            //   the 2nd replaces $dblist[] = $row['Db'];

            // Code following those 2 lines in correction continues
            // populating $dblist[], as previous code did. But it is
            // now populated with actual database names instead of
            // with regular expressions.
            var_dump($uva_mydbs);
            $uva_alldbs = PMA_DBI_query('SHOW DATABASES;', $GLOBALS['controllink']);
            // loic1: all databases cases - part 2
            if (isset($uva_mydbs['%'])) {
                while ($uva_row = PMA_DBI_fetch_row($uva_alldbs)) {
                    $dblist[] = $uva_row[0];
                } // end while
            } else {
                while ($uva_row = PMA_DBI_fetch_row($uva_alldbs)) {
                    $uva_db = $uva_row[0];
                    if (isset($uva_mydbs[$uva_db]) && $uva_mydbs[$uva_db] == 1) {
                        $dblist[]           = $uva_db;
                        $uva_mydbs[$uva_db] = 0;
                    } elseif (!isset($dblist[$uva_db])) {
                        foreach ($uva_mydbs as $uva_matchpattern => $uva_value) {
                            // loic1: fixed bad regexp
                            // TODO: db names may contain characters
                            //       that are regexp instructions
                            $re        = '(^|(\\\\\\\\)+|[^\])';
                            $uva_regex = ereg_replace($re . '%', '\\1.*', ereg_replace($re . '_', '\\1.{1}', $uva_matchpattern));
                            // Fixed db name matching
                            // 2000-08-28 -- Benjamin Gandon
                            if (ereg('^' . $uva_regex . '$', $uva_db)) {
                                $dblist[] = $uva_db;
                                break;
                            }
                        } // end while
                    } // end if ... elseif ...
                } // end while
            } // end else
            PMA_DBI_free_result($uva_alldbs);
            unset($uva_mydbs);
        } // end if

        // 2. get allowed dbs from the "mysql.tables_priv" table
        $local_query = 'SELECT DISTINCT Db FROM mysql.tables_priv WHERE Table_priv LIKE \'%Select%\' AND User = \'' . PMA_sqlAddslashes($GLOBALS['cfg']['Server']['user']) . '\'';
        $rs          = PMA_DBI_try_query($local_query, $GLOBALS['controllink']);
        if ($rs && @PMA_DBI_num_rows($rs)) {
            while ($row = PMA_DBI_fetch_assoc($rs)) {
                if (!in_array($row['Db'], $dblist)) {
                    $dblist[] = $row['Db'];
                }
            } // end while
            PMA_DBI_free_result($rs);
        } // end if
    }
}
?>