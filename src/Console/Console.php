<?php
/**
 * Used to render the console of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin\Console;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Template;

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

    private readonly Config $config;

    public function __construct(
        private readonly Relation $relation,
        private readonly Template $template,
        private readonly BookmarkRepository $bookmarkRepository,
        private readonly History $history,
    ) {
        $this->config = Config::getInstance();
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
    public function getBookmarkContent(): string
    {
        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        if ($bookmarkFeature === null) {
            return '';
        }

        $bookmarks = $this->bookmarkRepository->getList($this->config->selectedServer['user']);
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

        return $this->template->render('console/bookmark_content', [
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
        if (! $this->isEnabled) {
            return '';
        }

        $bookmarkFeature = $this->relation->getRelationParameters()->bookmarkFeature;
        $sqlHistory = $this->history->getHistory($this->config->selectedServer['user']);
        $bookmarkContent = $this->getBookmarkContent();

        return $this->template->render('console/display', [
            'settings' => $this->config->config->Console->asArray(),
            'has_bookmark_feature' => $bookmarkFeature !== null,
            'sql_history' => $sqlHistory,
            'bookmark_content' => $bookmarkContent,
            'debug' => $this->config->config->debug->sql,
        ]);
    }
}
