<?php
/**
 * Handles bookmarking SQL queries
 */

declare(strict_types=1);

namespace PhpMyAdmin\Bookmarks;

use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Util;

use function array_map;
use function max;
use function preg_match_all;
use function preg_replace;
use function str_replace;

/**
 * Handles bookmarking SQL queries
 */
class Bookmark
{
    /**
     * @param string $database    Database the bookmark belongs to
     * @param string $currentUser The user to whom the bookmark belongs, empty for public bookmarks
     * @param string $label       Label of the bookmark
     * @param string $query       SQL query that is bookmarked
     * @param int    $id          ID of the bookmark
     */
    public function __construct(
        private DatabaseInterface $dbi,
        private BookmarkFeature $bookmarkFeature,
        private string $database,
        private string $currentUser,
        private string $label,
        private string $query,
        private int $id = 0,
    ) {
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
        $query = 'INSERT INTO ' . Util::backquote($this->bookmarkFeature->database)
            . '.' . Util::backquote($this->bookmarkFeature->bookmark)
            . ' (id, dbase, user, query, label) VALUES (NULL, '
            . $this->dbi->quoteString($this->database) . ', '
            . $this->dbi->quoteString($this->currentUser) . ', '
            . $this->dbi->quoteString($this->query) . ', '
            . $this->dbi->quoteString($this->label) . ')';

        return (bool) $this->dbi->query($query, ConnectionType::ControlUser);
    }

    /**
     * Deletes a bookmark
     */
    public function delete(): bool
    {
        $query = 'DELETE FROM ' . Util::backquote($this->bookmarkFeature->database)
            . '.' . Util::backquote($this->bookmarkFeature->bookmark)
            . ' WHERE id = ' . $this->id;

        return (bool) $this->dbi->tryQuery($query, ConnectionType::ControlUser);
    }

    /**
     * Returns the highest variable number referenced in the bookmark.
     *
     * This is not the number of `[VARIABLEn]` occurrences — a query can
     * reference the same number more than once, or skip a number, and the
     * count of placeholders would then diverge from the count of distinct
     * variables actually needed. applyVariables() relies on this to know how
     * many substitution slots to fill; the bare `[VARIABLE]` form (no
     * number) counts as variable 1, same as applyVariables() treats it.
     *
     * @return int highest variable number referenced, or 0 if none
     */
    public function getVariableCount(): int
    {
        preg_match_all('/\[VARIABLE([0-9]*)\]/', $this->query, $matches);

        $numbers = array_map(
            static fn (string $number): int => $number === '' ? 1 : (int) $number,
            $matches[1],
        );

        return $numbers === [] ? 0 : max($numbers);
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
            $var = $variables[$i] ?? '';

            $query = str_replace('[VARIABLE' . $i . ']', $var, $query);
            // backward compatibility
            if ($i !== 1) {
                continue;
            }

            $query = str_replace('[VARIABLE]', $var, $query);
        }

        return $query;
    }
}
