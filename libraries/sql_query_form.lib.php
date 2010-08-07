<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying the sql query form
 *
 * @version $Id$
 * @usedby  server_sql.php
 * @usedby  db_sql.php
 * @usedby  tbl_sql.php
 * @usedby  tbl_structure.php
 * @usedby  tbl_tracking.php
 * @usedby  querywindow.php
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/file_listing.php'; // used for file listing
require_once './libraries/bookmark.lib.php'; // used for file listing

/**
 * prints the sql query boxes
 *
 * @usedby  server_sql.php
 * @usedby  db_sql.php
 * @usedby  tbl_sql.php
 * @usedby  tbl_structure.php
 * @usedby  tbl_tracking.php
 * @usedby  querywindow.php
 * @uses    $GLOBALS['table']
 * @uses    $GLOBALS['db']
 * @uses    $GLOBALS['server']
 * @uses    $GLOBALS['goto']
 * @uses    $GLOBALS['is_upload']           from common.inc.php
 * @uses    $GLOBALS['sql_query']           from grab_globals.lib.php
 * @uses    $GLOBALS['cfg']['DefaultQueryTable']
 * @uses    $GLOBALS['cfg']['DefaultQueryDatabase']
 * @uses    $GLOBALS['cfg']['Servers']
 * @uses    $GLOBALS['cfg']['DefaultTabDatabase']
 * @uses    $GLOBALS['cfg']['DefaultQueryDatabase']
 * @uses    $GLOBALS['cfg']['DefaultQueryTable']
 * @uses    $GLOBALS['cfg']['Bookmark']
 * @uses    $GLOBALS['strSuccess']
 * @uses    PMA_generate_common_url()
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_fetch_result()
 * @uses    PMA_showMySQLDocu()
 * @uses    PMA_generate_common_hidden_inputs()
 * @uses    PMA_sqlQueryFormBookmark()
 * @uses    PMA_sqlQueryFormInsert()
 * @uses    PMA_sqlQueryFormUpload()
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_set_enc_form()
 * @uses    sprintf()
 * @uses    htmlspecialchars()
 * @uses    str_replace()
 * @uses    md5()
 * @uses    function_exists()
 * @param   boolean|string  $query          query to display in the textarea
 *                                          or true to display last executed
 * @param   boolean|string  $display_tab    sql|files|history|full|FALSE
 *                                          what part to display
 *                                          false if not inside querywindow
 * @param   string          $delimiter
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
        echo '<form method="post" action="import.php" ' . $enctype . ' id="sqlqueryform"'
            .' onsubmit="return checkSqlQuery(this)" name="sqlform">' . "\n";
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
        .'<input type="hidden" name="zero_rows" value="'
        . htmlspecialchars($GLOBALS['strSuccess']) . '" />' . "\n"
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
    if ($is_querywindow) {
        ?>
        <script type="text/javascript">
        //<![CDATA[
            if (window.opener) {
                window.opener.parent.insertQuery();
            }
        //]]>
        </script>
        <?php
    }
}

/**
 * prints querybox fieldset
 *
 * @usedby  PMA_sqlQueryForm()
 * @uses    $GLOBALS['text_dir']
 * @uses    $GLOBALS['cfg']['TextareaAutoSelect']
 * @uses    $GLOBALS['cfg']['TextareaCols']
 * @uses    $GLOBALS['cfg']['TextareaRows']
 * @uses    $GLOBALS['strShowThisQuery']
 * @uses    $GLOBALS['strGo']
 * @uses    PMA_USR_OS
 * @uses    PMA_USR_BROWSER_AGENT
 * @uses    PMA_USR_BROWSER_VER
 * @uses    htmlspecialchars()
 * @param   string      $query          query to display in the textarea
 * @param   boolean     $is_querywindow if inside querywindow or not
 * @param   string      $delimiter      default delimiter to use
 */
function PMA_sqlQueryFormInsert($query = '', $is_querywindow = false, $delimiter = ';')
{

    // enable auto select text in textarea
    if ($GLOBALS['cfg']['TextareaAutoSelect']) {
        $auto_sel = ' onfocus="selectContent(this, sql_box_locked, true)"';
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
        $legend = sprintf($GLOBALS['strRunSQLQueryOnServer'],
            '&quot;' . htmlspecialchars(
                ! empty($GLOBALS['cfg']['Servers'][$GLOBALS['server']]['verbose']) ? $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['verbose'] : $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['host']) . '&quot;');
    } elseif (! strlen($GLOBALS['table'])) {
        // prepare for db related
        $db     = $GLOBALS['db'];
        // if you want navigation:
        $strDBLink = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase']
            . '?' . PMA_generate_common_url($db) . '"';
        if ($is_querywindow) {
            $strDBLink .= ' target="_self"'
                . ' onclick="this.target=window.opener.frame_content.name"';
        }
        $strDBLink .= '>'
            . htmlspecialchars($db) . '</a>';
        // else use
        // $strDBLink = htmlspecialchars($db);
        $legend = sprintf($GLOBALS['strRunSQLQuery'], $strDBLink);
        if (empty($query)) {
            $query = str_replace('%d',
                PMA_backquote($db), $GLOBALS['cfg']['DefaultQueryDatabase']);
        }
    } else {
        $table  = $GLOBALS['table'];
        $db     = $GLOBALS['db'];
        // Get the list and number of fields
        // we do a try_query here, because we could be in the query window,
        // trying to synchonize and the table has not yet been created
        $fields_list = PMA_DBI_fetch_result(
            'SHOW FULL COLUMNS FROM ' . PMA_backquote($db)
            . '.' . PMA_backquote($GLOBALS['table']));

        $strDBLink = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase']
            . '?' . PMA_generate_common_url($db) . '"';
        if ($is_querywindow) {
            $strDBLink .= ' target="_self"'
                . ' onclick="this.target=window.opener.frame_content.name"';
        }
        $strDBLink .= '>'
            . htmlspecialchars($db) . '</a>';
        // else use
        // $strDBLink = htmlspecialchars($db);
        $legend = sprintf($GLOBALS['strRunSQLQuery'], $strDBLink);
        if (empty($query) && count($fields_list)) {
            $field_names = array();
            foreach ($fields_list as $field) {
                $field_names[] = PMA_backquote($field['Field']);
            }
            $query =
                str_replace('%d', PMA_backquote($db),
                    str_replace('%t', PMA_backquote($table),
                        str_replace('%f',
                            implode(', ', $field_names),
                            $GLOBALS['cfg']['DefaultQueryTable'])));
            unset($field_names);
        }
    }
    $legend .= ': ' . PMA_showMySQLDocu('SQL-Syntax', 'SELECT');

    if (count($fields_list)) {
        $sqlquerycontainer_id = 'sqlquerycontainer';
    } else {
        $sqlquerycontainer_id = 'sqlquerycontainerfull';
    }

    echo '<a name="querybox"></a>' . "\n"
        .'<div id="queryboxcontainer">' . "\n"
        .'<fieldset id="querybox">' . "\n";
    echo '<legend>' . $legend . '</legend>' . "\n";
    echo '<div id="queryfieldscontainer">' . "\n";
    echo '<div id="' . $sqlquerycontainer_id . '">' . "\n"
        .'<textarea name="sql_query" id="sqlquery"'
        .'  cols="' . $GLOBALS['cfg']['TextareaCols'] . '"'
        .'  rows="' . $height . '"'
        .'  dir="' . $GLOBALS['text_dir'] . '"'
        .$auto_sel . $locking . '>' . htmlspecialchars($query) . '</textarea>' . "\n";
    echo '</div>' . "\n";

    if (count($fields_list)) {
        echo '<div id="tablefieldscontainer">' . "\n"
            .'<label>' . $GLOBALS['strFields'] . '</label>' . "\n"
            .'<select id="tablefields" name="dummy" '
            .'size="' . ($GLOBALS['cfg']['TextareaRows'] - 2) . '" '
            .'multiple="multiple" ondblclick="insertValueQuery()">' . "\n";
        foreach ($fields_list as $field) {
            echo '<option value="'
                .PMA_backquote(htmlspecialchars($field['Field'])) . '"';
            if (isset($field['Field']) && strlen($field['Field']) && isset($field['Comment'])) {
                echo ' title="' . htmlspecialchars($field['Comment']) . '"';
            }
            echo '>' . htmlspecialchars($field['Field']) . '</option>' . "\n";
        }
        echo '</select>' . "\n"
            .'<div id="tablefieldinsertbuttoncontainer">' . "\n";
        if ($GLOBALS['cfg']['PropertiesIconic']) {
            echo '<input type="button" name="insert" value="&lt;&lt;"'
                .' onclick="insertValueQuery()"'
                .' title="' . $GLOBALS['strInsert'] . '" />' . "\n";
        } else {
            echo '<input type="button" name="insert"'
                .' value="' . $GLOBALS['strInsert'] . '"'
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
            <?php echo $GLOBALS['strBookmarkThis']; ?>:</label>
        <input type="text" name="bkm_label" id="bkm_label" value="" />
        </div>
        <div class="formelement">
        <input type="checkbox" name="bkm_all_users" id="id_bkm_all_users"
            value="true" />
        <label for="id_bkm_all_users">
            <?php echo $GLOBALS['strBookmarkAllUsers']; ?></label>
        </div>
        <div class="formelement">
        <input type="checkbox" name="bkm_replace" id="id_bkm_replace"
            value="true" />
        <label for="id_bkm_replace">
            <?php echo $GLOBALS['strBookmarkReplace']; ?></label>
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
            document.writeln(' <input type="checkbox" name="LockFromUpdate" checked="checked" id="checkbox_lock" /> <label for="checkbox_lock"><?php echo $GLOBALS['strQueryWindowLock']; ?></label> ');
        //]]>
        </script>
        <?php
    }
    echo '</div>' . "\n";
    echo '<div class="formelement">' . "\n";
    echo '<label for="id_sql_delimiter">[ ' . $GLOBALS['strDelimiter']
        .'</label>' . "\n";
    echo '<input type="text" name="sql_delimiter" size="3" '
        .'value="' . $delimiter . '" '
        .'id="id_sql_delimiter" /> ]' . "\n";

    echo '<input type="checkbox" name="show_query" value="1" '
        .'id="checkbox_show_query" checked="checked" />' . "\n"
        .'<label for="checkbox_show_query">' . $GLOBALS['strShowThisQuery']
        .'</label>' . "\n";

    echo '</div>' . "\n";
    echo '<input type="submit" name="SQL" value="' . $GLOBALS['strGo'] . '" />'
        ."\n";
    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>' . "\n";
}

/**
 * prints bookmark fieldset
 *
 * @usedby  PMA_sqlQueryForm()
 * @uses    PMA_Bookmark_getList()
 * @uses    $GLOBALS['db']
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    $GLOBALS['cfg']['ReplaceHelpImg']
 * @uses    $GLOBALS['strBookmarkQuery']
 * @uses    $GLOBALS['strBookmarkView']
 * @uses    $GLOBALS['strDelete']
 * @uses    $GLOBALS['strDocu']
 * @uses    $GLOBALS['strGo']
 * @uses    $GLOBALS['strSubmit']
 * @uses    $GLOBALS['strVar']
 * @uses    count()
 * @uses    htmlspecialchars()
 */
function PMA_sqlQueryFormBookmark()
{
    $bookmark_list = PMA_Bookmark_getList($GLOBALS['db']);
    if (! $bookmark_list || count($bookmark_list) < 1) {
        return;
    }

    echo '<fieldset id="bookmarkoptions">';
    echo '<legend>';
    echo $GLOBALS['strBookmarkQuery'] . '</legend>' . "\n";
    echo '<div class="formelement">';
    echo '<select name="id_bookmark">' . "\n";
    echo '<option value="">&nbsp;</option>' . "\n";
    foreach ($bookmark_list as $key => $value) {
        echo '<option value="' . htmlspecialchars($key) . '">'
            .htmlspecialchars($value) . '</option>' . "\n";
    }
    // &nbsp; is required for correct display with styles/line height
    echo '</select>&nbsp;' . "\n";
    echo '</div>' . "\n";
    echo '<div class="formelement">' . "\n";
    echo $GLOBALS['strVar'];
    if ($GLOBALS['cfg']['ReplaceHelpImg']) {
        echo ' <a href="./Documentation.html#faqbookmark"'
            .' target="documentation">'
            .'<img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_help.png"'
            .' border="0" width="11" height="11" align="middle"'
            .' alt="' . $GLOBALS['strDocu'] . '" /></a> ';
    } else {
        echo ' (<a href="./Documentation.html#faqbookmark"'
            .' target="documentation">' . $GLOBALS['strDocu'] . '</a>): ';
    }
    echo '<input type="text" name="bookmark_variable" class="textfield"'
        .' size="10" />' . "\n";
    echo '</div>' . "\n";
    echo '<div class="formelement">' . "\n";
    echo '<input type="radio" name="action_bookmark" value="0"'
        .' id="radio_bookmark_exe" checked="checked" />'
        .'<label for="radio_bookmark_exe">' . $GLOBALS['strSubmit']
        .'</label>' . "\n";
    echo '<input type="radio" name="action_bookmark" value="1"'
        .' id="radio_bookmark_view" />'
        .'<label for="radio_bookmark_view">' . $GLOBALS['strBookmarkView']
        .'</label>' . "\n";
    echo '<input type="radio" name="action_bookmark" value="2"'
        .' id="radio_bookmark_del" />'
        .'<label for="radio_bookmark_del">' . $GLOBALS['strDelete']
        .'</label>' . "\n";
    echo '</div>' . "\n";
    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>' . "\n";

    echo '<fieldset id="bookmarkoptionsfooter" class="tblFooters">' . "\n";
    echo '<input type="submit" name="SQL" value="' . $GLOBALS['strGo'] . '" />';
    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>' . "\n";
}

/**
 * prints bookmark fieldset
 *
 * @usedby  PMA_sqlQueryForm()
 * @uses    $GLOBALS['cfg']['GZipDump']
 * @uses    $GLOBALS['cfg']['BZipDump']
 * @uses    $GLOBALS['cfg']['UploadDir']
 * @uses    $GLOBALS['cfg']['AvailableCharsets']
 * @uses    $GLOBALS['cfg']['AllowAnywhereRecoding']
 * @uses    $GLOBALS['strBzip']
 * @uses    $GLOBALS['strCharsetOfFile']
 * @uses    $GLOBALS['strCompression']
 * @uses    $GLOBALS['strError']
 * @uses    $GLOBALS['strGo']
 * @uses    $GLOBALS['strGzip']
 * @uses    $GLOBALS['strLocationTextfile']
 * @uses    $GLOBALS['strWebServerUploadDirectory']
 * @uses    $GLOBALS['strWebServerUploadDirectoryError']
 * @uses    $GLOBALS['charset']
 * @uses    $GLOBALS['max_upload_size']
 * @uses    PMA_supportedDecompressions()
 * @uses    PMA_getFileSelectOptions()
 * @uses    PMA_displayMaximumUploadSize()
 * @uses    PMA_generateCharsetDropdownBox()
 * @uses    PMA_generateHiddenMaxFileSize()
 * @uses    PMA_CSDROPDOWN_CHARSET
 * @uses    empty()
 */
function PMA_sqlQueryFormUpload(){
    $errors = array ();

    $matcher = '@\.sql(\.(' . PMA_supportedDecompressions() . '))?$@'; // we allow only SQL here

    if (!empty($GLOBALS['cfg']['UploadDir'])) {
        $files = PMA_getFileSelectOptions(PMA_userDir($GLOBALS['cfg']['UploadDir']), $matcher, (isset($timeout_passed) && $timeout_passed && isset($local_import_file)) ? $local_import_file : '');
    } else {
        $files = '';
    }

    // start output
    echo '<fieldset id="">';
    echo '<legend>';
    echo $GLOBALS['strLocationTextfile'] . '</legend>';
    echo '<div class="formelement">';
    echo '<input type="file" name="sql_file" class="textfield" /> ';
    echo PMA_displayMaximumUploadSize($GLOBALS['max_upload_size']);
    // some browsers should respect this :)
    echo PMA_generateHiddenMaxFileSize($GLOBALS['max_upload_size']) . "\n";
    echo '</div>';

    if ($files === FALSE) {
        $errors[] = PMA_Message::error('strWebServerUploadDirectoryError');
    } elseif (!empty($files)) {
        echo '<div class="formelement">';
        echo '<strong>' . $GLOBALS['strWebServerUploadDirectory'] .':</strong>' . "\n";
        echo '<select size="1" name="sql_localfile">' . "\n";
        echo '<option value="" selected="selected"></option>' . "\n";
        echo $files;
        echo '</select>' . "\n";
        echo '</div>';
    }

    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>';


    echo '<fieldset id="" class="tblFooters">';
    echo $GLOBALS['strCharsetOfFile'] . "\n";
    echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_CHARSET,
            'charset_of_file', null, 'utf8', FALSE);
    echo '<input type="submit" name="SQL" value="' . $GLOBALS['strGo']
        .'" />' . "\n";
    echo '<div class="clearfloat"></div>' . "\n";
    echo '</fieldset>';

    foreach ($errors as $error) {
        $error->display();
    }
}
?>
