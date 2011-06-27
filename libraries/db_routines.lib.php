<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This function parses a string containing one parameter of a routine,
 * as returned by PMA_RTN_parseAllParameters() and returns an array containing
 * the information about this parameter.
 *
 * @param   string  $value    A string containing one parameter of a routine
 *
 * @return  array             Parsed information about the input parameter
 *
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
    if ($parsed_param[$pos]['type'] == 'alpha_identifier' || $parsed_param[$pos]['type'] == 'quote_backtick') {
        $retval[1] = PMA_unQuote($parsed_param[$pos]['data']);
        $pos++;
    }
    $depth = 0;
    $param_length = '';
    $param_opts = array();
    for ($i=$pos; $i<$parsed_param['len']; $i++) {
        if (($parsed_param[$i]['type'] == 'alpha_columnType'
           || $parsed_param[$i]['type'] == 'alpha_functionName') // "CHAR" seems to be mistaken for a function by the parser
           && $depth == 0) {
            $retval[2] = strtoupper($parsed_param[$i]['data']);
        } else if ($parsed_param[$i]['type'] == 'punct_bracket_open_round' && $depth == 0) {
            $depth = 1;
        } else if ($parsed_param[$i]['type'] == 'punct_bracket_close_round' && $depth == 1) {
            $depth = 0;
        } else if ($depth == 1) {
            $param_length .= $parsed_param[$i]['data'];
        } else if ($parsed_param[$i]['type'] == 'alpha_reservedWord' && strtoupper($parsed_param[$i]['data']) == 'CHARSET' && $depth == 0) {
            if ($parsed_param[$i+1]['type'] == 'alpha_charset' || $parsed_param[$i+1]['type'] == 'alpha_identifier') {
                $param_opts[] = strtolower($parsed_param[$i+1]['data']);
            }
        } else if ($parsed_param[$i]['type'] == 'alpha_columnAttrib' && $depth == 0) {
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
 * @param   array   $parsed_query  Parsed query, returned by by PMA_SQP_parse()
 * @param   string  $routine_type  Routine type: 'PROCEDURE' or 'FUNCTION'
 *
 * @return  array   Information about the parameteres of a routine.
 *
 */
function PMA_RTN_parseAllParameters($parsed_query, $routine_type)
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
 * @param   array   $parsed_query   Parsed query, returned by PMA_SQP_parse()
 *
 * @return  string  The definer of a routine.
 *
 */
function PMA_RTN_parseRoutineDefiner($parsed_query)
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
} // end PMA_RTN_parseRoutineDefiner()

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
 */
function PMA_RTN_getRoutineDataFromName($db, $name, $all = true)
{
    global $param_directions, $param_sqldataaccess;

    $retval  = array();

    // Build and execute the query
    $fields  = "SPECIFIC_NAME, ROUTINE_TYPE, DTD_IDENTIFIER, "
             . "ROUTINE_DEFINITION, IS_DETERMINISTIC, SQL_DATA_ACCESS, "
             . "ROUTINE_COMMENT, SECURITY_TYPE";
    $where   = "ROUTINE_SCHEMA='" . PMA_sqlAddSlashes($db) . "' "
             . "AND SPECIFIC_NAME='" . PMA_sqlAddSlashes($name) . "'";
    $query   = "SELECT $fields FROM INFORMATION_SCHEMA.ROUTINES WHERE $where;";

    $routine = PMA_DBI_fetch_single_row($query);

    if (! $routine) {
        return false;
    }

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
    $params = PMA_RTN_parseAllParameters($parsed_query, $routine['ROUTINE_TYPE']);
    $retval['num_params']      = $params['num'];
    $retval['param_dir']       = $params['dir'];
    $retval['param_name']      = $params['name'];
    $retval['param_type']      = $params['type'];
    $retval['param_length']    = $params['length'];
    $retval['param_opts_num']  = $params['opts'];
    $retval['param_opts_text'] = $params['opts'];

    // Get extra data
    if ($all) {
        if ($retval['type'] == 'FUNCTION') {
            $retval['type_toggle'] = 'PROCEDURE';
        } else {
            $retval['type_toggle'] = 'FUNCTION';
        }
        $retval['returntype']   = '';
        $retval['returnlength'] = '';
        $retval['returnopts_num']  = '';
        $retval['returnopts_text'] = '';
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
                    && strtoupper($parsed_query[$i]['data']) == 'RETURNS') {
                        $fetching = true;
                    } else if ($fetching == true
                    && $parsed_query[$i]['type'] == 'alpha_reservedWord') {
                        // We will not be looking for options such as UNSIGNED
                        // or ZEROFILL because there is no way that a numeric
                        // field's DTD_IDENTIFIER can be longer than 64
                        // characters. We can safely assume that the return
                        // datatype is either ENUM or SET, so we only look
                        // for CHARSET.
                        $word = strtoupper($parsed_query[$i]['data']);
                        if ($word == 'CHARSET'
                        && ($parsed_query[$i+1]['type'] == 'alpha_charset'
                        || $parsed_query[$i+1]['type'] == 'alpha_identifier')) {
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
            $retval['returntype']      = $returnparam[2];
            $retval['returnlength']    = $returnparam[3];
            $retval['returnopts_num']  = $returnparam[4];
            $retval['returnopts_text'] = $returnparam[4];
        }
        $retval['definer']         = PMA_RTN_parseRoutineDefiner($parsed_query);
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
} // PMA_RTN_getRoutineDataFromName()

/**
 * This function will generate the values that are required to complete the "Add new routine" form
 * It is especially necessary to handle the 'Add another parameter', 'Remove last parameter'
 * and 'Change routine type' functionalities when JS is disabled.
 *
 * @return  array    Data necessary to create the routine editor.
 *
 */
function PMA_RTN_getRoutineDataFromRequest()
{
    global $_REQUEST, $param_directions, $param_sqldataaccess;

    $retval = array();
    $retval['name'] = '';
    if (isset($_REQUEST['routine_name'])) {
        $retval['name'] = $_REQUEST['routine_name'];
    }
    $retval['original_name'] = '';
    if (isset($_REQUEST['routine_original_name'])) {
         $retval['original_name'] = $_REQUEST['routine_original_name'];
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
    $retval['num_params']      = 0;
    $retval['param_dir']       = array();
    $retval['param_name']      = array();
    $retval['param_type']      = array();
    $retval['param_length']    = array();
    $retval['param_opts_num']  = array();
    $retval['param_opts_text'] = array();
    if (isset($_REQUEST['routine_param_name'])
        && isset($_REQUEST['routine_param_type'])
        && isset($_REQUEST['routine_param_length'])
        && isset($_REQUEST['routine_param_opts_num'])
        && isset($_REQUEST['routine_param_opts_text'])
        && is_array($_REQUEST['routine_param_name'])
        && is_array($_REQUEST['routine_param_type'])
        && is_array($_REQUEST['routine_param_length'])
        && is_array($_REQUEST['routine_param_opts_num'])
        && is_array($_REQUEST['routine_param_opts_text'])) {

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
            $retval['param_name'][$key] = $value;
            $temp_num_params++;
        }
        if ($temp_num_params > $retval['num_params']) {
            $retval['num_params'] = $temp_num_params;
        }
        $temp_num_params = 0;
        $retval['param_type'] = $_REQUEST['routine_param_type'];
        foreach ($retval['param_type'] as $key => $value) {
            if (! in_array($value, PMA_getSupportedDatatypes(), true)) {
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
            $retval['param_length'][$key] = $value;
            $temp_num_params++;
        }
        if ($temp_num_params > $retval['num_params']) {
            $retval['num_params'] = $temp_num_params;
        }
        $temp_num_params = 0;
        $retval['param_opts_num'] = $_REQUEST['routine_param_opts_num'];
        foreach ($retval['param_opts_num'] as $key => $value) {
            $retval['param_opts_num'][$key] = $value;
            $temp_num_params++;
        }
        if ($temp_num_params > $retval['num_params']) {
            $retval['num_params'] = $temp_num_params;
        }
        $temp_num_params = 0;
        $retval['param_opts_text'] = $_REQUEST['routine_param_opts_text'];
        foreach ($retval['param_opts_text'] as $key => $value) {
            $retval['param_opts_text'][$key] = $value;
            $temp_num_params++;
        }
        if ($temp_num_params > $retval['num_params']) {
            $retval['num_params'] = $temp_num_params;
        }
    }
    $retval['returntype'] = '';
    if (isset($_REQUEST['routine_returntype']) && in_array($_REQUEST['routine_returntype'], PMA_getSupportedDatatypes(), true)) {
        $retval['returntype'] = $_REQUEST['routine_returntype'];
    }
    $retval['returnlength'] = '';
    if (isset($_REQUEST['routine_returnlength'])) {
        $retval['returnlength'] = $_REQUEST['routine_returnlength'];
    }
    $retval['returnopts_num'] = '';
    if (isset($_REQUEST['routine_returnopts_num'])) {
        $retval['returnopts_num'] = $_REQUEST['routine_returnopts_num'];
    }
    $retval['returnopts_text'] = '';
    if (isset($_REQUEST['routine_returnopts_text'])) {
        $retval['returnopts_text'] = $_REQUEST['routine_returnopts_text'];
    }
    $retval['definition'] = '';
    if (isset($_REQUEST['routine_definition'])) {
        $retval['definition'] = $_REQUEST['routine_definition'];
    }
    $retval['isdeterministic'] = '';
    if (isset($_REQUEST['routine_isdeterministic']) && strtolower($_REQUEST['routine_isdeterministic']) == 'on') {
        $retval['isdeterministic'] = " checked='checked'";
    }
    $retval['definer'] = '';
    if (isset($_REQUEST['routine_definer'])) {
        $retval['definer'] = $_REQUEST['routine_definer'];
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
        $retval['comment'] = $_REQUEST['routine_comment'];
    }

    return $retval;
} // end function PMA_RTN_getRoutineDataFromRequest()

/**
 * Creates one row for the parameter table used in the routine editor.
 *
 * @param   array    $routine    Data for the routine returned by
 *                               PMA_RTN_getRoutineDataFromRequest() or
 *                               PMA_RTN_getRoutineDataFromName()
 * @param   mixed    $index      Either a numeric index of the row being processed
 *                               or NULL to create a template row for AJAX request
 * @param   string   $class      Class used to hide the direction column, if the
 *                               row is for a stored function.
 *
 * @return    string    HTML code of one row of parameter table for the routine editor.
 *
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
                       'param_dir'       => array(0 => ''),
                       'param_name'      => array(0 => ''),
                       'param_type'      => array(0 => ''),
                       'param_length'    => array(0 => ''),
                       'param_opts_num'  => array(0 => ''),
                       'param_opts_text' => array(0 => '')
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
    $retval .= "            <td class='routine_direction_cell$class'><select name='routine_param_dir[$index]'>\n";
    foreach ($param_directions as $key => $value) {
        $selected = "";
        if (! empty($routine['param_dir'][$i]) && $routine['param_dir'][$i] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "                <option$selected>$value</option>\n";
    }
    $retval .= "            </select></td>\n";
    $retval .= "            <td><input name='routine_param_name[$index]' type='text'\n";
    $retval .= "                       value='{$routine['param_name'][$i]}' /></td>\n";
    $retval .= "            <td><select name='routine_param_type[$index]'>";
    $retval .= PMA_getSupportedDatatypes(true, $routine['param_type'][$i]) . "\n";
    $retval .= "            </select></td>\n";
    $retval .= "            <td><input name='routine_param_length[$index]' type='text'\n";
    $retval .= "                       value='{$routine['param_length'][$i]}' /></td>\n";
    $retval .= "            <td class='routine_param_opts_text'>\n";
    $retval .= PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_CHARSET,
                                              "routine_param_opts_text[$index]",
                                              null,
                                              $routine['param_opts_text'][$i]);
    $retval .= "            </td>\n";
    $retval .= "            <td class='routine_param_opts_num'><select name='routine_param_opts_num[$index]'>\n";
    $retval .= "                <option value=''></option>";
    foreach ($param_opts_num as $key => $value) {
        $selected = "";
        if (! empty($routine['param_opts_num'][$i]) && $routine['param_opts_num'][$i] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "<option$selected>$value</option>";
    }
    $retval .= "\n            </select></td>\n";
    $retval .= "            <td class='routine_param_remove$drop_class' style='vertical-align: middle;'>\n";
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
 * @param   string   $mode         If the editor will be used edit a routine
 *                                 or add a new one: 'edit' or 'add'.
 * @param   string   $operation    If the editor was previously invoked with
 *                                 JS turned off, this will hold the name of
 *                                 the current operation: 'add', remove', 'change'
 * @param   array    $routine      Data for the routine returned by
 *                                 PMA_RTN_getRoutineDataFromRequest() or
 *                                 PMA_RTN_getRoutineDataFromName()
 * @param   array    $errors       If the editor was already invoked and there
 *                                 has been an error while processing the request
 *                                 this array will hold the errors.
 * @param   bool     $is_ajax      True, if called from an ajax request
 *
 * @return  string   HTML code for the routine editor.
 *
 */
function PMA_RTN_getEditorForm($mode, $operation, $routine, $errors, $is_ajax) {
    global $db, $titles, $param_directions, $param_sqldataaccess, $param_opts_num;

    // Escape special characters
    $need_escape = array(
                       'original_name',
                       'name',
                       'returnlength',
                       'definition',
                       'definer',
                       'comment'
                   );
    foreach($need_escape as $key => $index) {
        $routine[$index] = htmlentities($routine[$index], ENT_QUOTES);
    }
    for ($i=0; $i<$routine['num_params']; $i++) {
        $routine['param_name'][$i]   = htmlentities($routine['param_name'][$i], ENT_QUOTES);
        $routine['param_length'][$i] = htmlentities($routine['param_length'][$i], ENT_QUOTES);
    }

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
        $routine['param_dir'][]       = '';
        $routine['param_name'][]      = '';
        $routine['param_type'][]      = '';
        $routine['param_length'][]    = '';
        $routine['param_opts_num'][]  = '';
        $routine['param_opts_text'][] = '';
        $routine['num_params']++;
    } else if ($operation == 'remove') {
        unset($routine['param_dir'][$routine['num_params']-1]);
        unset($routine['param_name'][$routine['num_params']-1]);
        unset($routine['param_type'][$routine['num_params']-1]);
        unset($routine['param_length'][$routine['num_params']-1]);
        unset($routine['param_opts_num'][$routine['num_params']-1]);
        unset($routine['param_opts_text'][$routine['num_params']-1]);
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
    $isfunction_class   = '';
    $isprocedure_class  = '';
    $isfunction_select  = '';
    $isprocedure_select = '';
    if ($routine['type'] == 'PROCEDURE') {
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
    $retval .= "<input name='{$mode}routine' type='hidden' value='1' />\n";
    $retval .= $original_routine;
    $retval .= PMA_generate_common_hidden_inputs($db) . "\n";
    $retval .= "<fieldset>\n";
    $retval .= "<legend>" . __('Details') . "</legend>\n";
    $retval .= "<table class='rte_table' style='width: 100%'>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Routine name') . "</td>\n";
    $retval .= "    <td><input type='text' name='routine_name' value='{$routine['name']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Type') . "</td>\n";
    $retval .= "    <td>\n";
    if ($is_ajax) {
        $retval .= "        <select name='routine_type'>\n";
        $retval .= "            <option value='PROCEDURE'$isprocedure_select>PROCEDURE</option>\n";
        $retval .= "            <option value='FUNCTION'$isfunction_select>FUNCTION</option>\n";
        $retval .= "        </select>\n";
    } else {
        $retval .= "        <input name='routine_type' type='hidden' value='{$routine['type']}' />\n";
        $retval .= "        <div style='width: 49%; float: left; text-align: center; font-weight: bold;'>\n";
        $retval .= "            {$routine['type']}\n";
        $retval .= "        </div>\n";
        $retval .= "        <input style='width: 49%;' type='submit' name='routine_changetype'\n";
        $retval .= "               value='".sprintf(__('Change to %s'), $routine['type_toggle'])."' />\n";
    }
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Parameters') . "</td>\n";
    $retval .= "    <td>\n";
    // parameter handling start
    $retval .= "        <table class='routine_params_table' style='width: 100%;'>\n";
    $retval .= "        <tr>\n";
    $retval .= "            <th class='routine_direction_cell$isprocedure_class'>" . __('Direction') . "</th>\n";
    $retval .= "            <th>" . __('Name') . "</th>\n";
    $retval .= "            <th>" . __('Type') . "</th>\n";
    $retval .= "            <th>" . __('Length/Values') . "</th>\n";
    $retval .= "            <th colspan='2'>" . __('Options') . "</th>\n";
    $retval .= "            <th class='routine_param_remove hide'>&nbsp;</th>\n";
    $retval .= "        </tr>";
    for ($i=0; $i<$routine['num_params']; $i++) { // each parameter
        $retval .= PMA_RTN_getParameterRow($routine, $i, $isprocedure_class);
    }
    $retval .= "        </table>\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>&nbsp;</td>\n";
    $retval .= "    <td>\n";
    $retval .= "        <input style='width: 49%;' type='submit' \n";
    $retval .= "               name='routine_addparameter'\n";
    $retval .= "               value='" . __('Add parameter') . "' />\n";
    $retval .= "        <input style='width: 49%;$disable_remove_parameter' type='submit' \n";
    $retval .= "               name='routine_removeparameter'\n";
    $retval .= "               value='" . __('Remove last parameter') . "' />\n";
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    // parameter handling end
    $retval .= "<tr class='routine_return_row$isfunction_class'>\n";
    $retval .= "    <td>" . __('Return type') . "</td>\n";
    $retval .= "    <td><select name='routine_returntype'>\n";
    $retval .= PMA_getSupportedDatatypes(true, $routine['returntype']) . "\n";
    $retval .= "    </select></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr class='routine_return_row$isfunction_class'>\n";
    $retval .= "    <td>" . __('Return length/values') . "</td>\n";
    $retval .= "    <td><input type='text' name='routine_returnlength'\n";
    $retval .= "               value='{$routine['returnlength']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr class='routine_return_row$isfunction_class'>\n";
    $retval .= "    <td>" . __('Return options') . "</td>\n";
    $retval .= "    <td><div>\n";
    $retval .= PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_CHARSET,
                                              "routine_returnopts_text",
                                              null,
                                              $routine['returnopts_text']) . "\n";
    $retval .= "    </div>\n";
    $retval .= "    <div><select name='routine_returnopts_num'>\n";
    $retval .= "        <option value=''></option>";
    foreach ($param_opts_num as $key => $value) {
        $selected = "";
        if (! empty($routine['returnopts_num']) && $routine['returnopts_num'] == $value) {
            $selected = " selected='selected'";
        }
        $retval .= "<option$selected>$value</option>";
    }
    $retval .= "\n    </select></div></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definition') . "</td>\n";
    $retval .= "    <td><textarea name='routine_definition' rows='15' cols='40'>{$routine['definition']}</textarea></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Is deterministic') . "</td>\n";
    $retval .= "    <td><input type='checkbox' name='routine_isdeterministic'{$routine['isdeterministic']} /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Definer') . "</td>\n";
    $retval .= "    <td><input type='text' name='routine_definer'\n";
    $retval .= "               value='{$routine['definer']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Security type') . "</td>\n";
    $retval .= "    <td><select name='routine_securitytype'>\n";
    $retval .= "        <option value='DEFINER'{$routine['securitytype_definer']}>DEFINER</option>\n";
    $retval .= "        <option value='INVOKER'{$routine['securitytype_invoker']}>INVOKER</option>\n";
    $retval .= "    </select></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('SQL data access') . "</td>\n";
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
    if ($is_ajax) {
        $retval .= "<input type='hidden' name='routine_process_{$mode}routine' value='true' />\n";
        $retval .= "<input type='hidden' name='ajax_request' value='true' />\n";
    } else {
        $retval .= "<fieldset class='tblFooters routineEditorSubmit'>\n";
        $retval .= "    <input type='submit' name='routine_process_{$mode}routine'\n";
        $retval .= "           value='" . __('Go') . "' />\n";
        $retval .= "</fieldset>\n";
    }
    $retval .= "</form>\n\n";
    $retval .= "<!-- END " . strtoupper($mode) . " ROUTINE FORM -->\n\n";

    return $retval;
} // end PMA_RTN_getEditorForm()

/**
 * Creates the HTML code that shows the routine execution dialog.
 *
 * @param   array    $routine      Data for the routine returned by
 *                                 PMA_RTN_getRoutineDataFromName()
 * @param   bool     $is_ajax      True, if called from an ajax request
 *
 * @return  string   HTML code for the routine execution dialog.
 *
 */
function PMA_RTN_getExecuteForm($routine, $is_ajax)
{
    global $db, $cfg;

    // Escape special characters
    $routine['name'] = htmlentities($routine['name'], ENT_QUOTES);
    for ($i=0; $i<$routine['num_params']; $i++) {
        $routine['param_name'][$i] = htmlentities($routine['param_name'][$i], ENT_QUOTES);
    }

    // Create the output
    $retval  = "";
    $retval .= "<!-- START ROUTINE EXECUTE FORM -->\n\n";
    $retval .= "<form action='db_routines.php' method='post' class='rte_form'>\n";
    $retval .= "<input type='hidden' name='routine_name' value='{$routine['name']}' />\n";
    $retval .= PMA_generate_common_hidden_inputs($db) . "\n";
    $retval .= "<fieldset>\n";
    if ($is_ajax != true) {
        $retval .= "<legend>{$routine['name']}</legend>\n";
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
    for ($i=0; $i<$routine['num_params']; $i++) { // Each parameter
        if ($routine['type'] == 'PROCEDURE' && $routine['param_dir'][$i] == 'OUT') {
            continue;
        }
        $rowclass = ($i % 2 == 0) ? 'even' : 'odd';
        $retval .= "\n<tr class='$rowclass'>\n";
        $retval .= "<td><strong>{$routine['param_name'][$i]}</strong></td>\n";
        $retval .= "<td>{$routine['param_type'][$i]}</td>\n";
        if ($cfg['ShowFunctionFields']) {
            $retval .= "<td>\n";
            // Get a list of data types that are not yet supported.
            $no_support_types = PMA_unsupportedDatatypes();
            if (stristr($routine['param_type'][$i], 'enum')
                || stristr($routine['param_type'][$i], 'set')
                || in_array(strtolower($routine['param_type'][$i]), $no_support_types)) {
                $retval .= "--\n";
            } else {
                $field = array(
                             'True_Type'       => strtolower($routine['param_type'][$i]),
                             'Type'            => '',
                             'Key'             => '',
                             'Field'           => '',
                             'Default'         => '',
                             'first_timestamp' => false
                         );
                $retval .= "<select name='funcs[{$routine['param_name'][$i]}]'>";
                $retval .= PMA_getFunctionsForField($field, false);
                $retval .= "</select>";
            }
            $retval .= "</td>\n";
        }
        // Append a class to date/time fields so that
        // jQuery can attach a datepicker to them
        $class = '';
        if (in_array($routine['param_type'][$i], array('DATETIME', 'TIMESTAMP'))) {
            $class = 'datetimefield';
        } else if ($routine['param_type'][$i] == 'DATE') {
            $class = 'datefield';
        }
        $retval .= "<td style='white-space: nowrap;'>\n";
        if (in_array($routine['param_type'][$i], array('ENUM', 'SET'))) {
            $tokens = PMA_SQP_parse($routine['param_length'][$i]);
            if ($routine['param_type'][$i] == 'ENUM') {
                $input_type = 'radio';
            } else {
                $input_type = 'checkbox';
            }
            for ($j=0; $j<$tokens['len']; $j++) {
                if ($tokens[$j]['type'] != 'punct_listsep') {
                    $tokens[$j]['data'] = htmlentities(PMA_unquote($tokens[$j]['data']), ENT_QUOTES);
                    $retval .= "<input name='params[{$routine['param_name'][$i]}][]' "
                             . "value='{$tokens[$j]['data']}' type='$input_type' />"
                             . "{$tokens[$j]['data']}<br />\n";
                }
            }
        } else if (in_array(strtolower($routine['param_type'][$i]), $no_support_types)) {
            $retval .= "\n";
        } else {
            $retval .= "<input class='$class' type='text' name='params[{$routine['param_name'][$i]}]' />\n";
        }
        $retval .= "</td>\n";
        $retval .= "</tr>\n";
    }
    $retval .= "\n</table>\n";
    if ($is_ajax != true) {
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

/**
 * Composes the query necessary to create a routine from an HTTP request.
 *
 * @return  string    The CREATE [ROUTINE | PROCEDURE] query.
 *
 */
function PMA_RTN_getQueryFromRequest() {
    global $_REQUEST, $cfg, $routine_errors, $param_sqldataaccess;

    $query = 'CREATE ';
    if (! empty($_REQUEST['routine_definer']) && strpos($_REQUEST['routine_definer'], '@') !== false) {
        $arr = explode('@', $_REQUEST['routine_definer']);
        $query .= 'DEFINER=' . PMA_backquote($arr[0]) . '@' . PMA_backquote($arr[1]) . ' ';
    }
    if ($_REQUEST['routine_type'] == 'FUNCTION' || $_REQUEST['routine_type'] == 'PROCEDURE') {
        $query .= $_REQUEST['routine_type'] . ' ';
    } else {
        $routine_errors[] = sprintf(__('Invalid routine type: "%s"'), htmlspecialchars($_REQUEST['routine_type']));
    }
    if (! empty($_REQUEST['routine_name'])) {
        $query .= PMA_backquote($_REQUEST['routine_name']) . ' ';
    } else {
        $routine_errors[] = __('You must provide a routine name');
    }
    $params = '';
    $warned_about_dir    = false;
    $warned_about_name   = false;
    $warned_about_length = false;
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
                    $routine_errors[] = sprintf(__('Invalid direction "%s" given for parameter.'),
                                                htmlspecialchars($_REQUEST['routine_param_dir'][$i]));
                }
                if ($_REQUEST['routine_param_length'][$i] != ''
                    && !preg_match('@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$@i',
                                   $_REQUEST['routine_param_type'][$i])) {
                    $params .= "(" . $_REQUEST['routine_param_length'][$i] . ")";
                } else if ($_REQUEST['routine_param_length'][$i] == ''
                           && preg_match('@^(ENUM|SET|VARCHAR|VARBINARY)$@i', $_REQUEST['routine_param_type'][$i])) {
                    if (! $warned_about_length) {
                        $warned_about_length = true;
                        $routine_errors[] = __('You must provide length/values for routine '
                                             . 'parameters of type ENUM, SET, VARCHAR and VARBINARY.');
                    }
                }
                if (! empty($_REQUEST['routine_param_opts_text'][$i])) {
                    if (isset($cfg['RestrictColumnTypes'][strtoupper($_REQUEST['routine_param_type'][$i])])) {
                        $group = $cfg['RestrictColumnTypes'][strtoupper($_REQUEST['routine_param_type'][$i])];
                        if ($group == 'FUNC_CHAR') {
                            $params .= ' CHARSET ' . strtolower($_REQUEST['routine_param_opts_text'][$i]);
                        }
                    }
                }
                if (! empty($_REQUEST['routine_param_opts_num'][$i])) {
                    if (isset($cfg['RestrictColumnTypes'][strtoupper($_REQUEST['routine_param_type'][$i])])) {
                        $group = $cfg['RestrictColumnTypes'][strtoupper($_REQUEST['routine_param_type'][$i])];
                        if ($group == 'FUNC_NUMBER') {
                            $params .= ' ' . strtoupper($_REQUEST['routine_param_opts_num'][$i]);
                        }
                    }
                }
                if ($i != count($_REQUEST['routine_param_name'])-1) {
                    $params .= ", ";
                }
            } else if (! $warned_about_name) {
                $warned_about_name = true;
                $routine_errors[] = __('You must provide a name and a type for each routine parameter.');
                break;
            }
        }
    }
    $query .= " (" . $params . ") ";
    if ($_REQUEST['routine_type'] == 'FUNCTION') {
        $query .= "RETURNS {$_REQUEST['routine_returntype']}";
        if (! empty($_REQUEST['routine_returnlength'])
            && !preg_match('@^(DATE|DATETIME|TIME|TINYBLOB|TINYTEXT|BLOB|TEXT|MEDIUMBLOB|MEDIUMTEXT|LONGBLOB|LONGTEXT)$@i',
                            $_REQUEST['routine_returntype'])) {
            $query .= "(" . $_REQUEST['routine_returnlength'] . ")";
        } else if (empty($_REQUEST['routine_returnlength'])
            && preg_match('@^(ENUM|SET|VARCHAR|VARBINARY)$@i', $_REQUEST['routine_returntype'])) {
            if (! $warned_about_length) {
                $warned_about_length = true;
                $routine_errors[] = __('You must provide length/values for routine '
                                     . 'parameters of type ENUM, SET, VARCHAR and VARBINARY.');
            }
        }
        if (! empty($_REQUEST['routine_returnopts_text'])) {
            if (isset($cfg['RestrictColumnTypes'][strtoupper($_REQUEST['routine_returntype'])])) {
                $group = $cfg['RestrictColumnTypes'][strtoupper($_REQUEST['routine_returntype'])];
                if ($group == 'FUNC_CHAR') {
                    $query .= ' CHARSET ' . strtolower($_REQUEST['routine_returnopts_text']);
                }
            }
        }
        if (! empty($_REQUEST['routine_returnopts_num'])) {
            if (isset($cfg['RestrictColumnTypes'][strtoupper($_REQUEST['routine_returntype'])])) {
                $group = $cfg['RestrictColumnTypes'][strtoupper($_REQUEST['routine_returntype'])];
                if ($group == 'FUNC_NUMBER') {
                    $query .= ' ' . strtoupper($_REQUEST['routine_returnopts_num']);
                }
            }
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
        $routine_errors[] = __('You must provide a routine definition.');
    }
    return $query;
} // end PMA_RTN_getQueryFromRequest()

/**
 * Creates the HTML for a row of the routines list.
 *
 * @param    array    $routine    Data about the routine returned by a query
 *                                from the INFORMATION_SCHEMA db.
 * @param    int      $ct         Row count
 * @param    bool     $is_ajax    Whether this function was called
 *                                from within an AJAX request
 *
 * @return  string    An HTML snippet containing a row for the routines list.
 *
 */
function PMA_RTN_getRowForRoutinesList($routine, $ct = 0, $is_ajax = false) {
    global $titles, $db, $url_query, $ajax_class;

    // Do the logic first
    $rowclass = ($ct % 2 == 0) ? 'even' : 'odd';
    if ($is_ajax) {
        $rowclass .= ' ajaxInsert hide';
    }
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
    if ($routine['ROUTINE_DEFINITION'] !== NULL && PMA_currentUserHasPrivilege('EXECUTE', $db)) {
        // Check if he routine has any input parameters. If it does,
        // we will show a dialog to get values for these parameters,
        // otherwise we can execute it directly.
        $routine_details = PMA_RTN_getRoutineDataFromName($db, $routine['SPECIFIC_NAME'], false);
        if ($routine !== false) {
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
                          . '&amp;goto=db_routines.php' . urlencode("?db=$db")
                          . '" >' . $titles['Drop'] . '</a>';
    }
    // Display a row of data
    $retval  = "        <tr class='$rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>$sql_drop</span>\n";
    $retval .= "                <strong>" . htmlspecialchars($routine['ROUTINE_NAME']) . "</strong>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>$editlink</td>\n";
    $retval .= "            <td>$execlink</td>\n";
    $retval .= "            <td>$exprlink</td>\n";
    $retval .= "            <td>$droplink</td>\n";
    $retval .= "            <td>{$routine['ROUTINE_TYPE']}</td>\n";
    $retval .= "            <td>" . htmlspecialchars($routine['DTD_IDENTIFIER']) . "</td>\n";
    $retval .= "        </tr>\n";
    return $retval;
} // end PMA_RTN_getRowForRoutinesList()

/**
 * Creates a list of available routines for the specified database
 *
 * @return   string    An HTML snippet with the list of routines.
 *
 */
function PMA_RTN_getRoutinesList()
{
    global $db;

    /**
     * Get the routines
     */
    $columns  = "`SPECIFIC_NAME`, `ROUTINE_NAME`, `ROUTINE_TYPE`, `DTD_IDENTIFIER`, `ROUTINE_DEFINITION`";
    $where    = "ROUTINE_SCHEMA='" . PMA_sqlAddSlashes($db) . "'";
    $routines = PMA_DBI_fetch_result("SELECT $columns FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE $where;");
    /**
     * Conditional classes switch the list on or off
     */
    $class1 = 'hide';
    $class2 = '';
    if (! $routines) {
        $class1 = '';
        $class2 = ' hide';
    }

    /**
     * Generate output
     */
    $retval  = "";
    $retval .= "\n\n<span id='js_query_display'></span>\n\n";
    $retval .= "<!-- LIST OF ROUTINES START -->\n";
    $retval .= "<fieldset>\n";
    $retval .= "    <legend>" . __('Routines') . "</legend>\n";
    $retval .= "    <div class='$class1' id='nothing2display'>\n";
    $retval .= "      " . __('There are no routines to display.') . "\n";
    $retval .= "    </div>\n";
    $retval .= "    <table class='data$class2'>\n";
    $retval .= "        <!-- TABLE HEADERS -->\n";
    $retval .= "        <tr>\n";
    $retval .= "            <th>" . __('Name') . "</th>\n";
    $retval .= "            <th colspan='4'>" . __('Action') . "</th>\n";
    $retval .= "            <th>" . __('Type') . "</th>\n";
    $retval .= "            <th>" . __('Return type') . "</th>\n";
    $retval .= "        </tr>\n";
    $retval .= "        <!-- TABLE DATA -->\n";
    $ct = 0;
    // Display each routine
    foreach ($routines as $routine) {
        $retval .= PMA_RTN_getRowForRoutinesList($routine, $ct);
        $ct++;
    }
    $retval .= "    </table>\n";
    $retval .= "</fieldset>\n";
    $retval .= "<!-- LIST OF ROUTINES END -->\n\n";

    return $retval;
} // end PMA_RTN_getRoutinesList()

/**
 * Creates a fieldset for adding a new routine, if the user has the privileges.
 *
 * @return   string    An HTML snippet with the link to add a new routine.
 *
 */
function PMA_RTN_getAddRoutineLink()
{
    global $db, $url_query, $ajax_class;

    $retval  = "";
    $retval .= "<!-- ADD ROUTINE FORM START -->\n";
    $retval .= "<fieldset>\n";
    if (PMA_currentUserHasPrivilege('CREATE ROUTINE', $db)) {
        $retval .= "<a {$ajax_class['add']} href='db_routines.php";
        $retval .= "?$url_query&amp;addroutine=1'>\n";
        $retval .= PMA_getIcon('b_routine_add.png') . "\n";
        $retval .= __('Add routine') . "</a>\n";
    } else {
        $retval .= PMA_getIcon('b_routine_add.png') . "\n";
        $retval .= __('You do not have the necessary privileges to create a new routine') . "\n";
    }
    $retval .= PMA_showMySQLDocu('SQL-Syntax', 'CREATE_PROCEDURE') . "\n";
    $retval .= "</fieldset>\n";
    $retval .= "<!-- ADD ROUTINE FORM END -->\n\n";

    return $retval;
} // end PMA_RTN_getAddRoutineLink()

?>
