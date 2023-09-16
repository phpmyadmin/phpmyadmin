<?php
/**
 * Handles bookmarking SQL queries
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Identifiers\DatabaseName;

use function count;
use function preg_match_all;
use function preg_replace;
use function str_replace;

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
     * @param mixed[] $variables
     *
     * @return string query with variables applied
     */
    public function applyVariables(array $variables): string
    {
        // remove comments that encloses a variable placeholder
        $query = (string) preg_replace('|/\*(.*\[VARIABLE[0-9]*\].*)\*/|imsU', '${1}', $this->query);
        // replace variable placeholders with values
        $numberOfVariables = $this->getVariableCount();
        for ($i = 1; $i <= $numberOfVariables; $i++) {
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
     * @param bool $shared whether to make the bookmark available for all users
     */
    public static function createBookmark(
        DatabaseInterface $dbi,
        string $sqlQuery,
        string $label,
        string $user,
        string $database,
        bool $shared = false,
    ): Bookmark|false {
        if ($sqlQuery === '' || $label === '') {
            return false;
        }

        if (! Config::getInstance()->settings['AllowSharedBookmarks']) {
            $shared = false;
        }

        if (! $shared && $user === '') {
            return false;
        }

        $bookmark = new Bookmark($dbi, new Relation($dbi));
        $bookmark->database = $database;
        $bookmark->label = $label;
        $bookmark->query = $sqlQuery;
        $bookmark->currentUser = $shared ? '' : $user;

        return $bookmark;
    }

    /** @param mixed[] $row Resource used to build the bookmark */
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
     *
     * @infection-ignore-all
     */
    public static function getList(
        BookmarkFeature $bookmarkFeature,
        DatabaseInterface $dbi,
        string $user,
        string|false $db = false,
    ): array {
        $exactUserMatch = ! Config::getInstance()->settings['AllowSharedBookmarks'];

        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE (`user` = ' . $dbi->quoteString($user);
        if (! $exactUserMatch) {
            $query .= " OR `user` = ''";
        }

        $query .= ')';

        if ($db !== false) {
            $query .= ' AND dbase = ' . $dbi->quoteString($db);
        }

        $query .= ' ORDER BY label ASC';

        $result = $dbi->fetchResult($query, null, null, Connection::TYPE_CONTROL);

        $bookmarks = [];
        foreach ($result as $row) {
            $bookmarks[] = self::createFromRow($dbi, $row);
        }

        return $bookmarks;
    }

    /**
     * Retrieve a specific bookmark
     */
    public static function get(
        DatabaseInterface $dbi,
        string|null $user,
        int $id,
    ): self|null {
        $relation = new Relation($dbi);
        $bookmarkFeature = $relation->getRelationParameters()->bookmarkFeature;
        if ($bookmarkFeature === null) {
            return null;
        }

        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE `id` = ' . $id;

        if ($user !== null) {
            $query .= ' AND (user = ' . $dbi->quoteString($user);

            $exactUserMatch = ! Config::getInstance()->settings['AllowSharedBookmarks'];
            if (! $exactUserMatch) {
                $query .= " OR user = ''";
            }

            $query .= ')';
        }

        $query .= ' LIMIT 1';

        $result = $dbi->fetchSingleRow($query, DatabaseInterface::FETCH_ASSOC, Connection::TYPE_CONTROL);
        if ($result !== null) {
            return self::createFromRow($dbi, $result);
        }

        return null;
    }

    /**
     * Retrieve a specific bookmark by its label
     */
    public static function getByLabel(
        DatabaseInterface $dbi,
        string $user,
        DatabaseName $db,
        string $label,
    ): self|null {
        $relation = new Relation($dbi);
        $bookmarkFeature = $relation->getRelationParameters()->bookmarkFeature;
        if ($bookmarkFeature === null) {
            return null;
        }

        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE `label`'
            . ' = ' . $dbi->quoteString($label)
            . ' AND dbase = ' . $dbi->quoteString($db->getName())
            . ' AND user = ' . $dbi->quoteString($user)
            . ' LIMIT 1';

        $result = $dbi->fetchSingleRow($query, DatabaseInterface::FETCH_ASSOC, Connection::TYPE_CONTROL);
        if ($result !== null) {
            return self::createFromRow($dbi, $result);
        }

        return null;
    }
}
