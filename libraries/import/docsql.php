<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * DocSQL import plugin for phpMyAdmin
 *
 * @version $Id$
 * @package phpMyAdmin-Import
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Load relations.
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/**
 * We need relations enabled and we work only on database
 */
if ($plugin_param !== 'database' || $GLOBALS['num_tables'] < 1
 || ! $cfgRelation['relwork'] || ! $cfgRelation['commwork']) {
    return;
}

if (isset($plugin_list)) {
    $plugin_list['docsql'] = array(           // set name of your plugin
        'text' => 'strDocSQL',                // text to be displayed as choice
        'extension' => '',                  // extension this plugin can handle
        'options' => array(                 // array of options for your plugin (optional)
            array('type' => 'text', 'name' => 'table', 'text' => 'strTableName'),
        ),
        'options_text' => 'strOptions', // text to describe plugin options (must be set if options are used)
        );
    /* We do not define function when plugin is just queried for information above */
    return;
}

$tab = $_POST['docsql_table'];
$buffer = '';
/* Read whole buffer, we except it is small enough */
while (!$finished && !$error && !$timeout_passed) {
    $data = PMA_importGetNextChunk();
    if ($data === FALSE) {
        // subtract data we didn't handle yet and stop processing
        break;
    } elseif ($data === TRUE) {
        // nothing to read
        break;
    } else {
        // Append new data to buffer
        $buffer .= $data;
    }
} // End of import loop
/* Process the data */
if ($data === TRUE && !$error && !$timeout_passed) {
    $buffer = str_replace("\r\n", "\n", $buffer);
    $buffer = str_replace("\r", "\n", $buffer);
    $lines = explode("\n", $buffer);
    foreach ($lines AS $lkey => $line) {
        //echo '<p>' . $line . '</p>';
        $inf     = explode('|', $line);
        if (!empty($inf[1]) && strlen(trim($inf[1])) > 0) {
            $qry = '
                 INSERT INTO
                        ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
                      (db_name, table_name, column_name, ' . PMA_backquote('comment') . ')
                 VALUES (
                        \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\',
                        \'' . PMA_sqlAddslashes(trim($tab)) . '\',
                        \'' . PMA_sqlAddslashes(trim($inf[0])) . '\',
                        \'' . PMA_sqlAddslashes(trim($inf[1])) . '\')';
            PMA_importRunQuery($qry, $qry . '-- ' . htmlspecialchars($tab) . '.' . htmlspecialchars($inf[0]), true);
        } // end inf[1] exists
        if (!empty($inf[2]) && strlen(trim($inf[2])) > 0) {
            $for = explode('->', $inf[2]);
            $qry = '
                 INSERT INTO
                        ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation']) . '
                      (master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)
                 VALUES (
                        \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\',
                        \'' . PMA_sqlAddslashes(trim($tab)) . '\',
                        \'' . PMA_sqlAddslashes(trim($inf[0])) . '\',
                        \'' . PMA_sqlAddslashes($GLOBALS['db']) . '\',
                        \'' . PMA_sqlAddslashes(trim($for[0])) . '\',
                        \'' . PMA_sqlAddslashes(trim($for[1])) . '\')';
            PMA_importRunQuery($qry, $qry . '-- ' . htmlspecialchars($tab) . '.' . htmlspecialchars($inf[0]) . '(' . htmlspecialchars($inf[2]) . ')', true);
        } // end inf[2] exists
    } // End lines loop
} // End import
// Commit any possible data in buffers
PMA_importRunQuery();
?>
