<?php
/**
 * Used to render the console of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;

use function __;
use function _ngettext;
use function count;
use function sprintf;

/**
 * Class used to output the console
 */
class Console
{
    /**
     * Whether to display anything
     */
    private bool $isEnabled = true;

    /**
     * Whether we are servicing an ajax request.
     */
    private bool $isAjax = false;

    public function __construct(private Relation $relation, public Template $template)
    {
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Disables the rendering of the footer
     */
    public function disable(): void
    {
        $this->isEnabled = false;
    }

    /**
     * Renders the bookmark content
     */
    public static function getBookmarkContent(): string
    {
        $template = new Template();
        $relation = new Relation($GLOBALS['dbi']);
        $bookmarkFeature = $relation->getRelationParameters()->bookmarkFeature;
        if ($bookmarkFeature === null) {
            return '';
        }

        $bookmarks = Bookmark::getList($bookmarkFeature, $GLOBALS['dbi'], $GLOBALS['cfg']['Server']['user']);
        $countBookmarks = count($bookmarks);
        if ($countBookmarks > 0) {
            $welcomeMessage = sprintf(
                _ngettext(
                    'Showing %1$d bookmark (both private and shared)',
                    'Showing %1$d bookmarks (both private and shared)',
                    $countBookmarks,
                ),
                $countBookmarks,
            );
        } else {
            $welcomeMessage = __('No bookmarks');
        }

        return $template->render('console/bookmark_content', [
            'welcome_message' => $welcomeMessage,
            'bookmarks' => $bookmarks,
        ]);
    }

    /**
     * Returns the list of JS scripts required by console
     *
     * @return string[] list of scripts
     */
    public function getScripts(): array
    {
        return ['console.js'];
    }

    /**
     * Renders the console
     */
    public function getDisplay(): string
    {
        if ($this->isAjax || ! $this->isEnabled) {
            return '';
        }

        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        $sqlHistory = $this->relation->getHistory($GLOBALS['cfg']['Server']['user']);
        $bookmarkContent = static::getBookmarkContent();

        return $this->template->render('console/display', [
            'has_bookmark_feature' => $bookmarkFeature !== null,
            'sql_history' => $sqlHistory,
            'bookmark_content' => $bookmarkContent,
        ]);
    }
}
