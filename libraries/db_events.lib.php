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
    $retval  = "\n<!-- TOGGLE START -->\n";
    $retval .= "<noscript>\n";
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
    $retval .= "<!-- TOGGLE END -->\n";

    return $retval;
} // end PMA_toggleButton()

/**
 * Creates a fieldset for adding a new event, if the user has the privileges.
 *
 * @return   string    An HTML snippet with the link to add a new event.
 */
function PMA_EVN_getFooterLinks()
{
    global $db, $url_query, $ajax_class;
    /**
     * Create functionality for toggling the state of the event scheduler
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
    // Generate output
    $retval  = "<!-- ADD EVENT FORM START -->\n";
    $retval .= "<div class='doubleFieldset'>";
    $retval .= "    <fieldset class='left'>\n";
    $retval .= "        <legend>" . __('New');
    $retval .= PMA_showMySQLDocu('SQL-Syntax', 'CREATE_EVENT') . "</legend>\n";
    $retval .= "        <div class='wrap'>\n";
    if (PMA_currentUserHasPrivilege('EVENT', $db)) {
        $retval .= "            <a {$ajax_class['add']} href='db_events.php";
        $retval .= "?$url_query&amp;addroutine=1'>";
        $retval .= PMA_getIcon('b_event_add.png');
        $retval .= __('Add event') . "</a>\n";
    } else {
        $retval .= PMA_getIcon('b_event_add.png');
        $retval .= __('You do not have the necessary privileges to create a new routine') . "\n";
    }
    $retval .= "        </div>\n";
    $retval .= "    </fieldset>\n";
    $retval .= "    <fieldset class='right'>\n";
    $retval .= "        <legend>" . __('Event scheduler status') . '</legend>' . "\n";
    $retval .= "        <div class='wrap'>\n";
    $retval .= $event_scheduler . "\n";
    $retval .= "        </div>\n";
    $retval .= "    </fieldset>\n";
    $retval .= "    <div style='clear: both;'></div>\n";
    $retval .= "</div>";
    $retval .= "<!-- ADD EVENT FORM END -->\n\n";

    return $retval;
} // end PMA_EVN_getFooterLinks()

/**
 * TODO: comment
 */
function PMA_RTN_getRowForEventsList($event, $ct = 0, $is_ajax = false)
{
    global $titles, $db, $url_query, $ajax_class;

    // Do the logic first
    $rowclass = ($ct % 2 == 0) ? 'even' : 'odd';
    if ($is_ajax) {
        $rowclass .= ' ajaxInsert hide';
    }
    $editlink = $titles['NoEdit']; // FIXME
    $droplink = $titles['NoDrop'];
    $sql_drop = sprintf('DROP EVENT IF EXISTS %s',
                         PMA_backquote($event['EVENT_NAME']));
    $exprlink = '<a ' . $ajax_class['export'] . ' href="db_events.php?' . $url_query
                      . '&amp;exportevent=1'
                      . '&amp;eventname=' . urlencode($event['EVENT_NAME'])
                      . '">' . $titles['Export'] . '</a>';
    if (PMA_currentUserHasPrivilege('EVENT', $db)) {
        $droplink = '<a ' . $ajax_class['drop']. ' href="sql.php?' . $url_query
                          . '&amp;sql_query=' . urlencode($sql_drop)
                          . '&amp;goto=db_events.php' . urlencode("?db=$db")
                          . '" >' . $titles['Drop'] . '</a>';
    }
    // Display a row of data
    $retval  = "        <tr class='$rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>$sql_drop</span>\n";
    $retval .= "                <strong>" . htmlspecialchars($event['EVENT_NAME']) . "</strong>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>$editlink</td>\n";
    $retval .= "            <td>$exprlink</td>\n";
    $retval .= "            <td>$droplink</td>\n";
    $retval .= "            <td>{$event['EVENT_TYPE']}</td>\n";
    $retval .= "        </tr>\n";

    return $retval;
} // end PMA_RTN_getRowEventsList();

/**
 * TODO: comment
 */
function PMA_EVN_getEventsList()
{
    global $titles, $url_query, $db, $ajax_class;

    /**
     * Get the events
     */
    $columns = "`EVENT_NAME`, `EVENT_TYPE`";
    $where   = "EVENT_SCHEMA='" . PMA_sqlAddslashes($db,true) . "'";
    $events  = PMA_DBI_fetch_result("SELECT $columns FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE $where;");
    /**
     * Conditional classes switch the list on or off
     */
    $class1 = 'hide';
    $class2 = '';
    if (! $events) {
        $class1 = '';
        $class2 = ' hide';
    }
    /**
     * Generate output
     */
    $retval  = "<!-- LIST OF EVENTS START -->\n";
    $retval .= "<fieldset>\n";
    $retval .= " <legend>" . __('Events');
    $retval .= PMA_showMySQLDocu('SQL-Syntax', 'EVENTS') . "</legend>\n";
    $retval .= "    <div class='$class1' id='nothing2display'>\n";
    $retval .= "      " . __('There are no events to display.') . "\n";
    $retval .= "    </div>\n";
    $retval .= "    <table class='data$class2'>\n";
    $retval .= "        <!-- TABLE HEADERS -->\n";
    $retval .= "        <tr>\n";
    $retval .= "            <th>" . __('Name') . "</th>\n";
    $retval .= "            <th colspan='3'>" . __('Action') . "</th>\n";
    $retval .= "            <th>" . __('Type') . "</th>\n";
    $retval .= "        </tr>\n";
    $retval .= "        <!-- TABLE DATA -->\n";
    $ct=0;
    foreach ($events as $event) {
        $retval .= PMA_RTN_getRowForEventsList($event, $ct);
        $ct++;
    }
    $retval .= "</table>";
    $retval .= "</fieldset>\n";
    $retval .= "<!-- LIST OF EVENTS END -->\n";

    return $retval;
} // end PMA_EVN_getEventsList()

?>
