<?php
/* $Id$ */


require('./tbl_properties_common.php3');
require('./tbl_properties_table_info.php3');

// Get columns names
$local_query = 'SHOW COLUMNS FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
$result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
for ($i = 0; $row = mysql_fetch_array($result); $i++) {
        $columns[$i] = $row['Field'];
}
mysql_free_result($result);
?>
<ul>
    <!-- Add some new fields -->
    <li>
        <form method="post" action="tbl_addfield.php3"
            onsubmit="return checkFormElementInRange(this, 'num_fields', 1)">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strAddNewField; ?>&nbsp;:
            <input type="text" name="num_fields" size="2" maxlength="2" value="1" class="textfield" style="vertical-align: middle" onfocus="this.select()" />
            <select name="after_field" style="vertical-align: middle">
                <option value="--end--"><?php echo $strAtEndOfTable; ?></option>
                <option value="--first--"><?php echo $strAtBeginningOfTable; ?></option>
<?php
reset($columns);
while (list($junk, $fieldname) = each($columns)) {
    echo '                <option value="' . urlencode($fieldname) . '">' . sprintf($strAfter, htmlspecialchars($fieldname)) . '</option>' . "\n";
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
                <input type="hidden" name="db" value="<?php echo $db; ?>" />
                <input type="hidden" name="table" value="<?php echo $table; ?>" />
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
                <?php echo PMA_showDocuShort('C/H/CHECK_TABLE.html') . "\n"; ?>
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
                <?php echo PMA_showDocuShort('A/N/ANALYZE_TABLE.html') . "\n";?>
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
                <?php echo PMA_showDocuShort('R/E/REPAIR_TABLE.html') . "\n"; ?>
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
                <?php echo PMA_showDocuShort('O/P/OPTIMIZE_TABLE.html') . "\n"; ?>
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
        <?php echo PMA_showDocuShort('O/P/OPTIMIZE_TABLE.html') . "\n"; ?>
        </div>
    </li>
    <?php
    echo "\n";
} // end 3.23.06 < MySQL < 3.23.22

if (PMA_MYSQL_INT_VERSION >= 32322) {
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
            <input type="text" name="comment" maxlength="60" size="30" value="<?php echo str_replace('"', '&quot;', $show_comment); ?>" class="textfield" style="vertical-align: middle" onfocus="this.select()" />&nbsp;
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
            <?php echo PMA_showDocuShort('T/a/Table_types.html') . "\n"; ?>
        </form>
    </li>
    <?php
    echo "\n";
} // end MySQL >= 3.23.22

// Referential integrity check
if (!empty($cfg['Server']['relation'])) {

    $local_query = 'SELECT master_field, foreign_table, foreign_field'
                 . ' FROM ' . $cfg['Server']['relation']
                 . ' WHERE master_table = \'' . $table . '\';';

    // we need this mysql_select_db if the user has access to more than one db
    // and $db is not the last of the list, because PMA_availableDatabases()
    // has made a mysql_select_db() on the last one
    mysql_select_db($db);

    $result      = @mysql_query($local_query);

    if ($result != FALSE && mysql_num_rows($result) > 0) {
        ?>
    <!-- Referential integrity check -->
    <li style="vertical-align: top">
        <div style="margin-bottom: 10px">
        <?php echo $strReferentialIntegrity; ?><br />
        <?php
        echo "\n";
        while ($rel = mysql_fetch_row($result)) {
            echo '        <a href="sql.php3?' . $url_query .'&amp;sql_query='
                 . urlencode('SELECT ' . PMA_backquote($table) . '.* FROM '
                             . PMA_backquote($table) . ' LEFT JOIN '
                             . PMA_backquote($rel[1]) . ' ON '
                             . PMA_backquote($table) . '.' . PMA_backquote($rel[0])
                             . ' = ' . PMA_backquote($rel[1]) . '.' . PMA_backquote($rel[2])
                             . ' WHERE '
                             . PMA_backquote($rel[1]) . '.' . PMA_backquote($rel[2])
                             . ' IS NULL')
                 . '">' . $rel[0] . '&nbsp;->&nbsp;' . $rel[1] . '.' . $rel[2]
                 . '</a><br />' . "\n";
        } // end while
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
