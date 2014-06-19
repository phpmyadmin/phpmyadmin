<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Save handler for PMD
 *
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once 'libraries/pmd_common.php';

$cfgRelation = PMA_getRelationsParam();

if (! $cfgRelation['pdfwork']) {
    PMD_errorSave();
}

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'die_save_pos',
);

foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

PMA_saveTablePositions($_REQUEST['selected_page']);

/**
 * Error handler
 *
 * @return void
 */
function PMD_errorSave()
{
    global $die_save_pos; // if this file included
    if (! empty($die_save_pos)) {
        header("Content-Type: text/xml; charset=utf-8");
        header("Cache-Control: no-cache");
        die(
            '<root act="save_pos" return="'
            . __('Error saving coordinates for Designer.')
            . '"></root>'
        );
    }
}

if (! empty($die_save_pos)) {
    header("Content-Type: text/xml; charset=utf-8");
    header("Cache-Control: no-cache");
    echo '<root act="save_pos" return="'
        . __('Modifications have been saved') . '"></root>';
}
?>
