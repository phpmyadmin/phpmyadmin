<?php

/**
 * Include required files
 */
require_once './libraries/common.inc.php';
require_once './libraries/common.lib.php';
require_once './libraries/db_routines.lib.php';

/**
 * Include JavaScript libraries
 */
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'db_routines.js';

/**
 * Create labels for the list
 */
$titles = PMA_buildActionTitles();

if ($GLOBALS['is_ajax_request'] != true) {
	/**
	 * Displays the header
	 */
	require_once './libraries/db_common.inc.php';
	/**
	 * Displays the tabs
	 */
	require_once './libraries/db_info.inc.php';
} else {
	if (strlen($db)) {
		PMA_DBI_select_db($db);
		if (! isset($url_query)) {
		    $url_query = PMA_generate_common_url($db);
		}
	}
}

/**
 * Process all requests
 */

// Some definitions
$param_directions    = array('IN',
                             'OUT',
                             'INOUT');
$param_opts_num      = array('UNSIGNED',
                             'ZEROFILL',
                             'UNSIGNED ZEROFILL');
$param_sqldataaccess = array('NO SQL',
                             'CONTAINS SQL',
                             'READS SQL DATA',
                             'MODIFIES SQL DATA');

/**
 * Generate the conditional classes that will be used to attach jQuery events to links.
 */
$ajax_class = array(
                  'add'    => '',
                  'edit'   => '',
                  'exec'   => '',
                  'drop'   => '',
                  'export' => ''
              );
if ($GLOBALS['cfg']['AjaxEnable']) {
    $ajax_class['add']    = 'class="add_routine_anchor"';
    $ajax_class['edit']   = 'class="edit_routine_anchor"';
    $ajax_class['exec']   = 'class="exec_routine_anchor"';
    $ajax_class['drop']   = 'class="drop_routine_anchor"';
    $ajax_class['export'] = 'class="export_routine_anchor"';
}

/**
 * Keep a list of errors that occured while processing an 'Add' or 'Edit' operation.
 */
$routine_errors = array();

/**
 * Handle all user requests other than the default of listing routines
 */
if (! empty($_REQUEST['execute_routine']) && ! empty($_REQUEST['routine_name'])) {
    // Build the queries
    $routine   = PMA_RTN_getRoutineDataFromName($db, $_REQUEST['routine_name'], false);
    if ($routine !== false) {
        $queries   = array();
        $end_query = array();
        $args      = array();
        for ($i=0; $i<$routine['num_params']; $i++) {
            if (isset($_REQUEST['params'][$routine['param_name'][$i]])) {
                $value = $_REQUEST['params'][$routine['param_name'][$i]];
                if (is_array($value)) { // is SET type
                    $value = implode(',', $value);
                }
                $value = PMA_sqladdslashes($value);
                if (! empty($_REQUEST['funcs'][$routine['param_name'][$i]])
                      && in_array($_REQUEST['funcs'][$routine['param_name'][$i]], $cfg['Functions'])) {
                    $queries[] = "SET @p$i={$_REQUEST['funcs'][$routine['param_name'][$i]]}('$value');\n";
                } else {
                    $queries[] = "SET @p$i='$value';\n";
                }
                $args[] = "@p$i";
            } else {
                $args[] = "@p$i";
            }
            if ($routine['type'] == 'PROCEDURE') {
                if ($routine['param_dir'][$i] == 'OUT' || $routine['param_dir'][$i] == 'INOUT') {
                    $end_query[] = "@p$i AS " . PMA_backquote($routine['param_name'][$i]);
                }
            }
        }
        if ($routine['type'] == 'PROCEDURE') {
            $queries[] = "CALL " . PMA_backquote($routine['name'])
                       . "(" . implode(', ', $args) . ");\n";
            if (count($end_query)) {
                $queries[] = "SELECT " . implode(', ', $end_query) . ";\n";
            }
        } else {
            $queries[] = "SELECT " . PMA_backquote($routine['name'])
                       . "(" . implode(', ', $args) . ") "
                       . "AS " . PMA_backquote($routine['name']) . ";\n";
        }
        // Execute the queries
        $affected = 0;
        $result = null;
        foreach ($queries as $num => $query) {
            $resource = PMA_DBI_query($query);
            while (true) {
                if(! PMA_DBI_more_results()) {
                    break;
                }
                PMA_DBI_next_result();
            }
            if (substr($query, 0, 6) == 'SELECT') {
                $result = $resource;
            } else if (substr($query, 0, 4) == 'CALL') {
                $affected = PMA_DBI_affected_rows() - PMA_DBI_num_rows($resource);
            }
        }

        // If any of the queries failed, we wouldn't have gotten
        // this far, so we simply show a success message.
        $message  = __('Your SQL query has been executed successfully');
        if ($routine['type'] == 'PROCEDURE') {
            $message .= '<br />';
            $message .= sprintf(__('%s row(s) affected by the last statement inside the procedure'), $affected);
        }
        $message = PMA_message::success($message);

        // Pass the SQL queries through the "pretty printer"
        $output  = '<code class="sql" style="margin-bottom: 1em;">';
        $output .= PMA_SQP_formatHtml(PMA_SQP_parse(implode($queries)));
        $output .= '</code>';

        // Display results
        if ($result) {
            $output .= "<fieldset><legend>";
            $output .= sprintf(__('Execution Results of Routine %s'),
                               PMA_backquote(htmlspecialchars($routine['name'])));
            $output .= "</legend>";
            $output .= "<table><tr>";
            foreach (PMA_DBI_get_fields_meta($result) as $key => $field) {
                $output .= "<th>" . htmlspecialchars($field->name) . "</th>";
            }
            $output .= "</tr>";
            // Stored routines can only ever return ONE ROW.
            $data = PMA_DBI_fetch_single_row($result);
            foreach ($data as $key => $value) {
                if ($value === null) {
                    $value = '<i>NULL</i>';
                } else {
                    $value = htmlspecialchars($value);
                }
                $output .= "<td class='odd'>" . $value . "</td>";
            }
            $output .= "</table></fieldset>";
        } else {
            $notice = __('MySQL returned an empty result set (i.e. zero rows).');
            $output .= PMA_message::notice($notice)->getDisplay();
        }
        if ($GLOBALS['is_ajax_request']) {
            $extra_data = array();
            $extra_data['dialog']  = false;
            $extra_data['results'] = $message->getDisplay() . $output;
            PMA_ajaxResponse($message, true, $extra_data);
        } else {
            echo $message->getDisplay() . $output;
            unset($_POST);
            // Now deliberately fall through to displaying the routines list
        }
    }
} else if (! empty($_GET['execute_dialog']) && ! empty($_GET['routine_name'])) {
    /**
     * Display the execute form for a routine.
     */
    $routine = PMA_RTN_getRoutineDataFromName($db, $_GET['routine_name'], false);
    if ($routine !== false) {
        $form = PMA_RTN_getExecuteForm($routine, $GLOBALS['is_ajax_request']);
        if ($GLOBALS['is_ajax_request'] == true) {
            $extra_data = array();
            $extra_data['dialog'] = true;
            $extra_data['title']  = __("Execute Routine") . " ";
            $extra_data['title'] .= PMA_backquote(htmlentities($_GET['routine_name'], ENT_QUOTES));
            PMA_ajaxResponse($form, true, $extra_data);
        } else {
            echo "\n\n<h2>" . __("Execute Routine") . "</h2>\n\n";
            echo $form;
            require './libraries/footer.inc.php';
            // exit;
        }
    } else if (($GLOBALS['is_ajax_request'] == true)) {
        PMA_ajaxResponse(PMA_message::error(), false);
    }
} else if (! empty($_GET['exportroutine']) && ! empty($_GET['routine_name'])) {
    /**
     * Display the export for a routine.
     */
    $routine_name = htmlspecialchars(PMA_backquote($_GET['routine_name']));
    $routine_type = PMA_DBI_fetch_value("SELECT ROUTINE_TYPE "
                                      . "FROM INFORMATION_SCHEMA.ROUTINES "
                                      . "WHERE ROUTINE_SCHEMA='" . PMA_sqlAddslashes($db) . "' "
                                      . "AND SPECIFIC_NAME='" . PMA_sqlAddslashes($_GET['routine_name']) . "';");
    if (! empty($routine_type) && $create_proc = PMA_DBI_get_definition($db, $routine_type, $_GET['routine_name'])) {
        $create_proc = '<textarea cols="40" rows="15" style="width: 100%;">' . htmlspecialchars($create_proc) . '</textarea>';
        if ($GLOBALS['is_ajax_request']) {
            $extra_data = array('title' => sprintf(__('Export of routine %s'), $routine_name));
            PMA_ajaxResponse($create_proc, true, $extra_data);
        } else {
            echo '<fieldset>' . "\n"
               . ' <legend>' . sprintf(__('Export of routine %s'), $routine_name) . '</legend>' . "\n"
               . $create_proc . "\n"
               . '</fieldset>';
        }
    } else {
        $response = __('Error in Processing Request') . ' : '
                  . sprintf(__('No routine with name %s found in database %s'),
                            $routine_name, htmlspecialchars(PMA_backquote($db)));
        $response = PMA_message::error($response);
        if ($GLOBALS['is_ajax_request']) {
            PMA_ajaxResponse($response, false);
        } else {
            $response->display();
        }
    }
} else if (! empty($_REQUEST['routine_process_addroutine']) || ! empty($_REQUEST['routine_process_editroutine'])) {
    /**
     * Handle a request to create/edit a routine
     */
    $sql_query = '';
    $routine_query = PMA_RTN_getQueryFromRequest();
    if (! count($routine_errors)) { // set by PMA_RTN_getQueryFromRequest()
        // Execute the created query
        if (! empty($_REQUEST['routine_process_editroutine'])) {
            // Backup the old routine, in case something goes wrong
            $create_routine = PMA_DBI_get_definition($db, $_REQUEST['routine_original_type'], $_REQUEST['routine_original_name']);
            $drop_routine = "DROP {$_REQUEST['routine_original_type']} " . PMA_backquote($_REQUEST['routine_original_name']) . ";\n";
            $result = PMA_DBI_try_query($drop_routine);
            if (! $result) {
                $routine_errors[] = sprintf(__('Query "%s" failed'), $drop_routine) . '<br />'
                                  . __('MySQL said: ') . PMA_DBI_getError(null);
            } else {
                $result = PMA_DBI_try_query($routine_query);
                if (! $result) {
                    $routine_errors[] = sprintf(__('Query "%s" failed'), $routine_query) . '<br />'
                                      . __('MySQL said: ') . PMA_DBI_getError(null);
                    // We dropped the old routine, but were unable to create the new one
                    // Try to restore the backup query
                    $result = PMA_DBI_try_query($create_routine);
                    if (! $result) {
                        // OMG, this is really bad! We dropped the query, failed to create a new one
                        // and now even the backup query does not execute!
                        // This should not happen, but we better handle this just in case.
                        $routine_errors[] = __('Sorry, we failed to restore the dropped routine.') . '<br />'
                                          . __('The backed up query was:') . "\"$create_routine\"" . '<br />'
                                          . __('MySQL said: ') . PMA_DBI_getError(null);
                    }
                } else {
                    $message = PMA_Message::success(__('Routine %1$s has been modified.'));
                    $message->addParam(PMA_backquote($_REQUEST['routine_name']));
                    $sql_query = $drop_routine . $routine_query;
                }
            }
        } else {
            // 'Add a new routine' mode
            $result = PMA_DBI_try_query($routine_query);
            if (! $result) {
                $routine_errors[] = sprintf(__('Query "%s" failed'), $routine_query) . '<br /><br />'
                                  . __('MySQL said: ') . PMA_DBI_getError(null);
            } else {
                $message = PMA_Message::success(__('Routine %1$s has been created.'));
                $message->addParam(PMA_backquote($_REQUEST['routine_name']));
                $sql_query = $routine_query;
            }
        }
    }

    if (count($routine_errors)) {
        $message = PMA_Message::error(__('<b>One or more errors have occured while processing your request:</b>'));
        $message->addString('<ul>');
        foreach ($routine_errors as $num => $string) {
            $message->addString('<li>' . $string . '</li>');
        }
        $message->addString('</ul>');
    }

    $output = PMA_showMessage($message, $sql_query);
    if ($GLOBALS['is_ajax_request']) {
        $extra_data = array();
        if ($message->isSuccess()) {
            $columns  = "`SPECIFIC_NAME`, `ROUTINE_NAME`, `ROUTINE_TYPE`, `DTD_IDENTIFIER`, `ROUTINE_DEFINITION`";
            $where    = "ROUTINE_SCHEMA='" . PMA_sqlAddslashes($db,true) . "' AND ROUTINE_NAME='" . PMA_sqlAddslashes($_REQUEST['routine_name'],true) . "'";
            $routine  = PMA_DBI_fetch_single_row("SELECT $columns FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE $where;");
            $extra_data['name']      = htmlspecialchars(strtoupper($_REQUEST['routine_name']));
            $extra_data['new_row']   = PMA_RTN_getRowForRoutinesList($routine, 0, true);
            $extra_data['sql_query'] = $output;
            $response = PMA_message::success();
        } else {
            $response = $message;
        }
        PMA_ajaxResponse($response, $message->isSuccess(), $extra_data);
    }
}

/**
 * Display a form used to add/edit a routine, if necessary
 */
if (count($routine_errors) || ( empty($_REQUEST['routine_process_addroutine']) && empty($_REQUEST['routine_process_editroutine']) &&
          (! empty($_REQUEST['addroutine']) || ! empty($_REQUEST['editroutine'])
        || ! empty($_REQUEST['routine_addparameter']) || ! empty($_REQUEST['routine_removeparameter'])
        || ! empty($_REQUEST['routine_changetype'])))) { // FIXME: this must be simpler than that
    // Handle requests to add/remove parameters and changing routine type
    // This is necessary when JS is disabled
    $operation = '';
    if (! empty($_REQUEST['routine_addparameter'])) {
        $operation = 'add';
    } else if (! empty($_REQUEST['routine_removeparameter'])) {
        $operation = 'remove';
    } else if (! empty($_REQUEST['routine_changetype'])) {
        $operation = 'change';
    }
    // Get the data for the form (if any)
    if (! empty($_REQUEST['addroutine'])) {
        if ($GLOBALS['is_ajax_request'] != true) {
            echo "\n\n<h2>" . __("Create Routine") . "</h2>\n\n";
        } else {
            $title = __("Create Routine");
        }
        $routine = PMA_RTN_getRoutineDataFromRequest();
        $mode = 'add';
    } else if (! empty($_REQUEST['editroutine'])) {
        if ($GLOBALS['is_ajax_request'] != true) {
            echo "\n\n<h2>" . __("Edit Routine") . "</h2>\n\n";
        } else {
            $title = __("Edit Routine");
        }
        if (! $operation && ! empty($_REQUEST['routine_name']) && empty($_REQUEST['routine_process_editroutine'])) {
            $routine = PMA_RTN_getRoutineDataFromName($db, $_REQUEST['routine_name']);
            if ($routine !== false) {
                $routine['original_name'] = $routine['name'];
                $routine['original_type'] = $routine['type'];
            }
        } else {
            $routine = PMA_RTN_getRoutineDataFromRequest();
        }
        $mode = 'edit';
    }
    if ($routine !== false) {
        // Show form
        $editor = PMA_RTN_getEditorForm($mode, $operation, $routine, $routine_errors, $GLOBALS['is_ajax_request']);
        if ($GLOBALS['is_ajax_request']) {
            $template   = PMA_RTN_getParameterRow();
            $extra_data = array('title' => $title, 'param_template' => $template, 'type' => $routine['type']);
            PMA_ajaxResponse($editor, true, $extra_data);
        }
        echo $editor;
        require './libraries/footer.inc.php';
        // exit;
    }
}

/**
 * Display a list of available routines
 */
echo PMA_RTN_getRoutinesList();

/**
 * Display the form for adding a new routine, if the user has the privileges.
 */
echo PMA_RTN_getAddRoutineLink();

/**
 * Display a warning for users with PHP's old "mysql" extension.
 */
if ($GLOBALS['cfg']['Server']['extension'] !== 'mysqli') {
    trigger_error(__('You are using PHP\'s deprecated \'mysql\' extension, '
                   . 'which is not capable of handling multi queries. '
                   . '<b>The execution of some stored Routines may fail!</b> '
                   . 'Please use the improved \'mysqli\' extension to '
                   . 'avoid any problems.'), E_USER_WARNING);
}

if ($GLOBALS['is_ajax_request'] != true) {
	/**
	 * Displays the footer
	 */
	require './libraries/footer.inc.php';
}

?>
