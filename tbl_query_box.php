<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Check parameters

require_once('./libraries/common.lib.php');
require_once('./libraries/bookmark.lib.php');

if (!($cfg['QueryFrame'] && $cfg['QueryFrameJS'] && isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'sql' || $querydisplay_tab == 'full'))) {
    PMA_checkParameters(array('db','table','url_query'));
}

/**
 * Defines the query to be displayed in the query textarea
 */
if (isset($show_query) && $show_query == '1') {
    // This script has been called by read_dump.php
    if (isset($sql_query_cpy)) {
        $query_to_display = $sql_query_cpy;
    }
    // Other cases
    else {
        $query_to_display = $sql_query;
    }
} else {
    $query_to_display     = '';
}
unset($sql_query);

/**
 * Get the list and number of fields
 */
$fields_cnt = 0;
if (isset($db) && isset($table) && $table != '' && $db != '') {
    $local_query = 'SHOW FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db);
    $result      = @PMA_mysql_query($local_query);
    if (!$result) {
        PMA_mysqlDie('', $local_query, '', $err_url);
    }
    else {
        $fields_cnt        = mysql_num_rows($result);
        while ($row = PMA_mysql_fetch_array($result)) {
            $fields_list[] = $row['Field'];
        } // end while
        mysql_free_result($result);
    }
}

/**
 * Work on the table
 */
// loic1: defines wether file upload is available or not
// ($is_upload now defined in common.lib.php)

if ($cfg['QueryFrame'] && $cfg['QueryFrameJS'] && isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'sql' || $querydisplay_tab == 'full')) {
    $locking   = 'onKeyPress="document.sqlform.elements[\'LockFromUpdate\'].checked = true;"';
} else {
    $locking   = '';
}

$auto_sel  = ($cfg['TextareaAutoSelect']
               // 2003-02-05 rabus: This causes big trouble with Opera 7 for
               // Windows, so let's disable it there...
               && !(PMA_USR_OS == 'Win' && PMA_USR_BROWSER_AGENT == 'OPERA' && PMA_USR_BROWSER_VER >= 7))
           ? "\n" . '             onfocus="if (typeof(document.layers) == \'undefined\' || typeof(textarea_selected) == \'undefined\') {textarea_selected = 1; document.sqlform.elements[\'sql_query\'].select();}"'
           : '';
$auto_sel .= ' ' . $locking;

// garvin: If non-JS query window is embedded, display a list of databases to choose from.
//         Apart from that, a non-js query window sucks badly.

if ($cfg['QueryFrame'] && (!$cfg['QueryFrameJS'] && !$db || ($cfg['QueryFrameJS'] && !$db))) {
    /**
     * Get the list and number of available databases.
     */
    if ($server > 0) {
        PMA_availableDatabases(); // this function is defined in "common.lib.php"
    } else {
        $num_dbs = 0;
    }

    if ($num_dbs > 0) {
        $queryframe_db_list = '<select size=1 name="db">';
        for ($i = 0; $i < $num_dbs; $i++) {
            $t_db = $dblist[$i];
            $queryframe_db_list .= '<option value="' . htmlspecialchars($t_db) . '">' . htmlspecialchars($t_db) . '</option>';
        }
        $queryframe_db_list .= '</select>';
    } else {
        $queryframe_db_list = '';
    }
} else {
    $queryframe_db_list = '';
}

$form_items = 0;

if ($cfg['QueryFrame'] && $cfg['QueryFrameJS'] && isset($is_inside_querywindow) && $is_inside_querywindow) {
?>
        <script type="text/javascript">
        <!--
        document.writeln('<form method="post" target="phpmain' +  <?php echo ((isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) ? 'opener.' : '');?>parent.frames.queryframe.document.hashform.hash.value + '" action="read_dump.php"<?php if ($is_upload) echo ' enctype="multipart/form-data"'; ?> onsubmit="return checkSqlQuery(this)" name="sqlform">');
        //-->
        </script>
        <noscript>
            <form method="post" target="phpmain<?php echo md5($cfg['PmaAbsoluteUri']); ?>" action="read_dump.php"<?php if ($is_upload) echo ' enctype="multipart/form-data"'; ?> name="sqlform">
        </noscript>
<?php
} else {
?>
        <form method="post" action="read_dump.php"<?php if ($is_upload) echo ' enctype="multipart/form-data"'; ?> onsubmit="return checkSqlQuery(this)" name="sqlform">
<?php
}
?>
            <input type="hidden" name="is_js_confirmed" value="0" />
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="zero_rows" value="<?php echo $strSuccess; ?>" />
            <input type="hidden" name="prev_sql_query" value="<?php echo ((!empty($query_to_display)) ? urlencode($query_to_display) : ''); ?>" />
<?php
if (!isset($is_inside_querywindow) ||
    (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'sql' || $querydisplay_tab == 'full'))) {
?>
    <!-- Query box and bookmark support -->
<?php
if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
?>
    <input type="hidden" name="focus_querywindow" value="true" />
<?php
}
?>
    <a name="querybox"></a>
            <table cellpadding="1" cellspacing="1">
                <tr>
                    <td>
                        <?php echo sprintf($strRunSQLQuery,  htmlspecialchars($db)) . $queryframe_db_list . (isset($is_inside_querywindow) ? '<br />' : ' ') . PMA_showMySQLDocu('Reference', 'SELECT'); ?>
                        <br />
                        <textarea name="sql_query" rows="<?php echo $cfg['TextareaRows']; ?>" cols="<?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? ceil($cfg['TextareaCols'] * 1.25) : $cfg['TextareaCols'] * 2); ?>" wrap="virtual" dir="<?php echo $text_dir; ?>"<?php echo $auto_sel; ?>>
<?php
if (!empty($query_to_display)) {
    echo htmlspecialchars($query_to_display);
} elseif (isset($table)) {
    echo htmlspecialchars(str_replace('%d', PMA_backquote($db), str_replace('%t', PMA_backquote($table), $cfg['DefaultQueryTable'])));
} else {
    echo htmlspecialchars(str_replace('%d', PMA_backquote($db), $cfg['DefaultQueryDatabase']));
}
?></textarea>
                    </td>
                        <?php if (isset($table) && $fields_cnt > 0) { ?>
                    <td align="center" valign="top"><?php echo (isset($is_inside_querywindow) ? '<br />' : '') . $strFields; ?>:<br />
                            <select name="dummy" size="4" multiple>
                       <?php
                           echo "\n";
                           for ($i = 0 ; $i < $fields_cnt; $i++) {
                               echo '                '
                                    . '<option value="' . PMA_backquote(htmlspecialchars($fields_list[$i])) . '">' . htmlspecialchars($fields_list[$i]) . '</option>' . "\n";
                           }
                       ?>
                            </select><br /><br />
                            <input type="button" name="insert" value="<?php echo($strInsert); ?>" onclick="insertValueQuery()" />
                    </td>
                        <?php
                        }
                        ?>
                </tr>
            </table>
            <input type="checkbox" name="show_query" value="1" id="checkbox_show_query" checked="checked" />&nbsp;
                <label for="checkbox_show_query"><?php echo $strShowThisQuery; ?></label><br />
            <?php
            if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
            ?>
            <script type="text/javascript">
                document.writeln('<input type="checkbox" name="LockFromUpdate" value="1" id="checkbox_lock" />&nbsp;');
                document.writeln('    <label for="checkbox_lock"><?php echo $strQueryWindowLock; ?></label><br />');
            </script>
            <?php
            }

            $form_items++;
            ?>
            </div>
<?php
} else {
?>
            <input type="hidden" name="sql_query" value="" />
            <input type="hidden" name="show_query" value="1" />
<?php
}

// loic1: displays import dump feature only if file upload available
if ($is_upload && (!isset($is_inside_querywindow) ||
    (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'files' || $querydisplay_tab == 'full')))) {
    $form_items++;
    echo '            ' . ((isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && $querydisplay_tab == 'full') || !isset($is_inside_querywindow) ? '<i>' . $strOr . '</i>' : '') . ' ' . $strLocationTextfile . '&nbsp;:<br />' . "\n";
    ?>
            <div style="margin-bottom: 5px">
            <input type="file" name="sql_file" class="textfield" /><br />
    <?php
    if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
    ?>
            <input type="hidden" name="focus_querywindow" value="true" />
    <?php
    }

    $is_gzip = ($cfg['GZipDump'] && @function_exists('gzopen'));
    $is_bzip = ($cfg['BZipDump'] && @function_exists('bzdecompress'));
    if ($is_bzip || $is_gzip) {
        echo '        ' . $strCompression . ':' . "\n"
           . '            <input type="radio" id="radio_sql_file_compression_auto" name="sql_file_compression" value="" checked="checked" />' . "\n"
           . '            <label for="radio_sql_file_compression_auto">' . $strAutodetect . '</label>&nbsp;&nbsp;&nbsp;' . "\n"
           . '            <input type="radio" id="radio_sql_file_compression_plain" name="sql_file_compression" value="text/plain" />' . "\n"
           . '            <label for="radio_sql_file_compression_plain">' . $strNone . '</label>&nbsp;&nbsp;&nbsp;' . "\n";
        if ($is_gzip) {
            echo '            <input type="radio" id="radio_sql_file_compression_gzip" name="sql_file_compression" value="application/x-gzip" />' . "\n"
               . '            <label for="radio_sql_file_compression_gzip">' . $strGzip . '</label>&nbsp;&nbsp;&nbsp;' . "\n";
        }
        if ($is_bzip) {
            echo '            <input type="radio" id="radio_sql_file_compression_bzip" name="sql_file_compression" value="application/x-bzip" />' . "\n"
               . '            <label for="radio_sql_file_compression_bzip">' . $strBzip . '</label>&nbsp;&nbsp;&nbsp;' . "\n";
        }
    } else {
        echo '        <input type="hidden" name="sql_file_compression" value="text/plain" />' . "\n";
    }
    ?>
            </div>
    <?php
} // end if
echo "\n";

// web-server upload directory
$is_upload_dir = false;
if (!empty($cfg['UploadDir']) && !isset($is_inside_querywindow) ||
    (!empty($cfg['UploadDir']) && isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'files' || $querydisplay_tab == 'full'))) {

    if (substr($cfg['UploadDir'], -1) != '/') {
        $cfg['UploadDir'] .= '/';
    }
    if ($handle = @opendir($cfg['UploadDir'])) {
        if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
        ?>
            <input type="hidden" name="focus_querywindow" value="true" />
        <?php
        }

        $is_first = 0;
        while ($file = @readdir($handle)) {
            if (is_file($cfg['UploadDir'] . $file) && PMA_checkFileExtensions($file, '.sql')) {
                if ($is_first == 0) {
                    $is_upload_dir = true;
                    echo "\n";
                    echo '    ' . ((isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && $querydisplay_tab == 'full') || !isset($is_inside_querywindow) ? '<i>' . $strOr . '</i>' : '') . ' ' . $strWebServerUploadDirectory . '&nbsp;:<br />' . "\n";
                    echo '    <div style="margin-bottom: 5px">' . "\n";
                    echo '        <select size="1" name="sql_localfile">' . "\n";
                    echo '            <option value="" selected="selected"></option>' . "\n";
                    $form_items++;
                } // end if (is_first)
                echo '            <option value="' . htmlspecialchars($file) . '">' . htmlspecialchars($file) . '</option>' . "\n";
                $is_first++;
            } // end if (is_file)
        } // end while
        if ($is_first > 0) {
            echo '        </select>' . "\n"
                 . '    </div>' . "\n\n";
        } // end if (isfirst > 0)
        @closedir($handle);
    } else {
        echo '    <div style="margin-bottom: 5px">' . "\n";
        echo '        <font color="red">' . $strError . '</font><br />' . "\n";
        echo '        ' . $strWebServerUploadDirectoryError . "\n";
        echo '    </div>' . "\n";
    }
} // end if (web-server upload directory)
echo "\n";

// Encoding setting form appended by Y.Kawada
if (function_exists('PMA_set_enc_form')) {
    echo PMA_set_enc_form('            ');
    $form_items++;
}

// Charset conversion options
if (($is_upload || $is_upload_dir) &&
        (!isset($is_inside_querywindow) ||
         (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'files' || $querydisplay_tab == 'full')))
        && isset($db) && $db != ''){
    if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
        $form_items++;
        echo '    <div style="margin-bottom: 5px">' . "\n";
        $temp_charset = reset($cfg['AvailableCharsets']);
        echo $strCharsetOfFile . "\n"
             . '    <select name="charset_of_file" size="1">' . "\n"
             . '            <option value="' . $temp_charset . '"';
        if ($temp_charset == $charset) {
            echo ' selected="selected"';
        }
        echo '>' . $temp_charset . '</option>' . "\n";
        while ($temp_charset = next($cfg['AvailableCharsets'])) {
            echo '            <option value="' . $temp_charset . '"';
            if ($temp_charset == $charset) {
                echo ' selected="selected"';
            }
            echo '>' . $temp_charset . '</option>' . "\n";
        }
        echo '        </select><br />' . "\n" . '    ';
        echo '    </div>' . "\n";
    } // end if (recoding)
}

// Bookmark Support
$bookmark_go = FALSE;
if (!isset($is_inside_querywindow) ||
    (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'history' || $querydisplay_tab == 'full'))) {
    if ($cfg['Bookmark']['db'] && $cfg['Bookmark']['table']) {
        if (($bookmark_list = PMA_listBookmarks($db, $cfg['Bookmark'])) && count($bookmark_list) > 0) {
            $form_items++;
            echo "            " . ((isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && $querydisplay_tab == 'full') || !isset($is_inside_querywindow) ? "<i>$strOr</i>" : '') . " $strBookmarkQuery&nbsp;:<br />\n";

            if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
            ?>
                <input type="hidden" name="focus_querywindow" value="true" />
            <?php
            }

            echo '            <div style="margin-bottom: 5px">' . "\n";
            echo '            <select name="id_bookmark" style="vertical-align: middle">' . "\n";
            echo '                <option value=""></option>' . "\n";
            foreach($bookmark_list AS $key => $value) {
                echo '                <option value="' . $value . '">' . htmlspecialchars($key) . '</option>' . "\n";
            }
            echo '            </select>' . "<br />\n";
            echo '            ' . $strVar . ' (<a href="./Documentation.html#faqbookmark" target="documentation">' . $strDocu . '</a>): <input type="text" name="bookmark_variable" class="textfield" size="10" />' . "\n";
            echo '            <input type="radio" name="action_bookmark" value="0" id="radio_bookmark0" checked="checked" style="vertical-align: middle" /><label for="radio_bookmark0">' . $strSubmit . '</label>' . "\n";
            echo '            &nbsp;<input type="radio" name="action_bookmark" value="1" id="radio_bookmark1" style="vertical-align: middle" /><label for="radio_bookmark1">' . $strBookmarkView . '</label>' . "\n";
            echo '            &nbsp;<input type="radio" name="action_bookmark" value="2" id="radio_bookmark2" style="vertical-align: middle" /><label for="radio_bookmark2">' . $strDelete . '</label>' . "\n";
            echo '            <br />' . "\n";
            echo '            </div>' . "\n";
            $bookmark_go = TRUE;
        }
    }
}

if (!isset($is_inside_querywindow) || (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && (($querydisplay_tab == 'files') || $querydisplay_tab == 'sql' || $querydisplay_tab == 'full' || ($querydisplay_tab == 'history' && $bookmark_go)))) {
    if ($form_items > 0) {
?>
            <table border="0">
                <tr>
                    <td valign="top">
                        <input type="submit" name="SQL" value="<?php echo $strGo; ?>" />
                    </td>
<?php
        if ( $cfg['Bookmark']['db']
          && $cfg['Bookmark']['table']
          && (!isset($is_inside_querywindow)
            || (  isset($is_inside_querywindow)
               && $is_inside_querywindow == TRUE
               && isset($querydisplay_tab)
               //&& $querydisplay_tab != 'history'))) {
               && $querydisplay_tab == 'sql'))) {
?>
                    <td valign="top">
                        <input type="submit" name="SQLbookmark" value="<?php echo $strGo . ' &amp; ' . $strBookmarkThis; ?>" />
                    </td>

                    <td>
                        <fieldset>
                            <legend><?php echo $strBookmarkOptions; ?></legend>
                                <?php echo $strBookmarkLabel; ?>: <input type="text" name="bkm_label" value="" /><br />
                                <input type="checkbox" name="bkm_all_users" id="id_bkm_all_users" value="true" /><label for="id_bkm_all_users"><?php echo $strBookmarkAllUsers; ?></label>
                        </fieldset>
                    </td>
<?php
        }
?>
                </tr>
            </table>
<?php
        } else {
            // TODO: Add a more complete warning that no items (like for file import) where found.
            //       (After 2.5.2 release!)
            echo $strWebServerUploadDirectoryError;
        }
}
?>
</form>
<?php
if (!isset($is_inside_querywindow) ||
    (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'files' || $querydisplay_tab == 'full')) && isset($db) && $db != '') {

    // loic1: displays import dump feature only if file upload available
    $ldi_target = 'ldi_table.php?' . $url_query . (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? '&amp;focus_querywindow=true' : '');

    if ($is_upload && isset($db) && isset($table)) {
        ?>
        <!-- Insert a text file -->
        <br /><br />
        <li>
            <?php
            if ($cfg['QueryFrame'] && $cfg['QueryFrameJS']) {
            ?>

            <script type="text/javascript">
                document.writeln('<div style="margin-bottom: 10px"><a href="<?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? '#' : $ldi_target); ?>" <?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? 'onclick="opener.parent.frames.phpmain\' + opener.parent.frames.queryframe.document.hashform.hash.value + \'.location.href = \\\'' . $ldi_target . '\\\'; return false;"' : ''); ?>><?php echo addslashes($strInsertTextfiles); ?></a></div>');
            </script>

            <?php
            } else {
            ?>

            <script type="text/javascript">
                document.writeln('<div style="margin-bottom: 10px"><a href="<?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? '#' : $ldi_target); ?>" <?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? 'onclick="opener.parent.frames.phpmain' . md5($cfg['PmaAbsoluteUri']) . '.location.href = \\\'' . $ldi_target . '\\\'; return false;"' : ''); ?>><?php echo addslashes($strInsertTextfiles); ?></a></div>');
            </script>

            <?php
            }
            ?>

            <noscript>
               <div style="margin-bottom: 10px"><a href="<?php echo $ldi_target; ?>"><?php echo $strInsertTextfiles; ?></a></div>
            </noscript>
        </li>
        <?php
    }
}
echo "\n";
?>
