<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table tracking page
 *
 * @package PhpMyAdmin
 */

// Run common work
require_once './libraries/common.inc.php';

require_once './libraries/tbl_tracking.lib.php';

define('TABLE_MAY_BE_ABSENT', true);
require './libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_tracking.php&amp;back=tbl_tracking.php';
$url_params['goto'] = 'tbl_tracking.php';
$url_params['back'] = 'tbl_tracking.php';

// Init vars for tracking report
if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    $data = PMA_Tracker::getTrackedData(
        $_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version']
    );

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
    $entries = array();
    // Filtering data definition statements
    if ($_REQUEST['logtype'] == 'schema'
        || $_REQUEST['logtype'] == 'schema_and_data'
    ) {
        $entries = array_merge(
            $entries,
            PMA_filterTracking(
                $data['ddlog'], $filter_ts_from, $filter_ts_to, $filter_users
            )
        );
    }

    // Filtering data manipulation statements
    if ($_REQUEST['logtype'] == 'data'
        || $_REQUEST['logtype'] == 'schema_and_data'
    ) {
        $entries = array_merge(
            $entries,
            PMA_filterTracking(
                $data['dmlog'], $filter_ts_from, $filter_ts_to, $filter_users
            )
        );
    }

    // Sort it
    foreach ($entries as $key => $row) {
        $ids[$key]        = $row['id'];
        $timestamps[$key] = $row['timestamp'];
        $usernames[$key]  = $row['username'];
        $statements[$key] = $row['statement'];
    }

    array_multisort(
        $timestamps, SORT_ASC, $ids, SORT_ASC, $usernames,
        SORT_ASC, $statements, SORT_ASC, $entries
    );

}

// Export as file download
if (isset($_REQUEST['report_export'])
    && $_REQUEST['export_type'] == 'sqldumpfile'
) {
    @ini_set('url_rewriter.tags', '');

    $dump = "# " . sprintf(
        __('Tracking report for table `%s`'), htmlspecialchars($_REQUEST['table'])
    )
    . "\n" . "# " . date('Y-m-d H:i:s') . "\n";
    foreach ($entries as $entry) {
        $dump .= $entry['statement'];
    }
    $filename = 'log_' . htmlspecialchars($_REQUEST['table']) . '.sql';
    PMA_downloadHeader($filename, 'text/x-sql', strlen($dump));

    $html .= $dump;
    exit();
}


/**
 * Gets tables informations
 */

$html = '<br />';

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

    $versionCreated = PMA_Tracker::createVersion(
        $GLOBALS['db'],
        $GLOBALS['table'],
        $_REQUEST['version'],
        $tracking_set,
        PMA_Table::isView($GLOBALS['db'], $GLOBALS['table'])
    );
    if ($versionCreated) {
        $msg = PMA_Message::success(
            sprintf(
                __('Version %1$s was created, tracking for %2$s is active.'),
                htmlspecialchars($_REQUEST['version']),
                htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
            )
        );
        $msg->display();
    }
}

// Deactivate tracking
if (isset($_REQUEST['submit_deactivate_now'])) {
    $deactivated = PMA_Tracker::deactivateTracking(
        $GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version']
    );
    if ($deactivated) {
        $msg = PMA_Message::success(
            sprintf(
                __('Tracking for %1$s was deactivated at version %2$s.'),
                htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table']),
                htmlspecialchars($_REQUEST['version'])
            )
        );
        $msg->display();
    }
}

// Activate tracking
if (isset($_REQUEST['submit_activate_now'])) {
    $activated = PMA_Tracker::activateTracking(
        $GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version']
    );
    if ($activated) {
        $msg = PMA_Message::success(
            sprintf(
                __('Tracking for %1$s was activated at version %2$s.'),
                htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table']),
                htmlspecialchars($_REQUEST['version'])
            )
        );
        $msg->display();
    }
}

// Export as SQL execution
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'execution') {
    foreach ($entries as $entry) {
        $sql_result = $GLOBALS['dbi']->query("/*NOTRACK*/\n" . $entry['statement']);
    }
    $msg = PMA_Message::success(__('SQL statements executed.'));
    $msg->display();
}

// Export as SQL dump
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'sqldump') {
    $new_query = "# "
        . __('You can execute the dump by creating and using a temporary database. Please ensure that you have the privileges to do so.')
        . "\n"
        . "# " . __('Comment out these two lines if you do not need them.') . "\n"
        . "\n"
        . "CREATE database IF NOT EXISTS pma_temp_db; \n"
        . "USE pma_temp_db; \n"
        . "\n";

    foreach ($entries as $entry) {
        $new_query .= $entry['statement'];
    }
    $msg = PMA_Message::success(
        __('SQL statements exported. Please copy the dump or execute it.')
    );
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
    $html .= '<h3>' . __('Structure snapshot')
        . '  [<a href="tbl_tracking.php?' . $url_query . '">' . __('Close')
        . '</a>]</h3>';
    $data = PMA_Tracker::getTrackedData(
        $_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version']
    );

    // Get first DROP TABLE/VIEW and CREATE TABLE/VIEW statements
    $drop_create_statements = $data['ddlog'][0]['statement'];

    if (strstr($data['ddlog'][0]['statement'], 'DROP TABLE')
        || strstr($data['ddlog'][0]['statement'], 'DROP VIEW')
    ) {
        $drop_create_statements .= $data['ddlog'][1]['statement'];
    }
    // Print SQL code
    $html .= PMA_Util::getMessage(
        sprintf(
            __('Version %s snapshot (SQL code)'),
            htmlspecialchars($_REQUEST['version'])
        ),
        $drop_create_statements
    );

    // Unserialize snapshot
    $temp = unserialize($data['schema_snapshot']);
    $columns = $temp['COLUMNS'];
    $indexes = $temp['INDEXES'];
    $html .= '<h3>' . __('Structure') . '</h3>';
    $html .= '<table id="tablestructure" class="data">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>' . __('Column') . '</th>';
    $html .= '<th>' . __('Type') . '</th>';
    $html .= '<th>' . __('Collation') . '</th>';
    $html .= '<th>' . __('Null') . '</th>';
    $html .= '<th>' . __('Default') . '</th>';
    $html .= '<th>' . __('Extra') . '</th>';
    $html .= '<th>' . __('Comment') . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $style = 'odd';
    foreach ($columns as $field_index => $field) {
        $html .= '<tr class="noclick ' . $style . '">';
        if ($field['Key'] == 'PRI') {
            $html .= '<td><b><u>' . htmlspecialchars($field['Field']) . '</u></b></td>';
        } else {
            $html .= '<td><b>' . htmlspecialchars($field['Field']) . '</b></td>';
        }
        $html .= "\n";
        $html .= '<td>' . htmlspecialchars($field['Type']) . '</td>';
        $html .= '<td>' . htmlspecialchars($field['Collation']) . '</td>';
        $html .= '<td>' . (($field['Null'] == 'YES') ? __('Yes') : __('No')) . '</td>';
        $html .= '<td>';
        if (isset($field['Default'])) {
            $extracted_columnspec = PMA_Util::extractColumnSpec($field['Type']);
            if ($extracted_columnspec['type'] == 'bit') {
                // here, $field['Default'] contains something like b'010'
                $html .= PMA_Util::convertBitDefaultValue($field['Default']);
            } else {
                $html .= htmlspecialchars($field['Default']);
            }
        } else {
            if ($field['Null'] == 'YES') {
                $html .= '<i>NULL</i>';
            } else {
                $html .= '<i>' . _pgettext('None for default', 'None') . '</i>';
            }
        }
        $html .= '</td>';
        $html .= '<td>' . htmlspecialchars($field['Extra']) . '</td>';
        $html .= '<td>' . htmlspecialchars($field['Comment']) . '</td>';
        $html .= '</tr>';

        if ($style == 'even') {
            $style = 'odd';
        } else {
            $style = 'even';
        }
    }

    $html .= '</tbody>';
    $html .= '</table>';

    if (count($indexes) > 0) {
        $html .= '<h3>' . __('Indexes') . '</h3>';
        $html .= '<table id="tablestructure_indexes" class="data">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>' . __('Keyname') . '</th>';
        $html .= '<th>' . __('Type') . '</th>';
        $html .= '<th>' . __('Unique') . '</th>';
        $html .= '<th>' . __('Packed') . '</th>';
        $html .= '<th>' . __('Column') . '</th>';
        $html .= '<th>' . __('Cardinality') . '</th>';
        $html .= '<th>' . __('Collation') . '</th>';
        $html .= '<th>' . __('Null') . '</th>';
        $html .= '<th>' . __('Comment') . '</th>';
        $html .= '</tr>';
        $html .= '<tbody>';

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

            $html .= '<tr class="noclick ' . $style . '">';
            $html .= '<td><b>' . htmlspecialchars($index['Key_name']) . '</b></td>';
            $html .= '<td>' . htmlspecialchars($index['Index_type']) . '</td>';
            $html .= '<td>' . $str_unique . '</td>';
            $html .= '<td>' . $str_packed . '</td>';
            $html .= '<td>' . htmlspecialchars($index['Column_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($index['Cardinality']) . '</td>';
            $html .= '<td>' . htmlspecialchars($index['Collation']) . '</td>';
            $html .= '<td>' . htmlspecialchars($index['Null']) . '</td>';
            $html .= '<td>' . htmlspecialchars($index['Comment']) . '</td>';
            $html .= '</tr>';

            if ($style == 'even') {
                $style = 'odd';
            } else {
                $style = 'even';
            }
        }
        $html .= '</tbody>';
        $html .= '</table>';
    } // endif
    $html .= '<br /><hr /><br />';
}
// end of snapshot report

/*
 *  Tracking report
 */
if (isset($_REQUEST['report'])
    && (isset($_REQUEST['delete_ddlog']) || isset($_REQUEST['delete_dmlog']))
) {

    if (isset($_REQUEST['delete_ddlog'])) {

        // Delete ddlog row data
        $delete_id = $_REQUEST['delete_ddlog'];

        // Only in case of valable id
        if ($delete_id == (int)$delete_id) {
            unset($data['ddlog'][$delete_id]);

            $successfullyDeleted = PMA_Tracker::changeTrackingData(
                $_REQUEST['db'], $_REQUEST['table'],
                $_REQUEST['version'], 'DDL', $data['ddlog']
            );
            if ($successfullyDeleted) {
                $msg = PMA_Message::success(
                    __('Tracking data definition successfully deleted')
                );
            } else {
                $msg = PMA_Message::rawError(__('Query error'));
            }
            $msg->display();
        }
    }

    if (isset($_REQUEST['delete_dmlog'])) {

        // Delete dmlog row data
        $delete_id = $_REQUEST['delete_dmlog'];

        // Only in case of valable id
        if ($delete_id == (int)$delete_id) {
            unset($data['dmlog'][$delete_id]);

            $successfullyDeleted = PMA_Tracker::changeTrackingData(
                $_REQUEST['db'], $_REQUEST['table'],
                $_REQUEST['version'], 'DML', $data['dmlog']
            );
            if ($successfullyDeleted) {
                $msg = PMA_Message::success(
                    __('Tracking data manipulation successfully deleted')
                );
            } else {
                $msg = PMA_Message::rawError(__('Query error'));
            }
            $msg->display();
        }
    }
}

if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    $html .= PMA_getHtmlForTrackingReport(
        $url_query, $data, $url_params, $selection_schema, $selection_data,
        $selection_both, $filter_ts_to, $filter_ts_from, $filter_users
    ); 
} // end of report


/*
 * List selectable tables
 */
$selectable_tables_sql_result = PMA_getSQLResultForSelectableTables();
if ($GLOBALS['dbi']->numRows($selectable_tables_sql_result) > 0) {
    $html .= PMA_getHtmlForSelectableTables(
        $selectable_tables_sql_result, $url_query
    );
}
$html .= '<br />';

/*
 * List versions of current table
 */
$sql_result = PMA_getListOfVersionsOfTable();
$last_version = PMA_getTableLastVersionNumber($sql_result);
if ($last_version > 0) {
    $html .= PMA_getHtmlForTableVersionDetails(
        $sql_result, $last_version, $url_params, $url_query
    );
}

$html .= PMA_getHtmlForDataDefinitionAndManipulationStatements(
    $url_query, $last_version
);

$html .= '<br class="clearfloat"/>';

$response = PMA_Response::getInstance();
$response->addHTML($html);