<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./libraries/bookmark.lib.php3');


/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = 'db_details.php3'
           . '?lang=' . $lang
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db);
$err_url   = 'tbl_properties.php3'
           . '?lang=' . $lang
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db)
           . '&amp;table=' . urlencode($table);


/**
 * Ensures the database and the table exist (else move to the "parent" script)
 * and diplays headers
 */
if (!isset($is_db) || !$is_db) {
    // Not a valid db name -> back to the welcome page
    if (!empty($db)) {
        $is_db = @mysql_select_db($db);
    }
    if (empty($db) || !$is_db) {
        header('Location: ' . $cfgPmaAbsoluteUri . 'main.php3?lang=' . $lang . '&server=' . $server . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        exit();
    }
} // end if (ensures db exists)
if (!isset($is_table) || !$is_table) {
    // Not a valid table name -> back to the db_details.php3
    if (!empty($table)) {
        $is_table = @mysql_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'');
    }
    if (empty($table) || !@mysql_numrows($is_table)) {
        header('Location: ' . $cfgPmaAbsoluteUri . 'db_details.php3?lang=' . $lang . '&server=' . $server . '&db=' . urlencode($db) . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        exit();
    } else if (isset($is_table)) {
        mysql_free_result($is_table);
    }
} // end if (ensures table exists)

// Displays headers
if (!isset($message)) {
    $js_to_run = 'functions.js';
    include('./header.inc.php3');
} else {
    PMA_showMessage($message);
}


/**
 * Drop/delete mutliple tables if required
 */
if ((!empty($submit_mult) && isset($selected_fld))
    || isset($mult_btnDrop)) {
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
 * Set parameters for links
 */
$url_query = 'lang=' . $lang
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db)
           . '&amp;table=' . urlencode($table)
           . '&amp;goto=tbl_properties.php3';


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


/**
 * Gets table informations and displays these informations and also top
 * browse/select/insert/empty links
 */
// The 'show table' statement works correct since 3.23.03
if (PMA_MYSQL_INT_VERSION >= 32303) {
    $local_query  = 'SHOW TABLE STATUS LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'';
    $result       = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
    $showtable    = mysql_fetch_array($result);
    $tbl_type     = strtoupper($showtable['Type']);
    $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
    $show_comment = (isset($showtable['Comment']) ? $showtable['Comment'] : '');
} else {
    $local_query  = 'SELECT COUNT(*) AS count FROM ' . PMA_backquote($table);
    $result       = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
    $showtable    = array();
    $num_rows     = mysql_result($result, 0, 'count');
    $show_comment = '';
}
mysql_free_result($result);
?>

<?php
if ($num_rows > 0) {
    echo "\n";
    ?>
<!-- first browse links --> 
<p>
    [ <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0">
        <b><?php echo $strBrowse; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_select.php3?<?php echo $url_query; ?>">
        <b><?php echo $strSelect; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_change.php3?<?php echo $url_query; ?>">
        <b><?php echo $strInsert; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('DELETE FROM ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table))); ?>"
         onclick="return confirmLink(this, 'DELETE FROM <?php echo PMA_jsFormat($table); ?>')">
         <b><?php echo $strEmpty; ?></b></a> ]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&amp;back=tbl_properties.php3&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
         onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
         <b><?php echo $strDrop; ?></b></a> ]
</p>
    <?php
} else {
    echo "\n";
    ?>
<!-- first browse links -->
<p>
    [ <b><?php echo $strBrowse; ?></b> ]&nbsp;&nbsp;&nbsp;
    [ <b><?php echo $strSelect; ?></b> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_change.php3?<?php echo $url_query; ?>">
        <b><?php echo $strInsert; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <b><?php echo $strEmpty; ?></b> ]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&amp;back=tbl_properties.php3&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
         onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
         <b><?php echo $strDrop; ?></b></a> ]
</p>
    <?php
}
echo "\n";

if (!empty($show_comment)) {
    ?>
<!-- Table comment -->
<p><i>
    <?php echo $show_comment . "\n"; ?>
</i></p>
    <?php
} // end (1.)

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

<table border="<?php echo $cfgBorder; ?>">
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
$aryFields = array();

while ($row = mysql_fetch_array($fields_rs)) {
    $i++;
    $bgcolor          = ($i % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
    $aryFields[]      = $row['Field'];

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
    $field_name = htmlspecialchars($row['Field']);
    if (isset($pk_array[$row['Field']])) {
        $field_name = '<u>' . $field_name . '</u>';
    }
    echo "\n";
    ?>
<tr>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <input type="checkbox" name="selected_fld[]" value="<?php echo urlencode($row['Field']); ?>" />
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">&nbsp;<?php echo $field_name; ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>"<?php echo $type_nowrap; ?>><?php echo $type; ?><bdo dir="ltr"></bdo></td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php echo $strAttribute; ?></td>
    <td bgcolor="<?php echo $bgcolor; ?>"><?php echo (($row['Null'] == '') ? $strNo : $strYes); ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php if (isset($row['Default'])) echo $row['Default']; ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php echo $row['Extra']; ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_alter.php3?<?php echo $url_query; ?>&amp;field=<?php echo urlencode($row['Field']); ?>">
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
    if ($num_rows > 0) {
        ?>
<!-- Browse links --> 
<p>
    [ <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0">
        <b><?php echo $strBrowse; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_select.php3?<?php echo $url_query; ?>">
        <b><?php echo $strSelect; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_change.php3?<?php echo $url_query; ?>">
        <b><?php echo $strInsert; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('DELETE FROM ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($table))); ?>"
         onclick="return confirmLink(this, 'DELETE FROM <?php echo PMA_jsFormat($table); ?>')">
         <b><?php echo $strEmpty; ?></b></a> ]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&amp;back=tbl_properties.php3&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
         onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
         <b><?php echo $strDrop; ?></b></a> ]
</p>
        <?php
    } else {
        echo "\n";
        ?>
<!-- first browse links -->
<p>
    [ <b><?php echo $strBrowse; ?></b> ]&nbsp;&nbsp;&nbsp;
    [ <b><?php echo $strSelect; ?></b> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_change.php3?<?php echo $url_query; ?>">
        <b><?php echo $strInsert; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <b><?php echo $strEmpty; ?></b> ]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&amp;back=tbl_properties.php3&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
         onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
         <b><?php echo $strDrop; ?></b></a> ]
</p>
        <?php
    } // end if...else
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
if ($cfgShowStats) {
    $nonisam     = FALSE;
    if (isset($showtable['Type']) && !eregi('ISAM|HEAP', $showtable['Type'])) {
        $nonisam = TRUE;
    }
    if (PMA_MYSQL_INT_VERSION >= 32303 && $nonisam == FALSE) {
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
        <table border="<?php echo $cfgBorder; ?>">
        <tr>
            <th><?php echo $strType; ?></th>
            <th colspan="2" align="center"><?php echo $strUsage; ?></th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>" style="padding-right: 10px"><?php echo ucfirst($strData); ?></td>
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>" align="right" nowrap="nowrap"><?php echo $data_size; ?></td>
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>"><?php echo $data_unit; ?></td>
        </tr>
        <?php
        if (isset($index_size)) {
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>" style="padding-right: 10px"><?php echo ucfirst($strIndex); ?></td>
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>" align="right" nowrap="nowrap"><?php echo $index_size; ?></td>
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>"><?php echo $index_unit; ?></td>
        </tr>
            <?php
        }
        if (isset($free_size)) {
            echo "\n";
            ?>
        <tr style="color: #bb0000">
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>" style="padding-right: 10px"><?php echo ucfirst($strOverhead); ?></td>
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>" align="right" nowrap="nowrap"><?php echo $free_size; ?></td>
            <td bgcolor="<?php echo $cfgBgcolorTwo; ?>"><?php echo $free_unit; ?></td>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfgBgcolorOne; ?>" style="padding-right: 10px"><?php echo ucfirst($strEffective); ?></td>
            <td bgcolor="<?php echo $cfgBgcolorOne; ?>" align="right" nowrap="nowrap"><?php echo $effect_size; ?></td>
            <td bgcolor="<?php echo $cfgBgcolorOne; ?>"><?php echo $effect_unit; ?></td>
        </tr>
            <?php
        }
        if (isset($tot_size) && $mergetable == FALSE) {
            echo "\n";
        ?>
        <tr>
            <td bgcolor="<?php echo $cfgBgcolorOne; ?>" style="padding-right: 10px"><?php echo ucfirst($strTotal); ?></td>
            <td bgcolor="<?php echo $cfgBgcolorOne; ?>" align="right" nowrap="nowrap"><?php echo $tot_size; ?></td>
            <td bgcolor="<?php echo $cfgBgcolorOne; ?>"><?php echo $tot_unit; ?></td>
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
        <tr>
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
        <table border="<?php echo $cfgBorder; ?>">
        <tr>
            <th><?php echo $strStatement; ?></th>
            <th align="center"><?php echo $strValue; ?></th>
        </tr>
        <?php
        $i = 0;
        if (isset($showtable['Row_format'])) {
            $bgcolor = ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne);
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
        if (isset($showtable['Rows'])) {
            $bgcolor = ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne);
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
        if (isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
            $bgcolor = ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne);
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
        if (isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == FALSE) {
            $bgcolor = ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne);
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
            $bgcolor = ((++$i%2) ? $cfgBgcolorTwo : $cfgBgcolorOne);
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
?>
<!-- TABLE WORK -->
<ul>

    <!-- Printable view of the table -->
    <li>
        <div style="margin-bottom: 10px"><a href="tbl_printview.php3?<?php echo $url_query; ?>"><?php echo $strPrintView; ?></a></div>
    </li>

    <!-- Query box and bookmark support -->
    <li>
        <form method="post" action="read_dump.php3" enctype="multipart/form-data"
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
            <?php echo sprintf($strRunSQLQuery,  htmlspecialchars($db)) . ' ' . PMA_showDocu('manual_Reference.html#SELECT'); ?>&nbsp;:<br />
            <div style="margin-bottom: 5px">
<textarea name="sql_query" rows="<?php echo $cfgTextareaRows; ?>" cols="<?php echo $cfgTextareaCols; ?>" wrap="virtual" onfocus="this.select()">
<?php echo ((!empty($query_to_display)) ? htmlspecialchars($query_to_display) : 'SELECT * FROM ' . PMA_backquote($table) . ' WHERE 1'); ?>
</textarea><br />
            <input type="checkbox" name="show_query" value="y" checked="checked" />&nbsp;
                <?php echo $strShowThisQuery; ?><br />
            </div>
<?php
// loic1: displays import dump feature only if file upload available
$is_upload = (PMA_PHP_INT_VERSION >= 40000 && function_exists('ini_get'))
           ? ((strtolower(ini_get('file_uploads')) == 'on' || ini_get('file_uploads') == 1) && intval(ini_get('upload_max_filesize')))
           : (intval(@get_cfg_var('upload_max_filesize')));
if ($is_upload) {
    echo '            <i>' . $strOr . '</i> ' . $strLocationTextfile . '&nbsp;:<br />' . "\n";
    ?>
            <div style="margin-bottom: 5px">
            <input type="file" name="sql_file" /><br />
            </div>
    <?php
} // end if
echo "\n";

// Bookmark Support
if ($cfgBookmark['db'] && $cfgBookmark['table']) {
    if (($bookmark_list = PMA_listBookmarks($db, $cfgBookmark)) && count($bookmark_list) > 0) {
        echo "            <i>$strOr</i> $strBookmarkQuery&nbsp;:<br />\n";
        echo '            <div style="margin-bottom: 5px">' . "\n";
        echo '            <select name="id_bookmark" style="vertical-align: middle">' . "\n";
        echo '                <option value=""></option>' . "\n";
        while (list($key, $value) = each($bookmark_list)) {
            echo '                <option value="' . $value . '">' . htmlentities($key) . '</option>' . "\n";
        }
        echo '            </select>' . "\n";
        echo '            <input type="radio" name="action_bookmark" value="0" checked="checked" style="vertical-align: middle" />' . $strSubmit . "\n";
        echo '            &nbsp;<input type="radio" name="action_bookmark" value="1" style="vertical-align: middle" />' . $strBookmarkView . "\n";
        echo '            &nbsp;<input type="radio" name="action_bookmark" value="2" style="vertical-align: middle" />' . $strDelete . "\n";
        echo '            <br />' . "\n";
        echo '            </div>' . "\n";
    }
}
?>
            <input type="submit" name="SQL" value="<?php echo $strGo; ?>" />
        </form>
    </li>

    <!-- Add some new fields -->
    <li>
        <form method="post" action="tbl_addfield.php3"
            onsubmit="return checkFormElementInRange(this, 'num_fields', 1)">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strAddNewField; ?>&nbsp;:
            <input type="text" name="num_fields" size="2" maxlength="2" value="1" style="vertical-align: middle" onfocus="this.select()" />
            <select name="after_field" style="vertical-align: middle">
                <option value="--end--"><?php echo $strAtEndOfTable; ?></option>
                <option value="--first--"><?php echo $strAtBeginningOfTable; ?></option>
<?php
reset($aryFields);
while (list($junk, $fieldname) = each($aryFields)) {
    echo '                <option value="' . urlencode($fieldname) . '">' . $strAfter . ' ' . htmlspecialchars($fieldname) . '</option>' . "\n";
}
?>
            </select>
            <input type="submit" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
        </form>
    </li>

<?php
if (PMA_MYSQL_INT_VERSION >= 32334) {
    ?>
    <!-- Order the table -->
    <li>
        <form method="post" action="tbl_properties.php3">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strAlterOrderBy; ?>&nbsp;:
            <select name="order_field" style="vertical-align: middle">
    <?php
    echo "\n";
    reset($aryFields);
    while (list($junk, $fieldname) = each($aryFields)) {
        echo '                <option value="' . urlencode($fieldname) . '">' . htmlspecialchars($fieldname) . '</option>' . "\n";
    }
    ?>
            </select>
            <input type="submit" name="submitorderby" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
            &nbsp;<?php echo $strSingly . "\n"; ?>
        </form>
    </li>
    <?php
}
echo "\n";
?>

    <!-- Insert a text file -->
    <li>
        <div style="margin-bottom: 10px"><a href="ldi_table.php3?<?php echo $url_query; ?>"><?php echo $strInsertTextfiles; ?></a></div>
    </li>

    <!-- Dump of a database -->
    <li>
        <form method="post" action="tbl_dump.php3" name="tbl_dump">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strViewDump; ?><br />
            <table cellpadding="5" border="2">
            <tr>
                <td nowrap="nowrap">
                    <input type="radio" name="what" value="structure" checked="checked" />
                    <?php echo $strStrucOnly; ?>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="data" />
                    <?php echo $strStrucData; ?>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="dataonly" />
                    <?php echo $strDataOnly; ?>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="excel" />
                    <?php echo $strStrucExcelCSV; ?>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="csv" />
                    <?php echo $strStrucCSV;?>&nbsp;:<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strFieldsTerminatedBy; ?>&nbsp;
                    <input type="text" name="separator" size="2" value=";" />&nbsp;&nbsp;<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strFieldsEnclosedBy; ?>&nbsp;
                    <input type="text" name="enclosed" size="1" value="&quot;" />&nbsp;&nbsp;<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strFieldsEscapedBy; ?>&nbsp;
                    <input type="text" name="escaped" size="2" value="\" />&nbsp;&nbsp;<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strLinesTerminatedBy; ?>&nbsp;
                    <input type="text" name="add_character" size="2" value="<?php echo ((PMA_whichCrlf() == "\n") ? '\n' : '\r\n'); ?>" />&nbsp;&nbsp;
                </td>
                <td valign="middle">
                    <input type="checkbox" name="drop" value="1" />
                    <?php echo $strStrucDrop; ?><br />
                    <input type="checkbox" name="showcolumns" value="yes" />
                    <?php echo $strCompleteInserts; ?><br />
                    <input type="checkbox" name="extended_ins" value="yes" />
                    <?php echo $strExtendedInserts; ?><br />
<?php
// Add backquotes checkbox
if (PMA_MYSQL_INT_VERSION >= 32306) {
    ?>
                    <input type="checkbox" name="use_backquotes" value="1" />
                    <?php echo $strUseBackquotes; ?><br />
    <?php
} // end backquotes feature
echo "\n";
?>
                    <br />
                    <input type="checkbox" name="asfile" value="sendit" onclick="return checkTransmitDump(this.form, 'transmit')" />
                    <?php echo $strSend . "\n"; ?>
<?php
// zip, gzip and bzip2 encode features
if (PMA_PHP_INT_VERSION >= 40004) {
    $is_zip  = (isset($cfgZipDump) && $cfgZipDump && @function_exists('gzcompress'));
    $is_gzip = (isset($cfgGZipDump) && $cfgGZipDump && @function_exists('gzencode'));
    $is_bzip = (isset($cfgBZipDump) && $cfgBZipDump && @function_exists('bzcompress'));
    if ($is_zip || $is_gzip || $is_bzip) {
        echo "\n" . '                    (' . "\n";
        if ($is_zip) {
            ?>
                    <input type="checkbox" name="zip" value="zip" onclick="return checkTransmitDump(this.form, 'zip')" /><?php echo $strZip . (($is_gzip || $is_bzip) ? '&nbsp;' : '') . "\n"; ?>
            <?php
        }
        if ($is_gzip) {
            echo "\n"
            ?>
                    <input type="checkbox" name="gzip" value="gzip" onclick="return checkTransmitDump(this.form, 'gzip')" /><?php echo $strGzip . (($is_bzip) ? '&nbsp;' : '') . "\n"; ?>
            <?php
        }
        if ($is_bzip) {
            echo "\n"
            ?>
                    <input type="checkbox" name="bzip" value="bzip" onclick="return checkTransmitDump(this.form, 'bzip')" /><?php echo $strBzip . "\n"; ?>
            <?php
        }
        echo "\n" . '                    )';
    }
}
echo "\n";
?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    &nbsp;<?php echo $strStartingRecord; ?>&nbsp;
                    <input type="text" name="limit_from" value="0" size="5" style="vertical-align: middle" onfocus="this.select()" />
                    &nbsp;--&nbsp;<?php echo $strNbRecords; ?>&nbsp;
                    <input type="text" name="limit_to" size="5" value="<?php echo PMA_countRecords($db, $table, TRUE); ?>" style="vertical-align: middle" onfocus="this.select()" />
                </td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type="submit" value="<?php echo $strGo; ?>" />
                </td>
            </tr>
            </table>
        </form>
    </li>

    <!-- Change table name -->
    <li>
        <div style="margin-bottom: 10px">
            <form method="post" action="tbl_rename.php3"
                onsubmit="return emptyFormElements(this, 'new_name')">
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="db" value="<?php echo $db; ?>" />
                <input type="hidden" name="table" value="<?php echo $table; ?>" />
                <input type="hidden" name="reload" value="1" />
                <?php echo $strRenameTable; ?>&nbsp;:
                <input type="text" size="20" name="new_name" value="<?php echo htmlspecialchars($table); ?>" onfocus="this.select()" />&nbsp;
                <input type="submit" value="<?php echo $strGo; ?>" />
            </form>
        </div>
    </li>

    <!-- Move and copy table -->
    <li style="vertical-align: top">
        <div style="margin-bottom: 10px">
        <table border="0" cellspacing="0" cellpadding="0" style="vertical-align: top">
        <tr>
            <td valign="top">
            <form method="post" action="tbl_move_copy.php3"
                onsubmit="return emptyFormElements(this, 'new_name')">
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="db" value="<?php echo $db; ?>" />
                <input type="hidden" name="table" value="<?php echo $table; ?>" />
                <input type="hidden" name="reload" value="1" />
                <input type="hidden" name="what" value="data" />
                <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td nowrap="nowrap">
                        <?php echo $strMoveTable . "\n"; ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <select name="target_db">
                            <option value=""></option>
<?php
// The function used below is defined in "common.lib.php3"
PMA_availableDatabases('main.php3?lang=' . $lang . '&amp;server=' . $server);
for ($i = 0; $i < $num_dbs; $i++) {
    echo '                            ';
    echo '<option value="' . str_replace('"', '&quot;', $dblist[$i]) . '">' . htmlspecialchars($dblist[$i]) . '</option>';
    echo "\n";
} // end for
?>
                        </select>
                        &nbsp;<b>.</b>&nbsp;
                        <input type="text" size="20" name="new_name" value="<?php echo $table; ?>" onfocus="this.select()" />
                    </td>
                </tr>
                <tr>
                    <td align="<?php echo $cell_align_right; ?>" valign="top">
                        <input type="submit" name="submit_move" value="<?php echo $strGo; ?>" />
                    </td>
                </tr>
                </table>
            </form>
            </td>
            <td width="25">&nbsp;</td>
            <td valign="top">
            <form method="post" action="tbl_move_copy.php3"
                onsubmit="return emptyFormElements(this, 'new_name')">
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="db" value="<?php echo $db; ?>" />
                <input type="hidden" name="table" value="<?php echo $table; ?>" />
                <input type="hidden" name="reload" value="1" />
                <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td colspan="2" nowrap="nowrap">
                        <?php echo $strCopyTable . "\n"; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <select name="target_db">
<?php
for ($i = 0; $i < $num_dbs; $i++) {
    echo '                            ';
    echo '<option value="' . str_replace('"', '&quot;', $dblist[$i]) . '"';
    if ($dblist[$i] == $db) {
        echo ' selected="selected"';
    }
    echo '>' . htmlspecialchars($dblist[$i]) . '</option>';
    echo "\n";
} // end for
?>
                        </select>
                        &nbsp;<b>.</b>&nbsp;
                        <input type="text" size="20" name="new_name" onfocus="this.select()" />
                    </td>
                </tr>
                <tr>
                    <td nowrap="nowrap">
                        <input type="radio" name="what" value="structure" checked="checked" />
                        <?php echo $strStrucOnly; ?>&nbsp;&nbsp;<br />
                        <input type="radio" name="what" value="data" />
                        <?php echo $strStrucData; ?>&nbsp;&nbsp;
                    </td>
                    <td align="<?php echo $cell_align_right; ?>" valign="top">
                        <input type="submit" name="submit_copy" value="<?php echo $strGo; ?>" />
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    </table>
    </div>
    </li>

<?php
if (PMA_MYSQL_INT_VERSION >= 32322) {
    if ($tbl_type == 'MYISAM' or $tbl_type == 'BDB') {
        ?>
    <!-- Table maintenance -->
    <li style="vertical-align: top">
        <div style="margin-bottom: 10px">
        <table border="0" cellspacing="0" cellpadding="0" style="vertical-align: top">
        <tr>
            <td><?php echo $strTableMaintenance; ?>&nbsp;:&nbsp;</td>
        <?php
        echo "\n";
        if ($tbl_type == 'MYISAM') {
            ?>
            <td>
                <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('CHECK TABLE ' . PMA_backquote($table)); ?>">
                    <?php echo $strCheckTable; ?></a>&nbsp;
                <?php echo PMA_showDocu('manual_Reference.html#CHECK_TABLE') . "\n"; ?>
            </td>
            <td>&nbsp;-&nbsp;</td>
            <?php
        }
        echo "\n";
        if ($tbl_type == 'MYISAM' || $tbl_type == 'BDB') {
            ?>
            <td>
                <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ANALYZE TABLE ' . PMA_backquote($table)); ?>">
                    <?php echo $strAnalyzeTable; ?></a>&nbsp;
                <?php echo PMA_showDocu('manual_Reference.html#ANALYZE_TABLE') . "\n";?>
            </td>
            <?php
        }
        echo "\n";
        ?>
        </tr>
        <tr>
            <td>&nbsp;</td>
        <?php
        echo "\n";
        if ($tbl_type == 'MYISAM') {
            ?>
            <td>
                <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('REPAIR TABLE ' . PMA_backquote($table)); ?>">
                    <?php echo $strRepairTable; ?></a>&nbsp;
                <?php echo PMA_showDocu('manual_Reference.html#REPAIR_TABLE') . "\n"; ?>
            </td>
            <td>&nbsp;-&nbsp;</td>
            <?php
        }
        echo "\n";
        if ($tbl_type == 'MYISAM' || $tbl_type == 'BDB') {
            ?>
            <td>
                <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . PMA_backquote($table)); ?>">
                    <?php echo $strOptimizeTable; ?></a>&nbsp;
                <?php echo PMA_showDocu('manual_Reference.html#OPTIMIZE_TABLE') . "\n"; ?>
            </td>
            <?php
        }
        echo "\n";
        ?>
        </tr>
        </table><br />
        </div>
    </li>
        <?php
    } // end MYISAM or BDB case
    echo "\n";
    ?>

    <!-- Table comments -->
    <li>
        <form method="post" action="tbl_properties.php3">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strTableComments; ?>&nbsp;:&nbsp;
            <input type="hidden" name="prev_comment" value="<?php echo urlencode($show_comment); ?>" />&nbsp;
            <input type="text" name="comment" maxlength="60" size="30" value="<?php echo str_replace('"', '&quot;', $show_comment); ?>" style="vertical-align: middle" onfocus="this.select()" />&nbsp;
            <input type="submit" name="submitcomment" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
        </form>
    </li>

    <!-- Table type -->
    <?php
    // modify robbat2 code - staybyte - 11. June 2001
    $query  = 'SHOW VARIABLES LIKE \'have_%\'';
    $result = mysql_query($query);
    if ($result != FALSE && mysql_num_rows($result) > 0) {
        while ($tmp = mysql_fetch_array($result)) {
            if (isset($tmp['Variable_name'])) {
                switch ($tmp['Variable_name']) {
                    case 'have_bdb':
                        if ($tmp['Value'] == 'YES') {
                            $tbl_bdb    = TRUE;
                        }
                        break;
                    case 'have_gemini':
                        if ($tmp['Value'] == 'YES') {
                            $tbl_gemini = TRUE;
                        }
                        break;
                    case 'have_innodb':
                        if ($tmp['Value'] == 'YES') {
                            $tbl_innodb = TRUE;
                        }
                        break;
                    case 'have_isam':
                        if ($tmp['Value'] == 'YES') {
                            $tbl_isam   = TRUE;
                        }
                        break;
                } // end switch
            } // end if isset($tmp['Variable_name'])
        } // end while
    } // end if $result

    mysql_free_result($result);
    echo "\n";
    ?>
    <li>
        <form method="post" action="tbl_properties.php3">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strTableType; ?>&nbsp;:&nbsp;
            <select name="tbl_type" style="vertical-align: middle">
                <option value="MYISAM"<?php if ($tbl_type == 'MYISAM') echo ' selected="selected"'; ?>>MyISAM</option>
                <option value="HEAP"<?php if ($tbl_type == 'HEAP') echo ' selected="selected"'; ?>>Heap</option>
                <?php if (isset($tbl_bdb)) { ?><option value="BDB"<?php if ($tbl_type == 'BERKELEYDB') echo ' selected="selected"'; ?>>Berkeley DB</option><?php } ?> 
                <?php if (isset($tbl_gemini)) { ?><option value="GEMINI"<?php if ($tbl_type == 'GEMINI') echo ' selected="selected"'; ?>>Gemini</option><?php } ?> 
                <?php if (isset($tbl_innodb)) { ?><option value="INNODB"<?php if ($tbl_type == 'INNODB') echo ' selected="selected"'; ?>>INNO DB</option><?php } ?> 
                <?php if (isset($tbl_isam)) { ?><option value="ISAM"<?php if ($tbl_type == 'ISAM') echo ' selected="selected"'; ?>>ISAM</option><?php } ?> 
                <option value="MERGE"<?php if ($tbl_type == 'MRG_MYISAM') echo ' selected="selected"'; ?>>Merge</option>
            </select>&nbsp;
            <input type="submit" name="submittype" value="<?php echo $strGo; ?>" style="vertical-align: middle" />&nbsp;
            <?php echo PMA_showDocu('manual_Table_types.html#Table_types') . "\n"; ?>
        </form>
    </li>
    <?php
    echo "\n";
} // end MySQL >= 3.23.22

// loic1: "OPTIMIZE" statement is available for MyISAM and BDB tables only and
//        MyISAM/BDB tables exists since MySQL 3.23.06/3.23.34
else if (PMA_MYSQL_INT_VERSION >= 32306
         && ($tbl_type == 'MYISAM' or $tbl_type == 'BDB')) {
    ?>
    <!-- Table maintenance -->
    <li style="vertical-align: top">
        <div style="margin-bottom: 10px">
        <?php echo $strTableMaintenance; ?>&nbsp;:&nbsp;
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . PMA_backquote($table)); ?>">
            <?php echo $strOptimizeTable; ?></a>&nbsp;
        <?php echo PMA_showDocu('manual_Reference.html#OPTIMIZE_TABLE') . "\n"; ?>
        </div>
    </li>
    <?php
    echo "\n";
} // end 3.23.06 < MySQL < 3.23.22
?>

    <!-- Flushes the table -->
    <li>
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('FLUSH TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenFlushed, htmlspecialchars($table))); if ($cfgShowTooltip) echo '&amp;reload=1'; ?>">
            <?php echo $strFlushTable; ?></a>
        <br /><br />
    </li>

    <!-- Deletes the table -->
    <li>
        <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&amp;back=tbl_properties.php3&amp;reload=1&amp;sql_query=<?php echo urlencode('DROP TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenDropped, htmlspecialchars($table))); ?>"
            onclick="return confirmLink(this, 'DROP TABLE <?php echo PMA_jsFormat($table); ?>')">
            <?php echo $strDropTable; ?></a>
    </li>

</ul>

<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
