<?php
/* $Id$ */


/**
 * Gets some core libraries and diplays headers
 */
require('./grab_globals.inc.php3');
require('./lib.inc.php3');
if (!isset($message)) {
    include('./header.inc.php3');
} else {
    show_message($message);
}


/**
 * Defines the query to be displayed in the query textarea
 */
if (isset($show_query) && $show_query == 'y') {
    // This script has been called by db_readdump.php3
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
 * Selects the db that will be used during this script execution
 */
// mysql_select_db($db) or mysql_die();
$is_db = @mysql_select_db($db);
// Not a valid db name -> back to the welcome page
if (!$is_db) {
    header('Location: main.php3?lang=' . $lang . '&server=' . $server . '&reload=true');
    exit();
}
$is_table = @mysql_query('SHOW TABLES LIKE \'' . sql_addslashes($table, TRUE) . '\'');
// Not a valid table name -> back to the db_details.php3
if (!@mysql_numrows($is_table)) {
    header('Location: db_details.php3?lang=' . $lang . '&server=' . $server . '&db=' . urlencode($db) . '&reload=true');
    exit();
}


/**
 * Set parameters for links
 */
$url_query = 'lang=' . $lang
           . '&server=' . urlencode($server)
           . '&db=' . urlencode($db)
           . '&table=' . urlencode($table)
           . '&goto=tbl_properties.php3';


/**
 * Gets table informations
 */
// 1. Get table type and comments ('show table' works correct since 3.23.03)
if (MYSQL_MAJOR_VERSION >= 3.23 && intval(MYSQL_MINOR_VERSION) >= 3) {
    // Update table type, comment and order if required by the user
    if (isset($submitcomment)) {
        if (get_magic_quotes_gpc()) {
            $comment = stripslashes($comment);
        }
        if (empty($prev_comment) || urldecode($prev_comment) != str_replace('&quot;', '"', $comment)) {
            $result = mysql_query('ALTER TABLE ' . backquote($table) . ' COMMENT = \'' . sql_addslashes($comment) . '\'') or mysql_die();
        }
    }
    if (isset($submittype)) {
        $result = mysql_query('ALTER TABLE ' . backquote($table) . " TYPE=$tbl_type") or mysql_die();
    }
    if (isset($submitorderby) && !empty($order_field)) {
        $order_field = backquote(urldecode($order_field));
        $result      = mysql_query('ALTER TABLE ' . backquote($table) . 'ORDER BY ' . $order_field) or mysql_die();
    }

    // Get table type and comments and displays first browse links
    $result       = mysql_query('SHOW TABLE STATUS LIKE \'' . sql_addslashes($table, TRUE) . '\'') or mysql_die();
    $showtable    = mysql_fetch_array($result);
    $tbl_type     = strtoupper($showtable['Type']);

    if (isset($showtable['Rows']) && $showtable['Rows'] > 0) {
        echo "\n";
        ?>
<!-- first browse links --> 
<p>
    [ <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('SELECT * FROM ' . backquote($table)); ?>&pos=0">
        <b><?php echo $strBrowse; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_select.php3?<?php echo $url_query; ?>">
        <b><?php echo $strSelect; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="tbl_change.php3?<?php echo $url_query; ?>">
        <b><?php echo $strInsert; ?></b></a> ]&nbsp;&nbsp;&nbsp;
    [ <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('DELETE FROM ' . backquote($table)); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenEmptied); ?>">
         <b><?php echo $strEmpty; ?></b></a> ]
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
    [ <b><?php echo $strEmpty; ?></b> ]
</p>
        <?php
    }
    echo "\n";

    if (!empty($showtable['Comment'])) {
        $show_comment = $showtable['Comment'];
        ?>

<!-- Table comment -->
<p><i>
    <?php echo $show_comment . "\n"; ?>
</i></p>
        <?php
    } else {
        $show_comment = '';
    }
}

// 2. Get table keys and retains them
$result  = mysql_query('SHOW KEYS FROM ' . backquote($table)) or mysql_die();
$primary = '';
while($row = mysql_fetch_array($result)) {
    $ret_keys[]  = $row;
    if ($row['Key_name'] == 'PRIMARY') {
        $primary .= $row['Column_name'] . ', ';
    }
}

// 3. Get fields
$result = mysql_query('SHOW FIELDS FROM ' . backquote($table)) or mysql_die();


/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 */
?>



<!-- TABLE INFORMATIONS -->
<table border="<?php echo $cfgBorder; ?>">
<tr>
    <th><?php echo ucfirst($strField); ?></th>
    <th><?php echo ucfirst($strType); ?></th>
    <th><?php echo ucfirst($strAttr); ?></th>
    <th><?php echo ucfirst($strNull); ?></th>
    <th><?php echo ucfirst($strDefault); ?></th>
    <th><?php echo ucfirst($strExtra); ?></th>
<?php
if (empty($printer_friendly)) {
    ?>
    <th colspan="5"><?php echo ucfirst($strAction); ?></th>
    <?php
}
echo "\n";
?>
</tr>

<?php
$i         = 0;
$aryFields = array();

while ($row = mysql_fetch_array($result)) {
    $i++;
    $bgcolor          = ($i % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
    $aryFields[]      = $row['Field'];

    $type             = $row['Type'];
    // reformat mysql query output - staybyte - 9. June 2001
    $shorttype        = substr($type, 0, 3);
    if ($shorttype == 'set' || $shorttype == 'enu') {
        $type         = eregi_replace(',', ', ', $type);
        // Removes automatic MySQL escape format
        $type         = str_replace('\'\'', '\\\'', $type);
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
    echo "\n";
    ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
    <td nowrap="nowrap"><?php echo htmlspecialchars($row['Field']); ?>&nbsp;</td>
    <td<?php echo $type_nowrap; ?>><?php echo $type; ?></td>
    <td nowrap="nowrap"><?php echo $strAttribute; ?></td>
    <td><?php echo (($row['Null'] == '') ? $strNo : $strYes); ?>&nbsp;</td>
    <td nowrap="nowrap"><?php if (isset($row['Default'])) echo htmlspecialchars($row['Default']); ?>&nbsp;</td>
    <td nowrap="nowrap"><?php echo $row['Extra']; ?>&nbsp;</td>
    <?php
    if (empty($printer_friendly)) {
        echo "\n";
        ?>
    <td>
        <a href="tbl_alter.php3?<?php echo $url_query; ?>&field=<?php echo urlencode($row['Field']); ?>">
            <?php echo $strChange; ?></a>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('ALTER TABLE ' . backquote($table) . ' DROP ' . backquote($row['Field'])); ?>&zero_rows=<?php echo urlencode(htmlspecialchars($row['Field']) . ' ' . $strHasBeenDropped); ?>">
            <?php echo $strDrop; ?></a>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('ALTER TABLE ' . backquote($table) . ' DROP PRIMARY KEY, ADD PRIMARY KEY(' . $primary . backquote($row['Field']) . ')'); ?>&zero_rows=<?php echo urlencode($strAPrimaryKey . ' ' . htmlspecialchars($row['Field'])); ?>">
            <?php echo $strPrimary; ?></a>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('ALTER TABLE ' . backquote($table) . ' ADD INDEX(' . backquote($row['Field']) . ')'); ?>&zero_rows=<?php echo urlencode($strAnIndex . ' ' . htmlspecialchars($row['Field'])); ?>">
            <?php echo $strIndex; ?></a>
    </td>
    <td>
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('ALTER TABLE ' . backquote($table) . ' ADD UNIQUE(' . backquote($row['Field']) . ')'); ?>&zero_rows=<?php echo urlencode($strAnIndex . ' ' . htmlspecialchars($row['Field'])); ?>">
            <?php echo $strUnique; ?></a>
    </td>
        <?php
    }
    echo "\n";
    ?>
</tr>
    <?php
} // end while
echo "\n";
?>
</table>
<br />


<?php
/**
 * Displays indexes
 */
?>
<!-- Indexes, space usage and row statistics -->
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<?php
$index_count = (isset($ret_keys))
             ? count($ret_keys)
             : 0;
if ($index_count > 0) {
    ?>

    <!-- Indexes -->
    <td valign="top" align="left">
        <?php echo $strIndexes . '&nbsp;:' . "\n"; ?>
        <table border="<?php echo $cfgBorder; ?>">
        <tr>
            <th><?php echo $strKeyname; ?></th>
            <th><?php echo $strUnique; ?></th>
            <th><?php echo $strField; ?></th>
            <th><?php echo $strAction; ?></th>
        </tr>
    <?php
    for ($i = 0; $i < $index_count; $i++) {
        $row = $ret_keys[$i];
        if ($row['Key_name'] == 'PRIMARY') {
            $sql_query = urlencode('ALTER TABLE ' . backquote($table) . ' DROP PRIMARY KEY');
            $zero_rows = urlencode($strPrimaryKey . ' ' . $strHasBeenDropped);
        } else {
            $sql_query = urlencode('ALTER TABLE ' . backquote($table) . ' DROP INDEX ' . backquote($row['Key_name']));
            $zero_rows = urlencode($strIndex . ' ' . htmlspecialchars($row['Key_name']) . ' ' . $strHasBeenDropped);
        }
        echo "\n";
        ?>
        <tr>
            <td><?php echo htmlspecialchars($row['Key_name']) . "\n"; ?></td>
            <td><?php echo (($row['Non_unique'] == '0') ? $strYes : $strNo) . "\n"; ?></td>
            <td><?php echo htmlspecialchars($row['Column_name']) . "\n"; ?></td>
            <td>
                <?php echo "<a href=\"sql.php3?$url_query&sql_query=$sql_query&zero_rows=$zero_rows\">$strDrop</a>\n"; ?>
            </td>
        </tr>
        <?php
    }
    echo "\n";
    ?>
        </table>
        <?php echo show_docu('manual_Performance.html#MySQL_indexes') . "\n"; ?>
    </td>
    <?php
}


/**
 * Displays Space usage and row statistics
 */
?>
<?php
// BEGIN - Calc Table Space - staybyte - 9 June 2001
$nonisam     = FALSE;
if (!eregi('ISAM|HEAP', $showtable['Type'])) {
    $nonisam = TRUE;
}
if (MYSQL_MAJOR_VERSION >= 3.23 && intval(MYSQL_MINOR_VERSION) > 3 && $nonisam == FALSE && isset($showtable)) {
    // Gets some sizes
    $mergetable     = FALSE;
    if (isset($showtable['Type']) && $showtable['Type'] == 'MRG_MyISAM') {
        $mergetable = TRUE;
    }
    list($data_size, $data_unit)       = format_byte_down($showtable['Data_length']);
    if ($mergetable == FALSE) {
        list($index_size, $index_unit) = format_byte_down($showtable['Index_length']);
    }
    if (isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
        list($free_size, $free_unit)   = format_byte_down($showtable['Data_free']);
    }
    list($effect_size, $effect_unit)   = format_byte_down($showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free']);
    list($tot_size, $tot_unit)         = format_byte_down($showtable['Data_length'] + $showtable['Index_length']);
    if (isset($showtable['Rows']) && $showtable['Rows']>0) {
        list($avg_size, $avg_unit)     = format_byte_down(($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1);
    }

    // Displays them
    if ($index_count > 0) {
        echo '    <td width="20">&nbsp;</td>' . "\n";
    }
    ?>

    <!-- Space usage -->
    <td valign="top">
        <?php echo $strSpaceUsage . '&nbsp;:' . "\n"; ?>
        <a name="showusage"></a>
        <table border="<?php echo $cfgBorder; ?>">
        <tr>
            <th><?php echo $strType; ?></th>
            <th colspan="2" align="center"><?php echo $strUsage; ?></th>
        </tr>
        <tr bgcolor="<?php echo $cfgBgcolorTwo; ?>">
            <td style="padding-right: 10px"><?php echo ucfirst($strData); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $data_size; ?></td>
            <td><?php echo $data_unit; ?></td>
        </tr>
    <?php
    echo "\n";
    if (isset($index_size)) {
        ?>
        <tr bgcolor="<?php echo $cfgBgcolorTwo; ?>">
            <td style="padding-right: 10px"><?php echo ucfirst($strIndex); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $index_size; ?></td>
            <td><?php echo $index_unit; ?></td>
        </tr>
        <?php
    }
    echo "\n";
    if (isset($free_size)) {
        ?>
        <tr bgcolor="<?php echo $cfgBgcolorTwo; ?>" style="color: #bb0000">
        <td style="padding-right: 10px"><?php echo ucfirst($strOverhead); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $free_size; ?></td>
            <td><?php echo $free_unit; ?></td>
        </tr>
        <tr bgcolor="<?php echo $cfgBgcolorOne; ?>">
        <td style="padding-right: 10px"><?php echo ucfirst($strEffective); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $effect_size; ?></td>
            <td><?php echo $effect_unit; ?></td>
        </tr>
        <?php
    }
    if (isset($tot_size) && $mergetable == FALSE) {
        echo "\n";
        ?>
        <tr bgcolor="<?php echo $cfgBgcolorOne; ?>">
        <td style="padding-right: 10px"><?php echo ucfirst($strTotal); ?></td>
            <td align="right" nowrap="nowrap"><?php echo $tot_size; ?></td>
            <td><?php echo $tot_unit; ?></td>
        </tr>
        <?php
    }
    // Optimize link if overhead
    if (isset($free_size) && ($tbl_type == 'MYISAM' || $tbl_type == 'BDB')) {
        echo "\n";
        ?>
        <tr>
            <td colspan="3" align="center">
                [<a href="sql.php3?<?php echo $url_query; ?>&pos=0&sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . backquote($table)); ?>&display=simple"><?php echo $strOptimizeTable; ?></a>]
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
        echo (++$i%2)
             ? '    <tr bgcolor="' . $cfgBgcolorTwo . '">'
             : '    <tr bgcolor="' . $cfgBgcolorTwo . '">';
        echo "\n";
        ?>
            <td><?php echo ucfirst($strFormat); ?></td>
            <td align="right" nowrap="nowrap">
        <?php
        echo '        ';
        if ($showtable['Row_format'] == 'Fixed') {
            echo $strFixed;
        }
        else if ($showtable['Row_format'] == 'Dynamic') {
            echo $strDynamic;
        }
        else {
            echo $showtable['Row_format'];
        }
        ?>
            </td>
        </tr>
        <?php
    }
    if (isset($showtable['Rows'])) {
        echo (++$i%2)
             ? '    <tr bgcolor="' . $cfgBgcolorTwo . '">'
             : '    <tr bgcolor="' . $cfgBgcolorOne . '">';
        echo "\n";
        ?>
            <td><?php echo ucfirst($strRows); ?></td>
            <td align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
        <?php
    }
    if (isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
        echo (++$i%2)
             ? '    <tr bgcolor="' . $cfgBgcolorTwo . '">'
             : '    <tr bgcolor="' . $cfgBgcolorOne . '">';
        echo "\n";
        ?>
            <td><?php echo ucfirst($strRowLength); ?>&nbsp;&oslash;</td>
            <td align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Avg_row_length'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
        <?php
    }
    if (isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == FALSE) {
        echo (++$i%2)
             ? '    <tr bgcolor="' . $cfgBgcolorTwo . '">'
             : '    <tr bgcolor="' . $cfgBgcolorOne . '">';
        echo "\n";
        ?>
            <td><?php echo ucfirst($strRowSize); ?>&nbsp;&oslash;</td>
            <td align="right" nowrap="nowrap">
                <?php echo "$avg_size $avg_unit\n"; ?>
            </td>
        </tr>
        <?php
    }
    if (isset($showtable['Auto_increment'])) {
        echo (++$i%2)
             ? '    <tr bgcolor="' . $cfgBgcolorTwo . '">'
             : '    <tr bgcolor="' . $cfgBgcolorOne . '">';
        echo "\n";
        ?>
            <td><?php echo ucfirst($strNext); ?>&nbsp;Autoindex</td>
            <td align="right" nowrap="nowrap">
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
<script type="text/javascript" language="javascript">
<!--
var errorMsg0 = '<?php echo(str_replace('\'', '\\\'', $strFormEmpty)); ?>';
var errorMsg1 = '<?php echo(str_replace('\'', '\\\'', $strNotNumber)); ?>';
var errorMsg2 = '<?php echo(str_replace('\'', '\\\'', $strNotValidNumber)); ?>';
//-->
</script>
<script src="functions.js" type="text/javascript" language="javascript"></script>

<ul>

    <!-- Printable view of the table -->
    <li>
        <div style="margin-bottom: 10px"><a href="tbl_printview.php3?<?php echo $url_query; ?>"><?php echo $strPrintView; ?></a></div>
    </li>

    <!-- Query box and bookmark support -->
    <li>
        <form method="post" action="db_readdump.php3"
            onsubmit="return emptySqlQuery(this)">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="pos" value="0" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="goto" value="tbl_properties.php3" />
            <input type="hidden" name="zero_rows" value="<?php echo $strSuccess; ?>" />
            <input type="hidden" name="prev_sql_query" value="<?php echo ((!empty($query_to_display)) ? urlencode($query_to_display) : ''); ?>" />
            <?php echo $strRunSQLQuery . htmlspecialchars($db) . ' ' . show_docu('manual_Reference.html#SELECT'); ?>&nbsp;:<br />
            <div style="margin-bottom: 5px">
<textarea name="sql_query" rows="<?php echo $cfgTextareaRows; ?>" cols="<?php echo $cfgTextareaCols; ?>" wrap="virtual">
<?php echo ((!empty($query_to_display)) ? htmlspecialchars($query_to_display) : 'SELECT * FROM ' . backquote($table) . ' WHERE 1'); ?>
</textarea><br />
            <input type="checkbox" name="show_query" value="y" checked="checked" />&nbsp;
                <?php echo $strShowThisQuery; ?><br />
            </div>
<?php
// Bookmark Support
if ($cfgBookmark['db'] && $cfgBookmark['table'])  {
    if (($bookmark_list = list_bookmarks($db, $cfgBookmark)) && count($bookmark_list) > 0) {
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

    <!-- Display, select, insert and empty -->
    <li>
        <div style="margin-bottom: 10px">
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('SELECT * FROM ' . backquote($table)); ?>&pos=0">
            <b><?php echo $strBrowse; ?></b></a>&nbsp;-&nbsp;
        <a href="tbl_select.php3?<?php echo $url_query; ?>">
            <b><?php echo $strSelect; ?></b></a>&nbsp;-&nbsp;
        <a href="tbl_change.php3?<?php echo $url_query; ?>">
            <b><?php echo $strInsert; ?></b></a>&nbsp;-&nbsp;
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('DELETE FROM ' . backquote($table)); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenEmptied); ?>">
            <b><?php echo $strEmpty; ?></b></a>
        <br />
        </div>
    </li>

    <!-- Add some new fields -->
    <li>
        <form method="post" action="tbl_addfield.php3"
            onsubmit="return checkFormElementInRange(this, 'num_fields', 1, 99)">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strAddNewField; ?>&nbsp;:
            <input name="num_fields" size="2" maxlength="2" value="1" style="vertical-align: middle" />
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
if (MYSQL_MAJOR_VERSION >= 3.23 && MYSQL_MINOR_VERSION >= 34) {
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
            <?php echo $strViewDumpDB; ?><br />
            <table cellpadding="5" border="2">
            <tr>
                <td nowrap="nowrap">
                    <input type="radio" name="what" value="structure" checked="checked" />
                    <?php echo $strStrucOnly; ?>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="data" />
                    <?php echo $strStrucData; ?>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="dataonly" />
                    <?php echo $strDataOnly; ?>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="csv" />
                    <?php echo $strStrucCSV;?>&nbsp;:<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strFields . ' '. $strTerminatedBy; ?>&nbsp;
                    <input type="text" name="separator" size="2" value=";" />&nbsp;&nbsp;<br />
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strLines . ' '. $strTerminatedBy; ?>&nbsp;
                    <input type="text" name="add_character" size="2" value="<?php echo ((which_crlf() == "\n") ? '\n' : '\r\n'); ?>" />&nbsp;&nbsp;
                </td>
                <td valign="middle">
                    <input type="checkbox" name="drop" value="1" />
                    <?php echo $strStrucDrop; ?><br />
                    <input type="checkbox" name="showcolumns" value="yes" />
                    <?php echo $strCompleteInserts; ?><br />
<?php
// Add backquotes checkbox
if (MYSQL_MAJOR_VERSION >= 3.23 && intval(MYSQL_MINOR_VERSION) >= 6) {
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
// gzip and bzip2 encode features
if (PMA_INT_VERSION >= 40004) {
    $is_gzip = (isset($cfgGZipDump) && $cfgGZipDump && @function_exists('gzencode'));
    $is_bzip = (isset($cfgBZipDump) && $cfgBZipDump && @function_exists('bzcompress'));
    if ($is_gzip || $is_bzip) {
        echo "\n" . '                    (' . "\n";
        if ($is_gzip) {
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
                    &nbsp;<?php echo $strData . '&nbsp;:&nbsp;' . $strOffSet; ?>&nbsp;
                    <input type="text" name="limit_from" value="0" size="5" style="vertical-align: middle" />
                    &nbsp;--&nbsp;<?php echo $strNbRecords; ?>&nbsp;
                    <input type="text" name="limit_to" size="5" value="<?php echo count_records($db, $table, TRUE); ?>" style="vertical-align: middle" />
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

    <!-- Change table name and copy table -->
    <li style="vertical-align: top">
        <div style="margin-bottom: 10px">
        <table border="0" cellspacing="0" cellpadding="0" style="vertical-align: top">
        <tr>
            <td valign="top">
            <form method="post" action="tbl_rename.php3"
                onsubmit="return emptyFormElements(this, 'new_name')">
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="db" value="<?php echo $db; ?>" />
                <input type="hidden" name="table" value="<?php echo $table; ?>" />
                <input type="hidden" name="reload" value="true" />
                <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td>
                        <?php echo $strRenameTable; ?>&nbsp;:
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="text" style="width: 100%" name="new_name" />
                    </td>
                </tr>
                <tr>
                    <td align="right" valign="bottom">
                        <input type="submit" value="<?php echo $strGo; ?>" />
                    </td>
                </tr>
                </table>
            </form>
            </td>
            <td width="25">&nbsp;</td>
            <td valign="top">
            <form method="post" action="tbl_copy.php3"
                onsubmit="return emptyFormElements(this, 'new_name')">
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="db" value="<?php echo $db; ?>" />
                <input type="hidden" name="table" value="<?php echo $table; ?>" />
                <input type="hidden" name="reload" value="true" />
                <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td colspan="2">
                        <?php echo $strCopyTable; ?>&nbsp;:
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="text" style="width: 100%" name="new_name" />
                    </td>
                </tr>
                <tr>
                    <td nowrap="nowrap">
                        <input type="radio" name="what" value="structure" checked="checked" />
                        <?php echo $strStrucOnly; ?>&nbsp;&nbsp;<br />
                        <input type="radio" name="what" value="data" />
                        <?php echo $strStrucData; ?>&nbsp;&nbsp;
                    </td>
                    <td align="right" valign="top">
                        <input type="submit" value="<?php echo $strGo; ?>" />
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
if (MYSQL_MAJOR_VERSION >= 3.23 && intval(MYSQL_MINOR_VERSION) >= 22) {
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
                <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('CHECK TABLE ' . backquote($table)); ?>&display=simple">
                    <?php echo $strCheckTable; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#CHECK_TABLE') . "\n"; ?>
            </td>
            <td>&nbsp;-&nbsp;</td>
            <?php
        }
        echo "\n";
        if ($tbl_type == 'MYISAM' || $tbl_type == 'BDB') {
            ?>
            <td>
                <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('ANALYZE TABLE ' . backquote($table)); ?>&display=simple">
                    <?php echo $strAnalyzeTable; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#ANALYZE_TABLE') . "\n";?>
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
                <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('REPAIR TABLE ' . backquote($table)); ?>&display=simple">
                    <?php echo $strRepairTable; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#REPAIR_TABLE') . "\n"; ?>
            </td>
            <td>&nbsp;-&nbsp;</td>
            <?php
        }
        echo "\n";
        if ($tbl_type == 'MYISAM' || $tbl_type == 'BDB') {
            ?>
            <td>
                <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . backquote($table)); ?>&display=simple">
                    <?php echo $strOptimizeTable; ?></a>&nbsp;
                <?php echo show_docu('manual_Reference.html#OPTIMIZE_TABLE') . "\n"; ?>
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
            <input type="text" name="comment" maxlength="60" size="30" value="<?php echo str_replace('"', '&quot;', $show_comment); ?>" style="vertical-align: middle" />&nbsp;
            <input type="submit" name="submitcomment" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
        </form>
    </li>

    <!-- Table type -->
    <?php
    // modify robbat2 code - staybyte - 11. June 2001
    $query  = "SHOW VARIABLES LIKE 'have_%'";
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
            <input type="submit" name="submittype" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
        </form>
    </li>
    <?php
    echo "\n";
} // end MySQL >= 3.23

else { // MySQL < 3.23
    // FIXME: find a way to know the table type, then let OPTIMIZE if MYISAM or
    // BDB
    ?>
    <!-- Table maintenance -->
    <li>
        <?php echo $strTableMaintenance; ?>&nbsp;:&nbsp;
        <a href="sql.php3?<?php echo $url_query; ?>&sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . backquote($table)); ?>&display=simple">
            <?php echo $strOptimizeTable; ?></a>&nbsp;
        <?php echo show_docu('manual_Reference.html#OPTIMIZE_TABLE') . "\n"; ?>
    </li>
    <?php
    echo "\n";
} // end MySQL < 3.23
?>

    <!-- Deletes the table -->
    <li>
        <a href="sql.php3?<?php echo ereg_replace('tbl_properties.php3$', 'db_details.php3', $url_query); ?>&back=tbl_properties.php3&reload=true&sql_query=<?php echo urlencode('DROP TABLE ' . backquote($table)); ?>&zero_rows=<?php echo urlencode($strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenDropped); ?>">
            <?php echo $strDrop . ' ' . htmlspecialchars($table); ?></a> 
    </li> 

</ul>

<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
