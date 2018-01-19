<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the console of PMA's pages
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use PMA\libraries\Template;
use PMA\libraries\Bookmark;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Class used to output the console
 *
 * @package PhpMyAdmin
 */
class Console
{
    /**
     * Whether to display anything
     *
     * @access private
     * @var bool
     */
    private $_isEnabled;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        $this->_isEnabled = true;
    }

    /**
     * Whether we are servicing an ajax request.
     *
     * @access private
     * @var bool
     */
    private $_isAjax;

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     *
     * @return void
     */
    public function setAjax($isAjax)
    {
        $this->_isAjax = (boolean) $isAjax;
    }

    /**
     * Disables the rendering of the footer
     *
     * @return void
     */
    public function disable()
    {
        $this->_isEnabled = false;
    }

    /**
     * Renders the bookmark content
     *
     * @access public
     * @return string
     */
    public static function getBookmarkContent()
    {
        $cfgBookmark = Bookmark::getParams();
        if ($cfgBookmark) {
            $bookmarks = Bookmark::getList();
            $count_bookmarks = count($bookmarks);
            if ($count_bookmarks > 0) {
                $welcomeMessage = sprintf(
                    _ngettext(
                        'Showing %1$d bookmark (both private and shared)',
                        'Showing %1$d bookmarks (both private and shared)',
                        $count_bookmarks
                    ),
                    $count_bookmarks
                );
            } else {
                $welcomeMessage = __('No bookmarks');
            }
            unset($count_bookmarks, $private_message, $shared_message);
            return Template::get('console/bookmark_content')
                ->render(
                    array(
                        'welcomeMessage'    => $welcomeMessage,
                        'bookmarks'         => $bookmarks,
                    )
                );
        }
        return '';
    }

    /**
     * Returns the list of JS scripts required by console
     *
     * @return array list of scripts
     */
    public function getScripts()
    {
        return array('console.js');
    }

    /**
     * Renders the console
     *
     * @access public
     * @return string
     */
    public function getDisplay()
    {
        if ((! $this->_isAjax) && $this->_isEnabled) {
            $cfgBookmark = Bookmark::getParams();

            $image = Util::getImage('console.png', __('SQL Query Console'));
            $_sql_history = PMA_getHistory($GLOBALS['cfg']['Server']['user']);
            $bookmarkContent = static::getBookmarkContent();

            return Template::get('console/display')
                ->render(
                    array(
                        'cfgBookmark'       => $cfgBookmark,
                        'image'             => $image,
                        '_sql_history'      => $_sql_history,
                        'bookmarkContent'   => $bookmarkContent,
                    )
                );
        }
        return '';
    }

}
