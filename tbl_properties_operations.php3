<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Runs common work
 */
require('./tbl_properties_common.php3');
$err_url   = 'tbl_properties_operations.php3' . $err_url;
$url_query .= '&amp;goto=tbl_properties_operations.php3&amp;back=tbl_properties_operations.php3';


/**
 * Gets relation settings
 */
require('./libraries/relation.lib.php3');
$cfgRelation = PMA_getRelationsParam();


/**
 * Reordering the table has been requested by the user
 */
if (isset($submitorderby) && !empty($order_field)) {
    $sql_query   = 'ALTER TABLE ' . PMA_backquote($table)
                 . ' ORDER BY ' . PMA_backquote(urldecode($order_field));
    $result      = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $err_url);
    PMA_showMessage((get_magic_quotes_gpc()) ? addslashes($strSuccess) : $strSuccess);
} // end if


/**
 * Gets tables informations and displays top links
 */
require('./tbl_properties_table_info.php3');


/**
 * Get columns names
 */
$local_query = 'SHOW COLUMNS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db);
$result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
for ($i = 0; $row = PMA_mysql_fetch_array($result); $i++) {
        $columns[$i] = $row['Field'];
}
mysql_free_result($result);


/**
 * Displays the page
 */
?>
<ul>

<?php
if (PMA_MYSQL_INT_VERSION >= 32334) {
    ?>
    <!-- Order the table -->
    <li>
        <form method="post" action="tbl_properties_operations.php3">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
            <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
            <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>" />
            <?php echo $strAlterOrderBy; ?>&nbsp;:
            <select name="order_field" style="vertical-align: middle">
    <?php
    echo "\n";
    reset($columns);
    while (list($junk, $fieldname) = each($columns)) {
        echo '                <option value="' . urlencode($fieldname) . '">' . htmlspecialchars($fieldname) . '</option>' . "\n";
    }
    unset($columns);
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

    <!-- Change table name -->
    <li>
        <div style="margin-bottom: 10px">
            <form method="post" action="tbl_rename.php3"
                onsubmit="return emptyFormElements(this, 'new_name')">
                <input type="hidden" name="server" value="<?php echo $server; ?>" />
                <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
                <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>" />
                <input type="hidden" name="reload" value="1" />
                <?php echo $strRenameTable; ?>&nbsp;:
                <input type="text" size="20" name="new_name" value="<?php echo htmlspecialchars($table); ?>" class="textfield" onfocus="this.select()" />&nbsp;
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
                <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
                <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>" />
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
                        <input type="text" size="20" name="new_name" value="<?php echo $table; ?>" class="textfield" onfocus="this.select()" />
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
                <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
                <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>" />
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
                        <input type="text" size="20" name="new_name" class="textfield" onfocus="this.select()" />
                    </td>
                </tr>
                <tr>
                    <td nowrap="nowrap">
                        <input type="radio" name="what" value="structure" id="radio_copy_structure" checked="checked" />
                        <label for="radio_copy_structure"><?php echo $strStrucOnly; ?></label>&nbsp;&nbsp;<br />
                        <input type="radio" name="what" value="data" id="radio_copy_data" />
                        <label for="radio_copy_data"><?php echo $strStrucData; ?></label>&nbsp;&nbsp;
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
                <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'CHECK_TABLE') . "\n"; ?>
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
                <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'ANALYZE_TABLE') . "\n";?>
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
                <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'REPAIR_TABLE') . "\n"; ?>
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
                <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'OPTIMIZE_TABLE') . "\n"; ?>
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
        <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'OPTIMIZE_TABLE') . "\n"; ?>
        </div>
    </li>
    <?php
    echo "\n";
} // end 3.23.06 < MySQL < 3.23.22

// Referential integrity check
if ($cfgRelation['relwork']) {

    // we need this PMA_mysql_select_db if the user has access to more than one db
    // and $db is not the last of the list, because PMA_availableDatabases()
    // has made a PMA_mysql_select_db() on the last one
    PMA_mysql_select_db($db);
    $foreign = PMA_getForeigners($db, $table);

    if ($foreign) {
        ?>
    <!-- Referential integrity check -->
    <li style="vertical-align: top">
        <div style="margin-bottom: 10px">
        <?php echo $strReferentialIntegrity; ?><br />
        <?php
        echo "\n";
        while (list($master, $arr) = each($foreign)){
            $join_query  = 'SELECT ' . PMA_backquote($table) . '.* FROM '
                         . PMA_backquote($table) . ' LEFT JOIN '
                         . PMA_backquote($arr['foreign_table']);
            if ($arr['foreign_table'] == $table) {
                $foreign_table = $table . '1';
                $join_query .= ' AS ' . PMA_backquote($foreign_table);
            } else {
                $foreign_table = $arr['foreign_table'];
            }
            $join_query .= ' ON '
                         . PMA_backquote($table) . '.' . PMA_backquote($master)
                         . ' = ' . PMA_backquote($foreign_table) . '.' . PMA_backquote($arr['foreign_field'])
                         . ' WHERE '
                         . PMA_backquote($foreign_table) . '.' . PMA_backquote($arr['foreign_field'])
                         . ' IS NULL AND '
                         . PMA_backquote($table) . '.' . PMA_backquote($master)
                         . ' IS NOT NULL';
            echo '        '
                 . '<a href="sql.php3?' . $url_query
                 . '&amp;sql_query='
                 . urlencode($join_query)
                 . '">' . $master . '&nbsp;->&nbsp;' . $arr['foreign_table'] . '.' . $arr['foreign_field']
                 . '</a><br />' . "\n";
            unset($foreign_table);
            unset($join_query);
        } //  end while
        ?>
        </div>
    </li><br />
        <?php
    } // end if ($result)
    echo "\n";

} // end  if (!empty($cfg['Server']['relation']))
?>

    <!-- Flushes the table -->
    <li>
        <a href="sql.php3?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('FLUSH TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenFlushed, htmlspecialchars($table))); if ($cfg['ShowTooltip']) echo '&amp;reload=1'; ?>">
            <?php echo $strFlushTable; ?></a>&nbsp;
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'FLUSH') . "\n"; ?>
        <br /><br />
    </li>

</ul>

<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
