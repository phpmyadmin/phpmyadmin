<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Runs common work
 */
require('./db_details_common.php');
$url_query .= '&amp;goto=db_details.php';


/**
 * Database work
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


/**
 * Gets informations about the database and, if it is empty, move to the
 * "db_details_structure.php" script where table can be created
 */
$sub_part    = '';
require('./db_details_db_info.php');
if ($num_tables == 0 && empty($db_query_force)) {
    $is_info = TRUE;
    require('./db_details_structure.php');
    exit();
}

// loic1: defines wether file upload is available or not
// (now defined in common.lib.php)

$auto_sel  = ($cfg['TextareaAutoSelect']
               // 2003-02-05 rabus: This causes big trouble with Opera 7 for
               // Windows, so let's disable it there...
               && !(PMA_USR_OS == 'Win' && PMA_USR_BROWSER_AGENT == 'OPERA' && PMA_USR_BROWSER_VER >= 7))
           ? "\n" . '             onfocus="if (typeof(document.layers) == \'undefined\' || typeof(textarea_selected) == \'undefined\') {textarea_selected = 1; this.form.elements[\'sql_query\'].select();}"'
           : '';


// for better administration
$strHiddenFields = '        <input type="hidden" name="is_js_confirmed" value="0" />'  ."\n"
                 . '        ' .PMA_generate_common_hidden_inputs($db) . "\n"
                 . '        <input type="hidden" name="pos" value="0" />' . "\n"
                 . '        <input type="hidden" name="goto" value="db_details.php" />' . "\n"
                 . '        <input type="hidden" name="zero_rows" value="' . htmlspecialchars($strSuccess) . '" />' . "\n"
                 . '        <input type="hidden" name="prev_sql_query" value="' . ((!empty($query_to_display)) ? htmlspecialchars($query_to_display) : '') . '" />' . "\n";
?>
<!-- Query box, sql file loader and bookmark support -->
<form method="post" action="read_dump.php"<?php if ($is_upload) echo ' enctype="multipart/form-data"'; ?> onsubmit="return checkSqlQuery(this)" name="sqlform">
<?php
echo $strHiddenFields;
?>
<a name="querybox"></a>
<table border="0" cellpadding="2" cellspacing="0">
<tr><td class="tblHeaders" colspan="2">
    <?php
           // if you want navigation:
           $strDBLink = '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . $header_url_qry . '&amp;db=' . urlencode($db) . '">'
                      . htmlspecialchars($db) . '</a>';
           // else use
           // $strDBLink = htmlspecialchars($db);
            echo '&nbsp;' . sprintf($strRunSQLQuery, $strDBLink) . ':&nbsp;' . PMA_showMySQLDocu('Reference', 'SELECT');

    ?>
</td></tr>
<tr align="center" bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><td colspan="2">
<textarea name="sql_query" cols="<?php echo $cfg['TextareaCols'] * 2; ?>" rows="<?php echo $cfg['TextareaRows']; ?>" dir="<?php echo $text_dir; ?>"<?php echo $auto_sel; ?>>
<?php
if (!empty($query_to_display)) {
    echo htmlspecialchars($query_to_display);
} else {
    echo htmlspecialchars(str_replace('%d', PMA_backquote($db), $cfg['DefaultQueryDatabase']));
}
?></textarea>
</td></tr>
<tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
    <td>
        <input type="checkbox" name="show_query" value="1" id="checkbox_show_query" checked="checked" /><label for="checkbox_show_query"><?php echo $strShowThisQuery; ?></label>
    </td>
    <td align="right"><input type="submit" name="SQL" value="<?php echo $strGo; ?>" /></td>
</tr>
<?php
// loic1: displays import dump feature only if file upload available
if ($is_upload) {
?>
<tr><td colspan="2"><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td></tr>
<tr>
    <td colspan="2" class="tblHeaders">
    <?php
        echo '    <i>&nbsp;' . $strOr . '</i>' . "\n";
    ?>
    </td>
</tr>
<tr><td colspan="2" bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><b>&nbsp;<?php echo $strLocationTextfile . ':'; ?></b></td></tr>
<tr bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
  <td colspan="2" align="center">
    <input type="file" name="sql_file" class="textfield" />&nbsp;
    <?php
    echo PMA_displayMaximumUploadSize($max_upload_size) . '<br />';
    // some browsers should respect this :)
    echo '    ' . PMA_generateHiddenMaxFileSize($max_upload_size) . "\n";
    $is_gzip = ($cfg['GZipDump'] && @function_exists('gzopen'));
    $is_bzip = ($cfg['BZipDump'] && @function_exists('bzdecompress'));
    if ($is_bzip || $is_gzip) {
        echo '    </td>' . "\n"
           . '</tr>'   . "\n"
           . '<tr bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
           . '    <td colspan="2">' . "\n";
        echo '        &nbsp;&nbsp;' . $strCompression . ':<br />&nbsp;&nbsp;&nbsp;' . "\n"
           . '            <input type="radio" id="radio_sql_file_compression_auto" name="sql_file_compression" value="" checked="checked" /><label for="radio_sql_file_compression_auto">' . $strAutodetect . '</label>&nbsp;&nbsp;' . "\n"
           . '            <input type="radio" id="radio_sql_file_compression_plain" name="sql_file_compression" value="text/plain" /><label for="radio_sql_file_compression_plain">' . $strNone . '</label>&nbsp;&nbsp' . "\n";
        if ($is_gzip) {
            echo '            <input type="radio" id="radio_sql_file_compression_gzip" name="sql_file_compression" value="application/x-gzip" /><label for="radio_sql_file_compression_gzip">' . $strGzip . '</label>&nbsp;&nbsp;' . "\n";
        }
        if ($is_bzip) {
            echo '            <input type="radio" id="radio_sql_file_compression_bzip" name="sql_file_compression" value="application/x-bzip" /><label for="radio_sql_file_compression_bzip">' . $strBzip . '</label>&nbsp;&nbsp;' . "\n";
        }
    } else {
    ?>
        <input type="hidden" name="sql_file_compression" value="text/plain" />
    </td>
</tr>
    <?php
    }
} // end if (is upload)
// web-server upload directory
$is_upload_dir = false;
if (!empty($cfg['UploadDir'])) {
    if (substr($cfg['UploadDir'], -1) != '/') {
        $cfg['UploadDir'] .= '/';
    }
    if ($handle = @opendir($cfg['UploadDir'])) {
        $is_first = 0;
        while ($file = @readdir($handle)) {
            if (is_file($cfg['UploadDir'] . $file) && PMA_checkFileExtensions($file, '.sql')) {
                if ($is_first == 0) {
                    $is_upload_dir = true;
                    echo "\n";
                    echo '    <tr><td colspan=2" bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n";
                    echo '        &nbsp;<b>' . $strWebServerUploadDirectory . ':</b>&nbsp;' . "\n";
                    echo '    </td></tr>' . "\n";
                    echo '    <tr bgcolor="' . $cfg['BgcolorOne'] . '"><td colspan="2">' . "\n";
                    // add 2004-05-08 by mkkeck
                    // todo: building a php script for indexing files in UploadDir
                    //if ($cfg['UploadDirIndex']) {
                    //    echo '&nbsp;<a href="' . $cfg['UploadDir'] . '" target="_blank">' . $cfg['UploadDir'] . '</a>&nbsp;';
                    //}
                    // end indexing
                    echo '        <select size="1" name="sql_localfile">' . "\n";
                    echo '            <option value="" selected="selected"></option>' . "\n";
                } // end if (is_first)
                echo '            <option value="' . htmlspecialchars($file) . '">' . htmlspecialchars($file) . '</option>' . "\n";
                $is_first++;
            } // end if (is_file)
        } // end while
        if ($is_first > 0) {
            echo '        </select>' . "\n"
               . '    </td>'
               . '    </tr>' . "\n\n";
        } // end if (isfirst > 0)
        @closedir($handle);
    }
    else {
        $upload_dir_error = '<tr><td colspan="2"><img src="' .$GLOBALS['pmaThemeImage'] . 'spacer.png'  . '" width="1" height="1" border="0" alt="" /></td></tr>' . "\n"
                          . '<tr><th colspan="2" class="tblHeadError"><div class="errorhead">' . $strError . '</div></th></tr>' . "\n"
                          . '<tr><td colspan="2" class="tblError">' . $strWebServerUploadDirectoryError
                          . '</td></tr>' . "\n";
    }
} // end if (web-server upload directory)
// Charset conversion options
if ($is_upload || $is_upload_dir) {
    if (PMA_MYSQL_INT_VERSION < 40100 && $cfg['AllowAnywhereRecoding'] && $allow_recoding) {
        echo '    <tr bgcolor="' .$cfg['BgcolorTwo'] . '"><td>' . "\n";
        $temp_charset = reset($cfg['AvailableCharsets']);
        echo '&nbsp;' . $strCharsetOfFile . "\n"
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
        echo '    </td>' . "\n";
        echo '    <td align="right"><input type="submit" name="SQL" value="' . $strGo . '" /></td>' . "\n";
        echo '    </tr>' . "\n";
    } elseif (PMA_MYSQL_INT_VERSION >= 40100) {
        echo '    <tr bgcolor="' .$cfg['BgcolorTwo'] . '"><td>' . "\n"
           . $strCharsetOfFile . "\n";
        echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_CHARSET, 'charset_of_file', NULL, 'utf8', FALSE);
        echo '    </td>' . "\n";
        echo '    <td align="right"><input type="submit" name="SQL" value="' . $strGo . '" /></td>' . "\n";
        echo '    </tr>' . "\n";
    }else{
        echo '   <tr bgcolor="' . $cfg['BgcolorTwo'] . '"><td align="right" colspan="2"><input type="submit" name="SQL" value="' . $strGo . '" /></td></tr>' . "\n\n";
    } // end if (recoding)
}
// Bookmark Support
if ($cfg['Bookmark']['db'] && $cfg['Bookmark']['table']) {
    if (($bookmark_list = PMA_listBookmarks($db, $cfg['Bookmark'])) && count($bookmark_list) > 0) {
        echo '    <tr><td colspan="2"><img src="' .$GLOBALS['pmaThemeImage'] . 'spacer.png'  . '" width="1" height="1" border="0" alt="" /></td></tr>' . "\n";
        echo '    <tr><td colspan="2" class="tblHeaders">&nbsp;<i>' . $strOr . '</i></td></tr>' . "\n";
        echo '    <tr bgcolor="' . $cfg['BgcolorOne'] . '"><td colspan="2">' . "\n";
        echo '        <b>' . $strBookmarkQuery . ':</b>&nbsp;' . "\n";
        echo '        <select name="id_bookmark">' . "\n";
        echo '            <option value=""></option>' . "\n";
        foreach ($bookmark_list AS $key => $value) {
            echo '            <option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($key) . '</option>' . "\n";
        }
        echo '        </select>' . "\n";
        echo '    </td></tr><tr bgcolor="' . $cfg['BgcolorOne'] . '"><td colspan="2">';
        echo '            ' . $strVar . ' ';
        if ($cfg['ReplaceHelpImg']){
            echo '<a href="./Documentation.html#faqbookmark" target="documentation">'
             . '<img src="' .$pmaThemeImage . 'b_help.png" border="0" width="11" height="11" align="middle" alt="' . $strDocu . '" /></a>';
        }else{
            echo '(<a href="./Documentation.html#faqbookmark" target="documentation">' . $strDocu . '</a>):&nbsp;';
        }
        echo '        <input type="text" name="bookmark_variable" class="textfield" size="10" />' . "\n";
        echo '    </td></tr><tr bgcolor="' . $cfg['BgcolorOne'] . '"><td>';
        echo '        <input type="radio" name="action_bookmark" value="0" id="radio_bookmark0" checked="checked" style="vertical-align: middle" /><label for="radio_bookmark0">' . $strSubmit . '</label>' . "\n";
        echo '        &nbsp;<input type="radio" name="action_bookmark" value="1" id="radio_bookmark1" style="vertical-align: middle" /><label for="radio_bookmark1">' . $strBookmarkView . '</label>' . "\n";
        echo '        &nbsp;<input type="radio" name="action_bookmark" value="2" id="radio_bookmark2" style="vertical-align: middle" /><label for="radio_bookmark2">' . $strDelete . '</label>' . "\n";
        echo '        </td>' . "\n";
        echo '        <td align="right"><input type="submit" name="SQL" value="' . $strGo . '" /></td>';
        echo '    </tr>' . "\n";
   }
}

// Encoding setting form appended by Y.Kawada
if (function_exists('PMA_set_enc_form')) {
    echo PMA_set_enc_form('    ');
}
// modified by mkkeck 2004-05-08
//   showing UploadDir Error at the end of all option for SQL-Queries
if (isset($upload_dir_error)) {
    echo $upload_dir_error;
}
?>
</table>
</form>
<?php
/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');
?>
