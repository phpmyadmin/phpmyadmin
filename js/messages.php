<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Exporting of translated messages from PHP to Javascript
 *
 * @package phpMyAdmin
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
// But this one is needed for PMA_escapeJsString()
require_once './libraries/js_escape.lib.php';

$js_messages['strClickToSelect'] = __('Click to select');
$js_messages['strClickToUnselect'] = __('Click to unselect');
$js_messages['strNoDropDatabases'] = __('"DROP DATABASE" statements are disabled.');

/* For confirmations */
$js_messages['strDoYouReally'] = __('Do you really want to ');
$js_messages['strDropDatabaseStrongWarning'] = __('You are about to DESTROY a complete database!');
$js_messages['strDroppingEvent'] = __('Dropping Event');
$js_messages['strDroppingProcedure'] = __('Dropping Procedure');
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

/* For server_privileges.js */
$js_messages['strHostEmpty'] = __('The host name is empty!');
$js_messages['strUserEmpty'] = __('The user name is empty!');
$js_messages['strPasswordEmpty'] = __('The password is empty!');
$js_messages['strPasswordNotSame'] = __('The passwords aren\'t the same!');
$js_messages['strAddNewUser'] = __('Add a New User');
$js_messages['strCreateUser'] = __('Create User');
$js_messages['strReloadingPrivileges'] = __('Reloading Privileges');
$js_messages['strRemovingSelectedUsers'] = __('Removing Selected Users');
$js_messages['strClose'] = __('Close');

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

/* For db_operations.js */
$js_messages['strRenamingDatabases'] = __('Renaming Databases');
$js_messages['strReloadDatabase'] = __('Reload Database');
$js_messages['strCopyingDatabase'] = __('Copying Database');
$js_messages['strChangingCharset'] = __('Changing Charset');
$js_messages['strTableMustHaveAtleastOneColumn'] = __('Table must have at least one column');
$js_messages['strCreateTable'] = __('Create Table');
$js_messages['strYes'] = __('Yes');
$js_messages['strNo'] = __('No');

/* For db_search.js */
$js_messages['strSearching'] = __('Searching');
//$js_messages['strBrowsing'] = __('Browsing');
//$js_messages['strDeleting'] = __('Deleting');

/* For sql.js */
$js_messages['strHideQueryBox'] = __('Hide query box');
$js_messages['strShowQueryBox'] = __('Show query box');
$js_messages['strInlineEdit'] = __('Inline Edit');
$js_messages['strEdit'] = __('Edit');
$js_messages['strSave'] = __('Save');
$js_messages['strHide'] = __('Hide');

/* For tbl_select.js */
$js_messages['strHideSearchCriteria'] = __('Hide search criteria');
$js_messages['strShowSearchCriteria'] = __('Show search criteria');

/* For tbl_change.js */
$js_messages['strIgnore'] = __('Ignore');

/* Designer (pmd/scripts/move.js) */
$js_messages['strSelectReferencedKey'] = __('Select referenced key');
$js_messages['strSelectForeignKey'] = __('Select Foreign Key');
$js_messages['strPleaseSelectPrimaryOrUniqueKey'] = __('Please select the primary key or a unique key');
$js_messages['strChangeDisplay'] = __('Choose column to display');

/* Visual query builder (pmd/scripts/move.js) */
$js_messages['strAddOption'] = __('Add an option for column ');

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
PMA_printJsValue("$.datepicker.regional['']['prevText']", __('Prev'));
/* l10n: Display text for next month link in calendar */
PMA_printJsValue("$.datepicker.regional['']['nextText']", __('Next'));
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

PMA_printJsValue("$.datepicker.regional['']['hourText']", __('Hour'));
PMA_printJsValue("$.datepicker.regional['']['minuteText']", __('Minute'));
PMA_printJsValue("$.datepicker.regional['']['secondText']", __('Second'));
?>
$.extend($.datepicker._defaults, $.datepicker.regional['']);
} /* if ($.datepicker) */
