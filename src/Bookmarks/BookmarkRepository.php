<?php
/**
 * Handles bookmarking SQL queries
 */

declare(strict_types=1);

namespace PhpMyAdmin\Bookmarks;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Util;

/**
 * Handles bookmarking SQL queries
 */
final class BookmarkRepository
{
    public function __construct(private DatabaseInterface $dbi, private Relation $relation)
    {
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

        return new Bookmark(
            $dbi,
            new Relation($dbi),
            $database,
            $shared ? '' : $user,
            $label,
            $sqlQuery,
        );
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
    ): Bookmark|null {
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
    ): Bookmark|null {
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

    /** @param string[] $row Resource used to build the bookmark */
    private static function createFromRow(DatabaseInterface $dbi, array $row): Bookmark
    {
        return new Bookmark(
            $dbi,
            new Relation($dbi),
            $row['dbase'],
            $row['user'],
            $row['label'],
            $row['query'],
            (int) $row['id'],
        );
    }
}
