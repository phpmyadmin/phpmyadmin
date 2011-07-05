<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Creates a list of items containing the relevant
 * information and some action links.
 *
 * @param    string   $type    One of ['routine'|'trigger'|'event']
 * @param    array    $items   An array of items
 *
 * @return   string   HTML code of the list of items
 */
function PMA_RTE_getList($type, $items)
{
    global $header_arr;

    /**
     * Conditional classes switch the list on or off
     */
    $class1 = 'hide';
    $class2 = '';
    if (! $items) {
        $class1 = '';
        $class2 = ' hide';
    }
    /**
     * Generate output
     */
    $retval  = "<!-- LIST OF {$header_arr['docu']} -->\n";
    $retval .= "<fieldset>\n";
    $retval .= "    <legend>" . $header_arr['title'];
    $retval .= PMA_showMySQLDocu('SQL-Syntax', $header_arr['docu']) . "</legend>\n";
    $retval .= "    <div class='$class1' id='nothing2display'>\n";
    $retval .= "      " . $header_arr['nothing'] . "\n";
    $retval .= "    </div>\n";
    $retval .= "    <table class='data$class2'>\n";
    $retval .= "        <!-- TABLE HEADERS -->\n";
    $retval .= "        <tr>\n";
    foreach ($header_arr['cols'] as $key => $value) {
        if (isset($value['label'])) {
            $retval .= "            <th colspan='{$value['colspan']}'>{$value['label']}</th>\n";
        }
    }
    $retval .= "        </tr>\n";
    $retval .= "        <!-- TABLE DATA -->\n";
    $ct=0;
    foreach ($items as $item) {
        // Display each row
        $retval .= PMA_RTE_getRowForList($type, $item, $ct);
        $ct++;
    }
    $retval .= "    </table>\n";
    $retval .= "</fieldset>\n";
    $retval .= "<!-- LIST OF {$header_arr['docu']} END -->\n";

    return $retval;
} // end PMA_RTE_getList()

/**
 * Creates a row for a list of items
 *
 * @param    string   $type   One of ['routine'|'trigger'|'event']
 * @param    array    $item   An array with data about an item
 * @param    int      $ct     Row count
 *
 * @return   string   HTML code of the list of items
 */
function PMA_RTE_getRowForList($type, $item, $ct = 0)
{
    global $header_arr;

    $rowclass = ($ct % 2 == 0) ? 'even' : 'odd';
    if ($GLOBALS['is_ajax_request']) {
        $rowclass .= ' ajaxInsert hide';
    }
    $retval  = "        <tr class='$rowclass'>\n";
    foreach ($header_arr['cols'] as $key => $value) {
        // Get each cell from the correct function
        switch ($type) {
        case 'routine':
            $retval .= "            " . PMA_RTN_getCellForList($value['field'], $item);
            break;
        case 'trigger':
            $retval .= "            " . PMA_TRI_getCellForList($value['field'], $item);
            break;
        case 'event':
            $retval .= "            " . PMA_EVN_getCellForList($value['field'], $item);
            break;
        default:
            break;
        }
    }
    $retval .= "        </tr>\n";
    return $retval;
} // end PMA_RTE_getRowForList()

/**
 * Creates the contents for a cell in the list of routines
 *
 * @param    string   $field     What kind of cell to return
 * @param    array    $routine   An array of routine data
 *
 * @return   string   HTML code of a cell for the list of routines
 */
function PMA_RTN_getCellForList($field, $routine)
{
    global $ajax_class, $url_query, $db, $titles;

    $sql_drop = sprintf('DROP %s IF EXISTS %s',
                         $routine['ROUTINE_TYPE'],
                         PMA_backquote($routine['SPECIFIC_NAME']));
    switch ($field) {
    case 'name':
        $retval  = "<span class='drop_sql hide'>$sql_drop</span>"
                 . "<strong>" . htmlspecialchars($routine['SPECIFIC_NAME']) . "</strong>";
        break;
    case 'edit':
        $retval = $titles['NoEdit'];
        if ($routine['ROUTINE_DEFINITION'] !== NULL
            && PMA_currentUserHasPrivilege('ALTER ROUTINE', $db)
            && PMA_currentUserHasPrivilege('CREATE ROUTINE', $db)) {
            $retval = '<a ' . $ajax_class['edit'] . ' href="db_routines.php?' . $url_query
                            . '&amp;edit_item=1'
                            . '&amp;item_name=' . urlencode($routine['SPECIFIC_NAME'])
                            . '">' . $titles['Edit'] . '</a>';
        }
        break;
    case 'execute':
        if ($routine['ROUTINE_DEFINITION'] !== NULL && PMA_currentUserHasPrivilege('EXECUTE', $db)) {
            // Check if he routine has any input parameters. If it does,
            // we will show a dialog to get values for these parameters,
            // otherwise we can execute it directly.
            $routine_details = PMA_RTN_getRoutineDataFromName($routine['SPECIFIC_NAME'], false);
            if ($routine !== false) {
                $execute_action = 'execute_routine';
                for ($i=0; $i<$routine_details['num_params']; $i++) {
                    if ($routine_details['type'] == 'PROCEDURE' && $routine_details['param_dir'][$i] == 'OUT') {
                        continue;
                    }
                    $execute_action = 'execute_dialog';
                    break;
                }
                $retval = '<a ' . $ajax_class['exec']. ' href="db_routines.php?' . $url_query
                                . '&amp;' . $execute_action . '=1'
                                . '&amp;item_name=' . urlencode($routine['SPECIFIC_NAME'])
                                . '">' . $titles['Execute'] . '</a>';
            }
        }
        break;
    case 'export':
        $retval = '<a ' . $ajax_class['export'] . ' href="db_routines.php?' . $url_query
                . '&amp;export_item=1'
                . '&amp;item_name=' . urlencode($routine['SPECIFIC_NAME'])
                . '">' . $titles['Export'] . '</a>';
        break;
    case 'drop':
        $retval = $titles['NoDrop'];
        if (PMA_currentUserHasPrivilege('EVENT', $db)) {
            $retval = '<a ' . $ajax_class['drop']. ' href="sql.php?' . $url_query
                    . '&amp;sql_query=' . urlencode($sql_drop)
                    . '&amp;goto=db_events.php' . urlencode("?db={$db}")
                    . '" >' . $titles['Drop'] . "</a>";
        }
        break;
    case 'type':
        $retval = $routine['ROUTINE_TYPE'];
        break;
    case 'returns':
        $retval = htmlspecialchars($routine['DTD_IDENTIFIER']);
        break;
    default:
        return '';
    }
    return "<td>" . $retval . "</td>\n";
} // end PMA_RTN_getCellForList()

/**
 * Creates the contents for a cell in the list of triggers
 *
 * @param    string   $field     What kind of cell to return
 * @param    array    $trigger   An array of trigger data
 *
 * @return   string   HTML code of a cell for the list of triggers
 */
function PMA_TRI_getCellForList($field, $trigger)
{
    global $ajax_class, $url_query, $db, $table, $titles;

    switch ($field) {
    case 'name':
        $retval  = "<span class='drop_sql hide'>{$trigger['drop']}</span>"
                 . "<strong>" . htmlspecialchars($trigger['name']) . "</strong>";
        break;
    case 'table':
        $retval  = "<a href='db_triggers.php?db={$db}&amp;table={$trigger['table']}'>"
                 . $trigger['table'] . "</a>";
        break;
    case 'edit':
        $retval = $titles['NoEdit'];
        if (PMA_currentUserHasPrivilege('TRIGGER', $db, $table)) {
            $retval = '<a ' . $ajax_class['edit'] . ' href="db_triggers.php?' . $url_query
                            . '&amp;edit_item=1'
                            . '&amp;item_name=' . urlencode($trigger['name'])
                            . '">' . $titles['Edit'] . '</a>';
        }
        break;
    case 'export':
        $retval = '<a ' . $ajax_class['export'] . ' href="db_triggers.php?' . $url_query
                . '&amp;export_item=1'
                . '&amp;item_name=' . urlencode($trigger['name'])
                . '">' . $titles['Export'] . "</a>";
        break;
    case 'drop':
        $retval = $titles['NoDrop'];
        if (PMA_currentUserHasPrivilege('TRIGGER', $db)) {
            $retval = '<a ' . $ajax_class['drop']. ' href="sql.php?' . $url_query
                    . '&amp;sql_query=' . urlencode($trigger['drop'])
                    . '&amp;goto=db_triggers.php' . urlencode("?db={$db}")
                    . '" >' . $titles['Drop'] . "</a>";
        }
        break;
    case 'time':
        $retval = $trigger['action_timing'];
        break;
    case 'event':
        $retval = $trigger['event_manipulation'];
        break;
    default:
        $retval = '';
        break;
    }
    return "<td>" . $retval . "</td>\n";
} // end PMA_TRI_getCellForList()

/**
 * Creates the contents for a cell in the list of events
 *
 * @param    string   $field   What kind of cell to return
 * @param    array    $event   An array of routine data
 *
 * @return   string   HTML code of a cell for the list of events
 */
function PMA_EVN_getCellForList($field, $event)
{
    global $ajax_class, $url_query, $db, $titles;

    $sql_drop = sprintf('DROP EVENT IF EXISTS %s',
                         PMA_backquote($event['EVENT_NAME']));
    switch ($field) {
    case 'name':
        $retval  = "<span class='drop_sql hide'>$sql_drop</span>"
                 . "<strong>" . htmlspecialchars($event['EVENT_NAME']) . "</strong>";
        break;
    case 'status':
        $retval = $event['STATUS'];
        break;
    case 'edit':
        $retval = $titles['NoEdit'];
        if (PMA_currentUserHasPrivilege('EVENT', $db)) {
            $retval = '<a ' . $ajax_class['edit'] . ' href="db_events.php?' . $url_query
                            . '&amp;edit_item=1'
                            . '&amp;item_name=' . urlencode($event['EVENT_NAME'])
                            . '">' . $titles['Edit'] . '</a>';
        }
        break;
    case 'export':
        $retval = '<a ' . $ajax_class['export'] . ' href="db_events.php?' . $url_query
                . '&amp;export_item=1'
                . '&amp;item_name=' . urlencode($event['EVENT_NAME'])
                . '">' . $titles['Export'] . '</a>';
        break;
    case 'drop':
        $retval = $titles['NoDrop'];
        if (PMA_currentUserHasPrivilege('EVENT', $db)) {
            $retval = '<a ' . $ajax_class['drop']. ' href="sql.php?' . $url_query
                    . '&amp;sql_query=' . urlencode($sql_drop)
                    . '&amp;goto=db_events.php' . urlencode("?db={$db}")
                    . '" >' . $titles['Drop'] . "</a>";
        }
        break;
    case 'type':
        $retval = $event['EVENT_TYPE'];
        break;
    default:
        $retval = '';
        break;
    }

    return "<td>" . $retval . "</td>\n";
} // end PMA_EVN_getCellForList()

?>
