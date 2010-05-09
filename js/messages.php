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

/* For blobstreaming */
$js_messages['strBLOBRepositoryDisableStrongWarning'] = __('You are about to DISABLE a BLOB Repository!'); 
$js_messages['strBLOBRepositoryDisableAreYouSure'] = sprintf(__('Are you sure you want to disable all BLOB references for database %s?'), $GLOBALS['db']);

/* For indexes */
$js_messages['strFormEmpty'] = __('Missing value in the form!'); 
$js_messages['strNotNumber'] = __('This is not a number!'); 

/* For server_privileges.js */
$js_messages['strHostEmpty'] = __('The host name is empty!'); 
$js_messages['strUserEmpty'] = __('The user name is empty!'); 
$js_messages['strPasswordEmpty'] = __('The password is empty!'); 
$js_messages['strPasswordNotSame'] = __('The passwords aren\'t the same!'); 

/* For inline query editing */
$js_messages['strGo'] = __('Go');
$js_messages['strCancel'] = __('Cancel');

/* Designer */
$js_messages['strModifications'] = __('Modifications have been saved'); 
$js_messages['strRelationDeleted'] = __('Relation deleted'); 
$js_messages['strForeignKeyRelationAdded'] = __('FOREIGN KEY relation added'); 
$js_messages['strInternalRelationAdded'] = __('Internal relation added'); 
$js_messages['strErrorRelationAdded'] = __('Error: Relation not added.'); 
$js_messages['strErrorRelationExists'] = __('Error: relation already exists.'); 
$js_messages['strErrorSaveTable'] = __('Error saving coordinates for Designer.'); 
$js_messages['strGeneralRelationFeat:strDisabled'] = __('General relation features') . ': ' . __('Disabled');
$js_messages['strSelectReferencedKey'] = __('Select referenced key'); 
$js_messages['strSelectForeignKey'] = __('Select Foreign Key'); 
$js_messages['strPleaseSelectPrimaryOrUniqueKey'] = __('Please select the primary key or a unique key'); 
$js_messages['strChangeDisplay'] = __('Choose field to display'); 

echo "var PMA_messages = new Array();\n";
foreach ($js_messages as $name => $js_message) {
    PMA_printJsValue("PMA_messages['" . $name . "']", $js_message);
}

/* Calendar */
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
