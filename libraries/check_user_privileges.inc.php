<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Get user's global privileges and some db-specific privileges
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

use PhpMyAdmin\CheckUserPrivileges;

$checkUserPrivileges = new CheckUserPrivileges($GLOBALS['dbi']);

list($username, $hostname) = $GLOBALS['dbi']->getCurrentUserAndHost();
if ($username === '') { // MySQL is started with --skip-grant-tables
    $GLOBALS['is_create_db_priv'] = true;
    $GLOBALS['is_reload_priv'] = true;
    $GLOBALS['db_to_create'] = '';
    $GLOBALS['dbs_where_create_table_allowed'] = array('*');
    $GLOBALS['dbs_to_test'] = false;
    $GLOBALS['db_priv'] = true;
    $GLOBALS['col_priv'] = true;
    $GLOBALS['table_priv'] = true;
    $GLOBALS['proc_priv'] = true;
} else {
    $checkUserPrivileges->analyseShowGrant();
}
