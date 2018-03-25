<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the console of PMA's pages
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

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
     * Whether we are servicing an ajax request.
     *
     * @access private
     * @var bool
     */
    private $_isAjax;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        $this->_isEnabled = true;
        $this->relation = new Relation();
    }

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
        $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
        if ($cfgBookmark) {
            $bookmarks = Bookmark::getList(
                $GLOBALS['dbi'],
                $GLOBALS['cfg']['Server']['user']
            );
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
                        'welcome_message'    => $welcomeMessage,
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
            $cfgBookmark = Bookmark::getParams(
                $GLOBALS['cfg']['Server']['user']
            );

            $image = Util::getImage('console', __('SQL Query Console'));
            $_sql_history = $this->relation->getHistory(
                $GLOBALS['cfg']['Server']['user']
            );
            $bookmarkContent = static::getBookmarkContent();

            return Template::get('console/display')->render([
                'cfg_bookmark' => $cfgBookmark,
                'image' => $image,
                'sql_history' => $_sql_history,
                'bookmark_content' => $bookmarkContent,
            ]);
        }
        return '';
    }
}
