<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}
/**
 * TODO: comment
 */
function PMA_toggleButton($action, $select_name, $options, $callback)
{
    // Do the logic first
    $link_on = "$action&amp;$select_name=" . urlencode($options[1]['value']);
    $link_off = "$action&amp;$select_name=" . urlencode($options[0]['value']);
    if ($options[1]['selected'] == true) {
        $state = 'on';
    } else if ($options[0]['selected'] == true) {
        $state = 'off';
    } else {
        $state = 'on';
    }
    $selected1 = '';
    $selected0 = '';
    if ($options[1]['selected'] == true) {
        $selected1 = " selected='selected'";
    } else if ($options[0]['selected'] == true) {
        $selected0 = " selected='selected'";
    }
    // Generate output
    $retval  = "<noscript>\n";
    $retval .= "<div class='wrapper'>\n";
    $retval .= "    <form action='$action' method='post'>\n";
    $retval .= "        <select name='$select_name'>\n";
    $retval .= "            <option value='{$options[1]['value']}'$selected1>";
    $retval .= "                {$options[1]['label']}\n";
    $retval .= "            </option>\n";
    $retval .= "            <option value='{$options[0]['value']}'$selected0>";
    $retval .= "                {$options[0]['label']}\n";
    $retval .= "            </option>\n";
    $retval .= "        </select>\n";
    $retval .= "        <input type='submit' value='" . __('Change') . "'/>\n";
    $retval .= "    </form>\n";
    $retval .= "</div>\n";
    $retval .= "</noscript>\n";
    $retval .= "<div class='wrapper toggleAjax hide'>\n";
    $retval .= "    <div class='toggleButton'>\n";
    $retval .= "        <div title='" . __('Click to toggle') . "' class='container $state'>\n";
    $retval .= "            <img src='{$GLOBALS['pmaThemeImage']}toggle-{$GLOBALS['text_dir']}.png'\n";
    $retval .= "                 alt='' />\n";
    $retval .= "            <table cellspacing='0' cellpadding='0'><tr>\n";
    $retval .= "                <tbody>\n";
    $retval .= "                <td class='toggleOn'>\n";
    $retval .= "                    <span class='hide'>$link_on</span>\n";
    $retval .= "                    <div>";
    $retval .= str_replace(' ', '&nbsp;', $options[1]['label']) . "</div>\n";
    $retval .= "                </td>\n";
    $retval .= "                <td><div>&nbsp;</div></td>\n";
    $retval .= "                <td class='toggleOff'>\n";
    $retval .= "                    <span class='hide'>$link_off</span>\n";
    $retval .= "                    <div>";
    $retval .= str_replace(' ', '&nbsp;', $options[0]['label']) . "</div>\n";
    $retval .= "                    </div>\n";
    $retval .= "                </tbody>\n";
    $retval .= "            </tr></table>\n";
    $retval .= "            <span class='hide callback'>$callback</span>\n";
    $retval .= "            <span class='hide text_direction'>{$GLOBALS['text_dir']}</span>\n";
    $retval .= "        </div>\n";
    $retval .= "    </div>\n";
    $retval .= "</div>\n";

    return $retval;
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
 * Display the state of the event scheduler
 * and offer an option to toggle it.
 */
$es_state = strtolower(PMA_DBI_fetch_value("SHOW GLOBAL VARIABLES LIKE 'event_scheduler'", 0, 1));
$options = array(
                0 => array(
                    'label' => __('OFF'),
                    'value' => "SET GLOBAL event_scheduler=\"OFF\"",
                    'selected' => ($es_state != 'on')
                ),
                1 => array(
                    'label' => __('ON'),
                    'value' => "SET GLOBAL event_scheduler=\"ON\"",
                    'selected' => ($es_state == 'on')
                )
           );
$event_scheduler = PMA_toggleButton(
                        "sql.php?$url_query&amp;goto=db_events.php" . urlencode("?db=$db"),
                        'sql_query',
                        $options,
                        'PMA_slidingMessage(data.sql_query);'
                    );
/**
 * Display the form for adding a new event
 * and toggling the event scheduler
 */
echo "<fieldset>\n"
   . "<div class='operations_half_width'>\n"
   . "    <a href='db_events.php?$url_query&amp;addevent=1'$conditional_class_add>\n"
   . "    " . PMA_getIcon('b_event_add.png') . __('Add a new Event') . "</a>\n"
   . "</div>\n"
   . "<div class='operations_half_width'>\n"
   . "    <div class='wrapper'>\n"
   . "        &nbsp;&nbsp;" . __('Event scheduler') . " &nbsp;&nbsp;\n"
   . "    </div>\n"
   . $event_scheduler
   . "</div>\n"
   . "</fieldset>\n";

?>
