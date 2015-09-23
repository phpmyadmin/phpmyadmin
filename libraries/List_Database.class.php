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
require_once './libraries/check_user_privileges.lib.php';

/**
 * handles database lists
 *
 * <code>
 * $PMA_List_Database = new PMA_List_Database($userlink);
 * </code>
 *
 * @todo this object should be attached to the PMA_Server object
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
     * Constructor
     *
     * @param mixed $db_link_user user database link resource|object
     */
    public function __construct($db_link_user = null)
    {
        $this->db_link = $db_link_user;
        $this->db_link_user = $db_link_user;

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
     * @param string $like_db_name usually a db_name containing wildcards
     *
     * @return array
     */
    protected function retrieve($like_db_name = null)
    {
        $database_list = array();
        $command = "";
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $command .= "SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`";
            if (null !== $like_db_name) {
                $command .= " WHERE `SCHEMA_NAME` LIKE '" . $like_db_name . "'";
            }
        } else {
            if ($GLOBALS['dbs_to_test'] === false || null !== $like_db_name) {
                $command .= "SHOW DATABASES";
                if (null !== $like_db_name) {
                    $command .= " LIKE '" . $like_db_name . "'";
                }
            } else {
                foreach ($GLOBALS['dbs_to_test'] as $db) {
                    $database_list = array_merge(
                        $database_list, $this->retrieve($db)
                    );
                }
            }
        }

        if ($command) {
            $database_list = $GLOBALS['dbi']->fetchResult(
                $command, null, null, $this->db_link
            );
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
            && /*overload*/mb_strlen($GLOBALS['cfg']['Server']['only_db'])
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

            $items = array_merge($items, $this->retrieve($each_only_db));
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
        if (/*overload*/mb_strlen($GLOBALS['db'])) {
            return $GLOBALS['db'];
        }

        return $this->getEmpty();
    }
}
