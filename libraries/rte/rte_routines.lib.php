<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for routine management.
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Message;
use PMA\libraries\Response;
use PMA\libraries\Util;
use SqlParser\Statements\CreateStatement;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Sets required globals
 *
 * @return void
 */
function PMA_RTN_setGlobals()
{
    global $param_directions, $param_opts_num, $param_sqldataaccess;

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
}

/**
 * Main function for the routines functionality
 *
 * @param string $type 'FUNCTION' for functions,
 *                     'PROCEDURE' for procedures,
 *                     null for both
 *
 * @return void
 */
function PMA_RTN_main($type)
{
    global $db;

    PMA_RTN_setGlobals();
    /**
     * Process all requests
     */
    PMA_RTN_handleEditor();
    PMA_RTN_handleExecute();
    PMA_RTN_handleExport();
    /**
     * Display a list of available routines
     */
    if (! PMA_isValid($type, array('FUNCTION','PROCEDURE'))) {
        $type = null;
    }
    $items = $GLOBALS['dbi']->getRoutines($db, $type);
    echo PMA_RTE_getList('routine', $items);
    /**
     * Display the form for adding a new routine, if the user has the privileges.
     */
    echo PMA_RTN_getFooterLinks();
    /**
     * Display a warning for users with PHP's old "mysql" extension.
     */
    if (! PMA\libraries\DatabaseInterface::checkDbExtension('mysqli')) {
        trigger_error(
            __(
                'You are using PHP\'s deprecated \'mysql\' extension, '
                . 'which is not capable of handling multi queries. '
                . '[strong]The execution of some stored routines may fail![/strong] '
                . 'Please use the improved \'mysqli\' extension to '
                . 'avoid any problems.'
            ),
            E_USER_WARNING
        );
    }
} // end PMA_RTN_main()

/**
 * Handles editor requests for adding or editing an item
 *
 * @return void
 */
function PMA_RTN_handleEditor()
{
    global $_GET, $_POST, $_REQUEST, $GLOBALS, $db, $errors;

    $errors = PMA_RTN_handleRequestCreateOrEdit($errors, $db);

    /**
     * Display a form used to add/edit a routine, if necessary
     */
    // FIXME: this must be simpler than that
    if (count($errors)
        || ( empty($_REQUEST['editor_process_add'])
        && empty($_REQUEST['editor_process_edit'])
        && (! empty($_REQUEST['add_item']) || ! empty($_REQUEST['edit_item'])
        || ! empty($_REQUEST['routine_addparameter'])
        || ! empty($_REQUEST['routine_removeparameter'])
        || ! empty($_REQUEST['routine_changetype'])))
    ) {
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
        if (! empty($_REQUEST['add_item'])) {
            $title = PMA_RTE_getWord('add');
            $routine = PMA_RTN_getDataFromRequest();
            $mode = 'add';
        } else if (! empty($_REQUEST['edit_item'])) {
            $title = __("Edit routine");
            if (! $operation && ! empty($_REQUEST['item_name'])
                && empty($_REQUEST['editor_process_edit'])
            ) {
                $routine = PMA_RTN_getDataFromName(
                    $_REQUEST['item_name'], $_REQUEST['item_type']
                );
                if ($routine !== false) {
                    $routine['item_original_name'] = $routine['item_name'];
                    $routine['item_original_type'] = $routine['item_type'];
                }
            } else {
                $routine = PMA_RTN_getDataFromRequest();
            }
            $mode = 'edit';
        }
        if ($routine !== false) {
            // Show form
            $editor = PMA_RTN_getEditorForm($mode, $operation, $routine);
            if ($GLOBALS['is_ajax_request']) {
                $response = PMA\libraries\Response::getInstance();
                $response->addJSON('message', $editor);
                $response->addJSON('title', $title);
                $response->addJSON('param_template', PMA_RTN_getParameterRow());
                $response->addJSON('type', $routine['item_type']);
            } else {
                echo "\n\n<h2>$title</h2>\n\n$editor";
            }
            exit;
        } else {
            $message  = __('Error in processing request:') . ' ';
            $message .= sprintf(
                PMA_RTE_getWord('not_found'),
                htmlspecialchars(
                    PMA\libraries\Util::backquote($_REQUEST['item_name'])
                ),
                htmlspecialchars(PMA\libraries\Util::backquote($db))
            );
            $message = Message::error($message);
            if ($GLOBALS['is_ajax_request']) {
                $response = PMA\libraries\Response::getInstance();
                $response->setRequestStatus(false);
                $response->addJSON('message', $message);
                exit;
            } else {
                $message->display();
            }
        }
    }
}

/**
 * Handle request to create or edit a routine
 *
 * @param array  $errors Errors
 * @param string $db     DB name
 *
 * @return array
 */
function PMA_RTN_handleRequestCreateOrEdit($errors, $db)
{
    if (empty($_REQUEST['editor_process_add'])
        && empty($_REQUEST['editor_process_edit'])
    ) {
        return $errors;
    }

    $sql_query = '';
    $routine_query = PMA_RTN_getQueryFromRequest();
    if (!count($errors)) { // set by PMA_RTN_getQueryFromRequest()
        // Execute the created query
        if (!empty($_REQUEST['editor_process_edit'])) {
            $isProcOrFunc = in_array(
                $_REQUEST['item_original_type'],
                array('PROCEDURE', 'FUNCTION')
            );

            if (!$isProcOrFunc) {
                $errors[] = sprintf(
                    __('Invalid routine type: "%s"'),
                    htmlspecialchars($_REQUEST['item_original_type'])
                );
            } else {
                // Backup the old routine, in case something goes wrong
                $create_routine = $GLOBALS['dbi']->getDefinition(
                    $db,
                    $_REQUEST['item_original_type'],
                    $_REQUEST['item_original_name']
                );

                $privilegesBackup = PMA_RTN_backupPrivileges();

                $drop_routine = "DROP {$_REQUEST['item_original_type']} "
                    . PMA\libraries\Util::backquote($_REQUEST['item_original_name'])
                    . ";\n";
                $result = $GLOBALS['dbi']->tryQuery($drop_routine);
                if (!$result) {
                    $errors[] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($drop_routine)
                    )
                    . '<br />'
                    . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);
                } else {
                    list($newErrors, $message) = PMA_RTN_createRoutine(
                        $routine_query,
                        $create_routine,
                        $privilegesBackup
                    );
                    if (empty($newErrors)) {
                        $sql_query = $drop_routine . $routine_query;
                    } else {
                        $errors = array_merge($errors, $newErrors);
                    }
                    unset($newErrors);
                    if (null === $message) {
                        unset($message);
                    }
                }
            }
        } else {
            // 'Add a new routine' mode
            $result = $GLOBALS['dbi']->tryQuery($routine_query);
            if (!$result) {
                $errors[] = sprintf(
                    __('The following query has failed: "%s"'),
                    htmlspecialchars($routine_query)
                )
                . '<br /><br />'
                . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);
            } else {
                $message = PMA\libraries\Message::success(
                    __('Routine %1$s has been created.')
                );
                $message->addParam(
                    PMA\libraries\Util::backquote($_REQUEST['item_name'])
                );
                $sql_query = $routine_query;
            }
        }
    }

    if (count($errors)) {
        $message = PMA\libraries\Message::error(
            __(
                'One or more errors have occurred while'
                . ' processing your request:'
            )
        );
        $message->addString('<ul>');
        foreach ($errors as $string) {
            $message->addString('<li>' . $string . '</li>');
        }
        $message->addString('</ul>');
    }

    $output = PMA\libraries\Util::getMessage($message, $sql_query);
    if (!$GLOBALS['is_ajax_request']) {
        return $errors;
    }

    $response = PMA\libraries\Response::getInstance();
    if (!$message->isSuccess()) {
        $response->setRequestStatus(false);
        $response->addJSON('message', $output);
        exit;
    }

    $routines = $GLOBALS['dbi']->getRoutines(
        $db,
        $_REQUEST['item_type'],
        $_REQUEST['item_name']
    );
    $routine = $routines[0];
    $response->addJSON(
        'name',
        htmlspecialchars(
            mb_strtoupper($_REQUEST['item_name'])
        )
    );
    $response->addJSON('new_row', PMA_RTN_getRowForList($routine));
    $response->addJSON('insert', !empty($routine));
    $response->addJSON('message', $output);
    exit;
}

/**
 * Backup the privileges
 *
 * @return array
 */
function PMA_RTN_backupPrivileges()
{
    if (! $GLOBALS['proc_priv'] || ! $GLOBALS['is_reload_priv']) {
        return array();
    }

    // Backup the Old Privileges before dropping
    // if $_REQUEST['item_adjust_privileges'] set
    if (! isset($_REQUEST['item_adjust_privileges'])
        || empty($_REQUEST['item_adjust_privileges'])
    ) {
        return array();
    }

    $privilegesBackupQuery = 'SELECT * FROM ' . PMA\libraries\Util::backquote(
        'mysql'
    )
    . '.' . PMA\libraries\Util::backquote('procs_priv')
    . ' where Routine_name = "' . $_REQUEST['item_original_name']
    . '" AND Routine_type = "' . $_REQUEST['item_original_type']
    . '";';

    $privilegesBackup = $GLOBALS['dbi']->fetchResult(
        $privilegesBackupQuery,
        0
    );

    return $privilegesBackup;
}

/**
 * Create the routine
 *
 * @param string $routine_query    Query to create routine
 * @param string $create_routine   Query to restore routine
 * @param array  $privilegesBackup Privileges backup
 *
 * @return array
 */
function PMA_RTN_createRoutine(
    $routine_query,
    $create_routine,
    $privilegesBackup
) {
    $result = $GLOBALS['dbi']->tryQuery($routine_query);
    if (!$result) {
        $errors = array();
        $errors[] = sprintf(
            __('The following query has failed: "%s"'),
            htmlspecialchars($routine_query)
        )
        . '<br />'
        . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);
        // We dropped the old routine,
        // but were unable to create the new one
        // Try to restore the backup query
        $result = $GLOBALS['dbi']->tryQuery($create_routine);
        $errors = checkResult(
            $result,
            __(
                'Sorry, we failed to restore'
                . ' the dropped routine.'
            ),
            $create_routine,
            $errors
        );

        return array($errors, null);
    }

    // Default value
    $resultAdjust = false;

    if ($GLOBALS['proc_priv']
        && $GLOBALS['is_reload_priv']
    ) {
        // Insert all the previous privileges
        // but with the new name and the new type
        foreach ($privilegesBackup as $priv) {
            $adjustProcPrivilege = 'INSERT INTO '
                . Util::backquote('mysql') . '.'
                . Util::backquote('procs_priv')
                . ' VALUES("' . $priv[0] . '", "'
                . $priv[1] . '", "' . $priv[2] . '", "'
                . $_REQUEST['item_name'] . '", "'
                . $_REQUEST['item_type'] . '", "'
                . $priv[5] . '", "'
                . $priv[6] . '", "'
                . $priv[7] . '");';
            $resultAdjust = $GLOBALS['dbi']->query(
                $adjustProcPrivilege
            );
        }
    }

    $message = PMA_RTN_flushPrivileges($resultAdjust);

    return array(array(), $message);
}

/**
 * Flush privileges and get message
 *
 * @param bool $flushPrivileges Flush privileges
 *
 * @return PMA\libraries\Message
 */
function PMA_RTN_flushPrivileges($flushPrivileges)
{
    if ($flushPrivileges) {
        // Flush the Privileges
        $flushPrivQuery = 'FLUSH PRIVILEGES;';
        $GLOBALS['dbi']->query($flushPrivQuery);

        $message = PMA\libraries\Message::success(
            __(
                'Routine %1$s has been modified. Privileges have been adjusted.'
            )
        );
    } else {
        $message = PMA\libraries\Message::success(
            __('Routine %1$s has been modified.')
        );
    }
    $message->addParam(
        PMA\libraries\Util::backquote($_REQUEST['item_name'])
    );

    return $message;
} // end PMA_RTN_handleEditor()

/**
 * This function will generate the values that are required to
 * complete the editor form. It is especially necessary to handle
 * the 'Add another parameter', 'Remove last parameter' and
 * 'Change routine type' functionalities when JS is disabled.
 *
 * @return array    Data necessary to create the routine editor.
 */
function PMA_RTN_getDataFromRequest()
{
    global $_REQUEST, $param_directions, $param_sqldataaccess;

    $retval = array();
    $indices = array('item_name',
                     'item_original_name',
                     'item_returnlength',
                     'item_returnopts_num',
                     'item_returnopts_text',
                     'item_definition',
                     'item_comment',
                     'item_definer');
    foreach ($indices as $index) {
        $retval[$index] = isset($_REQUEST[$index]) ? $_REQUEST[$index] : '';
    }

    $retval['item_type']         = 'PROCEDURE';
    $retval['item_type_toggle']  = 'FUNCTION';
    if (isset($_REQUEST['item_type']) && $_REQUEST['item_type'] == 'FUNCTION') {
        $retval['item_type']         = 'FUNCTION';
        $retval['item_type_toggle']  = 'PROCEDURE';
    }
    $retval['item_original_type'] = 'PROCEDURE';
    if (isset($_REQUEST['item_original_type'])
        && $_REQUEST['item_original_type'] == 'FUNCTION'
    ) {
        $retval['item_original_type'] = 'FUNCTION';
    }
    $retval['item_num_params']      = 0;
    $retval['item_param_dir']       = array();
    $retval['item_param_name']      = array();
    $retval['item_param_type']      = array();
    $retval['item_param_length']    = array();
    $retval['item_param_opts_num']  = array();
    $retval['item_param_opts_text'] = array();
    if (isset($_REQUEST['item_param_name'])
        && isset($_REQUEST['item_param_type'])
        && isset($_REQUEST['item_param_length'])
        && isset($_REQUEST['item_param_opts_num'])
        && isset($_REQUEST['item_param_opts_text'])
        && is_array($_REQUEST['item_param_name'])
        && is_array($_REQUEST['item_param_type'])
        && is_array($_REQUEST['item_param_length'])
        && is_array($_REQUEST['item_param_opts_num'])
        && is_array($_REQUEST['item_param_opts_text'])
    ) {
        if ($_REQUEST['item_type'] == 'PROCEDURE') {
            $retval['item_param_dir'] = $_REQUEST['item_param_dir'];
            foreach ($retval['item_param_dir'] as $key => $value) {
                if (! in_array($value, $param_directions, true)) {
                    $retval['item_param_dir'][$key] = '';
                }
            }
        }
        $retval['item_param_name'] = $_REQUEST['item_param_name'];
        $retval['item_param_type'] = $_REQUEST['item_param_type'];
        foreach ($retval['item_param_type'] as $key => $value) {
            if (! in_array($value, PMA\libraries\Util::getSupportedDatatypes(), true)) {
                $retval['item_param_type'][$key] = '';
            }
        }
        $retval['item_param_length']    = $_REQUEST['item_param_length'];
        $retval['item_param_opts_num']  = $_REQUEST['item_param_opts_num'];
        $retval['item_param_opts_text'] = $_REQUEST['item_param_opts_text'];
        $retval['item_num_params'] = max(
            count($retval['item_param_name']),
            count($retval['item_param_type']),
            count($retval['item_param_length']),
            count($retval['item_param_opts_num']),
            count($retval['item_param_opts_text'])
        );
    }
    $retval['item_returntype'] = '';
    if (isset($_REQUEST['item_returntype'])
        && in_array($_REQUEST['item_returntype'], PMA\libraries\Util::getSupportedDatatypes())
    ) {
        $retval['item_returntype'] = $_REQUEST['item_returntype'];
    }

    $retval['item_isdeterministic'] = '';
    if (isset($_REQUEST['item_isdeterministic'])
        && mb_strtolower($_REQUEST['item_isdeterministic']) == 'on'
    ) {
        $retval['item_isdeterministic'] = " checked='checked'";
    }
    $retval['item_securitytype_definer'] = '';
    $retval['item_securitytype_invoker'] = '';
    if (isset($_REQUEST['item_securitytype'])) {
        if ($_REQUEST['item_securitytype'] === 'DEFINER') {
            $retval['item_securitytype_definer'] = " selected='selected'";
        } else if ($_REQUEST['item_securitytype'] === 'INVOKER') {
            $retval['item_securitytype_invoker'] = " selected='selected'";
        }
    }
    $retval['item_sqldataaccess'] = '';
    if (isset($_REQUEST['item_sqldataaccess'])
        && in_array($_REQUEST['item_sqldataaccess'], $param_sqldataaccess, true)
    ) {
        $retval['item_sqldataaccess'] = $_REQUEST['item_sqldataaccess'];
    }

    return $retval;
} // end function PMA_RTN_getDataFromRequest()

/**
 * This function will generate the values that are required to complete
 * the "Edit routine" form given the name of a routine.
 *
 * @param string $name The name of the routine.
 * @param string $type Type of routine (ROUTINE|PROCEDURE)
 * @param bool   $all  Whether to return all data or just
 *                     the info about parameters.
 *
 * @return array    Data necessary to create the routine editor.
 */
function PMA_RTN_getDataFromName($name, $type, $all = true)
{
    global $db;

    $retval  = array();

    // Build and execute the query
    $fields  = "SPECIFIC_NAME, ROUTINE_TYPE, DTD_IDENTIFIER, "
             . "ROUTINE_DEFINITION, IS_DETERMINISTIC, SQL_DATA_ACCESS, "
             . "ROUTINE_COMMENT, SECURITY_TYPE";
    $where   = "ROUTINE_SCHEMA " . PMA\libraries\Util::getCollateForIS() . "="
             . "'" . PMA\libraries\Util::sqlAddSlashes($db) . "' "
             . "AND SPECIFIC_NAME='" . PMA\libraries\Util::sqlAddSlashes($name) . "'"
             . "AND ROUTINE_TYPE='" . PMA\libraries\Util::sqlAddSlashes($type) . "'";
    $query   = "SELECT $fields FROM INFORMATION_SCHEMA.ROUTINES WHERE $where;";

    $routine = $GLOBALS['dbi']->fetchSingleRow($query);

    if (! $routine) {
        return false;
    }

    // Get required data
    $retval['item_name'] = $routine['SPECIFIC_NAME'];
    $retval['item_type'] = $routine['ROUTINE_TYPE'];

    $parser = new SqlParser\Parser(
        $GLOBALS['dbi']->getDefinition(
            $db,
            $routine['ROUTINE_TYPE'],
            $routine['SPECIFIC_NAME']
        )
    );

    /**
     * @var CreateStatement $stmt
     */
    $stmt = $parser->statements[0];

    $params = SqlParser\Utils\Routine::getParameters($stmt);
    $retval['item_num_params']       = $params['num'];
    $retval['item_param_dir']        = $params['dir'];
    $retval['item_param_name']       = $params['name'];
    $retval['item_param_type']       = $params['type'];
    $retval['item_param_length']     = $params['length'];
    $retval['item_param_length_arr'] = $params['length_arr'];
    $retval['item_param_opts_num']   = $params['opts'];
    $retval['item_param_opts_text']  = $params['opts'];

    // Get extra data
    if (!$all) {
        return $retval;
    }

    if ($retval['item_type'] == 'FUNCTION') {
        $retval['item_type_toggle'] = 'PROCEDURE';
    } else {
        $retval['item_type_toggle'] = 'FUNCTION';
    }
    $retval['item_returntype']      = '';
    $retval['item_returnlength']    = '';
    $retval['item_returnopts_num']  = '';
    $retval['item_returnopts_text'] = '';

    if (! empty($routine['DTD_IDENTIFIER'])) {
        $options = array();
        foreach ($stmt->return->options->options as $opt) {
            $options[] = is_string($opt) ? $opt : $opt['value'];
        }

        $retval['item_returntype']      = $stmt->return->name;
        $retval['item_returnlength']    = implode(',', $stmt->return->size);
        $retval['item_returnopts_num']  = implode(' ', $options);
        $retval['item_returnopts_text'] = implode(' ', $options);
    }

    $retval['item_definer'] = $stmt->options->has('DEFINER');
    $retval['item_definition'] = $routine['ROUTINE_DEFINITION'];
    $retval['item_isdeterministic'] = '';
    if ($routine['IS_DETERMINISTIC'] == 'YES') {
        $retval['item_isdeterministic'] = " checked='checked'";
    }
    $retval['item_securitytype_definer'] = '';
    $retval['item_securitytype_invoker'] = '';
    if ($routine['SECURITY_TYPE'] == 'DEFINER') {
        $retval['item_securitytype_definer'] = " selected='selected'";
    } else if ($routine['SECURITY_TYPE'] == 'INVOKER') {
        $retval['item_securitytype_invoker'] = " selected='selected'";
    }
    $retval['item_sqldataaccess'] = $routine['SQL_DATA_ACCESS'];
    $retval['item_comment']       = $routine['ROUTINE_COMMENT'];

    return $retval;
} // PMA_RTN_getDataFromName()

/**
 * Creates one row for the parameter table used in the routine editor.
 *
 * @param array  $routine Data for the routine returned by
 *                        PMA_RTN_getDataFromRequest() or
 *                        PMA_RTN_getDataFromName()
 * @param mixed  $index   Either a numeric index of the row being processed
 *                        or NULL to create a template row for AJAX request
 * @param string $class   Class used to hide the direction column, if the
 *                        row is for a stored function.
 *
 * @return string    HTML code of one row of parameter table for the editor.
 */
function PMA_RTN_getParameterRow($routine = array(), $index = null, $class = '')
{
    global $param_directions, $param_opts_num, $titles;

    if ($index === null) {
        // template row for AJAX request
        $i = 0;
        $index = '%s';
        $drop_class = '';
        $routine = array(
            'item_param_dir'       => array(0 => ''),
            'item_param_name'      => array(0 => ''),
            'item_param_type'      => array(0 => ''),
            'item_param_length'    => array(0 => ''),
            'item_param_opts_num'  => array(0 => ''),
            'item_param_opts_text' => array(0 => '')
        );
    } else if (! empty($routine)) {
        // regular row for routine editor
        $drop_class = ' hide';
        $i = $index;
    } else {
        // No input data. This shouldn't happen,
        // but better be safe than sorry.
        return '';
    }

    // Create the output
    $retval  = "";
    $retval .= "        <tr>\n";
    $retval .= "            <td class='dragHandle'>"
        . "<span class='ui-icon ui-icon-arrowthick-2-n-s'></span>"
        . "</td>\n";
    $retval .= "            <td class='routine_direction_cell$class'>\n";
    $retval .= "                <select name='item_param_dir[$index]'>\n";
    foreach ($param_directions as $key => $value) {
        $selected = "";
        if (! empty($routine['item_param_dir'][$i])
            && $routine['item_param_dir'][$i] == $value
        ) {
            $selected = " selected='selected'";
        }
        $retval .= "                    <option$selected>$value</option>\n";
    }
    $retval .= "                </select>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td><input name='item_param_name[$index]' type='text'\n"
        . " value='{$routine['item_param_name'][$i]}' /></td>\n";
    $retval .= "            <td><select name='item_param_type[$index]'>";
    $retval .= PMA\libraries\Util::getSupportedDatatypes(
        true, $routine['item_param_type'][$i]
    ) . "\n";
    $retval .= "            </select></td>\n";
    $retval .= "            <td>\n";
    $retval .= "                <input id='item_param_length_$index'\n"
        . " name='item_param_length[$index]' type='text'\n"
        . " value='{$routine['item_param_length'][$i]}' />\n";
    $retval .= "                <div class='enum_hint'>\n";
    $retval .= "                    <a href='#' class='open_enum_editor'>\n";
    $retval .= "                        "
        . PMA\libraries\Util::getImage('b_edit', '', array('title'=>__('ENUM/SET editor')))
        . "\n";
    $retval .= "                    </a>\n";
    $retval .= "                </div>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td class='hide no_len'>---</td>\n";
    $retval .= "            <td class='routine_param_opts_text'>\n";
    $retval .= PMA_generateCharsetDropdownBox(
        PMA_CSDROPDOWN_CHARSET,
        "item_param_opts_text[$index]",
        null,
        $routine['item_param_opts_text'][$i]
    );
    $retval .= "            </td>\n";
    $retval .= "            <td class='hide no_opts'>---</td>\n";
    $retval .= "            <td class='routine_param_opts_num'>\n";
    $retval .= "                <select name='item_param_opts_num[$index]'>\n";
    $retval .= "                    <option value=''></option>";
    foreach ($param_opts_num as $key => $value) {
        $selected = "";
        if (! empty($routine['item_param_opts_num'][$i])
            && $routine['item_param_opts_num'][$i] == $value
        ) {
            $selected = " selected='selected'";
        }
        $retval .= "<option$selected>$value</option>";
    }
    $retval .= "\n                </select>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td class='routine_param_remove$drop_class'>\n";
    $retval .= "                <a href='#' class='routine_param_remove_anchor'>\n";
    $retval .= "                    {$titles['Drop']}\n";
    $retval .= "                </a>\n";
    $retval .= "            </td>\n";
    $retval .= "        </tr>\n";

    return $retval;
} // end PMA_RTN_getParameterRow()

/**
 * Displays a form used to add/edit a routine
 *
 * @param string $mode      If the editor will be used to edit a routine
 *                          or add a new one: 'edit' or 'add'.
 * @param string $operation If the editor was previously invoked with
 *                          JS turned off, this will hold the name of
 *                          the current operation
 * @param array  $routine   Data for the routine returned by
 *                          PMA_RTN_getDataFromRequest() or
 *                          PMA_RTN_getDataFromName()
 *
 * @return string   HTML code for the editor.
 */
function PMA_RTN_getEditorForm($mode, $operation, $routine)
{
    global $db, $errors, $param_sqldataaccess, $param_opts_num;

    // Escape special characters
    $need_escape = array(
        'item_original_name',
        'item_name',
        'item_returnlength',
        'item_definition',
        'item_definer',
        'item_comment'
    );
    foreach ($need_escape as $key => $index) {
        $routine[$index] = htmlentities($routine[$index], ENT_QUOTES, 'UTF-8');
    }
    for ($i = 0; $i < $routine['item_num_params']; $i++) {
        $routine['item_param_name'][$i]   = htmlentities(
            $routine['item_param_name'][$i],
            ENT_QUOTES
        );
        $routine['item_param_length'][$i] = htmlentities(
            $routine['item_param_length'][$i],
            ENT_QUOTES
        );
    }

    // Handle some logic first
    if ($operation == 'change') {
        if ($routine['item_type'] == 'PROCEDURE') {
            $routine['item_type']        = 'FUNCTION';
            $routine['item_type_toggle'] = 'PROCEDURE';
        } else {
            $routine['item_type']        = 'PROCEDURE';
            $routine['item_type_toggle'] = 'FUNCTION';
        }
    } else if ($operation == 'add'
        || ($routine['item_num_params'] == 0 && $mode == 'add' && ! $errors)
    ) {
        $routine['item_param_dir'][]       = '';
        $routine['item_param_name'][]      = '';
        $routine['item_param_type'][]      = '';
        $routine['item_param_length'][]    = '';
        $routine['item_param_opts_num'][]  = '';
        $routine['item_param_opts_text'][] = '';
        $routine['item_num_params']++;
    } else if ($operation == 'remove') {
        unset($routine['item_param_dir'][$routine['item_num_params'] - 1]);
        unset($routine['item_param_name'][$routine['item_num_params'] - 1]);
        unset($routine['item_param_type'][$routine['item_num_params'] - 1]);
        unset($routine['item_param_length'][$routine['item_num_params'] - 1]);
        unset($routine['item_param_opts_num'][$routine['item_num_params'] - 1]);
        unset($routine['item_param_opts_text'][$routine['item_num_params'] - 1]);
        $routine['item_num_params']--;
    }
    $disableRemoveParam = '';
    if (! $routine['item_num_params']) {
        $disableRemoveParam = " color: gray;' disabled='disabled";
    }
    $original_routine = '';
    if ($mode == 'edit') {
        $original_routine = "<input name='item_original_name' "
                          . "type='hidden' "
                          . "value='{$routine['item_original_name']}'/>\n"
                          . "<input name='item_original_type' "
                          . "type='hidden' "
                          . "value='{$routine['item_original_type']}'/>\n";
    }
    $isfunction_class   = '';
    $isprocedure_class  = '';
    $isfunction_select  = '';
    $isprocedure_select = '';
    if ($routine['item_type'] == 'PROCEDURE') {
        $isfunction_class   = ' hide';
        $isprocedure_select = " selected='selected'";
    } else {
        $isprocedure_class = ' hide';
        $isfunction_select = " selected='selected'";
    }

    // Create the output
    $retval  = "";
    $retval .= "<!-- START " . mb_strtoupper($mode)
        . " ROUTINE FORM -->\n\n";
    $retval .= "<form class='rte_form' action='db_routines.php' method='post'>\n";
    $retval .= "<input name='{$mode}_item' type='hidden' value='1' />\n";
    $retval .= $original_routine;
    $retval .= PMA_URL_getHiddenInputs($db) . "\n";
    $retval .= "<fieldset>\n";
    $retval .= "<legend>" . __('Details') . "</legend>\n";
    $retval .= "<table class='rte_table' style='width: 100%'>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td style='width: 20%;'>" . __('Routine name') . "</td>\n";
    $retval .= "    <td><input type='text' name='item_name' maxlength='64'\n";
    $retval .= "               value='{$routine['item_name']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Type') . "</td>\n";
    $retval .= "    <td>\n";
    if ($GLOBALS['is_ajax_request']) {
        $retval .= "        <select name='item_type'>\n"
            . "<option value='PROCEDURE'$isprocedure_select>PROCEDURE</option>\n"
            . "<option value='FUNCTION'$isfunction_select>FUNCTION</option>\n"
            . "</select>\n";
    } else {
        $retval .= "<input name='item_type' type='hidden'"
            . " value='{$routine['item_type']}' />\n"
            . "<div class='floatleft' style='width: 49%; text-align: center;"
            . " font-weight: bold;'>\n"
            . $routine['item_type'] . "\n"
            . "</div>\n"
            . "<input style='width: 49%;' type='submit' name='routine_changetype'\n"
            . " value='" . sprintf(__('Change to %s'), $routine['item_type_toggle'])
            . "' />\n";
    }
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Parameters') . "</td>\n";
    $retval .= "    <td>\n";
    // parameter handling start
    $retval .= "        <table class='routine_params_table'>\n";
    $retval .= "        <thead>\n";
    $retval .= "        <tr>\n";
    $retval .= "            <td></td>\n";
    $retval .= "            <th class='routine_direction_cell$isprocedure_class'>"
        . __('Direction') . "</th>\n";
    $retval .= "            <th>" . __('Name') . "</th>\n";
    $retval .= "            <th>" . __('Type') . "</th>\n";
    $retval .= "            <th>" . __('Length/Values') . "</th>\n";
    $retval .= "            <th colspan='2'>" . __('Options') . "</th>\n";
    $retval .= "            <th class='routine_param_remove hide'>&nbsp;</th>\n";
    $retval .= "        </tr>";
    $retval .= "        </thead>\n";
    $retval .= "        <tbody>\n";
    for ($i = 0; $i < $routine['item_num_params']; $i++) { // each parameter
        $retval .= PMA_RTN_getParameterRow($routine, $i, $isprocedure_class);
    }
    $retval .= "        </tbody>\n";
    $retval .= "        </table>";
    $retval .= "    </td>";
    $retval .= "</tr>";
    $retval .= "<tr>";
    $retval .= "    <td>&nbsp;</td>";
    $retval .= "    <td>";
    $retval .= "        <input style='width: 49%;' type='button'";
    $retval .= "               name='routine_addparameter'";
    $retval .= "               value='" . __('Add parameter') . "' />";
    $retval .= "        <input style='width: 49%;" . $disableRemoveParam . "'";
    $retval .= "               type='submit' ";
    $retval .= "               name='routine_removeparameter'";
    $retval .= "               value='" . __('Remove last parameter') . "' />";
    $retval .= "    </td>";
    $retval .= "</tr>";
    // parameter handling end
    $retval .= "<tr class='routine_return_row" . $isfunction_class . "'>";
    $retval .= "    <td>" . __('Return type') . "</td>";
    $retval .= "    <td><select name='item_returntype'>";
    $retval .= PMA\libraries\Util::getSupportedDatatypes(true, $routine['item_returntype']);
    $retval .= "    </select></td>";
    $retval .= "</tr>";
    $retval .= "<tr class='routine_return_row" . $isfunction_class . "'>";
    $retval .= "    <td>" . __('Return length/values') . "</td>";
    $retval .= "    <td><input type='text' name='item_returnlength'";
    $retval .= "        value='" . $routine['item_returnlength'] . "' /></td>";
    $retval .= "    <td class='hide no_len'>---</td>";
    $retval .= "</tr>";
    $retval .= "<tr class='routine_return_row" . $isfunction_class . "'>";
    $retval .= "    <td>" . __('Return options') . "</td>";
    $retval .= "    <td><div>";
    $retval .= PMA_generateCharsetDropdownBox(
        PMA_CSDROPDOWN_CHARSET,
        "item_returnopts_text",
        null,
        $routine['item_returnopts_text']
    );
    $retval .= "    </div>";
    $retval .= "    <div><select name='item_returnopts_num'>";
    $retval .= "        <option value=''></option>";
    foreach ($param_opts_num as $key => $value) {
        $selected = "";
        if (! empty($routine['item_returnopts_num'])
            && $routine['item_returnopts_num'] == $value
        ) {
            $selected = " selected='selected'";
        }
        $retval .= "<option" . $selected . ">" . $value . "</option>";
    }
    $retval .= "    </select></div>";
    $retval .= "    <div class='hide no_opts'>---</div>";
    $retval .= "</td>";
    $retval .= "</tr>";
    $retval .= "<tr>";
    $retval .= "    <td>" . __('Definition') . "</td>";
    $retval .= "    <td><textarea name='item_definition' rows='15' cols='40'>";
    $retval .= $routine['item_definition'];
    $retval .= "</textarea></td>";
    $retval .= "</tr>";
    $retval .= "<tr>";
    $retval .= "    <td>" . __('Is deterministic') . "</td>";
    $retval .= "    <td><input type='checkbox' name='item_isdeterministic'"
        . $routine['item_isdeterministic'] . " /></td>";
    $retval .= "</tr>";
    if (isset($_REQUEST['edit_item'])
        && ! empty($_REQUEST['edit_item'])
    ) {
        $retval .= "<tr>";
        $retval .= "    <td>" . __('Adjust privileges');
        $retval .= PMA\libraries\Util::showDocu('faq', 'faq6-39');
        $retval .= "</td>";
        if ($GLOBALS['proc_priv']
            && $GLOBALS['is_reload_priv']
        ) {
            $retval .= "    <td><input type='checkbox' "
                . "name='item_adjust_privileges' value='1' checked /></td>";
        } else {
            $retval .= "    <td><input type='checkbox' "
                . "name='item_adjust_privileges' value='1' title='" . __(
                    "You do not have sufficient privileges to perform this "
                    . "operation; Please refer to the documentation for more "
                    . "details"
                )
                . "' disabled/></td>";
        }
        $retval .= "</tr>";
    }

    $retval .= "<tr>";
    $retval .= "    <td>" . __('Definer') . "</td>";
    $retval .= "    <td><input type='text' name='item_definer'";
    $retval .= "               value='" . $routine['item_definer'] . "' /></td>";
    $retval .= "</tr>";
    $retval .= "<tr>";
    $retval .= "    <td>" . __('Security type') . "</td>";
    $retval .= "    <td><select name='item_securitytype'>";
    $retval .= "        <option value='DEFINER'"
        . $routine['item_securitytype_definer'] . ">DEFINER</option>";
    $retval .= "        <option value='INVOKER'"
        . $routine['item_securitytype_invoker'] . ">INVOKER</option>";
    $retval .= "    </select></td>";
    $retval .= "</tr>";
    $retval .= "<tr>";
    $retval .= "    <td>" . __('SQL data access') . "</td>";
    $retval .= "    <td><select name='item_sqldataaccess'>";
    foreach ($param_sqldataaccess as $key => $value) {
        $selected = "";
        if ($routine['item_sqldataaccess'] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "        <option" . $selected . ">" . $value . "</option>";
    }
    $retval .= "    </select></td>";
    $retval .= "</tr>";
    $retval .= "<tr>";
    $retval .= "    <td>" . __('Comment') . "</td>";
    $retval .= "    <td><input type='text' name='item_comment' maxlength='64'";
    $retval .= "    value='" . $routine['item_comment'] . "' /></td>";
    $retval .= "</tr>";
    $retval .= "</table>";
    $retval .= "</fieldset>";
    if ($GLOBALS['is_ajax_request']) {
        $retval .= "<input type='hidden' name='editor_process_" . $mode . "'";
        $retval .= "       value='true' />";
        $retval .= "<input type='hidden' name='ajax_request' value='true' />";
    } else {
        $retval .= "<fieldset class='tblFooters'>";
        $retval .= "    <input type='submit' name='editor_process_" . $mode . "'";
        $retval .= "           value='" . __('Go') . "' />";
        $retval .= "</fieldset>";
    }
    $retval .= "</form>";
    $retval .= "<!-- END " . mb_strtoupper($mode) . " ROUTINE FORM -->";

    return $retval;
} // end PMA_RTN_getEditorForm()

/**
 * Composes the query necessary to create a routine from an HTTP request.
 *
 * @return string  The CREATE [ROUTINE | PROCEDURE] query.
 */
function PMA_RTN_getQueryFromRequest()
{
    global $_REQUEST, $errors, $param_sqldataaccess, $param_directions, $PMA_Types;

    $_REQUEST['item_type'] = isset($_REQUEST['item_type'])
        ? $_REQUEST['item_type'] : '';

    $query = 'CREATE ';
    if (! empty($_REQUEST['item_definer'])) {
        if (mb_strpos($_REQUEST['item_definer'], '@') !== false) {
            $arr = explode('@', $_REQUEST['item_definer']);
            $query .= 'DEFINER=' . PMA\libraries\Util::backquote($arr[0]);
            $query .= '@' . PMA\libraries\Util::backquote($arr[1]) . ' ';
        } else {
            $errors[] = __('The definer must be in the "username@hostname" format!');
        }
    }
    if ($_REQUEST['item_type'] == 'FUNCTION'
        || $_REQUEST['item_type'] == 'PROCEDURE'
    ) {
        $query .= $_REQUEST['item_type'] . ' ';
    } else {
        $errors[] = sprintf(
            __('Invalid routine type: "%s"'),
            htmlspecialchars($_REQUEST['item_type'])
        );
    }
    if (! empty($_REQUEST['item_name'])) {
        $query .= PMA\libraries\Util::backquote($_REQUEST['item_name']);
    } else {
        $errors[] = __('You must provide a routine name!');
    }
    $params = '';
    $warned_about_dir    = false;
    $warned_about_length = false;

    if (! empty($_REQUEST['item_param_name'])
        && ! empty($_REQUEST['item_param_type'])
        && ! empty($_REQUEST['item_param_length'])
        && is_array($_REQUEST['item_param_name'])
        && is_array($_REQUEST['item_param_type'])
        && is_array($_REQUEST['item_param_length'])
    ) {
        $item_param_name = $_REQUEST['item_param_name'];
        $item_param_type = $_REQUEST['item_param_type'];
        $item_param_length = $_REQUEST['item_param_length'];

        for ($i=0, $nb = count($item_param_name); $i < $nb; $i++) {
            if (! empty($item_param_name[$i])
                && ! empty($item_param_type[$i])
            ) {
                if ($_REQUEST['item_type'] == 'PROCEDURE'
                    && ! empty($_REQUEST['item_param_dir'][$i])
                    && in_array($_REQUEST['item_param_dir'][$i], $param_directions)
                ) {
                    $params .= $_REQUEST['item_param_dir'][$i] . " "
                        . PMA\libraries\Util::backquote($item_param_name[$i])
                        . " " . $item_param_type[$i];
                } else if ($_REQUEST['item_type'] == 'FUNCTION') {
                    $params .= PMA\libraries\Util::backquote($item_param_name[$i])
                        . " " . $item_param_type[$i];
                } else if (! $warned_about_dir) {
                    $warned_about_dir = true;
                    $errors[] = sprintf(
                        __('Invalid direction "%s" given for parameter.'),
                        htmlspecialchars($_REQUEST['item_param_dir'][$i])
                    );
                }
                if ($item_param_length[$i] != ''
                    && !preg_match(
                        '@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|'
                        . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|'
                        . 'SERIAL|BOOLEAN)$@i',
                        $item_param_type[$i]
                    )
                ) {
                    $params .= "(" . $item_param_length[$i] . ")";
                } else if ($item_param_length[$i] == ''
                    && preg_match(
                        '@^(ENUM|SET|VARCHAR|VARBINARY)$@i',
                        $item_param_type[$i]
                    )
                ) {
                    if (! $warned_about_length) {
                        $warned_about_length = true;
                        $errors[] = __(
                            'You must provide length/values for routine parameters'
                            . ' of type ENUM, SET, VARCHAR and VARBINARY.'
                        );
                    }
                }
                if (! empty($_REQUEST['item_param_opts_text'][$i])) {
                    if ($PMA_Types->getTypeClass($item_param_type[$i]) == 'CHAR') {
                        $params .= ' CHARSET '
                            . mb_strtolower(
                                $_REQUEST['item_param_opts_text'][$i]
                            );
                    }
                }
                if (! empty($_REQUEST['item_param_opts_num'][$i])) {
                    if ($PMA_Types->getTypeClass($item_param_type[$i]) == 'NUMBER') {
                        $params .= ' '
                            . mb_strtoupper(
                                $_REQUEST['item_param_opts_num'][$i]
                            );
                    }
                }
                if ($i != (count($item_param_name) - 1)) {
                    $params .= ", ";
                }
            } else {
                $errors[] = __(
                    'You must provide a name and a type for each routine parameter.'
                );
                break;
            }
        }
    }
    $query .= "(" . $params . ") ";
    if ($_REQUEST['item_type'] == 'FUNCTION') {
        $item_returntype = isset($_REQUEST['item_returntype'])
            ? $_REQUEST['item_returntype']
            : null;

        if (! empty($item_returntype)
            && in_array(
                $item_returntype, PMA\libraries\Util::getSupportedDatatypes()
            )
        ) {
            $query .= "RETURNS " . $item_returntype;
        } else {
            $errors[] = __('You must provide a valid return type for the routine.');
        }
        if (! empty($_REQUEST['item_returnlength'])
            && !preg_match(
                '@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|'
                . 'MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT|SERIAL|BOOLEAN)$@i',
                $item_returntype
            )
        ) {
            $query .= "(" . $_REQUEST['item_returnlength'] . ")";
        } else if (empty($_REQUEST['item_returnlength'])
            && preg_match(
                '@^(ENUM|SET|VARCHAR|VARBINARY)$@i', $item_returntype
            )
        ) {
            if (! $warned_about_length) {
                $errors[] = __(
                    'You must provide length/values for routine parameters'
                    . ' of type ENUM, SET, VARCHAR and VARBINARY.'
                );
            }
        }
        if (! empty($_REQUEST['item_returnopts_text'])) {
            if ($PMA_Types->getTypeClass($item_returntype) == 'CHAR') {
                $query .= ' CHARSET '
                    . mb_strtolower($_REQUEST['item_returnopts_text']);
            }
        }
        if (! empty($_REQUEST['item_returnopts_num'])) {
            if ($PMA_Types->getTypeClass($item_returntype) == 'NUMBER') {
                $query .= ' '
                    . mb_strtoupper($_REQUEST['item_returnopts_num']);
            }
        }
        $query .= ' ';
    }
    if (! empty($_REQUEST['item_comment'])) {
        $query .= "COMMENT '" . PMA\libraries\Util::sqlAddslashes($_REQUEST['item_comment'])
            . "' ";
    }
    if (isset($_REQUEST['item_isdeterministic'])) {
        $query .= 'DETERMINISTIC ';
    } else {
        $query .= 'NOT DETERMINISTIC ';
    }
    if (! empty($_REQUEST['item_sqldataaccess'])
        && in_array($_REQUEST['item_sqldataaccess'], $param_sqldataaccess)
    ) {
        $query .= $_REQUEST['item_sqldataaccess'] . ' ';
    }
    if (! empty($_REQUEST['item_securitytype'])) {
        if ($_REQUEST['item_securitytype'] == 'DEFINER'
            || $_REQUEST['item_securitytype'] == 'INVOKER'
        ) {
            $query .= 'SQL SECURITY ' . $_REQUEST['item_securitytype'] . ' ';
        }
    }
    if (! empty($_REQUEST['item_definition'])) {
        $query .= $_REQUEST['item_definition'];
    } else {
        $errors[] = __('You must provide a routine definition.');
    }

    return $query;
} // end PMA_RTN_getQueryFromRequest()

/**
 * Handles requests for executing a routine
 *
 * @return void
 */
function PMA_RTN_handleExecute()
{
    global $_GET, $_POST, $_REQUEST, $GLOBALS, $db;

    /**
     * Handle all user requests other than the default of listing routines
     */
    if (! empty($_REQUEST['execute_routine']) && ! empty($_REQUEST['item_name'])) {
        // Build the queries
        $routine = PMA_RTN_getDataFromName(
            $_REQUEST['item_name'], $_REQUEST['item_type'], false
        );
        if ($routine === false) {
            $message  = __('Error in processing request:') . ' ';
            $message .= sprintf(
                PMA_RTE_getWord('not_found'),
                htmlspecialchars(PMA\libraries\Util::backquote($_REQUEST['item_name'])),
                htmlspecialchars(PMA\libraries\Util::backquote($db))
            );
            $message = Message::error($message);
            if ($GLOBALS['is_ajax_request']) {
                $response = PMA\libraries\Response::getInstance();
                $response->setRequestStatus(false);
                $response->addJSON('message', $message);
                exit;
            } else {
                echo $message->getDisplay();
                unset($_POST);
            }
        }

        $queries   = array();
        $end_query = array();
        $args      = array();
        $all_functions = $GLOBALS['PMA_Types']->getAllFunctions();
        for ($i = 0; $i < $routine['item_num_params']; $i++) {
            if (isset($_REQUEST['params'][$routine['item_param_name'][$i]])) {
                $value = $_REQUEST['params'][$routine['item_param_name'][$i]];
                if (is_array($value)) { // is SET type
                    $value = implode(',', $value);
                }
                $value = PMA\libraries\Util::sqlAddSlashes($value);
                if (! empty($_REQUEST['funcs'][$routine['item_param_name'][$i]])
                    && in_array(
                        $_REQUEST['funcs'][$routine['item_param_name'][$i]],
                        $all_functions
                    )
                ) {
                    $queries[] = "SET @p$i="
                        . $_REQUEST['funcs'][$routine['item_param_name'][$i]]
                        . "('$value');\n";
                } else {
                    $queries[] = "SET @p$i='$value';\n";
                }
                $args[] = "@p$i";
            } else {
                $args[] = "@p$i";
            }
            if ($routine['item_type'] == 'PROCEDURE') {
                if ($routine['item_param_dir'][$i] == 'OUT'
                    || $routine['item_param_dir'][$i] == 'INOUT'
                ) {
                    $end_query[] = "@p$i AS "
                        . PMA\libraries\Util::backquote($routine['item_param_name'][$i]);
                }
            }
        }
        if ($routine['item_type'] == 'PROCEDURE') {
            $queries[] = "CALL " . PMA\libraries\Util::backquote($routine['item_name'])
                       . "(" . implode(', ', $args) . ");\n";
            if (count($end_query)) {
                $queries[] = "SELECT " . implode(', ', $end_query) . ";\n";
            }
        } else {
            $queries[] = "SELECT " . PMA\libraries\Util::backquote($routine['item_name'])
                       . "(" . implode(', ', $args) . ") "
                       . "AS " . PMA\libraries\Util::backquote($routine['item_name'])
                        . ";\n";
        }

        // Get all the queries as one SQL statement
        $multiple_query = implode("", $queries);

        $outcome = true;
        $affected = 0;

        // Execute query
        if (! $GLOBALS['dbi']->tryMultiQuery($multiple_query)) {
            $outcome = false;
        }

        // Generate output
        if ($outcome) {

            // Pass the SQL queries through the "pretty printer"
            $output  = PMA\libraries\Util::formatSql(implode($queries, "\n"));

            // Display results
            $output .= "<fieldset><legend>";
            $output .= sprintf(
                __('Execution results of routine %s'),
                PMA\libraries\Util::backquote(htmlspecialchars($routine['item_name']))
            );
            $output .= "</legend>";

            $nbResultsetToDisplay = 0;

            do {

                $result = $GLOBALS['dbi']->storeResult();
                $num_rows = $GLOBALS['dbi']->numRows($result);

                if (($result !== false) && ($num_rows > 0)) {

                    $output .= "<table><tr>";
                    foreach ($GLOBALS['dbi']->getFieldsMeta($result) as $field) {
                        $output .= "<th>";
                        $output .= htmlspecialchars($field->name);
                        $output .= "</th>";
                    }
                    $output .= "</tr>";

                    $color_class = 'odd';

                    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                        $output .= "<tr>" . browseRow($row, $color_class) . "</tr>";
                        $color_class = ($color_class == 'odd') ? 'even' : 'odd';
                    }

                    $output .= "</table>";
                    $nbResultsetToDisplay++;
                    $affected = $num_rows;

                }

                if (! $GLOBALS['dbi']->moreResults()) {
                    break;
                }

                $output .= "<br/>";

                $GLOBALS['dbi']->freeResult($result);

            } while ($GLOBALS['dbi']->nextResult());

            $output .= "</fieldset>";

            $message = __('Your SQL query has been executed successfully.');
            if ($routine['item_type'] == 'PROCEDURE') {
                $message .= '<br />';

                // TODO : message need to be modified according to the
                // output from the routine
                $message .= sprintf(
                    _ngettext(
                        '%d row affected by the last statement inside the '
                        . 'procedure.',
                        '%d rows affected by the last statement inside the '
                        . 'procedure.',
                        $affected
                    ),
                    $affected
                );
            }
            $message = Message::success($message);

            if ($nbResultsetToDisplay == 0) {
                $notice = __(
                    'MySQL returned an empty result set (i.e. zero rows).'
                );
                $output .= Message::notice($notice)->getDisplay();
            }

        } else {
            $output = '';
            $message = Message::error(
                sprintf(
                    __('The following query has failed: "%s"'),
                    htmlspecialchars($multiple_query)
                )
                . '<br /><br />'
                . __('MySQL said: ') . $GLOBALS['dbi']->getError(null)
            );
        }

        // Print/send output
        if ($GLOBALS['is_ajax_request']) {
            $response = PMA\libraries\Response::getInstance();
            $response->setRequestStatus($message->isSuccess());
            $response->addJSON('message', $message->getDisplay() . $output);
            $response->addJSON('dialog', false);
            exit;
        } else {
            echo $message->getDisplay() , $output;
            if ($message->isError()) {
                // At least one query has failed, so shouldn't
                // execute any more queries, so we quit.
                exit;
            }
            unset($_POST);
            // Now deliberately fall through to displaying the routines list
        }
        return;
    } else if (! empty($_GET['execute_dialog']) && ! empty($_GET['item_name'])) {
        /**
         * Display the execute form for a routine.
         */
        $routine = PMA_RTN_getDataFromName(
            $_GET['item_name'], $_GET['item_type'], true
        );
        if ($routine !== false) {
            $form = PMA_RTN_getExecuteForm($routine);
            if ($GLOBALS['is_ajax_request'] == true) {
                $title = __("Execute routine") . " " . PMA\libraries\Util::backquote(
                    htmlentities($_GET['item_name'], ENT_QUOTES)
                );
                $response = PMA\libraries\Response::getInstance();
                $response->addJSON('message', $form);
                $response->addJSON('title', $title);
                $response->addJSON('dialog', true);
            } else {
                echo "\n\n<h2>" . __("Execute routine") . "</h2>\n\n";
                echo $form;
            }
            exit;
        } else if (($GLOBALS['is_ajax_request'] == true)) {
            $message  = __('Error in processing request:') . ' ';
            $message .= sprintf(
                PMA_RTE_getWord('not_found'),
                htmlspecialchars(PMA\libraries\Util::backquote($_REQUEST['item_name'])),
                htmlspecialchars(PMA\libraries\Util::backquote($db))
            );
            $message = Message::error($message);

            $response = PMA\libraries\Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $message);
            exit;
        }
    }
}

/**
 * Browse row array
 *
 * @param array  $row         Columns
 * @param string $color_class CSS class
 *
 * @return string
 */
function browseRow($row, $color_class)
{
    $output = null;
    foreach ($row as $value) {
        if ($value === null) {
            $value = '<i>NULL</i>';
        } else {
            $value = htmlspecialchars($value);
        }
        $output .= "<td class='" . $color_class . "'>" . $value . "</td>";
    }
    return $output;
}

/**
 * Creates the HTML code that shows the routine execution dialog.
 *
 * @param array $routine Data for the routine returned by
 *                       PMA_RTN_getDataFromName()
 *
 * @return string   HTML code for the routine execution dialog.
 */
function PMA_RTN_getExecuteForm($routine)
{
    global $db, $cfg;

    // Escape special characters
    $routine['item_name'] = htmlentities($routine['item_name'], ENT_QUOTES);
    for ($i = 0; $i < $routine['item_num_params']; $i++) {
        $routine['item_param_name'][$i] = htmlentities(
            $routine['item_param_name'][$i],
            ENT_QUOTES
        );
    }

    // Create the output
    $retval  = "";
    $retval .= "<!-- START ROUTINE EXECUTE FORM -->\n\n";
    $retval .= "<form action='db_routines.php' method='post'\n";
    $retval .= "       class='rte_form ajax' onsubmit='return false'>\n";
    $retval .= "<input type='hidden' name='item_name'\n";
    $retval .= "       value='{$routine['item_name']}' />\n";
    $retval .= "<input type='hidden' name='item_type'\n";
    $retval .= "       value='{$routine['item_type']}' />\n";
    $retval .= PMA_URL_getHiddenInputs($db) . "\n";
    $retval .= "<fieldset>\n";
    if ($GLOBALS['is_ajax_request'] != true) {
        $retval .= "<legend>{$routine['item_name']}</legend>\n";
        $retval .= "<table class='rte_table'>\n";
        $retval .= "<caption class='tblHeaders'>\n";
        $retval .= __('Routine parameters');
        $retval .= "</caption>\n";
    } else {
        $retval .= "<legend>" . __('Routine parameters') . "</legend>\n";
        $retval .= "<table class='rte_table' style='width: 100%;'>\n";
    }
    $retval .= "<tr>\n";
    $retval .= "<th>" . __('Name') . "</th>\n";
    $retval .= "<th>" . __('Type') . "</th>\n";
    if ($cfg['ShowFunctionFields']) {
        $retval .= "<th>" . __('Function') . "</th>\n";
    }
    $retval .= "<th>" . __('Value')    . "</th>\n";
    $retval .= "</tr>\n";
    // Get a list of data types that are not yet supported.
    $no_support_types = PMA\libraries\Util::unsupportedDatatypes();
    for ($i = 0; $i < $routine['item_num_params']; $i++) { // Each parameter
        if ($routine['item_type'] == 'PROCEDURE'
            && $routine['item_param_dir'][$i] == 'OUT'
        ) {
            continue;
        }
        $rowclass = ($i % 2 == 0) ? 'even' : 'odd';
        $retval .= "\n<tr class='$rowclass'>\n";
        $retval .= "<td>{$routine['item_param_name'][$i]}</td>\n";
        $retval .= "<td>{$routine['item_param_type'][$i]}</td>\n";
        if ($cfg['ShowFunctionFields']) {
            $retval .= "<td>\n";
            if (stristr($routine['item_param_type'][$i], 'enum')
                || stristr($routine['item_param_type'][$i], 'set')
                || in_array(
                    mb_strtolower($routine['item_param_type'][$i]),
                    $no_support_types
                )
            ) {
                $retval .= "--\n";
            } else {
                $field = array(
                    'True_Type'       => mb_strtolower(
                        $routine['item_param_type'][$i]
                    ),
                    'Type'            => '',
                    'Key'             => '',
                    'Field'           => '',
                    'Default'         => '',
                    'first_timestamp' => false
                );
                $retval .= "<select name='funcs["
                    . $routine['item_param_name'][$i] . "]'>";
                $retval .= PMA\libraries\Util::getFunctionsForField($field, false);
                $retval .= "</select>";
            }
            $retval .= "</td>\n";
        }
        // Append a class to date/time fields so that
        // jQuery can attach a datepicker to them
        $class = '';
        if ($routine['item_param_type'][$i] == 'DATETIME'
            || $routine['item_param_type'][$i] == 'TIMESTAMP'
        ) {
            $class = 'datetimefield';
        } else if ($routine['item_param_type'][$i] == 'DATE') {
            $class = 'datefield';
        }
        $retval .= "<td class='nowrap'>\n";
        if (in_array($routine['item_param_type'][$i], array('ENUM', 'SET'))) {
            if ($routine['item_param_type'][$i] == 'ENUM') {
                $input_type = 'radio';
            } else {
                $input_type = 'checkbox';
            }
            foreach ($routine['item_param_length_arr'][$i] as $value) {
                $value = htmlentities(PMA\libraries\Util::unquote($value), ENT_QUOTES);
                $retval .= "<input name='params["
                    . $routine['item_param_name'][$i] . "][]' "
                    . "value='" . $value . "' type='"
                    . $input_type . "' />"
                    . $value . "<br />\n";
            }
        } else if (in_array(
            mb_strtolower($routine['item_param_type'][$i]),
            $no_support_types
        )) {
            $retval .= "\n";
        } else {
            $retval .= "<input class='$class' type='text' name='params["
                . $routine['item_param_name'][$i] . "]' />\n";
        }
        $retval .= "</td>\n";
        $retval .= "</tr>\n";
    }
    $retval .= "\n</table>\n";
    if ($GLOBALS['is_ajax_request'] != true) {
        $retval .= "</fieldset>\n\n";
        $retval .= "<fieldset class='tblFooters'>\n";
        $retval .= "    <input type='submit' name='execute_routine'\n";
        $retval .= "           value='" . __('Go') . "' />\n";
        $retval .= "</fieldset>\n";
    } else {
        $retval .= "<input type='hidden' name='execute_routine' value='true' />";
        $retval .= "<input type='hidden' name='ajax_request' value='true' />";
    }
    $retval .= "</form>\n\n";
    $retval .= "<!-- END ROUTINE EXECUTE FORM -->\n\n";

    return $retval;
} // end PMA_RTN_getExecuteForm()

