<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Defines the query to be displayed in the query textarea
 */
if (isset($show_query) && $show_query == '1') {
    // This script has been called by read_dump.php3
    if (isset($sql_query_cpy)) {
        $query_to_display = $sql_query_cpy;
    }
    // Other cases
    else if (get_magic_quotes_gpc()) {
        $query_to_display = stripslashes($sql_query);
    }
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


/**
 * Work on the table
 */
// loic1: defines wether file upload is available or not
// lem9: we should check if PHP 4.0.0 really implements the "file_uploads"
// variable, because I got a support request and his 4.0.0 did not have it

$is_upload = (PMA_PHP_INT_VERSION >= 40000 && function_exists('ini_get'))
           ? ((strtolower(ini_get('file_uploads')) == 'on' || ini_get('file_uploads') == 1) && intval(ini_get('upload_max_filesize')))
           // loic1: php 3.0.15 and lower bug -> always enabled
           : (PMA_PHP_INT_VERSION < 30016 || intval(@get_cfg_var('upload_max_filesize')));

$auto_sel  = ($cfg['TextareaAutoSelect'])
           ? "\n" . '             onfocus="if (typeof(document.layers) == \'undefined\' || typeof(textarea_selected) == \'undefined\') {textarea_selected = 1; this.form.elements[\'sql_query\'].select();}"'
           : '';
?>
    <!-- Query box and bookmark support -->
    <li>
        <a name="querybox"></a>
        <form method="post" action="read_dump.php3"<?php if ($is_upload) echo ' enctype="multipart/form-data"'; echo "\n"; ?>
            onsubmit="return checkSqlQuery(this)" name="sqlform">
            <input type="hidden" name="is_js_confirmed" value="0" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
            <input type="hidden" name="zero_rows" value="<?php echo $strSuccess; ?>" />
            <input type="hidden" name="prev_sql_query" value="<?php echo ((!empty($query_to_display)) ? urlencode($query_to_display) : ''); ?>" />
            <?php echo sprintf($strRunSQLQuery,  htmlspecialchars($db)) . ' ' . PMA_showMySQLDocu('Reference', 'SELECT') . '&nbsp;&nbsp;&nbsp;' . $strFields . ':' . "\n"; ?>
            <select name="dummy" size="1">
<?php
echo "\n";
for ($i = 0 ; $i < $fields_cnt; $i++) {
    echo '                '
         . '<option value="' . urlencode($fields_list[$i]) . '">' . htmlspecialchars($fields_list[$i]) . '</option>' . "\n";
}
?>
            </select>
            <input type="button" name="insert" value="<?php echo($strInsert); ?>" onclick="sqlform.sql_query.value = sqlform.sql_query.value + sqlform.dummy.value" />
            <br />
            <div style="margin-bottom: 5px">
            <textarea name="sql_query" rows="<?php echo $cfg['TextareaRows']; ?>" cols="<?php echo $cfg['TextareaCols'] * 2; ?>" wrap="virtual" dir="<?php echo $text_dir; ?>"<?php echo $auto_sel; ?>>
<?php echo ((!empty($query_to_display)) ? htmlspecialchars($query_to_display) : 'SELECT * FROM ' . PMA_backquote($table) . ' WHERE 1'); ?>
</textarea><br />
            <input type="checkbox" name="show_query" value="1" id="checkbox_show_query" checked="checked" />&nbsp;
                <label for="checkbox_show_query"><?php echo $strShowThisQuery; ?></label><br />
            </div>
<?php
// loic1: displays import dump feature only if file upload available
if ($is_upload) {
    echo '            <i>' . $strOr . '</i> ' . $strLocationTextfile . '&nbsp;:<br />' . "\n";
    ?>
            <div style="margin-bottom: 5px">
            <input type="file" name="sql_file" class="textfield" /><br />
    <?php
    if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
        $temp_charset = reset($cfg['AvailableCharsets']);
        echo $strCharsetOfFile . "\n"
             . '        <select name="charset_of_file" size="1">' . "\n"
             . '                <option value="' . $temp_charset . '"';
        if ($temp_charset == $charset) {
            echo ' selected="selected"';
        }
        echo '>' . $temp_charset . '</option>' . "\n";
        while ($temp_charset = next($cfg['AvailableCharsets'])) {
            echo '                <option value="' . $temp_charset . '"';
            if ($temp_charset == $charset) {
                echo ' selected="selected"';
            }
            echo '>' . $temp_charset . '</option>' . "\n";
        } // end while
        echo '            </select><br />' . "\n" . '    ';
    } // end if
    $is_gzip = ($cfg['GZipDump'] && @function_exists('gzopen'));
    $is_bzip = ($cfg['BZipDump'] && @function_exists('bzdecompress'));
    if ($is_bzip || $is_gzip) {
        echo '        ' . $strCompression . ':' . "\n"
           . '            <input type="radio" id="radio_sql_file_compression_plain" name="sql_file_compression" value="text/plain" checked="checked" />' . "\n"
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

// Encoding setting form appended by Y.Kawada
if (function_exists('PMA_set_enc_form')) {
    echo PMA_set_enc_form('            ');
}

// Bookmark Support
if ($cfg['Bookmark']['db'] && $cfg['Bookmark']['table']) {
    if (($bookmark_list = PMA_listBookmarks($db, $cfg['Bookmark'])) && count($bookmark_list) > 0) {
        echo "            <i>$strOr</i> $strBookmarkQuery&nbsp;:<br />\n";
        echo '            <div style="margin-bottom: 5px">' . "\n";
        echo '            <select name="id_bookmark" style="vertical-align: middle">' . "\n";
        echo '                <option value=""></option>' . "\n";
        while (list($key, $value) = each($bookmark_list)) {
            echo '                <option value="' . $value . '">' . htmlentities($key) . '</option>' . "\n";
        }
        echo '            </select>' . "\n";
        echo '            <input type="radio" name="action_bookmark" value="0" id="radio_bookmark0" checked="checked" style="vertical-align: middle" /><label for="radio_bookmark0">' . $strSubmit . '</label>' . "\n";
        echo '            &nbsp;<input type="radio" name="action_bookmark" value="1" id="radio_bookmark1" style="vertical-align: middle" /><label for="radio_bookmark1">' . $strBookmarkView . '</label>' . "\n";
        echo '            &nbsp;<input type="radio" name="action_bookmark" value="2" id="radio_bookmark2" style="vertical-align: middle" /><label for="radio_bookmark2">' . $strDelete . '</label>' . "\n";
        echo '            <br />' . "\n";
        echo '            </div>' . "\n";
    }
}
?>
            <input type="submit" name="SQL" value="<?php echo $strGo; ?>" />
        </form>
    </li>

<?php
// loic1: displays import dump feature only if file upload available
if ($is_upload) {
    ?>
    <!-- Insert a text file -->
    <li>
        <div style="margin-bottom: 10px"><a href="ldi_table.php3?<?php echo $url_query; ?>"><?php echo $strInsertTextfiles; ?></a></div>
    </li>
    <?php
}
echo "\n";
?>
