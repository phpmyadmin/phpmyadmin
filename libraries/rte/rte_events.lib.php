<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Main function for the events functionality
 */
function PMA_EVN_main()
{
    global $db, $header_arr, $human_name;

    /**
     * Here we define some data that will be used to create the list events
     */
    $human_name = __('event');
    $columns    = "`EVENT_NAME`, `EVENT_TYPE`, `STATUS`";
    $where      = "EVENT_SCHEMA='" . PMA_sqlAddSlashes($db) . "'";
    $items      = PMA_DBI_fetch_result("SELECT $columns FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE $where ORDER BY `EVENT_NAME` ASC;");
    $cols       = array(array('label'   => __('Name'),   'colspan' => 1, 'field'   => 'name'),
                        array('label'   => __('Status'), 'colspan' => 1, 'field'   => 'status'),
                        array('label'   => __('Action'), 'colspan' => 3, 'field'   => 'edit'),
                        array(                                           'field'   => 'export'),
                        array(                                           'field'   => 'drop'),
                        array('label'   => __('Type'),   'colspan' => 1, 'field'   => 'type'));
    $header_arr = array('title'   => __('Events'),
                        'docu'    => 'EVENTS',
                        'nothing' => __('There are no events to display.'),
                        'cols'    => $cols);
    /**
     * Process all requests
     */
    PMA_EVN_handleEditor();
    PMA_EVN_handleExport();
    /**
     * Display a list of available events
     */
    echo PMA_RTE_getList('event', $items);
    /**
     * Display a link for adding a new event, if
     * the user has the privileges and a link to
     * toggle the state of the vent scheduler.
     */
    echo PMA_EVN_getFooterLinks();
} // end PMA_EVN_main()

function PMA_EVN_handleEditor()
{
    global $_REQUEST, $_POST, $errors, $db, $table;

    if (! empty($_REQUEST['editor_process_add']) || ! empty($_REQUEST['editor_process_edit'])) {
        $sql_query = '';

        $item_query = PMA_EVN_getQueryFromRequest();

        if (! count($errors)) { // set by PMA_RTN_getQueryFromRequest()
            // Execute the created query
            if (! empty($_REQUEST['editor_process_edit'])) {
                // Backup the old trigger, in case something goes wrong
                $create_item = PMA_DBI_get_definition($db, 'EVENT', $_REQUEST['item_original_name']);
                $drop_item = "DROP EVENT " . PMA_backquote($_REQUEST['item_original_name']) . ";\n";
                $result = PMA_DBI_try_query($drop_item);
                if (! $result) {
                    $errors[] = sprintf(__('The following query has failed: "%s"'), $drop_item) . '<br />'
                                      . __('MySQL said: ') . PMA_DBI_getError(null);
                } else {
                    $result = PMA_DBI_try_query($item_query);
                    if (! $result) {
                        $errors[] = sprintf(__('The following query has failed: "%s"'), $item_query) . '<br />'
                                          . __('MySQL said: ') . PMA_DBI_getError(null);
                        // We dropped the old item, but were unable to create the new one
                        // Try to restore the backup query
                        $result = PMA_DBI_try_query($create_item);
                        if (! $result) {
                            // OMG, this is really bad! We dropped the query, failed to create a new one
                            // and now even the backup query does not execute!
                            // This should not happen, but we better handle this just in case.
                            $errors[] = __('Sorry, we failed to restore the dropped event.') . '<br />'
                                              . __('The backed up query was:') . "\"$create_item\"" . '<br />'
                                              . __('MySQL said: ') . PMA_DBI_getError(null);
                        }
                    } else {
                        $message = PMA_Message::success(__('Event %1$s has been modified.'));
                        $message->addParam(PMA_backquote($_REQUEST['item_name']));
                        $sql_query = $drop_item . $item_query;
                    }
                }
            } else {
                // 'Add a new item' mode
                $result = PMA_DBI_try_query($item_query);
                if (! $result) {
                    $errors[] = sprintf(__('The following query has failed: "%s"'), $item_query) . '<br /><br />'
                                      . __('MySQL said: ') . PMA_DBI_getError(null);
                } else {
                    $message = PMA_Message::success(__('Event %1$s has been created.'));
                    $message->addParam(PMA_backquote($_REQUEST['item_name']));
                    $sql_query = $item_query;
                }
            }
        }

        if (count($errors)) {
            $message = PMA_Message::error(__('<b>One or more errors have occured while processing your request:</b>'));
            $message->addString('<ul>');
            foreach ($errors as $num => $string) {
                $message->addString('<li>' . $string . '</li>');
            }
            $message->addString('</ul>');
        }

        $output = PMA_showMessage($message, $sql_query);
        if ($GLOBALS['is_ajax_request']) {
            $extra_data = array();
            if ($message->isSuccess()) {
                $columns = "`EVENT_NAME`, `EVENT_TYPE`, `STATUS`";
                $where   = "EVENT_SCHEMA='" . PMA_sqlAddSlashes($db) . "' "
                         . "AND EVENT_NAME='" . PMA_sqlAddSlashes($_REQUEST['item_name']) . "'";
                $query   = "SELECT $columns FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE $where;";
                $event   = PMA_DBI_fetch_single_row($query);
                $extra_data['name'] = htmlspecialchars(strtoupper($_REQUEST['item_name']));
                $extra_data['new_row'] = PMA_RTE_getRowForList('event', $event, 0);
                $extra_data['insert'] = ! empty($event);
                $response = $output;
            } else {
                $response = $message;
            }
            PMA_ajaxResponse($response, $message->isSuccess(), $extra_data);
        }
    }
    /**
     * Display a form used to add/edit a trigger, if necessary
     */
    if (count($errors) || ( empty($_REQUEST['editor_process_add']) && empty($_REQUEST['editor_process_edit']) &&
              (! empty($_REQUEST['add_item']) || ! empty($_REQUEST['edit_item'])
            || ! empty($_REQUEST['item_changetype'])))) { // FIXME: this must be simpler than that
        $operation = '';
        if (! empty($_REQUEST['item_changetype'])) {
            $operation = 'change';
        }
        // Get the data for the form (if any)
        if (! empty($_REQUEST['add_item'])) {
            $title = __("Create event");
            $item = PMA_EVN_getDataFromRequest();
            $mode = 'add';
        } else if (! empty($_REQUEST['edit_item'])) {
            $title = __("Edit event");
            if (! empty($_REQUEST['item_name']) && empty($_REQUEST['editor_process_edit']) && empty($_REQUEST['item_changetype'])) {
                $item = PMA_EVN_getDataFromName($_REQUEST['item_name']);
                if ($item !== false) {
                    $item['original_name'] = $item['name'];
                }
            } else {
                $item = PMA_EVN_getDataFromRequest();
            }
            $mode = 'edit';
        }
        if ($item !== false) {
            // Show form
            $editor = PMA_EVN_getEditorForm($mode, $operation, $item);
            if ($GLOBALS['is_ajax_request']) {
                $extra_data = array('title' => $title);
                PMA_ajaxResponse($editor, true, $extra_data);
            } else {
                echo "\n\n<h2>$title</h2>\n\n$editor";
                unset($_POST);
                require './libraries/footer.inc.php';
            }
            // exit;
        } else {
            $message = __('Error in processing request') . ' : '
                     . sprintf(__('No event with name %1$s found in database %2$s'),
                               htmlspecialchars(PMA_backquote($_REQUEST['item_name'])),
                               htmlspecialchars(PMA_backquote($db)));
            $message = PMA_message::error($message);
            if ($GLOBALS['is_ajax_request']) {
                PMA_ajaxResponse($message, false);
            } else {
                $message->display();
            }
        }
    }
}

function PMA_EVN_getDataFromRequest()
{
    $retval = array();

    $retval['name'] = '';
    if (isset($_REQUEST['item_name'])) {
        $retval['name'] = $_REQUEST['item_name'];
    }
    $retval['original_name'] = '';
    if (isset($_REQUEST['item_original_name'])) {
         $retval['original_name'] = $_REQUEST['item_original_name'];
    }
    $retval['status'] = '';
    if (isset($_REQUEST['item_status'])) {
        $retval['status'] = $_REQUEST['item_status'];
    }
    $retval['type']        = 'ONE TIME';
    $retval['type_toggle'] = 'RECURRING';
    if (isset($_REQUEST['item_type']) && $_REQUEST['item_type'] == 'RECURRING') {
        $retval['type']        = 'RECURRING';
        $retval['type_toggle'] = 'ONE TIME';
    }
    $retval['execute_at'] = '';
    if (isset($_REQUEST['item_execute_at'])) {
        $retval['execute_at'] = $_REQUEST['item_execute_at'];
    }
    $retval['interval_value'] = '';
    if (isset($_REQUEST['item_interval_value'])) {
        $retval['interval_value'] = $_REQUEST['item_interval_value'];
    }
    $retval['interval_field'] = '';
    if (isset($_REQUEST['item_interval_field'])) {
        $retval['interval_field'] = $_REQUEST['item_interval_field'];
    }
    $retval['starts'] = '';
    if (isset($_REQUEST['item_starts'])) {
        $retval['starts'] = $_REQUEST['item_starts'];
    }
    $retval['ends'] = '';
    if (isset($_REQUEST['item_ends'])) {
        $retval['ends'] = $_REQUEST['item_ends'];
    }
    $retval['definition'] = '';
    if (isset($_REQUEST['item_definition'])) {
        $retval['definition'] = $_REQUEST['item_definition'];
    }
    $retval['preserve'] = '';
    if (isset($_REQUEST['item_preserve'])) {
        $retval['preserve'] = $_REQUEST['item_preserve'];
    }
    $retval['definer'] = '';
    if (isset($_REQUEST['item_definer'])) {
        $retval['definer'] = $_REQUEST['item_definer'];
    }
    $retval['comment'] = '';
    if (isset($_REQUEST['item_comment'])) {
        $retval['comment'] = $_REQUEST['item_comment'];
    }

    return $retval;
}

function PMA_EVN_getDataFromName($name)
{
    global $db;

    $retval = array();
    $columns = "`EVENT_NAME`, `STATUS`, `EVENT_TYPE`, `EXECUTE_AT`, "
             . "`INTERVAL_VALUE`, `INTERVAL_FIELD`, `STARTS`, `ENDS`, "
             . "`EVENT_DEFINITION`, `ON_COMPLETION`, `DEFINER`, `EVENT_COMMENT`";
    $where   = "EVENT_SCHEMA='" . PMA_sqlAddSlashes($db) . "' AND EVENT_NAME='" . PMA_sqlAddSlashes($name) . "'";
    $query   = "SELECT $columns FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE $where;";
    $item    = PMA_DBI_fetch_single_row($query);
    if (! $item) {
        return false;
    }
    $retval['name']   = $item['EVENT_NAME'];
    $retval['status'] = $item['STATUS'];
    $retval['type']   = $item['EVENT_TYPE'];
    if ($retval['type'] == 'RECURRING') {
        $retval['type_toggle'] = 'ONE TIME';
    } else {
        $retval['type_toggle'] = 'RECURRING';
    }
    $retval['execute_at']     = $item['EXECUTE_AT'];
    $retval['interval_value'] = $item['INTERVAL_VALUE'];
    $retval['interval_field'] = $item['INTERVAL_FIELD'];
    $retval['starts']         = $item['STARTS'];
    $retval['ends']           = $item['ENDS'];
    $retval['preserve']       = '';
    if ($item['ON_COMPLETION'] == 'PRESERVE') {
        $retval['preserve']   = " checked='checked'";
    }
    $retval['definition'] = $item['EVENT_DEFINITION'];
    $retval['definer']    = $item['DEFINER'];
    $retval['comment']    = $item['EVENT_COMMENT'];

    return $retval;
}

function PMA_EVN_getEditorForm($mode, $operation, $item)
{
    global $db, $table, $titles, $event_status, $event_type, $event_interval;

    // Escape special characters
    $need_escape = array(
                       'original_name',
                       'name',
                       'type',
                       'execute_at',
                       'interval_value',
                       'starts',
                       'ends',
                       'definition',
                       'definer',
                       'comment'
                   );
    foreach($need_escape as $key => $index) {
        $item[$index] = htmlentities($item[$index], ENT_QUOTES);
    }
    $original_data = '';
    if ($mode == 'edit') {
        $original_data = "<input name='item_original_name' "
                       . "type='hidden' value='{$item['original_name']}'/>\n";
    }
    // Handle some logic first
    if ($operation == 'change') {
        if ($item['type'] == 'RECURRING') {
            $item['type']         = 'ONE TIME';
            $item['type_toggle']  = 'RECURRING';
        } else {
            $item['type']         = 'RECURRING';
            $item['type_toggle']  = 'ONE TIME';
        }
    }
    if ($item['type'] == 'ONE TIME') {
        $isrecurring_class = ' hide';
        $isonetime_class   = '';
    } else {
        $isrecurring_class = '';
        $isonetime_class   = ' hide';
    }
    // Create the output
    $retval  = "";
    $retval .= "<!-- START " . strtoupper($mode) . " EVENT FORM -->\n\n";
    $retval .= "<form class='rte_form' action='db_events.php' method='post'>\n";
    $retval .= "<input name='{$mode}_item' type='hidden' value='1' />\n";
    $retval .= $original_data;
    $retval .= PMA_generate_common_hidden_inputs($db, $table) . "\n";
    $retval .= "<fieldset>\n";
    $retval .= "<legend>" . __('Details') . "</legend>\n";
    $retval .= "<table class='rte_table' style='width: 100%'>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td style='width: 20%;'>" . __('Event name') . "</td>\n";
    $retval .= "    <td><input type='text' name='item_name' value='{$item['name']}'\n";
    $retval .= "               maxlength='64' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Status') . "</td>\n";
    $retval .= "    <td>\n";
    $retval .= "        <select name='item_status'>\n";
    foreach ($event_status['display'] as $key => $value) {
        $selected = "";
        if (! empty($item['status']) && $item['status'] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "<option$selected>$value</option>";
    }
    $retval .= "        </select>\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";

    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Event type') . "</td>\n";
    $retval .= "    <td>\n";
    if ($GLOBALS['is_ajax_request']) {
        $retval .= "        <select name='item_type'>";
        foreach ($event_type as $key => $value) {
            $selected = "";
            if (! empty($item['type']) && $item['type'] == $value) {
                $selected = " selected='selected'";
            }
            $retval .= "<option$selected>$value</option>";
        }
        $retval .= "        </select>\n";
    } else {
        $retval .= "        <input name='item_type' type='hidden' value='{$item['type']}' />\n";
        $retval .= "        <div style='width: 49%; float: left; text-align: center; font-weight: bold;'>\n";
        $retval .= "            {$item['type']}\n";
        $retval .= "        </div>\n";
        $retval .= "        <input style='width: 49%;' type='submit' name='item_changetype'\n";
        $retval .= "               value='".sprintf(__('Change to %s'), $item['type_toggle'])."' />\n";
    }
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr class='onetime_event_row $isonetime_class'>\n";
    $retval .= "    <td>" . __('Execute at') . "</td>\n";
    $retval .= "    <td style='white-space: nowrap;'>\n";
    $retval .= "        <input type='text' name='item_execute_at' value='{$item['execute_at']}' class='datetimefield' />\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr class='recurring_event_row $isrecurring_class'>\n";
    $retval .= "    <td>" . __('Execute every') . "</td>\n";
    $retval .= "    <td>\n";
    $retval .= "        <input style='width: 49%;' type='text' name='item_interval_value' value='{$item['interval_value']}' />\n";
    $retval .= "        <select style='width: 49%;' name='item_interval_field'>";
    foreach ($event_interval as $key => $value) {
        $selected = "";
        if (! empty($item['interval_field']) && $item['interval_field'] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "<option$selected>$value</option>";
    }
    $retval .= "        </select>\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr class='recurring_event_row$isrecurring_class'>\n";
    $retval .= "    <td>" . __('Start') . "</td>\n";
    $retval .= "    <td style='white-space: nowrap;'><input type='text' name='item_starts' value='{$item['starts']}' class='datetimefield' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr class='recurring_event_row$isrecurring_class'>\n";
    $retval .= "    <td>" . __('End') . "</td>\n";
    $retval .= "    <td style='white-space: nowrap;'><input type='text' name='item_ends' value='{$item['ends']}' class='datetimefield' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definition') . "</td>\n";
    $retval .= "    <td><textarea name='item_definition' rows='15' cols='40'>{$item['definition']}</textarea></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('On completion preserve') . "</td>\n";
    $retval .= "    <td><input type='checkbox' name='item_preserve'{$item['preserve']} /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definer') . "</td>\n";
    $retval .= "    <td><input type='text' name='item_definer'\n";
    $retval .= "               value='{$item['definer']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Comment') . "</td>\n";
    $retval .= "    <td><input type='text' name='item_comment' maxlength='64'\n";
    $retval .= "               value='{$item['comment']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "</table>\n";
    $retval .= "</fieldset>\n";
    if ($GLOBALS['is_ajax_request']) {
        $retval .= "<input type='hidden' name='editor_process_{$mode}' value='true' />\n";
        $retval .= "<input type='hidden' name='ajax_request' value='true' />\n";
    } else {
        $retval .= "<fieldset class='tblFooters'>\n";
        $retval .= "    <input type='submit' name='editor_process_{$mode}'\n";
        $retval .= "           value='" . __('Go') . "' />\n";
        $retval .= "</fieldset>\n";
    }
    $retval .= "</form>\n\n";
    $retval .= "<!-- END " . strtoupper($mode) . " EVENT FORM -->\n\n";

    return $retval;
}

function PMA_EVN_getQueryFromRequest() // FIXME: need better error checking here
{
    global $_REQUEST, $cfg, $db, $errors, $event_status;

    $query = 'CREATE ';
    if (! empty($_REQUEST['item_definer']) && strpos($_REQUEST['item_definer'], '@') !== false) {
        $arr = explode('@', $_REQUEST['item_definer']);
        $query .= 'DEFINER=' . PMA_backquote($arr[0]) . '@' . PMA_backquote($arr[1]) . ' ';
    }
    $query .= 'EVENT ';
    if (! empty($_REQUEST['item_name'])) {
        $query .= PMA_backquote($_REQUEST['item_name']) . ' ';
    } else {
        $errors[] = __('You must provide an event name');
    }
    $query .= 'ON SCHEDULE ';
    if ($_REQUEST['item_type'] == 'RECURRING') {
        $query .= 'EVERY ' . $_REQUEST['item_interval_value'] . ' ';
        $query .= $_REQUEST['item_interval_field'] . ' ';
        if (! empty($_REQUEST['item_starts'])) {
            $query .= "STARTS '" . $_REQUEST['item_starts'] . "' ";
        }
        if (! empty($_REQUEST['item_ends'])) {
            $query .= "ENDS '" . $_REQUEST['item_ends'] . "' ";
        }
    } else {
        $query .= "AT '" . $_REQUEST['item_execute_at'] . "' ";
    }
    $query .= 'ON COMPLETION ';
    if (empty($_REQUEST['item_preserve'])) {
        $query .= 'NOT ';
    }
    $query .= 'PRESERVE ';
    if (! empty($_REQUEST['item_status'])) {
        foreach($event_status['display'] as $key => $value) {
            if ($value == $_REQUEST['item_status']) {
                $query .= $event_status['query'][$key] . ' ';
                break;
            }
        }
    }
    $query .= 'DO ';
    if (! empty($_REQUEST['item_definition'])) {
        $query .= $_REQUEST['item_definition'];
    } else {
        $errors[] = __('You must provide an event definition.');
    }
    return $query;
}

?>
