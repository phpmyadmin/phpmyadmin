<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for trigger management.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Sets required globals
 */
function PMA_TRI_setGlobals()
{
    global $action_timings, $event_manipulations;

    // Some definitions for triggers
    $action_timings      = array('BEFORE',
                                 'AFTER');
    $event_manipulations = array('INSERT',
                                 'UPDATE',
                                 'DELETE');
}

/**
 * Main function for the triggers functionality
 */
function PMA_TRI_main()
{
    global $db, $table;

    PMA_TRI_setGlobals();
    /**
     * Process all requests
     */
    PMA_TRI_handleEditor();
    PMA_TRI_handleExport();
    /**
     * Display a list of available triggers
     */
    $items = PMA_DBI_get_triggers($db, $table);
    echo PMA_RTE_getList('trigger', $items);
    /**
     * Display a link for adding a new trigger,
     * if the user has the necessary privileges
     */
    echo PMA_TRI_getFooterLinks();
} // end PMA_TRI_main()

/**
 * Handles editor requests for adding or editing an item
 */
function PMA_TRI_handleEditor()
{
    global $_REQUEST, $_POST, $errors, $db, $table;

    if (! empty($_REQUEST['editor_process_add'])
        || ! empty($_REQUEST['editor_process_edit'])
    ) {
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
            foreach ($errors as $string) {
                $message->addString('<li>' . $string . '</li>');
            }
            $message->addString('</ul>');
        }

        $output = PMA_showMessage($message, $sql_query);
        if ($GLOBALS['is_ajax_request']) {
            $extra_data = array();
            if ($message->isSuccess()) {
                $items = PMA_DBI_get_triggers($db, $table, '');
                $trigger = false;
                foreach ($items as $value) {
                    if ($value['name'] == $_REQUEST['item_name']) {
                        $trigger = $value;
                    }
                }
                $extra_data['insert'] = false;
                if (empty($table) || ($trigger !== false && $table == $trigger['table'])) {
                    $extra_data['insert'] = true;
                    $extra_data['new_row'] = PMA_TRI_getRowForList($trigger);
                    $extra_data['name'] = htmlspecialchars(
                        strtoupper($_REQUEST['item_name'])
                    );
                }
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
    if (count($errors) || ( empty($_REQUEST['editor_process_add']) && empty($_REQUEST['editor_process_edit'])
        && (! empty($_REQUEST['add_item']) || ! empty($_REQUEST['edit_item']))) // FIXME: this must be simpler than that
    ) {
        // Get the data for the form (if any)
        if (! empty($_REQUEST['add_item'])) {
            $title = PMA_RTE_getWord('add');
            $item = PMA_TRI_getDataFromRequest();
            $mode = 'add';
        } else if (! empty($_REQUEST['edit_item'])) {
            $title = __("Edit trigger");
            if (! empty($_REQUEST['item_name'])
                && empty($_REQUEST['editor_process_edit'])
            ) {
                $item = PMA_TRI_getDataFromName($_REQUEST['item_name']);
                if ($item !== false) {
                    $item['item_original_name'] = $item['item_name'];
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
                include './libraries/footer.inc.php';
            }
            // exit;
        } else {
            $message  = __('Error in processing request') . ' : ';
            $message .= sprintf(
                PMA_RTE_getWord('not_found'),
                htmlspecialchars(PMA_backquote($_REQUEST['item_name'])),
                htmlspecialchars(PMA_backquote($db))
            );
            $message = PMA_message::error($message);
            if ($GLOBALS['is_ajax_request']) {
                PMA_ajaxResponse($message, false);
            } else {
                $message->display();
            }
        }
    }
} // end PMA_TRI_handleEditor()

/**
 * This function will generate the values that are required to for the editor
 *
 * @return  array    Data necessary to create the editor.
 */
function PMA_TRI_getDataFromRequest()
{
    $retval = array();
    $indices = array('item_name',
                     'item_table',
                     'item_original_name',
                     'item_action_timing',
                     'item_event_manipulation',
                     'item_definition',
                     'item_definer');
    foreach ($indices as $index) {
        $retval[$index] = isset($_REQUEST[$index]) ? $_REQUEST[$index] : '';
    }
    return $retval;
} // end PMA_TRI_getDataFromRequest()

/**
 * This function will generate the values that are required to complete
 * the "Edit trigger" form given the name of a trigger.
 *
 * @param   string   $name   The name of the trigger.
 *
 * @return  array    Data necessary to create the editor.
 */
function PMA_TRI_getDataFromName($name)
{
    global $db, $table, $_REQUEST;

    $temp = array();
    $items = PMA_DBI_get_triggers($db, $table, '');
    foreach ($items as $value) {
        if ($value['name'] == $name) {
            $temp = $value;
        }
    }
    if (empty($temp)) {
        return false;
    } else {
        $retval = array();
        $retval['create']                  = $temp['create'];
        $retval['drop']                    = $temp['drop'];
        $retval['item_name']               = $temp['name'];
        $retval['item_table']              = $temp['table'];
        $retval['item_action_timing']      = $temp['action_timing'];
        $retval['item_event_manipulation'] = $temp['event_manipulation'];
        $retval['item_definition']         = $temp['definition'];
        $retval['item_definer']            = $temp['definer'];
        return $retval;
    }
} // end PMA_TRI_getDataFromName()

/**
 * Displays a form used to add/edit a trigger
 *
 * @param   string   $mode         If the editor will be used edit a trigger
 *                                 or add a new one: 'edit' or 'add'.
 * @param   array    $item         Data for the trigger returned by
 *                                 PMA_TRI_getDataFromRequest() or
 *                                 PMA_TRI_getDataFromName()
 *
 * @return  string   HTML code for the editor.
 */
function PMA_TRI_getEditorForm($mode, $item)
{
    global $db, $table, $event_manipulations, $action_timings;

    // Escape special characters
    $need_escape = array(
                       'item_original_name',
                       'item_name',
                       'item_definition',
                       'item_definer'
                   );
    foreach ($need_escape as $key => $index) {
        $item[$index] = htmlentities($item[$index], ENT_QUOTES);
    }
    $original_data = '';
    if ($mode == 'edit') {
        $original_data = "<input name='item_original_name' "
                       . "type='hidden' value='{$item['item_original_name']}'/>\n";
    }
    $query  = "SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` ";
    $query .= "WHERE `TABLE_SCHEMA`='" . PMA_sqlAddSlashes($db) . "' ";
    $query .= "AND `TABLE_TYPE`='BASE TABLE'";
    $tables = PMA_DBI_fetch_result($query);

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
    $retval .= "               value='{$item['item_name']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Table') . "</td>\n";
    $retval .= "    <td>\n";
    $retval .= "        <select name='item_table'>\n";
    foreach ($tables as $key => $value) {
        $selected = "";
        if ($mode == 'add' && $value == $table) {
            $selected = " selected='selected'";
        } else if ($mode == 'edit' && $value == $item['item_table']) {
            $selected = " selected='selected'";
        }
        $retval .= "            <option$selected>$value</option>\n";
    }
    $retval .= "        </select>\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . _pgettext('Trigger action time', 'Time') . "</td>\n";
    $retval .= "    <td><select name='item_timing'>\n";
    foreach ($action_timings as $key => $value) {
        $selected = "";
        if (! empty($item['item_action_timing'])
            && $item['item_action_timing'] == $value
        ) {
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
        if (! empty($item['item_event_manipulation'])
            && $item['item_event_manipulation'] == $value
        ) {
            $selected = " selected='selected'";
        }
        $retval .= "<option$selected>$value</option>";
    }
    $retval .= "    </select></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definition') . "</td>\n";
    $retval .= "    <td><textarea name='item_definition' rows='15' cols='40'>";
    $retval .= $item['item_definition'];
    $retval .= "</textarea></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definer') . "</td>\n";
    $retval .= "    <td><input type='text' name='item_definer'\n";
    $retval .= "               value='{$item['item_definer']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "</table>\n";
    $retval .= "</fieldset>\n";
    if ($GLOBALS['is_ajax_request']) {
        $retval .= "<input type='hidden' name='editor_process_{$mode}'\n";
        $retval .= "       value='true' />\n";
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
} // end PMA_TRI_getEditorForm()

/**
 * Composes the query necessary to create a trigger from an HTTP request.
 *
 * @return  string  The CREATE TRIGGER query.
 */
function PMA_TRI_getQueryFromRequest()
{
    global $_REQUEST, $db, $errors, $action_timings, $event_manipulations;

    $query = 'CREATE ';
    if (! empty($_REQUEST['item_definer'])) {
        if (strpos($_REQUEST['item_definer'], '@') !== false) {
            $arr = explode('@', $_REQUEST['item_definer']);
            $query .= 'DEFINER=' . PMA_backquote($arr[0]);
            $query .= '@' . PMA_backquote($arr[1]) . ' ';
        } else {
            $errors[] = __('The definer must be in the "username@hostname" format');
        }
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
        $errors[] = __('You must provide a valid timing for the trigger');
    }
    if (! empty($_REQUEST['item_event']) && in_array($_REQUEST['item_event'], $event_manipulations)) {
        $query .= $_REQUEST['item_event'] . ' ';
    } else {
        $errors[] = __('You must provide a valid event for the trigger');
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
} // end PMA_TRI_getQueryFromRequest()

?>
