<?php
/**
 * functions for displaying the sql query form
 *
 * @usedby  /server/sql
 * @usedby  /database/sql
 * @usedby  /table/sql
 * @usedby  /table/structure
 * @usedby  /table/tracking
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * PhpMyAdmin\SqlQueryForm class
 *
 * @package PhpMyAdmin
 */
class SqlQueryForm
{
    /**
     * @var Template
     */
    private $template;

    /**
     * @param Template $template Template object
     */
    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    /**
     * return HTML for the sql query boxes
     *
     * @param boolean|string $query       query to display in the textarea
     *                                    or true to display last executed
     * @param boolean|string $display_tab sql|full|false
     *                                    what part to display
     *                                    false if not inside querywindow
     * @param string         $delimiter   delimiter
     *
     * @return string
     *
     * @usedby  /server/sql
     * @usedby  /database/sql
     * @usedby  /table/sql
     * @usedby  /table/structure
     * @usedby  /table/tracking
     */
    public function getHtml(
        $query = true,
        $display_tab = false,
        $delimiter = ';'
    ) {
        if (! $display_tab) {
            $display_tab = 'full';
        }
        // query to show
        if (true === $query) {
            $query = $GLOBALS['sql_query'];
        }

        $table = '';
        $db = '';
        if (strlen($GLOBALS['db']) === 0) {
            // prepare for server related
            $goto = empty($GLOBALS['goto']) ? Url::getFromRoute('/server/sql') : $GLOBALS['goto'];
        } elseif (strlen($GLOBALS['table']) === 0) {
            // prepare for db related
            $db = $GLOBALS['db'];
            $goto = empty($GLOBALS['goto']) ? Url::getFromRoute('/database/sql') : $GLOBALS['goto'];
        } else {
            $table = $GLOBALS['table'];
            $db = $GLOBALS['db'];
            $goto = empty($GLOBALS['goto']) ? Url::getFromRoute('/table/sql') : $GLOBALS['goto'];
        }

        $insert = '';
        if ($display_tab === 'full' || $display_tab === 'sql') {
            $insert = $this->getHtmlForInsert(
                $query,
                $delimiter
            );
        }

        $bookmark = '';
        if ($display_tab === 'full') {
            $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
            if ($cfgBookmark) {
                $bookmark = $this->getHtmlForBookmark();
            }
        }

        return $this->template->render('sql/query/page', [
            'is_upload' => $GLOBALS['is_upload'],
            'db' => $db,
            'table' => $table,
            'goto' => $goto,
            'query' => $query,
            'display_tab' => $display_tab,
            'insert' => $insert,
            'bookmark' => $bookmark,
            'can_convert_kanji' => Encoding::canConvertKanji(),
        ]);
    }

    /**
     * Get initial values for Sql Query Form Insert
     *
     * @param string $query query to display in the textarea
     *
     * @return array ($legend, $query, $columns_list)
     */
    public function init($query)
    {
        $columns_list    = [];
        if (strlen($GLOBALS['db']) === 0) {
            // prepare for server related
            $legend = sprintf(
                __('Run SQL query/queries on server “%s”'),
                htmlspecialchars(
                    ! empty($GLOBALS['cfg']['Servers'][$GLOBALS['server']]['verbose'])
                    ? $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['verbose']
                    : $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['host']
                )
            );
        } elseif (strlen($GLOBALS['table']) === 0) {
            // prepare for db related
            $db     = $GLOBALS['db'];
            // if you want navigation:
            $scriptName = Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            );
            $tmp_db_link = '<a href="' . $scriptName . Url::getCommon(['db' => $db], strpos($scriptName, '?') === false ? '?' : '&') . '">';
            $tmp_db_link .= htmlspecialchars($db) . '</a>';
            $legend = sprintf(__('Run SQL query/queries on database %s'), $tmp_db_link);
            if (empty($query)) {
                $query = Util::expandUserString(
                    $GLOBALS['cfg']['DefaultQueryDatabase'],
                    'backquote'
                );
            }
        } else {
            $db     = $GLOBALS['db'];
            $table  = $GLOBALS['table'];
            // Get the list and number of fields
            // we do a try_query here, because we could be in the query window,
            // trying to synchronize and the table has not yet been created
            $columns_list = $GLOBALS['dbi']->getColumns(
                $db,
                $GLOBALS['table'],
                null,
                true
            );

            $scriptName = Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'],
                'table'
            );
            $tmp_tbl_link = '<a href="' . $scriptName . Url::getCommon(['db' => $db, 'table' => $table], '&') . '">';
            $tmp_tbl_link .= htmlspecialchars($db) . '.' . htmlspecialchars($table) . '</a>';
            $legend = sprintf(__('Run SQL query/queries on table %s'), $tmp_tbl_link);
            if (empty($query)) {
                $query = Util::expandUserString(
                    $GLOBALS['cfg']['DefaultQueryTable'],
                    'backquote'
                );
            }
        }
        $legend .= ': ' . Util::showMySQLDocu('SELECT');

        return [
            $legend,
            $query,
            $columns_list,
        ];
    }

    /**
     * return HTML for Sql Query Form Insert
     *
     * @param string $query     query to display in the textarea
     * @param string $delimiter default delimiter to use
     *
     * @return string
     */
    public function getHtmlForInsert(
        $query = '',
        $delimiter = ';'
    ) {
        list($legend, $query, $columns_list) = $this->init($query);
        $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);

        return $this->template->render('sql/query/insert', [
            'legend' => $legend,
            'textarea_cols' => $GLOBALS['cfg']['TextareaCols'],
            'textarea_rows' => $GLOBALS['cfg']['TextareaRows'],
            'textarea_auto_select' => $GLOBALS['cfg']['TextareaAutoSelect'],
            'query' => $query,
            'columns_list' => $columns_list,
            'codemirror_enable' => $GLOBALS['cfg']['CodemirrorEnable'],
            'has_bookmark' => $cfgBookmark,
            'delimiter' => $delimiter,
            'retain_query_box' => $GLOBALS['cfg']['RetainQueryBox'] !== false,
        ]);
    }

    /**
     * return HTML for sql Query Form Bookmark
     *
     * @return string|null
     */
    public function getHtmlForBookmark()
    {
        $bookmark_list = Bookmark::getList(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
            $GLOBALS['db']
        );
        if (empty($bookmark_list) || count($bookmark_list) < 1) {
            return null;
        }

        $html  = '<fieldset id="fieldsetBookmarkOptions">';
        $html .= '<legend>';
        $html .= __('Bookmarked SQL query') . '</legend>' . "\n";
        $html .= '<div class="formelement">';
        $html .= '<select name="id_bookmark" id="id_bookmark">' . "\n";
        $html .= '<option value="">&nbsp;</option>' . "\n";
        foreach ($bookmark_list as $bookmark) {
            $html .= '<option value="' . htmlspecialchars((string) $bookmark->getId()) . '"'
                . ' data-varcount="' . $bookmark->getVariableCount()
                . '">'
                . htmlspecialchars($bookmark->getLabel())
                . (empty($bookmark->getUser()) ? (' (' . __('shared') . ')') : '')
                . '</option>' . "\n";
        }
        // &nbsp; is required for correct display with styles/line height
        $html .= '</select>&nbsp;' . "\n";
        $html .= '</div>' . "\n";
        $html .= '<div class="formelement">' . "\n";
        $html .= '<input type="radio" name="action_bookmark" value="0"'
            . ' id="radio_bookmark_exe" checked="checked">'
            . '<label for="radio_bookmark_exe">' . __('Submit')
            . '</label>' . "\n";
        $html .= '<input type="radio" name="action_bookmark" value="1"'
            . ' id="radio_bookmark_view">'
            . '<label for="radio_bookmark_view">' . __('View only')
            . '</label>' . "\n";
        $html .= '<input type="radio" name="action_bookmark" value="2"'
            . ' id="radio_bookmark_del">'
            . '<label for="radio_bookmark_del">' . __('Delete')
            . '</label>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '<div class="clearfloat"></div>' . "\n";
        $html .= '<div class="formelement hide">' . "\n";
        $html .= __('Variables');
        $html .= Util::showDocu('faq', 'faqbookmark');
        $html .= '<div id="bookmark_variables"></div>';
        $html .= '</div>' . "\n";
        $html .= '</fieldset>' . "\n";

        $html .= '<fieldset id="fieldsetBookmarkOptionsFooter" class="tblFooters">';
        $html .= '<input class="btn btn-primary" type="submit" name="SQL" id="button_submit_bookmark" value="'
            . __('Go') . '">';
        $html .= '<div class="clearfloat"></div>' . "\n";
        $html .= '</fieldset>' . "\n";

        return $html;
    }
}
