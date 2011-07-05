<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Main function for the triggers functionality
 */
function PMA_RTE_main()
{
    global $db, $table, $header_arr, $human_name;

    /**
     * Here we define some data that will be used to create the list triggers
     */
    $human_name = __('trigger');
    $items      = PMA_DBI_get_triggers($db, $table);
    $cols       = array(array('label' => __('Name'),   'colspan' => 1, 'field'   => 'name'),
                        array('label' => __('Table'),  'colspan' => 1, 'field'   => 'table'),
                        array('label' => __('Action'), 'colspan' => 3, 'field'   => 'edit'),
                        array(                                         'field'   => 'export'),
                        array(                                         'field'   => 'drop'),
                        array('label' => __('Time'),   'colspan' => 1, 'field'   => 'time'),
                        array('label' => __('Event'),  'colspan' => 1, 'field'   => 'event'));
    $header_arr = array('title'   => __('Triggers'),
                        'docu'    => 'TRIGGERS',
                        'nothing' => __('There are no triggers to display.'),
                        'cols'    => $cols);
    if (! empty($table)) {
        // Remove the table header
        unset ($header_arr['cols']['1']);
    }
    /**
     * Process all requests
     */
    PMA_TRI_handleEditor();
    PMA_TRI_handleExport();
    /**
     * Display a list of available triggers
     */
    $items = PMA_DBI_get_triggers($db, $table); // refresh list
    echo PMA_RTE_getList('trigger', $items);
    /**
     * Display a link for adding a new trigger,
     * if the user has the necessary privileges
     */
    echo PMA_TRI_getFooterLinks();
} // end PMA_TRI_main()

function PMA_TRI_handleEditor()
{
    global $_REQUEST, $_POST, $errors, $db, $table;

    if (! empty($_REQUEST['editor_process_add']) || ! empty($_REQUEST['editor_process_edit'])) {
        $sql_query = '';

        $item_query = PMA_TRI_getQueryFromRequest();

        if (! count($errors)) { // set by PMA_RTN_getQueryFromRequest()
            // Execute the created query
            if (! empty($_REQUEST['editor_process_edit'])) {
                // Backup the old trigger, in case something goes wrong
                $trigger = PMA_TRI_getDataFromName($_REQUEST['item_original_name']);
                $create_item = $trigger['create'];
                $drop_item = $trigger['drop'] . ';';
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
                            $errors[] = __('Sorry, we failed to restore the dropped trigger.') . '<br />'
                                              . __('The backed up query was:') . "\"$create_item\"" . '<br />'
                                              . __('MySQL said: ') . PMA_DBI_getError(null);
                        }
                    } else {
                        $message = PMA_Message::success(__('Trigger %1$s has been modified.'));
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
                    $message = PMA_Message::success(__('Trigger %1$s has been created.'));
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
                $trigger = PMA_TRI_getDataFromName($_REQUEST['item_name']);
                $extra_data['name'] = htmlspecialchars(strtoupper($_REQUEST['item_name']));
                $extra_data['insert'] = false;
                if (empty($table) || $table == $trigger['table']) {
                    $extra_data['insert'] = true;
                }
                $extra_data['new_row'] = PMA_RTE_getRowForList('trigger', $trigger, 0);
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
              (! empty($_REQUEST['add_item']) || ! empty($_REQUEST['edit_item'])))) { // FIXME: this must be simpler than that
        // Get the data for the form (if any)
        if (! empty($_REQUEST['add_item'])) {
            $title = __("Create trigger");
            $item = PMA_TRI_getDataFromRequest();
            $mode = 'add';
        } else if (! empty($_REQUEST['edit_item'])) {
            $title = __("Edit trigger");
            if (! empty($_REQUEST['item_name']) && empty($_REQUEST['editor_process_edit'])) {
                $item = PMA_TRI_getDataFromName($_REQUEST['item_name']);
                if ($item !== false) {
                    $item['original_name'] = $item['name'];
                }
            } else {
                $item = PMA_TRI_getDataFromRequest();
            }
            $mode = 'edit';
        }
        if ($item !== false) {
            // Show form
            $editor = PMA_TRI_getEditorForm($mode, $item);
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
                     . sprintf(__('No trigger with name %1$s found in database %2$s'),
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

function PMA_TRI_getDataFromRequest()
{
    $retval = array();

    $retval['name'] = '';
    if (isset($_REQUEST['item_name'])) {
        $retval['name'] = $_REQUEST['item_name'];
    }
    $retval['table'] = '';
    if (isset($_REQUEST['item_table'])) {
        $retval['table'] = $_REQUEST['item_table'];
    }
    $retval['original_name'] = '';
    if (isset($_REQUEST['item_original_name'])) {
         $retval['original_name'] = $_REQUEST['item_original_name'];
    }
    $retval['action_timing'] = '';
    if (isset($_REQUEST['item_timing'])) {
        $retval['action_timing'] = $_REQUEST['item_timing'];
    }
    $retval['event_manipulation'] = '';
    if (isset($_REQUEST['item_event'])) {
        $retval['event_manipulation'] = $_REQUEST['item_event'];
    }
    $retval['definition'] = '';
    if (isset($_REQUEST['item_definition'])) {
        $retval['definition'] = $_REQUEST['item_definition'];
    }
    $retval['definer'] = '';
    if (isset($_REQUEST['item_definer'])) {
        $retval['definer'] = $_REQUEST['item_definer'];
    }

    return $retval;
}

function PMA_TRI_getDataFromName($name)
{
    global $db, $table, $_REQUEST;

    $retval = array();
    $items = PMA_DBI_get_triggers($db, $table, '');
    foreach ($items as $key => $value) {
        if ($value['name'] == $name) {
            $retval = $value;
        }
    }
    if (empty($retval)) {
        return false;
    } else {
        return $retval;
    }
}

function PMA_TRI_getEditorForm($mode, $item)
{
    global $db, $table, $titles, $event_manipulations, $action_timings;

    // Escape special characters
    $need_escape = array(
                       'original_name',
                       'name',
                       'definition',
                       'definer'
                   );
    foreach($need_escape as $key => $index) {
        $item[$index] = htmlentities($item[$index], ENT_QUOTES);
    }
    $original_data = '';
    if ($mode == 'edit') {
        $original_data = "<input name='item_original_name' "
                       . "type='hidden' value='{$item['original_name']}'/>\n";
    }

    // Create the output
    $retval  = "";
    $retval .= "<!-- START " . strtoupper($mode) . " TRIGGER FORM -->\n\n";
    $retval .= "<form class='rte_form' action='db_triggers.php' method='post'>\n";
    $retval .= "<input name='{$mode}_item' type='hidden' value='1' />\n";
    $retval .= $original_data;
    $retval .= PMA_generate_common_hidden_inputs($db, $table) . "\n";
    $retval .= "<fieldset>\n";
    $retval .= "<legend>" . __('Details') . "</legend>\n";
    $retval .= "<table class='rte_table' style='width: 100%'>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td style='width: 20%;'>" . __('Trigger name') . "</td>\n";
    $retval .= "    <td><input type='text' name='item_name' maxlength='64'\n";
    $retval .= "               value='{$item['name']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Table') . "</td>\n";
    $retval .= "    <td>\n";
    $retval .= "        <select name='item_table'>\n";
    foreach (PMA_DBI_get_tables($db) as $key => $value) {
        $selected = "";
        if ($value == $item['table']) {
            $selected = " selected='selected'";
        }
        $retval .= "            <option$selected>$value</option>\n";
    }
    $retval .= "        </select>\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Time') . "</td>\n";
    $retval .= "    <td><select name='item_timing'>\n";
    foreach ($action_timings as $key => $value) {
        $selected = "";
        if (! empty($item['action_timing']) && $item['action_timing'] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "<option$selected>$value</option>";
    }
    $retval .= "    </select></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Event') . "</td>\n";
    $retval .= "    <td><select name='item_event'>\n";
    foreach ($event_manipulations as $key => $value) {
        $selected = "";
        if (! empty($item['event_manipulation']) && $item['event_manipulation'] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "<option$selected>$value</option>";
    }
    $retval .= "    </select></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definition') . "</td>\n";
    $retval .= "    <td><textarea name='item_definition' rows='15' cols='40'>{$item['definition']}</textarea></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definer') . "</td>\n";
    $retval .= "    <td><input type='text' name='item_definer'\n";
    $retval .= "               value='{$item['definer']}' /></td>\n";
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
    $retval .= "<!-- END " . strtoupper($mode) . " TRIGGER FORM -->\n\n";

    return $retval;
}

function PMA_TRI_getQueryFromRequest() {
    global $_REQUEST, $cfg, $db, $errors, $action_timings, $event_manipulations;

    $query = 'CREATE ';
    if (! empty($_REQUEST['item_definer']) && strpos($_REQUEST['item_definer'], '@') !== false) {
        $arr = explode('@', $_REQUEST['item_definer']);
        $query .= 'DEFINER=' . PMA_backquote($arr[0]) . '@' . PMA_backquote($arr[1]) . ' ';
    }
    $query .= 'TRIGGER ';
    if (! empty($_REQUEST['item_name'])) {
        $query .= PMA_backquote($_REQUEST['item_name']) . ' ';
    } else {
        $errors[] = __('You must provide a trigger name');
    }
    if (! empty($_REQUEST['item_timing']) && in_array($_REQUEST['item_timing'], $action_timings)) {
        $query .= $_REQUEST['item_timing'] . ' ';
    } else {
        $query .= 'BEFORE ';
    }
    if (! empty($_REQUEST['item_event']) && in_array($_REQUEST['item_event'], $event_manipulations)) {
        $query .= $_REQUEST['item_event'] . ' ';
    } else {
        $query .= 'INSERT ';
    }
    $query .= 'ON ';
    if (! empty($_REQUEST['item_table']) && in_array($_REQUEST['item_table'], PMA_DBI_get_tables($db))) {
        $query .= PMA_backQuote($_REQUEST['item_table']);
    } else {
        $errors[] = __('You must provide a valid table name');
    }
    $query .= ' FOR EACH ROW ';
    if (! empty($_REQUEST['item_definition'])) {
        $query .= $_REQUEST['item_definition'];
    } else {
        $errors[] = __('You must provide a trigger definition.');
    }
    return $query;
}

?>
