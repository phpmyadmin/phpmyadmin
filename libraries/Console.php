<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the console of PMA's pages
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/bookmark.lib.php';

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
     * We can't simply use $GLOBALS['is_ajax_request']
     * here since it may have not been initialised yet.
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
        $output = '';
        $cfgBookmark = PMA_Bookmark_getParams();
        if ($cfgBookmark) {

            $tpl_bookmark_actions
                = '<span class="action collapse">' . __('Collapse') . '</span> '
                . '<span class="action expand">' . __('Expand') . '</span> '
                . '<span class="action requery">' . __('Requery') . '</span> '
                . '<span class="action edit_bookmark">' . __('Edit') . '</span> '
                .  '<span class="action delete_bookmark">' . __('Delete')
                . '</span> '
                . '<span class="text targetdb">' . __('Database')
                . ': <span>%s</span></span>';

            $bookmarks = PMA_Bookmark_getList();
            $output .= '<div class="message welcome"><span>';
            $count_bookmarks = count($bookmarks);
            if ($count_bookmarks > 0) {
                $output .= sprintf(
                    _ngettext(
                        'Showing %1$d bookmark (both private and shared)',
                        'Showing %1$d bookmarks (both private and shared)',
                        $count_bookmarks
                    ),
                    $count_bookmarks
                );
            } else {
                $output .= __('No bookmarks');
            }
            unset($count_bookmarks, $private_message, $shared_message);
            $output .= '</span></div>';
            foreach ($bookmarks as $val) {
                $output .= '<div class="message collapsed bookmark" bookmarkid="'
                .  $val['id'] . '" targetdb="' . htmlspecialchars($val['db'])
                .  '"><div class="action_content">'
                .  sprintf($tpl_bookmark_actions, htmlspecialchars($val['db']))
                .  '</div><span class="bookmark_label '
                . ($val['shared'] ? 'shared' : '') . '">'
                .  htmlspecialchars($val['label'])
                .  '</span> <span class="query">'
                .  htmlspecialchars($val['query'])
                .  '</span></div>';
            }
        }
        return $output;
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
     * Gets the history
     *
     * @param string $tpl_query_actions the template for query actions
     *
     * @return string $output the generated HTML for history
     *
     * @access  private
     *
     */
    private function _getHistory($tpl_query_actions)
    {
        $output = '';

        $_sql_history = PMA_getHistory($GLOBALS['cfg']['Server']['user']);
        if (! empty($_sql_history)) {
            foreach (array_reverse($_sql_history) as $record) {
                $isSelect = preg_match(
                    '@^SELECT[[:space:]]+@i', $record['sqlquery']
                );
                $output .= '<div class="message history collapsed hide'
                        . ($isSelect ? ' select' : '')
                        . '" targetdb="'
                        . htmlspecialchars($record['db'])
                        . '" targettable="' . htmlspecialchars($record['table'])
                        . '"><div class="action_content">'
                        . sprintf(
                            $tpl_query_actions,
                            htmlspecialchars($record['db']),
                            (isset($record['timevalue'])
                                ? $record['timevalue']
                                : __('During current session')
                            )
                        )
                        . '</div><span class="query">'
                        . htmlspecialchars($record['sqlquery'])
                        . '</span></div>';
            }
        }
        return $output;
    }

    /**
     * Renders the console
     *
     * @access public
     * @return string
     */
    public function getDisplay()
    {
        $output  = '';
        if ((! $this->_isAjax) && $this->_isEnabled) {
            $cfgBookmark = PMA_Bookmark_getParams();
            $output .= '<div id="pma_console_container"><div id="pma_console">';

            // The templates, use sprintf() to output them
            // There're white space at the end of every <span>,
            // for double-click selection
            $tpl_query_actions = '<span class="action collapse">' . __('Collapse')
                . '</span> '
                . '<span class="action expand">' . __('Expand') . '</span> '
                . '<span class="action requery">' . __('Requery') . '</span> '
                . '<span class="action edit">' . __('Edit') . '</span> '
                . '<span class="action explain">' . __('Explain') . '</span> '
                . '<span class="action profiling">' . __('Profiling') . '</span> '
                . ($cfgBookmark ? '<span class="action bookmark">'
                . __('Bookmark') . '</span> ' : '')
                . '<span class="text failed">' . __('Query failed') . '</span> '
                . '<span class="text targetdb">' . __('Database')
                . ': <span>%s</span></span> '
                . '<span class="text query_time">' . __(
                    'Queried time'
                ) . ': <span>%s</span></span> ';

            // Console toolbar
            $output .= '<div class="toolbar collapsed">';

            $output .= '<div class="switch_button console_switch">';
            $output .= Util::getImage('console.png', __('SQL Query Console'));
            $output .= '<span>' . __('Console') . '</span></div>';

            $output .= '<div class="button clear"><span>'
                    . __('Clear') . '</span></div>';

            $output .= '<div class="button history"><span>'
                    . __('History') . '</span></div>';

            $output .= '<div class="button options"><span>'
                    . __('Options') . '</span></div>';

            if ($cfgBookmark) {
                $output .= '<div class="button bookmarks"><span>'
                        . __('Bookmarks') . '</span></div>';
            }

            $output .= '<div class="button debug hide"><span>'
                    . __('Debug SQL') . '</span></div>';

            $output .= '</div>'; // Toolbar end

            // Console messages
            $output .= '<div class="content">';
            $output .= '<div class="console_message_container">'
                    .  '<div class="message welcome"><span>'
                    .  '<span id="instructions-0">'
                    .  __('Press Ctrl+Enter to execute query') . '</span>'
                    .  '<span class="hide" id="instructions-1">'
                    .  __('Press Enter to execute query') . '</span>'
                    .  '</span></div>';

            $output .= $this->_getHistory($tpl_query_actions);

            $output .= '</div>'; // .console_message_container
            $output .= '<div class="query_input">'
                    . '<span class="console_query_input"></span>'
                    . '</div>';
            $output .= '</div>'; // Messages end

            // Dark the console while other cards cover it
            $output .= '<div class="mid_layer"></div>';

            // Debug SQL card
            $output .= '<div class="card" id="debug_console">';
            $output .= '<div class="toolbar">'
                . '<div class="button order order_asc">'
                . '<span>' . __('ascending') . '</span>'
                . '</div>'
                . '<div class="button order order_desc">'
                . '<span>' . __('descending') . '</span>'
                . '</div>'
                . '<div class="text">'
                . '<span>' . __('Order:') . '</span>'
                . '</div>'
                . '<div class="switch_button">'
                . '<span>' . __('Debug SQL') . '</span>'
                . '</div>'
                . '<div class="button order_by sort_count">'
                . '<span>' . __('Count') . '</span>'
                . '</div>'
                . '<div class="button order_by sort_exec">'
                . '<span>' . __('Execution order') . '</span>'
                . '</div>'
                . '<div class="button order_by sort_time">'
                . '<span>' . __('Time taken') . '</span>'
                . '</div>'
                . '<div class="text">'
                . '<span>' . __('Order by:') . '</span>'
                . '</div>'
                . '<div class="button group_queries">'
                . '<span>' . __('Group queries') . '</span>'
                . '</div>'
                . '<div class="button ungroup_queries">'
                . '<span>' . __('Ungroup queries') . '</span>'
                . '</div>'
                . '</div>'; // Toolbar
            $output .= '<div class="content debug">';
            $output .= '<div class="message welcome"></div>';
            $output .= '<div class="debugLog"></div>';
            $output .= '</div>'; // Content
            $output .= '<div class="templates">'
                . '<div class="debug_query action_content">'
                . '<span class="action collapse">' . __('Collapse') . '</span> '
                . '<span class="action expand">' . __('Expand') . '</span> '
                . '<span class="action dbg_show_trace">' . __('Show trace')
                . '</span> '
                . '<span class="action dbg_hide_trace">' . __('Hide trace')
                . '</span> '
                . '<span class="text count hide">' . __('Count:')
                . ' <span></span></span>'
                . '<span class="text time">' . __('Time taken:')
                . ' <span></span></span>'
                . '</div>'
                . '</div>'; // Template
            $output .= '</div>'; // Debug SQL card

            // Bookmarks card:

            if ($cfgBookmark) {
                $output .= '<div class="card" id="pma_bookmarks">';
                $output .= '<div class="toolbar">'
                        .  '<div class="switch_button"><span>' . __('Bookmarks')
                        .  '</span></div>';

                $output .= '<div class="button refresh"><span>'
                        . __('Refresh') . '</span></div>';

                $output .= '<div class="button add"><span>'
                        . __('Add') . '</span></div>';

                $output .= '</div><div class="content bookmark">';
                $output .= static::getBookmarkContent();
                $output .= '</div>';
                    $output .= '<div class="mid_layer"></div>';
                    $output .= '<div class="card add">';
                    $output .= '<div class="toolbar">'
                            . '<div class="switch_button"><span>'
                            . __('Add bookmark')
                            . '</span></div>';
                    $output .= '</div><div class="content add_bookmark">'
                            . '<div class="options">'
                            . '<label>' . __('Label')
                            . ': <input type="text" name="label"></label> '
                            . '<label>' . __('Target database')
                            . ': <input type="text" name="targetdb"></label> '
                            . '<label><input type="checkbox" name="shared">'
                            . __('Share this bookmark') . '</label>'
                            . '<button type="submit" name="submit">Ok</button>'
                            . '</div>' // .options
                            . '<div class="query_input">'
                            . '<span class="bookmark_add_input"></span></div>';
                    $output .= '</div>';
                    $output .= '</div>'; // Add bookmark card
                $output .= '</div>'; // Bookmarks card
            }

            // Options card:
            $output .= '<div class="card" id="pma_console_options">';
            $output .= '<div class="toolbar">'
                    .  '<div class="switch_button"><span>' . __('Options')
                    . '</span></div>';

            $output .= '<div class="button default"><span>'
                    . __('Set default') . '</span></div>';

            $output .= '</div><div class="content">'
                    .  '<label><input type="checkbox" name="always_expand">'
                    .  __('Always expand query messages') . '</label><br>'
                    .  '<label><input type="checkbox" name="start_history">'
                    .  __('Show query history at start') . '</label><br>'
                    .  '<label><input type="checkbox" name="current_query">'
                    .  __('Show current browsing query') . '</label><br>'
                    .  '<label><input type="checkbox" name="enter_executes">'
                    .  __(
                        'Execute queries on Enter and insert new line with Shift + '
                        . 'Enter. To make this permanent, view settings.'
                    ) . '</label><br>'
                    .  '<label><input type="checkbox" name="dark_theme">'
                    .  __('Switch to dark theme') . '</label><br>'
                    .  '</div>';
            $output .= '</div>'; // Options card

            $output .= '<div class="templates">'
            // Templates for console message actions
                    .  '<div class="query_actions">'
                    .  sprintf($tpl_query_actions, '', '')
                    .  '</div>'
                    .  '</div>';
            $output .= '</div></div>'; // #console and #pma_console_container ends
        }
        return $output;
    }

}
