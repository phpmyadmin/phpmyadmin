<?php
/* $Id$ */


require('./tbl_properties_common.php3');

/**
 * Drop multiple fields if required
 */
if ((!empty($submit_mult) && isset($selected_fld))
    || isset($mult_btn)) {
    $action = 'tbl_properties.php3';
    include('./mult_submits.inc.php3');
}


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
 * Updates table type, comment and order if required
 */
if (isset($submitcomment)) {
    if (get_magic_quotes_gpc()) {
        $comment = stripslashes($comment);
    }
    if (empty($prev_comment) || urldecode($prev_comment) != $comment) {
        $local_query = 'ALTER TABLE ' . PMA_backquote($table) . ' COMMENT = \'' . PMA_sqlAddslashes($comment) . '\'';
        $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    }
}
if (isset($submittype)) {
    $local_query = 'ALTER TABLE ' . PMA_backquote($table) . ' TYPE = ' . $tbl_type;
    $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
}
if (isset($submitorderby) && !empty($order_field)) {
    $order_field = PMA_backquote(urldecode($order_field));
    $local_query = 'ALTER TABLE ' . PMA_backquote($table) . 'ORDER BY ' . $order_field;
    $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
}

// 1. Get table information
require('./tbl_properties_table_info.php3');

// 2. Gets table keys and retains them
$local_query = 'SHOW KEYS FROM ' . PMA_backquote($table);
$result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
$primary     = '';
$ret_keys    = array();
$pk_array    = array(); // will be use to emphasis prim. keys in the table view
while ($row = mysql_fetch_array($result)) {
    $ret_keys[]  = $row;
    // Backups the list of primary keys
    if ($row['Key_name'] == 'PRIMARY') {
        $primary .= $row['Column_name'] . ', ';
        $pk_array[$row['Column_name']] = 1;
    }
} // end while
mysql_free_result($result);


// 3. Get fields
$local_query = 'SHOW FIELDS FROM ' . PMA_backquote($table);
$fields_rs   = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
$fields_cnt  = mysql_num_rows($fields_rs);



/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 */
?>

<!-- TABLE INFORMATIONS -->

<form action="tbl_properties.php3">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="table" value="<?php echo $table; ?>" />

<table border="<?php echo $cfg['Border']; ?>">
<tr>
    <td></td>
    <th>&nbsp;<?php echo ucfirst($strField); ?>&nbsp;</th>
    <th><?php echo ucfirst($strType); ?></th>
    <th><?php echo ucfirst($strAttr); ?></th>
    <th><?php echo ucfirst($strNull); ?></th>
    <th><?php echo ucfirst($strDefault); ?></th>
    <th><?php echo ucfirst($strExtra); ?></th>
    <th colspan="<?php echo((PMA_MYSQL_INT_VERSION >= 32323) ? '6' : '5'); ?>"><?php echo ucfirst($strAction); ?></th>
</tr>

<?php
$i         = 0;

while ($row = mysql_fetch_array($fields_rs)) {
    $i++;
    $bgcolor          = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];

    $type             = $row['Type'];
    // reformat mysql query output - staybyte - 9. June 2001
    // loic1: set or enum types: slashes single quotes inside options
    if (eregi('^(set|enum)\((.+)\)$', $type, $tmp)) {
        $tmp[2]       = substr(ereg_replace('([^,])\'\'', '\\1\\\'', ',' . $tmp[2]), 1);
        $type         = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
        $type_nowrap  = '';
    } else {
        $type_nowrap  = ' nowrap="nowrap"';
    }
    $type             = eregi_replace('BINARY', '', $type);
    $type             = eregi_replace('ZEROFILL', '', $type);
    $type             = eregi_replace('UNSIGNED', '', $type);
    if (empty($type)) {
        $type         = '&nbsp;';
    }

    $binary           = eregi('BINARY', $row['Type'], $test);
    $unsigned         = eregi('UNSIGNED', $row['Type'], $test);
    $zerofill         = eregi('ZEROFILL', $row['Type'], $test);
    $strAttribute     = '&nbsp;';
    if ($binary) {
        $strAttribute = 'BINARY';
    }
    if ($unsigned) {
        $strAttribute = 'UNSIGNED';
    }
    if ($zerofill) {
        $strAttribute = 'UNSIGNED ZEROFILL';
    }
    if (!isset($row['Default'])) {
        if ($row['Null'] != '') {
            $row['Default'] = '<i>NULL</i>';
        }
    } else {
        $row['Default'] = htmlspecialchars($row['Default']);
    }

    $field_encoded = urlencode($row['Field']);
    $field_name    = htmlspecialchars($row['Field']);
    if (isset($pk_array[$row['Field']])) {
        $field_name = '<u>' . $field_name . '</u>';
    }
    echo "\n";

    ?>
<tr>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <input type="checkbox" name="selected_fld[]" value="<?php echo $field_encoded; ?>" id="checkbox_row_<?php echo $i; ?>" />
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">&nbsp;<label for="checkbox_row_<?php echo $i; ?>"><?php echo $field_name; ?></label>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>"<?php echo $type_nowrap; ?>><?php echo $type; ?><bdo dir="ltr"></bdo></td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php echo $strAttribute; ?></td>
    <td bgcolor="<?php echo $bgcolor; ?>"><?php echo (($row['Null'] == '') ? $strNo : $strYes); ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php if (isset($row['Default'])) echo $row['Default']; ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php echo $row['Extra']; ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_alter.php3?<?php echo $url_query; ?>&amp;field=<?php echo $field_encoded; ?>">
            <?php echo $strChange; ?></a>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        // loic1: Drop field only if there is more than one field in the table
        if ($fields_cnt > 1) {
            echo "\n";
            ?>
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP ' . PMA_backquote($row['Field'])); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strFieldHasBeenDropped, htmlspecialchars($row['Field']))); ?>"
            onclick="return confirmLink(this, 'ALTER TABLE <?php echo PMA_jsFormat($table); ?> DROP <?php echo PMA_jsFormat($row['Field']); ?>')">
            <?php echo $strDrop; ?></a>
            <?php
        } else {
            echo "\n" . '        ' . $strDrop;
        }
        echo "\n";
        ?>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if ($type == 'text' || $type == 'blob') {
            echo $strPrimary . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP PRIMARY KEY, ADD PRIMARY KEY(' . $primary . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAPrimaryKey, htmlspecialchars($row['Field']))); ?>"
            onclick="return confirmLink(this, 'ALTER TABLE <?php echo PMA_jsFormat($table); ?> DROP PRIMARY KEY, ADD PRIMARY KEY(<?php echo PMA_jsFormat($row['Field']); ?>)')">
            <?php echo $strPrimary; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if ($type == 'text' || $type == 'blob') {
            echo $strIndex . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD INDEX(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex ,htmlspecialchars($row['Field']))); ?>">
            <?php echo $strIndex; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if ($type == 'text' || $type == 'blob') {
            echo $strUnique . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD UNIQUE(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex , htmlspecialchars($row['Field']))); ?>">
            <?php echo $strUnique; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <?php
    if (PMA_MYSQL_INT_VERSION >= 32323) {
        if ((!empty($tbl_type) && $tbl_type == 'MYISAM')
            && ($type == 'text' || strpos(' ' . $type, 'varchar'))) {
            echo "\n";
            ?>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD FULLTEXT(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex , htmlspecialchars($row['Field']))); ?>">
            <?php echo $strIdxFulltext; ?></a>
    </td>
            <?php
        } else {
            echo "\n";
            ?>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        <?php echo $strIdxFulltext . "\n"; ?>
    </td>
            <?php
        } // end if... else...
    } // end if
    echo "\n"
    ?>
</tr>
    <?php
} // end while

echo "\n";
?>

<tr>
    <td colspan="<?php echo((PMA_MYSQL_INT_VERSION >= 32323) ? '13' : '12'); ?>">
        <img src="./images/arrow_<?php echo $text_dir; ?>.gif" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
        <i><?php echo $strWithChecked; ?></i>&nbsp;&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strChange; ?>" />
<?php
// Drop button if there is at least two fields
if ($fields_cnt > 1) {
    ?>
        &nbsp;<i><?php echo $strOr; ?></i>&nbsp;
        <input type="submit" name="submit_mult" value="<?php echo $strDrop; ?>" />
    <?php
}
echo "\n";
?>
    </td>
</tr>
</table>

</form>


<?php
/**
 * If there are more than 20 rows, displays browse/select/insert/empty/drop
 * links again
 */
if ($fields_cnt > 20) {
    ?>
<!-- Browse links -->
    <?php
    echo "\n";
    include('./tbl_properties_links.php3');
} // end if ($fields_cnt > 20)
echo "\n\n";


/**
 * Displays indexes
 */
?>
<!-- Indexes, space usage and row statistics -->
<br />
<table border="0" cellspacing="0" cellpadding="0">
<tr>
    <td>
<?php
define('PMA_IDX_INCLUDED', 1);
require ('./tbl_indexes.php3');
?>
    </td>

<?php
/**
 * Displays Space usage and row statistics
 */
// BEGIN - Calc Table Space - staybyte - 9 June 2001
// loic1, 22 feb. 2002: updated with patch from
//                      Joshua Nye <josh at boxcarmedia.com> to get valid
//                      statistics whatever is the table type
if ($cfg['ShowStats']) {
    $nonisam     = FALSE;
    $is_innodb   = ($showtable['Type'] == 'InnoDB');
    if (isset($showtable['Type']) && !eregi('ISAM|HEAP', $showtable['Type'])) {
        $nonisam = TRUE;
    }
    if (PMA_MYSQL_INT_VERSION >= 32303 && ($nonisam == FALSE || $is_innodb)) {
        // Gets some sizes
        $mergetable     = FALSE;
        if (isset($showtable['Type']) && $showtable['Type'] == 'MRG_MyISAM') {
            $mergetable = TRUE;
        }
        list($data_size, $data_unit)         = PMA_formatByteDown($showtable['Data_length']);
        if ($mergetable == FALSE) {
            list($index_size, $index_unit)   = PMA_formatByteDown($showtable['Index_length']);
        }
        if (isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
            list($free_size, $free_unit)     = PMA_formatByteDown($showtable['Data_free']);
            list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free']);
        } else {
            list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length']);
        }
        list($tot_size, $tot_unit)           = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length']);
        if ($num_rows > 0) {
            list($avg_size, $avg_unit)       = PMA_formatByteDown(($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1);
        }

        // Displays them
        ?>

    <!-- Space usage -->
    <td width="20">&nbsp;</td>
    <td valign="top">
        <?php echo $strSpaceUsage . '&nbsp;:' . "\n"; ?>
        <a name="showusage"></a>
        <table border="<?php echo $cfg['Border']; ?>">
        <tr>
            <th><?php echo $strType; ?></th>
            <th colspan="2" align="center"><?php echo $strUsage; ?></th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" style="padding-right: 10px"><?php echo ucfirst($strData); ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right" nowrap="nowrap"><?php echo $data_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><?php echo $data_unit; ?></td>
        </tr>
        <?php
        if (isset($index_size)) {
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" style="padding-right: 10px"><?php echo ucfirst($strIndex); ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right" nowrap="nowrap"><?php echo $index_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><?php echo $index_unit; ?></td>
        </tr>
            <?php
        }
        if (isset($free_size)) {
            echo "\n";
            ?>
        <tr style="color: #bb0000">
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" style="padding-right: 10px"><?php echo ucfirst($strOverhead); ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right" nowrap="nowrap"><?php echo $free_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><?php echo $free_unit; ?></td>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" style="padding-right: 10px"><?php echo ucfirst($strEffective); ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right" nowrap="nowrap"><?php echo $effect_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><?php echo $effect_unit; ?></td>
        </tr>
            <?php
        }
        if (isset($tot_size) && $mergetable == FALSE) {
            echo "\n";
        ?>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" style="padding-right: 10px"><?php echo ucfirst($strTotal); ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right" nowrap="nowrap"><?php echo $tot_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><?php echo $tot_unit; ?></td>
        </tr>
            <?php
        }
        // Optimize link if overhead
        if (isset($free_size) && ($tbl_type == 'MYISAM' || $tbl_type == 'BDB')) {
            echo "\n";
            ?>
        <tr>
            <td colspan="3" align="center">
                [<a href="sql.php3?<?php echo $url_query; ?>&amp;pos=0&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . PMA_backquote($table)); ?>"><?php echo $strOptimizeTable; ?></a>]
            </td>
        </tr>
            <?php
        }
        echo "\n";
        ?>
        </table>
    </td>

    <!-- Rows Statistic -->
    <td width="20">&nbsp;</td>
    <td valign="top">
        <?php echo $strRowsStatistic . '&nbsp;:' . "\n"; ?>
        <table border="<?php echo $cfg['Border']; ?>">
        <tr>
            <th><?php echo $strStatement; ?></th>
            <th align="center"><?php echo $strValue; ?></th>
        </tr>
        <?php
        $i = 0;
        if (isset($showtable['Row_format'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo ucfirst($strFormat); ?></td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="<?php echo $cell_align_left; ?>" nowrap="nowrap">
            <?php
            echo '                ';
            if ($showtable['Row_format'] == 'Fixed') {
                echo $strFixed;
            }
            else if ($showtable['Row_format'] == 'Dynamic') {
                echo $strDynamic;
            }
            else {
                echo $showtable['Row_format'];
            }
            echo "\n";
            ?>
            </td>
        </tr>
            <?php
        }
        if (!$is_innodb && isset($showtable['Rows'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo ucfirst($strRows); ?></td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        if (!$is_innodb && isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo ucfirst($strRowLength); ?>&nbsp;&oslash;</td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Avg_row_length'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        if (!$is_innodb && isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == FALSE) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo ucfirst($strRowSize); ?>&nbsp;&oslash;</td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="right" nowrap="nowrap">
                <?php echo $avg_size . ' ' . $avg_unit . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        if (isset($showtable['Auto_increment'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo ucfirst($strNext); ?>&nbsp;Autoindex</td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Auto_increment'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        echo "\n";
        ?>
        </table>
    </td>
        <?php
    }
}
// END - Calc Table Space
echo "\n";
?>
</tr>
</table>
<hr />



<?php
/**
 * Work on the table
 */
// loic1: defines wether file upload is available or not
$is_upload = (PMA_PHP_INT_VERSION >= 40000 && function_exists('ini_get'))
           ? ((strtolower(ini_get('file_uploads')) == 'on' || ini_get('file_uploads') == 1) && intval(ini_get('upload_max_filesize')))
           // loic1: php 3.0.15 and lower bug -> always enabled
           : (PMA_PHP_INT_VERSION < 30016 || intval(@get_cfg_var('upload_max_filesize')));
?>
<!-- TABLE WORK -->
<ul>

    <!-- Printable view of the table -->
    <li>
        <div style="margin-bottom: 10px"><a href="tbl_printview.php3?<?php echo $url_query; ?>"><?php echo $strPrintView; ?></a></div>
    </li>

    <!-- Query box and bookmark support -->
    <li>
        <a name="querybox"></a>
        <form method="post" action="read_dump.php3"<?php if ($is_upload) echo ' enctype="multipart/form-data"'; echo "\n"; ?>
            onsubmit="return checkSqlQuery(this)">
            <input type="hidden" name="is_js_confirmed" value="0" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="goto" value="tbl_properties.php3" />
            <input type="hidden" name="zero_rows" value="<?php echo $strSuccess; ?>" />
            <input type="hidden" name="prev_sql_query" value="<?php echo ((!empty($query_to_display)) ? urlencode($query_to_display) : ''); ?>" />
            <?php echo sprintf($strRunSQLQuery,  htmlspecialchars($db)) . ' ' . PMA_showDocuShort('S/E/SELECT.html'); ?>&nbsp;:<br />
            <div style="margin-bottom: 5px">
<textarea name="sql_query" rows="<?php echo $cfg['TextareaRows']; ?>" cols="<?php echo $cfg['TextareaCols']; ?>" wrap="virtual"
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

</ul>

<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
