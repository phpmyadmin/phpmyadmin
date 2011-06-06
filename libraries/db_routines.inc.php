<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @todo Support seeing the "results" of the called procedure or
 *       function. This needs further reseach because a procedure
 *       does not necessarily contain a SELECT statement that
 *       produces something to see. But it seems we could at least
 *       get the number of rows affected. We would have to
 *       use the CLIENT_MULTI_RESULTS flag to get the result set
 *       and also the call status. All this does not fit well with
 *       our current sql.php.
 *       Of course the interface would need a way to pass calling parameters.
 *       Also, support DEFINER (like we do in export).
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// Some definitions
$param_directions    = array('IN', 'OUT', 'INOUT');
$param_sqldataaccess = array('',
                             'NO SQL',
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
 * This function processes the datatypes supported by the DB, as specified in
 * $cfg['ColumnTypes'] and either returns an array (useful for quickly checking
 * if a datatype is supported) or an HTML snippet that creates a drop-down list.
 *
 * @param   bool    $html       Whether to generate an html snippet or an array
 * @param   string  $selected   The value to mark as selected in HTML mode
 *
 * @return  mixed   An HTML snippet or an array of datatypes.
 *
 * @uses    htmlspecialchars()
 * @uses    in_array()
 */
function getSupportedDatatypes($html = false, $selected = '')
{
    global $cfg;

    if ($html) {
        // NOTE: the SELECT tag in not included in this snippet.
        $retval = '';
        foreach ($cfg['ColumnTypes'] as $key => $value) {
            if (is_array($value)) {
                $retval .= "<optgroup label='" . htmlspecialchars($key) . "'>";
                foreach ($value as $subkey => $subvalue) {
                    if ($subvalue == $selected) {
                        $retval .= "<option selected='selected'>";
                        $retval .= $subvalue;
                        $retval .= "</option>";
                    } else if ($subvalue === '-') {
                        $retval .= "<option disabled='disabled'>";
                        $retval .= $subvalue;
                        $retval .= "</option>";
                    } else {
                        $retval .= "<option>$subvalue</option>";
                    }
                }
                $retval .= '</optgroup>';
            } else {
                if ($selected == $value) {
                    $retval .= "<option selected='selected'>$value</option>";
                } else {
                    $retval .= "<option>$value</option>";
                }
            }
        }
    } else {
        $retval = array();
        foreach ($cfg['ColumnTypes'] as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    if ($subvalue !== '-') {
                        $retval[] = $subvalue;
                    }
                }
            } else {
                if ($value !== '-') {
                    $retval[] = $value;
                }
            }
        }
    }

    return $retval;
} // end getSupportedDatatypes()

/**
 * This function looks through the contents of a parsed
 * SHOW CREATE [PROCEDURE | FUNCTION] query and extracts
 * information about the routine's parameters.
 *
 * @param   array   $parsed_query  Parsed query, returned by by PMA_SQP_parse()
 * @param   string  $routine_type  Routine type: 'PROCEDURE' or 'FUNCTION'
 *
 * @return  array   Information about the parameteres of a routine.
 *
 * @uses    isset()
 * @uses    PMA_SQP_parse()
 * @uses    PMA_unQuote()
 * @uses    htmlspecialchars()
 * @uses    htmlentities()
 */
function getRoutineParameters($parsed_query, $routine_type)
{
    global $param_directions;

    $retval = array();
    $retval['num'] = 0;

    // First get the list of parameters from the query
    $buffer = '';
    $params = array();
    $fetching = false;
    $depth = 0;
    for ($i=0; $i<$parsed_query['len']; $i++) {
        if ($parsed_query[$i]['type'] == 'alpha_reservedWord' && $parsed_query[$i]['data'] == $routine_type) {
            $fetching = true;
        } else if ($fetching == true && $parsed_query[$i]['type'] == 'punct_bracket_open_round') {
            $depth++;
            if ($depth > 1) {
                $buffer .= $parsed_query[$i]['data'] . ' ';
            }
        } else if ($fetching == true && $parsed_query[$i]['type'] == 'punct_bracket_close_round') {
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
        $parsed_param = PMA_SQP_parse($value);
        $pos = 0;
        if (in_array($parsed_param[$pos]['data'], $param_directions)) {
            $retval['dir'][] = $parsed_param[0]['data'];
            $pos++;
        }
        if ($parsed_param[$pos]['type'] == 'alpha_identifier' || $parsed_param[$pos]['type'] == 'quote_backtick') {
            $retval['name'][] = htmlspecialchars(
                                    PMA_unQuote(
                                        $parsed_param[$pos]['data']
                                    )
                                );
            $pos++;
        }
        $depth = 0;
        $param_length = '';
        for ($i=$pos; $i<$parsed_param['len']; $i++) {
            if ($parsed_param[$i]['type'] == 'alpha_columnType' && $depth == 0) {
                $retval['type'][] = $parsed_param[$i]['data'];
            } else if ($parsed_param[$i]['type'] == 'punct_bracket_open_round' && $depth == 0) {
                $depth = 1;
            } else if ($parsed_param[$i]['type'] == 'punct_bracket_close_round' && $depth == 1) {
                $depth = 0;
            } else if ($depth == 1) {
                $param_length .= $parsed_param[$i]['data'];
            }
        }
        $retval['length'][] = htmlentities($param_length, ENT_QUOTES);
        // FIXME: parameter attributes, such as 'UNSIGNED', are currenly ignored
    }

    // Since some indices of $retval may be still undefined, we fill
    // them each with an empty array to avoid E_ALL errors in PHP.
    foreach (array('dir', 'name', 'type', 'length') as $key => $index) {
        if (! isset($retval[$index])) {
            $retval[$index] = array();
        }
    }

    return $retval;
} // end getRoutineParameters()

/**
 * This function looks through the contents of a parsed
 * SHOW CREATE [PROCEDURE | FUNCTION] query and extracts
 * information about the routine's definer.
 *
 * @param   array   $parsed_query  Parsed query, returned by by PMA_SQP_parse()
 *
 * @return  string  The definer of a routine.
 *
 * @uses    substr()
 * @uses    PMA_unQuote()
 */
function getRoutineDefiner($parsed_query)
{
    $retval = '';
    $fetching = false;
    for ($i=0; $i<$parsed_query['len']; $i++) {
        if ($parsed_query[$i]['type'] == 'alpha_reservedWord' && $parsed_query[$i]['data'] == 'DEFINER') {
            $fetching = true;
        } else if ($fetching == true &&
                  ($parsed_query[$i]['type'] != 'quote_backtick' && substr($parsed_query[$i]['type'], 0, 5) != 'punct')) {
            break;
        } else if ($fetching == true && $parsed_query[$i]['type'] == 'quote_backtick') {
            $retval .= PMA_unQuote($parsed_query[$i]['data']);
        } else if ($fetching == true && $parsed_query[$i]['type'] == 'punct_user') {
            $retval .= $parsed_query[$i]['data'];
        }
    }
    return $retval;
} // end getRoutineDefiner()

/**
 * This function will generate the values that are required to complete
 * the "Edit routine" form given the name of a routine.
 *
 * @param   string   $db     The database that the routine belogs to.
 * @param   string   $name   The name of the routine.
 * @param   bool     $all    Whether to return all data or just
 *                           the info about parameters.
 *
 * @return  array    Data necessary to create the routine editor.
 *
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_fetch_result()
 * @uses    PMA_SQP_parse()
 * @uses    PMA_PMA_DBI_get_definition()
 * @uses    getRoutineParameters()
 * @uses    getRoutineDefiner()
 * @uses    strpos()
 * @uses    strrpos()
 * @uses    strtoupper()
 * @uses    trim()
 * @uses    substr()
 * @uses    htmlentities()
 */
function getFormInputFromRoutineName($db, $name, $all = true)
{
    global $_REQUEST, $param_directions, $param_sqldataaccess;

    $retval  = array();

    // Build and execute the query
    $fields  = "SPECIFIC_NAME, ROUTINE_TYPE, DTD_IDENTIFIER, "
             . "ROUTINE_DEFINITION, IS_DETERMINISTIC, SQL_DATA_ACCESS, "
             . "ROUTINE_COMMENT, SECURITY_TYPE";
    $where   = "ROUTINE_SCHEMA='" . PMA_sqlAddslashes($db,true) . "' "
             . "AND SPECIFIC_NAME='" . PMA_sqlAddslashes($name,true) . "'";
    $query   = "SELECT $fields FROM INFORMATION_SCHEMA.ROUTINES WHERE $where;";
    $routine = PMA_DBI_fetch_single_row($query);

    // Get required data
    $retval['name'] = $routine['SPECIFIC_NAME'];
    $retval['type'] = $routine['ROUTINE_TYPE'];
    $parsed_query = PMA_SQP_parse(
                        PMA_DBI_get_definition(
                            $db,
                            $routine['ROUTINE_TYPE'],
                            $routine['SPECIFIC_NAME']
                        )
                    );
    $params = getRoutineParameters($parsed_query, $routine['ROUTINE_TYPE']);
    $retval['num_params']   = $params['num'];
    $retval['param_dir']    = $params['dir'];
    $retval['param_name']   = $params['name'];
    $retval['param_type']   = $params['type'];
    $retval['param_length'] = $params['length'];

    if ($all) {
        if ($retval['type'] == 'FUNCTION') {
            $retval['type_toggle'] = 'PROCEDURE';
        } else {
            $retval['type_toggle'] = 'FUNCTION';
        }
        $retval['returntype']   = '';
        $retval['returnlength'] = '';
        if (! empty($routine['DTD_IDENTIFIER'])) {
            $brac1_pos = strpos($routine['DTD_IDENTIFIER'], '(');
            $brac2_pos = strrpos($routine['DTD_IDENTIFIER'], ')');
            if ($brac1_pos !== false && $brac2_pos !== false) {
                $retval['returntype']   = strtoupper(
                                              trim(
                                                  substr(
                                                      $routine['DTD_IDENTIFIER'],
                                                      0,
                                                      $brac1_pos
                                                  )
                                              )
                                          );
                $retval['returnlength'] = htmlentities(
                                              trim(
                                                  substr(
                                                      $routine['DTD_IDENTIFIER'],
                                                      $brac1_pos+1,
                                                      $brac2_pos-$brac1_pos-1
                                                  )
                                              ),
                                              ENT_QUOTES
                                          );
            } else {
                $retval['returntype'] = strtoupper($routine['DTD_IDENTIFIER']);
            }
        }
        $retval['definer']         = getRoutineDefiner($parsed_query);
        $retval['definition']      = $routine['ROUTINE_DEFINITION'];
        $retval['isdeterministic'] = '';
        if ($routine['IS_DETERMINISTIC'] == 'YES') {
            $retval['isdeterministic'] = " checked='checked'";
        }
        $retval['securitytype_definer'] = '';
        $retval['securitytype_invoker'] = '';
        if ($routine['SECURITY_TYPE'] == 'DEFINER') {
            $retval['securitytype_definer'] = " selected='selected'";
        } else if ($routine['SECURITY_TYPE'] == 'INVOKER') {
            $retval['securitytype_invoker'] = " selected='selected'";
        }
        $retval['sqldataaccess'] = $routine['SQL_DATA_ACCESS'];
        $retval['comment']       = $routine['ROUTINE_COMMENT'];
    }

    return $retval;
} // getFormInputFromRoutineName()

/**
 * This function will generate the values that are required to complete the "Add new routine" form
 * It is especially necessary to handle the 'Add another parameter', 'Remove last parameter'
 * and 'Change routine type' functionalities when JS is disabled.
 *
 * @return  array    Data necessary to create the routine editor.
 *
 * @uses    htmlspecialchars()
 * @uses    isset()
 * @uses    is_array()
 * @uses    in_array()
 * @uses    htmlentities()
 * @uses    htmlspecialchars()
 * @uses    strtolower()
 */
function getFormInputFromRequest()
{
    global $_REQUEST, $param_directions, $param_sqldataaccess;

    $retval = array();
    $retval['name'] = '';
    if (isset($_REQUEST['routine_name'])) {
        $retval['name'] = htmlspecialchars($_REQUEST['routine_name']);
    }
    $retval['original_name'] = '';
    if (isset($_REQUEST['routine_original_name'])) {
         $retval['original_name'] = htmlspecialchars($_REQUEST['routine_original_name']);
    }
    $retval['type']         = 'PROCEDURE';
    $retval['type_toggle']  = 'FUNCTION';
    if (isset($_REQUEST['routine_type']) && $_REQUEST['routine_type'] == 'FUNCTION') {
        $retval['type']         = 'FUNCTION';
        $retval['type_toggle']  = 'PROCEDURE';
    }
    $retval['original_type'] = 'PROCEDURE';
    if (isset($_REQUEST['routine_original_type']) && $_REQUEST['routine_original_type'] == 'FUNCTION') {
        $retval['original_type'] = 'FUNCTION';
    }
    $retval['num_params']   = 0;
    $retval['param_dir']    = array();
    $retval['param_name']   = array();
    $retval['param_type']   = array();
    $retval['param_length'] = array();
    if (isset($_REQUEST['routine_param_name'])
        && isset($_REQUEST['routine_param_type'])
        && isset($_REQUEST['routine_param_length'])
        && is_array($_REQUEST['routine_param_name'])
        && is_array($_REQUEST['routine_param_type'])
        && is_array($_REQUEST['routine_param_length'])) {

        if ($_REQUEST['routine_type'] == 'PROCEDURE') {
            $temp_num_params = 0;
            $retval['param_dir'] = $_REQUEST['routine_param_dir'];
            foreach ($retval['param_dir'] as $key => $value) {
                if (! in_array($value, $param_directions, true)) {
                    $retval['param_dir'][$key] = '';
                }
                $retval['num_params']++;
            }
            if ($temp_num_params > $retval['num_params']) {
                $retval['num_params'] = $temp_num_params;
            }
        }
        $temp_num_params = 0;
        $retval['param_name'] = $_REQUEST['routine_param_name'];
        foreach ($retval['param_name'] as $key => $value) {
            $retval['param_name'][$key] = htmlentities($value, ENT_QUOTES);
            $temp_num_params++;
        }
        if ($temp_num_params > $retval['num_params']) {
            $retval['num_params'] = $temp_num_params;
        }
        $temp_num_params = 0;
        $retval['param_type'] = $_REQUEST['routine_param_type'];
        foreach ($retval['param_type'] as $key => $value) {
            if (! in_array($value, getSupportedDatatypes(), true)) {
                $retval['param_type'][$key] = '';
            }
            $temp_num_params++;
        }
        if ($temp_num_params > $retval['num_params']) {
            $retval['num_params'] = $temp_num_params;
        }
        $temp_num_params = 0;
        $retval['param_length'] = $_REQUEST['routine_param_length'];
        foreach ($retval['param_length'] as $key => $value) {
            $retval['param_length'][$key] = htmlentities($value, ENT_QUOTES);
            $temp_num_params++;
        }
        if ($temp_num_params > $retval['num_params']) {
            $retval['num_params'] = $temp_num_params;
        }
    }
    $retval['returntype'] = '';
    if (isset($_REQUEST['routine_returntype']) && in_array($_REQUEST['routine_returntype'], getSupportedDatatypes(), true)) {
        $retval['returntype'] = $_REQUEST['routine_returntype'];
    }
    $retval['returnlength'] = '';
    if (isset($_REQUEST['routine_returnlength'])) {
        $retval['returnlength'] = htmlentities($_REQUEST['routine_returnlength'], ENT_QUOTES);
    }
    $retval['definition'] = '';
    if (isset($_REQUEST['routine_definition'])) {
        $retval['definition'] = htmlspecialchars($_REQUEST['routine_definition']);
    }
    $retval['isdeterministic'] = '';
    if (isset($_REQUEST['routine_isdeterministic']) && strtolower($_REQUEST['routine_isdeterministic']) == 'on') {
        $retval['isdeterministic'] = " checked='checked'";
    }
    $retval['definer'] = '';
    if (isset($_REQUEST['routine_definer'])) {
        $retval['definer'] = htmlentities($_REQUEST['routine_definer'], ENT_QUOTES);
    }
    $retval['securitytype_definer'] = '';
    $retval['securitytype_invoker'] = '';
    if (isset($_REQUEST['routine_securitytype'])) {
        if ($_REQUEST['routine_securitytype'] === 'DEFINER') {
            $retval['securitytype_definer'] = " selected='selected'";
        } else if ($_REQUEST['routine_securitytype'] === 'INVOKER') {
            $retval['securitytype_invoker'] = " selected='selected'";
        }
    }
    $retval['sqldataaccess'] = '';
    if (isset($_REQUEST['routine_sqldataaccess']) && in_array($_REQUEST['routine_sqldataaccess'], $param_sqldataaccess, true)) {
        $retval['sqldataaccess'] = $_REQUEST['routine_sqldataaccess'];
    }
    $retval['comment'] = '';
    if (isset($_REQUEST['routine_comment'])) {
        $retval['comment'] = htmlentities($_REQUEST['routine_comment'], ENT_QUOTES);
    }

    return $retval;
} // end function getFormInputFromRequest()

/**
 * Displays a form used to add/edit a routine
 *
 * @param   string   $mode         If the editor will be used edit a routine
 *                                 or add a new one: 'edit' or 'add'.
 * @param   string   $operation    If the editor was previously invoked with
 *                                 JS turned off, this will hold the name of
 *                                 the current operation: 'add', remove', 'change'
 * @param   array    $routine      Data for the routine returned by
 *                                 getFormInputFromRequest() or
 *                                 getFormInputFromRoutineName()
 * @param   array    $errors       If the editor was already invoked and there
 *                                 has been an error while processing the request
 *                                 this array will hold the errors.
 *
 * @return  string   HTML code for the routine editor.
 *
 * @uses    PMA_message
 * @uses    PMA_generate_common_hidden_inputs()
 * @uses    count()
 * @uses    strtoupper()
 * @uses    sprintf()
 * @uses    getSupportedDatatypes()
 */
function displayRoutineEditor($mode, $operation, $routine, $errors) {
    global $db, $table, $url_query, $param_directions, $param_sqldataaccess;

    // Handle some logic first
    if ($operation == 'change') {
        if ($routine['type'] == 'PROCEDURE') {
            $routine['type']        = 'FUNCTION';
            $routine['type_toggle'] = 'PROCEDURE';
        } else {
            $routine['type']        = 'PROCEDURE';
            $routine['type_toggle'] = 'FUNCTION';
        }
    } else if ($operation == 'add' || ($routine['num_params'] == 0 && $mode == 'add' && ! $errors)) {
        $routine['param_dir'][]  = '';
        $routine['param_name'][] = '';
        $routine['param_type'][] = '';
        $routine['param_length'][] = '';
        $routine['num_params']++;
    } else if ($operation == 'remove') {
        unset($routine['param_dir'][$routine['num_params']-1]);
        unset($routine['param_name'][$routine['num_params']-1]);
        unset($routine['param_type'][$routine['num_params']-1]);
        unset($routine['param_length'][$routine['num_params']-1]);
        $routine['num_params']--;
    }
    $disable_remove_parameter = '';
    if (! $routine['num_params']) {
        $disable_remove_parameter = " color: gray;' disabled='disabled";
    }
    $original_routine = '';
    if ($mode == 'edit') {
        $original_routine = "<input name='routine_original_name' "
                          . "type='hidden' value='{$routine['original_name']}'/>\n"
                          . "<input name='routine_original_type' "
                          . "type='hidden' value='{$routine['original_type']}'/>\n";
    }
    $isfunction_class = '';
    $isprocedure_class = '';
    if ($routine['type'] == 'PROCEDURE') {
        $isfunction_class = ' hide';
    } else {
        $isprocedure_class = ' hide';
    }

    // Create the output
    $retval  = "";
    $retval .= "<!-- START " . strtoupper($mode) . " ROUTINE FORM -->\n\n";
    $retval .= "<form class='rte_form' action='db_routines.php?$url_query' method='post'>\n";
    $retval .= "<input name='{$mode}routine' type='hidden' value='1' />\n";
    $retval .= $original_routine;
    $retval .= PMA_generate_common_hidden_inputs($db, $table) . "\n";
    $retval .= "<fieldset>\n";
    $retval .= "<legend>" . __('Details') . "</legend>\n";
    $retval .= "<table class='rte_table'>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Routine Name') . "</td>\n";
    $retval .= "    <td><input type='text' name='routine_name' value='{$routine['name']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Type') . "</td>\n";
    $retval .= "    <td class='routine_changetype_cell'>\n";
    $retval .= "        <input name='routine_type' type='hidden' value='{$routine['type']}' />\n";
    $retval .= "        <div style='width: 49%; float: left; text-align: center; font-weight: bold;'>\n";
    $retval .= "            {$routine['type']}\n";
    $retval .= "        </div>\n";
    $retval .= "        <input style='width: 49%;' type='submit' name='routine_changetype'\n";
    $retval .= "               value='".sprintf(__('Change to %s'), $routine['type_toggle'])."' />\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Parameters') . "</td>\n";
    $retval .= "    <td>\n";
    // parameter handling start
    $retval .= "        <table class='routine_params_table'>\n";
    $retval .= "        <tr>\n";
    $retval .= "            <th class='routine_direction_cell$isprocedure_class'>" . __('Direction') . "</th>\n";
    $retval .= "            <th>" . __('Name') . "</th>\n";
    $retval .= "            <th>" . __('Type') . "</th>\n";
    $retval .= "            <th>" . __('Length/Values') . "</th>\n";
    $retval .= "            <th class='routine_param_remove hide'>&nbsp;</th>\n";
    $retval .= "        </tr>";
    for ($i=0; $i<$routine['num_params']; $i++) { // each parameter
        $retval .= "        <tr>\n";
        $retval .= "            <td class='routine_direction_cell$isprocedure_class'><select name='routine_param_dir[$i]'>\n";
        foreach ($param_directions as $key => $value) {
            $selected = "";
            if (! empty($routine['param_dir']) && $routine['param_dir'][$i] == $value) {
                $selected = " selected='selected'";
            }
            $retval .= "                <option$selected>$value</option>\n";
        }
        $retval .= "            </select></td>\n";
        $retval .= "            <td><input name='routine_param_name[$i]' type='text'\n";
        $retval .= "                       value='{$routine['param_name'][$i]}' /></td>\n";
        $retval .= "            <td><select name='routine_param_type[$i]'>";
        $retval .= getSupportedDatatypes(true, $routine['param_type'][$i]) . "\n";
        $retval .= "            </select></td>\n";
        $retval .= "            <td><input name='routine_param_length[$i]' type='text'\n";
        $retval .= "                       value='{$routine['param_length'][$i]}' /></td>\n";
        $retval .= "            <td class='routine_param_remove hide' style='vertical-align: middle;'>\n";
        $retval .= "                <a href='#' class='routine_param_remove_anchor'>\n";
        $retval .= "                    " . PMA_getIcon('b_drop.png') . "\n";
        $retval .= "                </a>\n";
        $retval .= "            </td>\n";
        $retval .= "        </tr>\n";
    }
    $retval .= "        </table>\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>&nbsp;</td>\n";
    $retval .= "    <td>\n";
    $retval .= "        <input style='width: 49%;' type='submit' \n";
    $retval .= "               name='routine_addparameter'\n";
    $retval .= "               value='" . __('Add another parameter') . "'>\n";
    $retval .= "        <input style='width: 49%;$disable_remove_parameter' type='submit' \n";
    $retval .= "               name='routine_removeparameter'\n";
    $retval .= "               value='" . __('Remove last parameter') . "'>\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    // parameter handling end
    $retval .= "<tr class='routine_return_row$isfunction_class'>\n";
    $retval .= "    <td>" . __('Return Type') . "</td>\n";
    $retval .= "    <td><select name='routine_returntype'>\n";
    $retval .= getSupportedDatatypes(true, $routine['returntype']) . "\n";
    $retval .= "    </select></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr class='routine_return_row$isfunction_class'>\n";
    $retval .= "    <td>" . __('Return Length/Values') . "</td>\n";
    $retval .= "    <td><input type='text' name='routine_returnlength'\n";
    $retval .= "               value='{$routine['returnlength']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definition') . "</td>\n";
    $retval .= "    <td><textarea name='routine_definition'>{$routine['definition']}</textarea></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Is Deterministic') . "</td>\n";
    $retval .= "    <td><input type='checkbox' name='routine_isdeterministic'{$routine['isdeterministic']} /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definer') . "</td>\n";
    $retval .= "    <td><input type='text' name='routine_definer'\n";
    $retval .= "               value='{$routine['definer']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Security Type') . "</td>\n";
    $retval .= "    <td><select name='routine_securitytype'>\n";
    $retval .= "        <option value='DEFINER'{$routine['securitytype_definer']}>DEFINER</option>\n";
    $retval .= "        <option value='INVOKER'{$routine['securitytype_invoker']}>INVOKER</option>\n";
    $retval .= "    </select></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('SQL Data Access') . "</td>\n";
    $retval .= "    <td><select name='routine_sqldataaccess'>\n";
    foreach ($param_sqldataaccess as $key => $value) {
        $selected = "";
        if ($routine['sqldataaccess'] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "        <option$selected>$value</option>\n";
    }
    $retval .= "    </select></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Comment') . "</td>\n";
    $retval .= "    <td><input type='text' name='routine_comment'\n";
    $retval .= "               value='{$routine['comment']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "</table>\n";
    $retval .= "</fieldset>\n";
    $retval .= "<fieldset class='tblFooters routineEditorSubmit'>\n";
    $retval .= "    <input type='submit' name='routine_process_{$mode}routine'\n";
    $retval .= "           value='" . __('Go') . "' />\n";
    $retval .= "</fieldset>\n";
    $retval .= "</form>\n\n";
    $retval .= "<!-- END " . strtoupper($mode) . " ROUTINE FORM -->\n\n";

    return $retval;
} // displayRoutineEditor()

/**
 * Composes the query necessary to create a routine from an HTTP request.
 *
 * @return  string    The CREATE [ROUTINE | PROCEDURE] query.
 *
 * @uses    explode()
 * @uses    strpos()
 * @uses    PMA_backquote()
 * @uses    sprintf()
 * @uses    htmlspecialchars()
 * @uses    is_array()
 * @uses    preg_match()
 * @uses    count()
 */
function createQueryFromRequest() {
    global $_REQUEST, $routine_errors, $param_sqldataaccess;

    $query = 'CREATE ';
    if (! empty($_REQUEST['routine_definer']) && strpos($_REQUEST['routine_definer'], '@') !== false) {
        $arr = explode('@', $_REQUEST['routine_definer']);
        $query .= 'DEFINER=' . PMA_backquote($arr[0]) . '@' . PMA_backquote($arr[1]) . ' ';
    }
    if ($_REQUEST['routine_type'] == 'FUNCTION' || $_REQUEST['routine_type'] == 'PROCEDURE') {
        $query .= $_REQUEST['routine_type'] . ' ';
    } else {
        $routine_errors[] = sprintf(__('Invalid Routine Type: "%s"'), htmlspecialchars($_REQUEST['routine_type']));
    }
    if (! empty($_REQUEST['routine_name'])) {
        $query .= PMA_backquote($_REQUEST['routine_name']) . ' ';
    } else {
        $routine_errors[] = __('You must provide a routine Name');
    }
    $params = '';
    $warned_about_dir  = false;
    $warned_about_name = false;
    if ( ! empty($_REQUEST['routine_param_name']) && ! empty($_REQUEST['routine_param_type'])
        && ! empty($_REQUEST['routine_param_length']) && is_array($_REQUEST['routine_param_name'])
        && is_array($_REQUEST['routine_param_type']) && is_array($_REQUEST['routine_param_length'])) {

        for ($i=0; $i<count($_REQUEST['routine_param_name']); $i++) {
            if (! empty($_REQUEST['routine_param_name'][$i]) && ! empty($_REQUEST['routine_param_type'][$i])) {
                if ($_REQUEST['routine_type'] == 'PROCEDURE' && ! empty($_REQUEST['routine_param_dir'][$i])) {
                    $params .= $_REQUEST['routine_param_dir'][$i] . " " . PMA_backquote($_REQUEST['routine_param_name'][$i]) . " "
                            . $_REQUEST['routine_param_type'][$i];
                } else if ($_REQUEST['routine_type'] == 'FUNCTION') {
                    $params .= PMA_backquote($_REQUEST['routine_param_name'][$i]) . " " . $_REQUEST['routine_param_type'][$i];
                } else if (! $warned_about_dir) {
                    $warned_about_dir = true;
                    $routine_errors[] = sprintf(__('Invalid Direction "%s" given for a Parameter.'),
                                                htmlspecialchars($_REQUEST['routine_param_dir'][$i]));
                }
                if ($_REQUEST['routine_param_length'][$i] != ''
                    && !preg_match('@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$@i',
                                   $_REQUEST['routine_param_type'][$i])) {
                    $params .= "(" . $_REQUEST['routine_param_length'][$i] . ")";
                }
                if ($i != count($_REQUEST['routine_param_name'])-1) {
                    $params .= ", ";
                }
            } else if (! $warned_about_name) {
                $warned_about_name = true;
                $routine_errors[] = __('You must provide a Name and a Type for each routine Parameter.');
                break;
            }
        }
    }
    $query .= " (" . $params . ") ";
    if ($_REQUEST['routine_type'] == 'FUNCTION') {
        $query .= "RETURNS {$_REQUEST['routine_returntype']}";
        if (! empty($_REQUEST['routine_returnlength'])
            && !preg_match('@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$@i',
                            $_REQUEST['routine_returnlength'])) {
            $query .= "(" . $_REQUEST['routine_returnlength'] . ")";
        }
        $query .= ' ';
    }
    if (! empty($_REQUEST['routine_comment'])) {
        $query .= "COMMENT '{$_REQUEST['routine_comment']}' ";
    }
    if (isset($_REQUEST['routine_isdeterministic'])) {
        $query .= 'DETERMINISTIC ';
    } else {
        $query .= 'NOT DETERMINISTIC ';
    }
    if (! empty($_REQUEST['routine_sqldataaccess']) && in_array($_REQUEST['routine_sqldataaccess'], $param_sqldataaccess, true)) {
        $query .= $_REQUEST['routine_sqldataaccess'] . ' ';
    }
    if (! empty($_REQUEST['routine_securitytype'])) {
        if ($_REQUEST['routine_securitytype'] == 'DEFINER' || $_REQUEST['routine_securitytype'] == 'INVOKER') {
            $query .= 'SQL SECURITY ' . $_REQUEST['routine_securitytype'] . ' ';
        }
    }
    if (! empty($_REQUEST['routine_definition'])) {
        $query .= $_REQUEST['routine_definition'];
    } else {
        $routine_errors[] = __('You must provide a routine Definition.');
    }
    return $query;
} // end createQueryFromRequest()

/**
 * Creates the HTML for a row of the routines list.
 *
 * @return  string    An HTML snippet containing a row for the routines list.
 *
 * @uses    sprintf()
 * @uses    PMA_currentUserHasPrivilege()
 * @uses    PMA_backquote
 * @uses    getFormInputFromRoutineName()
 * @uses    urlencode()
 */
function routineMakeRowForList($routine, $ct = 0) {
    global $titles, $db, $url_query, $ajax_class;

    // Do the logic first
    $rowclass = ($ct % 2 == 0) ? 'even' : 'odd';
    $editlink = $titles['NoEdit'];
    $execlink = $titles['NoExecute'];
    $exprlink = $titles['NoExport'];
    $droplink = $titles['NoDrop'];
    $sql_drop = sprintf('DROP %s IF EXISTS %s',
                           $routine['ROUTINE_TYPE'],
                           PMA_backquote($routine['SPECIFIC_NAME']));
    if ($routine['ROUTINE_DEFINITION'] !== NULL
        && PMA_currentUserHasPrivilege('ALTER ROUTINE', $db)
        && PMA_currentUserHasPrivilege('CREATE ROUTINE', $db)) {
        $editlink = '<a ' . $ajax_class['edit'] . ' href="db_routines.php?' . $url_query
                          . '&amp;editroutine=1'
                          . '&amp;routine_name=' . urlencode($routine['SPECIFIC_NAME'])
                          . '">' . $titles['Edit'] . '</a>';
    }
    if (PMA_currentUserHasPrivilege('EXECUTE', $db)) {
        // Check if he routine has any input parameters. If it does,
        // we will show a dialog to get values for these parameters,
        // otherwise we can execute it directly.
        $routine_details = getFormInputFromRoutineName($db, $routine['SPECIFIC_NAME'], false);
        $execute_action = 'execute_routine';
        for ($i=0; $i<$routine_details['num_params']; $i++) {
            if ($routine_details['type'] == 'PROCEDURE' && $routine_details['param_dir'][$i] == 'OUT') {
                continue;
            }
            $execute_action = 'execute_dialog';
            break;
        }
        $execlink = '<a ' . $ajax_class['exec']. ' href="db_routines.php?' . $url_query
                          . '&amp;' . $execute_action . '=1'
                          . '&amp;routine_name=' . urlencode($routine['SPECIFIC_NAME'])
                          . '">' . $titles['Execute'] . '</a>';
    }
    if ($routine['ROUTINE_DEFINITION'] !== NULL) {
        $exprlink = '<a ' . $ajax_class['export'] . ' href="db_routines.php?' . $url_query
                          . '&amp;exportroutine=1'
                          . '&amp;routine_name=' . urlencode($routine['SPECIFIC_NAME'])
                          . '">' . $titles['Export'] . '</a>';
    }
    if (PMA_currentUserHasPrivilege('ALTER ROUTINE', $db)) {
        $droplink = '<a ' . $ajax_class['drop']. ' href="sql.php?' . $url_query
                          . '&amp;sql_query=' . urlencode($sql_drop)
                          . '" >' . $titles['Drop'] . '</a>';
    }
    // Display a row of data
    $retval  = "        <tr class='$rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>$sql_drop</span>\n";
    $retval .= "                <strong>{$routine['ROUTINE_NAME']}</strong>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>$editlink</td>\n";
    $retval .= "            <td>$execlink</td>\n";
    $retval .= "            <td>$exprlink</td>\n";
    $retval .= "            <td>$droplink</td>\n";
    $retval .= "            <td>{$routine['ROUTINE_TYPE']}</td>\n";
    $retval .= "            <td>{$routine['DTD_IDENTIFIER']}</td>\n";
    $retval .= "        </tr>\n";
    return $retval;
} // end routineMakeRowForList()

/**
 *  ### MAIN ##########################################################################################################
 */

// $url_query .= '&amp;goto=db_routines.php' . rawurlencode("?db=$db"); // FIXME

/**
 * Keep a list of errors that occured while processing an 'Add' or 'Edit' operation.
 */
$routine_errors = array();

/**
 * Handle all user requests other than the default of listing routines
 */
if (! empty($_REQUEST['execute_routine']) && ! empty($_REQUEST['routine_name'])) {
    $routine        = getFormInputFromRoutineName($db, $_REQUEST['routine_name'], false);
    $queries        = array();
    $end_query      = 'SELECT ';
    $args           = '';
    $need_end_query = false;
    if ($routine['type'] == 'FUNCTION') {
        $need_end_query = true;
        for ($i=0; $i<$routine['num_params']; $i++) {
            if (isset($_REQUEST['params'][$routine['param_name'][$i]])) {
                $value = PMA_sqladdslashes($_REQUEST['params'][$routine['param_name'][$i]]);
                $queries[] = "SET @p$i='$value';\n";
                $args  .= "@p$i";
            }
            if ($i != $routine['num_params']-1) {
                $args .= ", ";
            }
        }
        $queries[] = "SELECT " . PMA_backquote($_REQUEST['routine_name']) . "($args) "
                   . "AS " . PMA_backquote($_REQUEST['routine_name']) . ";";
    } else {
        for ($i=0; $i<$routine['num_params']; $i++) {
            if (isset($_REQUEST['params'][$routine['param_name'][$i]])) {
                $value = PMA_sqladdslashes($_REQUEST['params'][$routine['param_name'][$i]]);
                $queries[] = "SET @p$i='$value';\n";
                $args  .= "@p$i";
            } else {
                $args  .= "@p$i";
            }
            if ($i != $routine['num_params']-1) {
                $args .= ", ";
            }
            if ($routine['param_dir'][$i] == 'OUT' || $routine['param_dir'][$i] == 'INOUT') {
                $need_end_query = true;
                $end_query .= "@p$i AS " . PMA_backquote($routine['param_name'][$i]);
                if ($i != $routine['num_params']-1) {
                    $end_query .= ", ";
                }
            }
        }
        $queries[] = "CALL " . PMA_backquote($_REQUEST['routine_name']) . "($args);";
        if ($need_end_query) {
            if (substr($end_query, -2) == ", ") {
                $end_query = substr($end_query, 0, -2);
            }
            $queries[] = "$end_query;";
        }
    }

    $sql_query = '';
    foreach ($queries as $num => $query) {
        $resource = PMA_DBI_query($query, null, PMA_DBI_QUERY_STORE);
        if (! is_bool($resource)) {
            $result = $resource;
        }
        $sql_query .= $query;
    }

    // FIXME: This whole "displayTable" business is rubbish, it's probably best
    // to write the code to display the results of the query here from scratch.
    $num_rows = ($result) ? @PMA_DBI_num_rows($result) : 0;
    $unlim_num_rows = $num_rows;
    $fields_meta = PMA_DBI_get_fields_meta($result);
    $fields_cnt  = count($fields_meta);
    $disp = 'nnnn000000';
    require_once './libraries/parse_analyze.lib.php';
    require_once "display_tbl.lib.php";
    $analyzed_sql[0]['queryflags']['procedure'] = true;
    PMA_displayTable_checkConfigParams();
    PMA_displayTable($result, $disp, $analyzed_sql);

    require './libraries/footer.inc.php';
    // exit;
} else if (! empty($_GET['execute_dialog']) && ! empty($_GET['routine_name'])) {
    /**
     * Display the execute form for a routine.
     */
    if ($GLOBALS['is_ajax_request'] != true) {
        echo "\n\n<h2>" . __("Execute Routine") . "</h2>\n\n";
    }
    $routine = getFormInputFromRoutineName($db, $_GET['routine_name'], false);
    echo "<form action='db_routines.php?$url_query' method='post'>\n"
       . "<input type='hidden' name='routine_name' value='{$routine['name']}' />\n"
       . PMA_generate_common_hidden_inputs($db, $table) . "\n";
    echo '<fieldset>' . "\n"
       . "<legend>{$routine['name']}</legend>\n";
    echo "<table id='rte_table'>\n";
    echo "<caption class='tblHeaders'>\n";
    echo __('Routine Parameters');
    echo "</caption>\n";
    echo "<tr>\n";
    echo "<th>" . __('Type') . "</th>\n";
    echo "<th>" . __('Name') . "</th>\n";
    echo "<th>" . __('Value') . "</th>\n";
    echo "</tr>\n<tr>\n";
    for ($i=0; $i<$routine['num_params']; $i++) {
        if ($routine['type'] == 'PROCEDURE' && $routine['param_dir'][$i] == 'OUT') {
            continue;
        }
        $class = '';
        if (in_array($routine['param_type'][$i], array('DATETIME', 'TIMESTAMP'))) {
            $class = 'datetimefield';
        } else if ($routine['param_type'][$i] == 'DATE') {
            $class = 'datefield';
        }
        echo "\n<tr>\n";
        echo "<td>{$routine['param_type'][$i]}</td>\n";
        echo "<td>{$routine['param_name'][$i]}</td>\n";
        if ($routine['param_type'][$i] == 'ENUM') { // TODO: must also handle SET fields
            echo "<td><select name='params[{$routine['param_name'][$i]}]'>\n";
            $tokens = PMA_SQP_parse(html_entity_decode($routine['param_length'][$i], ENT_QUOTES));
            for ($i=0; $i<$tokens['len']; $i++) {
                if ($tokens[$i]['type'] != 'punct_listsep') {
                    $tokens[$i]['data'] = htmlentities(PMA_unquote($tokens[$i]['data']), ENT_QUOTES);
                    echo "<option value='{$tokens[$i]['data']}'>{$tokens[$i]['data']}</option>\n";
                }
            }
            echo "</select></td>\n";
        } else {
            echo "<td style='white-space: nowrap;'>\n";
            echo "<input class='$class' type='text' name='params[{$routine['param_name'][$i]}]' />\n";
            echo "</td>\n";
        }
        echo "</tr>\n";
    }
    echo "\n</tr></table>\n";
    echo "</fieldset>\n\n";
    echo "<fieldset class='tblFooters'>\n"
       . "    <input type='submit' name='execute_routine'\n"
       . "           value='" . __('Go') . "' />\n"
       . "</fieldset>\n"
       . "</form>\n\n";
    require './libraries/footer.inc.php';
    // exit;
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
        $create_proc = '<textarea cols="40" rows="15" style="width: 100%;">' . $create_proc . '</textarea>';
        if (! empty($_REQUEST['ajax_request'])) {
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
        if (! empty($_REQUEST['ajax_request'])) {
            PMA_ajaxResponse($response, false);
        } else {
            $response->display();
        }
    }
} else if (! empty($_REQUEST['routine_process_addroutine']) || ! empty($_REQUEST['routine_process_editroutine'])) {
    /**
     * Handle a request to create/edit a routine
     */
    $routine_query = createQueryFromRequest();
    if (! count($routine_errors)) { // set by createQueryFromRequest()
        // Execute the created query
        if (! empty($_REQUEST['routine_process_editroutine'])) {
            // Backup the old routine, in case something goes wrong
            $create_routine = PMA_DBI_get_definition($db, $_REQUEST['routine_original_type'], $_REQUEST['routine_original_name']);
            $drop_routine = "DROP {$_REQUEST['routine_original_type']} " . PMA_backquote($_REQUEST['routine_original_name']);
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
                    $query_html = '<code class="sql">'
                                . PMA_SQP_formatHtml(PMA_SQP_parse($drop_routine))
                                . '<br /><br />'
                                . PMA_SQP_formatHtml(PMA_SQP_parse($routine_query))
                                . '</code>';
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
                $query_html = '<code class="sql">'
                            . PMA_SQP_formatHtml(PMA_SQP_parse($routine_query))
                            . '</code>';
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

    if ($GLOBALS['is_ajax_request']) {
        $extra_data = array();
        if (isset($query_html)) {
            $columns  = "`SPECIFIC_NAME`, `ROUTINE_NAME`, `ROUTINE_TYPE`, `DTD_IDENTIFIER`, `ROUTINE_DEFINITION`";
            $where    = "ROUTINE_SCHEMA='" . PMA_sqlAddslashes($db,true) . "' AND ROUTINE_NAME='" . PMA_sqlAddslashes($_REQUEST['routine_name'],true) . "'";
            $routine  = PMA_DBI_fetch_single_row("SELECT $columns FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE $where;");

            $extra_data['name']      = htmlspecialchars(strtoupper($_REQUEST['routine_name']));
            $extra_data['new_row']   = routineMakeRowForList($routine);
            $extra_data['sql_query'] = $query_html;
        }
        PMA_ajaxResponse($message, $message->isSuccess(), $extra_data);
    } else {
        echo $message->getDisplay() . $query_html;
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
        }
        $routine = getFormInputFromRequest();
        $mode = 'add';
    } else if (! empty($_REQUEST['editroutine'])) {
        if ($GLOBALS['is_ajax_request'] != true) {
            echo "\n\n<h2>" . __("Edit Routine") . "</h2>\n\n";
        }
        if (! $operation && ! empty($_REQUEST['routine_name']) && empty($_REQUEST['routine_process_editroutine'])) {
            $routine = getFormInputFromRoutineName($db, $_REQUEST['routine_name']);
            $routine['original_name'] = $routine['name'];
            $routine['original_type'] = $routine['type'];
        } else {
            $routine = getFormInputFromRequest();
        }
        $mode = 'edit';
    }
    // Show form
    $editor = displayRoutineEditor($mode, $operation, $routine, $routine_errors);
    if (! empty($_REQUEST['ajax_request'])) {
        $template  = "        <tr>\n";
        $template .= "            <td class='routine_direction_cell'><select name='routine_param_dir[%s]'>\n";
        foreach ($param_directions as $key => $value) {
            $template .= "                <option>$value</option>\n";
        }
        $template .= "            </select></td>\n";
        $template .= "            <td><input name='routine_param_name[%s]' type='text'\n";
        $template .= "                       value='' /></td>\n";
        $template .= "            <td><select name='routine_param_type[%s]'>";
        $template .= getSupportedDatatypes(true) . "\n";
        $template .= "            </select></td>\n";
        $template .= "            <td><input name='routine_param_length[%s]' type='text'\n";
        $template .= "                       value='' /></td>\n";
        $template .= "            <td class='routine_param_remove' style='vertical-align: middle;'>\n";
        $template .= "                <a href='#' class='routine_param_remove_anchor'>\n";
        $template .= "                    " . PMA_getIcon('b_drop.png') . "\n";
        $template .= "                </a>\n";
        $template .= "            </td>\n";
        $template .= "        </tr>\n";
        $extra_data = array('title' => __('Add a new Routine'), 'param_template' => $template, 'type' => $routine['type']);
        PMA_ajaxResponse($editor, true, $extra_data);
    }
    echo $editor;
    require './libraries/footer.inc.php';
    // exit;
}

/**
 * Get the routines.
 */
$columns  = "`SPECIFIC_NAME`, `ROUTINE_NAME`, `ROUTINE_TYPE`, `DTD_IDENTIFIER`, `ROUTINE_DEFINITION`";
$where    = "ROUTINE_SCHEMA='" . PMA_sqlAddslashes($db,true) . "'";
$routines = PMA_DBI_fetch_result("SELECT $columns FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE $where;");

/**
 * Display a list of available routines
 */
echo "\n\n<span id='js_query_display'></span>";
echo "\n\n";
echo "<!-- LIST OF ROUTINES START -->\n";
echo "<fieldset>\n";
echo "    <legend>" . __('Routines') . "</legend>\n";
if (! $routines) {
    echo "    " . __('There are no routines to display.') . "\n";
} else {
    echo "    <div class='hide' id='nothing2display'>\n";
    echo "      " . __('There are no routines to display.') . "\n";
    echo "    </div>\n";
    echo "    <table class='data'>\n";
    echo "        <!-- TABLE HEADERS -->\n";
    echo "        <tr>\n";
    echo "            <th>" . __('Name') . "</th>\n";
    echo "            <th>&nbsp;</th>\n";
    echo "            <th>&nbsp;</th>\n";
    echo "            <th>&nbsp;</th>\n";
    echo "            <th>&nbsp;</th>\n";
    echo "            <th>" . __('Type') . "</th>\n";
    echo "            <th>" . __('Return type') . "</th>\n";
    echo "        </tr>\n";
    echo "        <!-- TABLE DATA -->\n";
    $ct = 0;
    // Display each routine
    foreach ($routines as $routine) {
        echo routineMakeRowForList($routine, $ct);
        $ct++;
    }
    echo "    </table>\n";
}
echo "</fieldset>\n";
echo "<!-- LIST OF ROUTINES END -->\n\n";

/**
 * Display the form for adding a new routine, if the user has the privileges.
 */
echo '<!-- ADD ROUTINE FORM START -->' . "\n";
echo '<fieldset>' . "\n";
if (PMA_currentUserHasPrivilege('CREATE ROUTINE', $db)) {
    echo '<a href="db_routines.php?' . $url_query
       . '&amp;addroutine=1" ' . $ajax_class['add'] . '>' . "\n"
       . PMA_getIcon('b_routine_add.png') . "\n"
       . __('Add a new Routine') . '</a>' . "\n";
} else {
    echo PMA_getIcon('b_routine_add.png') . "\n"
       . __('You do not have the necessary privileges to create a new Routine') . "\n";
}
echo PMA_showMySQLDocu('SQL-Syntax', 'CREATE_PROCEDURE') . "\n";
echo '</fieldset>' . "\n";
echo '<!-- ADD ROUTINE FORM END -->' . "\n\n";

?>
