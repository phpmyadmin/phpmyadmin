<?php
/* $Id$ */


/**
 * Defines the query to be displayed in the query textarea
 */
if (isset($show_query) && $show_query == 'y') {
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
$local_query = 'SHOW FIELDS FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
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

?>
<!-- TABLE WORK -->
<a name="querybox"></a>

    <!-- Query box and bookmark support -->
    <li>
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
            <?php echo sprintf($strRunSQLQuery,  htmlspecialchars($db)) . ' ' . PMA_showDocuShort('S/E/SELECT.html') . '&nbsp;&nbsp;&nbsp;' . $strFields . ':'; ?>
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
<textarea name="sql_query" rows="<?php echo $cfg['TextareaRows']; ?>" cols="<?php echo $cfg['TextareaCols'] * 2; ?>" wrap="virtual"
    onfocus="if (typeof(document.layers) == 'undefined' || typeof(textarea_selected) == 'undefined') {textarea_selected = 1; this.form.elements['sql_query'].select();}">
<?php echo ((!empty($query_to_display)) ? htmlspecialchars($query_to_display) : 'SELECT * FROM ' . PMA_backquote($table) . ' WHERE 1'); ?>
</textarea><br />
            <input type="checkbox" name="show_query" value="y" id="checkbox_show_query" checked="checked" />&nbsp;
                <label for="checkbox_show_query"><?php echo $strShowThisQuery; ?></label><br />
            </div>
<?php
// loic1: displays import dump feature only if file upload available
if ($is_upload) {
    echo '            <i>' . $strOr . '</i> ' . $strLocationTextfile . '&nbsp;:<br />' . "\n";
    ?>
            <div style="margin-bottom: 5px">
            <input type="file" name="sql_file" class="textfield" /><br />
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
