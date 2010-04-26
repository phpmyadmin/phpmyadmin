<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @author Alexander Rutkowski
 * @version $Id$
 * @package phpMyAdmin
 */

// Run common work
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';

define('TABLE_MAY_BE_ABSENT', true);
require './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_tracking.php&amp;back=tbl_tracking.php';
$url_params['goto'] = 'tbl_tracking.php';;
$url_params['back'] = 'tbl_tracking.php';

// Get relation settings
require_once './libraries/relation.lib.php';

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
    } elseif($_REQUEST['logtype'] == 'data') {
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
 * @param   array   the entries to filter 
 * @param   string  "from" date
 * @param   string  "to" date
 * @param   string  users 
 *
 * @return  array   filtered entries 
 *
 */
    function PMA_filter_tracking($data, $filter_ts_from, $filter_ts_to, $filter_users) {
        $tmp_entries = array();
        $id = 0;
        foreach( $data as $entry ) {
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
    @ini_set('url_rewriter.tags','');

    $dump = "# " . sprintf($strTrackingReportForTable, htmlspecialchars($_REQUEST['table'])) . "\n" .
            "# " . date('Y-m-d H:i:s') . "\n";
    foreach($entries as $entry) {
        $dump .= $entry['statement'];
    }
    $filename = 'log_' . htmlspecialchars($_REQUEST['table']) . '.sql';
    header('Content-Type: text/x-sql');
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    if (PMA_USR_BROWSER_AGENT == 'IE') {
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    } else {
        header('Pragma: no-cache');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    }

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
        $msg = PMA_Message::success(sprintf($strTrackingVersionCreated, $_REQUEST['version'], $GLOBALS['db'], $GLOBALS['table']));
        $msg->display();
    }
}

// Deactivate tracking
if (isset($_REQUEST['submit_deactivate_now'])) {
    if (PMA_Tracker::deactivateTracking($GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version'])) {
        $msg = PMA_Message::success(sprintf($strTrackingVersionDeactivated, $GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version']));
        $msg->display();
    }
}

// Activate tracking
if (isset($_REQUEST['submit_activate_now'])) {
    if (PMA_Tracker::activateTracking($GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version'])) {
        $msg = PMA_Message::success(sprintf($strTrackingVersionActivated, $GLOBALS['db'], $GLOBALS['table'], $_REQUEST['version']));
        $msg->display();
    }
}

// Export as SQL execution
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'execution') {
    foreach($entries as $entry) {
        $sql_result = PMA_DBI_query( "/*NOTRACK*/\n" . $entry['statement'] );
    }
    $msg = PMA_Message::success($strTrackingSQLExecuted);
    $msg->display();
}

// Export as SQL dump
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'sqldump')
{
    $new_query =    "# " . $strTrackingYouCanExecute . "\n" .
                    "# " . $strTrackingCommentOut . "\n" .
                    "\n" .
                    "CREATE database IF NOT EXISTS pma_temp_db; \n" .
                    "USE pma_temp_db; \n" .
                    "\n";

    foreach($entries as $entry) {
        $new_query .= $entry['statement'];
    }
    $msg = PMA_Message::success($strTrackingSQLExported);
    $msg->display();

    $db_temp = $db;
    $table_temp = $table;

    $db = $table = '';
    $GLOBALS['js_include'][] = 'functions.js';
    require_once './libraries/sql_query_form.lib.php';

    PMA_sqlQueryForm($new_query, 'sql');

    $db = $db_temp;
    $table = $table_temp;
}

/*
 * Schema snapshot
 */
if (isset($_REQUEST['snapshot'])) {
?>
    <h3><?php echo $strTrackingStructureSnapshot;?>  [<a href="tbl_tracking.php?<?php echo $url_query;?>"><?php echo $strTrackingReportClose;?></a>]</h3>
<?php
    $data = PMA_Tracker::getTrackedData($_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version']);

    // Get first DROP TABLE and CREATE TABLE statements
    $drop_create_statements = $data['ddlog'][0]['statement'];

    if (strstr($data['ddlog'][0]['statement'], 'DROP TABLE')) {
        $drop_create_statements .= $data['ddlog'][1]['statement'];
    }
    // Print SQL code
    PMA_showMessage(sprintf($strTrackingVersionSnapshotSQL, $_REQUEST['version']), $drop_create_statements);

    // Unserialize snapshot
    $temp = unserialize($data['schema_snapshot']);
    $columns = $temp['COLUMNS'];
    $indexes = $temp['INDEXES'];
?>
    <h3><?php echo $strStructure;?></h3>
    <table id="tablestructure" class="data">
    <thead>
    <tr>
        <th><?php echo $strField; ?></th>
        <th><?php echo $strType; ?></th>
        <th><?php echo $strCollation; ?></th>
        <th><?php echo $strNull; ?></th>
        <th><?php echo $strDefault; ?></th>
        <th><?php echo $strExtra; ?></th>
        <th><?php echo $strComment; ?></th>
    </tr>
    </thead>
    <tbody>
<?php
    $style = 'odd';
    foreach($columns as $field_index => $field) {
?>
        <tr class="<?php echo $style; ?>">
            <?php
            if ($field['Key'] == 'PRI') {
                echo '<td><b><u>' . $field['Field'] . '</u></b></td>' . "\n";
            } else {
                echo '<td><b>' . $field['Field'] . '</b></td>' . "\n";
            }
            ?>
            <td><?php echo $field['Type'];?></td>
            <td><?php echo $field['Collation'];?></td>
            <td><?php echo $field['Null'];?></td>
            <td><?php echo $field['Default'];?></td>
            <td><?php echo $field['Extra'];?></td>
            <td><?php echo $field['Comment'];?></td>
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
        <h3><?php echo $strIndexes;?></h3>
        <table id="tablestructure_indexes" class="data">
        <thead>
        <tr>
            <th><?php echo $strKeyname;?></th>
            <th><?php echo $strType;?></th>
            <th><?php echo $strUnique;?></th>
            <th><?php echo $strPacked;?></th>
            <th><?php echo $strField;?></th>
            <th><?php echo $strCardinality;?></th>
            <th><?php echo $strCollation;?></th>
            <th><?php echo $strNull;?></th>
            <th><?php echo $strComment;?></th>
        </tr>
        <tbody>
<?php
        $style = 'odd';
        foreach ($indexes as $indexes_index => $index) {
            if ($index['Non_unique'] == 0) {
                $str_unique = $strYes;
            } else {
                $str_unique = $strNo;
            }
            if ($index['Packed'] != '') {
                $str_packed = $strYes;
            } else {
                $str_packed = $strNo;
            }
?>
            <tr class="<?php echo $style; ?>">
                <td><b><?php echo $index['Key_name'];?></b></td>
                <td><?php echo $index['Index_type'];?></td>
                <td><?php echo $str_unique;?></td>
                <td><?php echo $str_packed;?></td>
                <td><?php echo $index['Column_name'];?></td>
                <td><?php echo $index['Cardinality'];?></td>
                <td><?php echo $index['Collation'];?></td>
                <td><?php echo $index['Null'];?></td>
                <td><?php echo $index['Comment'];?></td>
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
if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    ?>
    <h3><?php echo $strTrackingReport;?>  [<a href="tbl_tracking.php?<?php echo $url_query;?>"><?php echo $strTrackingReportClose;?></a>]</h3>

    <small><?php echo $strTrackingStatements . ' ' . $data['tracking']; ?></small><br/>
    <br/>

    <form method="post" action="tbl_tracking.php?<?php echo $url_query; ?>&amp;report=true&amp;version=<?php echo $_REQUEST['version'];?>">
    <?php

    $str1 = '<select name="logtype">' .
            '<option value="schema"' . ($selection_schema ? ' selected="selected"' : '') . '>' . $strStrucOnly . '</option>' .
            '<option value="data"' . ($selection_data ? ' selected="selected"' : ''). '>' . $strDataOnly . '</option>' .
            '<option value="schema_and_data"' . ($selection_both ? ' selected="selected"' : '') . '>' . $strStrucData . '</option>' .
            '</select>';
    $str2 = '<input type="text" name="date_from" value="' . $_REQUEST['date_from'] . '" size="19" />';
    $str3 = '<input type="text" name="date_to" value="' . $_REQUEST['date_to'] . '" size="19" />';
    $str4 = '<input type="text" name="users" value="' . $_REQUEST['users'] . '" />';
    $str5 = '<input type="submit" name="list_report" value="' . $strGo . '" />';

    printf($strTrackingShowLogDateUsers, $str1, $str2, $str3, $str4, $str5);


    /*
     *  First, list tracked data definition statements
     */
    $i = 1;
    if ($selection_schema || $selection_both ) {
    ?>
        <table id="ddl_versions" class="data" width="100%">
        <thead>
        <tr>
            <th width="18">#</th>
            <th width="100"><?php echo $strTrackingDate;?></th>
            <th width="60"><?php echo $strTrackingUsername;?></th>
            <th><?php echo $strTrackingDataDefinitionStatement;?></th>
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
                <tr class="<?php echo $style; ?>">
                    <td><small><?php echo $i;?></small></td>
                    <td><small><?php echo $entry['date'];?></small></td>
                    <td><small><?php echo $entry['username']; ?></small></td>
                    <td><?php echo $statement; ?></td>
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

    /*
     *  Secondly, list tracked data manipulation statements
     */

    if (($selection_data || $selection_both) && count($data['dmlog']) > 0) {
    ?>
        <table id="dml_versions" class="data" width="100%">
        <thead>
        <tr>
            <th width="18">#</th>
            <th width="100"><?php echo $strTrackingDate;?></th>
            <th width="60"><?php echo $strTrackingUsername;?></th>
            <th><?php echo $strTrackingDataManipulationStatement;?></th>
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
                <tr class="<?php echo $style; ?>">
                    <td><small><?php echo $i; ?></small></td>
                    <td><small><?php echo $entry['date']; ?></small></td>
                    <td><small><?php echo $entry['username']; ?></small></td>
                    <td><?php echo $statement; ?></td>
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
    <form method="post" action="tbl_tracking.php?<?php echo $url_query; ?>&amp;report=true&amp;version=<?php echo $_REQUEST['version'];?>">
    <?php
    printf($strTrackingShowLogDateUsers, $str1, $str2, $str3, $str4, $str5);

    $str_export1 =  '<select name="export_type">' .
                    '<option value="sqldumpfile">' . $strTrackingSQLDumpFile . '</option>' .
                    '<option value="sqldump">' . $strTrackingSQLDump . '</option>' .
                    '<option value="execution" onclick="alert(\'' . $strTrackingSQLExecutionAlert .'\')">' . $strTrackingSQLExecution . '</option>' .
                    '</select>';

    $str_export2 = '<input type="submit" name="report_export" value="' . $strGo .'" />';
    ?>
    </form>
    <form method="post" action="tbl_tracking.php?<?php echo $url_query; ?>&amp;report=true&amp;version=<?php echo $_REQUEST['version'];?>">
    <input type="hidden" name="logtype" value="<?php echo $_REQUEST['logtype'];?>" />
    <input type="hidden" name="date_from" value="<?php echo $_REQUEST['date_from'];?>" />
    <input type="hidden" name="date_to" value="<?php echo $_REQUEST['date_to'];?>" />
    <input type="hidden" name="users" value="<?php echo $_REQUEST['users'];?>" />
    <?php
    echo "<br/>" . sprintf($strTrackingExportAs, $str_export1) . $str_export2 . "<br/>";
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
             " WHERE " . PMA_backquote('db_name') . " = '" . PMA_sqlAddslashes($GLOBALS['db']) . "' " .
             " ORDER BY ". PMA_backquote('db_name') . ", " . PMA_backquote('table_name');

$sql_result = PMA_query_as_controluser($sql_query);

if (PMA_DBI_num_rows($sql_result) > 0) {
?>
    <form method="post" action="tbl_tracking.php?<?php echo $url_query;?>">
    <select name="table">
    <?php
    while ($entries = PMA_DBI_fetch_array($sql_result)) {
        if (PMA_Tracker::isTracked($entries['db_name'], $entries['table_name'])) {
            $status = ' (' . $strTrackingStatusActive . ')';
        } else {
            $status = ' (' . $strTrackingStatusNotActive . ')';
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
    <input type="submit" name="show_versions_submit" value="<?php echo $strTrackingShowVersions;?>" />
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
             " WHERE " . PMA_backquote('db_name')    . " = '" . PMA_sqlAddslashes($_REQUEST['db']) . "' ".
             " AND "   . PMA_backquote('table_name') . " = '" . PMA_sqlAddslashes($_REQUEST['table']) ."' ".
             " ORDER BY ". PMA_backquote('version') . " DESC ";

$sql_result = PMA_query_as_controluser($sql_query);

$last_version = 0;
$maxversion = PMA_DBI_fetch_array($sql_result);
$last_version = $maxversion['version'];

if ($last_version > 0) {
?>
    <table id="versions" class="data">
    <thead>
    <tr>
        <th><?php echo $strDatabase;?></th>
        <th><?php echo $strTable;?></th>
        <th><?php echo $strTrackingThVersion;?></th>
        <th><?php echo $strTrackingThCreated;?></th>
        <th><?php echo $strTrackingThUpdated;?></th>
        <th><?php echo $strStatus;?></th>
        <th><?php echo $strShow;?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $style = 'odd';
    PMA_DBI_data_seek($sql_result, 0);
    while($version = PMA_DBI_fetch_array($sql_result)) {
        if ($version['tracking_active'] == 1) {
            $version_status = $strTrackingStatusActive;
        } else {
            $version_status = $strTrackingStatusNotActive;
        }
        if (($version['version'] == $last_version) && ($version_status == $strTrackingStatusNotActive)) {
            $tracking_active = false;
        }
        if (($version['version'] == $last_version) && ($version_status == $strTrackingStatusActive)) {
            $tracking_active = true;
        }
    ?>
        <tr class="<?php echo $style;?>">
            <td><?php echo htmlspecialchars($version['db_name']);?></td>
            <td><?php echo htmlspecialchars($version['table_name']);?></td>
            <td><?php echo $version['version'];?></td>
            <td><?php echo $version['date_created'];?></td>
            <td><?php echo $version['date_updated'];?></td>
            <td><?php echo $version_status;?></td>
            <td> <a href="tbl_tracking.php?<?php echo $url_query;?>&amp;report=true&amp;version=<?php echo $version['version'];?>"><?php echo $strTrackingReport;?></a> | <a href="tbl_tracking.php?<?php echo $url_query;?>&amp;snapshot=true&amp;version=<?php echo $version['version'];?>"><?php echo $strTrackingStructureSnapshot;?></a></td>
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
            <legend><?php printf($strTrackingDeactivateTrackingFor, $GLOBALS['db'], $GLOBALS['table']); ?></legend>
            <input type="hidden" name="version" value="<?php echo $last_version; ?>" />
            <input type="submit" name="submit_deactivate_now" value="<?php echo $strTrackingDeactivateNow; ?>" />
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
            <legend><?php printf($strTrackingActivateTrackingFor, $GLOBALS['db'], $GLOBALS['table']); ?></legend>
            <input type="hidden" name="version" value="<?php echo $last_version; ?>" />
            <input type="submit" name="submit_activate_now" value="<?php echo $strTrackingActivateNow; ?>" />
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
    <legend><?php printf($strTrackingCreateVersionOf, ($last_version + 1), $GLOBALS['db'], $GLOBALS['table']); ?></legend>

    <input type="hidden" name="version" value="<?php echo ($last_version + 1); ?>" />

    <p><?php echo $strTrackingTrackDDStatements;?></p>
    <input type="checkbox" name="alter_table" value="true" checked="checked" /> ALTER TABLE<br/>
    <input type="checkbox" name="rename_table" value="true" checked="checked" /> RENAME TABLE<br/>
    <input type="checkbox" name="create_table" value="true" checked="checked" /> CREATE TABLE<br/>
    <input type="checkbox" name="drop_table" value="true" checked="checked" /> DROP TABLE<br/>
    <br/>
    <input type="checkbox" name="create_index" value="true" checked="checked" /> CREATE INDEX<br/>
    <input type="checkbox" name="drop_index" value="true" checked="checked" /> DROP INDEX<br/>
    <p><?php echo $strTrackingTrackDMStatements;?></p>
    <input type="checkbox" name="insert" value="true" checked="checked" /> INSERT<br/>
    <input type="checkbox" name="update" value="true" checked="checked" /> UPDATE<br/>
    <input type="checkbox" name="delete" value="true" checked="checked" /> DELETE<br/>
    <input type="checkbox" name="truncate" value="true" checked="checked" /> TRUNCATE<br/>

</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="submit_create_version" value="<?php echo $strTrackingCreateVersion; ?>" />
</fieldset>
</form>
</div>

<br class="clearfloat"/>

<?php
/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
