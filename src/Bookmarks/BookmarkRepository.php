<?php
/**
 * Handles bookmarking SQL queries
 */

declare(strict_types=1);

namespace PhpMyAdmin\Bookmarks;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Util;

/**
 * Handles bookmarking SQL queries
 */
final class BookmarkRepository
{
    private RelationParameters|null $relationParameters = null;

    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly Relation $relation,
        private readonly Config $config,
    ) {
    }

    /**
     * Creates a Bookmark object from the parameters
     *
     * @param bool $shared whether to make the bookmark available for all users
     */
    public function createBookmark(
        string $sqlQuery,
        string $label,
        string $user,
        string $database,
        bool $shared = false,
    ): Bookmark|false {
        $bookmarkFeature = $this->getBookmarkFeature();
        if ($bookmarkFeature === null) {
            return false;
        }

        if ($sqlQuery === '' || $label === '') {
            return false;
        }

        if (! $this->config->config->AllowSharedBookmarks) {
            $shared = false;
        }

        if (! $shared && $user === '') {
            return false;
        }

        return new Bookmark($this->dbi, $bookmarkFeature, $database, $shared ? '' : $user, $label, $sqlQuery);
    }

    /**
     * Retrieve the bookmarks visible to the given user, optionally scoped to a database.
     *
     * Bookmarks shared across users (empty `user`, subject to
     * AllowSharedBookmarks) and bookmarks shared across databases (empty
     * `dbase`, created without a database context) are always included
     * alongside the exact matches.
     *
     * @param string       $user Current user
     * @param string|false $db   the database to scope the results to, or false to return
     *                           bookmarks for every database
     *
     * @return Bookmark[] the bookmarks list
     *
     * @infection-ignore-all
     */
    public function getList(
        string $user,
        string|false $db = false,
    ): array {
        $bookmarkFeature = $this->getBookmarkFeature();
        if ($bookmarkFeature === null) {
            return [];
        }

        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE (' . $this->userMatchCondition('`user`', $user) . ')';

        if ($db !== false) {
            $query .= ' AND (dbase = ' . $this->dbi->quoteString($db) . " OR dbase = '')";
        }

        $query .= ' ORDER BY label ASC';

        $result = $this->dbi->fetchResultSimple($query, ConnectionType::ControlUser);

        $bookmarks = [];
        foreach ($result as $row) {
            $bookmarks[] = $this->createFromRow($row, $bookmarkFeature);
        }

        return $bookmarks;
    }

    /**
     * Retrieve a specific bookmark by id.
     *
     * When $user is not null, only bookmarks owned by that user (or shared,
     * subject to AllowSharedBookmarks) are matched. Passing null skips the
     * user filter entirely, returning the bookmark regardless of its owner
     * — callers are responsible for their own authorization check in that case.
     *
     * @return Bookmark|null the matching bookmark, or null if none exists
     */
    public function get(
        string|null $user,
        int $id,
    ): Bookmark|null {
        $bookmarkFeature = $this->getBookmarkFeature();
        if ($bookmarkFeature === null) {
            return null;
        }

        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE `id` = ' . $id;

        if ($user !== null) {
            $query .= ' AND (' . $this->userMatchCondition('user', $user) . ')';
        }

        $query .= ' LIMIT 1';

        $result = $this->dbi->fetchSingleRow($query, DatabaseInterface::FETCH_ASSOC, ConnectionType::ControlUser);
        if ($result !== []) {
            return $this->createFromRow($result, $bookmarkFeature);
        }

        return null;
    }

    /**
     * Retrieve a specific bookmark by its label.
     *
     * Matches bookmarks shared across users (empty `user`, subject to
     * AllowSharedBookmarks) as well as bookmarks owned by the given user —
     * same fallback behavior as getList(). Used by
     * {@see \PhpMyAdmin\Sql::getDefaultSqlQueryForBrowse()} to look up a
     * per-table default browse query, which is why a bookmark labeled after
     * a table can be shared to apply to every user browsing it.
     *
     * @return Bookmark|null the matching bookmark, or null if none exists
     */
    public function getByLabel(
        string $user,
        DatabaseName $db,
        string $label,
    ): Bookmark|null {
        $bookmarkFeature = $this->getBookmarkFeature();
        if ($bookmarkFeature === null) {
            return null;
        }

        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE `label`'
            . ' = ' . $this->dbi->quoteString($label)
            . ' AND dbase = ' . $this->dbi->quoteString($db->getName())
            . ' AND (' . $this->userMatchCondition('user', $user) . ')'
            . ' LIMIT 1';

        $result = $this->dbi->fetchSingleRow($query, DatabaseInterface::FETCH_ASSOC, ConnectionType::ControlUser);
        if ($result !== []) {
            return $this->createFromRow($result, $bookmarkFeature);
        }

        return null;
    }

    /** @param string[] $row Resource used to build the bookmark */
    private function createFromRow(array $row, BookmarkFeature $bookmarkFeature): Bookmark
    {
        return new Bookmark(
            $this->dbi,
            $bookmarkFeature,
            $row['dbase'],
            $row['user'],
            $row['label'],
            $row['query'],
            (int) $row['id'],
        );
    }

    /**
     * Builds the `WHERE` fragment that matches the owner of a bookmark,
     * accounting for shared (publicly owned, empty `user`) bookmarks.
     */
    private function userMatchCondition(string $column, string $user): string
    {
        $condition = $column . ' = ' . $this->dbi->quoteString($user);

        return $this->config->config->AllowSharedBookmarks
            ? $condition . ' OR ' . $column . " = ''"
            : $condition;
    }

    private function getBookmarkFeature(): BookmarkFeature|null
    {
        if ($this->relationParameters === null) {
            $this->relationParameters = $this->relation->getRelationParameters();
        }

        return $this->relationParameters->bookmarkFeature;
    }
}
