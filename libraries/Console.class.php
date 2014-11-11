<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the console of PMA's pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Scripts.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/bookmark.lib.php';

/**
 * Class used to output the console
 *
 * @package PhpMyAdmin
 */
class PMA_Console
{
    /**
     * PMA_Scripts instance
     *
     * @access private
     * @var PMA_Scripts
     */
    private $_scripts;
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
        $this->_scripts   = new PMA_Scripts();
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
        $this->_isAjax = ($isAjax == true);
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
                $bookmarks_message = sprintf(
                    _ngettext(
                        'Total %d bookmark',
                        'Total %d bookmarks',
                        $count_bookmarks
                    ),
                    $count_bookmarks
                );
                $private_message = sprintf(
                    '<span class="bookmark_label">%1$s</span>',
                    __('private')
                );
                $shared_message = sprintf(
                    '<span class="bookmark_label shared">%1$s</span>',
                    __('shared')
                );
                $output .= sprintf(
                    /* l10n: First parameter will be replaced with the translation for Total and the number of bookmarks, second one with the translation for private and the third one, with the translation for shared */
                    __('%1$s, %2$s and %3$s bookmarks included'),
                    $bookmarks_message,
                    $private_message,
                    $shared_message
                );
            } else {
                $output .= __('No bookmarks');
            }
            unset($count_bookmarks, $private_message, $shared_message);
            $output .= '</span></div>';
            foreach ($bookmarks as $key => $val) {
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
            if ($GLOBALS['cfg']['CodemirrorEnable']) {
                $this->_scripts->addFile('codemirror/lib/codemirror.js');
                $this->_scripts->addFile('codemirror/mode/sql/sql.js');
                $this->_scripts->addFile('codemirror/addon/runmode/runmode.js');
                $this->_scripts->addFile('codemirror/addon/hint/show-hint.js');
                $this->_scripts->addFile('codemirror/addon/hint/sql-hint.js');
            }
            $this->_scripts->addFile('console.js');
            $output .= $this->_scripts->getDisplay();
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
            $output .= PMA_Util::getImage('console.png', __('SQL Query Console'));
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

            $output .= '</div>'; // Toolbar end

            // Console messages
            $output .= '<div class="content">';
            $output .= '<div class="console_message_container">'
                    .  '<div class="message welcome"><span>'
                    .  __('Press Ctrl+Enter to execute query')
                    .  '</span></div>';

            // History support
            $_sql_history = PMA_getHistory($GLOBALS['cfg']['Server']['user']);
            if ($_sql_history) {
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

            $output .= '</div>'; // .console_message_container
            $output .= '<div class="query_input">'
                    . '<span class="console_query_input"></span>'
                    . '</div>';
            $output .= '</div>'; // Messages end

            // Dark the console while other cards cover it
            $output .= '<div class="mid_layer"></div>';

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
                $output .= $this->getBookmarkContent();
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
