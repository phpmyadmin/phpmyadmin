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

echo "var PMA_messages = new Array();\n";
foreach ($js_messages as $name => $js_message) {
    echo "PMA_messages['" . $name . "'] = '" . PMA_escapeJsString($js_message) . "';\n";
}
?>
