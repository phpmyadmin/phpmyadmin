<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * If coming from a Show MySQL link on the home page,
 * put something in $sub_part
 */
if (empty($sub_part)) {
    $sub_part = '_structure';
}

/**
 * Checks for superuser privileges
 */
$is_superuser = PMA_isSuperuser();

/**
 * Prepares links
 */

/**
 * export, search and qbe links if there is at least one table
 */
if ($num_tables == 0) {
    $tab_qbe['warning'] = __('Database seems to be empty!');
    $tab_search['warning'] = __('Database seems to be empty!');
    $tab_export['warning'] = __('Database seems to be empty!');
}

$tab_structure['link']  = 'db_structure.php';
$tab_structure['text']  = __('Structure');
$tab_structure['icon']  = 'b_props.png';

$tab_sql['link']        = 'db_sql.php';
$tab_sql['args']['db_query_force'] = 1;
$tab_sql['text']        = __('SQL');
$tab_sql['icon']        = 'b_sql.png';

$tab_export['text']     = __('Export');
$tab_export['icon']     = 'b_export.png';
$tab_export['link']     = 'db_export.php';

$tab_search['text']     = __('Search');
$tab_search['icon']     = 'b_search.png';
$tab_search['link']     = 'db_search.php';

if (PMA_Tracker::isActive()) {
    $tab_tracking['text'] = __('Tracking');
    $tab_tracking['icon'] = 'eye.png';
    $tab_tracking['link'] = 'db_tracking.php';
}

$tab_qbe['text']        = __('Query');
$tab_qbe['icon']        = 's_db.png';
$tab_qbe['link']        = 'db_qbe.php';

if ($cfgRelation['designerwork']) {
    $tab_designer['text']   = __('Designer');
    $tab_designer['icon']   = 'b_relations.png';
    $tab_designer['link']   = 'pmd_general.php';
}

if (! $db_is_information_schema) {
    $tab_import['link']     = 'db_import.php';
    $tab_import['text']     = __('Import');
    $tab_import['icon']     = 'b_import.png';
    $tab_operation['link']  = 'db_operations.php';
    $tab_operation['text']  = __('Operations');
    $tab_operation['icon']  = 'b_tblops.png';
    if ($is_superuser && !PMA_DRIZZLE) {
        $tab_privileges['link'] = 'server_privileges.php';
        $tab_privileges['args']['checkprivs']       = $db;
        // stay on database view
        $tab_privileges['args']['viewing_mode'] = 'db';
        $tab_privileges['text'] = __('Privileges');
        $tab_privileges['icon'] = 's_rights.png';
    }
    $tab_routines['link']   = 'db_routines.php';
    $tab_routines['text']   = __('Routines');
    $tab_routines['icon']   = 'b_routines.png';

    $tab_events['link']     = 'db_events.php';
    $tab_events['text']     = __('Events');
    $tab_events['icon']     = 'b_events.png';

    $tab_triggers['link']   = 'db_triggers.php';
    $tab_triggers['text']   = __('Triggers');
    $tab_triggers['icon']   = 'b_triggers.png';
}

/**
 * Displays tab links
 */
$tabs = array();
$tabs[] =& $tab_structure;
$tabs[] =& $tab_sql;
$tabs[] =& $tab_search;
$tabs[] =& $tab_qbe;
$tabs[] =& $tab_export;
if (! $db_is_information_schema) {
    $tabs[] =& $tab_import;
    $tabs[] =& $tab_operation;
    if ($is_superuser && !PMA_DRIZZLE) {
        $tabs[] =& $tab_privileges;
    }
    if (!PMA_DRIZZLE) {
        $tabs[] =& $tab_routines;
    }
    if (PMA_MYSQL_INT_VERSION >= 50106 && ! PMA_DRIZZLE) {
        if (PMA_currentUserHasPrivilege('EVENT', $db)) {
            $tabs[] =& $tab_events;
        }
    }
    if (!PMA_DRIZZLE) {
        if (PMA_currentUserHasPrivilege('TRIGGER', $db)) {
            $tabs[] =& $tab_triggers;
        }
    }
}
if (PMA_Tracker::isActive()) {
    $tabs[] =& $tab_tracking;
}
if (! $db_is_information_schema) {
    if ($cfgRelation['designerwork']) {
        $tabs[] =& $tab_designer;
    }
}

$url_params['db'] = $db;

echo PMA_generate_html_tabs($tabs, $url_params);
unset($tabs);

/**
 * Displays a message
 */
if (!empty($message)) {
    PMA_showMessage($message);
    unset($message);
}
?>
