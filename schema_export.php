<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Schema export handler
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require 'libraries/StorageEngine.class.php';

/**
 * get all variables needed for exporting relational schema
 * in $cfgRelation
 */
$cfgRelation = PMA_getRelationsParam();

require_once 'libraries/Index.class.php';
require_once 'libraries/pmd_common.php';
require_once 'libraries/plugin_interface.lib.php';

/**
 * get all the export options and verify
 * call and include the appropriate Schema Class depending on $export_type
 * default is PDF
 */

$post_params = array(
    'db'
);
foreach ($post_params as $one_post_param) {
    if (isset($_REQUEST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_REQUEST[$one_post_param];
    }
}

PMA_processExportSchema($_REQUEST['export_type']);

/**
 * get all the export options and verify
 * call and include the appropriate Schema Class depending on $export_type
 *
 * @param string $export_type format of the export
 *
 * @return void
 */
function PMA_processExportSchema($export_type)
{
    /**
     * default is PDF, otherwise validate it's only letters a-z
     */
    if (! isset($export_type) || ! preg_match('/^[a-zA-Z]+$/', $export_type)) {
        $export_type = 'pdf';
    }

    // sanitize this parameter which will be used below in a file inclusion
    $export_type = PMA_securePath($export_type);

    // get the specific plugin
    $export_plugin = PMA_getPlugin(
        "schema",
        $export_type,
        'libraries/plugins/schema/'
    );

    // Check schema export type
    if (! isset($export_plugin)) {
        PMA_fatalError(__('Bad type!'));
    }

    $GLOBALS['dbi']->selectDb($GLOBALS['db']);
    $export_plugin->exportSchema($GLOBALS['db']);
}
?>
