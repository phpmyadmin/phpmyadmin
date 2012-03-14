<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

// Run common work
require_once './libraries/common.inc.php';

define('TABLE_MAY_BE_ABSENT', true);
require './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_tracking.php&amp;back=tbl_tracking.php';
$url_params['goto'] = 'tbl_tracking.php';;
$url_params['back'] = 'tbl_tracking.php';

// Init vars for tracking report
if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    $data = PMA_Tracker::getTrackedData($_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version']);

    $selection_schema = false;
    $selection_data   = false;
    $selection_both  = false;

    if (! isset($_REQUEST['logtype'])) {
        $_REQUEST['logtype'] = 'schema_and_data';
    }
    if ($_REQUEST['logtype'] == 'schema') {
        $selection_schema = true;
    } elseif ($_REQUEST['logtype'] == 'data') {
        $selection_data   = true;
    } else {
        $selection_both   = true;
    }
    if (! isset($_REQUEST['date_from'])) {
        $_REQUEST['date_from'] = $data['date_from'];
    }
    if (! isset($_REQUEST['date_to'])) {
        $_REQUEST['date_to'] = $data['date_to'];
    }
    if (! isset($_REQUEST['users'])) {
        $_REQUEST['users'] = '*';
    }
    $filter_ts_from = strtotime($_REQUEST['date_from']);
    $filter_ts_to   = strtotime($_REQUEST['date_to']);
    $filter_users   = array_map('trim', explode(',', $_REQUEST['users']));
}

// Prepare export
if (isset($_REQUEST['report_export'])) {

/**
 * Filters tracking entries
 *
 * @param array   the entries to filter
 * @param string  "from" date
 * @param string  "to" date
 * @param string  users
 *
 * @return  array   filtered entries
 *
 */
    function PMA_filter_tracking($data, $filter_ts_from, $filter_ts_to, $filter_users) {
        $tmp_entries = array();
        $id = 0;
        foreach ( $data as $entry ) {
            $timestamp = strtotime($entry['date']);

            if ($timestamp >= $filter_ts_from && $timestamp <= $filter_ts_to &&
              ( in_array('*', $filter_users) || in_array($entry['username'], $filter_users) ) ) {
                $tmp_entries[] = array( 'id' => $id,
                                    'timestamp' => $timestamp,
                                    'username'  => $entry['username'],
                                    'statement' => $entry['statement']
                             );
            }
            $id++;
        }
        return($tmp_entries);
    }

    $entries = array();
    // Filtering data definition statements
    if ($_REQUEST['logtype'] == 'schema' || $_REQUEST['logtype'] == 'schema_and_data') {
        $entries = array_merge($entries, PMA_filter_tracking($data['ddlog'], $filter_ts_from, $filter_ts_to, $filter_users));
    }

    // Filtering data manipulation statements
    if ($_REQUEST['logtype'] == 'data' || $_REQUEST['logtype'] == 'schema_and_data') {
        $entries = array_merge($entries, PMA_filter_tracking($data['dmlog'], $filter_ts_from, $filter_ts_to, $filter_users));
    }

    // Sort it
    foreach ($entries as $key => $row) {
        $ids[$key]        = $row['id'];
        $timestamps[$key] = $row['timestamp'];
        $usernames[$key]  = $row['username'];
        $statements[$key] = $row['statement'];
    }

    array_multisort($timestamps, SORT_ASC, $ids, SORT_ASC, $usernames, SORT_ASC, $statements, SORT_ASC, $entries);

}

// Export as file download
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'sqldumpfile') {
    @ini_set('url_rewriter.tags', '');

    $dump = "# " . sprintf(__('Tracking report for table `%s`'), htmlspecialchars($_REQUEST['table'])) . "\n" .
            "# " . date('Y-m-d H:i:s') . "\n";
    foreach ($entries as $entry) {
        $dump .= $entry['statement'];
    }
    $filename = 'log_' . htmlspecialchars($_REQUEST['table']) . '.sql';
    PMA_download_header($filename, 'text/x-sql', strlen($dump));

    echo $dump;
    exit();
}


/**
 * Gets tables informations
 */

/**
 * Displays top menu links
 */
require_once './libraries/tbl_links.inc.php';
echo '<br />';

/**
 * Actions
 */

// Create tracking version
if (isset($_REQUEST['submit_create_version'])) {
    $tracking_set = '';

    if ($_REQUEST['alter_table'] == true) {
        $tracking_set .= 'ALTER TABLE,';
    }
    if ($_REQUEST['rename_table'] == true) {
        $tracking_set .= 'RENAME TABLE,';
    }
    if ($_REQUEST['create_table'] == true) {
        $tracking_set .= 'CREATE TABLE,';
    }
    if ($_REQUEST['drop_table'] == true) {
        $tracking_set .= 'DROP TABLE,';
    }
    if ($_REQUEST['create_index'] == true) {
        $tracking_set .= 'CREATE INDEX,';
    }
    if ($_REQUEST['drop_index'] == true) {
        $tracking_set .= 'DROP INDEX,';
    }
    if ($_REQUEST['insert'] == true) {
        $tracking_set .= 'INSERT,';
    }
    if ($_REQUEST['update'] == true) {
        $tracking_set .= 'UPDATE,';
    }
    if ($_REQUEST['delete'] == true) {
        $tracking_set .= 'DELETE,';
    }
    if ($_REQUEST['truncate'] == true) {
        $tracking_set .= 'TRUNCATE,';
    }
    $tracking_set = rtrim($tracking_set, ',');

    if (PMA_Tracker::createVersion($GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version'], $tracking_set )) {
        $msg = PMA_Message::success(sprintf(__('Version %s is created, tracking for %s.%s is activated.'), htmlspecialchars($_REQUEST['version']), htmlspecialchars($GLOBALS['db']), htmlspecialchars($GLOBALS['table'])));
        $msg->display();
    }
}

// Deactivate tracking
if (isset($_REQUEST['submit_deactivate_now'])) {
    if (PMA_Tracker::deactivateTracking($GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version'])) {
        $msg = PMA_Message::success(sprintf(__('Tracking for %s.%s , version %s is deactivated.'), htmlspecialchars($GLOBALS['db']), htmlspecialchars($GLOBALS['table']), htmlspecialchars($_REQUEST['version'])));
        $msg->display();
    }
}

// Activate tracking
if (isset($_REQUEST['submit_activate_now'])) {
    if (PMA_Tracker::activateTracking($GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version'])) {
        $msg = PMA_Message::success(sprintf(__('Tracking for %s.%s , version %s is activated.'), htmlspecialchars($GLOBALS['db']), htmlspecialchars($GLOBALS['table']), htmlspecialchars($_REQUEST['version'])));
        $msg->display();
    }
}

// Export as SQL execution
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'execution') {
    foreach ($entries as $entry) {
        $sql_result = PMA_DBI_query( "/*NOTRACK*/\n" . $entry['statement'] );
    }
    $msg = PMA_Message::success(__('SQL statements executed.'));
    $msg->display();
}

// Export as SQL dump
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'sqldump') {
    $new_query =    "# " . __('You can execute the dump by creating and using a temporary database. Please ensure that you have the privileges to do so.') . "\n" .
                    "# " . __('Comment out these two lines if you do not need them.') . "\n" .
                    "\n" .
                    "CREATE database IF NOT EXISTS pma_temp_db; \n" .
                    "USE pma_temp_db; \n" .
                    "\n";

    foreach ($entries as $entry) {
        $new_query .= $entry['statement'];
    }
    $msg = PMA_Message::success(__('SQL statements exported. Please copy the dump or execute it.'));
    $msg->display();

    $db_temp = $db;
    $table_temp = $table;

    $db = $table = '';
    include_once './libraries/sql_query_form.lib.php';

    PMA_sqlQueryForm($new_query, 'sql');

    $db = $db_temp;
    $table = $table_temp;
}

/*
 * Schema snapshot
 */
if (isset($_REQUEST['snapshot'])) {
?>
    <h3><?php echo __('Structure snapshot');?>  [<a href="tbl_tracking.php?<?php echo $url_query;?>"><?php echo __('Close');?></a>]</h3>
<?php
    $data = PMA_Tracker::getTrackedData($_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version']);

    // Get first DROP TABLE and CREATE TABLE statements
    $drop_create_statements = $data['ddlog'][0]['statement'];

    if (strstr($data['ddlog'][0]['statement'], 'DROP TABLE')) {
        $drop_create_statements .= $data['ddlog'][1]['statement'];
    }
    // Print SQL code
    PMA_showMessage(sprintf(__('Version %s snapshot (SQL code)'), htmlspecialchars($_REQUEST['version'])), $drop_create_statements);

    // Unserialize snapshot
    $temp = unserialize($data['schema_snapshot']);
    $columns = $temp['COLUMNS'];
    $indexes = $temp['INDEXES'];
?>
    <h3><?php echo __('Structure');?></h3>
    <table id="tablestructure" class="data">
    <thead>
    <tr>
        <th><?php echo __('Column'); ?></th>
        <th><?php echo __('Type'); ?></th>
        <th><?php echo __('Collation'); ?></th>
        <th><?php echo __('Null'); ?></th>
        <th><?php echo __('Default'); ?></th>
        <th><?php echo __('Extra'); ?></th>
        <th><?php echo __('Comment'); ?></th>
    </tr>
    </thead>
    <tbody>
<?php
    $style = 'odd';
    foreach ($columns as $field_index => $field) {
?>
        <tr class="noclick <?php echo $style; ?>">
            <?php
            if ($field['Key'] == 'PRI') {
                echo '<td><b><u>' . htmlspecialchars($field['Field']) . '</u></b></td>' . "\n";
            } else {
                echo '<td><b>' . htmlspecialchars($field['Field']) . '</b></td>' . "\n";
            }
            ?>
            <td><?php echo htmlspecialchars($field['Type']);?></td>
            <td><?php echo htmlspecialchars($field['Collation']);?></td>
            <td><?php echo (($field['Null'] == 'YES') ? __('Yes') : __('No')); ?></td>
            <td><?php
            if (isset($field['Default'])) {
                $extracted_fieldspec = PMA_extractFieldSpec($field['Type']);
                if ($extracted_fieldspec['type'] == 'bit') {
                    // here, $field['Default'] contains something like b'010'
                    echo PMA_convert_bit_default_value($field['Default']);
                } else {
                    echo htmlspecialchars($field['Default']);
                }
            } else {
                if ($field['Null'] == 'YES') {
                    echo '<i>NULL</i>';
                } else {
                    echo '<i>' . _pgettext('None for default', 'None') . '</i>';
                }
            } ?></td>
            <td><?php echo htmlspecialchars($field['Extra']);?></td>
            <td><?php echo htmlspecialchars($field['Comment']);?></td>
        </tr>
<?php
            if ($style == 'even') {
                $style = 'odd';
            } else {
                $style = 'even';
            }
    }
?>
    </tbody>
    </table>

<?php
    if (count($indexes) > 0) {
?>
        <h3><?php echo __('Indexes');?></h3>
        <table id="tablestructure_indexes" class="data">
        <thead>
        <tr>
            <th><?php echo __('Keyname');?></th>
            <th><?php echo __('Type');?></th>
            <th><?php echo __('Unique');?></th>
            <th><?php echo __('Packed');?></th>
            <th><?php echo __('Column');?></th>
            <th><?php echo __('Cardinality');?></th>
            <th><?php echo __('Collation');?></th>
            <th><?php echo __('Null');?></th>
            <th><?php echo __('Comment');?></th>
        </tr>
        <tbody>
<?php
        $style = 'odd';
        foreach ($indexes as $indexes_index => $index) {
            if ($index['Non_unique'] == 0) {
                $str_unique = __('Yes');
            } else {
                $str_unique = __('No');
            }
            if ($index['Packed'] != '') {
                $str_packed = __('Yes');
            } else {
                $str_packed = __('No');
            }
?>
            <tr class="noclick <?php echo $style; ?>">
                <td><b><?php echo htmlspecialchars($index['Key_name']);?></b></td>
                <td><?php echo htmlspecialchars($index['Index_type']);?></td>
                <td><?php echo $str_unique;?></td>
                <td><?php echo $str_packed;?></td>
                <td><?php echo htmlspecialchars($index['Column_name']);?></td>
                <td><?php echo htmlspecialchars($index['Cardinality']);?></td>
                <td><?php echo htmlspecialchars($index['Collation']);?></td>
                <td><?php echo htmlspecialchars($index['Null']);?></td>
                <td><?php echo htmlspecialchars($index['Comment']);?></td>
            </tr>
<?php
            if ($style == 'even') {
                $style = 'odd';
            } else {
                $style = 'even';
            }
        }
?>
    </tbody>
    </table>
<?php
    } // endif
?>
    <br /><hr /><br />
<?php
}
// end of snapshot report

/*
 *  Tracking report
 */
if (isset($_REQUEST['report']) && (isset($_REQUEST['delete_ddlog']) || isset($_REQUEST['delete_dmlog']))) {

    if (isset($_REQUEST['delete_ddlog'])) {

        // Delete ddlog row data
        $delete_id = $_REQUEST['delete_ddlog'];

        // Only in case of valable id
        if ($delete_id == (int)$delete_id) {
            unset($data['ddlog'][$delete_id]);

            if (PMA_Tracker::changeTrackingData($_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version'], 'DDL', $data['ddlog']))
                $msg = PMA_Message::success(__('Tracking data definition successfully deleted'));
            else
                $msg = PMA_Message::rawError(__('Query error'));
            $msg->display();
        }
    }

    if (isset($_REQUEST['delete_dmlog'])) {

        // Delete dmlog row data
        $delete_id = $_REQUEST['delete_dmlog'];

        // Only in case of valable id
        if ($delete_id == (int)$delete_id) {
            unset($data['dmlog'][$delete_id]);

            if (PMA_Tracker::changeTrackingData($_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version'], 'DML', $data['dmlog']))
                $msg = PMA_Message::success(__('Tracking data manipulation successfully deleted'));
            else
                $msg = PMA_Message::rawError(__('Query error'));
            $msg->display();
        }
    }
}

if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    ?>
    <h3><?php echo __('Tracking report');?>  [<a href="tbl_tracking.php?<?php echo $url_query;?>"><?php echo __('Close');?></a>]</h3>

    <small><?php echo __('Tracking statements') . ' ' . htmlspecialchars($data['tracking']); ?></small><br/>
    <br/>

    <form method="post" action="tbl_tracking.php<?php echo PMA_generate_common_url($url_params + array('report' => 'true', 'version' => $_REQUEST['version'])); ?>">
    <?php

    $str1 = '<select name="logtype">' .
            '<option value="schema"' . ($selection_schema ? ' selected="selected"' : '') . '>' . __('Structure only') . '</option>' .
            '<option value="data"' . ($selection_data ? ' selected="selected"' : ''). '>' . __('Data only') . '</option>' .
            '<option value="schema_and_data"' . ($selection_both ? ' selected="selected"' : '') . '>' . __('Structure and data') . '</option>' .
            '</select>';
    $str2 = '<input type="text" name="date_from" value="' . htmlspecialchars($_REQUEST['date_from']) . '" size="19" />';
    $str3 = '<input type="text" name="date_to" value="' . htmlspecialchars($_REQUEST['date_to']) . '" size="19" />';
    $str4 = '<input type="text" name="users" value="' . htmlspecialchars($_REQUEST['users']) . '" />';
    $str5 = '<input type="submit" name="list_report" value="' . __('Go') . '" />';

    printf(__('Show %s with dates from %s to %s by user %s %s'), $str1, $str2, $str3, $str4, $str5);

    // Prepare delete link content here
    $drop_image_or_text = '';
    if (true == $GLOBALS['cfg']['PropertiesIconic']) {
        $drop_image_or_text .= PMA_getImage('b_drop.png', __('Delete tracking data row from report'));
    }
    if ('both' === $GLOBALS['cfg']['PropertiesIconic'] || false === $GLOBALS['cfg']['PropertiesIconic']) {
        $drop_image_or_text .= __('Delete');
    }

    /*
     *  First, list tracked data definition statements
     */
    $i = 1;
    if (count($data['ddlog']) == 0 && count($data['dmlog']) == 0) {
        $msg = PMA_Message::notice(__('No data'));
        $msg->display();
    }

    if ($selection_schema || $selection_both  && count($data['ddlog']) > 0) {
    ?>
        <table id="ddl_versions" class="data" width="100%">
        <thead>
        <tr>
            <th width="18">#</th>
            <th width="100"><?php echo __('Date');?></th>
            <th width="60"><?php echo __('Username');?></th>
            <th><?php echo __('Data definition statement');?></th>
            <th><?php echo __('Delete');?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        $style = 'odd';
        foreach ($data['ddlog'] as $entry) {
            if (strlen($entry['statement']) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
                $statement = substr($entry['statement'], 0, $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) . '[...]';
            } else {
                $statement  = PMA_formatSql(PMA_SQP_parse($entry['statement']));
            }
            $timestamp = strtotime($entry['date']);

            if ($timestamp >= $filter_ts_from && $timestamp <= $filter_ts_to &&
              ( in_array('*', $filter_users) || in_array($entry['username'], $filter_users) ) ) {
        ?>
                <tr class="noclick <?php echo $style; ?>">
                    <td><small><?php echo $i;?></small></td>
                    <td><small><?php echo htmlspecialchars($entry['date']);?></small></td>
                    <td><small><?php echo htmlspecialchars($entry['username']); ?></small></td>
                    <td><?php echo $statement; ?></td>
                    <td nowrap="nowrap"><a href="tbl_tracking.php?<?php echo $url_query;?>&amp;report=true&amp;version=<?php echo $version['version'];?>&amp;delete_ddlog=<?php echo $i-1; ?>"><?php echo $drop_image_or_text; ?></a></td>
                </tr>
        <?php
                if ($style == 'even') {
                    $style = 'odd';
                } else {
                    $style = 'even';
                }
                $i++;
            }
        }
        ?>
        </tbody>
        </table>
    <?php

    } //endif

    // Memorize data definition amount
    $ddlog_count = $i;

    /*
     *  Secondly, list tracked data manipulation statements
     */

    if (($selection_data || $selection_both) && count($data['dmlog']) > 0) {
    ?>
        <table id="dml_versions" class="data" width="100%">
        <thead>
        <tr>
            <th width="18">#</th>
            <th width="100"><?php echo __('Date');?></th>
            <th width="60"><?php echo __('Username');?></th>
            <th><?php echo __('Data manipulation statement');?></th>
            <th><?php echo __('Delete');?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $style = 'odd';
        foreach ($data['dmlog'] as $entry) {
            if (strlen($entry['statement']) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
                $statement = substr($entry['statement'], 0, $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) . '[...]';
            } else {
                $statement  = PMA_formatSql(PMA_SQP_parse($entry['statement']));
            }
            $timestamp = strtotime($entry['date']);

            if ($timestamp >= $filter_ts_from && $timestamp <= $filter_ts_to &&
              ( in_array('*', $filter_users) || in_array($entry['username'], $filter_users) ) ) {
        ?>
                <tr class="noclick <?php echo $style; ?>">
                    <td><small><?php echo $i; ?></small></td>
                    <td><small><?php echo htmlspecialchars($entry['date']); ?></small></td>
                    <td><small><?php echo htmlspecialchars($entry['username']); ?></small></td>
                    <td><?php echo $statement; ?></td>
                    <td nowrap="nowrap"><a href="tbl_tracking.php?<?php echo $url_query;?>&amp;report=true&amp;version=<?php echo $version['version'];?>&amp;delete_dmlog=<?php echo $i-$ddlog_count; ?>"><?php echo $drop_image_or_text; ?></a></td>
                </tr>
        <?php
                if ($style == 'even') {
                    $style = 'odd';
                } else {
                    $style = 'even';
                }
                $i++;
            }
        }
    ?>
        </tbody>
        </table>
    <?php
    }
    ?>
    </form>
    <form method="post" action="tbl_tracking.php<?php echo PMA_generate_common_url($url_params + array('report' => 'true', 'version' => $_REQUEST['version'])); ?>">
    <?php
    printf(__('Show %s with dates from %s to %s by user %s %s'), $str1, $str2, $str3, $str4, $str5);

    $str_export1 =  '<select name="export_type">' .
                    '<option value="sqldumpfile">' . __('SQL dump (file download)') . '</option>' .
                    '<option value="sqldump">' . __('SQL dump') . '</option>' .
                    '<option value="execution" onclick="alert(\'' . PMA_escapeJsString(__('This option will replace your table and contained data.')) .'\')">' . __('SQL execution') . '</option>' .
                    '</select>';

    $str_export2 = '<input type="submit" name="report_export" value="' . __('Go') .'" />';
    ?>
    </form>
    <form method="post" action="tbl_tracking.php<?php echo PMA_generate_common_url($url_params + array('report' => 'true', 'version' => $_REQUEST['version'])); ?>">
    <input type="hidden" name="logtype" value="<?php echo htmlspecialchars($_REQUEST['logtype']);?>" />
    <input type="hidden" name="date_from" value="<?php echo htmlspecialchars($_REQUEST['date_from']);?>" />
    <input type="hidden" name="date_to" value="<?php echo htmlspecialchars($_REQUEST['date_to']);?>" />
    <input type="hidden" name="users" value="<?php echo htmlspecialchars($_REQUEST['users']);?>" />
    <?php
    echo "<br/>" . sprintf(__('Export as %s'), $str_export1) . $str_export2 . "<br/>";
    ?>
    </form>
    <?php
    echo "<br/><br/><hr/><br/>\n";
} // end of report


/*
 * List selectable tables
 */

$sql_query = " SELECT DISTINCT db_name, table_name FROM " .
             PMA_backquote($GLOBALS['cfg']['Server']['pmadb']) . "." .
             PMA_backquote($GLOBALS['cfg']['Server']['tracking']) .
             " WHERE db_name = '" . PMA_sqlAddSlashes($GLOBALS['db']) . "' " .
             " ORDER BY db_name, table_name";

$sql_result = PMA_query_as_controluser($sql_query);

if (PMA_DBI_num_rows($sql_result) > 0) {
?>
    <form method="post" action="tbl_tracking.php?<?php echo $url_query;?>">
    <select name="table">
    <?php
    while ($entries = PMA_DBI_fetch_array($sql_result)) {
        if (PMA_Tracker::isTracked($entries['db_name'], $entries['table_name'])) {
            $status = ' (' . __('active') . ')';
        } else {
            $status = ' (' . __('not active') . ')';
        }
        if ($entries['table_name'] == $_REQUEST['table']) {
            $s = ' selected="selected"';
        } else {
            $s = '';
        }
        echo '<option value="' . htmlspecialchars($entries['table_name']) . '"' . $s . '>' . htmlspecialchars($entries['db_name']) . ' . ' . htmlspecialchars($entries['table_name']) . $status . '</option>' . "\n";
    }
    ?>
    </select>
    <input type="submit" name="show_versions_submit" value="<?php echo __('Show versions');?>" />
    </form>
<?php
}
?>
<br />
<?php

/*
 * List versions of current table
 */

$sql_query = " SELECT * FROM " .
             PMA_backquote($GLOBALS['cfg']['Server']['pmadb']) . "." .
             PMA_backquote($GLOBALS['cfg']['Server']['tracking']) .
             " WHERE db_name = '" . PMA_sqlAddSlashes($_REQUEST['db']) . "' ".
             " AND table_name = '" . PMA_sqlAddSlashes($_REQUEST['table']) ."' ".
             " ORDER BY version DESC ";

$sql_result = PMA_query_as_controluser($sql_query);

$last_version = 0;
$maxversion = PMA_DBI_fetch_array($sql_result);
$last_version = $maxversion['version'];

if ($last_version > 0) {
?>
    <table id="versions" class="data">
    <thead>
    <tr>
        <th><?php echo __('Database');?></th>
        <th><?php echo __('Table');?></th>
        <th><?php echo __('Version');?></th>
        <th><?php echo __('Created');?></th>
        <th><?php echo __('Updated');?></th>
        <th><?php echo __('Status');?></th>
        <th><?php echo __('Show');?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $style = 'odd';
    PMA_DBI_data_seek($sql_result, 0);
    while ($version = PMA_DBI_fetch_array($sql_result)) {
        if ($version['tracking_active'] == 1) {
            $version_status = __('active');
        } else {
            $version_status = __('not active');
        }
        if ($version['version'] == $last_version) {
            if ($version['tracking_active'] == 1) {
                $tracking_active = true;
            } else {
                $tracking_active = false;
            }
        }
    ?>
        <tr class="noclick <?php echo $style;?>">
            <td><?php echo htmlspecialchars($version['db_name']);?></td>
            <td><?php echo htmlspecialchars($version['table_name']);?></td>
            <td><?php echo htmlspecialchars($version['version']);?></td>
            <td><?php echo htmlspecialchars($version['date_created']);?></td>
            <td><?php echo htmlspecialchars($version['date_updated']);?></td>
            <td><?php echo $version_status;?></td>
            <td> <a href="tbl_tracking.php<?php echo PMA_generate_common_url($url_params + array('report' => 'true', 'version' => $version['version'])
);?>"><?php echo __('Tracking report');?></a>
                | <a href="tbl_tracking.php<?php echo PMA_generate_common_url($url_params + array('snapshot' => 'true', 'version' => $version['version'])
);?>"><?php echo __('Structure snapshot');?></a>
            </td>
        </tr>
    <?php
        if ($style == 'even') {
            $style = 'odd';
        } else {
            $style = 'even';
        }
    }
    ?>
    </tbody>
    </table>
    <?php if ($tracking_active == true) {?>
        <div id="div_deactivate_tracking">
        <form method="post" action="tbl_tracking.php?<?php echo $url_query; ?>">
        <fieldset>
            <legend><?php printf(__('Deactivate tracking for %s.%s'), htmlspecialchars($GLOBALS['db']), htmlspecialchars($GLOBALS['table'])); ?></legend>
            <input type="hidden" name="version" value="<?php echo $last_version; ?>" />
            <input type="submit" name="submit_deactivate_now" value="<?php echo __('Deactivate now'); ?>" />
        </fieldset>
        </form>
        </div>
    <?php
    }
    ?>
    <?php if ($tracking_active == false) {?>
        <div id="div_activate_tracking">
        <form method="post" action="tbl_tracking.php?<?php echo $url_query; ?>">
        <fieldset>
            <legend><?php printf(__('Activate tracking for %s.%s'), htmlspecialchars($GLOBALS['db']), htmlspecialchars($GLOBALS['table'])); ?></legend>
            <input type="hidden" name="version" value="<?php echo $last_version; ?>" />
            <input type="submit" name="submit_activate_now" value="<?php echo __('Activate now'); ?>" />
        </fieldset>
        </form>
        </div>
    <?php
    }
}
?>

<div id="div_create_version">
<form method="post" action="tbl_tracking.php?<?php echo $url_query; ?>">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db'], $GLOBALS['table']); ?>
<fieldset>
    <legend><?php printf(__('Create version %s of %s.%s'), ($last_version + 1), htmlspecialchars($GLOBALS['db']), htmlspecialchars($GLOBALS['table'])); ?></legend>

    <input type="hidden" name="version" value="<?php echo ($last_version + 1); ?>" />

    <p><?php echo __('Track these data definition statements:');?></p>
    <input type="checkbox" name="alter_table" value="true" checked="checked" /> ALTER TABLE<br/>
    <input type="checkbox" name="rename_table" value="true" checked="checked" /> RENAME TABLE<br/>
    <input type="checkbox" name="create_table" value="true" checked="checked" /> CREATE TABLE<br/>
    <input type="checkbox" name="drop_table" value="true" checked="checked" /> DROP TABLE<br/>
    <br/>
    <input type="checkbox" name="create_index" value="true" checked="checked" /> CREATE INDEX<br/>
    <input type="checkbox" name="drop_index" value="true" checked="checked" /> DROP INDEX<br/>
    <p><?php echo __('Track these data manipulation statements:');?></p>
    <input type="checkbox" name="insert" value="true" checked="checked" /> INSERT<br/>
    <input type="checkbox" name="update" value="true" checked="checked" /> UPDATE<br/>
    <input type="checkbox" name="delete" value="true" checked="checked" /> DELETE<br/>
    <input type="checkbox" name="truncate" value="true" checked="checked" /> TRUNCATE<br/>

</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit_create_version" value="<?php echo __('Create version'); ?>" />
</fieldset>
</form>
</div>

<br class="clearfloat"/>

<?php
/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
