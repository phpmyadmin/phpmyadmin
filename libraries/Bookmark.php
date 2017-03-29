<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles bookmarking SQL queries
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use PMA\libraries\Util;
use PMA\libraries\DatabaseInterface;

/**
 * Handles bookmarking SQL queries
 *
 * @package PhpMyAdmin
 */
class Bookmark
{
    /**
     * ID of the bookmark
     *
     * @var int
     */
    private $_id;
    /**
     * Database the bookmark belongs to
     *
     * @var string
     */
    private $_database;
    /**
     * The user to whom the bookmark belongs, empty for public bookmarks
     *
     * @var string
     */
    private $_user;
    /**
     * Label of the bookmark
     *
     * @var string
     */
    private $_label;
    /**
     * SQL query that is bookmarked
     *
     * @var string
     */
    private $_query;

    /**
     * Returns the ID of the bookmark
     *
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns the database of the bookmark
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->_database;
    }

    /**
     * Returns the user whom the bookmark belongs to
     *
     * @return string
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Returns the label of the bookmark
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Returns the query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Adds a bookmark
     *
     * @return boolean whether the INSERT succeeds or not
     *
     * @access public
     *
     * @global resource $controllink the controluser db connection handle
     */
    public function save()
    {
        global $controllink;

        $cfgBookmark = self::getParams();
        if (empty($cfgBookmark)) {
            return false;
        }

        $query = "INSERT INTO " . Util::backquote($cfgBookmark['db'])
            . "." . Util::backquote($cfgBookmark['table'])
            . " (id, dbase, user, query, label) VALUES (NULL, "
            . "'" . $GLOBALS['dbi']->escapeString($this->_database) . "', "
            . "'" . $GLOBALS['dbi']->escapeString($this->_user) . "', "
            . "'" . $GLOBALS['dbi']->escapeString($this->_query) . "', "
            . "'" . $GLOBALS['dbi']->escapeString($this->_label) . "')";
        return $GLOBALS['dbi']->query($query, $controllink);
    }

    /**
     * Deletes a bookmark
     *
     * @return bool true if successful
     *
     * @access public
     *
     * @global resource $controllink the controluser db connection handle
     */
    public function delete()
    {
        global $controllink;

        $cfgBookmark = self::getParams();
        if (empty($cfgBookmark)) {
            return false;
        }

        $query  = "DELETE FROM " . Util::backquote($cfgBookmark['db'])
            . "." . Util::backquote($cfgBookmark['table'])
            . " WHERE id = " . $this->_id;
        return $GLOBALS['dbi']->tryQuery($query, $controllink);
    }

    /**
     * Returns the number of variables in a bookmark
     *
     * @return number number of variables
     */
    public function getVariableCount()
    {
        $matches = array();
        preg_match_all("/\[VARIABLE[0-9]*\]/", $this->_query, $matches, PREG_SET_ORDER);
        return count($matches);
    }

    /**
     * Replace the placeholders in the bookmark query with variables
     *
     * @param  array $variables array of variables
     *
     * @return string query with variables applied
     */
    public function applyVariables($variables)
    {
        // remove comments that encloses a variable placeholder
        $query = preg_replace(
            '|/\*(.*\[VARIABLE[0-9]*\].*)\*/|imsU',
            '${1}',
            $this->_query
        );
        // replace variable placeholders with values
        $number_of_variables = $this->getVariableCount();
        for ($i = 1; $i <= $number_of_variables; $i++) {
            $var = '';
            if (! empty($variables[$i])) {
                $var = $GLOBALS['dbi']->escapeString($variables[$i]);
            }
            $query = str_replace('[VARIABLE' . $i . ']', $var, $query);
            // backward compatibility
            if ($i == 1) {
                $query = str_replace('[VARIABLE]', $var, $query);
            }
        }
        return $query;
    }

    /**
     * Defines the bookmark parameters for the current user
     *
     * @return array the bookmark parameters for the current user
     * @access  public
     */
    public static function getParams()
    {
        static $cfgBookmark = null;

        if (null !== $cfgBookmark) {
            return $cfgBookmark;
        }

        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['bookmarkwork']) {
            $cfgBookmark = array(
                'user'  => $GLOBALS['cfg']['Server']['user'],
                'db'    => $cfgRelation['db'],
                'table' => $cfgRelation['bookmark'],
            );
        } else {
            $cfgBookmark = false;
        }

        return $cfgBookmark;
    }

    /**
     * Creates a Bookmark object from the parameters
     *
     * @param array   $bkm_fields the properties of the bookmark to add; here,
     *                            $bkm_fields['bkm_sql_query'] is urlencoded
     * @param boolean $all_users  whether to make the bookmark available
     *                            for all users
     *
     * @return Bookmark|false
     */
    public static function createBookmark($bkm_fields, $all_users = false)
    {
        if (!(isset($bkm_fields['bkm_sql_query'])
            && strlen($bkm_fields['bkm_sql_query']) > 0
            && isset($bkm_fields['bkm_label'])
            && strlen($bkm_fields['bkm_label']) > 0)
        ) {
            return false;
        }

        $bookmark = new Bookmark();
        $bookmark->_database = $bkm_fields['bkm_database'];
        $bookmark->_label = $bkm_fields['bkm_label'];
        $bookmark->_query = $bkm_fields['bkm_sql_query'];
        $bookmark->_user = $all_users ? '' : $bkm_fields['bkm_user'];

        return $bookmark;
    }

    /**
     * Gets the list of bookmarks defined for the current database
     *
     * @param string|bool $db the current database name or false
     *
     * @return Bookmark[] the bookmarks list
     *
     * @access public
     *
     * @global resource $controllink the controluser db connection handle
     */
    public static function getList($db = false)
    {
        global $controllink;

        $cfgBookmark = self::getParams();
        if (empty($cfgBookmark)) {
            return array();
        }

        $query = "SELECT * FROM " . Util::backquote($cfgBookmark['db'])
            . "." . Util::backquote($cfgBookmark['table'])
            . " WHERE `user` = ''"
            . " OR `user` = '" . $GLOBALS['dbi']->escapeString($cfgBookmark['user']) . "'";
        if ($db !== false) {
            $query .= " AND dbase = '" . $GLOBALS['dbi']->escapeString($db) . "'";
        }
        $query .= " ORDER BY label ASC";

        $result = $GLOBALS['dbi']->fetchResult(
            $query,
            null,
            null,
            $controllink,
            DatabaseInterface::QUERY_STORE
        );

        if (! empty($result)) {
            $bookmarks = array();
            foreach ($result as $row) {
                $bookmark = new Bookmark();
                $bookmark->_id = $row['id'];
                $bookmark->_database = $row['dbase'];
                $bookmark->_user = $row['user'];
                $bookmark->_label = $row['label'];
                $bookmark->_query = $row['query'];
                $bookmarks[] = $bookmark;
            }

            return $bookmarks;
        }

        return array();
    }

    /**
     * Retrieve a specific bookmark
     *
     * @param string  $db                  the current database name
     * @param mixed   $id                  an identifier of the bookmark to get
     * @param string  $id_field            which field to look up the identifier
     * @param boolean $action_bookmark_all true: get all bookmarks regardless
     *                                     of the owning user
     * @param boolean $exact_user_match    whether to ignore bookmarks with no user
     *
     * @return Bookmark the bookmark
     *
     * @access  public
     *
     * @global  resource $controllink the controluser db connection handle
     *
     */
    public static function get($db, $id, $id_field = 'id',
        $action_bookmark_all = false, $exact_user_match = false
    ) {
        global $controllink;

        $cfgBookmark = self::getParams();
        if (empty($cfgBookmark)) {
            return null;
        }

        $query = "SELECT * FROM " . Util::backquote($cfgBookmark['db'])
            . "." . Util::backquote($cfgBookmark['table'])
            . " WHERE dbase = '" . $GLOBALS['dbi']->escapeString($db) . "'";
        if (! $action_bookmark_all) {
            $query .= " AND (user = '"
                . $GLOBALS['dbi']->escapeString($cfgBookmark['user']) . "'";
            if (! $exact_user_match) {
                $query .= " OR user = ''";
            }
            $query .= ")";
        }
        $query .= " AND " . Util::backquote($id_field)
            . " = " . $GLOBALS['dbi']->escapeString($id) . " LIMIT 1";

        $result = $GLOBALS['dbi']->fetchSingleRow($query, 'ASSOC', $controllink);
        if (! empty($result)) {
            $bookmark = new Bookmark();
            $bookmark->_id = $result['id'];
            $bookmark->_database = $result['dbase'];
            $bookmark->_user = $result['user'];
            $bookmark->_label = $result['label'];
            $bookmark->_query = $result['query'];
            return $bookmark;
        }

        return null;
    }
}
