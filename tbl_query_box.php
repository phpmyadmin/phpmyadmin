<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Check parameters

require_once('./libraries/common.lib.php');
require_once('./libraries/bookmark.lib.php');

$upload_dir_error='';

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
    $result            = PMA_DBI_query('SHOW FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db) . ';');
    $fields_cnt        = PMA_DBI_num_rows($result);
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $fields_list[] = $row['Field'];
    } // end while
    PMA_DBI_free_result($result);
}

/**
 * Work on the table
 */
// loic1: defines wether file upload is available or not
// ($is_upload now defined in common.lib.php)

if ($cfg['QueryFrame'] && $cfg['QueryFrameJS'] && isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'sql' || $querydisplay_tab == 'full')) {
    $locking   = 'onkeypress="document.sqlform.elements[\'LockFromUpdate\'].checked = true;"';
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
/**
  * Get the list and number of available databases.
  */
if ($server > 0) {
    PMA_availableDatabases(); // this function is defined in "common.lib.php"
} else {
    $num_dbs = 0;
}
if ($cfg['QueryFrame'] && (!$cfg['QueryFrameJS'] && !$db || ($cfg['QueryFrameJS'] && !$db))) {
    if ($num_dbs > 0) {
        $queryframe_db_list = '<select size=1 name="db" style="vertical-align: middle;">';
        for ($i = 0; $i < $num_dbs; $i++) {
            $t_db = $dblist[$i];
            $queryframe_db_list .= '<option value="' . htmlspecialchars($t_db) . '">' . htmlspecialchars($t_db) . '</option>';
        }
        $queryframe_db_list .= '</select>&nbsp;';
        $queryframe_thcolspan = ' colspan="2"';
        $queryframe_tdcolspan = '';
    } else {
        $queryframe_db_list = '';
        $queryframe_thcolspan = ' colspan="3"';
        $queryframe_tdcolspan = ' colspan="2"';
    }
} else {
    $queryframe_db_list = '';
    if ($num_dbs > 0) {
        $queryframe_thcolspan = ' colspan="3"';
        $queryframe_tdcolspan = ' colspan="2"';
    } else {
        $queryframe_thcolspan = ' colspan="2"';
        $queryframe_tdcolspan = '';
    }
}
$form_items = 0;
if ($cfg['QueryFrame'] && $cfg['QueryFrameJS'] && isset($is_inside_querywindow) && $is_inside_querywindow) {
?>
        <script type="text/javascript">
        <!--
        document.writeln('<form method="post" target="phpmain' +  <?php echo ((isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) ? 'opener.' : '');?>parent.frames.queryframe.document.hashform.hash.value + '" action="read_dump.php"<?php if ($is_upload) echo ' enctype="multipart/form-data"'; ?> onsubmit="return checkSqlQuery(this)" name="sqlform">');
        //-->
        </script>
<?php
    echo '        <noscript>' . "\n"
       . '        <form method="post" target="phpmain' . md5($cfg['PmaAbsoluteUri']) . '" ' . "\n"
       . '            action="read_dump.php"' . ($is_upload ? ' enctype="multipart/form-data"' : '' ) . ' name="sqlform">' . "\n"
       . '        </noscript>';
} else {
?>    
        <form method="post" action="read_dump.php"<?php if ($is_upload) echo ' enctype="multipart/form-data"'; ?> onSubmit="return checkSqlQuery(this)" name="sqlform">
<?php
}
?>
            <table border="0" cellpadding="2" cellspacing="0">
<?php
// for better administration
$querybox_hidden_fields = '                    <input type="hidden" name="is_js_confirmed" value="0" />' . "\n"
                            . '                    ' . PMA_generate_common_hidden_inputs($db, $table) . "\n"
                            . '                    <input type="hidden" name="pos" value="0" />'. "\n"
                            . '                    <input type="hidden" name="goto" value="' . $goto . '" />'. "\n"
                            . '                    <input type="hidden" name="zero_rows" value="' . $strSuccess . '" />'. "\n"
                            . '                    <input type="hidden" name="prev_sql_query" value="' . ((!empty($query_to_display)) ? urlencode($query_to_display) : '') . '" />'. "\n";
if (!isset($is_inside_querywindow) ||
    (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'sql' || $querydisplay_tab == 'full'))) {
?>
    <!-- Query box and bookmark support -->
<?php
    if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
        $querybox_hidden_fields .= '                    <input type="hidden" name="focus_querywindow" value="true" />' . "\n";
    }
?>
            <tr class="tblHeaders">
                <td nowrap="nowrap"<?php if ($queryframe_tdcolspan == '') { echo ' colspan="2"'; } ?>>
                    <a name="querybox"></a>
                    <?php echo sprintf($strRunSQLQuery,  htmlspecialchars($db) . $queryframe_db_list) . PMA_showMySQLDocu('Reference', 'SELECT'); ?>
                </td>
                <?php
    if (isset($table) && $fields_cnt > 0) { ?>
                <td nowrap="nowrap">&nbsp;&nbsp;&nbsp;</td>
                <td nowrap="nowrap"><?php echo $strFields; ?>:&nbsp;</td>
                <?php
    }
                ?>
            </tr>
            <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <td valign="top"<?php if ($queryframe_tdcolspan == '') { echo ' colspan="2"'; } ?>>
                    <textarea name="sql_query" rows="<?php echo $cfg['TextareaRows']; ?>" cols="<?php echo ((isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && $queryframe_tdcolspan != '') ? ceil($cfg['TextareaCols'] * 1.25) : $cfg['TextareaCols'] * 2); ?>" dir="<?php echo $text_dir; ?>"<?php echo $auto_sel; ?>><?php
    if (!empty($query_to_display)) {
        echo htmlspecialchars($query_to_display);
    } elseif (isset($table)) {
        echo htmlspecialchars(str_replace('%d', PMA_backquote($db), str_replace('%t', PMA_backquote($table), $cfg['DefaultQueryTable'])));
    } else {
        echo htmlspecialchars(str_replace('%d', PMA_backquote($db), $cfg['DefaultQueryDatabase']));
    }
?></textarea>
                </td>
<?php
    if (isset($table) && $fields_cnt > 0) {
?>
                <td valign="middle">
                    <?php
        if ($cfg['PropertiesIconic']) {
            echo '<input type="button" name="insert" value="<<" onclick="insertValueQuery()" title="' . $strInsert. '" />';
        } else {
            echo '<input type="button" name="insert" value="' . $strInsert . '" onclick="insertValueQuery()" />';
        }
                ?>
                </td>   
                <td valign="top">   
                    <select name="dummy" size="<?php echo $cfg['TextareaRows']; ?>" multiple="multiple" class="textfield">
<?php
        echo "\n";
        for ($i = 0 ; $i < $fields_cnt; $i++) {
            echo '                        '
               . '<option value="' . PMA_backquote(htmlspecialchars($fields_list[$i])) . '">' . htmlspecialchars($fields_list[$i]) . '</option>' . "\n";
        }
?>
                    </select>        
                </td>
<?php
    }
?>
            </tr>
            <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <td<?php if ($queryframe_tdcolspan != '') { echo ' colspan="2"'; } //echo $queryframe_tdcolspan; ?>>
                    <input type="checkbox" name="show_query" value="1" id="checkbox_show_query" checked="checked" />
                    <label for="checkbox_show_query"><?php echo $strShowThisQuery; ?></label>
<?php
            if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
            ?>
            <br />
            <script type="text/javascript">
                document.writeln('<input type="checkbox" name="LockFromUpdate" value="1" id="checkbox_lock" />&nbsp;');
                document.writeln('    <label for="checkbox_lock"><?php echo $strQueryWindowLock; ?></label><br />');
            </script>
            <?php
            }

            $form_items++;
            ?>
                </td>
                <td align="right" valign="bottom"><input type="submit" name="SQL" value="<?php echo $strGo; ?>" /></td>
            </tr>
<?php
} else {
    $querybox_hidden_fields .= '                    <input type="hidden" name="sql_query" value="" />' . "\n";
    $querybox_hidden_fields .= '                    <input type="hidden" name="show_query" value="1" />' . "\n";
}

// loic1: displays import dump feature only if file upload available
if ($is_upload && (!isset($is_inside_querywindow) ||
    (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'files' || $querydisplay_tab == 'full')))) {
    $form_items++;
?>
            <tr>
                <td<?php echo $queryframe_thcolspan; ?>><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td>
            </tr>
            <tr>
                <td class="tblHeaders"<?php echo $queryframe_thcolspan; ?>>
<?php
    echo '            ' 
       . ((isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && $querydisplay_tab == 'full') || !isset($is_inside_querywindow) ? '<i>' . $strOr . '</i>' : '') 
       . ' ' . $strLocationTextfile . ':&nbsp;' . "\n";
?>
                </td>
            </tr>
            <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                <td<?php echo $queryframe_thcolspan; ?>>
                    <b>&nbsp;<?php echo $strLocationTextfile; ?>:&nbsp;</b>
                </td>
            </tr>
            <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <td<?php echo $queryframe_thcolspan; ?>>
                    &nbsp;&nbsp;<input type="file" name="sql_file" class="textfield" />&nbsp;<?php echo PMA_displayMaximumUploadSize($max_upload_size);?><br />
<?php
    // some browsers should respect this :)
    echo '        ' . PMA_generateHiddenMaxFileSize($max_upload_size) . "\n";

    if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
        $querybox_hidden_fields .= '                    <input type="hidden" name="focus_querywindow" value="true" />' . "\n";
    }

    $is_gzip = ($cfg['GZipDump'] && @function_exists('gzopen'));
    $is_bzip = ($cfg['BZipDump'] && @function_exists('bzdecompress'));
    if ($is_bzip || $is_gzip) {
        echo '                </td>' . "\n"
           . '            </tr>' . "\n"
           . '            <tr bgcolor="' . $cfg['BgcolorOne'] .'">' . "\n"
           . '                <td' . $queryframe_thcolspan . '>' . "\n"
           . '                    &nbsp;&nbsp;'. $strCompression . ':<br />&nbsp;&nbsp;' . "\n"
           . '                    &nbsp;&nbsp;<input type="radio" id="radio_sql_file_compression_auto" name="sql_file_compression" value="" checked="checked" />'
           . '<label for="radio_sql_file_compression_auto">' . $strAutodetect . '</label>&nbsp;&nbsp;' . "\n"
           . '                    <input type="radio" id="radio_sql_file_compression_plain" name="sql_file_compression" value="text/plain" />'
           . '<label for="radio_sql_file_compression_plain">' . $strNone . '</label>&nbsp;&nbsp;' . "\n";
        if ($is_gzip) {
            echo '                    <input type="radio" id="radio_sql_file_compression_gzip" name="sql_file_compression" value="application/x-gzip" />'
               . '<label for="radio_sql_file_compression_gzip">' . $strGzip . '</label>&nbsp;&nbsp;' . "\n";
        }
        if ($is_bzip) {
            echo '                    <input type="radio" id="radio_sql_file_compression_bzip" name="sql_file_compression" value="application/x-bzip" />' 
               . '<label for="radio_sql_file_compression_bzip">' . $strBzip . '</label>&nbsp;&nbsp;' . "\n";
        }
    } else {
        $querybox_hidden_fields .= '                    <input type="hidden" name="sql_file_compression" value="text/plain" />' . "\n";
    }
    ?>
                </td>
            </tr>
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
            $querybox_hidden_fields .= '            <input type="hidden" name="focus_querywindow" value="true" />' . "\n";
        }

        $is_first = 0;
        while ($file = @readdir($handle)) {
            if (is_file($cfg['UploadDir'] . $file) && PMA_checkFileExtensions($file, '.sql')) {
                if ($is_first == 0) {
                    $is_upload_dir = true;
                    echo "\n";
                    echo '    ' . "\n"
                       . '        <tr bgcolor="' .$cfg['BgcolorTwo'].'"><td' . $queryframe_thcolspan . '>';
                    echo '        <b>&nbsp;' . $strWebServerUploadDirectory . ':&nbsp;</b>' . "\n";
                    echo '       </td></tr>' . "\n"
                       . '       <tr bgcolor="' . $cfg['BgcolorOne'] . '"><td' . $queryframe_thcolspan . '>';
                    // add 2004-05-08 by mkkeck
                    // todo: building a php script for indexing files in UploadDir
                    //if ($cfg['UploadDirIndex']) {
                    //    echo '&nbsp;&nbsp;<a href="' . $cfg['UploadDir'] . '" target="_blank">' . $cfg['UploadDir'] . '</a>&nbsp;';
                    //}
                    // end indexing
                    echo '        &nbsp;&nbsp;<select size="1" name="sql_localfile">' . "\n";
                    echo '            <option value="" selected="selected"></option>' . "\n";
                    $form_items++;
                } // end if (is_first)
                echo '            <option value="' . htmlspecialchars($file) . '">' . htmlspecialchars($file) . '</option>' . "\n";
                $is_first++;
            } // end if (is_file)
        } // end while
        if ($is_first > 0) {
            echo '        </select>' . "\n"
               . '        </td></tr>' . "\n";
        } // end if (isfirst > 0)
        @closedir($handle);
        $upload_dir_error=''; // please see 'else {' below ;)
    }
    else {
        // modified by mkkeck 2004-05-08
        //   showing UploadDir Error at the end of all option for SQL-Queries
        $upload_dir_error.= '        <tr><td' . $queryframe_thcolspan . '><img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" width="1" height="1" border="0" alt="" /></td></tr>' . "\n"
                          . '        <tr>' . "\n"
                          . '            <th' . $queryframe_thcolspan . ' class="tblHeadError"><div class="errorhead">' . $strError . '</div></th>' . "\n"
                          . '        </tr>' . "\n"
                          . '        <tr>' . "\n"
                          . '            <td' . $queryframe_thcolspan . ' class="tblError">' . wordwrap($strWebServerUploadDirectoryError,80,'<br />&nbsp;') . '</td>' . "\n"
                          . '        </tr>' . "\n"
                          . '        <tr><td' . $queryframe_thcolspan . '><img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" width="1" height="1" border="0" alt="" /></td></tr>' . "\n";
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
/*
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
*/
   if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
        echo '    <tr bgcolor="' .$cfg['BgcolorTwo'] . '"><td' . $queryframe_thcolspan . '>' . "\n";
        $temp_charset = reset($cfg['AvailableCharsets']);
        echo '&nbsp;' . $strCharsetOfFile
             . '&nbsp;<select name="charset_of_file" size="1">' . "\n"
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
        echo '        </select>' . "\n" . '    ';
        echo '    </td>' . "\n";
        echo '    </tr>' . "\n";
    } // end if (recoding)
    echo '    <tr bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
       . '        <td align="right"' . $queryframe_thcolspan . '><input type="submit" name="SQL" value="' . $strGo . '" /></td>' . "\n"
       . '    </tr>' . "\n\n";  
}

// Bookmark Support
$bookmark_go = FALSE;
if (!isset($is_inside_querywindow) ||
    (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'history' || $querydisplay_tab == 'full'))) {
    if ($cfg['Bookmark']['db'] && $cfg['Bookmark']['table']) {
        if (($bookmark_list = PMA_listBookmarks($db, $cfg['Bookmark'])) && count($bookmark_list) > 0) {
            $form_items++;
            echo '    <tr><td' . $queryframe_thcolspan . '><img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" width="1" height="1" border="0" alt="" /></td></tr>' . "\n";
            echo '    <tr><td' . $queryframe_thcolspan . ' class="tblHeaders">' . "\n";
            echo "            " . ((isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && $querydisplay_tab == 'full') || !isset($is_inside_querywindow) ? "<i>$strOr</i>" : '') . " $strBookmarkQuery:&nbsp;\n";
            echo '    </td></tr>' . "\n";
            if (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE) {
                $querybox_hidden_fields .= '            <input type="hidden" name="focus_querywindow" value="true" />' . "\n";
            }

            echo '    <tr bgcolor="' .$cfg['BgcolorOne'] . '"><td' . $queryframe_tdcolspan . '>' . "\n";
            echo '            <select name="id_bookmark" style="vertical-align: middle">' . "\n";
            echo '                <option value=""></option>' . "\n";
            foreach($bookmark_list AS $key => $value) {
                echo '                <option value="' . $value . '">' . htmlspecialchars($key) . '</option>' . "\n";
            }
            echo '            </select>' . "&nbsp;&nbsp;&nbsp;\n";
            echo '            ' . $strVar; 
            echo '            ' . $cfg['ReplaceHelpImg'] ? '<a href="./Documentation.html#faqbookmark" target="documentation"><img src="' . $pmaThemeImage . 'b_help.png" width="11" height="11" align="absmiddle" alt="' . $strDocu . '" hspace="2" border="0" /></a>' : '(<a href="./Documentation.html#faqbookmark" target="documentation">' . $strDocu . '</a>)';
            echo ': <input type="text" name="bookmark_variable" class="textfield" size="10" style="vertical-align: middle" /><br />' . "\n";
            echo '            <input type="radio" name="action_bookmark" value="0" id="radio_bookmark0" checked="checked" style="vertical-align: middle" /><label for="radio_bookmark0">' . $strSubmit . '</label>' . "\n";
            echo '            &nbsp;<input type="radio" name="action_bookmark" value="1" id="radio_bookmark1" style="vertical-align: middle" /><label for="radio_bookmark1">' . $strBookmarkView . '</label>' . "\n";
            echo '            &nbsp;<input type="radio" name="action_bookmark" value="2" id="radio_bookmark2" style="vertical-align: middle" /><label for="radio_bookmark2">' . $strDelete . '</label>' . "\n";
            echo '            <br />' . "\n";
            echo '            </td>' . "\n";
            echo '    <td valign="bottom" align="right">' . "\n";
            echo '        <input type="submit" name="SQL" value="' . $strGo . '" />';
            echo '    </td></tr>' . "\n";
            $bookmark_go = TRUE;
        }
    }
}

if (!isset($is_inside_querywindow) || (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && (($querydisplay_tab == 'files') || $querydisplay_tab == 'sql' || $querydisplay_tab == 'full' || ($querydisplay_tab == 'history' && $bookmark_go)))) {
    if ($form_items > 0) {
?><!-- not needed 
            <table border="0">
                <tr>
                    <td valign="top">
                        <input type="submit" name="SQL" value="<?php echo $strGo; ?>" />
                    </td>
//-->
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
                <tr><td<?php echo $queryframe_thcolspan; ?>><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" border="0" width="1" height="1" /></td></tr>
                <tr><th<?php echo $queryframe_thcolspan; ?> align="left"><?php echo $strBookmarkThis; ?>:</th>
                <tr bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
                    <td<?php echo $queryframe_thcolspan; ?>>
                        <b><?php echo $strBookmarkOptions; ?>:</b>
                    </td></tr>

                    </td>
                </tr>
                <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <td<?php echo $queryframe_thcolspan; ?>>
                       &nbsp;&nbsp;<?php echo $strBookmarkLabel; ?>: <input type="text" name="bkm_label" value="" /><br />
                       &nbsp;&nbsp;<input type="checkbox" name="bkm_all_users" id="id_bkm_all_users" value="true" /><label for="id_bkm_all_users"><?php echo $strBookmarkAllUsers; ?></label>
                    </td>
                </tr>
                <tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <td align="right"<?php echo $queryframe_thcolspan; ?>>
                        <input type="submit" name="SQLbookmark" value="<?php echo $strGo . ' &amp; ' . $strBookmarkThis; ?>" onClick="if(document.forms['sqlform'].elements['bkm_label'].value==''){ alert('Please insert a Bookmark-Titel     ');forms['sqlform'].elements['bkm_label'].focus();return false; }"/>
                    </td>
                </tr>
<?php
        }
?>
<!-- not needed
                </tr>
            </table>
//-->
<?php
      } else {
            echo '                <tr><td' . $queryframe_thcolspan . '>' . "\n";
            // TODO: Add a more complete warning that no items (like for file import) where found.
            //       (After 2.5.2 release!)
            echo $strWebServerUploadDirectoryError;
            echo '                </td></tr>' . "\n";
      }
}
echo '                <tr><td' . $queryframe_thcolspan . ' height="1">' . "\n";
echo $querybox_hidden_fields;
echo '                </td></tr>';
if ($upload_dir_error!='') {
    echo $upload_dir_error;
}
?>
            </table>
        </form>
        
<?php
//if (!isset($is_inside_querywindow) || !$is_inside_querywindow) echo "</li>\n";
if (!isset($is_inside_querywindow) ||
    (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE && isset($querydisplay_tab) && ($querydisplay_tab == 'files' || $querydisplay_tab == 'full')) && isset($db) && $db != '') {

    // loic1: displays import dump feature only if file upload available
    $ldi_target = 'ldi_table.php?' . $url_query . (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? '&amp;focus_querywindow=true' : '');

    if ($is_upload && isset($db) && isset($table)) {
        //if (!isset($is_inside_querywindow) || !$is_inside_querywindow) echo "<li>\n";
        if ($cfg['PropertiesIconic']) {
            $imgInsertTextfiles = '<img src="' . $pmaThemeImage. 'b_tblimport.png" '
                                . 'width="16" height="16" hspace="2" border="0" align="absmiddle" alt="' . $strInsertTextfiles. '" />';
        }else{
            $imgInsertTextfiles = '';
        }
        ?>
        <!-- Insert a text file -->
            <?php
            if ($cfg['QueryFrame'] && $cfg['QueryFrameJS']) {
            ?>

            <script type="text/javascript">
                <!--
                document.writeln('<a href="<?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? '#' : $ldi_target); ?>" <?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? 'onclick="opener.parent.frames.phpmain\' + opener.parent.frames.queryframe.document.hashform.hash.value + \'.location.href = \\\'' . $ldi_target . '\\\'; return false;"' : ''); ?>><?php echo addslashes($imgInsertTextfiles . $strInsertTextfiles); ?></a>');
                //-->
            </script>

            <?php
            } else {
            ?>

            <script type="text/javascript">
                <!--
                document.writeln('<a href="<?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? '#' : $ldi_target); ?>" <?php echo (isset($is_inside_querywindow) && $is_inside_querywindow == TRUE ? 'onclick="opener.parent.frames.phpmain' . md5($cfg['PmaAbsoluteUri']) . '.location.href = \\\'' . $ldi_target . '\\\'; return false;"' : ''); ?>><?php echo addslashes($imgInsertTextfiles . $strInsertTextfiles); ?></a>');
                //-->
            </script>

            <?php
            }
            ?>

            <noscript>
               <a href="<?php echo $ldi_target; ?>"><?php
                  echo $imgInsertTextfiles . $strInsertTextfiles; 
               ?></a>
            </noscript>
        <?php
        //if (!isset($is_inside_querywindow) || !$is_inside_querywindow) echo "</li>\n";
    }
}
echo "\n";
?>
