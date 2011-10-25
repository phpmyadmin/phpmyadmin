<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * holds the PMA_List_Database class
 *
 * @package PhpMyAdmin
 */

/**
 * the list base class
 */
require_once './libraries/List.class.php';

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
 * @package PhpMyAdmin
 */
/*public*/ class PMA_List_Database extends PMA_List
{
    /**
     * @var mixed   database link resource|object to be used
     */
    protected $_db_link = null;

    /**
     * @var mixed   user database link resource|object
     */
    protected $_db_link_user = null;

    /**
     * @var mixed   controluser database link resource|object
     */
    protected $_db_link_control = null;

    /**
     * @var boolean whether SHOW DATABASES is disabled or not
     * @access protected
     */
    protected $_show_databases_disabled = false;

    /**
     * @var string command to retrieve databases from server
     */
    protected $_command = null;

    /**
     * Constructor
     *
     * @param mixed   $db_link_user       user database link resource|object
     * @param mixed   $db_link_control    control database link resource|object
     */
    public function __construct($db_link_user = null, $db_link_control = null)
    {
        $this->_db_link = $db_link_user;
        $this->_db_link_user = $db_link_user;
        $this->_db_link_control = $db_link_control;

        parent::__construct();
        $this->build();
    }

    /**
     * checks if the configuration wants to hide some databases
     */
    protected function _checkHideDatabase()
    {
        if (empty($GLOBALS['cfg']['Server']['hide_db'])) {
            return;
        }

        foreach ($this->getArrayCopy() as $key => $db) {
            if (preg_match('/' . $GLOBALS['cfg']['Server']['hide_db'] . '/', $db)) {
                $this->offsetUnset($key);
            }
        }
    }

    /**
     * retrieves database list from server
     *
     * @todo    we could also search mysql tables if all fail?
     * @param string  $like_db_name   usally a db_name containing wildcards
     * @return array
     */
    protected function _retrieve($like_db_name = null)
    {
        if ($this->_show_databases_disabled) {
            return array();
        }

        if (null !== $like_db_name) {
            $command = "SHOW DATABASES LIKE '" . $like_db_name . "'";
        } elseif (null === $this->_command) {
            $command = str_replace('#user#', $GLOBALS['cfg']['Server']['user'],
                $GLOBALS['cfg']['Server']['ShowDatabasesCommand']);
            $this->_command = $command;
        } else {
            $command = $this->_command;
        }

        $database_list = PMA_DBI_fetch_result($command, null, null, $this->_db_link);
        PMA_DBI_getError();

        if ($GLOBALS['errno'] !== 0) {
            // failed to get database list, try the control user
            // (hopefully there is one and he has SHOW DATABASES right)
            $this->_db_link = $this->_db_link_control;
            $database_list = PMA_DBI_fetch_result($command, null, null, $this->_db_link);

            PMA_DBI_getError();

            if ($GLOBALS['errno'] !== 0) {
                // failed! we will display a warning that phpMyAdmin could not safely
                // retrieve database list, the admin has to setup a control user or
                // allow SHOW DATABASES
                $GLOBALS['error_showdatabases'] = true;
                $this->_show_databases_disabled = true;
            }
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            natsort($database_list);
        } else {
            // need to sort anyway, otherwise information_schema
            // goes at the top
            sort($database_list);
        }

        return $database_list;
    }

    /**
     * builds up the list
     *
     */
    public function build()
    {
        if (! $this->_checkOnlyDatabase()) {
            $items = $this->_retrieve();
            $this->exchangeArray($items);
        }

        $this->_checkHideDatabase();
    }

    /**
     * checks the only_db configuration
     *
     * @return  boolean false if there is no only_db, otherwise true
     */
    protected function _checkOnlyDatabase()
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

        $items = array();

        foreach ($GLOBALS['cfg']['Server']['only_db'] as $each_only_db) {
            if ($each_only_db === '*' && ! $this->_show_databases_disabled) {
                // append all not already listed dbs to the list
                $items = array_merge($items,
                    array_diff($this->_retrieve(), $items));
                // there can only be one '*', and this can only be last
                break;
            }

            // check if the db name contains wildcard,
            // thus containing not escaped _ or %
            if (! preg_match('/(^|[^\\\\])(_|%)/', $each_only_db)) {
                // ... not contains wildcard
                $items[] = PMA_unescape_mysql_wildcards($each_only_db);
                continue;
            }

            if (! $this->_show_databases_disabled) {
                $items = array_merge($items, $this->_retrieve($each_only_db));
                continue;
            }

            // @todo induce error, about not using wildcards with SHOW DATABASE disabled?
        }

        $this->exchangeArray($items);

        return true;
    }

    /**
     * returns default item
     *
     * @return  string  default item
     */
    public function getDefault()
    {
        if (strlen($GLOBALS['db'])) {
            return $GLOBALS['db'];
        }

        return $this->getEmpty();
    }

    /**
     * returns array with dbs grouped with extended infos
     *
     * @param integer $offset
     * @param integer $count
     * @return  array   db list
     */
    public function getGroupedDetails($offset, $count)
    {
        $dbgroups   = array();

        if ($GLOBALS['cfg']['ShowTooltip']
          && $GLOBALS['cfgRelation']['commwork']) {
            $db_tooltips = PMA_getDbComments();
        }

        if (!$GLOBALS['cfg']['LeftFrameDBTree']) {
            $separators = array();
        } elseif (is_array($GLOBALS['cfg']['LeftFrameDBSeparator'])) {
            $separators = $GLOBALS['cfg']['LeftFrameDBSeparator'];
        } elseif (!empty($GLOBALS['cfg']['LeftFrameDBSeparator'])) {
            $separators = array($GLOBALS['cfg']['LeftFrameDBSeparator']);
        } else {
            $separators = array();
        }

        foreach ($this->getLimitedItems($offset, $count) as $db) {
            // Get comments from PMA comments table
            $db_tooltip = '';

            if (isset($db_tooltips[$db])) {
                $db_tooltip = $db_tooltips[$db];
            }

            $pos = false;

            foreach ($separators as $separator) {
                // use strpos instead of strrpos; it seems more common to
                // have the db name, the separator, then the rest which
                // might contain a separator
                // like dbname_the_rest
                $pos = strpos($db, $separator, 1);

                if ($pos !== false) {
                    break;
                }
            }

            if ($pos !== false) {
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
            );

            if ($GLOBALS['cfg']['Server']['CountTables']) {
                $dbgroups[$group][$db]['num_tables'] = PMA_getTableCount($db);
            }
        } // end foreach ($GLOBALS['PMA_List_Database']->items as $db)
        return $dbgroups;
    }

    /**
     * returns a part of the items
     *
     * @param integer $offset
     * @param integer $count
     * @return  array  some items
     */
    public function getLimitedItems($offset, $count)
    {
        return array_slice($this->getArrayCopy(), $offset, $count);
    }

    /**
     * returns html code for list with dbs
     *
     * @return  string  html code list
     */
    public function getHtmlListGrouped($selected = '', $offset, $count)
    {
        if (true === $selected) {
            $selected = $this->getDefault();
        }

        $return = '<ul id="databaseList" xml:lang="en" dir="ltr">' . "\n";
        foreach ($this->getGroupedDetails($offset, $count) as $group => $dbs) {
            if (count($dbs) > 1) {
                $return .= '<li class="group"><span>' . htmlspecialchars($group) . '</span><ul>' . "\n";
                // whether display db_name cut by the group part
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
                $return .= '><a';
                if (! empty($db['comment'])) {
                    $return .= ' title="' . htmlspecialchars($db['comment']) . '"';
                }
                $return .= ' href="index.php?' . PMA_generate_common_url($db['name'])
                    . '" target="_parent">';
                if ($cut) {
                    $return .= htmlspecialchars($db['disp_name_cut']);
                } else {
                    $return .= htmlspecialchars($db['disp_name']);
                }

                if (! empty($db['num_tables'])) {
                    $return .= ' (' . $db['num_tables'] . ')';
                }
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
    public function getHtmlSelectGrouped($selected = '', $offset, $count)
    {
        if (true === $selected) {
            $selected = $this->getDefault();
        }

        $return = '<select name="db" id="lightm_db" xml:lang="en" dir="ltr"'
            . ' onchange="if (this.value != \'\') window.parent.openDb(this.value);">' . "\n"
            . '<option value="" dir="' . $GLOBALS['text_dir'] . '">'
            . '(' . __('Databases') . ') ...</option>' . "\n";
        foreach ($this->getGroupedDetails($offset, $count) as $group => $dbs) {
            if (count($dbs) > 1) {
                $return .= '<optgroup label="' . htmlspecialchars($group)
                    . '">' . "\n";
                // whether display db_name cuted by the group part
                $cut = true;
            } else {
                // .. or full
                $cut = false;
            }
            foreach ($dbs as $db) {
                $return .= '<option value="' . htmlspecialchars($db['name']) . '"'
                    .' title="' . htmlspecialchars($db['comment']) . '"';
                if ($db['name'] == $selected || (PMA_DRIZZLE && strtolower($db['name']) == strtolower($selected))) {
                    $return .= ' selected="selected"';
                }
                $return .= '>' . htmlspecialchars($cut ? $db['disp_name_cut'] : $db['disp_name']);
                if (! empty($db['num_tables'])) {
                    $return .= ' (' . $db['num_tables'] . ')';
                }
                $return .= '</option>' . "\n";
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
     */
    protected function _checkAgainstPrivTables()
    {
        // 1. get allowed dbs from the "mysql.db" table
        // User can be blank (anonymous user)
        $local_query = "
            SELECT DISTINCT `Db` FROM `mysql`.`db`
            WHERE `Select_priv` = 'Y'
            AND `User`
            IN ('" . PMA_sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . "', '')";
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
            $tmp_alldbs = PMA_DBI_query('SHOW DATABASES;', $GLOBALS['controllink']);
            // all databases cases - part 2
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
                    } elseif (! isset($dblist[$tmp_db])) {
                        foreach ($tmp_mydbs as $tmp_matchpattern => $tmp_value) {
                            // fixed bad regexp
                            // TODO: db names may contain characters
                            //       that are regexp instructions
                            $re        = '(^|(\\\\\\\\)+|[^\])';
                            $tmp_regex = preg_replace('/' . addcslashes($re, '/') . '%/', '\\1.*', preg_replace('/' . addcslashes($re, '/') . '_/', '\\1.{1}', $tmp_matchpattern));
                            // Fixed db name matching
                            // 2000-08-28 -- Benjamin Gandon
                            if (preg_match('/^' . addcslashes($tmp_regex, '/') . '$/', $tmp_db)) {
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
        $local_query = 'SELECT DISTINCT Db FROM mysql.tables_priv WHERE Table_priv LIKE \'%Select%\' AND User = \'' . PMA_sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . '\'';
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
