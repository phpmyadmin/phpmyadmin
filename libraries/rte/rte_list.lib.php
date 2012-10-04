<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for generating lists of Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
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
    global $table;

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
    $retval  = "<!-- LIST OF " . PMA_RTE_getWord('docu') . " START -->\n";
    $retval .= "<fieldset>\n";
    $retval .= "    <legend>\n";
    $retval .= "        " . PMA_RTE_getWord('title') . "\n";
    $retval .= "        " . PMA_showMySQLDocu('SQL-Syntax', PMA_RTE_getWord('docu')) . "\n";
    $retval .= "    </legend>\n";
    $retval .= "    <div class='$class1' id='nothing2display'>\n";
    $retval .= "      " . PMA_RTE_getWord('nothing') . "\n";
    $retval .= "    </div>\n";
    $retval .= "    <table class='data$class2'>\n";
    $retval .= "        <!-- TABLE HEADERS -->\n";
    $retval .= "        <tr>\n";
    switch ($type) {
    case 'routine':
        $retval .= "            <th>" . __('Name') . "</th>\n";
        $retval .= "            <th colspan='4'>" . __('Action') . "</th>\n";
        $retval .= "            <th>" . __('Type') . "</th>\n";
        $retval .= "            <th>" . __('Returns') . "</th>\n";
        break;
    case 'trigger':
        $retval .= "            <th>" . __('Name') . "</th>\n";
        if (empty($table)) {
            $retval .= "            <th>" . __('Table') . "</th>\n";
        }
        $retval .= "            <th colspan='3'>" . __('Action') . "</th>\n";
        $retval .= "            <th>" . __('Time') . "</th>\n";
        $retval .= "            <th>" . __('Event') . "</th>\n";
        break;
    case 'event':
        $retval .= "            <th>" . __('Name') . "</th>\n";
        $retval .= "            <th>" . __('Status') . "</th>\n";
        $retval .= "            <th colspan='3'>" . __('Action') . "</th>\n";
        $retval .= "            <th>" . __('Type') . "</th>\n";
        break;
    default:
        break;
    }
    $retval .= "        </tr>\n";
    $retval .= "        <!-- TABLE DATA -->\n";
    $ct = 0;
    foreach ($items as $item) {
        $rowclass = ($ct % 2 == 0) ? 'odd' : 'even';
        if ($GLOBALS['is_ajax_request']) {
            $rowclass .= ' ajaxInsert hide';
        }
        // Get each row from the correct function
        switch ($type) {
        case 'routine':
            $retval .= PMA_RTN_getRowForList($item, $rowclass);
            break;
        case 'trigger':
            $retval .= PMA_TRI_getRowForList($item, $rowclass);
            break;
        case 'event':
            $retval .= PMA_EVN_getRowForList($item, $rowclass);
            break;
        default:
            break;
        }
        $ct++;
    }
    $retval .= "    </table>\n";
    $retval .= "</fieldset>\n";
    $retval .= "<!-- LIST OF " . PMA_RTE_getWord('docu') . " END -->\n";

    return $retval;
} // end PMA_RTE_getList()

/**
 * Creates the contents for a row in the list of routines
 *
 * @param    array    $routine    An array of routine data
 * @param    string   $rowclass   Empty or one of ['even'|'odd']
 *
 * @return   string   HTML code of a row for the list of routines
 */
function PMA_RTN_getRowForList($routine, $rowclass = '')
{
    global $ajax_class, $url_query, $db, $titles;

    $sql_drop = sprintf('DROP %s IF EXISTS %s',
                         $routine['ROUTINE_TYPE'],
                         PMA_backquote($routine['SPECIFIC_NAME']));
    $type_link = "item_type={$routine['ROUTINE_TYPE']}";

    $retval  = "        <tr class='noclick $rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>" . htmlspecialchars($sql_drop) . "</span>\n";
    $retval .= "                <strong>\n";
    $retval .= "                    " . htmlspecialchars($routine['SPECIFIC_NAME']) . "\n";
    $retval .= "                </strong>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if ($routine['ROUTINE_DEFINITION'] !== null
        && PMA_currentUserHasPrivilege('ALTER ROUTINE', $db)
        && PMA_currentUserHasPrivilege('CREATE ROUTINE', $db)
    ) {
        $retval .= '                <a ' . $ajax_class['edit']
                                         . ' href="db_routines.php?'
                                         . $url_query
                                         . '&amp;edit_item=1'
                                         . '&amp;item_name=' . urlencode($routine['SPECIFIC_NAME'])
                                         . '&amp;' . $type_link
                                         . '">' . $titles['Edit'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoEdit']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if ($routine['ROUTINE_DEFINITION'] !== null
        && PMA_currentUserHasPrivilege('EXECUTE', $db)
    ) {
        // Check if he routine has any input parameters. If it does,
        // we will show a dialog to get values for these parameters,
        // otherwise we can execute it directly.
        $routine_details = PMA_RTN_getDataFromName(
            $routine['SPECIFIC_NAME'],
            $routine['ROUTINE_TYPE'],
            false
        );
        if ($routine !== false) {
            $execute_action = 'execute_routine';
            for ($i=0; $i<$routine_details['item_num_params']; $i++) {
                if ($routine_details['item_type'] == 'PROCEDURE'
                    && $routine_details['item_param_dir'][$i] == 'OUT'
                ) {
                    continue;
                }
                $execute_action = 'execute_dialog';
                break;
            }
            $retval .= '                <a ' . $ajax_class['exec']
                                             . ' href="db_routines.php?'
                                             . $url_query
                                             . '&amp;' . $execute_action . '=1'
                                             . '&amp;item_name=' . urlencode($routine['SPECIFIC_NAME'])
                                             . '&amp;' . $type_link
                                             . '">' . $titles['Execute'] . "</a>\n";
        }
    } else {
        $retval .= "                {$titles['NoExecute']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= '                <a ' . $ajax_class['export']
                                     . ' href="db_routines.php?'
                                     . $url_query
                                     . '&amp;export_item=1'
                                     . '&amp;item_name=' . urlencode($routine['SPECIFIC_NAME'])
                                     . '&amp;' . $type_link
                                     . '">' . $titles['Export'] . "</a>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if (PMA_currentUserHasPrivilege('ALTER ROUTINE', $db)) {
        $retval .= '                <a ' . $ajax_class['drop']
                                         . ' href="sql.php?'
                                         . $url_query
                                         . '&amp;sql_query=' . urlencode($sql_drop)
                                         . '&amp;goto=db_routines.php' . urlencode("?db={$db}")
                                         . '" >' . $titles['Drop'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoDrop']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$routine['ROUTINE_TYPE']}\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                " . htmlspecialchars($routine['DTD_IDENTIFIER']) . "\n";
    $retval .= "            </td>\n";
    $retval .= "        </tr>\n";

    return $retval;
} // end PMA_RTN_getRowForList()

/**
 * Creates the contents for a row in the list of triggers
 *
 * @param    array    $trigger    An array of routine data
 * @param    string   $rowclass   Empty or one of ['even'|'odd']
 *
 * @return   string   HTML code of a cell for the list of triggers
 */
function PMA_TRI_getRowForList($trigger, $rowclass = '')
{
    global $ajax_class, $url_query, $db, $table, $titles;

    $retval  = "        <tr class='noclick $rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>" . htmlspecialchars($trigger['drop']) . "</span>\n";
    $retval .= "                <strong>\n";
    $retval .= "                    " . htmlspecialchars($trigger['name']) . "\n";
    $retval .= "                </strong>\n";
    $retval .= "            </td>\n";
    if (empty($table)) {
        $retval .= "            <td>\n";
        $retval .= "                <a href='db_triggers.php?db={$db}"
                                     . "&amp;table={$trigger['table']}'>"
                                     . $trigger['table'] . "</a>\n";
        $retval .= "            </td>\n";
    }
    $retval .= "            <td>\n";
    if (PMA_currentUserHasPrivilege('TRIGGER', $db, $table)) {
        $retval .= '                <a ' . $ajax_class['edit']
                                         . ' href="db_triggers.php?'
                                         . $url_query
                                         . '&amp;edit_item=1'
                                         . '&amp;item_name=' . urlencode($trigger['name'])
                                         . '">' . $titles['Edit'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoEdit']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= '                    <a ' . $ajax_class['export']
                                         . ' href="db_triggers.php?'
                                         . $url_query
                                         . '&amp;export_item=1'
                                         . '&amp;item_name=' . urlencode($trigger['name'])
                                         . '">' . $titles['Export'] . "</a>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if (PMA_currentUserHasPrivilege('TRIGGER', $db)) {
        $retval .= '                <a ' . $ajax_class['drop']
                                         . ' href="sql.php?'
                                         . $url_query
                                         . '&amp;sql_query=' . urlencode($trigger['drop'])
                                         . '&amp;goto=db_triggers.php' . urlencode("?db={$db}")
                                         . '" >' . $titles['Drop'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoDrop']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$trigger['action_timing']}\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$trigger['event_manipulation']}\n";
    $retval .= "            </td>\n";
    $retval .= "        </tr>\n";

    return $retval;
} // end PMA_TRI_getRowForList()

/**
 * Creates the contents for a row in the list of events
 *
 * @param    array    $event      An array of routine data
 * @param    string   $rowclass   Empty or one of ['even'|'odd']
 *
 * @return   string   HTML code of a cell for the list of events
 */
function PMA_EVN_getRowForList($event, $rowclass = '')
{
    global $ajax_class, $url_query, $db, $titles;

    $sql_drop = sprintf(
        'DROP EVENT IF EXISTS %s',
        PMA_backquote($event['EVENT_NAME'])
    );

    $retval  = "        <tr class='noclick $rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>" . htmlspecialchars($sql_drop) . "</span>\n";
    $retval .= "                <strong>\n";
    $retval .= "                    " . htmlspecialchars($event['EVENT_NAME']) . "\n";
    $retval .= "                </strong>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$event['STATUS']}\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if (PMA_currentUserHasPrivilege('EVENT', $db)) {
        $retval .= '                <a ' . $ajax_class['edit']
                                         . ' href="db_events.php?'
                                         . $url_query
                                         . '&amp;edit_item=1'
                                         . '&amp;item_name=' . urlencode($event['EVENT_NAME'])
                                         . '">' . $titles['Edit'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoEdit']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= '                <a ' . $ajax_class['export']
                                     . ' href="db_events.php?'
                                     . $url_query
                                     . '&amp;export_item=1'
                                     . '&amp;item_name=' . urlencode($event['EVENT_NAME'])
                                     . '">' . $titles['Export'] . "</a>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if (PMA_currentUserHasPrivilege('EVENT', $db)) {
        $retval .= '                <a ' . $ajax_class['drop']
                                         . ' href="sql.php?'
                                         . $url_query
                                         . '&amp;sql_query=' . urlencode($sql_drop)
                                         . '&amp;goto=db_events.php' . urlencode("?db={$db}")
                                         . '" >' . $titles['Drop'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoDrop']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$event['EVENT_TYPE']}\n";
    $retval .= "            </td>\n";
    $retval .= "        </tr>\n";

    return $retval;
} // end PMA_EVN_getRowForList()

?>
