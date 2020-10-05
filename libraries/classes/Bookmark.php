<?php
/**
 * Handles bookmarking SQL queries
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use const PREG_SET_ORDER;
use function count;
use function is_array;
use function preg_match_all;
use function preg_replace;
use function str_replace;
use function strlen;

/**
 * Handles bookmarking SQL queries
 */
class Bookmark
{
    /**
     * ID of the bookmark
     *
     * @var int
     */
    private $id;
    /**
     * Database the bookmark belongs to
     *
     * @var string
     */
    private $database;
    /**
     * The user to whom the bookmark belongs, empty for public bookmarks
     *
     * @var string
     */
    private $currentUser;
    /**
     * Label of the bookmark
     *
     * @var string
     */
    private $label;
    /**
     * SQL query that is bookmarked
     *
     * @var string
     */
    private $query;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * Current user
     *
     * @var string
     */
    private $user;

    /**
     * @param DatabaseInterface $dbi  DatabaseInterface object
     * @param string            $user Current user
     */
    public function __construct(DatabaseInterface $dbi, string $user)
    {
        $this->dbi = $dbi;
        $this->user = $user;
    }

    /**
     * Returns the ID of the bookmark
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Returns the database of the bookmark
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Returns the user whom the bookmark belongs to
     */
    public function getUser(): string
    {
        return $this->currentUser;
    }

    /**
     * Returns the label of the bookmark
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Returns the query
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Adds a bookmark
     *
     * @return bool whether the INSERT succeeds or not
     *
     * @access public
     */
    public function save(): bool
    {
        $cfgBookmark = self::getParams($this->user);
        if (! is_array($cfgBookmark)) {
            return false;
        }

        $query = 'INSERT INTO ' . Util::backquote($cfgBookmark['db'])
            . '.' . Util::backquote($cfgBookmark['table'])
            . ' (id, dbase, user, query, label) VALUES (NULL, '
            . "'" . $this->dbi->escapeString($this->database) . "', "
            . "'" . $this->dbi->escapeString($this->currentUser) . "', "
            . "'" . $this->dbi->escapeString($this->query) . "', "
            . "'" . $this->dbi->escapeString($this->label) . "')";

        return $this->dbi->query($query, DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * Deletes a bookmark
     *
     * @return bool true if successful
     *
     * @access public
     */
    public function delete(): bool
    {
        $cfgBookmark = self::getParams($this->user);
        if (! is_array($cfgBookmark)) {
            return false;
        }

        $query  = 'DELETE FROM ' . Util::backquote($cfgBookmark['db'])
            . '.' . Util::backquote($cfgBookmark['table'])
            . ' WHERE id = ' . $this->id;

        return $this->dbi->tryQuery($query, DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * Returns the number of variables in a bookmark
     *
     * @return int number of variables
     */
    public function getVariableCount(): int
    {
        $matches = [];
        preg_match_all('/\[VARIABLE[0-9]*\]/', $this->query, $matches, PREG_SET_ORDER);

        return count($matches);
    }

    /**
     * Replace the placeholders in the bookmark query with variables
     *
     * @param array $variables array of variables
     *
     * @return string query with variables applied
     */
    public function applyVariables(array $variables): string
    {
        // remove comments that encloses a variable placeholder
        $query = (string) preg_replace(
            '|/\*(.*\[VARIABLE[0-9]*\].*)\*/|imsU',
            '${1}',
            $this->query
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
            if ($i != 1) {
                continue;
            }

            $query = str_replace('[VARIABLE]', $var, $query);
        }

        return $query;
    }

    /**
     * Defines the bookmark parameters for the current user
     *
     * @param string $user Current user
     *
     * @return array|bool the bookmark parameters for the current user
     *
     * @access public
     */
    public static function getParams(string $user)
    {
        global $dbi;

        static $cfgBookmark = null;

        if ($cfgBookmark !== null) {
            return $cfgBookmark;
        }

        $relation = new Relation($dbi);
        $cfgRelation = $relation->getRelationsParam();
        if ($cfgRelation['bookmarkwork']) {
            $cfgBookmark = [
                'user'  => $user,
                'db'    => $cfgRelation['db'],
                'table' => $cfgRelation['bookmark'],
            ];
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
     * @param bool              $all_users  whether to make the bookmark
     *                                      available for all users
     *
     * @return Bookmark|false
     */
    public static function createBookmark(
        DatabaseInterface $dbi,
        string $user,
        array $bkm_fields,
        bool $all_users = false
    ) {
        if (! (isset($bkm_fields['bkm_sql_query'], $bkm_fields['bkm_label'])
            && strlen($bkm_fields['bkm_sql_query']) > 0
            && strlen($bkm_fields['bkm_label']) > 0)
        ) {
            return false;
        }

        $bookmark = new Bookmark($dbi, $user);
        $bookmark->database = $bkm_fields['bkm_database'];
        $bookmark->label = $bkm_fields['bkm_label'];
        $bookmark->query = $bkm_fields['bkm_sql_query'];
        $bookmark->currentUser = $all_users ? '' : $bkm_fields['bkm_user'];

        return $bookmark;
    }

    /**
     * @param DatabaseInterface $dbi  DatabaseInterface object
     * @param string            $user Current user
     * @param array             $row  Resource used to build the bookmark
     */
    protected static function createFromRow(
        DatabaseInterface $dbi,
        string $user,
        $row
    ): Bookmark {
        $bookmark = new Bookmark($dbi, $user);
        $bookmark->id = $row['id'];
        $bookmark->database = $row['dbase'];
        $bookmark->currentUser = $row['user'];
        $bookmark->label = $row['label'];
        $bookmark->query = $row['query'];

        return $bookmark;
    }

    /**
     * Gets the list of bookmarks defined for the current database
     *
     * @param DatabaseInterface $dbi  DatabaseInterface object
     * @param string            $user Current user
     * @param string|false      $db   the current database name or false
     *
     * @return Bookmark[] the bookmarks list
     *
     * @access public
     */
    public static function getList(
        DatabaseInterface $dbi,
        string $user,
        $db = false
    ): array {
        $cfgBookmark = self::getParams($user);
        if (! is_array($cfgBookmark)) {
            return [];
        }

        $query = 'SELECT * FROM ' . Util::backquote($cfgBookmark['db'])
            . '.' . Util::backquote($cfgBookmark['table'])
            . " WHERE ( `user` = ''"
            . " OR `user` = '" . $dbi->escapeString($cfgBookmark['user']) . "' )";
        if ($db !== false) {
            $query .= " AND dbase = '" . $dbi->escapeString($db) . "'";
        }
        $query .= ' ORDER BY label ASC';

        $result = $dbi->fetchResult(
            $query,
            null,
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );

        if (! empty($result)) {
            $bookmarks = [];
            foreach ($result as $row) {
                $bookmarks[] = self::createFromRow($dbi, $user, $row);
            }

            return $bookmarks;
        }

        return [];
    }

    /**
     * Retrieve a specific bookmark
     *
     * @param DatabaseInterface $dbi                 DatabaseInterface object
     * @param string            $user                Current user
     * @param string            $db                  the current database name
     * @param int|string        $id                  an identifier of the bookmark to get
     * @param string            $id_field            which field to look up the identifier
     * @param bool              $action_bookmark_all true: get all bookmarks regardless
     *                                               of the owning user
     * @param bool              $exact_user_match    whether to ignore bookmarks with no user
     *
     * @return Bookmark|null the bookmark
     *
     * @access public
     */
    public static function get(
        DatabaseInterface $dbi,
        string $user,
        string $db,
        $id,
        string $id_field = 'id',
        bool $action_bookmark_all = false,
        bool $exact_user_match = false
    ): ?self {
        $cfgBookmark = self::getParams($user);
        if (! is_array($cfgBookmark)) {
            return null;
        }

        $query = 'SELECT * FROM ' . Util::backquote($cfgBookmark['db'])
            . '.' . Util::backquote($cfgBookmark['table'])
            . " WHERE dbase = '" . $dbi->escapeString($db) . "'";
        if (! $action_bookmark_all) {
            $query .= " AND (user = '"
                . $dbi->escapeString($cfgBookmark['user']) . "'";
            if (! $exact_user_match) {
                $query .= " OR user = ''";
            }
            $query .= ')';
        }
        $query .= ' AND ' . Util::backquote($id_field)
            . " = '" . $dbi->escapeString((string) $id) . "' LIMIT 1";

        $result = $dbi->fetchSingleRow($query, 'ASSOC', DatabaseInterface::CONNECT_CONTROL);
        if (! empty($result)) {
            return self::createFromRow($dbi, $user, $result);
        }

        return null;
    }
}
