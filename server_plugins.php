<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server plugin page
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * JS includes
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('server_plugins.js');

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';
require 'libraries/server_plugins.lib.php';

/**
 * Prepare plugin list
 */
$sql = "SELECT p.plugin_name, p.plugin_type, p.is_active, m.module_name,
        m.module_library, m.module_version, m.module_author,
        m.module_description, m.module_license
    FROM data_dictionary.plugins p
        JOIN data_dictionary.modules m USING (module_name)
    ORDER BY m.module_name, p.plugin_type, p.plugin_name";
$res = $GLOBALS['dbi']->query($sql);
$plugins = array();
$modules = array();
while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
    $plugins[$row['plugin_type']][] = $row;
    $modules[$row['module_name']]['info'] = $row;
    $modules[$row['module_name']]['plugins'][$row['plugin_type']][] = $row;
}
$GLOBALS['dbi']->freeResult($res);

// sort plugin list (modules are already sorted)
ksort($plugins);

/**
 * Displays the page
 */
$response->addHTML(PMA_getHtmlForSubPageHeader('plugins'));
$response->addHTML(PMA_getPluginAndModuleInfo($plugins, $modules));

exit;

?>
