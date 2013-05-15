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
 * @usedby  querywindow.php
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/file_listing.lib.php'; // used for file listing
require_once './libraries/bookmark.lib.php'; // used for bookmarks

/**
 * Prints the sql query boxes
 *
 * @param boolean|string $query       query to display in the textarea
 *                                    or true to display last executed
 * @param boolean|string $display_tab sql|files|history|full|false
 *                                    what part to display
 *                                    false if not inside querywindow
 * @param string         $delimiter   delimeter
 *
 * @return void
 *
 * @usedby  server_sql.php
 * @usedby  db_sql.php
 * @usedby  tbl_sql.php
 * @usedby  tbl_structure.php
 * @usedby  tbl_tracking.php
 * @usedby  querywindow.php
 */
function PMA_sqlQueryForm($query = true, $display_tab = false, $delimiter = ';')
{
    // check tab to display if inside querywindow
    if (! $display_tab) {
        $display_tab = 'full';
        $is_querywindow = false;
    } else {
        $is_querywindow = true;
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
    if (! strlen($GLOBALS['db'])) {
        // prepare for server related
        $goto   = empty($GLOBALS['goto']) ?
                    'server_sql.php' : $GLOBALS['goto'];
    } elseif (! strlen($GLOBALS['table'])) {
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
    if ($is_querywindow) {
        ?>
        <form method="post" id="sqlqueryform" target="frame_content"
              action="import.php"<?php echo $enctype; ?> name="sqlform"
              onsubmit="var save_name = window.opener.parent.frame_content.name;
              window.opener.parent.frame_content.name = save_name + '<?php echo time(); ?>';
              this.target = window.opener.parent.frame_content.name;
              return checkSqlQuery(this)">
        <?php
    } else {
        echo '<form method="post" action="import.php" ' . $enctype;
        echo ' class="ajax"';
        echo ' id="sqlqueryform" name="sqlform">' . "\n";
    }

    if ($is_querywindow) {
        echo '<input type="hidden" name="focus_querywindow" value="true" />'
            ."\n";
        if ($display_tab != 'sql' && $display_tab != 'full') {
            echo '<input type="hidden" name="sql_query" value="" />' . "\n";
            echo '<input type="hidden" name="show_query" value="1" />' . "\n";
        }
    }
    echo '<input type="hidden" name="is_js_confirmed" value="0" />' . "\n"
        .PMA_generate_common_hidden_inputs($db, $table) . "\n"
        .'<input type="hidden" name="pos" value="0" />' . "\n"
        .'<input type="hidden" name="goto" value="'
        .htmlspecialchars($goto) . '" />' . "\n"
        .'<input type="hidden" name="message_to_show" value="'
        . __('Your SQL query has been executed successfully') . '" />' . "\n"
        .'<input type="hidden" name="prev_sql_query" value="'
        . htmlspecialchars($query) . '" />' . "\n";

    // display querybox
    if ($display_tab === 'full' || $display_tab === 'sql') {
        PMA_sqlQueryFormInsert($query, $is_querywindow, $delimiter);
    }

    // display uploads
    if ($display_tab === 'files' && $GLOBALS['is_upload']) {
        PMA_sqlQueryFormUpload();
    }

    // Bookmark Support
    if ($display_tab === 'full' || $display_tab === 'history') {
        if (! empty($GLOBALS['cfg']['Bookmark'])) {
            PMA_sqlQueryFormBookmark();
        }
    }

    // Encoding setting form appended by Y.Kawada
    if (function_exists('PMA_set_enc_form')) {
        echo PMA_set_enc_form('    ');
    }

    echo '</form>' . "\n";

    // print an empty div, which will be later filled with
    // the sql query results by ajax
    echo '<div id="sqlqueryresults"></div>';
}

/**
 * Prints querybox fieldset
 *
 * @param string  $query          query to display in the textarea
 * @param boolean $is_querywindow if inside querywindow or not
 * @param string  $delimiter      default delimiter to use
 *
 * @return void
 *
 * @usedby  PMA_sqlQueryForm()
 */
function PMA_sqlQueryFormInsert(
    $query = '', $is_querywindow = false, $delimiter = ';'
) {
    // enable auto select text in textarea
    if ($GLOBALS['cfg']['TextareaAutoSelect']) {
        $auto_sel = ' onclick="selectContent(this, sql_box_locked, true)"';
    } else {
        $auto_sel = '';
    }

    // enable locking if inside query window
    if ($is_querywindow) {
        $locking = ' onkeypress="document.sqlform.elements[\'LockFromUpdate\'].'
            .'checked = true;"';
        $height = $GLOBALS['cfg']['TextareaRows'] * 1.25;
    } else {
        $locking = '';
        $height = $GLOBALS['cfg']['TextareaRows'] * 2;
    }

    $table          = '';
    $db             = '';
    $fields_list    = array();
    if (! strlen($GLOBALS['db'])) {
        // prepare for server related
        $legend = sprintf(
            __('Run SQL query/queries on server %s'),
            '&quot;' . htmlspecialchars(
                ! empty($GLOBALS['cfg']['Servers'][$GLOBALS['server']]['verbose'])
                ? $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['verbose']
                : $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['host']
            ) . '&quot;'
        );
    } elseif (! strlen($GLOBALS['table'])) {
        // prepare for db related
        $db     = $GLOBALS['db'];
        // if you want navigation:
        $tmp_db_link = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase']
            . '?' . PMA_generate_common_url($db) . '"';
        if ($is_querywindow) {
            $tmp_db_link .= ' target="_self"'
                . ' onclick="this.target=window.opener.frame_content.name"';
        }
        $tmp_db_link .= '>'
            . htmlspecialchars($db) . '</a>';
        // else use
        // $tmp_db_link = htmlspecialchars($db);
        $legend = sprintf(__('Run SQL query/queries on database %s'), $tmp_db_link);
        if (empty($query)) {
            $query = PMA_Util::expandUserString(
                $GLOBALS['cfg']['DefaultQueryDatabase'], 'backquote'
            );
        }
    } else {
        $table  = $GLOBALS['table'];
        $db     = $GLOBALS['db'];
        // Get the list and number of fields
        // we do a try_query here, because we could be in the query window,
        // trying to synchonize and the table has not yet been created
        $fields_list = PMA_DBI_get_columns($db, $GLOBALS['table'], null, true);

        $tmp_db_link = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase']
            . '?' . PMA_generate_common_url($db) . '"';
        if ($is_querywindow) {
            $tmp_db_link .= ' target="_self"'
                . ' onclick="this.target=window.opener.frame_content.name"';
        }
        $tmp_db_link .= '>'
            . htmlspecialchars($db) . '</a>';
        // else use
        // $tmp_db_link = htmlspecialchars($db);
        $legend = sprintf(__('Run SQL query/queries on database %s'), $tmp_db_link);
        if (empty($query)) {
            $query = PMA_Util::expandUserString(
                $GLOBALS['cfg']['DefaultQueryTable'], 'backquote'
            );
        }
    }
    $legend .= ': ' . PMA_Util::showMySQLDocu('SQL-Syntax', 'SELECT');

    if (count($fields_list)) {
        $sqlquerycontainer_id = 'sqlquerycontainer';
    } else {
        $sqlquerycontainer_id = 'sqlquerycontainerfull';
    }

    echo '<a id="querybox"></a>' . "\n"
        .'<div id="queryboxcontainer">' . "\n"
        .'<fieldset id="queryboxf">' . "\n";
    echo '<legend>' . $legend . '</legend>' . "\n";
    echo '<div id="queryfieldscontainer">' . "\n";
    echo '<div id="' . $sqlquerycontainer_id . '">' . "\n"
        .'<textarea tabindex="100" name="sql_query" id="sqlquery"'
        .'  cols="' . $GLOBALS['cfg']['TextareaCols'] . '"'
        .'  rows="' . $height . '"'
        .'  dir="' . $GLOBALS['text_dir'] . '"'
        .$auto_sel . $locking . '>'
        . htmlspecialchars($query)
        . '</textarea>' . "\n";
    // Add buttons to generate query easily for
    // select all, single select, insert, update and delete
    if (count($fields_list)) {
        echo '<input type="button" value="SELECT *" id="selectall" class="button sqlbutton" />';
        echo '<input type="button" value="SELECT" id="select" class="button sqlbutton" />';
        echo '<input type="button" value="INSERT" id="insert" class="button sqlbutton" />';
        echo '<input type="button" value="UPDATE" id="update" class="button sqlbutton" />';
        echo '<input type="button" value="DELETE" id="delete" class="button sqlbutton" />';
    }
    echo '<input type="button" value="' . __('Clear') . '" id="clear" class="button sqlbutton" />';
    echo '</div>' . "\n";

    if (count($fields_list)) {
        echo '<div id="tablefieldscontainer">' . "\n"
            .'<label>' . __('Columns') . '</label>' . "\n"
            .'<select id="tablefields" name="dummy" '
            .'size="' . ($GLOBALS['cfg']['TextareaRows'] - 2) . '" '
            .'multiple="multiple" ondblclick="insertValueQuery()">' . "\n";
        foreach ($fields_list as $field) {
            echo '<option value="'
                .PMA_Util::backquote(htmlspecialchars($field['Field'])) . '"';
            if (isset($field['Field'])
                && strlen($field['Field'])
                && isset($field['Comment'])
            ) {
                echo ' title="' . htmlspecialchars($field['Comment']) . '"';
            }
            echo '>' . htmlspecialchars($field['Field']) . '</option>' . "\n";
        }
        echo '</select>' . "\n"
            .'<div id="tablefieldinsertbuttoncontainer">' . "\n";
        if ($GLOBALS['cfg']['PropertiesIconic']) {
            echo '<input type="button" class="button" name="insert" value="&lt;&lt;"'
                .' onclick="insertValueQuery()"'
                .' title="' . __('Insert') . '" />' . "\n";
        } else {
            echo '<input type="button" class="button" name="insert"'
                .' value="' . __('Insert') . '"'
                .' onclick="insertValueQuery()" />' . "\n";
        }
        echo '</div>' . "\n"
            .'</div>' . "\n";
    }

    echo '<div class="clearfloat"></div>' . "\n";
    echo '</div>' . "\n";

    if (! empty($GLOBALS['cfg']['Bookmark'])) {
        ?>
        <div id="bookmarkoptions">
        <div class="formelement">
        <label for="bkm_label">
            <?php echo __('Bookmark this SQL query'); ?>:</label>
        <input type="text" name="bkm_label" id="bkm_label" tabindex="110" value="" />
        </div>
        <div class="formelement">
        <input type="checkbox" name="bkm_all_users" tabindex="111" id="id_bkm_all_users" value="true" />
        <label for="id_bkm_all_users">
            <?php echo __('Let every user access this bookmark'); ?></label>
        </div>
        <div class="formelement">
        <input type="checkbox" name="bkm_replace" tabindex="112" id="id_bkm_replace"
            value="true" />
        <label for="id_bkm_replace">
            <?php echo __('Replace existing bookmark of same name'); ?></label>
        </div>
        </div>
        <?php
    }

    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>' . "\n"
        .'</div>' . "\n";

    echo '<fieldset id="queryboxfooter" class="tblFooters">' . "\n";
    echo '<div class="formelement">' . "\n";
    if ($is_querywindow) {
        ?>
        <script type="text/javascript">
        //<![CDATA[
            document.writeln(' <input type="checkbox" name="LockFromUpdate" checked="checked" tabindex="120" id="checkbox_lock" /> <label for="checkbox_lock"><?php echo __('Do not overwrite this query from outside the window'); ?></label> ');
        //]]>
        </script>
        <?php
    }
    echo '</div>' . "\n";
    echo '<div class="formelement">' . "\n";
    echo '<label for="id_sql_delimiter">[ ' . __('Delimiter')
        .'</label>' . "\n";
    echo '<input type="text" name="sql_delimiter" tabindex="131" size="3" '
        .'value="' . $delimiter . '" '
        .'id="id_sql_delimiter" /> ]' . "\n";

    echo '<input type="checkbox" name="show_query" value="1" '
        .'id="checkbox_show_query" tabindex="132" checked="checked" />' . "\n"
        .'<label for="checkbox_show_query">' . __('Show this query here again')
        .'</label>' . "\n";

    if (! $is_querywindow) {
        echo '<input type="checkbox" name="retain_query_box" value="1" '
            . 'id="retain_query_box" tabindex="133" '
            . ($GLOBALS['cfg']['RetainQueryBox'] === false
                ? '' : ' checked="checked"')
            . ' />'
            . '<label for="retain_query_box">' . __('Retain query box')
            . '</label>';
    }
    echo '</div>' . "\n";
    echo '<input type="submit" id="button_submit_query" name="SQL" tabindex="200" value="' . __('Go') . '" />'
        ."\n";
    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>' . "\n";
}

/**
 * Prints bookmark fieldset
 *
 * @return void
 *
 * @usedby  PMA_sqlQueryForm()
 */
function PMA_sqlQueryFormBookmark()
{
    $bookmark_list = PMA_Bookmark_getList($GLOBALS['db']);
    if (! $bookmark_list || count($bookmark_list) < 1) {
        return;
    }

    echo '<fieldset id="bookmarkoptions">';
    echo '<legend>';
    echo __('Bookmarked SQL query') . '</legend>' . "\n";
    echo '<div class="formelement">';
    echo '<select name="id_bookmark" id="id_bookmark">' . "\n";
    echo '<option value="">&nbsp;</option>' . "\n";
    foreach ($bookmark_list as $key => $value) {
        echo '<option value="' . htmlspecialchars($key) . '">'
            .htmlspecialchars($value) . '</option>' . "\n";
    }
    // &nbsp; is required for correct display with styles/line height
    echo '</select>&nbsp;' . "\n";
    echo '</div>' . "\n";
    echo '<div class="formelement">' . "\n";
    echo __('Variable');
    echo PMA_Util::showDocu('faq', 'faqbookmark');
    echo '<input type="text" name="bookmark_variable" class="textfield"'
        .' size="10" />' . "\n";
    echo '</div>' . "\n";
    echo '<div class="formelement">' . "\n";
    echo '<input type="radio" name="action_bookmark" value="0"'
        .' id="radio_bookmark_exe" checked="checked" />'
        .'<label for="radio_bookmark_exe">' . __('Submit')
        .'</label>' . "\n";
    echo '<input type="radio" name="action_bookmark" value="1"'
        .' id="radio_bookmark_view" />'
        .'<label for="radio_bookmark_view">' . __('View only')
        .'</label>' . "\n";
    echo '<input type="radio" name="action_bookmark" value="2"'
        .' id="radio_bookmark_del" />'
        .'<label for="radio_bookmark_del">' . __('Delete')
        .'</label>' . "\n";
    echo '</div>' . "\n";
    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>' . "\n";

    echo '<fieldset id="bookmarkoptionsfooter" class="tblFooters">' . "\n";
    echo '<input type="submit" name="SQL" id="button_submit_bookmark" value="' . __('Go') . '" />';
    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>' . "\n";
}

/**
 * Prints bookmark fieldset
 *
 * @return void
 *
 * @usedby  PMA_sqlQueryForm()
 */
function PMA_sqlQueryFormUpload()
{
    global $timeout_passed, $local_import_file;

    $errors = array();

    // we allow only SQL here
    $matcher = '@\.sql(\.(' . PMA_supportedDecompressions() . '))?$@';

    if (!empty($GLOBALS['cfg']['UploadDir'])) {
        $files = PMA_getFileSelectOptions(
            PMA_Util::userDir($GLOBALS['cfg']['UploadDir']), $matcher,
            (isset($timeout_passed) && $timeout_passed && isset($local_import_file))
            ? $local_import_file
            : ''
        );
    } else {
        $files = '';
    }

    // start output
    echo '<fieldset id="">';
    echo '<legend>';
    echo __('Browse your computer:') . '</legend>';
    echo '<div class="formelement">';
    echo '<input type="file" name="sql_file" class="textfield" /> ';
    echo PMA_Util::getFormattedMaximumUploadSize($GLOBALS['max_upload_size']);
    // some browsers should respect this :)
    echo PMA_Util::generateHiddenMaxFileSize($GLOBALS['max_upload_size']) . "\n";
    echo '</div>';

    if ($files === false) {
        $errors[] = PMA_Message::error(__('The directory you set for upload work cannot be reached'));
    } elseif (!empty($files)) {
        echo '<div class="formelement">';
        echo '<strong>' . __('web server upload directory') .':</strong>' . "\n";
        echo '<select size="1" name="sql_localfile">' . "\n";
        echo '<option value="" selected="selected"></option>' . "\n";
        echo $files;
        echo '</select>' . "\n";
        echo '</div>';
    }

    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>';


    echo '<fieldset id="" class="tblFooters">';
    echo __('Character set of the file:') . "\n";
    echo PMA_generateCharsetDropdownBox(
        PMA_CSDROPDOWN_CHARSET,
        'charset_of_file', null, 'utf8', false
    );
    echo '<input type="submit" name="SQL" value="' . __('Go')
        .'" />' . "\n";
    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>';

    foreach ($errors as $error) {
        $error->display();
    }
}
?>
