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

/**
 * Updates table comment, type and options if required
 */
if (isset($submitcomment)) {
    if (empty($prev_comment) || urldecode($prev_comment) != $comment) {
        $sql_query = 'ALTER TABLE ' . PMA_backquote($table) . ' COMMENT = \'' . PMA_sqlAddslashes($comment) . '\'';
        $result    = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $err_url);
        $message   = $strSuccess;
    }
}
if (isset($submittype)) {
    $sql_query     = 'ALTER TABLE ' . PMA_backquote($table) . ' TYPE = ' . $tbl_type;
    $result        = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $err_url);
    $message       = $strSuccess;
}
if (isset($submitcharset)) {
    $sql_query     = 'ALTER TABLE ' . PMA_backquote($table) . ' CHARACTER SET = ' . $tbl_charset;
    $result        = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $err_url);
    $message       = $strSuccess;
}
if (isset($submitoptions)) {
    $sql_query     = 'ALTER TABLE ' . PMA_backquote($table)
                   . (isset($pack_keys) ? ' pack_keys=1': ' pack_keys=0')
                   . (isset($checksum) ? ' checksum=1': ' checksum=0')
                   . (isset($delay_key_write) ? ' delay_key_write=1': ' delay_key_write=0')
                   . (isset($auto_increment) ? ' auto_increment=' . PMA_sqlAddslashes($auto_increment) : '');
    $result        = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $err_url);
    $message       = $strSuccess;
}

// Displays a message if a query had been submitted
if (isset($message)) {
    PMA_showMessage($message);
}



/**
 * Reordering the table has been requested by the user
 */
if (isset($submitorderby) && !empty($order_field)) {
    $sql_query   = 'ALTER TABLE ' . PMA_backquote($table)
                 . ' ORDER BY ' . PMA_backquote(urldecode($order_field));
    $result      = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $err_url);
    PMA_showMessage($strSuccess);
} // end if


/**
 * Gets tables informations and displays top links
 */
require('./tbl_properties_table_info.php');


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
        <form method="post" action="tbl_properties_operations.php">
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <?php echo $strAlterOrderBy; ?>&nbsp;:
            <select name="order_field" style="vertical-align: middle">
    <?php
    echo "\n";
    foreach($columns AS $junk => $fieldname) {
        echo '                <option value="' . htmlspecialchars($fieldname) . '">' . htmlspecialchars($fieldname) . '</option>' . "\n";
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
        <form method="post" action="tbl_rename.php"
            onsubmit="return emptyFormElements(this, 'new_name')">
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <input type="hidden" name="reload" value="1" />
            <?php echo $strRenameTable; ?>&nbsp;:
            <input type="text" size="20" name="new_name" value="<?php echo htmlspecialchars($table); ?>" class="textfield" onfocus="this.select()" />&nbsp;
            <input type="submit" value="<?php echo $strGo; ?>" />
        </form>
    </li>

    <!-- Move table -->
    <li>
        <?php echo $strMoveTable . "\n"; ?>
        <form method="post" action="tbl_move_copy.php"
            onsubmit="return emptyFormElements(this, 'new_name')">
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <input type="hidden" name="reload" value="1" />
            <input type="hidden" name="what" value="data" />
            <table border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td>
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
            </tr>
            <tr>
                <td align="<?php echo $cell_align_right; ?>" valign="top">
                    <input type="submit" name="submit_move" value="<?php echo $strGo; ?>" />
                </td>
            </tr>
            </table>
        </form>
    </li>

    <!-- Copy table -->
    <li>
        <?php echo $strCopyTable . "\n"; ?>
        <form method="post" action="tbl_move_copy.php"
            onsubmit="return emptyFormElements(this, 'new_name')">
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <input type="hidden" name="reload" value="1" />
            <table border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td colspan="2">
                    <select name="target_db">
<?php
for ($i = 0; $i < $num_dbs; $i++) {
    echo '                            ';
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
                <td nowrap="nowrap">
                    <input type="radio" name="what" value="structure" id="radio_copy_structure" checked="checked" />
                    <label for="radio_copy_structure"><?php echo $strStrucOnly; ?></label>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="data" id="radio_copy_data" />
                    <label for="radio_copy_data"><?php echo $strStrucData; ?></label>&nbsp;&nbsp;<br />
                    <input type="radio" name="what" value="dataonly" id="radio_copy_dataonly" />
                    <label for="radio_copy_dataonly"><?php echo $strDataOnly; ?></label>&nbsp;&nbsp;<br />
                    <input type="checkbox" name="drop_if_exists" value="true" id="checkbox_drop" />
                    <label for="checkbox_drop"><?php echo $strStrucDrop; ?></label>&nbsp;&nbsp;<br />
                    <input type="checkbox" name="auto_increment" value="1" id="checkbox_auto_increment" />
                    <label for="checkbox_auto_increment"><?php echo $strAddAutoIncrement; ?></label><br />
                    <?php
                    // display "Add constraints" choice only if there are
                    // foreign keys
                    if (PMA_getForeigners($db, $table, '', 'innodb')) {
                    ?>
                    <input type="checkbox" name="constraints" value="1" id="checkbox_constraints" />
                    <label for="checkbox_constraints"><?php echo $strAddConstraints; ?></label><br />
                    <?php
                    } // endif
                    if (isset($_COOKIE) && isset($_COOKIE['pma_switch_to_new']) && $_COOKIE['pma_switch_to_new'] == 'true') {
                        $pma_switch_to_new = 'true';
                    }
                    ?>
                    <input type="checkbox" name="switch_to_new" value="true" id="checkbox_switch" <?php echo ((isset($pma_switch_to_new) && $pma_switch_to_new == 'true') ? 'checked="checked"' : ''); ?>/>
                    <label for="checkbox_switch"><?php echo $strSwitchToTable; ?></label>&nbsp;&nbsp;
                </td>
                <td align="<?php echo $cell_align_right; ?>" valign="top">
                    <input type="submit" name="submit_copy" value="<?php echo $strGo; ?>" />
                </td>
            </tr>
            </table>
        </form>
    </li>

    <!-- Table maintenance -->
    <li>
        <?php echo $strTableMaintenance; ?>
        <ul>
<?php
if ($tbl_type == 'MYISAM' || $tbl_type == 'BERKELEYDB' || $tbl_type == 'INNODB') {
    echo "\n";
    if ($tbl_type == 'MYISAM' || $tbl_type == 'INNODB') {
        ?>
        <li>
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('CHECK TABLE ' . PMA_backquote($table)); ?>">
                <?php echo $strCheckTable; ?></a>&nbsp;
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'CHECK_TABLE') . "\n"; ?>
        </li>
        <?php
    }
    echo "\n";
    if ($tbl_type == 'MYISAM' || $tbl_type == 'BERKELEYDB') {
        ?>
        <li>
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ANALYZE TABLE ' . PMA_backquote($table)); ?>">
                <?php echo $strAnalyzeTable; ?></a>&nbsp;
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'ANALYZE_TABLE') . "\n";?>
        </li>
        <?php
    }
    echo "\n";
    if ($tbl_type == 'MYISAM') {
        ?>
        <li>
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('REPAIR TABLE ' . PMA_backquote($table)); ?>">
                <?php echo $strRepairTable; ?></a>&nbsp;
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'REPAIR_TABLE') . "\n"; ?>
        </li>
        <?php
    }
    echo "\n";
    if ($tbl_type == 'MYISAM' || $tbl_type == 'BERKELEYDB') {
        ?>
        <li>
            <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . PMA_backquote($table)); ?>">
                <?php echo $strOptimizeTable; ?></a>&nbsp;
            <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'OPTIMIZE_TABLE') . "\n"; ?>
        </li>
        <?php
    }
    echo "\n";
    ?>
    <?php
} // end MYISAM or BERKELEYDB case
echo "\n";
?>
            <li>
                <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('FLUSH TABLE ' . PMA_backquote($table)); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strTableHasBeenFlushed, htmlspecialchars($table))); if ($cfg['ShowTooltip']) echo '&amp;reload=1'; ?>">
                    <?php echo $strFlushTable; ?></a>&nbsp;
                    <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'FLUSH') . "\n"; ?>
            </li>

<?php
// Referential integrity check
// The Referential integrity check was intended for the non-InnoDB
// tables for which the relations are defined in pmadb
// so I assume that if the current table is InnoDB, I don't display
// this choice (InnoDB maintains integrity by itself)

if ($cfgRelation['relwork'] && $tbl_type != "INNODB") {

    // we need this PMA_mysql_select_db if the user has access to more than one db
    // and $db is not the last of the list, because PMA_availableDatabases()
    // has made a PMA_mysql_select_db() on the last one
    PMA_mysql_select_db($db);
    $foreign = PMA_getForeigners($db, $table);

    if ($foreign) {
        ?>
    <!-- Referential integrity check -->
            <li>
                <?php echo $strReferentialIntegrity; ?><br />
                <?php
                echo "\n";
                foreach($foreign AS $master => $arr) {
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
            </li>
        <?php
    } // end if ($result)
    echo "\n";

} // end  if (!empty($cfg['Server']['relation']))
?>
            <br />
        </ul>
    </li>

<?php

/**
 * Displays form controls
 */
?>
    <!-- Table comments -->
    <li>
        <form method="post" action="tbl_properties_operations.php">
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <?php echo $strTableComments; ?>&nbsp;:&nbsp;
            <?php $comment = ereg_replace('; InnoDB free:.*$' , '', ereg_replace('^InnoDB free:.*$', '', $show_comment)); ?>
            <input type="hidden" name="prev_comment" value="<?php echo urlencode($comment); ?>" />&nbsp;
            <input type="text" name="comment" maxlength="60" size="30" value="<?php echo htmlspecialchars($comment); ?>" class="textfield" style="vertical-align: middle" onfocus="this.select()" />&nbsp;
            <input type="submit" name="submitcomment" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
        </form>
    </li>

    <!-- Table type -->
    <?php
    // modify robbat2 code - staybyte - 11. June 2001
    $query  = 'SHOW VARIABLES LIKE \'have_%\'';
    $result = PMA_mysql_query($query);
    if ($result != FALSE && mysql_num_rows($result) > 0) {
        while ($tmp = PMA_mysql_fetch_array($result)) {
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
        <form method="post" action="tbl_properties_operations.php">
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <?php echo $strTableType; ?>&nbsp;:&nbsp;
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
            </select>&nbsp;
            <input type="submit" name="submittype" value="<?php echo $strGo; ?>" style="vertical-align: middle" />&nbsp;
            <?php echo PMA_showMySQLDocu('Table_types', 'Table_types') . "\n"; ?>
        </form>
    </li>
    <?php

    if (PMA_MYSQL_INT_VERSION >= 40100) {
        echo "\n"
           . '<!-- Table character set -->' . "\n"
           . '    <li>' . "\n"
           . '        <form method="post" action="tbl_properties_operations.php">' . "\n"
           . PMA_generate_common_hidden_inputs($db, $table, 3)
           . '            ' . $strCharset . '&nbsp;:&nbsp;' . "\n"
           . '            <select name="tbl_charset" style="vertical-align: middle">' . "\n";
           $real_charset = strpos($tbl_charset, '_') ? substr($tbl_charset, 0, strpos($tbl_charset, '_')) : $tbl_charset;
        for ($i = 1; isset($mysql_charsets[$i]); $i++) {
            echo '                <option value="' . $mysql_charsets[$i] . '"' . ($mysql_charsets[$i] == $real_charset ? ' selected="selected"' : '') . '>' . $mysql_charsets[$i] . '</option>' . "\n";
        }
        unset($i);
        unset($real_charset);
        echo '            </select>&nbsp;' . "\n"
           . '            <input type="submit" name="submitcharset" value="' . $strGo . '" style="vertical-align: middle" />&nbsp;' . "\n"
           . '        </form>' . "\n"
           . '    </li>' . "\n";
    }

    ?>

    <!-- Table options -->
    <li>
        <?php echo $strTableOptions; ?>:<br />
        <table border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td valign="top">
                <form method="post" action="tbl_properties_operations.php">
                    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>

                    <table border="0" cellspacing="1" cellpadding="1">
                    <tr>
                        <td align="right"><input type="checkbox" name="pack_keys" id="pack_keys_opt"
                                <?php echo (isset($pack_keys) && $pack_keys == 1) ? ' checked="checked"' : ''; ?> /></td>
                        <td><label for="pack_keys_opt">pack_keys</label>&nbsp;&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="right"><input type="checkbox" name="checksum" id="checksum_opt"
                                <?php echo (isset($checksum) && $checksum == 1) ? ' checked="checked"' : ''; ?> /></td>
                         <td><label for="checksum_opt">checksum</label>&nbsp;&nbsp;</td>
                    </tr>
                    <tr>
                         <td align="right"><input type="checkbox" name="delay_key_write" id="delay_key_write_opt"
                                <?php echo (isset($delay_key_write) && $delay_key_write == 1) ? ' checked="checked"' : ''; ?> /></td>
                         <td><label for="delay_key_write_opt">delay_key_write</label>&nbsp;&nbsp;</td>
                    </tr>
                    <tr>
                         <td><input type="text" name="auto_increment" id="auto_increment_opt" class="textfield" style="width: 30px"
                                <?php echo (isset($auto_increment) && !empty($auto_increment) ? ' value="' . $auto_increment . '"' : ''); ?> /></td>
                         <td valign="top"><label for="auto_increment_opt">auto_increment</label>&nbsp;&nbsp;<input type="submit" name="submitoptions" value="<?php echo $strGo; ?>" /></td>
                    </tr>
                    </table>
                </form>
            </td>
        </tr>
        </table>
    </li>
</ul>
<?php

/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');
?>
