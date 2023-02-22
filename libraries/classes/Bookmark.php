<?php
/**
 * Handles bookmarking SQL queries
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DatabaseName;

use function count;
use function preg_match_all;
use function preg_replace;
use function str_replace;
use function strlen;

use const PREG_SET_ORDER;

/**
 * Handles bookmarking SQL queries
 */
class Bookmark
{
    /**
     * ID of the bookmark
     */
    private int $id = 0;
    /**
     * Database the bookmark belongs to
     */
    private string $database = '';
    /**
     * The user to whom the bookmark belongs, empty for public bookmarks
     */
    private string $currentUser = '';
    /**
     * Label of the bookmark
     */
    private string $label = '';
    /**
     * SQL query that is bookmarked
     */
    private string $query = '';

    public function __construct(private DatabaseInterface $dbi, private Relation $relation)
    {
    }

    /**
     * Returns the ID of the bookmark
     */
    public function getId(): int
    {
        return $this->id;
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
     */
    public function save(): bool
    {
        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        if ($bookmarkFeature === null) {
            return false;
        }

        $query = 'INSERT INTO ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' (id, dbase, user, query, label) VALUES (NULL, '
            . $this->dbi->quoteString($this->database) . ', '
            . $this->dbi->quoteString($this->currentUser) . ', '
            . $this->dbi->quoteString($this->query) . ', '
            . $this->dbi->quoteString($this->label) . ')';

        return (bool) $this->dbi->query($query, Connection::TYPE_CONTROL);
    }

    /**
     * Deletes a bookmark
     */
    public function delete(): bool
    {
        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        if ($bookmarkFeature === null) {
            return false;
        }

        $query = 'DELETE FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE id = ' . $this->id;

        return (bool) $this->dbi->tryQuery($query, Connection::TYPE_CONTROL);
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
     * @param array $variables
     *
     * @return string query with variables applied
     */
    public function applyVariables(array $variables): string
    {
        // remove comments that encloses a variable placeholder
        $query = (string) preg_replace('|/\*(.*\[VARIABLE[0-9]*\].*)\*/|imsU', '${1}', $this->query);
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
     * Creates a Bookmark object from the parameters
     *
     * @param array $bkm_fields the properties of the bookmark to add; here, $bkm_fields['bkm_sql_query'] is urlencoded
     * @param bool  $all_users  whether to make the bookmark available for all users
     */
    public static function createBookmark(
        DatabaseInterface $dbi,
        array $bkm_fields,
        bool $all_users = false,
    ): Bookmark|false {
        if (
            ! (isset($bkm_fields['bkm_sql_query'], $bkm_fields['bkm_label'])
            && strlen($bkm_fields['bkm_sql_query']) > 0
            && strlen($bkm_fields['bkm_label']) > 0)
        ) {
            return false;
        }

        $bookmark = new Bookmark($dbi, new Relation($dbi));
        $bookmark->database = $bkm_fields['bkm_database'];
        $bookmark->label = $bkm_fields['bkm_label'];
        $bookmark->query = $bkm_fields['bkm_sql_query'];
        $bookmark->currentUser = $all_users ? '' : $bkm_fields['bkm_user'];

        return $bookmark;
    }

    /** @param array $row Resource used to build the bookmark */
    protected static function createFromRow(DatabaseInterface $dbi, array $row): Bookmark
    {
        $bookmark = new Bookmark($dbi, new Relation($dbi));
        $bookmark->id = (int) $row['id'];
        $bookmark->database = $row['dbase'];
        $bookmark->currentUser = $row['user'];
        $bookmark->label = $row['label'];
        $bookmark->query = $row['query'];

        return $bookmark;
    }

    /**
     * Gets the list of bookmarks defined for the current database
     *
     * @param string       $user Current user
     * @param string|false $db   the current database name or false
     *
     * @return Bookmark[] the bookmarks list
     */
    public static function getList(
        BookmarkFeature $bookmarkFeature,
        DatabaseInterface $dbi,
        string $user,
        string|false $db = false,
    ): array {
        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . " WHERE ( `user` = ''"
            . ' OR `user` = ' . $dbi->quoteString($user) . ' )';
        if ($db !== false) {
            $query .= ' AND dbase = ' . $dbi->quoteString($db);
        }

        $query .= ' ORDER BY label ASC';

        $result = $dbi->fetchResult(
            $query,
            null,
            null,
            Connection::TYPE_CONTROL,
        );

        $bookmarks = [];
        foreach ($result as $row) {
            $bookmarks[] = self::createFromRow($dbi, $row);
        }

        return $bookmarks;
    }

    /**
     * Retrieve a specific bookmark
     *
     * @param string       $user                Current user
     * @param DatabaseName $db                  the current database name
     * @param int|string   $id                  an identifier of the bookmark to get
     * @param string       $id_field            which field to look up the identifier
     * @param bool         $action_bookmark_all true: get all bookmarks regardless
     *                                          of the owning user
     * @param bool         $exact_user_match    whether to ignore bookmarks with no user
     *
     * @return Bookmark|null the bookmark
     */
    public static function get(
        DatabaseInterface $dbi,
        string $user,
        DatabaseName $db,
        int|string $id,
        string $id_field = 'id',
        bool $action_bookmark_all = false,
        bool $exact_user_match = false,
    ): self|null {
        $relation = new Relation($dbi);
        $bookmarkFeature = $relation->getRelationParameters()->bookmarkFeature;
        if ($bookmarkFeature === null) {
            return null;
        }

        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE dbase = ' . $dbi->quoteString($db->getName());
        if (! $action_bookmark_all) {
            $query .= ' AND (user = ' . $dbi->quoteString($user);
            if (! $exact_user_match) {
                $query .= " OR user = ''";
            }

            $query .= ')';
        }

        $query .= ' AND ' . Util::backquote($id_field)
            . ' = ' . $dbi->quoteString((string) $id) . ' LIMIT 1';

        $result = $dbi->fetchSingleRow($query, DatabaseInterface::FETCH_ASSOC, Connection::TYPE_CONTROL);
        if ($result !== null) {
            return self::createFromRow($dbi, $result);
        }

        return null;
    }
}
