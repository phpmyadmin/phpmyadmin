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

function PMA_printJsValue($key, $value) {
    echo $key . ' = ';
    if (is_array($value)) {
        echo '[';
        foreach ($value as $id => $val) {
            echo "'" . PMA_escapeJsString($val) . "',\n";
        }
        echo "];\n";
    } else {
        echo "'" . PMA_escapeJsString($value) . "';\n";
    }
}

$js_messages['strFormEmpty'] = $GLOBALS['strFormEmpty'];
$js_messages['strNotNumber'] = $GLOBALS['strNotNumber'];
$js_messages['strClickToSelect'] = $GLOBALS['strClickToSelect'];
$js_messages['strClickToUnselect'] = $GLOBALS['strClickToUnselect'];
$js_messages['strNoDropDatabases'] = $GLOBALS['strNoDropDatabases'];

/* For confirmations */
$js_messages['strDoYouReally'] = $GLOBALS['strDoYouReally'];
$js_messages['strDropDatabaseStrongWarning'] = $GLOBALS['strDropDatabaseStrongWarning'];

/* For blobstreaming */
$js_messages['strBLOBRepositoryDisableStrongWarning'] = $GLOBALS['strBLOBRepositoryDisableStrongWarning'];
$js_messages['strBLOBRepositoryDisableAreYouSure'] = sprintf($GLOBALS['strBLOBRepositoryDisableAreYouSure'], $GLOBALS['db']);

/* For indexes */
$js_messages['strFormEmpty'] = $GLOBALS['strFormEmpty'];
$js_messages['strNotNumber'] = $GLOBALS['strNotNumber'];

/* For server_privileges.js */
$js_messages['strHostEmpty'] = $GLOBALS['strHostEmpty'];
$js_messages['strUserEmpty'] = $GLOBALS['strUserEmpty'];
$js_messages['strPasswordEmpty'] = $GLOBALS['strPasswordEmpty'];
$js_messages['strPasswordNotSame'] = $GLOBALS['strPasswordNotSame'];

/* For inline query editing */
$js_messages['strGo'] = __('Go');
$js_messages['strCancel'] = __('Cancel');

/* Designer */
$js_messages['strModifications'] = $GLOBALS['strModifications'];
$js_messages['strRelationDeleted'] = $GLOBALS['strRelationDeleted'];
$js_messages['strForeignKeyRelationAdded'] = $GLOBALS['strForeignKeyRelationAdded'];
$js_messages['strInternalRelationAdded'] = $GLOBALS['strInternalRelationAdded'];
$js_messages['strErrorRelationAdded'] = $GLOBALS['strErrorRelationAdded'];
$js_messages['strErrorRelationExists'] = $GLOBALS['strErrorRelationExists'];
$js_messages['strErrorSaveTable'] = $GLOBALS['strErrorSaveTable'];
$js_messages['strGeneralRelationFeat:strDisabled'] = $GLOBALS['strGeneralRelationFeat'] . ': ' . $GLOBALS['strDisabled'];
$js_messages['strSelectReferencedKey'] = $GLOBALS['strSelectReferencedKey'];
$js_messages['strSelectForeignKey'] = $GLOBALS['strSelectForeignKey'];
$js_messages['strPleaseSelectPrimaryOrUniqueKey'] = $GLOBALS['strPleaseSelectPrimaryOrUniqueKey'];
$js_messages['strChangeDisplay'] = $GLOBALS['strChangeDisplay'];

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
?>
$.extend($.datepicker._defaults, $.datepicker.regional['']);
