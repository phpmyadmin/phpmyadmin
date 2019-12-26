<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles bookmarking SQL queries
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Util;

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
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * Current user
     *
     * @var string
     */
    private $user;

    public function __construct(DatabaseInterface $dbi, $user)
    {
        $this->dbi = $dbi;
        $this->user = $user;
    }

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
     */
    public function save()
    {
        $cfgBookmark = self::getParams($this->user);
        if (empty($cfgBookmark)) {
            return false;
        }

        $query = "INSERT INTO " . Util::backquote($cfgBookmark['db'])
            . "." . Util::backquote($cfgBookmark['table'])
            . " (id, dbase, user, query, label) VALUES (NULL, "
            . "'" . $this->dbi->escapeString($this->_database) . "', "
            . "'" . $this->dbi->escapeString($this->_user) . "', "
            . "'" . $this->dbi->escapeString($this->_query) . "', "
            . "'" . $this->dbi->escapeString($this->_label) . "')";
        return $this->dbi->query($query, DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * Deletes a bookmark
     *
     * @return bool true if successful
     *
     * @access public
     */
    public function delete()
    {
        $cfgBookmark = self::getParams($this->user);
        if (empty($cfgBookmark)) {
            return false;
        }

        $query  = "DELETE FROM " . Util::backquote($cfgBookmark['db'])
            . "." . Util::backquote($cfgBookmark['table'])
            . " WHERE id = " . $this->_id;
        return $this->dbi->tryQuery($query, DatabaseInterface::CONNECT_CONTROL);
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
     * @param array $variables array of variables
     *
     * @return string query with variables applied
     */
    public function applyVariables(array $variables)
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
                $var = $this->dbi->escapeString($variables[$i]);
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
     * @param string $user Current user
     * @return array the bookmark parameters for the current user
     * @access  public
     */
    public static function getParams($user)
    {
        static $cfgBookmark = null;

        if (null !== $cfgBookmark) {
            return $cfgBookmark;
        }

        $relation = new Relation();
        $cfgRelation = $relation->getRelationsParam();
        if ($cfgRelation['bookmarkwork']) {
            $cfgBookmark = array(
                'user'  => $user,
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
     * @param DatabaseInterface $dbi        DatabaseInterface object
     * @param string            $user       Current user
     * @param array             $bkm_fields the properties of the bookmark to add; here,
     *                                      $bkm_fields['bkm_sql_query'] is urlencoded
     * @param boolean           $all_users  whether to make the bookmark
     *                                      available for all users
     *
     * @return Bookmark|false
     */
    public static function createBookmark(
        DatabaseInterface $dbi,
        $user,
        array $bkm_fields,
        $all_users = false
    ) {
        if (!(isset($bkm_fields['bkm_sql_query'])
            && strlen($bkm_fields['bkm_sql_query']) > 0
            && isset($bkm_fields['bkm_label'])
            && strlen($bkm_fields['bkm_label']) > 0)
        ) {
            return false;
        }

        $bookmark = new Bookmark($dbi, $user);
        $bookmark->_database = $bkm_fields['bkm_database'];
        $bookmark->_label = $bkm_fields['bkm_label'];
        $bookmark->_query = $bkm_fields['bkm_sql_query'];
        $bookmark->_user = $all_users ? '' : $bkm_fields['bkm_user'];

        return $bookmark;
    }

    /**
     * Gets the list of bookmarks defined for the current database
     *
     * @param DatabaseInterface $dbi  DatabaseInterface object
     * @param string            $user Current user
     * @param string|bool       $db   the current database name or false
     *
     * @return Bookmark[] the bookmarks list
     *
     * @access public
     */
    public static function getList(DatabaseInterface $dbi, $user, $db = false)
    {
        $cfgBookmark = self::getParams($user);
        if (empty($cfgBookmark)) {
            return array();
        }

        $query = "SELECT * FROM " . Util::backquote($cfgBookmark['db'])
            . "." . Util::backquote($cfgBookmark['table'])
            . " WHERE ( `user` = ''"
            . " OR `user` = '" . $dbi->escapeString($cfgBookmark['user']) . "' )";
        if ($db !== false) {
            $query .= " AND dbase = '" . $dbi->escapeString($db) . "'";
        }
        $query .= " ORDER BY label ASC";

        $result = $dbi->fetchResult(
            $query,
            null,
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );

        if (! empty($result)) {
            $bookmarks = array();
            foreach ($result as $row) {
                $bookmark = new Bookmark($dbi, $user);
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
     * @param DatabaseInterface $dbi                 DatabaseInterface object
     * @param string            $user                Current user
     * @param string            $db                  the current database name
     * @param mixed             $id                  an identifier of the bookmark to get
     * @param string            $id_field            which field to look up the identifier
     * @param boolean           $action_bookmark_all true: get all bookmarks regardless
     *                                               of the owning user
     * @param boolean           $exact_user_match    whether to ignore bookmarks with no user
     *
     * @return Bookmark the bookmark
     *
     * @access  public
     *
     */
    public static function get(
        DatabaseInterface $dbi,
        $user,
        $db,
        $id,
        $id_field = 'id',
        $action_bookmark_all = false,
        $exact_user_match = false
    ) {
        $cfgBookmark = self::getParams($user);
        if (empty($cfgBookmark)) {
            return null;
        }

        $query = "SELECT * FROM " . Util::backquote($cfgBookmark['db'])
            . "." . Util::backquote($cfgBookmark['table'])
            . " WHERE dbase = '" . $dbi->escapeString($db) . "'";
        if (! $action_bookmark_all) {
            $query .= " AND (user = '"
                . $dbi->escapeString($cfgBookmark['user']) . "'";
            if (! $exact_user_match) {
                $query .= " OR user = ''";
            }
            $query .= ")";
        }
        $query .= " AND " . Util::backquote($id_field)
            . " = '" . $dbi->escapeString($id) . "' LIMIT 1";

        $result = $dbi->fetchSingleRow($query, 'ASSOC', DatabaseInterface::CONNECT_CONTROL);
        if (! empty($result)) {
            $bookmark = new Bookmark($dbi, $user);
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
