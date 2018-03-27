<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying the sql query form
 *
 * @usedby  server_sql.php
 * @usedby  db_sql.php
 * @usedby  tbl_sql.php
 * @usedby  tbl_structure.php
 * @usedby  tbl_tracking.php
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\SqlQueryForm class
 *
 * @package PhpMyAdmin
 */
class SqlQueryForm
{
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
     * @usedby  server_sql.php
     * @usedby  db_sql.php
     * @usedby  tbl_sql.php
     * @usedby  tbl_structure.php
     * @usedby  tbl_tracking.php
     */
    public static function getHtml(
        $query = true, $display_tab = false, $delimiter = ';'
    ) {
        $html = '';
        if (! $display_tab) {
            $display_tab = 'full';
        }
        // query to show
        if (true === $query) {
            $query = $GLOBALS['sql_query'];
        }

        // set enctype to multipart for file uploads
        if ($GLOBALS['is_upload']) {
            $enctype = ' enctype="multipart/form-data"';
        } else {
            $enctype = '';
        }

        $table  = '';
        $db     = '';
        if (strlen($GLOBALS['db']) === 0) {
            // prepare for server related
            $goto   = empty($GLOBALS['goto']) ?
                        'server_sql.php' : $GLOBALS['goto'];
        } elseif (strlen($GLOBALS['table']) === 0) {
            // prepare for db related
            $db     = $GLOBALS['db'];
            $goto   = empty($GLOBALS['goto']) ?
                        'db_sql.php' : $GLOBALS['goto'];
        } else {
            $table  = $GLOBALS['table'];
            $db     = $GLOBALS['db'];
            $goto   = empty($GLOBALS['goto']) ?
                        'tbl_sql.php' : $GLOBALS['goto'];
        }

        // start output
        $html .= '<form method="post" action="import.php" ' . $enctype;
        $html .= ' class="ajax lock-page"';
        $html .= ' id="sqlqueryform" name="sqlform">' . "\n";

        $html .= '<input type="hidden" name="is_js_confirmed" value="0" />'
            . "\n" . Url::getHiddenInputs($db, $table) . "\n"
            . '<input type="hidden" name="pos" value="0" />' . "\n"
            . '<input type="hidden" name="goto" value="'
            . htmlspecialchars($goto) . '" />' . "\n"
            . '<input type="hidden" name="message_to_show" value="'
            . __('Your SQL query has been executed successfully.') . '" />'
            . "\n" . '<input type="hidden" name="prev_sql_query" value="'
            . htmlspecialchars($query) . '" />' . "\n";

        // display querybox
        if ($display_tab === 'full' || $display_tab === 'sql') {
            $html .= self::getHtmlForInsert(
                $query, $delimiter
            );
        }

        // Bookmark Support
        if ($display_tab === 'full') {
            $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
            if ($cfgBookmark) {
                $html .= self::getHtmlForBookmark();
            }
        }

        // Japanese encoding setting
        if (Encoding::canConvertKanji()) {
            $html .= Encoding::kanjiEncodingForm();
        }

        $html .= '</form>' . "\n";
        // print an empty div, which will be later filled with
        // the sql query results by ajax
        $html .= '<div id="sqlqueryresultsouter"></div>';

        return $html;
    }

    /**
     * Get initial values for Sql Query Form Insert
     *
     * @param string $query query to display in the textarea
     *
     * @return array ($legend, $query, $columns_list)
     *
     * @usedby  self::getHtmlForInsert()
     */
    public static function init($query)
    {
        $columns_list    = array();
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
            $tmp_db_link = '<a href="' . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
            )
                . Url::getCommon(array('db' => $db)) . '"';
            $tmp_db_link .= '>'
                . htmlspecialchars($db) . '</a>';
            $legend = sprintf(__('Run SQL query/queries on database %s'), $tmp_db_link);
            if (empty($query)) {
                $query = Util::expandUserString(
                    $GLOBALS['cfg']['DefaultQueryDatabase'], 'backquote'
                );
            }
        } else {
            $db     = $GLOBALS['db'];
            $table  = $GLOBALS['table'];
            // Get the list and number of fields
            // we do a try_query here, because we could be in the query window,
            // trying to synchronize and the table has not yet been created
            $columns_list = $GLOBALS['dbi']->getColumns(
                $db, $GLOBALS['table'], null, true
            );

            $tmp_tbl_link = '<a href="' . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'], 'table'
            ) . Url::getCommon(array('db' => $db, 'table' => $table)) . '" >';
            $tmp_tbl_link .= htmlspecialchars($db)
                . '.' . htmlspecialchars($table) . '</a>';
            $legend = sprintf(__('Run SQL query/queries on table %s'), $tmp_tbl_link);
            if (empty($query)) {
                $query = Util::expandUserString(
                    $GLOBALS['cfg']['DefaultQueryTable'], 'backquote'
                );
            }
        }
        $legend .= ': ' . Util::showMySQLDocu('SELECT');

        return array($legend, $query, $columns_list);
    }

    /**
     * return HTML for Sql Query Form Insert
     *
     * @param string $query     query to display in the textarea
     * @param string $delimiter default delimiter to use
     *
     * @return string
     *
     * @usedby  self::getHtml()
     */
    public static function getHtmlForInsert(
        $query = '', $delimiter = ';'
    ) {
        // enable auto select text in textarea
        if ($GLOBALS['cfg']['TextareaAutoSelect']) {
            $auto_sel = ' onclick="selectContent(this, sql_box_locked, true);"';
        } else {
            $auto_sel = '';
        }

        $locking = '';
        $height = $GLOBALS['cfg']['TextareaRows'] * 2;

        list($legend, $query, $columns_list) = self::init($query);

        if (! empty($columns_list)) {
            $sqlquerycontainer_id = 'sqlquerycontainer';
        } else {
            $sqlquerycontainer_id = 'sqlquerycontainerfull';
        }

        $html = '<a id="querybox"></a>'
            . '<div id="queryboxcontainer">'
            . '<fieldset id="queryboxf">';
        $html .= '<legend>' . $legend . '</legend>';
        $html .= '<div id="queryfieldscontainer">';
        $html .= '<div id="' . $sqlquerycontainer_id . '">'
            . '<textarea tabindex="100" name="sql_query" id="sqlquery"'
            . '  cols="' . $GLOBALS['cfg']['TextareaCols'] . '"'
            . '  rows="' . $height . '"'
            . $auto_sel . $locking . '>'
            . htmlspecialchars($query)
            . '</textarea>';
        $html .= '<div id="querymessage"></div>';
        // Add buttons to generate query easily for
        // select all, single select, insert, update and delete
        if (! empty($columns_list)) {
            $html .= '<input type="button" value="SELECT *" id="selectall"'
                . ' class="button sqlbutton" />';
            $html .= '<input type="button" value="SELECT" id="select"'
                . ' class="button sqlbutton" />';
            $html .= '<input type="button" value="INSERT" id="insert"'
                . ' class="button sqlbutton" />';
            $html .= '<input type="button" value="UPDATE" id="update"'
                . ' class="button sqlbutton" />';
            $html .= '<input type="button" value="DELETE" id="delete"'
                . ' class="button sqlbutton" />';
        }
        $html .= '<input type="button" value="' . __('Clear') . '" id="clear"'
            . ' class="button sqlbutton" />';
        if ($GLOBALS['cfg']['CodemirrorEnable']) {
            $html .= '<input type="button" value="' . __('Format') . '" id="format"'
                . ' class="button sqlbutton" />';
        }
        $html .= '<input type="button" value="' . __('Get auto-saved query')
            . '" id="saved" class="button sqlbutton" />';

        // parameter binding
        $html .= '<div>';
        $html .= '<input type="checkbox" name="parameterized" id="parameterized" />';
        $html .= '<label for="parameterized">' . __('Bind parameters') . '</label>';
        $html .= Util::showDocu('faq', 'faq6-40');
        $html .= '<div id="parametersDiv"></div>';
        $html .= '</div>';

        $html .= '</div>' . "\n";

        if (! empty($columns_list)) {
            $html .= '<div id="tablefieldscontainer">'
                . '<label>' . __('Columns') . '</label>'
                . '<select id="tablefields" name="dummy" '
                . 'size="' . ($GLOBALS['cfg']['TextareaRows'] - 2) . '" '
                . 'multiple="multiple" ondblclick="insertValueQuery()">';
            foreach ($columns_list as $field) {
                $html .= '<option value="'
                    . Util::backquote(htmlspecialchars($field['Field']))
                    . '"';
                if (isset($field['Field'])
                    && strlen($field['Field']) > 0
                    && isset($field['Comment'])
                ) {
                    $html .= ' title="' . htmlspecialchars($field['Comment']) . '"';
                }
                $html .= '>' . htmlspecialchars($field['Field']) . '</option>' . "\n";
            }
            $html .= '</select>'
                . '<div id="tablefieldinsertbuttoncontainer">';
            if (Util::showIcons('ActionLinksMode')) {
                $html .= '<input type="button" class="button" name="insert"'
                    . ' value="&lt;&lt;" onclick="insertValueQuery()"'
                    . ' title="' . __('Insert') . '" />';
            } else {
                $html .= '<input type="button" class="button" name="insert"'
                    . ' value="' . __('Insert') . '"'
                    . ' onclick="insertValueQuery()" />';
            }
            $html .= '</div>' . "\n"
                . '</div>' . "\n";
        }

        $html .= '<div class="clearfloat"></div>' . "\n";
        $html .= '</div>' . "\n";

        $cfgBookmark = Bookmark::getParams($GLOBALS['cfg']['Server']['user']);
        if ($cfgBookmark) {
            $html .= '<div id="bookmarkoptions">';
            $html .= '<div class="formelement">';
            $html .= '<label for="bkm_label">'
                . __('Bookmark this SQL query:') . '</label>';
            $html .= '<input type="text" name="bkm_label" id="bkm_label"'
                . ' tabindex="110" value="" />';
            $html .= '</div>';
            $html .= '<div class="formelement">';
            $html .= '<input type="checkbox" name="bkm_all_users" tabindex="111"'
                . ' id="id_bkm_all_users" value="true" />';
            $html .= '<label for="id_bkm_all_users">'
                . __('Let every user access this bookmark') . '</label>';
            $html .= '</div>';
            $html .= '<div class="formelement">';
            $html .= '<input type="checkbox" name="bkm_replace" tabindex="112"'
                . ' id="id_bkm_replace" value="true" />';
            $html .= '<label for="id_bkm_replace">'
                . __('Replace existing bookmark of same name') . '</label>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '<div class="clearfloat"></div>' . "\n";
        $html .= '</fieldset>' . "\n"
            . '</div>' . "\n";

        $html .= '<fieldset id="queryboxfooter" class="tblFooters">' . "\n";
        $html .= '<div class="formelement">' . "\n";
        $html .= '</div>' . "\n";

        $html .= '<div class="formelement">';
        $html .= '<label for="id_sql_delimiter">[ ' . __('Delimiter')
            . '</label>' . "\n";
        $html .= '<input type="text" name="sql_delimiter" tabindex="131" size="3" '
            . 'value="' . $delimiter . '" '
            . 'id="id_sql_delimiter" /> ]';
        $html .= '</div>';

        $html .= '<div class="formelement">';
        $html .= '<input type="checkbox" name="show_query" value="1" '
            . 'id="checkbox_show_query" tabindex="132" checked="checked" />'
            . '<label for="checkbox_show_query">' . __('Show this query here again')
            . '</label>';
        $html .= '</div>';

        $html .= '<div class="formelement">';
        $html .= '<input type="checkbox" name="retain_query_box" value="1" '
            . 'id="retain_query_box" tabindex="133" '
            . ($GLOBALS['cfg']['RetainQueryBox'] === false
                ? '' : ' checked="checked"')
            . ' />'
            . '<label for="retain_query_box">' . __('Retain query box')
            . '</label>';
        $html .= '</div>';

        $html .= '<div class="formelement">';
        $html .= '<input type="checkbox" name="rollback_query" value="1" '
            . 'id="rollback_query" tabindex="134" />'
            . '<label for="rollback_query">' . __('Rollback when finished')
            . '</label>';
        $html .= '</div>';

        // Disable/Enable foreign key checks
        $html .= '<div class="formelement">';
        $html .= Util::getFKCheckbox();
        $html .= '</div>';

        $html .= '<input type="submit" id="button_submit_query" name="SQL"';

        $html .= ' tabindex="200" value="' . __('Go') . '" />' . "\n";
        $html .= '<div class="clearfloat"></div>' . "\n";
        $html .= '</fieldset>' . "\n";

        return $html;
    }

    /**
     * return HTML for sql Query Form Bookmark
     *
     * @return string|null
     *
     * @usedby  self::getHtml()
     */
    public static function getHtmlForBookmark()
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
            $html .= '<option value="' . htmlspecialchars($bookmark->getId()) . '"'
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
            . ' id="radio_bookmark_exe" checked="checked" />'
            . '<label for="radio_bookmark_exe">' . __('Submit')
            . '</label>' . "\n";
        $html .= '<input type="radio" name="action_bookmark" value="1"'
            . ' id="radio_bookmark_view" />'
            . '<label for="radio_bookmark_view">' . __('View only')
            . '</label>' . "\n";
        $html .= '<input type="radio" name="action_bookmark" value="2"'
            . ' id="radio_bookmark_del" />'
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
        $html .= '<input type="submit" name="SQL" id="button_submit_bookmark" value="'
            . __('Go') . '" />';
        $html .= '<div class="clearfloat"></div>' . "\n";
        $html .= '</fieldset>' . "\n";

        return $html;
    }
}
