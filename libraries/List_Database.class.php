<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * holds the PMA_List_Database class
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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
 *
 * @package PhpMyAdmin
 * @since   phpMyAdmin 2.9.10
 */
class PMA_List_Database extends PMA_List
{
    /**
     * @var mixed   database link resource|object to be used
     * @access protected
     */
    protected $db_link = null;

    /**
     * @var mixed   user database link resource|object
     * @access protected
     */
    protected $db_link_user = null;

    /**
     * @var mixed   controluser database link resource|object
     * @access protected
     */
    protected $db_link_control = null;

    /**
     * @var boolean whether SHOW DATABASES is disabled or not
     * @access protected
     */
    protected $show_databases_disabled = false;

    /**
     * @var string command to retrieve databases from server
     * @access protected
     */
    protected $command = null;

    /**
     * Constructor
     *
     * @param mixed $db_link_user    user database link resource|object
     * @param mixed $db_link_control control database link resource|object
     *
     * @return void
     */
    public function __construct($db_link_user = null, $db_link_control = null)
    {
        $this->db_link = $db_link_user;
        $this->db_link_user = $db_link_user;
        $this->db_link_control = $db_link_control;

        parent::__construct();
        $this->build();
    }

    /**
     * checks if the configuration wants to hide some databases
     *
     * @return void
     */
    protected function checkHideDatabase()
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
     * @param string $like_db_name usally a db_name containing wildcards
     *
     * @return array
     * @todo   we could also search mysql tables if all fail?
     */
    protected function retrieve($like_db_name = null)
    {
        if ($this->show_databases_disabled) {
            return array();
        }

        if (null !== $like_db_name) {
            $command = "SHOW DATABASES LIKE '" . $like_db_name . "'";
        } elseif (null === $this->command) {
            $command = str_replace(
                '#user#', $GLOBALS['cfg']['Server']['user'],
                $GLOBALS['cfg']['Server']['ShowDatabasesCommand']
            );
            $this->command = $command;
        } else {
            $command = $this->command;
        }

        $database_list = PMA_DBI_fetch_result($command, null, null, $this->db_link);
        PMA_DBI_getError();

        if ($GLOBALS['errno'] !== 0) {
            // failed to get database list, try the control user
            // (hopefully there is one and he has SHOW DATABASES right)
            $this->db_link = $this->db_link_control;
            $database_list = PMA_DBI_fetch_result(
                $command, null, null, $this->db_link
            );

            PMA_DBI_getError();

            if ($GLOBALS['errno'] !== 0) {
                // failed! we will display a warning that phpMyAdmin could not safely
                // retrieve database list, the admin has to setup a control user or
                // allow SHOW DATABASES
                $GLOBALS['error_showdatabases'] = true;
                $this->show_databases_disabled = true;
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
     * @return void
     */
    public function build()
    {
        if (! $this->checkOnlyDatabase()) {
            $items = $this->retrieve();
            $this->exchangeArray($items);
        }

        $this->checkHideDatabase();
    }

    /**
     * checks the only_db configuration
     *
     * @return boolean false if there is no only_db, otherwise true
     */
    protected function checkOnlyDatabase()
    {
        if (is_string($GLOBALS['cfg']['Server']['only_db'])
            && strlen($GLOBALS['cfg']['Server']['only_db'])
        ) {
            $GLOBALS['cfg']['Server']['only_db'] = array(
                $GLOBALS['cfg']['Server']['only_db']
            );
        }

        if (! is_array($GLOBALS['cfg']['Server']['only_db'])) {
            return false;
        }

        $items = array();

        foreach ($GLOBALS['cfg']['Server']['only_db'] as $each_only_db) {

            // check if the db name contains wildcard,
            // thus containing not escaped _ or %
            if (! preg_match('/(^|[^\\\\])(_|%)/', $each_only_db)) {
                // ... not contains wildcard
                $items[] = PMA_Util::unescapeMysqlWildcards($each_only_db);
                continue;
            }

            if (! $this->show_databases_disabled) {
                $items = array_merge($items, $this->retrieve($each_only_db));
                continue;
            }

            // @todo induce error, about not using wildcards
            // with SHOW DATABASE disabled?
        }

        $this->exchangeArray($items);

        return true;
    }

    /**
     * returns default item
     *
     * @return string default item
     */
    public function getDefault()
    {
        if (strlen($GLOBALS['db'])) {
            return $GLOBALS['db'];
        }

        return $this->getEmpty();
    }

    /**
     * this is just a backup, if all is fine this can be deleted later
     *
     * @deprecated
     * @return void
     */
    protected function checkAgainstPrivTables()
    {
        // 1. get allowed dbs from the "mysql.db" table
        // User can be blank (anonymous user)
        $local_query = "
            SELECT DISTINCT `Db` FROM `mysql`.`db`
            WHERE `Select_priv` = 'Y'
            AND `User`
            IN ('" . PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . "', '')";
        $tmp_mydbs = PMA_DBI_fetch_result(
            $local_query, null, null, $GLOBALS['controllink']
        );
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
                            $tmp_regex = preg_replace(
                                '/' . addcslashes($re, '/') . '%/',
                                '\\1.*',
                                preg_replace(
                                    '/' . addcslashes($re, '/') . '_/',
                                    '\\1.{1}',
                                    $tmp_matchpattern
                                )
                            );
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
        $local_query = 'SELECT DISTINCT `Db` FROM `mysql`.`tables_priv`';
        $local_query .= ' WHERE `Table_priv` LIKE \'%Select%\'';
        $local_query .= ' AND `User` = \'';
        $local_query .= PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . '\'';
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
