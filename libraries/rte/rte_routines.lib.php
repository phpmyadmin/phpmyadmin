<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for routine management.
 *
 * @package PhpMyAdmin
 */
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
    $columns  = "`SPECIFIC_NAME`, `ROUTINE_NAME`, `ROUTINE_TYPE`, ";
    $columns .= "`DTD_IDENTIFIER`, `ROUTINE_DEFINITION`";
    $where    = "ROUTINE_SCHEMA " . PMA_Util::getCollateForIS() . "="
        . "'" . PMA_Util::sqlAddSlashes($db) . "'";
    if (PMA_isValid($type, array('FUNCTION','PROCEDURE'))) {
        $where .= " AND `ROUTINE_TYPE`='" . $type . "'";
    }
    $items    = $GLOBALS['dbi']->fetchResult(
        "SELECT $columns FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE $where;"
    );
    echo PMA_RTE_getList('routine', $items);
    /**
     * Display the form for adding a new routine, if the user has the privileges.
     */
    echo PMA_RTN_getFooterLinks();
    /**
     * Display a warning for users with PHP's old "mysql" extension.
     */
    if (! PMA_DatabaseInterface::checkDbExtension('mysqli')) {
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
 * This function parses a string containing one parameter of a routine,
 * as returned by PMA_RTN_parseAllParameters() and returns an array containing
 * the information about this parameter.
 *
 * @param string $value A string containing one parameter of a routine
 *
 * @return array             Parsed information about the input parameter
 */
function PMA_RTN_parseOneParameter($value)
{
    global $param_directions;

    $retval = array(0 => '',
                    1 => '',
                    2 => '',
                    3 => '',
                    4 => '');
    $parsed_param = PMA_SQP_parse($value);
    $pos = 0;
    if (in_array(strtoupper($parsed_param[$pos]['data']), $param_directions)) {
        $retval[0] = strtoupper($parsed_param[0]['data']);
        $pos++;
    }
    if ($parsed_param[$pos]['type'] == 'alpha_identifier'
        || $parsed_param[$pos]['type'] == 'quote_backtick'
    ) {
        $retval[1] = PMA_Util::unQuote(
            $parsed_param[$pos]['data']
        );
        $pos++;
    }
    $depth = 0;
    $param_length = '';
    $param_opts = array();
    for ($i=$pos; $i<$parsed_param['len']; $i++) {
        if (($parsed_param[$i]['type'] == 'alpha_columnType'
            || $parsed_param[$i]['type'] == 'alpha_functionName') && $depth == 0
        ) {
            $retval[2] = strtoupper($parsed_param[$i]['data']);
        } else if ($parsed_param[$i]['type'] == 'punct_bracket_open_round'
            && $depth == 0
        ) {
            $depth = 1;
        } else if ($parsed_param[$i]['type'] == 'punct_bracket_close_round'
            && $depth == 1
        ) {
            $depth = 0;
        } else if ($depth == 1) {
            $param_length .= $parsed_param[$i]['data'];
        } else if ($parsed_param[$i]['type'] == 'alpha_reservedWord'
            && strtoupper($parsed_param[$i]['data']) == 'CHARSET' && $depth == 0
        ) {
            if ($parsed_param[$i+1]['type'] == 'alpha_charset'
                || $parsed_param[$i+1]['type'] == 'alpha_identifier'
            ) {
                $param_opts[] = strtolower($parsed_param[$i+1]['data']);
            }
        } else if ($parsed_param[$i]['type'] == 'alpha_columnAttrib'
            && $depth == 0
        ) {
            $param_opts[] = strtoupper($parsed_param[$i]['data']);
        }
    }
    $retval[3] = $param_length;
    sort($param_opts);
    $retval[4] = implode(' ', $param_opts);

    return $retval;
} // end PMA_RTN_parseOneParameter()

/**
 * This function looks through the contents of a parsed
 * SHOW CREATE [PROCEDURE | FUNCTION] query and extracts
 * information about the routine's parameters.
 *
 * @param array  $parsed_query Parsed query, returned by by PMA_SQP_parse()
 * @param string $routine_type Routine type: 'PROCEDURE' or 'FUNCTION'
 *
 * @return array   Information about the parameteres of a routine.
 */
function PMA_RTN_parseAllParameters($parsed_query, $routine_type)
{
    $retval = array();
    $retval['num'] = 0;

    // First get the list of parameters from the query
    $buffer = '';
    $params = array();
    $fetching = false;
    $depth = 0;
    for ($i=0; $i<$parsed_query['len']; $i++) {
        if ($parsed_query[$i]['type'] == 'alpha_reservedWord'
            && $parsed_query[$i]['data'] == $routine_type
        ) {
            $fetching = true;
        } else if ($fetching == true
            && $parsed_query[$i]['type'] == 'punct_bracket_open_round'
        ) {
            $depth++;
            if ($depth > 1) {
                $buffer .= $parsed_query[$i]['data'] . ' ';
            }
        } else if ($fetching == true
            && $parsed_query[$i]['type'] == 'punct_bracket_close_round'
        ) {
            $depth--;
            if ($depth > 0) {
                $buffer .= $parsed_query[$i]['data'] . ' ';
            } else {
                break;
            }
        } else if ($parsed_query[$i]['type'] == 'punct_listsep' && $depth == 1) {
            $params[] = $buffer;
            $retval['num']++;
            $buffer = '';
        } else if ($fetching == true && $depth > 0) {
            $buffer .= $parsed_query[$i]['data'] . ' ';
        }
    }
    if (! empty($buffer)) {
        $params[] = $buffer;
        $retval['num']++;
    }
    // Now parse each parameter individually
    foreach ($params as $key => $value) {
        list($retval['dir'][],
             $retval['name'][],
             $retval['type'][],
             $retval['length'][],
             $retval['opts'][]) = PMA_RTN_parseOneParameter($value);
    }
    // Since some indices of $retval may be still undefined, we fill
    // them each with an empty array to avoid E_ALL errors in PHP.
    foreach (array('dir', 'name', 'type', 'length', 'opts') as $key => $index) {
        if (! isset($retval[$index])) {
            $retval[$index] = array();
        }
    }

    return $retval;
} // end PMA_RTN_parseAllParameters()

/**
 * This function looks through the contents of a parsed
 * SHOW CREATE [PROCEDURE | FUNCTION] query and extracts
 * information about the routine's definer.
 *
 * @param array $parsed_query Parsed query, returned by PMA_SQP_parse()
 *
 * @return string  The definer of a routine.
 */
function PMA_RTN_parseRoutineDefiner($parsed_query)
{
    $retval = '';
    $fetching = false;
    for ($i=0; $i<$parsed_query['len']; $i++) {
        if ($parsed_query[$i]['type'] == 'alpha_reservedWord'
            && $parsed_query[$i]['data'] == 'DEFINER'
        ) {
            $fetching = true;
        } else if ($fetching == true
            && $parsed_query[$i]['type'] != 'quote_backtick'
            && substr($parsed_query[$i]['type'], 0, 5) != 'punct'
        ) {
            break;
        } else if ($fetching == true
            && $parsed_query[$i]['type'] == 'quote_backtick'
        ) {
            $retval .= PMA_Util::unQuote(
                $parsed_query[$i]['data']
            );
        } else if ($fetching == true && $parsed_query[$i]['type'] == 'punct_user') {
            $retval .= $parsed_query[$i]['data'];
        }
    }
    return $retval;
} // end PMA_RTN_parseRoutineDefiner()

/**
 * Handles editor requests for adding or editing an item
 *
 * @return void
 */
function PMA_RTN_handleEditor()
{
    global $_GET, $_POST, $_REQUEST, $GLOBALS, $db, $errors;

    if (! empty($_REQUEST['editor_process_add'])
        || ! empty($_REQUEST['editor_process_edit'])
    ) {
        /**
         * Handle a request to create/edit a routine
         */
        $sql_query = '';
        $routine_query = PMA_RTN_getQueryFromRequest();
        if (! count($errors)) { // set by PMA_RTN_getQueryFromRequest()
            // Execute the created query
            if (! empty($_REQUEST['editor_process_edit'])) {
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
                        $db, $_REQUEST['item_original_type'],
                        $_REQUEST['item_original_name']
                    );
                    $drop_routine = "DROP {$_REQUEST['item_original_type']} "
                        . PMA_Util::backquote($_REQUEST['item_original_name'])
                        . ";\n";
                    $result = $GLOBALS['dbi']->tryQuery($drop_routine);
                    if (! $result) {
                        $errors[] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($drop_routine)
                        )
                        . '<br />'
                        . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);
                    } else {
                        $result = $GLOBALS['dbi']->tryQuery($routine_query);
                        if (! $result) {
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
                            if (! $result) {
                                // OMG, this is really bad! We dropped the query,
                                // failed to create a new one
                                // and now even the backup query does not execute!
                                // This should not happen, but we better handle
                                // this just in case.
                                $errors[] = __(
                                    'Sorry, we failed to restore'
                                    . ' the dropped routine.'
                                )
                                . '<br />'
                                . __('The backed up query was:')
                                . "\"" . htmlspecialchars($create_routine) . "\""
                                . '<br />'
                                . __('MySQL said: ')
                                . $GLOBALS['dbi']->getError(null);
                            }
                        } else {
                            $message = PMA_Message::success(
                                __('Routine %1$s has been modified.')
                            );
                            $message->addParam(
                                PMA_Util::backquote($_REQUEST['item_name'])
                            );
                            $sql_query = $drop_routine . $routine_query;
                        }
                    }
                }
            } else {
                // 'Add a new routine' mode
                $result = $GLOBALS['dbi']->tryQuery($routine_query);
                if (! $result) {
                    $errors[] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($routine_query)
                    )
                    . '<br /><br />'
                    . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);
                } else {
                    $message = PMA_Message::success(
                        __('Routine %1$s has been created.')
                    );
                    $message->addParam(
                        PMA_Util::backquote($_REQUEST['item_name'])
                    );
                    $sql_query = $routine_query;
                }
            }
        }

        if (count($errors)) {
            $message = PMA_Message::error(
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

        $output = PMA_Util::getMessage($message, $sql_query);
        if ($GLOBALS['is_ajax_request']) {
            $response = PMA_Response::getInstance();
            if ($message->isSuccess()) {
                $columns  = "`SPECIFIC_NAME`, `ROUTINE_NAME`, `ROUTINE_TYPE`,"
                    . " `DTD_IDENTIFIER`, `ROUTINE_DEFINITION`";
                $where    = "ROUTINE_SCHEMA " . PMA_Util::getCollateForIS() . "="
                    . "'" . PMA_Util::sqlAddSlashes($db) . "' "
                    . "AND ROUTINE_NAME='"
                    . PMA_Util::sqlAddSlashes($_REQUEST['item_name']) . "'"
                    . "AND ROUTINE_TYPE='"
                    . PMA_Util::sqlAddSlashes($_REQUEST['item_type']) . "'";
                $routine  = $GLOBALS['dbi']->fetchSingleRow(
                    "SELECT $columns FROM `INFORMATION_SCHEMA`.`ROUTINES`"
                    . " WHERE $where;"
                );
                $response->addJSON(
                    'name', htmlspecialchars(strtoupper($_REQUEST['item_name']))
                );
                $response->addJSON('new_row', PMA_RTN_getRowForList($routine));
                $response->addJSON('insert', ! empty($routine));
                $response->addJSON('message', $output);
            } else {
                $response->isSuccess(false);
                $response->addJSON('message', $output);
            }
            exit;
        }
    }

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
                $response = PMA_Response::getInstance();
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
                htmlspecialchars(PMA_Util::backquote($_REQUEST['item_name'])),
                htmlspecialchars(PMA_Util::backquote($db))
            );
            $message = PMA_message::error($message);
            if ($GLOBALS['is_ajax_request']) {
                $response->isSuccess(false);
                $response->addJSON('message', $message);
                exit;
            } else {
                $message->display();
            }
        }
    }
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
    foreach ($indices as $key => $index) {
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
    if (   isset($_REQUEST['item_param_name'])
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
            if (! in_array($value, PMA_Util::getSupportedDatatypes(), true)) {
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
        && in_array($_REQUEST['item_returntype'], PMA_Util::getSupportedDatatypes())
    ) {
        $retval['item_returntype'] = $_REQUEST['item_returntype'];
    }

    $retval['item_isdeterministic'] = '';
    if (isset($_REQUEST['item_isdeterministic'])
        && strtolower($_REQUEST['item_isdeterministic']) == 'on'
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
    $where   = "ROUTINE_SCHEMA " . PMA_Util::getCollateForIS() . "="
             . "'" . PMA_Util::sqlAddSlashes($db) . "' "
             . "AND SPECIFIC_NAME='" . PMA_Util::sqlAddSlashes($name) . "'"
             . "AND ROUTINE_TYPE='" . PMA_Util::sqlAddSlashes($type) . "'";
    $query   = "SELECT $fields FROM INFORMATION_SCHEMA.ROUTINES WHERE $where;";

    $routine = $GLOBALS['dbi']->fetchSingleRow($query);

    if (! $routine) {
        return false;
    }

    // Get required data
    $retval['item_name'] = $routine['SPECIFIC_NAME'];
    $retval['item_type'] = $routine['ROUTINE_TYPE'];
    $parsed_query = PMA_SQP_parse(
        $GLOBALS['dbi']->getDefinition(
            $db,
            $routine['ROUTINE_TYPE'],
            $routine['SPECIFIC_NAME']
        )
    );
    $params = PMA_RTN_parseAllParameters($parsed_query, $routine['ROUTINE_TYPE']);
    $retval['item_num_params']      = $params['num'];
    $retval['item_param_dir']       = $params['dir'];
    $retval['item_param_name']      = $params['name'];
    $retval['item_param_type']      = $params['type'];
    $retval['item_param_length']    = $params['length'];
    $retval['item_param_opts_num']  = $params['opts'];
    $retval['item_param_opts_text'] = $params['opts'];

    // Get extra data
    if (!$all) {
        return $retval;
    }

    if ($retval['item_type'] == 'FUNCTION') {
        $retval['item_type_toggle'] = 'PROCEDURE';
    } else {
        $retval['item_type_toggle'] = 'FUNCTION';
    }
    $retval['item_returntype']   = '';
    $retval['item_returnlength'] = '';
    $retval['item_returnopts_num']  = '';
    $retval['item_returnopts_text'] = '';
    if (! empty($routine['DTD_IDENTIFIER'])) {
        if (strlen($routine['DTD_IDENTIFIER']) > 63) {
            // If the DTD_IDENTIFIER string from INFORMATION_SCHEMA is
            // at least 64 characters, then it may actually have been
            // chopped because that column is a varchar(64), so we will
            // parse the output of SHOW CREATE query to get accurate
            // information about the return variable.
            $dtd = '';
            $fetching = false;
            for ($i=0; $i<$parsed_query['len']; $i++) {
                if ($parsed_query[$i]['type'] == 'alpha_reservedWord'
                    && strtoupper($parsed_query[$i]['data']) == 'RETURNS'
                ) {
                    $fetching = true;
                } else if ($fetching == true
                    && $parsed_query[$i]['type'] == 'alpha_reservedWord'
                ) {
                    // We will not be looking for options such as UNSIGNED
                    // or ZEROFILL because there is no way that a numeric
                    // field's DTD_IDENTIFIER can be longer than 64
                    // characters. We can safely assume that the return
                    // datatype is either ENUM or SET, so we only look
                    // for CHARSET.
                    $word = strtoupper($parsed_query[$i]['data']);
                    if ($word == 'CHARSET'
                        && ($parsed_query[$i+1]['type'] == 'alpha_charset'
                        || $parsed_query[$i+1]['type'] == 'alpha_identifier')
                    ) {
                        $dtd .= $word . ' ' . $parsed_query[$i+1]['data'];
                    }
                    break;
                } else if ($fetching == true) {
                    $dtd .= $parsed_query[$i]['data'] . ' ';
                }
            }
            $routine['DTD_IDENTIFIER'] = $dtd;
        }
        $returnparam = PMA_RTN_parseOneParameter($routine['DTD_IDENTIFIER']);
        $retval['item_returntype']      = $returnparam[2];
        $retval['item_returnlength']    = $returnparam[3];
        $retval['item_returnopts_num']  = $returnparam[4];
        $retval['item_returnopts_text'] = $returnparam[4];
    }

    $retval['item_definer'] = PMA_RTN_parseRoutineDefiner($parsed_query);
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
    $retval .= PMA_Util::getSupportedDatatypes(
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
        . PMA_Util::getImage('b_edit', '', array('title'=>__('ENUM/SET editor')))
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
 * @param string $mode      If the editor will be used edit a routine
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
    for ($i=0; $i<$routine['item_num_params']; $i++) {
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
        unset($routine['item_param_dir'][$routine['item_num_params']-1]);
        unset($routine['item_param_name'][$routine['item_num_params']-1]);
        unset($routine['item_param_type'][$routine['item_num_params']-1]);
        unset($routine['item_param_length'][$routine['item_num_params']-1]);
        unset($routine['item_param_opts_num'][$routine['item_num_params']-1]);
        unset($routine['item_param_opts_text'][$routine['item_num_params']-1]);
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
    $retval .= "<!-- START " . strtoupper($mode) . " ROUTINE FORM -->\n\n";
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
            . "<div style='width: 49%; float: left; text-align: center;"
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
    $retval .= "        <tr>\n";
    $retval .= "            <th class='routine_direction_cell$isprocedure_class'>"
        . __('Direction') . "</th>\n";
    $retval .= "            <th>" . __('Name') . "</th>\n";
    $retval .= "            <th>" . __('Type') . "</th>\n";
    $retval .= "            <th>" . __('Length/Values') . "</th>\n";
    $retval .= "            <th colspan='2'>" . __('Options') . "</th>\n";
    $retval .= "            <th class='routine_param_remove hide'>&nbsp;</th>\n";
    $retval .= "        </tr>";
    for ($i=0; $i<$routine['item_num_params']; $i++) { // each parameter
        $retval .= PMA_RTN_getParameterRow($routine, $i, $isprocedure_class);
    }
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
    $retval .= PMA_Util::getSupportedDatatypes(true, $routine['item_returntype']);
    $retval .= "    </select></td>";
    $retval .= "</tr>";
    $retval .= "<tr class='routine_return_row" . $isfunction_class . "'>";
    $retval .= "    <td>" . __('Return length/values') . "</td>";
    $retval .= "    <td><input type='text' name='item_returnlength'";
    $retval .= "               value='" . $routine['item_returnlength'] . "' /></td>";
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
    $retval .= "<!-- END " . strtoupper($mode) . " ROUTINE FORM -->";

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
        if (strpos($_REQUEST['item_definer'], '@') !== false) {
            $arr = explode('@', $_REQUEST['item_definer']);
            $query .= 'DEFINER=' . PMA_Util::backquote($arr[0]);
            $query .= '@' . PMA_Util::backquote($arr[1]) . ' ';
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
        $query .= PMA_Util::backquote($_REQUEST['item_name']);
    } else {
        $errors[] = __('You must provide a routine name!');
    }
    $params = '';
    $warned_about_dir    = false;
    $warned_about_name   = false;
    $warned_about_length = false;

    if (   ! empty($_REQUEST['item_param_name'])
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
                        . PMA_Util::backquote($item_param_name[$i])
                        . " " . $item_param_type[$i];
                } else if ($_REQUEST['item_type'] == 'FUNCTION') {
                    $params .= PMA_Util::backquote($item_param_name[$i])
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
                            . strtolower($_REQUEST['item_param_opts_text'][$i]);
                    }
                }
                if (! empty($_REQUEST['item_param_opts_num'][$i])) {
                    if ($PMA_Types->getTypeClass($item_param_type[$i]) == 'NUMBER') {
                        $params .= ' '
                            . strtoupper($_REQUEST['item_param_opts_num'][$i]);
                    }
                }
                if ($i != (count($item_param_name) - 1)) {
                    $params .= ", ";
                }
            } else if (! $warned_about_name) {
                $warned_about_name = true;
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
                $item_returntype, PMA_Util::getSupportedDatatypes()
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
                $warned_about_length = true;
                $errors[] = __(
                    'You must provide length/values for routine parameters'
                    . ' of type ENUM, SET, VARCHAR and VARBINARY.'
                );
            }
        }
        if (! empty($_REQUEST['item_returnopts_text'])) {
            if ($PMA_Types->getTypeClass($item_returntype) == 'CHAR') {
                $query .= ' CHARSET '
                    . strtolower($_REQUEST['item_returnopts_text']);
            }
        }
        if (! empty($_REQUEST['item_returnopts_num'])) {
            if ($PMA_Types->getTypeClass($item_returntype) == 'NUMBER') {
                $query .= ' ' . strtoupper($_REQUEST['item_returnopts_num']);
            }
        }
        $query .= ' ';
    }
    if (! empty($_REQUEST['item_comment'])) {
        $query .= "COMMENT '" . PMA_Util::sqlAddslashes($_REQUEST['item_comment'])
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
                htmlspecialchars(PMA_Util::backquote($_REQUEST['item_name'])),
                htmlspecialchars(PMA_Util::backquote($db))
            );
            $message = PMA_message::error($message);
            if ($GLOBALS['is_ajax_request']) {
                $response = PMA_Response::getInstance();
                $response->isSuccess(false);
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
        for ($i=0; $i<$routine['item_num_params']; $i++) {
            if (isset($_REQUEST['params'][$routine['item_param_name'][$i]])) {
                $value = $_REQUEST['params'][$routine['item_param_name'][$i]];
                if (is_array($value)) { // is SET type
                    $value = implode(',', $value);
                }
                $value = PMA_Util::sqlAddSlashes($value);
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
                        . PMA_Util::backquote($routine['item_param_name'][$i]);
                }
            }
        }
        if ($routine['item_type'] == 'PROCEDURE') {
            $queries[] = "CALL " . PMA_Util::backquote($routine['item_name'])
                       . "(" . implode(', ', $args) . ");\n";
            if (count($end_query)) {
                $queries[] = "SELECT " . implode(', ', $end_query) . ";\n";
            }
        } else {
            $queries[] = "SELECT " . PMA_Util::backquote($routine['item_name'])
                       . "(" . implode(', ', $args) . ") "
                       . "AS " . PMA_Util::backquote($routine['item_name'])
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
            $output  = PMA_Util::formatSql(implode($queries, "\n"));

            // Display results
            $output .= "<fieldset><legend>";
            $output .= sprintf(
                __('Execution results of routine %s'),
                PMA_Util::backquote(htmlspecialchars($routine['item_name']))
            );
            $output .= "</legend>";

            $nbResultsetToDisplay = 0;

            do {

                $result = $GLOBALS['dbi']->storeResult();
                $num_rows = $GLOBALS['dbi']->numRows($result);

                if (($result !== false) && ($num_rows > 0)) {

                    $output .= "<table><tr>";
                    foreach ($GLOBALS['dbi']->getFieldsMeta($result)
                        as $key => $field) {
                        $output .= "<th>";
                        $output .= htmlspecialchars($field->name);
                        $output .= "</th>";
                    }
                    $output .= "</tr>";

                    $color_class = 'odd';

                    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                        $output .= "<tr>";
                        foreach ($row as $key => $value) {
                            if ($value === null) {
                                $value = '<i>NULL</i>';
                            } else {
                                $value = htmlspecialchars($value);
                            }
                            $output .= "<td class='" . $color_class . "'>"
                                . $value . "</td>";
                        }
                        $output .= "</tr>";
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
                        '%d row affected by the last statement inside the procedure.',
                        '%d rows affected by the last statement inside the '
                        . 'procedure.',
                        $affected
                    ),
                    $affected
                );
            }
            $message = PMA_message::success($message);

            if ($nbResultsetToDisplay == 0) {
                $notice = __(
                    'MySQL returned an empty result set (i.e. zero rows).'
                );
                $output .= PMA_message::notice($notice)->getDisplay();
            }

        } else {
            $output = '';
            $message = PMA_message::error(
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
            $response = PMA_Response::getInstance();
            $response->isSuccess($message->isSuccess());
            $response->addJSON('message', $message->getDisplay() . $output);
            $response->addJSON('dialog', false);
            exit;
        } else {
            echo $message->getDisplay() . $output;
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
                $title = __("Execute routine") . " " . PMA_Util::backquote(
                    htmlentities($_GET['item_name'], ENT_QUOTES)
                );
                $response = PMA_Response::getInstance();
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
                htmlspecialchars(PMA_Util::backquote($_REQUEST['item_name'])),
                htmlspecialchars(PMA_Util::backquote($db))
            );
            $message = PMA_message::error($message);

            $response = PMA_Response::getInstance();
            $response->isSuccess(false);
            $response->addJSON('message', $message);
            exit;
        }
    }
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
    for ($i=0; $i<$routine['item_num_params']; $i++) {
        $routine['item_param_name'][$i] = htmlentities(
            $routine['item_param_name'][$i],
            ENT_QUOTES
        );
    }

    // Create the output
    $retval  = "";
    $retval .= "<!-- START ROUTINE EXECUTE FORM -->\n\n";
    $retval .= "<form action='db_routines.php' method='post' class='rte_form ajax' onsubmit='return false'>\n";
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
    $no_support_types = PMA_Util::unsupportedDatatypes();
    for ($i=0; $i<$routine['item_num_params']; $i++) { // Each parameter
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
                    strtolower($routine['item_param_type'][$i]), $no_support_types
                )
            ) {
                $retval .= "--\n";
            } else {
                $field = array(
                    'True_Type'       => strtolower($routine['item_param_type'][$i]),
                    'Type'            => '',
                    'Key'             => '',
                    'Field'           => '',
                    'Default'         => '',
                    'first_timestamp' => false
                );
                $retval .= "<select name='funcs["
                    . $routine['item_param_name'][$i] . "]'>";
                $retval .= PMA_Util::getFunctionsForField($field, false);
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
            $tokens = PMA_SQP_parse($routine['item_param_length'][$i]);
            if ($routine['item_param_type'][$i] == 'ENUM') {
                $input_type = 'radio';
            } else {
                $input_type = 'checkbox';
            }
            for ($j=0; $j<$tokens['len']; $j++) {
                if ($tokens[$j]['type'] != 'punct_listsep') {
                    $tokens[$j]['data'] = htmlentities(
                        PMA_Util::unquote($tokens[$j]['data']),
                        ENT_QUOTES
                    );
                    $retval .= "<input name='params["
                        . $routine['item_param_name'][$i] . "][]' "
                        . "value='" . $tokens[$j]['data'] . "' type='"
                        . $input_type . "' />"
                        . $tokens[$j]['data'] . "<br />\n";
                }
            }
        } else if (in_array(
            strtolower($routine['item_param_type'][$i]), $no_support_types
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

?>
