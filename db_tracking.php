<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 * @author Alexander Rutkowski
 * @package phpMyAdmin
 */

// Run common work
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';

require './libraries/db_common.inc.php';
$url_query .= '&amp;goto=tbl_tracking.php&amp;back=db_tracking.php';

// Get the database structure
$sub_part = '_structure';
require './libraries/db_info.inc.php';

// Get relation settings
require_once './libraries/relation.lib.php';

// Get tracked data about the database
$data = PMA_Tracker::getTrackedData($_REQUEST['db'], '', '1');

// No tables present and no log exist
if ($num_tables == 0 and count($data['ddlog']) == 0)
{
    echo '<p>' . $strNoTablesFound . '</p>' . "\n";

    if (empty($db_is_information_schema))
    {
        require './libraries/display_create_table.lib.php';
    }

    // Display the footer
    require_once './libraries/footer.inc.php';
    exit;
}

// ---------------------------------------------------------------------------

/*
 * Display top menu links
 */
require_once './libraries/db_links.inc.php';
?>
<p/>
<p/>
<?php

/*
 * List versions of current table
 */

// Prepare statement to get HEAD version
$sql_query = ' SELECT *, MAX(version) as version FROM ' .
             PMA_backquote($GLOBALS['cfg']['Server']['pmadb']) . '.' .
             PMA_backquote($GLOBALS['cfg']['Server']['tracking']) .
             ' WHERE ' . PMA_backquote('db_name')    . ' = \'' . PMA_sqlAddslashes($_REQUEST['db']) . '\' ' .
             ' AND ' . PMA_backquote('table_name')    . ' <> \'' . PMA_sqlAddslashes('') . '\' ' .
             ' GROUP BY '. PMA_backquote('table_name') .
             ' ORDER BY '. PMA_backquote('table_name') .'ASC , '. PMA_backquote('version') .' ASC ';

$sql_result = PMA_query_as_controluser($sql_query);

// Init HEAD version
$last_version = 0;

// Get HEAD version
$maxversion = PMA_DBI_fetch_array($sql_result);
$last_version = $maxversion['version'];

// If a HEAD version exists
if($last_version > 0)
{
?>
    <h3><?php echo $strTrackingTrackedTables;?></h3>

    <table id="versions" class="data">
    <thead>
    <tr>
        <th><?php echo $strTrackingThDatabase;?></th>
        <th><?php echo $strTrackingThTable;?></th>
        <th><?php echo $strTrackingThLastVersion;?></th>
        <th><?php echo $strTrackingThCreated;?></th>
        <th><?php echo $strTrackingThUpdated;?></th>
        <th><?php echo $strTrackingThStatus;?></th>
        <th><?php echo $strTrackingThShow;?></th>
    </tr>
    </thead>
    <tbody>
    <?php

    // Print out information about versions

    $style = 'odd';
    PMA_DBI_data_seek($sql_result, 0);
    while($version = PMA_DBI_fetch_array($sql_result))
    {
        if($version['tracking_active'] == 1)
            $version_status = $strTrackingStatusActive;
        else
            $version_status = $strTrackingStatusDeactive;

        if(($version['version'] == $last_version) and ($version_status == $strTrackingStatusDeactive))
            $tracking_active = false;
        if(($version['version'] == $last_version) and ($version_status == $strTrackingStatusActive))
            $tracking_active = true;
        ?>
        <tr class="<?php echo $style;?>">
            <td><?php echo $version['db_name'];?></td>
            <td><?php echo $version['table_name'];?></td>
            <td><?php echo $version['version'];?></td>
            <td><?php echo $version['date_created'];?></td>
            <td><?php echo $version['date_updated'];?></td>
            <td><?php echo $version_status;?></td>
            <td> <a href="tbl_tracking.php?<?php echo $url_query;?>&table=<?php echo $version['table_name'];?>"><?php echo $strTrackingVersions;?></a>
               | <a href="tbl_tracking.php?<?php echo $url_query;?>&table=<?php echo $version['table_name'];?>&report=true&version=<?php echo $version['version'];?>"><?php echo $strTrackingReport;?></a>
               | <a href="tbl_tracking.php?<?php echo $url_query;?>&table=<?php echo $version['table_name'];?>&snapshot=true&version=<?php echo $version['version'];?>"><?php echo $strTrackingStructureSnapshot;?></a></td>
        </tr>
        <?php
        if($style == 'even') $style = 'odd'; else $style = 'even';
    }
    ?>
    </tbody>
    </table>
<?php
}

// Get list of tables
$table_list = PMA_getTableList($GLOBALS['db']);

// For each table try to get the tracking version
foreach($table_list as $key => $value)
{
    if(PMA_Tracker::getVersion($GLOBALS['db'], $value['Name']) == -1)
        $my_tables[] = $value['Name'];
}

// If untracked tables exist
if(isset($my_tables))
{
?>
    <h3><?php echo $strTrackingUntrackedTables;?></h3>

    <table id="noversions" class="data">
    <thead>
    <tr>
        <th width="300"><?php echo $strTrackingThTable;?></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
<?php
    // Print out list of untracked tables

    $style = 'odd';

    foreach($my_tables as $key => $tablename)
    {
        if(PMA_Tracker::getVersion($GLOBALS['db'], $tablename) == -1)
        {
            $my_link = '<a href="tbl_tracking.php?' . $url_query . '&table=' . $tablename .'">';

            if ($cfg['PropertiesIconic'])
            {
                $my_link .= '<img class="icon" src="' . $pmaThemeImage . 'eye.png" width="16" height="16" alt="' . $strTrackingTrackTable . '" /> ';
            }
            $my_link .= $strTrackingTrackTable . '</a>';
        ?>
            <tr class="<?php echo $style;?>">
            <td><?php echo $tablename;?></td>
            <td><?php echo $my_link;?></td>
            </tr>
        <?php
            if($style == 'even') $style = 'odd'; else $style = 'even';
        }
    }
    ?>
    </tbody>
    </table>

<?php
}
?>
<p/>
<?php

// If available print out database log
if(count($data['ddlog']) > 0)
{
    $log = '';
    foreach ($data['ddlog'] as $entry)
    {
        $log .= '# ' . $entry['date'] . ' ' . $entry['username'] . "\n" . $entry['statement'] . "\n";
    }
    PMA_showMessage($strTrackingDatabaseLog, $log);
}


/**
 * Display the footer
 */
require_once './libraries/footer.inc.php';
?>
