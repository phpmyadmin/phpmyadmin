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
     * Gets the list of bookmarks defined for the current database
     *
     * @param string       $user Current user
     * @param string|false $db   the current database name or false
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

        $exactUserMatch = ! $this->config->config->AllowSharedBookmarks;

        $query = 'SELECT * FROM ' . Util::backquote($bookmarkFeature->database)
            . '.' . Util::backquote($bookmarkFeature->bookmark)
            . ' WHERE (`user` = ' . $this->dbi->quoteString($user);
        if (! $exactUserMatch) {
            $query .= " OR `user` = ''";
        }

        $query .= ')';

        if ($db !== false) {
            $query .= ' AND dbase = ' . $this->dbi->quoteString($db);
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
     * Retrieve a specific bookmark
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
            $query .= ' AND (user = ' . $this->dbi->quoteString($user);

            $exactUserMatch = ! $this->config->config->AllowSharedBookmarks;
            if (! $exactUserMatch) {
                $query .= " OR user = ''";
            }

            $query .= ')';
        }

        $query .= ' LIMIT 1';

        $result = $this->dbi->fetchSingleRow($query, DatabaseInterface::FETCH_ASSOC, ConnectionType::ControlUser);
        if ($result !== []) {
            return $this->createFromRow($result, $bookmarkFeature);
        }

        return null;
    }

    /**
     * Retrieve a specific bookmark by its label
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
            . ' AND user = ' . $this->dbi->quoteString($user)
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

    private function getBookmarkFeature(): BookmarkFeature|null
    {
        if ($this->relationParameters === null) {
            $this->relationParameters = $this->relation->getRelationParameters();
        }

        return $this->relationParameters->bookmarkFeature;
    }
}
