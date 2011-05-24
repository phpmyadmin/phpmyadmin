<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$events = PMA_DBI_fetch_result('SELECT EVENT_NAME, EVENT_TYPE FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \'' . PMA_sqlAddslashes($db,true) . '\';');

$conditional_class_add    = '';
$conditional_class_drop   = '';
$conditional_class_export = '';
if ($GLOBALS['cfg']['AjaxEnable']) {
    $conditional_class_add    = 'class="add_event_anchor"';
    $conditional_class_drop   = 'class="drop_event_anchor"';
    $conditional_class_export = 'class="export_event_anchor"';
}

echo '<fieldset>' . "\n";
echo ' <legend>' . __('Events') . '</legend>' . "\n";
if (! $events) {
    echo __('There are no events to display.');
} else {
    echo '<div style="display: none;" id="no_events">' . __('There are no events to display.') . '</div>';
    echo '<table class="data" id="event_list">';
    echo sprintf('<tr>
                      <th>%s</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>%s</th>
                </tr>',
          __('Name'),
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
                     '<a ' . $conditional_class_export . ' href="#" >' . $titles['Export'] . '</a>',
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
    $tableStart = '<table style="width: 100%"><tr><td style="width: 50%;">';
    $schedulerFieldset = '</td><td><fieldset>' . "\n"
       . PMA_getIcon('b_events.png') . __('The event scheduler is ') . $es_state . ':'
       . '    <a href="db_events.php?' . $GLOBALS['url_query'] . '&amp;toggle_scheduler=' . $es_change . '">'
       . __('Turn') . " $es_change\n" .  '</a>' . "\n"
       . '</fieldset></td></tr></table>' . "\n";
}

/**
 * Display the form for adding a new event
 */
echo $tableStart . '<fieldset>' . "\n"
   . '    <a href="db_events.php?' . $GLOBALS['url_query'] . '&amp;addevent=1" ' . $conditional_class_add . '>' . "\n"
   . PMA_getIcon('b_event_add.png') . __('Add a new Event') . '</a>' . "\n"
   . '</fieldset>' . "\n";

/**
 * Display the state of the event scheduler
 * and offer an option to toggle it.
 */
echo $schedulerFieldset;

?>
