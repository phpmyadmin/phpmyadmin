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
    $conditional_class_add    = '';
    $conditional_class_drop   = '';
    $conditional_class_export = '';
    if ($GLOBALS['cfg']['AjaxEnable']) {
        $conditional_class_add    = 'class="add_event_anchor"';
        $conditional_class_drop   = 'class="drop_event_anchor"';
        $conditional_class_export = 'class="export_event_anchor"';
    }
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
 * Display the form for adding a new event
 */
echo '<fieldset>' . "\n"
   . '    <a href="db_events.php?' . $GLOBALS['url_query'] . '&amp;addevent=1" class="' . $conditional_class_add . '">' . "\n"
   . PMA_getIcon('b_event_add.png') . __('Add a new Event') . '</a>' . "\n"
   . '</fieldset>' . "\n";

?>
