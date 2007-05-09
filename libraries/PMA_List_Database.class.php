<?php
/**
 * holds the PMA_List_Database class
 *
 */

/**
 * the list base class
 */
require_once './libraries/PMA_List.class.php';

/**
 * handles database lists
 *
 * <code>
 * $PMA_List_Database = new PMA_List_Database($userlink, $controllink);
 * </code>
 *
 * @todo this object should be attached to the PMA_Server object
 * @todo ? make use of INFORMATION_SCHEMA
 * @todo ? support --skip-showdatabases and user has only global rights
 * @access public
 * @since phpMyAdmin 2.9.10
 */
/*public*/ class PMA_List_Database extends PMA_List
{
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
     * @todo temporaly use this docblock to test how to doc $GLOBALS
     * @access  protected
     * @uses    PMA_List_Database::$items
     * @uses    PMA_List_Database::$_need_to_reindex to set it if reuqired
     * @uses    preg_match()
     * @uses    $GLOBALS['cfg']
     * @uses    $GLOBALS['cfg']['Server']
     * @uses    $GLOBALS['cfg']['Server']['hide_db']
     * @global  array $GLOBALS['cfg']
     * @global  array $cfg
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
     * @global  boolean $error_showdatabases to alert not allowed SHOW DATABASE
     * @global  integer $errno from PMA_DBI_getError()
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
     * @global  array   $cfg
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
     * @global  array   $cfg
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
     * returns default item
     *
     * @uses    PMA_List::getEmpty()
     * @uses    strlen()
     * @global  string  $db
     * @return  string  default item
     */
    function getDefault()
    {
        if (strlen($GLOBALS['db'])) {
            return $GLOBALS['db'];
        }

        return $this->getEmpty();
    }

    /**
     * returns array with dbs grouped with extended infos
     *
     * @uses    $GLOBALS['PMA_List_Database']
     * @uses    $GLOBALS['cfgRelation']['commwork']
     * @uses    $GLOBALS['cfg']['ShowTooltip']
     * @uses    $GLOBALS['cfg']['LeftFrameDBTree']
     * @uses    $GLOBALS['cfg']['LeftFrameDBSeparator']
     * @uses    $GLOBALS['cfg']['ShowTooltipAliasDB']
     * @uses    PMA_getTableCount()
     * @uses    PMA_getComments()
     * @uses    is_array()
     * @uses    implode()
     * @uses    strstr()
     * @uses    explode()
     * @return  array   db list
     */
    function getGroupedDetails()
    {
        $dbgroups   = array();
        $parts      = array();
        foreach ($this->items as $key => $db) {
            // garvin: Get comments from PMA comments table
            $db_tooltip = '';
            if ($GLOBALS['cfg']['ShowTooltip']
              && $GLOBALS['cfgRelation']['commwork']) {
                $_db_tooltip = PMA_getComments($db);
                if (is_array($_db_tooltip)) {
                    $db_tooltip = implode(' ', $_db_tooltip);
                }
            }

            if ($GLOBALS['cfg']['LeftFrameDBTree']
                && $GLOBALS['cfg']['LeftFrameDBSeparator']
                && strstr($db, $GLOBALS['cfg']['LeftFrameDBSeparator']))
            {
                // use strpos instead of strrpos; it seems more common to
                // have the db name, the separator, then the rest which
                // might contain a separator
                // like dbname_the_rest
                $pos            = strpos($db, $GLOBALS['cfg']['LeftFrameDBSeparator']);
                $group          = substr($db, 0, $pos);
                $disp_name_cut  = substr($db, $pos);
            } else {
                $group          = $db;
                $disp_name_cut  = $db;
            }

            $disp_name  = $db;
            if ($db_tooltip && $GLOBALS['cfg']['ShowTooltipAliasDB']) {
                $disp_name      = $db_tooltip;
                $disp_name_cut  = $db_tooltip;
                $db_tooltip     = $db;
            }

            $dbgroups[$group][$db] = array(
                'name'          => $db,
                'disp_name_cut' => $disp_name_cut,
                'disp_name'     => $disp_name,
                'comment'       => $db_tooltip,
                'num_tables'    => PMA_getTableCount($db),
            );
        } // end foreach ($GLOBALS['PMA_List_Database']->items as $db)
        return $dbgroups;
    }

    /**
     * returns html code for list with dbs
     *
     * @return  string  html code list
     */
    function getHtmlListGrouped($selected = '')
    {
        if (true === $selected) {
            $selected = $this->getDefault();
        }

	$return = '<ul id="databaseList" xml:lang="en" dir="ltr">' . "\n";
        foreach ($this->getGroupedDetails() as $group => $dbs) {
            if (count($dbs) > 1) {
		$return .= '<li>' . $group . '<ul>' . "\n";
                // wether display db_name cuted by the group part
                $cut = true;
            } else {
                // .. or full
                $cut = false;
            }
            foreach ($dbs as $db) {
	    	$return .= '<li';
		if ($db['name'] == $selected) {
		    $return .= ' class="selected"';
		}
                $return .= '><a' . (! empty($db['comment']) ? ' title="' . $db['comment'] . '"' : '') . ' href="index.php?' . PMA_generate_common_url($db['name']) . '" target="_parent">';
                $return .= ($cut ? $db['disp_name_cut'] : $db['disp_name'])
			.' (' . $db['num_tables'] . ')';
		$return .= '</a></li>' . "\n";
            }
            if (count($dbs) > 1) {
                $return .= '</ul></li>' . "\n";
            }
        }
        $return .= '</ul>';

        return $return;
    }

    /**
     * returns html code for select form element with dbs
     *
     * @todo IE can not handle different text directions in select boxes so,
     * as mostly names will be in english, we set the whole selectbox to LTR
     * and EN
     *
     * @return  string  html code select
     */
    function getHtmlSelectGrouped($selected = '')
    {
        if (true === $selected) {
            $selected = $this->getDefault();
        }

        $return = '<select name="db" id="lightm_db" xml:lang="en" dir="ltr"'
            . ' onchange="if (this.value != \'\') window.parent.openDb(this.value);">' . "\n"
            . '<option value="" dir="' . $GLOBALS['text_dir'] . '">'
            . '(' . $GLOBALS['strDatabases'] . ') ...</option>' . "\n";
        foreach ($this->getGroupedDetails() as $group => $dbs) {
            if (count($dbs) > 1) {
                $return .= '<optgroup label="' . htmlspecialchars($group)
                    . '">' . "\n";
                // wether display db_name cuted by the group part
                $cut = true;
            } else {
                // .. or full
                $cut = false;
            }
            foreach ($dbs as $db) {
                $return .= '<option value="' . htmlspecialchars($db['name']) . '"'
                    .' title="' . htmlspecialchars($db['comment']) . '"';
                if ($db['name'] == $selected) {
                    $return .= ' selected="selected"';
                }
                $return .= '>' . htmlspecialchars($cut ? $db['disp_name_cut'] : $db['disp_name'])
                    .' (' . $db['num_tables'] . ')</option>' . "\n";
            }
            if (count($dbs) > 1) {
                $return .= '</optgroup>' . "\n";
            }
        }
        $return .= '</select>';

        return $return;
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
        $tmp_mydbs = PMA_DBI_fetch_result($local_query, null, null,
            $GLOBALS['controllink']);
        if ($tmp_mydbs) {
            // Will use as associative array of the following 2 code
            // lines:
            //   the 1st is the only line intact from before
            //     correction,
            //   the 2nd replaces $dblist[] = $row['Db'];

            // Code following those 2 lines in correction continues
            // populating $dblist[], as previous code did. But it is
            // now populated with actual database names instead of
            // with regular expressions.
            var_dump($tmp_mydbs);
            $tmp_alldbs = PMA_DBI_query('SHOW DATABASES;', $GLOBALS['controllink']);
            // loic1: all databases cases - part 2
            if (isset($tmp_mydbs['%'])) {
                while ($tmp_row = PMA_DBI_fetch_row($tmp_alldbs)) {
                    $dblist[] = $tmp_row[0];
                } // end while
            } else {
                while ($tmp_row = PMA_DBI_fetch_row($tmp_alldbs)) {
                    $tmp_db = $tmp_row[0];
                    if (isset($tmp_mydbs[$tmp_db]) && $tmp_mydbs[$tmp_db] == 1) {
                        $dblist[]           = $tmp_db;
                        $tmp_mydbs[$tmp_db] = 0;
                    } elseif (!isset($dblist[$tmp_db])) {
                        foreach ($tmp_mydbs as $tmp_matchpattern => $tmp_value) {
                            // loic1: fixed bad regexp
                            // TODO: db names may contain characters
                            //       that are regexp instructions
                            $re        = '(^|(\\\\\\\\)+|[^\])';
                            $tmp_regex = ereg_replace($re . '%', '\\1.*', ereg_replace($re . '_', '\\1.{1}', $tmp_matchpattern));
                            // Fixed db name matching
                            // 2000-08-28 -- Benjamin Gandon
                            if (ereg('^' . $tmp_regex . '$', $tmp_db)) {
                                $dblist[] = $tmp_db;
                                break;
                            }
                        } // end while
                    } // end if ... elseif ...
                } // end while
            } // end else
            PMA_DBI_free_result($tmp_alldbs);
            unset($tmp_mydbs);
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
