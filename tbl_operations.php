<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';

$pma_table = new PMA_Table($GLOBALS['table'], $GLOBALS['db']);

/**
 * Runs common work
 */
require './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_operations.php&amp;back=tbl_operations.php';
$url_params['goto'] = $url_params['back'] = 'tbl_operations.php';

/**
 * Gets relation settings
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/**
 * Gets available MySQL charsets and storage engines
 */
require_once './libraries/mysql_charsets.lib.php';
require_once './libraries/StorageEngine.class.php';

/**
 * Class for partition management
 */
require_once './libraries/Partition.class.php';

// reselect current db (needed in some cases probably due to
// the calling of relation.lib.php)
PMA_DBI_select_db($GLOBALS['db']);

/**
 * Gets tables informations
 */

require './libraries/tbl_info.inc.php';

// define some globals here, for improved syntax in the conditionals
$is_myisam_or_maria = $is_isam = $is_innodb = $is_berkeleydb = $is_maria = $is_pbxt = false;
// set initial value of these globals, based on the current table engine
PMA_set_global_variables_for_engine($tbl_type);

if ($is_maria) {
    // the value for transactional can be implicit
    // (no create option found, in this case it means 1)
    // or explicit (option found with a value of 0 or 1)
    // ($transactional may have been set by libraries/tbl_info.inc.php,
    // from the $create_options)
    $transactional = (isset($transactional) && $transactional == '0') ? '0' : '1';
    $page_checksum = (isset($page_checksum)) ? $page_checksum : '';
}

$reread_info = false;
$table_alters = array();

/**
 * Updates table comment, type and options if required
 */
if (isset($_REQUEST['submitoptions'])) {
    $_message = '';
    $warning_messages = array();

    if (isset($_REQUEST['new_name'])) {
        if ($pma_table->rename($_REQUEST['new_name'])) {
            $_message .= $pma_table->getLastMessage();
            $result = true;
            $GLOBALS['table'] = $pma_table->getName();
            $reread_info = true;
            $reload = true;
        } else {
            $_message .= $pma_table->getLastError();
            $result = false;
        }
    }
    if (isset($_REQUEST['comment'])
      && urldecode($_REQUEST['prev_comment']) !== $_REQUEST['comment']) {
        $table_alters[] = 'COMMENT = \'' . PMA_sqlAddslashes($_REQUEST['comment']) . '\'';
    }
    if (! empty($_REQUEST['new_tbl_type'])
      && strtolower($_REQUEST['new_tbl_type']) !== strtolower($tbl_type)) {
        $table_alters[] = 'ENGINE = ' . $_REQUEST['new_tbl_type'];
        $tbl_type = $_REQUEST['new_tbl_type'];
        // reset the globals for the new engine
        PMA_set_global_variables_for_engine($tbl_type);
        if ($is_maria) {
            $transactional = (isset($transactional) && $transactional == '0') ? '0' : '1';
            $page_checksum = (isset($page_checksum)) ? $page_checksum : '';
        }
    }

    if (! empty($_REQUEST['tbl_collation'])
      && $_REQUEST['tbl_collation'] !== $tbl_collation) {
        $table_alters[] = 'DEFAULT ' . PMA_generateCharsetQueryPart($_REQUEST['tbl_collation']);
    }

    if (($is_myisam_or_maria || $is_isam)
      && isset($_REQUEST['new_pack_keys'])
      && $_REQUEST['new_pack_keys'] != (string)$pack_keys) {
        $table_alters[] = 'pack_keys = ' . $_REQUEST['new_pack_keys'];
    }

    $checksum = empty($checksum) ? '0' : '1';
    $_REQUEST['new_checksum'] = empty($_REQUEST['new_checksum']) ? '0' : '1';
    if ($is_myisam_or_maria
      && $_REQUEST['new_checksum'] !== $checksum) {
        $table_alters[] = 'checksum = ' . $_REQUEST['new_checksum'];
    }

    $_REQUEST['new_transactional'] = empty($_REQUEST['new_transactional']) ? '0' : '1';
    if ($is_maria
      && $_REQUEST['new_transactional'] !== $transactional) {
        $table_alters[] = 'TRANSACTIONAL = ' . $_REQUEST['new_transactional'];
    }

    $_REQUEST['new_page_checksum'] = empty($_REQUEST['new_page_checksum']) ? '0' : '1';
    if ($is_maria
      && $_REQUEST['new_page_checksum'] !== $page_checksum) {
        $table_alters[] = 'PAGE_CHECKSUM = ' . $_REQUEST['new_page_checksum'];
    }

    $delay_key_write = empty($delay_key_write) ? '0' : '1';
    $_REQUEST['new_delay_key_write'] = empty($_REQUEST['new_delay_key_write']) ? '0' : '1';
    if ($is_myisam_or_maria
      && $_REQUEST['new_delay_key_write'] !== $delay_key_write) {
        $table_alters[] = 'delay_key_write = ' . $_REQUEST['new_delay_key_write'];
    }

    if (($is_myisam_or_maria || $is_innodb || $is_pbxt)
      &&  ! empty($_REQUEST['new_auto_increment'])
      && (! isset($auto_increment) || $_REQUEST['new_auto_increment'] !== $auto_increment)) {
        $table_alters[] = 'auto_increment = ' . PMA_sqlAddslashes($_REQUEST['new_auto_increment']);
    }

    if (($is_myisam_or_maria || $is_innodb || $is_pbxt)
      &&  ! empty($_REQUEST['new_row_format'])
      && (! isset($row_format) || strtolower($_REQUEST['new_row_format']) !== strtolower($row_format))) {
        $table_alters[] = 'ROW_FORMAT = ' . PMA_sqlAddslashes($_REQUEST['new_row_format']);
    }

    if (count($table_alters) > 0) {
        $sql_query      = 'ALTER TABLE ' . PMA_backquote($GLOBALS['table']);
        $sql_query     .= "\r\n" . implode("\r\n", $table_alters);
        $result        .= PMA_DBI_query($sql_query) ? true : false;
        $reread_info    = true;
        unset($table_alters);
        foreach (PMA_DBI_get_warnings() as $warning) {
            // In MariaDB 5.1.44, when altering a table from Maria to MyISAM 
            // and if TRANSACTIONAL was set, the system reports an error;
            // I discussed with a Maria developer and he agrees that this
            // should not be reported with a Level of Error, so here
            // I just ignore it. But there are other 1478 messages
            // that it's better to show.
            if (! ($_REQUEST['new_tbl_type'] == 'MyISAM' && $warning['Code'] == '1478' && $warning['Level'] == 'Error')) {
                $warning_messages[] = $warning['Level'] . ': #' . $warning['Code']
                    . ' ' . $warning['Message'];
            }
        }
    }
}
/**
 * Reordering the table has been requested by the user
 */
if (isset($_REQUEST['submitorderby']) && ! empty($_REQUEST['order_field'])) {
    $sql_query = '
        ALTER TABLE ' . PMA_backquote($GLOBALS['table']) . '
        ORDER BY ' . PMA_backquote(urldecode($_REQUEST['order_field']));
    if (isset($_REQUEST['order_order']) && $_REQUEST['order_order'] === 'desc') {
        $sql_query .= ' DESC';
    }
    $result = PMA_DBI_query($sql_query);
} // end if

/**
 * A partition operation has been requested by the user
 */
if (isset($_REQUEST['submit_partition']) && ! empty($_REQUEST['partition_operation'])) {
    $sql_query = 'ALTER TABLE ' . PMA_backquote($GLOBALS['table']) . ' ' . $_REQUEST['partition_operation'] . ' PARTITION ' . $_REQUEST['partition_name'];
    $result = PMA_DBI_query($sql_query);
} // end if

if ($reread_info) {
    // to avoid showing the old value (for example the AUTO_INCREMENT) after
    // a change, clear the cache
    PMA_Table::$cache = array(); 
    $page_checksum = $checksum = $delay_key_write = 0;
    require './libraries/tbl_info.inc.php';
}
unset($reread_info);

/**
 * Displays top menu links
 */
require_once './libraries/tbl_links.inc.php';

if (isset($result) && empty($zero_rows)) {
    // set to success by default, because result set could be empty
    // (for example, a table rename)
    $_type = 'success';
    if (empty($_message)) {
        $_message = $result ? $strSuccess : $strError;
        // $result should exist, regardless of $_message
        $_type = $result ? 'success' : 'error';
    }
    if (! empty($warning_messages)) {
        $_message = new PMA_Message;
        $_message->addMessages($warning_messages);
        $_message->isWarning(true);
        unset($warning_messages);
    }
    PMA_showMessage($_message, $sql_query, $_type);
    unset($_message, $_type);
}

$url_params['goto'] = 'tbl_operations.php';
$url_params['back'] = 'tbl_operations.php';

/**
 * Get columns names
 */
$local_query = '
    SHOW COLUMNS
    FROM ' . PMA_backquote($GLOBALS['table']) . '
    FROM ' . PMA_backquote($GLOBALS['db']);
$columns = PMA_DBI_fetch_result($local_query, null, 'Field');
unset($local_query);

/**
 * Displays the page
 */
?>
<!-- Order the table -->
<div id="div_table_order">
<form method="post" action="tbl_operations.php">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']); ?>
<fieldset id="fieldset_table_order">
    <legend><?php echo $strAlterOrderBy; ?></legend>
    <select name="order_field">
<?php
foreach ($columns as $fieldname) {
    echo '            <option value="' . htmlspecialchars($fieldname) . '">'
        . htmlspecialchars($fieldname) . '</option>' . "\n";
}
unset($columns);
?>
    </select> <?php echo $strSingly; ?>
    <select name="order_order">
        <option value="asc"><?php echo $strAscending; ?></option>
        <option value="desc"><?php echo $strDescending; ?></option>
    </select>
    <input type="submit" name="submitorderby" value="<?php echo $strGo; ?>" />
</fieldset>
</form>
</div>

<!-- Move table -->
<div id="div_table_rename">
<form method="post" action="tbl_move_copy.php"
    onsubmit="return emptyFormElements(this, 'new_name')">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']); ?>
<input type="hidden" name="reload" value="1" />
<input type="hidden" name="what" value="data" />
<fieldset id="fieldset_table_rename">
    <legend><?php echo $strMoveTable; ?></legend>
<?php if (count($GLOBALS['pma']->databases) > $GLOBALS['cfg']['MaxDbList']) {
?>
    <input type="text" maxlength="100" size="30" name="target_db" value="<?php echo htmlspecialchars($GLOBALS['db']); ?>"/>
<?php
    } else {
?>
    <select name="target_db">
        <?php echo $GLOBALS['pma']->databases->getHtmlOptions(true, false); ?>
    </select>
<?php
    } // end if
?>
    &nbsp;<strong>.</strong>&nbsp;
    <input type="text" size="20" name="new_name" onfocus="this.select()"
value="<?php echo htmlspecialchars($GLOBALS['table']); ?>" /><br />
    <?php
    // starting with MySQL 5.0.24, SHOW CREATE TABLE includes the AUTO_INCREMENT
    // next value but users can decide if they want it or not for the operation
    ?>
    <input type="checkbox" name="sql_auto_increment" value="1" id="checkbox_auto_increment_mv" checked="checked" />
    <label for="checkbox_auto_increment_mv"><?php echo $strAddAutoIncrement; ?></label><br />
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit_move" value="<?php echo $strGo; ?>" />
</fieldset>
</form>
</div>

<?php
if (strstr($show_comment, '; InnoDB free') === false) {
    if (strstr($show_comment, 'InnoDB free') === false) {
        // only user entered comment
        $comment = $show_comment;
    } else {
        // here we have just InnoDB generated part
        $comment = '';
    }
} else {
    // remove InnoDB comment from end, just the minimal part (*? is non greedy)
    $comment = preg_replace('@; InnoDB free:.*?$@', '', $show_comment);
}

// PACK_KEYS: MyISAM or ISAM
// DELAY_KEY_WRITE, CHECKSUM, : MyISAM only
// AUTO_INCREMENT: MyISAM and InnoDB since 5.0.3, PBXT

// nijel: Here should be version check for InnoDB, however it is supported
// in >5.0.4, >4.1.12 and >4.0.11, so I decided not to
// check for version
?>

<!-- Table options -->
<div id="div_table_options">
<form method="post" action="tbl_operations.php">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']); ?>
<input type="hidden" name="reload" value="1" />
<fieldset>
    <legend><?php echo $strTableOptions; ?></legend>

    <table>
    <!-- Change table name -->
    <tr><td><?php echo $strRenameTable; ?></td>
        <td><input type="text" size="20" name="new_name" onfocus="this.select()"
                value="<?php echo htmlspecialchars($GLOBALS['table']); ?>" />
        </td>
    </tr>

    <!-- Table comments -->
    <tr><td><?php echo $strTableComments; ?></td>
        <td><input type="text" name="comment" maxlength="60" size="30"
                value="<?php echo htmlspecialchars($comment); ?>" onfocus="this.select()" />
            <input type="hidden" name="prev_comment" value="<?php echo htmlspecialchars($comment); ?>" />
        </td>
    </tr>

    <!-- Storage engine -->
    <tr><td><?php echo $strStorageEngine; ?>
            <?php echo PMA_showMySQLDocu('Storage_engines', 'Storage_engines'); ?>
        </td>
        <td><?php echo PMA_StorageEngine::getHtmlSelect('new_tbl_type', null, $tbl_type); ?>
        </td>
    </tr>

    <!-- Table character set -->
    <tr><td><?php echo $strCollation; ?></td>
        <td><?php echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION,
                'tbl_collation', null, $tbl_collation, false, 3); ?>
        </td>
    </tr>
<?php
if ($is_myisam_or_maria || $is_isam) {
    ?>
    <tr>
        <td><label for="new_pack_keys">PACK_KEYS</label></td>
        <td><select name="new_pack_keys" id="new_pack_keys">
                <option value="DEFAULT"
                    <?php if ($pack_keys == 'DEFAULT') echo 'selected="selected"'; ?>
                    >DEFAULT</option>
                <option value="0"
                    <?php if ($pack_keys == '0') echo 'selected="selected"'; ?>
                    >0</option>
                <option value="1"
                    <?php if ($pack_keys == '1') echo 'selected="selected"'; ?>
                    >1</option>
            </select>
        </td>
    </tr>
    <?php
} // end if (MYISAM|ISAM)

if ($is_myisam_or_maria) {
    ?>
    <tr><td><label for="new_checksum">CHECKSUM</label></td>
        <td><input type="checkbox" name="new_checksum" id="new_checksum"
                value="1"
    <?php echo (isset($checksum) && $checksum == 1)
        ? ' checked="checked"'
        : ''; ?> />
        </td>
    </tr>

    <tr><td><label for="new_delay_key_write">DELAY_KEY_WRITE</label></td>
        <td><input type="checkbox" name="new_delay_key_write" id="new_delay_key_write"
                value="1"
    <?php echo (isset($delay_key_write) && $delay_key_write == 1)
        ? ' checked="checked"'
        : ''; ?> />
        </td>
    </tr>

    <?php
} // end if (MYISAM)

if ($is_maria) {
    ?>
    <tr><td><label for="new_transactional">TRANSACTIONAL</label></td>
        <td><input type="checkbox" name="new_transactional" id="new_transactional"
                value="1"
    <?php echo (isset($transactional) && $transactional == 1)
        ? ' checked="checked"'
        : ''; ?> />
        </td>
    </tr>

    <tr><td><label for="new_page_checksum">PAGE_CHECKSUM</label></td>
        <td><input type="checkbox" name="new_page_checksum" id="new_page_checksum"
                value="1"
    <?php echo (isset($page_checksum) && $page_checksum == 1)
        ? ' checked="checked"'
        : ''; ?> />
        </td>
    </tr>

    <?php
} // end if (MARIA)

if (isset($auto_increment) && strlen($auto_increment) > 0
  && ($is_myisam_or_maria || $is_innodb || $is_pbxt)) {
    ?>
    <tr><td><label for="auto_increment_opt">AUTO_INCREMENT</label></td>
        <td><input type="text" name="new_auto_increment" id="auto_increment_opt"
                value="<?php echo $auto_increment; ?>" /></td>
    </tr>
    <?php
} // end if (MYISAM|INNODB)

// the outer array is for engines, the inner array contains the dropdown
// option values as keys then the dropdown option labels

$possible_row_formats = array(
     'MARIA'  => array(
        'FIXED'     => 'FIXED',
        'DYNAMIC'   => 'DYNAMIC',
        'PAGE'      => 'PAGE'
            ),
     'MYISAM' => array(
         'FIXED'    => 'FIXED',
         'DYNAMIC'  => 'DYNAMIC'
     ),
     'PBXT'   => array(
         'FIXED'    => 'FIXED',
         'DYNAMIC'  => 'DYNAMIC'
     ),
     'INNODB' => array(
         'COMPACT'  => 'COMPACT',
         'REDUNDANT' => 'REDUNDANT')
);

$innodb_engine_plugin = PMA_StorageEngine::getEngine('innodb');
$innodb_plugin_version = $innodb_engine_plugin->getInnodbPluginVersion();
if (!empty($innodb_plugin_version)) {
    $innodb_file_format = $innodb_engine_plugin->getInnodbFileFormat();
}  else {
    $innodb_file_format = '';
}
if ('Barracuda' == $innodb_file_format && $innodb_engine_plugin->supportsFilePerTable()) {
    $possible_row_formats['INNODB']['DYNAMIC'] = 'DYNAMIC';
    $possible_row_formats['INNODB']['COMPRESSED'] = 'COMPRESSED';
}
unset($innodb_engine_plugin, $innodb_plugin_version, $innodb_file_format);

// for MYISAM there is also COMPRESSED but it can be set only by the
// myisampack utility, so don't offer here the choice because if we
// try it inside an ALTER TABLE, MySQL (at least in 5.1.23-maria)
// does not return a warning
// (if the table was compressed, it can be seen on the Structure page)

if (isset($possible_row_formats[$tbl_type])) {
    $current_row_format = strtoupper($showtable['Row_format']);
    echo '<tr><td><label for="new_row_format">ROW_FORMAT</label></td>';
    echo '<td>';
    echo PMA_generate_html_dropdown('new_row_format', $possible_row_formats[$tbl_type], $current_row_format, 'new_row_format');
    unset($possible_row_formats, $current_row_format);
    echo '</td>';
    echo '</tr>';
}
?>
    </table>
</fieldset>
<fieldset class="tblFooters">
        <input type="submit" name="submitoptions" value="<?php echo $strGo; ?>" />
</fieldset>
</form>
</div>

<!-- Copy table -->
<div id="div_table_copy">
<form method="post" action="tbl_move_copy.php"
    onsubmit="return emptyFormElements(this, 'new_name')">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']); ?>
<input type="hidden" name="reload" value="1" />
<fieldset>
    <legend><?php echo $strCopyTable; ?></legend>
<?php if (count($GLOBALS['pma']->databases) > $GLOBALS['cfg']['MaxDbList']) {
?>
    <input type="text" maxlength="100" size="30" name="target_db" value="<?php echo htmlspecialchars($GLOBALS['db']); ?>"/>
<?php
    } else {
?>
    <select name="target_db">
        <?php echo $GLOBALS['pma']->databases->getHtmlOptions(true, false); ?>
    </select>
<?php
    } // end if
?>
    &nbsp;<strong>.</strong>&nbsp;
    <input type="text" size="20" name="new_name" onfocus="this.select()" /><br />
<?php
        $choices = array(
            'structure' => $strStrucOnly,
            'data'      => $strStrucData,
            'dataonly'  => $strDataOnly);
        PMA_display_html_radio('what', $choices, 'data', true);
        unset($choices);
?>

    <input type="checkbox" name="drop_if_exists" value="true" id="checkbox_drop" />
    <label for="checkbox_drop"><?php echo sprintf($strAddClause, 'DROP TABLE'); ?></label><br />
    <input type="checkbox" name="sql_auto_increment" value="1" id="checkbox_auto_increment_cp" />
    <label for="checkbox_auto_increment_cp"><?php echo $strAddAutoIncrement; ?></label><br />
    <?php
        // display "Add constraints" choice only if there are
        // foreign keys
        if (PMA_getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'foreign')) {
        ?>
    <input type="checkbox" name="add_constraints" value="1" id="checkbox_constraints" />
    <label for="checkbox_constraints"><?php echo $strAddConstraints; ?></label><br />
        <?php
        } // endif
        if (isset($_COOKIE['pma_switch_to_new'])
          && $_COOKIE['pma_switch_to_new'] == 'true') {
            $pma_switch_to_new = 'true';
        }
    ?>
    <input type="checkbox" name="switch_to_new" value="true"
        id="checkbox_switch"<?php echo
            isset($pma_switch_to_new) && $pma_switch_to_new == 'true'
            ? ' checked="checked"'
            : ''; ?> />
    <label for="checkbox_switch"><?php echo $strSwitchToTable; ?></label>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit_copy" value="<?php echo $strGo; ?>" />
</fieldset>
</form>
</div>

<br class="clearfloat"/>

<div id="div_table_maintenance">
<fieldset>
 <legend><?php echo $strTableMaintenance; ?></legend>

<ul>
<?php
// Note: BERKELEY (BDB) is no longer supported, starting with MySQL 5.1
if ($is_myisam_or_maria || $is_innodb || $is_berkeleydb) {
    if ($is_myisam_or_maria || $is_innodb) {
        $this_url_params = array_merge($url_params,
            array('sql_query' => 'CHECK TABLE ' . PMA_backquote($GLOBALS['table'])));
        ?>
    <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo $strCheckTable; ?></a>
        <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'CHECK_TABLE'); ?>
    </li>
        <?php
    }
    if ($is_innodb) {
        $this_url_params = array_merge($url_params,
            array('sql_query' => 'ALTER TABLE ' . PMA_backquote($GLOBALS['table']) . ' ENGINE = InnoDB'));
        ?>
    <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo $strDefragment; ?></a>
        <?php echo PMA_showMySQLDocu('Table_types', 'InnoDB_File_Defragmenting'); ?>
    </li>
        <?php
    }
    if ($is_myisam_or_maria || $is_berkeleydb) {
        $this_url_params = array_merge($url_params,
            array('sql_query' => 'ANALYZE TABLE ' . PMA_backquote($GLOBALS['table'])));
        ?>
    <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo $strAnalyzeTable; ?></a>
        <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'ANALYZE_TABLE');?>
    </li>
        <?php
    }
    if ($is_myisam_or_maria) {
        $this_url_params = array_merge($url_params,
            array('sql_query' => 'REPAIR TABLE ' . PMA_backquote($GLOBALS['table'])));
        ?>
    <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo $strRepairTable; ?></a>
        <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'REPAIR_TABLE'); ?>
    </li>
        <?php
    }
    if ($is_myisam_or_maria || $is_innodb || $is_berkeleydb) {
        $this_url_params = array_merge($url_params,
            array('sql_query' => 'OPTIMIZE TABLE ' . PMA_backquote($GLOBALS['table'])));
        ?>
    <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo $strOptimizeTable; ?></a>
        <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'OPTIMIZE_TABLE'); ?>
    </li>
        <?php
    }
} // end MYISAM or BERKELEYDB case
$this_url_params = array_merge($url_params,
    array(
        'sql_query' => 'FLUSH TABLE ' . PMA_backquote($GLOBALS['table']),
        'zero_rows' => sprintf($strTableHasBeenFlushed,
            htmlspecialchars($GLOBALS['table'])),
        'reload'    => 1,
        ));
?>
    <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo $strFlushTable; ?></a>
        <?php echo PMA_showMySQLDocu('MySQL_Database_Administration', 'FLUSH'); ?>
    </li>
</ul>
</fieldset>
</div>
<?php if (PMA_Partition::havePartitioning()) {
    $partition_names = PMA_Partition::getPartitionNames($db, $table);
    // show the Partition maintenance section only if we detect a partition
    if (! is_null($partition_names[0])) {
    ?>
<div id="div_partition_maintenance">
<form method="post" action="tbl_operations.php">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']); ?>
<fieldset>
 <legend><?php echo $strPartitionMaintenance; ?></legend>
<?php
        $html_select = '<select name="partition_name">' . "\n";
        foreach($partition_names as $one_partition) {
            $one_partition = htmlspecialchars($one_partition);
            $html_select .= '<option value="' . $one_partition . '">' . $one_partition . '</option>' . "\n";
        }
        $html_select .= '</select>' . "\n";
        printf($GLOBALS['strPartition'], $html_select);
        unset($partition_names, $one_partition, $html_select);
        $choices = array(
            'ANALYZE' => $strAnalyze,
            'CHECK' => $strCheck,
            'OPTIMIZE' => $strOptimize,
            'REBUILD' => $strRebuild,
            'REPAIR' => $strRepair);
        PMA_display_html_radio('partition_operation', $choices, '', false);
        unset($choices);
        echo PMA_showMySQLDocu('partitioning_maintenance', 'partitioning_maintenance');
        // I'm not sure of the best way to display that; this link does
        // not depend on the Go button
    $this_url_params = array_merge($url_params,
        array(
            'sql_query' => 'ALTER TABLE ' . PMA_backquote($GLOBALS['table']) . ' REMOVE PARTITIONING'
            ));
?>
    <br /><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo $strRemovePartitioning; ?></a>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit_partition" value="<?php echo $strGo; ?>" />
</fieldset>
</form>
</div>
<?php
        } // end if
    } // end if

// Referential integrity check
// The Referential integrity check was intended for the non-InnoDB
// tables for which the relations are defined in pmadb
// so I assume that if the current table is InnoDB, I don't display
// this choice (InnoDB maintains integrity by itself)

if ($cfgRelation['relwork'] && ! $is_innodb) {
    PMA_DBI_select_db($GLOBALS['db']);
    $foreign = PMA_getForeigners($GLOBALS['db'], $GLOBALS['table']);

    if ($foreign) {
        ?>
    <!-- Referential integrity check -->
<div id="div_referential_integrity">
<fieldset>
 <legend><?php echo $strReferentialIntegrity; ?></legend>
    <ul>
        <?php
        echo "\n";
        foreach ($foreign AS $master => $arr) {
            $join_query  = 'SELECT ' . PMA_backquote($GLOBALS['table']) . '.* FROM '
                         . PMA_backquote($GLOBALS['table']) . ' LEFT JOIN '
                         . PMA_backquote($arr['foreign_table']);
            if ($arr['foreign_table'] == $GLOBALS['table']) {
                $foreign_table = $GLOBALS['table'] . '1';
                $join_query .= ' AS ' . PMA_backquote($foreign_table);
            } else {
                $foreign_table = $arr['foreign_table'];
            }
            $join_query .= ' ON '
                         . PMA_backquote($GLOBALS['table']) . '.' . PMA_backquote($master)
                         . ' = ' . PMA_backquote($foreign_table) . '.' . PMA_backquote($arr['foreign_field'])
                         . ' WHERE '
                         . PMA_backquote($foreign_table) . '.' . PMA_backquote($arr['foreign_field'])
                         . ' IS NULL AND '
                         . PMA_backquote($GLOBALS['table']) . '.' . PMA_backquote($master)
                         . ' IS NOT NULL';
            $this_url_params = array_merge($url_params,
                array('sql_query' => $join_query));
            echo '        <li>'
                 . '<a href="sql.php'
                 . PMA_generate_common_url($this_url_params)
                 . '">' . $master . '&nbsp;->&nbsp;' . $arr['foreign_table'] . '.' . $arr['foreign_field']
                 . '</a></li>' . "\n";
        } //  foreach $foreign
        unset($foreign_table, $join_query);
        ?>
    </ul>
   </fieldset>
  </div>
        <?php
    } // end if ($foreign)

} // end  if (!empty($cfg['Server']['relation']))


/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';


function PMA_set_global_variables_for_engine($tbl_type)
{
    global $is_myisam_or_maria, $is_innodb, $is_isam, $is_berkeleydb, $is_maria, $is_pbxt;

    $is_myisam_or_maria = $is_isam = $is_innodb = $is_berkeleydb = $is_maria = $is_pbxt = false;
    $upper_tbl_type = strtoupper($tbl_type);

    //Options that apply to MYISAM usually apply to MARIA
    $is_myisam_or_maria = ($upper_tbl_type == 'MYISAM' || $upper_tbl_type == 'MARIA');
    $is_maria = ($upper_tbl_type == 'MARIA');

    $is_isam = ($upper_tbl_type == 'ISAM');
    $is_innodb = ($upper_tbl_type == 'INNODB');
    $is_berkeleydb = ($upper_tbl_type == 'BERKELEYDB');
    $is_pbxt = ($upper_tbl_type == 'PBXT');
}

?>
