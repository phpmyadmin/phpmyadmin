<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Runs common work
 */
require('./tbl_properties_common.php');
//$err_url   = 'tbl_properties_operations.php' . $err_url;
$url_query .= '&amp;goto=tbl_properties_operations.php&amp;back=tbl_properties_operations.php';


/**
 * Gets relation settings
 */
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

/**
 * Gets available MySQL charsets
 */
require_once('./libraries/mysql_charsets.lib.php');

// reselect current db (needed in some cases probably due to
// the calling of relation.lib.php)
PMA_DBI_select_db($db);

/**
 * Updates table comment, type and options if required
 */
if (isset($submitcomment)) {
    if (empty($prev_comment) || urldecode($prev_comment) != $comment) {
        $sql_query = 'ALTER TABLE ' . PMA_backquote($table) . ' COMMENT = \'' . PMA_sqlAddslashes($comment) . '\'';
        $result    = PMA_DBI_query($sql_query);
        $message   = $strSuccess;
    }
}
if (isset($submittype)) {
    $sql_query     = 'ALTER TABLE ' . PMA_backquote($table) . ' TYPE = ' . $tbl_type;
    $result        = PMA_DBI_query($sql_query);
    $message       = $strSuccess;
}
if (isset($submitcollation)) {
    $sql_query     = 'ALTER TABLE ' . PMA_backquote($table) . ' DEFAULT' . PMA_generateCharsetQueryPart($tbl_collation);
    $result        = PMA_DBI_query($sql_query);
    $message       = $strSuccess;
    unset($tbl_collation);
}
if (isset($submitoptions)) {
    $sql_query     = 'ALTER TABLE ' . PMA_backquote($table)
                   . (isset($pack_keys) ? ' pack_keys=1': ' pack_keys=0')
                   . (isset($checksum) ? ' checksum=1': ' checksum=0')
                   . (isset($delay_key_write) ? ' delay_key_write=1': ' delay_key_write=0')
                   . (!empty($auto_increment) ? ' auto_increment=' . PMA_sqlAddslashes($auto_increment) : '');
    $result        = PMA_DBI_query($sql_query);
    $message       = $strSuccess;
}

/**
 * Reordering the table has been requested by the user
 */
if (isset($submitorderby) && !empty($order_field)) {
    $sql_query   = 'ALTER TABLE ' . PMA_backquote($table)
                 . ' ORDER BY ' . PMA_backquote(urldecode($order_field));
    if (isset($order_order) && $order_order == 'desc') {
        $sql_query .= ' DESC';
    }
    $result      = PMA_DBI_query($sql_query);
    $message     = $result ? $strSuccess : $strFailed;
} // end if

/**
 * Gets tables informations
 */
require('./tbl_properties_table_info.php');

/**
 * Displays top menu links
 */
require('./tbl_properties_links.php');

/**
 * Get columns names
 */
$local_query = 'SHOW COLUMNS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db);
$result      = PMA_DBI_query($local_query);
for ($i = 0; $row = PMA_DBI_fetch_assoc($result); $i++) {
        $columns[$i] = $row['Field'];
}
PMA_DBI_free_result($result);
unset($result);
?>

<table border="0" align="left" cellpadding="3" cellspacing="0">
<?php

/**
 * Displays the page
 */

if (PMA_MYSQL_INT_VERSION >= 32334) {
    ?>
    <!-- Order the table -->

    <form method="post" action="tbl_properties_operations.php">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <tr>
            <th class="tblHeaders" colspan="2" align="left"><?php echo $strAlterOrderBy; ?>:&nbsp;</th></tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <select name="order_field" style="vertical-align: middle">
    <?php
    echo "\n";
    foreach ($columns AS $junk => $fieldname) {
        echo '                <option value="' . htmlspecialchars($fieldname) . '">' . htmlspecialchars($fieldname) . '</option>' . "\n";
    }
    unset($columns);
    ?>
                </select>&nbsp;<?php echo $strSingly . "\n"; ?>
                <select name="order_order" style="vertical-align: middle">
                    <option value="asc"><?php echo $strAscending; ?></option>
                    <option value="desc"><?php echo $strDescending; ?></option>
                </select>
            </td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">
                <input type="submit" name="submitorderby" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
            </td>
        </tr>
    </form>
        <tr><td colspan="2" height="5"></td></tr>
    <?php
}
echo "\n";
?>
    <!-- Change table name -->
    <form method="post" action="tbl_rename.php" onsubmit="return emptyFormElements(this, 'new_name')">
        <tr>
            <th class="tblHeaders" colspan="2" align="left">
                <?php echo $strRenameTable; ?>:&nbsp;
                <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
                <input type="hidden" name="reload" value="1" />
            </th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <input type="text" size="20" name="new_name" value="<?php echo htmlspecialchars($table); ?>" class="textfield" onfocus="this.select()" />&nbsp;
            </td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">
                <input type="submit" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
    </form>
        <tr><td colspan="2" height="5"></td></tr>
    <!-- Move table -->
    <form method="post" action="tbl_move_copy.php" onsubmit="return emptyFormElements(this, 'new_name')">
        <tr>
            <th class="tblHeaders" colspan="2" align="left">
                <?php echo $strMoveTable . "\n"; ?>
                <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
                <input type="hidden" name="reload" value="1" />
                <input type="hidden" name="what" value="data" />
            </th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" nowrap="nowrap">
                <select name="target_db">
<?php
// The function used below is defined in "common.lib.php"
PMA_availableDatabases('main.php?' . PMA_generate_common_url());
for ($i = 0; $i < $num_dbs; $i++) {
    echo '                            ';
    echo '<option value="' . htmlspecialchars($dblist[$i]) . '">' . htmlspecialchars($dblist[$i]) . '</option>';
    echo "\n";
} // end for
?>
                </select>
                &nbsp;<b>.</b>&nbsp;
                <input type="text" size="20" name="new_name" value="<?php echo htmlspecialchars($table); ?>" class="textfield" onfocus="this.select()" />
            </td>
            <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <input type="submit" name="submit_move" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
    </form>
        <tr><td colspan="2" height="5"></td></tr>
    <!-- Copy table -->
    <form method="post" action="tbl_move_copy.php" onsubmit="return emptyFormElements(this, 'new_name')">
        <tr>
            <th class="tblHeaders" colspan="2" align="left">
                <?php echo $strCopyTable . "\n"; ?>
                <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
                <input type="hidden" name="reload" value="1" />
            </th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" colspan="2" nowrap="nowrap">
                <select name="target_db">
<?php
for ($i = 0; $i < $num_dbs; $i++) {
    echo '                    ';
    echo '<option value="' . htmlspecialchars($dblist[$i]) . '"';
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
            <td nowrap="nowrap" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <input type="radio" name="what" value="structure" id="radio_copy_structure" style="vertical-align: middle" /><label for="radio_copy_structure"><?php echo $strStrucOnly; ?></label>&nbsp;&nbsp;<br />
                <input type="radio" name="what" value="data" id="radio_copy_data" checked="checked" style="vertical-align: middle" /><label for="radio_copy_data"><?php echo $strStrucData; ?></label>&nbsp;&nbsp;<br />
                <input type="radio" name="what" value="dataonly" id="radio_copy_dataonly" style="vertical-align: middle" /><label for="radio_copy_dataonly"><?php echo $strDataOnly; ?></label>&nbsp;&nbsp;<br />

                <input type="checkbox" name="drop_if_exists" value="true" id="checkbox_drop" style="vertical-align: middle" /><label for="checkbox_drop"><?php echo $strStrucDrop; ?></label>&nbsp;&nbsp;<br />
                <input type="checkbox" name="auto_increment" value="1" id="checkbox_auto_increment" style="vertical-align: middle" /><label for="checkbox_auto_increment"><?php echo $strAddAutoIncrement; ?></label><br />
                <?php
                    // display "Add constraints" choice only if there are
                    // foreign keys
                    if (PMA_getForeigners($db, $table, '', 'innodb')) {
                ?>
                <input type="checkbox" name="constraints" value="1" id="checkbox_constraints" style="vertical-align: middle" /><label for="checkbox_constraints"><?php echo $strAddConstraints; ?></label><br />
                <?php
                    } // endif
                    if (isset($_COOKIE) && isset($_COOKIE['pma_switch_to_new']) && $_COOKIE['pma_switch_to_new'] == 'true') {
                        $pma_switch_to_new = 'true';
                    }
                ?>
                <input type="checkbox" name="switch_to_new" value="true" id="checkbox_switch"<?php echo ((isset($pma_switch_to_new) && $pma_switch_to_new == 'true') ? ' checked="checked"' : ''); ?> style="vertical-align: middle" /><label for="checkbox_switch"><?php echo $strSwitchToTable; ?></label>&nbsp;&nbsp;
            </td>
            <td align="<?php echo $cell_align_right; ?>" valign="bottom" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <input type="submit" name="submit_copy" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
    </form>
        <tr><td colspan="2" height="5"></td></tr>
<?php

/**
 * Displays form controls
 */
?>
    <!-- Table comments -->
    <form method="post" action="tbl_properties_operations.php">
        <tr>
            <th colspan="2" class="tblHeaders" align="left">
                <?php
                echo PMA_generate_common_hidden_inputs($db, $table);
                echo $strTableComments . '&nbsp;';
                if (strstr($show_comment, '; InnoDB free') === FALSE) {
                    if (strstr($show_comment, 'InnoDB free') === FALSE) {
                        // only user entered comment
                        $comment = $show_comment;
                    } else {
                        // here we have just InnoDB generated part
                        $comment = '';
                    }
                } else {
                    // remove InnoDB comment from end, just the minimal part (*? is non greedy)
                    $comment = preg_replace('@; InnoDB free:.*?$@' , '', $show_comment);
                }
                ?>
                <input type="hidden" name="prev_comment" value="<?php echo urlencode($comment); ?>" />&nbsp;
            </th>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <input type="text" name="comment" maxlength="60" size="30" value="<?php echo htmlspecialchars($comment); ?>" class="textfield" style="vertical-align: middle" onfocus="this.select()" />&nbsp;
            </td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">
                <input type="submit" name="submitcomment" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
            </td>
        </tr>
    </form>
        <tr><td colspan="2" height="5"></td></tr>
    <!-- Table type -->
    <?php
    // modify robbat2 code - staybyte - 11. June 2001
    $result = PMA_DBI_query('SHOW VARIABLES LIKE \'have_%\';');
    if ($result) {
        while ($tmp = PMA_DBI_fetch_assoc($result)) {
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

    PMA_DBI_free_result($result);
    echo "\n";
    ?>
    <form method="post" action="tbl_properties_operations.php">
        <tr>
            <th colspan="2" class="tblHeaders" align="left">
                <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
                <?php echo $strTableType; ?>:&nbsp;
                <?php echo PMA_showMySQLDocu('Table_types', 'Table_types') . "\n"; ?>
            </th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <select name="tbl_type" style="vertical-align: middle">
                    <option value="MYISAM"<?php if ($tbl_type == 'MYISAM') echo ' selected="selected"'; ?>>MyISAM</option>
                    <option value="HEAP"<?php if ($tbl_type == 'HEAP') echo ' selected="selected"'; ?>>Heap</option>
    <?php
    $tbl_types     = "\n";
    if (isset($tbl_bdb)) {
        $tbl_types .= '                <option value="BERKELEYDB"'
                   .  (($tbl_type == 'BERKELEYDB') ? ' selected="selected"' : '')
                   .  '>Berkeley DB</option>' . "\n";
    }
    if (isset($tbl_gemini)) {
        $tbl_types .= '                <option value="GEMINI"'
                   .  (($tbl_type == 'GEMINI') ? ' selected="selected"' : '')
                   .  '>Gemini</option>' . "\n";
    }
    if (isset($tbl_innodb)) {
        $tbl_types .= '                <option value="INNODB"'
                   .  (($tbl_type == 'INNODB') ? ' selected="selected"' : '')
                   .  '>INNO DB</option>' . "\n";
    }
    if (isset($tbl_isam)) {
        $tbl_types .= '                <option value="ISAM"'
                   .  (($tbl_type == 'ISAM') ? ' selected="selected"' : '')
                   .  '>ISAM</option>' . "\n";
    }

    echo $tbl_types;
    ?>
                    <option value="MERGE"<?php if ($tbl_type == 'MRG_MYISAM') echo ' selected="selected"'; ?>>Merge</option>
                    </select>
            </td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">
                <input type="submit" name="submittype" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
    </form>
        <tr><td colspan="2" height="5"></td></tr>
    <?php

    if (PMA_MYSQL_INT_VERSION >= 40100) {
        echo "\n"
           . '<!-- Table character set -->' . "\n"
           . '    <form method="post" action="tbl_properties_operations.php">' . "\n"
           . '        <tr>' . "\n"
           . '            <th colspan="2" class="tblHeaders" align="left">' . "\n"
           . PMA_generate_common_hidden_inputs($db, $table, 3)
           . '            ' . $strCollation . ':&nbsp;' . "\n"
           . '            </th>' . "\n"
           . '        </tr>' . "\n"
           . '        <tr>' . "\n"
           . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
           . PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'tbl_collation', NULL, $tbl_collation, FALSE, 3)
           . '            </td>' . "\n"
           . '            <td bgcolor="' . $cfg['BgcolorOne'] . '" align="right">' . "\n"
           . '                <input type="submit" name="submitcollation" value="' . $strGo . '" style="vertical-align: middle" />' . "\n"
           . '        </td>' . "\n"
           . '        </tr>' . "\n"
           . '    </form>' . "\n"
           . '        <tr><td colspan="2" height="5"></td></tr>' . "\n";
    }
    // PACK_KEYS: MyISAM or ISAM
    // DELAY_KEY_WRITE, CHECKSUM, AUTO_INCREMENT: MyISAM only

    if ($tbl_type == 'MYISAM' || $tbl_type == 'ISAM') {
    ?>
    <!-- Table options -->
    <form method="post" action="tbl_properties_operations.php">
        <tr>
            <th colspan="2" class="tblHeaders" align="left">
                <?php echo $strTableOptions; ?>:&nbsp;
                <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            </th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <input type="checkbox" name="pack_keys" id="pack_keys_opt"
                <?php echo (isset($pack_keys) && $pack_keys == 1) ? ' checked="checked"' : ''; ?> style="vertical-align: middle" /><label for="pack_keys_opt">pack_keys</label><br />
        <?php
        if ($tbl_type == 'MYISAM') {
        ?>
                <input type="checkbox" name="checksum" id="checksum_opt"
                <?php echo (isset($checksum) && $checksum == 1) ? ' checked="checked"' : ''; ?> style="vertical-align: middle" /><label for="checksum_opt">checksum</label><br />

                <input type="checkbox" name="delay_key_write" id="delay_key_write_opt"
                <?php echo (isset($delay_key_write) && $delay_key_write == 1) ? ' checked="checked"' : ''; ?> style="vertical-align: middle" /><label for="delay_key_write_opt">delay_key_write</label><br />

                <input type="text" name="auto_increment" id="auto_increment_opt" class="textfield"
                <?php echo (isset($auto_increment) && !empty($auto_increment) ? ' value="' . $auto_increment . '"' : ''); ?> style="width: 30px; vertical-align: middle" />&nbsp;<label for="auto_increment_opt">auto_increment</label>
            </td>
        <?php
        } // end if (MYISAM)
        ?>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right" valign="bottom">
                <input type="submit" name="submitoptions" value="<?php echo $strGo; ?>" />
            </td>
        </tr>
    </form>
<?php
    } // end if (MYISAM or ISAM)
?>
</table>
<img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="25" height="1" border="0" align="left" />
<!----->
<table border="0" cellpadding="3" cellspacing="0">
    <tr>
        <th class="tblHeaders" colspan="2" align="left">
            <?php echo $strTableMaintenance; ?>
        </th>
    </tr>
<?php
if ($tbl_type == 'MYISAM' || $tbl_type == 'BERKELEYDB' || $tbl_type == 'INNODB') {
    echo "\n";
    if ($tbl_type == 'MYISAM' || $tbl_type == 'INNODB') {
        ?>
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('CHECK TABLE ' . PMA_backquote($table)); ?>">
                <?php echo $strCheckTable; ?></a>&nbsp;
        </td>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'CHECK_TABLE') . "\n"; ?>
        </td>
    </tr>
        <?php
    }
    echo "\n";
    if ($tbl_type == 'INNODB') {
        ?>
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' TYPE=InnoDB'); ?>">
                <?php echo $strDefragment; ?></a>&nbsp;
        </td>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php echo PMA_showMySQLDocu('Table_types', 'InnoDB_File_Defragmenting') . "\n"; ?>
        </td>
    </tr>
        <?php
    }
    echo "\n";
    if ($tbl_type == 'MYISAM' || $tbl_type == 'BERKELEYDB') {
        ?>
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ANALYZE TABLE ' . PMA_backquote($table)); ?>">
                <?php echo $strAnalyzeTable; ?></a>&nbsp;
        </td>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'ANALYZE_TABLE') . "\n";?>
        </td>
    </tr>
        <?php
    }
    echo "\n";
    if ($tbl_type == 'MYISAM') {
        ?>
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('REPAIR TABLE ' . PMA_backquote($table)); ?>">
                <?php echo $strRepairTable; ?></a>&nbsp;
        </td>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'REPAIR_TABLE') . "\n"; ?>
        </td>
    </tr>
        <?php
    }
    echo "\n";
    if ($tbl_type == 'MYISAM' || $tbl_type == 'BERKELEYDB') {
        ?>
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . PMA_backquote($table)); ?>">
                <?php echo $strOptimizeTable; ?></a>&nbsp;
        </td>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'OPTIMIZE_TABLE') . "\n"; ?>
        </td>
    </tr>
        <?php
    }
    echo "\n";
    ?>
    <?php
} // end MYISAM or BERKELEYDB case
echo "\n";
?>
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('FLUSH TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenFlushed, htmlspecialchars($table))); if ($cfg['ShowTooltip']) echo '&amp;reload=1'; ?>">
                    <?php echo $strFlushTable; ?></a>&nbsp;
        </td>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'FLUSH') . "\n"; ?>
        </td>
    </tr>

<?php
// Referential integrity check
// The Referential integrity check was intended for the non-InnoDB
// tables for which the relations are defined in pmadb
// so I assume that if the current table is InnoDB, I don't display
// this choice (InnoDB maintains integrity by itself)

if ($cfgRelation['relwork'] && $tbl_type != "INNODB") {

    // we need this PMA_DBI_select_db if the user has access to more than one db
    // and $db is not the last of the list, because PMA_availableDatabases()
    // has made a PMA_DBI_select_db() on the last one
    PMA_DBI_select_db($db);
    $foreign = PMA_getForeigners($db, $table);

    if ($foreign) {
        ?>
    <!-- Referential integrity check -->
    <tr>
        <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" colspan="2">
                <?php echo $strReferentialIntegrity; ?><br />
                <?php
                echo "\n";
                foreach ($foreign AS $master => $arr) {
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
                         . '<a href="sql.php?' . $url_query
                         . '&amp;sql_query='
                         . urlencode($join_query)
                         . '">' . $master . '&nbsp;->&nbsp;' . $arr['foreign_table'] . '.' . $arr['foreign_field']
                         . '</a><br />' . "\n";
                    unset($foreign_table);
                    unset($join_query);
                } //  end while
                ?>
        </td>
    </tr>
        <?php
    } // end if ($result)
    echo "\n";

} // end  if (!empty($cfg['Server']['relation']))
?>
</table>
<?php

/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');
?>
