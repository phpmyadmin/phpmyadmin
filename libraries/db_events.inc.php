<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$events = PMA_DBI_fetch_result('SELECT EVENT_NAME, EVENT_TYPE FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \'' . PMA_sqlAddSlashes($db,true) . '\';');

$conditional_class_add    = '';
$conditional_class_drop   = '';
$conditional_class_export = '';
if ($GLOBALS['cfg']['AjaxEnable']) {
    $conditional_class_add    = 'class="add_event_anchor"';
    $conditional_class_drop   = 'class="drop_event_anchor"';
    $conditional_class_export = 'class="export_event_anchor"';
}

/**
 * Display the export for a event. This is for when JS is disabled.
 */
if (! empty($_GET['exportevent']) && ! empty($_GET['eventname'])) {
    $event_name = htmlspecialchars(PMA_backquote($_GET['eventname']));
    if ($create_event = PMA_DBI_get_definition($db, 'EVENT', $_GET['eventname'])) {
        $create_event = '<textarea cols="40" rows="15" style="width: 100%;">' . $create_event . '</textarea>';
        if (! empty($_REQUEST['ajax_request'])) {
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
        if (! empty($_REQUEST['ajax_request'])) {
            PMA_ajaxResponse($response, false);
        } else {
            $response->display();
        }
    }
}

/**
 * Display a list of available events
 */
echo "\n\n<span id='js_query_display'></span>\n\n";
echo '<fieldset>' . "\n";
echo ' <legend>' . __('Events') . '</legend>' . "\n";
if (! $events) {
    echo __('There are no events to display.');
} else {
    echo '<div class="hide" id="nothing2display">' . __('There are no events to display.') . '</div>';
    echo '<table class="data">';
    echo sprintf('<tr>
                      <th>%s</th>
                      <th colspan="3">%s</th>
                      <th>%s</th>
                </tr>',
          __('Name'),
          __('Action'),
          __('Type'));
    $ct=0;
    $delimiter = '//';
    foreach ($events as $event) {

        // information_schema (at least in MySQL 5.1.22) does not return
        // the full CREATE EVENT statement in a way that could be useful for us
        // so we rely on PMA_DBI_get_definition() which uses SHOW CREATE EVENT

        $create_event = PMA_DBI_get_definition($db, 'EVENT', $event['EVENT_NAME']);
        $definition = 'DROP EVENT IF EXISTS ' . PMA_backquote($event['EVENT_NAME'])
                    . $delimiter . "\n" . $create_event . "\n";

        $sqlDrop = 'DROP EVENT ' . PMA_backquote($event['EVENT_NAME']);
        echo sprintf('<tr class="%s">
                          <td><span class="drop_sql" style="display:none;">%s</span><strong>%s</strong></td>
                          <td>%s</td>
                          <td><div class="create_sql" style="display: none;">%s</div>%s</td>
                          <td>%s</td>
                          <td>%s</td>
                     </tr>',
                     ($ct%2 == 0) ? 'even' : 'odd',
                     $sqlDrop,
                     $event['EVENT_NAME'],
                     ! empty($definition) ? PMA_linkOrButton('db_sql.php?' . $url_query . '&amp;sql_query=' . urlencode($definition) . '&amp;show_query=1&amp;db_query_force=1&amp;delimiter=' . urlencode($delimiter), $titles['Edit']) : '&nbsp;',
                     $create_event,
                     '<a ' . $conditional_class_export . ' href="db_events.php?' . $url_query
                           . '&amp;exportevent=1'
                           . '&amp;eventname=' . urlencode($event['EVENT_NAME'])
                           . '">' . $titles['Export'] . '</a>',
                     '<a ' . $conditional_class_drop . ' href="sql.php?' . $url_query . '&amp;sql_query=' . urlencode($sqlDrop) . '" >' . $titles['Drop'] . '</a>',
                     $event['EVENT_TYPE']);
        $ct++;
    }
    echo '</table>';
}
echo '</fieldset>' . "\n";

/**
 * If there has been a request to change the state
 * of the event scheduler, process it now.
 */
if (! empty($_GET['toggle_scheduler'])) {
    $new_scheduler_state = $_GET['toggle_scheduler'];
    if ($new_scheduler_state === 'ON' || $new_scheduler_state === 'OFF') {
        PMA_DBI_query("SET GLOBAL event_scheduler='$new_scheduler_state'");
    }
}

/**
 * Prepare to show the event scheduler fieldset, if necessary
 */
$tableStart = '';
$schedulerFieldset = '';
$es_state = PMA_DBI_fetch_value("SHOW GLOBAL VARIABLES LIKE 'event_scheduler'", 0, 1);
if ($es_state === 'ON' || $es_state === 'OFF') {
    $es_change = ($es_state == 'ON') ? 'OFF' : 'ON';
    $tableStart = '<table style="width: 100%;"><tr><td style="width: 50%;">';
    $schedulerFieldset = '</td><td><fieldset style="margin: 1em 0;">' . "\n"
       . PMA_getIcon('b_events.png')
       . ($es_state === 'ON' ? __('The event scheduler is enabled') : __('The event scheduler is disabled')) . ':'
       . '    <a href="db_events.php?' . $url_query . '&amp;toggle_scheduler=' . $es_change . '">'
       . ($es_change === 'ON' ? __('Turn it on') : __('Turn it off'))
       .  '</a>' . "\n"
       . '</fieldset></td></tr></table>' . "\n";
}

/**
 * Display the form for adding a new event
 */
echo $tableStart . '<fieldset style="margin: 1em 0;">' . "\n"
   . '    <a href="db_events.php?' . $url_query . '&amp;addevent=1" ' . $conditional_class_add . '>' . "\n"
   . PMA_getIcon('b_event_add.png') . __('Add an event') . '</a>' . "\n"
   . '</fieldset>' . "\n";

/**
 * Display the state of the event scheduler
 * and offer an option to toggle it.
 */
echo $schedulerFieldset;

?>
