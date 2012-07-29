<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

/**
 * functions implementation for this script
 */
require_once 'libraries/operations.lib.php';

$pma_table = new PMA_Table($GLOBALS['table'], $GLOBALS['db']);
$common_functions = PMA_CommonFunctions::getInstance();

/**
 * Runs common work
 */
require 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_operations.php&amp;back=tbl_operations.php';
$url_params['goto'] = $url_params['back'] = 'tbl_operations.php';

/**
 * Gets relation settings
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * Gets available MySQL charsets and storage engines
 */
require_once 'libraries/mysql_charsets.lib.php';
require_once 'libraries/StorageEngine.class.php';

/**
 * Class for partition management
 */
require_once 'libraries/Partition.class.php';

// reselect current db (needed in some cases probably due to
// the calling of relation.lib.php)
PMA_DBI_select_db($GLOBALS['db']);

/**
 * Gets tables informations
 */

require 'libraries/tbl_info.inc.php';

// define some globals here, for improved syntax in the conditionals
$is_myisam_or_aria = $is_isam = $is_innodb = $is_berkeleydb = $is_aria = $is_pbxt = false;
// set initial value of these globals, based on the current table engine
PMA_set_global_variables_for_engine($tbl_storage_engine);

if ($is_aria) {
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
 * If the table has to be moved to some other database
 */
if (isset($_REQUEST['submit_move']) || isset($_REQUEST['submit_copy'])) {
    $_message = '';
    include_once 'tbl_move_copy.php';
}
/**
 * If the table has to be maintained
 */
if (isset($_REQUEST['table_maintenance'])) {
    include_once 'sql.php';
    unset($result);
}
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
        && urldecode($_REQUEST['prev_comment']) !== $_REQUEST['comment']
    ) {
        $table_alters[] = 'COMMENT = \'' . $common_functions->sqlAddSlashes($_REQUEST['comment']) . '\'';
    }
    if (! empty($_REQUEST['new_tbl_storage_engine'])
        && strtolower($_REQUEST['new_tbl_storage_engine']) !== strtolower($tbl_storage_engine)
    ) {
        $table_alters[] = 'ENGINE = ' . $_REQUEST['new_tbl_storage_engine'];
        $tbl_storage_engine = $_REQUEST['new_tbl_storage_engine'];
        // reset the globals for the new engine
        PMA_set_global_variables_for_engine($tbl_storage_engine);
        if ($is_aria) {
            $transactional = (isset($transactional) && $transactional == '0') ? '0' : '1';
            $page_checksum = (isset($page_checksum)) ? $page_checksum : '';
        }
    }

    if (! empty($_REQUEST['tbl_collation'])
        && $_REQUEST['tbl_collation'] !== $tbl_collation
    ) {
        $table_alters[] = 'DEFAULT ' . PMA_generateCharsetQueryPart($_REQUEST['tbl_collation']);
    }

    if (($is_myisam_or_aria || $is_isam)
        && isset($_REQUEST['new_pack_keys'])
        && $_REQUEST['new_pack_keys'] != (string)$pack_keys
    ) {
        $table_alters[] = 'pack_keys = ' . $_REQUEST['new_pack_keys'];
    }

    $checksum = empty($checksum) ? '0' : '1';
    $_REQUEST['new_checksum'] = empty($_REQUEST['new_checksum']) ? '0' : '1';
    if ($is_myisam_or_aria
        && $_REQUEST['new_checksum'] !== $checksum
    ) {
        $table_alters[] = 'checksum = ' . $_REQUEST['new_checksum'];
    }

    $_REQUEST['new_transactional'] = empty($_REQUEST['new_transactional']) ? '0' : '1';
    if ($is_aria
        && $_REQUEST['new_transactional'] !== $transactional
    ) {
        $table_alters[] = 'TRANSACTIONAL = ' . $_REQUEST['new_transactional'];
    }

    $_REQUEST['new_page_checksum'] = empty($_REQUEST['new_page_checksum']) ? '0' : '1';
    if ($is_aria
        && $_REQUEST['new_page_checksum'] !== $page_checksum
    ) {
        $table_alters[] = 'PAGE_CHECKSUM = ' . $_REQUEST['new_page_checksum'];
    }

    $delay_key_write = empty($delay_key_write) ? '0' : '1';
    $_REQUEST['new_delay_key_write'] = empty($_REQUEST['new_delay_key_write']) ? '0' : '1';
    if ($is_myisam_or_aria
        && $_REQUEST['new_delay_key_write'] !== $delay_key_write
    ) {
        $table_alters[] = 'delay_key_write = ' . $_REQUEST['new_delay_key_write'];
    }

    if (($is_myisam_or_aria || $is_innodb || $is_pbxt)
        &&  ! empty($_REQUEST['new_auto_increment'])
        && (! isset($auto_increment) || $_REQUEST['new_auto_increment'] !== $auto_increment)
    ) {
        $table_alters[] = 'auto_increment = ' . $common_functions->sqlAddSlashes($_REQUEST['new_auto_increment']);
    }

    if (($is_myisam_or_aria || $is_innodb || $is_pbxt)
        &&  ! empty($_REQUEST['new_row_format'])
        && (! isset($row_format) || strtolower($_REQUEST['new_row_format']) !== strtolower($row_format))
    ) {
        $table_alters[] = 'ROW_FORMAT = ' . $common_functions->sqlAddSlashes($_REQUEST['new_row_format']);
    }

    if (count($table_alters) > 0) {
        $sql_query      = 'ALTER TABLE ' . $common_functions->backquote($GLOBALS['table']);
        $sql_query     .= "\r\n" . implode("\r\n", $table_alters);
        $sql_query     .= ';';
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
            if (! ($_REQUEST['new_tbl_storage_engine'] == 'MyISAM' && $warning['Code'] == '1478' && $warning['Level'] == 'Error')) {
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
        ALTER TABLE ' . $common_functions->backquote($GLOBALS['table']) . '
        ORDER BY ' . $common_functions->backquote(urldecode($_REQUEST['order_field']));
    if (isset($_REQUEST['order_order']) && $_REQUEST['order_order'] === 'desc') {
        $sql_query .= ' DESC';
    }
    $sql_query .= ';';
    $result = PMA_DBI_query($sql_query);
} // end if

/**
 * A partition operation has been requested by the user
 */
if (isset($_REQUEST['submit_partition']) && ! empty($_REQUEST['partition_operation'])) {
    $sql_query = 'ALTER TABLE ' . $common_functions->backquote($GLOBALS['table']) . ' ' . $_REQUEST['partition_operation'] . ' PARTITION ' . $_REQUEST['partition_name'] . ';';
    $result = PMA_DBI_query($sql_query);
} // end if

if ($reread_info) {
    // to avoid showing the old value (for example the AUTO_INCREMENT) after
    // a change, clear the cache
    PMA_Table::$cache = array();
    $page_checksum = $checksum = $delay_key_write = 0;
    include 'libraries/tbl_info.inc.php';
}
unset($reread_info);

if (isset($result) && empty($message_to_show)) {
    // set to success by default, because result set could be empty
    // (for example, a table rename)
    $_type = 'success';
    if (empty($_message)) {
        $_message = $result ? $message = PMA_Message::success(__('Your SQL query has been executed successfully')) : PMA_Message::error(__('Error'));
        // $result should exist, regardless of $_message
        $_type = $result ? 'success' : 'error';
        if (isset($GLOBALS['ajax_request']) && $GLOBALS['ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->isSuccess($_message->isSuccess());
            $response->addJSON('message', $_message);
            $response->addJSON(
                'sql_query', $common_functions->getMessage(null, $sql_query)
            );
            exit;
        }
    }
    if (! empty($warning_messages)) {
        $_message = new PMA_Message;
        $_message->addMessages($warning_messages);
        $_message->isError(true);
        if ($GLOBALS['ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->isSuccess(false);
            $response->addJSON('message', $_message);
            exit;
        }
        unset($warning_messages);
    }

    echo $common_functions->getMessage($_message, $sql_query, $_type);
    unset($_message, $_type);
}

$url_params['goto']
    = $url_params['back']
        = 'tbl_operations.php';

/**
 * Get columns names
 */
$columns = PMA_DBI_get_columns($GLOBALS['db'], $GLOBALS['table']);

/**
 * Displays the page
 */
/**
 * Order the table
 */
echo PMA_getHtmlForOrderTheTable($columns);

/**
 * Move table
 */
echo PMA_getHtmlForMoveTable();

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

// Here should be version check for InnoDB, however it is supported
// in >5.0.4, >4.1.12 and >4.0.11, so I decided not to
// check for version

echo PMA_getTableOptionDiv($comment, $tbl_collation, $tbl_storage_engine,
    $is_myisam_or_aria, $is_isam, $pack_keys, $delay_key_write, $auto_increment,
    $transactional, $page_checksum, $is_innodb, $is_pbxt, $is_aria
);

/**
 * Copy table
 */
echo PMA_getHtmlForCopytable();

?>
<br class="clearfloat"/>

<div class="operations_half_width">
<fieldset>
 <legend><?php echo __('Table maintenance'); ?></legend>

<ul id="tbl_maintenance" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : '');?>>
<?php
// Note: BERKELEY (BDB) is no longer supported, starting with MySQL 5.1
if ($is_myisam_or_aria || $is_innodb || $is_berkeleydb) {
    if ($is_myisam_or_aria || $is_innodb) {
        $this_url_params = array_merge(
            $url_params,
            array(
                'sql_query' => 'CHECK TABLE ' . $common_functions->backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
                )
        );
        ?>
    <li><a class='maintain_action' href="tbl_operations.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo __('Check table'); ?></a>
        <?php echo $common_functions->showMySQLDocu('MySQL_Database_Administration', 'CHECK_TABLE'); ?>
    </li>
        <?php
    }
    if ($is_innodb) {
        $this_url_params = array_merge(
            $url_params,
            array('sql_query' => 'ALTER TABLE ' . $common_functions->backquote($GLOBALS['table']) . ' ENGINE = InnoDB;')
        );
        ?>
    <li><a class='maintain_action' href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo __('Defragment table'); ?></a>
        <?php echo $common_functions->showMySQLDocu('Table_types', 'InnoDB_File_Defragmenting'); ?>
    </li>
        <?php
    }
    if ($is_myisam_or_aria || $is_berkeleydb) {
        $this_url_params = array_merge(
            $url_params,
            array(
                'sql_query' => 'ANALYZE TABLE ' . $common_functions->backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
                )
        );
        ?>
    <li><a class='maintain_action' href="tbl_operations.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo __('Analyze table'); ?></a>
        <?php echo $common_functions->showMySQLDocu('MySQL_Database_Administration', 'ANALYZE_TABLE');?>
    </li>
        <?php
    }
    if ($is_myisam_or_aria && !PMA_DRIZZLE) {
        $this_url_params = array_merge(
            $url_params,
            array(
                'sql_query' => 'REPAIR TABLE ' . $common_functions->backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
                )
        );
        ?>
    <li><a class='maintain_action' href="tbl_operations.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo __('Repair table'); ?></a>
        <?php echo $common_functions->showMySQLDocu('MySQL_Database_Administration', 'REPAIR_TABLE'); ?>
    </li>
        <?php
    }
    if (($is_myisam_or_aria || $is_innodb || $is_berkeleydb) && !PMA_DRIZZLE) {
        $this_url_params = array_merge(
            $url_params,
            array(
                'sql_query' => 'OPTIMIZE TABLE ' . $common_functions->backquote($GLOBALS['table']),
                'table_maintenance' => 'Go',
                )
        );
        ?>
    <li><a class='maintain_action' href="tbl_operations.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo __('Optimize table'); ?></a>
        <?php echo $common_functions->showMySQLDocu('MySQL_Database_Administration', 'OPTIMIZE_TABLE'); ?>
    </li>
        <?php
    }
} // end MYISAM or BERKELEYDB case
$this_url_params = array_merge(
    $url_params,
    array(
        'sql_query' => 'FLUSH TABLE ' . $common_functions->backquote($GLOBALS['table']),
        'message_to_show' => sprintf(
            __('Table %s has been flushed'),
            htmlspecialchars($GLOBALS['table'])
        ),
        'reload'    => 1,
    )
);
?>
    <li><a class='maintain_action' href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo __('Flush the table (FLUSH)'); ?></a>
        <?php echo $common_functions->showMySQLDocu('MySQL_Database_Administration', 'FLUSH'); ?>
    </li>
</ul>
</fieldset>
</div>
<?php if (! (isset($db_is_information_schema) && $db_is_information_schema)) { ?>
<div class="operations_half_width">
<fieldset class="caution">
 <legend><?php echo __('Delete data or table'); ?></legend>

<ul>
<?php
if (! $tbl_is_view && ! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $this_sql_query = 'TRUNCATE TABLE ' . $common_functions->backquote($GLOBALS['table']);
    $this_url_params = array_merge(
        $url_params,
        array(
            'sql_query' => $this_sql_query,
            'goto' => 'tbl_structure.php',
            'reload' => '1',
            'message_to_show' => sprintf(__('Table %s has been emptied'), htmlspecialchars($table)),
        )
    );
    ?>
    <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? 'id="truncate_tbl_anchor" class="ajax"' : ''); ?>>
            <?php echo __('Empty the table (TRUNCATE)'); ?></a>
        <?php echo $common_functions->showMySQLDocu('SQL-Syntax', 'TRUNCATE_TABLE'); ?>
    </li>
<?php
}
if (! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $this_sql_query = 'DROP TABLE ' . $common_functions->backquote($GLOBALS['table']);
    $this_url_params = array_merge(
        $url_params,
        array(
            'sql_query' => $this_sql_query,
            'goto' => 'db_operations.php',
            'reload' => '1',
            'purge' => '1',
            'message_to_show' => sprintf(($tbl_is_view ? __('View %s has been dropped') : __('Table %s has been dropped')), htmlspecialchars($table)),
            // table name is needed to avoid running
            // PMA_relationsCleanupDatabase() on the whole db later
            'table' => $GLOBALS['table'],
        )
    );
    ?>
    <li><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? 'id="drop_tbl_anchor"' : ''); ?>>
            <?php echo __('Delete the table (DROP)'); ?></a>
        <?php echo $common_functions->showMySQLDocu('SQL-Syntax', 'DROP_TABLE'); ?>
    </li>
<?php
}
?>
</ul>
</fieldset>
</div>
<?php
}
?>
<br class="clearfloat">
<?php if (PMA_Partition::havePartitioning()) {
    $partition_names = PMA_Partition::getPartitionNames($db, $table);
    // show the Partition maintenance section only if we detect a partition
    if (! is_null($partition_names[0])) {
    ?>
<div class="operations_half_width">
<form method="post" action="tbl_operations.php">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']); ?>
<fieldset>
 <legend><?php echo __('Partition maintenance'); ?></legend>
<?php
        $html_select = '<select name="partition_name">' . "\n";
        foreach ($partition_names as $one_partition) {
            $one_partition = htmlspecialchars($one_partition);
            $html_select .= '<option value="' . $one_partition . '">' . $one_partition . '</option>' . "\n";
        }
        $html_select .= '</select>' . "\n";
        printf(__('Partition %s'), $html_select);
        unset($partition_names, $one_partition, $html_select);
        $choices = array(
            'ANALYZE' => __('Analyze'),
            'CHECK' => __('Check'),
            'OPTIMIZE' => __('Optimize'),
            'REBUILD' => __('Rebuild'),
            'REPAIR' => __('Repair'));
        echo $common_functions->getRadioFields('partition_operation', $choices, '', false);
        unset($choices);
        echo $common_functions->showMySQLDocu('partitioning_maintenance', 'partitioning_maintenance');
        // I'm not sure of the best way to display that; this link does
        // not depend on the Go button
    $this_url_params = array_merge(
        $url_params,
        array(
            'sql_query' => 'ALTER TABLE ' . $common_functions->backquote($GLOBALS['table']) . ' REMOVE PARTITIONING;'
            )
        );
?>
    <br /><a href="sql.php<?php echo PMA_generate_common_url($this_url_params); ?>">
            <?php echo __('Remove partitioning'); ?></a>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit_partition" value="<?php echo __('Go'); ?>" />
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
<div class="operations_half_width">
<fieldset>
 <legend><?php echo __('Check referential integrity:'); ?></legend>
    <ul>
        <?php
        echo "\n";
        foreach ($foreign AS $master => $arr) {
            $join_query  = 'SELECT ' . $common_functions->backquote($GLOBALS['table']) . '.* FROM '
                         . $common_functions->backquote($GLOBALS['table']) . ' LEFT JOIN '
                         . $common_functions->backquote($arr['foreign_table']);
            if ($arr['foreign_table'] == $GLOBALS['table']) {
                $foreign_table = $GLOBALS['table'] . '1';
                $join_query .= ' AS ' . $common_functions->backquote($foreign_table);
            } else {
                $foreign_table = $arr['foreign_table'];
            }
            $join_query .= ' ON '
                         . $common_functions->backquote($GLOBALS['table']) . '.' . $common_functions->backquote($master)
                         . ' = ' . $common_functions->backquote($foreign_table) . '.' . $common_functions->backquote($arr['foreign_field'])
                         . ' WHERE '
                         . $common_functions->backquote($foreign_table) . '.' . $common_functions->backquote($arr['foreign_field'])
                         . ' IS NULL AND '
                         . $common_functions->backquote($GLOBALS['table']) . '.' . $common_functions->backquote($master)
                         . ' IS NOT NULL';
            $this_url_params = array_merge(
                $url_params,
                array('sql_query' => $join_query)
            );
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

function PMA_set_global_variables_for_engine($tbl_storage_engine)
{
    global $is_myisam_or_aria, $is_innodb, $is_isam, $is_berkeleydb, $is_aria, $is_pbxt;

    $is_myisam_or_aria = $is_isam = $is_innodb = $is_berkeleydb = $is_aria = $is_pbxt = false;
    $upper_tbl_storage_engine = strtoupper($tbl_storage_engine);

    //Options that apply to MYISAM usually apply to ARIA
    $is_myisam_or_aria = ($upper_tbl_storage_engine == 'MYISAM' || $upper_tbl_storage_engine == 'ARIA' || $upper_tbl_storage_engine == 'MARIA');
    $is_aria = ($upper_tbl_storage_engine == 'ARIA');

    $is_isam = ($upper_tbl_storage_engine == 'ISAM');
    $is_innodb = ($upper_tbl_storage_engine == 'INNODB');
    $is_berkeleydb = ($upper_tbl_storage_engine == 'BERKELEYDB');
    $is_pbxt = ($upper_tbl_storage_engine == 'PBXT');
}

?>
