<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Exporting of translated messages from PHP to Javascript
 *
 * @package PhpMyAdmin
 */

chdir('..');

// Send correct type:
header('Content-Type: text/javascript; charset=UTF-8');

// Cache output in client - the nocache query parameter makes sure that this
// file is reloaded when config changes
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Avoid loading the full common.inc.php because this would add many
// non-js-compatible stuff like DOCTYPE
define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';
// Close session early as we won't write anything there
session_write_close();
// But this one is needed for PMA_escapeJsString()
require_once './libraries/js_escape.lib.php';

$js_messages['strNoDropDatabases'] = $cfg['AllowUserDropDatabase'] ? '' : __('"DROP DATABASE" statements are disabled.');

/* For confirmations */
$js_messages['strDoYouReally'] = __('Do you really want to ');
$js_messages['strDropDatabaseStrongWarning'] = __('You are about to DESTROY a complete database!');
$js_messages['strDropTableStrongWarning'] = __('You are about to DESTROY a complete table!');
$js_messages['strTruncateTableStrongWarning'] = __('You are about to TRUNCATE a complete table!');
$js_messages['strDeleteTrackingData'] = __('Delete tracking data for this table');
$js_messages['strDeletingTrackingData'] = __('Deleting tracking data');
$js_messages['strDroppingPrimaryKeyIndex'] = __('Dropping Primary Key/Index');
$js_messages['strOperationTakesLongTime'] = __('This operation could take a long time. Proceed anyway?');

/* For blobstreaming */
$js_messages['strBLOBRepositoryDisableStrongWarning'] = __('You are about to DISABLE a BLOB Repository!');
$js_messages['strBLOBRepositoryDisableAreYouSure'] = sprintf(__('Are you sure you want to disable all BLOB references for database %s?'), PMA_escapeJsString($GLOBALS['db']));

/* For indexes */
$js_messages['strFormEmpty'] = __('Missing value in the form!');
$js_messages['strNotNumber'] = __('This is not a number!');
$js_messages['strAddIndex'] = __('Add Index');
$js_messages['strEditIndex'] = __('Edit Index');
$js_messages['strAddToIndex'] = __('Add %d column(s) to index');

/* Charts */
/* l10n: Default description for the y-Axis of Charts */
$js_messages['strTotalCount'] = __('Total count');

/* For server_privileges.js */
$js_messages['strHostEmpty'] = __('The host name is empty!');
$js_messages['strUserEmpty'] = __('The user name is empty!');
$js_messages['strPasswordEmpty'] = __('The password is empty!');
$js_messages['strPasswordNotSame'] = __('The passwords aren\'t the same!');
$js_messages['strAddUser'] = __('Add user');
$js_messages['strReloadingPrivileges'] = __('Reloading Privileges');
$js_messages['strRemovingSelectedUsers'] = __('Removing Selected Users');
$js_messages['strClose'] = __('Close');

/* for server_status.js */
$js_messages['strEdit'] = __('Edit');
$js_messages['strLiveTrafficChart'] = __('Live traffic chart');
$js_messages['strLiveConnChart'] = __('Live conn./process chart');
$js_messages['strLiveQueryChart'] = __('Live query chart');

$js_messages['strStaticData'] = __('Static data');
/* l10n: Total number of queries */
$js_messages['strTotal'] = __('Total');
/* l10n: Other, small valued, queries */
$js_messages['strOther'] = __('Other');
/* l10n: Thousands separator */
$js_messages['strThousandsSeperator'] = __(',');
/* l10n: Decimal separator */
$js_messages['strDecimalSeperator'] = __('.');

$js_messages['strChartKBSent'] = __('KiB sent since last refresh');
$js_messages['strChartKBReceived'] = __('KiB received since last refresh');
$js_messages['strChartServerTraffic'] = __('Server traffic (in KiB)');
$js_messages['strChartConnections'] = __('Connections since last refresh');
$js_messages['strChartProcesses'] = __('Processes');
$js_messages['strChartConnectionsTitle'] = __('Connections / Processes');
/* l10n: Questions is the name of a MySQL Status variable */
$js_messages['strChartIssuedQueries'] = __('Questions since last refresh');
/* l10n: Questions is the name of a MySQL Status variable */
$js_messages['strChartIssuedQueriesTitle'] = __('Questions (executed statements by the server)');

$js_messages['strChartQueryPie'] = __('Query statistics');

/* server status monitor */
$js_messages['strIncompatibleMonitorConfig'] = __('Local monitor configuration incompatible');
$js_messages['strIncompatibleMonitorConfigDescription'] = __('The chart arrangement configuration in your browsers local storage is not compatible anymore to the newer version of the monitor dialog. It is very likely that your current configuration will not work anymore. Please reset your configuration to default in the <i>Settings</i> menu.');

$js_messages['strQueryCacheEfficiency'] = __('Query cache efficiency');
$js_messages['strQueryCacheUsage'] = __('Query cache usage');
$js_messages['strQueryCacheUsed'] = __('Query cache used');

$js_messages['strSystemCPUUsage'] = __('System CPU Usage');
$js_messages['strSystemMemory'] = __('System memory');
$js_messages['strSystemSwap'] = __('System swap');
$js_messages['strMiB'] = __('MiB');
$js_messages['strKiB'] = __('KiB');

$js_messages['strAverageLoad'] = __('Average load');
$js_messages['strTotalMemory'] = __('Total memory');
$js_messages['strCachedMemory'] = __('Cached memory');
$js_messages['strBufferedMemory'] = __('Buffered memory');
$js_messages['strFreeMemory'] = __('Free memory');
$js_messages['strUsedMemory'] = __('Used memory');

$js_messages['strTotalSwap'] = __('Total Swap');
$js_messages['strCachedSwap'] = __('Cached Swap');
$js_messages['strUsedSwap'] = __('Used Swap');
$js_messages['strFreeSwap'] = __('Free Swap');

$js_messages['strBytesSent'] = __('Bytes sent');
$js_messages['strBytesReceived'] = __('Bytes received');
$js_messages['strConnections'] = __('Connections');
$js_messages['strProcesses'] = __('Processes');

/* summary row */
$js_messages['strB'] = __('B');
$js_messages['strKiB'] = __('KiB');
$js_messages['strMiB'] = __('MiB');
$js_messages['strGiB'] = __('GiB');
$js_messages['strTiB'] = __('TiB');
$js_messages['strPiB'] = __('PiB');
$js_messages['strEiB'] = __('EiB');
$js_messages['strTables'] = __('%d table(s)');

/* l10n: Questions is the name of a MySQL Status variable */
$js_messages['strQuestions'] = __('Questions');
$js_messages['strTraffic'] = __('Traffic');
$js_messages['strSettings'] = __('Settings');
$js_messages['strRemoveChart'] = __('Remove chart');
$js_messages['strEditChart'] = __('Edit title and labels');
$js_messages['strAddChart'] = __('Add chart to grid');
$js_messages['strClose'] = __('Close');
$js_messages['strAddOneSeriesWarning'] = __('Please add at least one variable to the series');
$js_messages['strNone'] = __('None');
$js_messages['strResumeMonitor'] = __('Resume monitor');
$js_messages['strPauseMonitor'] = __('Pause monitor');
/* Monitor: Instructions Dialog */
$js_messages['strBothLogOn'] = __('general_log and slow_query_log are enabled.');
$js_messages['strGenLogOn'] = __('general_log is enabled.');
$js_messages['strSlowLogOn'] = __('slow_query_log is enabled.');
$js_messages['strBothLogOff'] = __('slow_query_log and general_log are disabled.');
$js_messages['strLogOutNotTable'] = __('log_output is not set to TABLE.');
$js_messages['strLogOutIsTable'] = __('log_output is set to TABLE.');
$js_messages['strSmallerLongQueryTimeAdvice'] = __('slow_query_log is enabled, but the server logs only queries that take longer than %d seconds. It is advisable to set this long_query_time 0-2 seconds, depending on your system.');
$js_messages['strLongQueryTimeSet'] = __('long_query_time is set to %d second(s).');
$js_messages['strSettingsAppliedGlobal'] = __('Following settings will be applied globally and reset to default on server restart:');
/* l10n: %s is FILE or TABLE */
$js_messages['strSetLogOutput'] = __('Set log_output to %s');
/* l10n: Enable in this context means setting a status variable to ON */
$js_messages['strEnableVar'] = __('Enable %s');
/* l10n: Disable in this context means setting a status variable to OFF */
$js_messages['strDisableVar'] = __('Disable %s');
/* l10n: %d seconds */
$js_messages['setSetLongQueryTime'] = __('Set long_query_time to %ds');
$js_messages['strNoSuperUser'] = __('You can\'t change these variables. Please log in as root or contact your database administrator.');
$js_messages['strChangeSettings'] = __('Change settings');
$js_messages['strCurrentSettings'] = __('Current settings');

$js_messages['strChartTitle'] = __('Chart Title');
/* l10n: As in differential values */
$js_messages['strDifferential'] = __('Differential');
$js_messages['strDividedBy'] = __('Divided by %s');
$js_messages['strUnit'] = __('Unit');

$js_messages['strFromSlowLog'] = __('From slow log');
$js_messages['strFromGeneralLog'] = __('From general log');
$js_messages['strAnalysingLogsTitle'] = __('Analysing logs');
$js_messages['strAnalysingLogs'] = __('Analysing & loading logs. This may take a while.');
$js_messages['strCancelRequest'] = __('Cancel request');
$js_messages['strCountColumnExplanation'] = __('This column shows the amount of identical queries that are grouped together. However only the SQL query itself has been used as a grouping criteria, so the other attributes of queries, such as start time, may differ.');
$js_messages['strMoreCountColumnExplanation'] = __('Since grouping of INSERTs queries has been selected, INSERT queries into the same table are also being grouped together, disregarding of the inserted data.');
$js_messages['strLogDataLoaded'] = __('Log data loaded. Queries executed in this time span:');

$js_messages['strJumpToTable'] = __('Jump to Log table');
$js_messages['strNoDataFoundTitle'] = __('No data found');
$js_messages['strNoDataFound'] = __('Log analysed, but no data found in this time span.');

$js_messages['strAnalyzing'] = __('Analyzing...');
$js_messages['strExplainOutput'] = __('Explain output');
$js_messages['strStatus'] = __('Status');
$js_messages['strTime'] = __('Time');
$js_messages['strTotalTime'] = __('Total time:');
$js_messages['strProfilingResults'] = __('Profiling results');
$js_messages['strTable'] = _pgettext('Display format', 'Table');
$js_messages['strChart'] = __('Chart');
$js_messages['strChartEdit'] = __('Edit chart');
$js_messages['strSeries'] = __('Series');

/* l10n: A collection of available filters */
$js_messages['strFiltersForLogTable'] = __('Log table filter options');
/* l10n: Filter as in "Start Filtering" */
$js_messages['strFilter'] = __('Filter');
$js_messages['strFilterByWordRegexp'] = __('Filter queries by word/regexp:');
$js_messages['strIgnoreWhereAndGroup'] = __('Group queries, ignoring variable data in WHERE clauses');
$js_messages['strSumRows'] = __('Sum of grouped rows:');
$js_messages['strTotal'] = __('Total:');

$js_messages['strLoadingLogs'] = __('Loading logs');
$js_messages['strRefreshFailed'] = __('Monitor refresh failed');
$js_messages['strInvalidResponseExplanation'] = __('While requesting new chart data the server returned an invalid response. This is most likely because your session expired. Reloading the page and reentering your credentials should help.');
$js_messages['strReloadPage'] = __('Reload page');

$js_messages['strAffectedRows'] = __('Affected rows:');

$js_messages['strFailedParsingConfig'] = __('Failed parsing config file. It doesn\'t seem to be valid JSON code.');
$js_messages['strFailedBuildingGrid'] = __('Failed building chart grid with imported config. Resetting to default config...');
$js_messages['strImport'] = __('Import');
$js_messages['strImportDialogTitle'] = __('Import monitor configuration');
$js_messages['strImportDialogMessage'] = __('Please select the file you want to import');

$js_messages['strAnalyzeQuery'] = __('Analyse Query');

/* Server status advisor */

$js_messages['strAdvisorSystem'] = __('Advisor system');
$js_messages['strPerformanceIssues'] = __('Possible performance issues');
$js_messages['strIssuse'] = __('Issue');
$js_messages['strRecommendation'] = __('Recommendation');
$js_messages['strRuleDetails'] = __('Rule details');
$js_messages['strJustification'] = __('Justification');
$js_messages['strFormula'] = __('Used variable / formula');
$js_messages['strTest'] = __('Test');


/* For inline query editing */
$js_messages['strGo'] = __('Go');
$js_messages['strCancel'] = __('Cancel');

/* For Ajax Notifications */
$js_messages['strLoading'] = __('Loading');
$js_messages['strProcessingRequest'] = __('Processing Request');
$js_messages['strErrorProcessingRequest'] = __('Error in Processing Request');
$js_messages['strDroppingColumn'] = __('Dropping Column');
$js_messages['strAddingPrimaryKey'] = __('Adding Primary Key');
$js_messages['strOK'] = __('OK');
$js_messages['strDismiss'] = __('Click to dismiss this notification');

/* For db_operations.js */
$js_messages['strRenamingDatabases'] = __('Renaming Databases');
$js_messages['strReloadDatabase'] = __('Reload Database');
$js_messages['strCopyingDatabase'] = __('Copying Database');
$js_messages['strChangingCharset'] = __('Changing Charset');
$js_messages['strTableMustHaveAtleastOneColumn'] = __('Table must have at least one column');
$js_messages['strYes'] = __('Yes');
$js_messages['strNo'] = __('No');

/* For db_stucture.js */
$js_messages['strInsertTable'] = __('Insert Table');
$js_messages['strHideIndexes'] = __('Hide indexes');
$js_messages['strShowIndexes'] = __('Show indexes');

/* For db_search.js */
$js_messages['strSearching'] = __('Searching');
$js_messages['strHideSearchResults'] = __('Hide search results');
$js_messages['strShowSearchResults'] = __('Show search results');
$js_messages['strBrowsing'] = __('Browsing');
$js_messages['strDeleting'] = __('Deleting');

/* For db_routines.js */
$js_messages['MissingReturn'] = __('The definition of a stored function must contain a RETURN statement!');

/* For ENUM/SET editor*/
$js_messages['enum_editor'] = __('ENUM/SET editor');
$js_messages['enum_columnVals'] =__('Values for column %s');
$js_messages['enum_newColumnVals'] = __('Values for a new column');
$js_messages['enum_hint'] =__('Enter each value in a separate field');
$js_messages['enum_addValue'] =__('Add %d value(s)');

/* For import.js */
$js_messages['strImportCSV'] = __('Note: If the file contains multiple tables, they will be combined into one');

/* For sql.js */
$js_messages['strHideQueryBox'] = __('Hide query box');
$js_messages['strShowQueryBox'] = __('Show query box');
$js_messages['strEdit'] = __('Edit');
$js_messages['strNoRowSelected'] = __('No rows selected');
$js_messages['strChangeTbl'] = __('Change');
$js_messages['strQueryExecutionTime'] = __('Query execution time');
$js_messages['strNotValidRowNumber'] = __('%d is not valid row number.');

/* For server_variables.js */
$js_messages['strSave'] = __('Save');

/* For tbl_select.js */
$js_messages['strHideSearchCriteria'] = __('Hide search criteria');
$js_messages['strShowSearchCriteria'] = __('Show search criteria');

/* For tbl_zoom_plot_jqplot.js */
$js_messages['strZoomSearch'] = __('Zoom Search');
$js_messages['strDisplayHelp'] = '<ul><li>'
    . __('Each point represents a data row.')
    . '</li><li>'
    . __('Hovering over a point will show its label.')
    . '</li><li>'
    . __('To zoom in, select a section of the plot with the mouse.')
    . '</li><li>'
    . __('Click reset zoom link to come back to original state.')
    . '</li><li>'
    . __('Click a data point to view and possibly edit the data row.')
    . '</li><li>'
    . __('The plot can be resized by dragging it along the bottom right corner.')
    . '</li></ul>';
$js_messages['strInputNull'] = '<strong>' . __('Select two columns') . '</strong>';
$js_messages['strSameInputs'] = '<strong>' . __('Select two different columns') . '</strong>';
$js_messages['strQueryResults'] = __('Query results');
$js_messages['strDataPointContent'] = __('Data point content');

/* For tbl_change.js */
$js_messages['strIgnore'] = __('Ignore');
$js_messages['strCopy'] = __('Copy');
$js_messages['strX'] = __('X');
$js_messages['strY'] = __('Y');
$js_messages['strPoint'] = __('Point');
$js_messages['strPointN'] = __('Point %d');
$js_messages['strLineString'] = __('Linestring');
$js_messages['strPolygon'] = __('Polygon');
$js_messages['strGeometry'] = __('Geometry');
$js_messages['strInnerRing'] = __('Inner Ring');
$js_messages['strOuterRing'] = __('Outer Ring');
$js_messages['strAddPoint'] = __('Add a point');
$js_messages['strAddInnerRing'] = __('Add an inner ring');
$js_messages['strAddPolygon'] = __('Add a polygon');

/* For tbl_structure.js */
$js_messages['strAddColumns'] = __('Add columns');

/* Designer (js/pmd/move.js) */
$js_messages['strSelectReferencedKey'] = __('Select referenced key');
$js_messages['strSelectForeignKey'] = __('Select Foreign Key');
$js_messages['strPleaseSelectPrimaryOrUniqueKey'] = __('Please select the primary key or a unique key');
$js_messages['strChangeDisplay'] = __('Choose column to display');
$js_messages['strLeavingDesigner'] = __('You haven\'t saved the changes in the layout. They will be lost if you don\'t save them. Do you want to continue?');

/* Visual query builder (js/pmd/move.js) */
$js_messages['strAddOption'] = __('Add an option for column ');

/* For makegrid.js (column reordering, show/hide column, grid editing) */
$js_messages['strCellEditHint'] = __('Press escape to cancel editing');
$js_messages['strSaveCellWarning'] = __('You have edited some data and they have not been saved. Are you sure you want to leave this page before saving the data?');
$js_messages['strColOrderHint'] = __('Drag to reorder');
$js_messages['strSortHint'] = __('Click to sort');
$js_messages['strColMarkHint'] = __('Click to mark/unmark');
$js_messages['strColVisibHint'] = __('Click the drop-down arrow<br />to toggle column\'s visibility');
$js_messages['strShowAllCol'] = __('Show all');
$js_messages['strAlertNonUnique'] = __('This table does not contain a unique column. Features related to the grid edit, checkbox, Edit, Copy and Delete links may not work after saving.');
$js_messages['strGridEditFeatureHint'] = __('You can also edit most columns<br />by clicking directly on their content.');
$js_messages['strGoToLink'] = __('Go to link');

/* password generation */
$js_messages['strGeneratePassword'] = __('Generate password');
$js_messages['strGenerate'] = __('Generate');
$js_messages['strChangePassword'] = __('Change Password');

/* navigation tabs */
$js_messages['strMore'] = __('More');

/* update */
$js_messages['strNewerVersion'] = __('A newer version of phpMyAdmin is available and you should consider upgrading. The newest version is %s, released on %s.');
/* l10n: Latest available phpMyAdmin version */
$js_messages['strLatestAvailable'] = __(', latest stable version:');
$js_messages['strUpToDate'] = __('up to date');


echo "var PMA_messages = new Array();\n";
foreach ($js_messages as $name => $js_message) {
    PMA_printJsValue("PMA_messages['" . $name . "']", $js_message);
}

/* Calendar */
echo "var themeCalendarImage = '" . $GLOBALS['pmaThemeImage'] . 'b_calendar.png' . "';\n";

/* Image path */
echo "var pmaThemeImage = '" . $GLOBALS['pmaThemeImage'] . "';\n";

/* Version */
echo "var pmaversion = '" . PMA_VERSION . "';\n";

echo "if ($.datepicker) {\n";
/* l10n: Display text for calendar close link */
PMA_printJsValue("$.datepicker.regional['']['closeText']", __('Done'));
/* l10n: Display text for previous month link in calendar */
PMA_printJsValue(
    "$.datepicker.regional['']['prevText']",
    _pgettext('Previous month', 'Prev')
);
/* l10n: Display text for next month link in calendar */
PMA_printJsValue(
    "$.datepicker.regional['']['nextText']",
    _pgettext('Next month', 'Next')
);
/* l10n: Display text for current month link in calendar */
PMA_printJsValue("$.datepicker.regional['']['currentText']", __('Today'));
PMA_printJsValue("$.datepicker.regional['']['monthNames']",
    array(
        __('January'),
        __('February'),
        __('March'),
        __('April'),
        __('May'),
        __('June'),
        __('July'),
        __('August'),
        __('September'),
        __('October'),
        __('November'),
        __('December')));
PMA_printJsValue("$.datepicker.regional['']['monthNamesShort']",
    array(
/* l10n: Short month name */
        __('Jan'),
/* l10n: Short month name */
        __('Feb'),
/* l10n: Short month name */
        __('Mar'),
/* l10n: Short month name */
        __('Apr'),
/* l10n: Short month name */
        _pgettext('Short month name', 'May'),
/* l10n: Short month name */
        __('Jun'),
/* l10n: Short month name */
        __('Jul'),
/* l10n: Short month name */
        __('Aug'),
/* l10n: Short month name */
        __('Sep'),
/* l10n: Short month name */
        __('Oct'),
/* l10n: Short month name */
        __('Nov'),
/* l10n: Short month name */
        __('Dec')));
PMA_printJsValue("$.datepicker.regional['']['dayNames']",
    array(
        __('Sunday'),
        __('Monday'),
        __('Tuesday'),
        __('Wednesday'),
        __('Thursday'),
        __('Friday'),
        __('Saturday')));
PMA_printJsValue("$.datepicker.regional['']['dayNamesShort']",
    array(
/* l10n: Short week day name */
        __('Sun'),
/* l10n: Short week day name */
        __('Mon'),
/* l10n: Short week day name */
        __('Tue'),
/* l10n: Short week day name */
        __('Wed'),
/* l10n: Short week day name */
        __('Thu'),
/* l10n: Short week day name */
        __('Fri'),
/* l10n: Short week day name */
        __('Sat')));
PMA_printJsValue("$.datepicker.regional['']['dayNamesMin']",
    array(
/* l10n: Minimal week day name */
        __('Su'),
/* l10n: Minimal week day name */
        __('Mo'),
/* l10n: Minimal week day name */
        __('Tu'),
/* l10n: Minimal week day name */
        __('We'),
/* l10n: Minimal week day name */
        __('Th'),
/* l10n: Minimal week day name */
        __('Fr'),
/* l10n: Minimal week day name */
        __('Sa')));
/* l10n: Column header for week of the year in calendar */
PMA_printJsValue("$.datepicker.regional['']['weekHeader']", __('Wk'));

/* l10n: Month-year order for calendar, use either "calendar-month-year" or "calendar-year-month". */
PMA_printJsValue("$.datepicker.regional['']['showMonthAfterYear']", (__('calendar-month-year') == 'calendar-year-month'));
/* l10n: Year suffix for calendar, "none" is empty. */
$year_suffix = _pgettext('Year suffix', 'none');
PMA_printJsValue("$.datepicker.regional['']['yearSuffix']", ($year_suffix == 'none' ? '' : $year_suffix));
?>
$.extend($.datepicker._defaults, $.datepicker.regional['']);
} /* if ($.datepicker) */

<?php
echo "if ($.timepicker) {\n";
PMA_printJsValue("$.timepicker.regional['']['timeText']", __('Time'));
PMA_printJsValue("$.timepicker.regional['']['hourText']", __('Hour'));
PMA_printJsValue("$.timepicker.regional['']['minuteText']", __('Minute'));
PMA_printJsValue("$.timepicker.regional['']['secondText']", __('Second'));
?>
$.extend($.timepicker._defaults, $.timepicker.regional['']);
} /* if ($.timepicker) */
