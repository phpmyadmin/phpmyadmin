<?php

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/common.lib.php';
require_once './libraries/db_events.lib.php';

$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'db_events.js';

/**
 * Create labels for the list
 */
$titles = PMA_buildActionTitles();

if ($GLOBALS['is_ajax_request'] != true) {
    /**
     * Displays the header
     */
    require_once './libraries/db_common.inc.php';
    /**
     * Displays the tabs
     */
    require_once './libraries/db_info.inc.php';
} else {
    if (strlen($db)) {
        PMA_DBI_select_db($db);
        if (! isset($url_query)) {
            $url_query = PMA_generate_common_url($db);
        }
    }
}

/**
 * Process all requests
 */

/**
 * Generate the conditional classes that will be used to attach jQuery events to links.
 */
$ajax_class = array(
                  'add'    => '',
                  'edit'   => '',
                  'exec'   => '',
                  'drop'   => '',
                  'export' => ''
              );
if ($GLOBALS['cfg']['AjaxEnable']) {
    $ajax_class['add']    = 'class="add_event_anchor"';
    $ajax_class['edit']   = 'class="edit_event_anchor"';
    $ajax_class['exec']   = 'class="exec_event_anchor"';
    $ajax_class['drop']   = 'class="drop_event_anchor"';
    $ajax_class['export'] = 'class="export_event_anchor"';
}

if (! empty($_GET['exportevent']) && ! empty($_GET['eventname'])) {
    /**
     * Display the export for an event.
     */
    $event_name = htmlspecialchars(PMA_backquote($_GET['eventname']));
    if ($create_event = PMA_DBI_get_definition($db, 'EVENT', $_GET['eventname'])) {
        $create_event = '<textarea cols="40" rows="15" style="width: 100%;">' . htmlspecialchars($create_event) . '</textarea>';
        if ($GLOBALS['is_ajax_request'] == true) {
            $extra_data = array('title' => sprintf(__('Export of event %s'), $event_name));
            PMA_ajaxResponse($create_event, true, $extra_data);
        } else {
            echo '<fieldset>' . "\n"
               . ' <legend>' . sprintf(__('Export of event "%s"'), $event_name) . '</legend>' . "\n"
               . $create_event
               . '</fieldset>';
        }
    } else {
        $response = __('Error in Processing Request') . ' : '
                  . sprintf(__('No event with name %s found in database %s'),
                            $event_name, htmlspecialchars(PMA_backquote($db)));
        $response = PMA_message::error($response);
        if ($GLOBALS['is_ajax_request'] == true) {
            PMA_ajaxResponse($response, false);
        } else {
            $response->display();
        }
    }
}

/**
 * Display a list of available events
 */
echo PMA_EVN_getEventsList();

/**
 * Display a link for adding a new event, if
 * the user has the privileges and a link to
 * toggle the state of the vent scheduler.
 */
echo PMA_EVN_getFooterLinks();

/**
 * Display the footer, if necessary
 */
if ($GLOBALS['is_ajax_request'] != true) {
    require './libraries/footer.inc.php';
}

?>
