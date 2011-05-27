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
$param_datatypes       = getSupportedDatatypes();
$param_directions      = array('IN', 'OUT', 'INOUT');
$param_sqldataaccess   = array('', 'CONTAINS SQL', 'NO SQL', 'READS SQL DATA', 'MODIFIES SQL DATA');

/**
 * This function processes the datatypes supported by the DB, as specified in $cfg['ColumnTypes']
 * and either returns an array (useful for quickly checking if a datatype is supported)
 * or an HTML snippet that creates a drop-down list.
 */
function getSupportedDatatypes($html = false, $selected = '')
{
    global $cfg;

    if ($html) {
        $retval = '';
        foreach ($cfg['ColumnTypes'] as $key => $value) {
            if (is_array($value)) {
                $retval .= "<optgroup label='" . htmlspecialchars($key) . "'>";
                foreach ($value as $subkey => $subvalue) {
                    if ($subvalue == $selected) {
                        $retval .= "<option selected='selected'>$subvalue</option>";
                    } else if ($subvalue === '-') {
                        $retval .= "<option disabled='disabled'>$subvalue</option>";
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
 * Parses the parameters of a routine given a string
 * This function can handle absolutely horrible, yet perfectly valid, cases like:
 * "IN a INT(10), OUT b DECIMAL(10,5), INOUT c ENUM('1,2,3\\\')(', '2', '3', '4')"
 */
function parseListOfParameters($str, &$num, &$dir, &$name, &$type, &$length)
{
    // Setup the stack based parser
    $len    = strlen($str);
    $char   = '';
    $buffer = '';
    $stack  = array();
    $params = array();
    // TOKENS
    $BRAC = 0;
    $STR1 = 1;
    $STR2 = 2;
    // Parse the list of parameters
    for ($i=0; $i<$len; $i++) {
	    $char = $str[$i];
	    switch ($char) {
	    case ',':
		    if (count($stack) == 0) {
			    $params[] = $buffer;
			    $buffer = '';
		    } else {
			    $buffer .= $char;
		    }
		    break;
	    case '(':
		    if (count($stack) == 0) {
			    $stack[] = $BRAC;
		    }
		    $buffer .= $char;
		    break;
	    case ')':
		    if (end($stack) == $BRAC) {
			    array_pop($stack);
		    }
		    $buffer .= $char;
		    break;
	    case '"':
		    if (end($stack) == $BRAC) {
			    $stack[] = $STR1;
		    } else if (end($stack) == $STR1) {
			    array_pop($stack);
		    }
		    $buffer .= $char;
		    break;
	    case "'":
		    if (end($stack) == $BRAC) {
			    $stack[] = $STR2;
		    } else if (end($stack) == $STR2) {
			    array_pop($stack);
		    }
		    $buffer .= $char;
		    break;
	    case '\\':
		    if (end($stack) == $STR1 || (end($stack) == $STR2 && $str[$i+1] == "'")) {
			    // skip escaped character
                // FIXME: do we need to support escaping a single quote by another single quote?
			    $buffer .= $char;
			    $i++;
			    $buffer .= $str[$i];
		    } else {
			    $buffer .= $char;
		    }
		    break;
	    default:
		    $buffer .= $char;
		    break;
	    }
    }
    if (! empty($buffer)) {
        $params[] = $buffer;
    }
    array_walk($params, create_function('&$val', '$val = trim($val);'));
    $num = count($params);

    // Now parse each parameter individually
    foreach ($params as $key => $value) {
	    // Get direction
	    if (substr($value, 0, 5) == 'INOUT') {
		    $dir[] = 'INOUT';
		    $value = ltrim(substr($value, 5));
	    } else if (substr($value, 0, 2) == 'IN') {
		    $dir[] = 'IN';
		    $value = ltrim(substr($value, 2));
	    } else if (substr($value, 0, 3) == 'OUT') {
		    $dir[] = 'OUT';
		    $value = ltrim(substr($value, 3));
	    }
	    // Get name
	    $space_pos = strpos($value, ' ');
	    $name[] = htmlspecialchars(substr($value, 0, $space_pos));
	    $value = ltrim(substr($value, $space_pos));
	    // Get type
	    $brac_pos = strpos($value, '(');
	    if ($brac_pos === false) {
		    // Simple type, no length
		    $type[] = $value;
		    $length[] = '';
	    } else {
		    // Need to get length
		    $type[] = substr($value, 0, $brac_pos);
		    $length[] = htmlentities(substr($value, $brac_pos+1, -1), ENT_QUOTES);
	    }
    }
} // end parseListOfParameters()

/**
 * This function will generate the values that are required to complete
 * the "Edit routine" form given the name of a routine.
 */
function getFormInputFromRoutineName($db, $name)
{
    global $_REQUEST, $param_directions, $param_datatypes, $param_sqldataaccess;

    $retval = array();

    $routine = PMA_DBI_fetch_result('SELECT SPECIFIC_NAME, ROUTINE_TYPE, DTD_IDENTIFIER, ROUTINE_DEFINITION, IS_DETERMINISTIC, SQL_DATA_ACCESS, ROUTINE_COMMENT, SECURITY_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA= \'' . PMA_sqlAddslashes($db,true) . '\' AND SPECIFIC_NAME=\'' . PMA_sqlAddslashes($name,true) . '\';');
    $routine = $routine[0];

    // FIXME: should we even fetch the parameters from the mysql db?
    //        OR is it better to get then from SHOW ROUTINE_TYPE query?
    $mysql_routine = PMA_DBI_fetch_result('SELECT definer, param_list FROM mysql.proc WHERE db=\'' . PMA_sqlAddslashes($db,true) . '\' AND name=\'' . PMA_sqlAddslashes($name,true) . '\';');
    $mysql_routine = $mysql_routine[0];

    $retval['name']            = $routine['SPECIFIC_NAME'];
    $retval['type']            = $routine['ROUTINE_TYPE'];
    if ($retval['type'] == 'FUNCTION') {
        $retval['type_toggle'] = 'PROCEDURE';
    } else {
        $retval['type_toggle'] = 'FUNCTION';
    }

    $retval['num_params']      = 0;
    $retval['param_dir']       = array();
    $retval['param_name']      = array();
    $retval['param_type']      = array();
    $retval['param_length']    = array();
    parseListOfParameters($mysql_routine['param_list'],
                          $retval['num_params'],
                          $retval['param_dir'],
                          $retval['param_name'],
                          $retval['param_type'],
                          $retval['param_length']);

    $retval['returntype']      = '';
    $retval['returnlength']    = '';
    if (! empty($routine['DTD_IDENTIFIER'])) {
        $brac1_pos = strpos($routine['DTD_IDENTIFIER'], '(');
        $brac2_pos = strrpos($routine['DTD_IDENTIFIER'], ')');
        if ($brac1_pos !== false && $brac2_pos !== false) {
            $retval['returntype']   = strtoupper(trim(substr($routine['DTD_IDENTIFIER'], 0, $brac1_pos)));
            $retval['returnlength'] = htmlentities(trim(substr($routine['DTD_IDENTIFIER'], $brac1_pos+1, $brac2_pos-$brac1_pos-1)), ENT_QUOTES);
        } else {
            $retval['returntype'] = strtoupper($routine['DTD_IDENTIFIER']);
        }
    }
    $retval['definition']      = $routine['ROUTINE_DEFINITION'];
    $retval['isdeterministic'] = '';
    if ($routine['IS_DETERMINISTIC'] == 'YES') {
        $retval['isdeterministic'] = " checked='checked'";
    }
    $retval['definer']         = $mysql_routine['definer'];
    $retval['securitytype_definer'] = '';
    $retval['securitytype_invoker'] = '';
    if ($routine['SECURITY_TYPE'] == 'DEFINER') {
        $retval['securitytype_definer'] = " selected='selected'";
    } else if ($routine['SECURITY_TYPE'] == 'INVOKER') {
        $retval['securitytype_invoker'] = " selected='selected'";
    }
    $retval['sqldataaccess']   = $routine['SQL_DATA_ACCESS'];
    $retval['comment']         = $routine['ROUTINE_COMMENT'];

    return $retval;
} // getFormInputFromRoutineName()

/**
 * This function will generate the values that are required to complete the "Add new routine" form
 * It is especially necessary to handle the 'Add another parameter', 'Remove last parameter'
 * and 'Change routine type' functionalities when JS is disabled.
 */
function getFormInputFromRequest()
{
    global $_REQUEST, $param_directions, $param_datatypes, $param_sqldataaccess;

    $retval = array();
    $retval['name'] = isset($_REQUEST['routine_name']) ? htmlspecialchars($_REQUEST['routine_name']) : '';
    $retval['type']         = 'PROCEDURE';
    $retval['type_toggle']  = 'FUNCTION';
    if (isset($_REQUEST['routine_type']) && $_REQUEST['routine_type'] == 'FUNCTION') {
        $retval['type']         = 'FUNCTION';
        $retval['type_toggle']  = 'PROCEDURE';
    }
    $retval['num_params']   = 0;
    $retval['param_dir']    = array();
    $retval['param_name']   = array();
    $retval['param_type']   = array();
    $retval['param_length'] = array();
    if (isset($_REQUEST['routine_param_name'])
        && isset($_REQUEST['routine_param_type']) && isset($_REQUEST['routine_param_length'])
        && is_array($_REQUEST['routine_param_name'])
        && is_array($_REQUEST['routine_param_type']) && is_array($_REQUEST['routine_param_length'])) {

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
            if (! in_array($value, $param_datatypes, true)) {
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
    if (isset($_REQUEST['routine_returntype']) && in_array($_REQUEST['routine_returntype'], $param_datatypes, true)) {
        $retval['returntype'] = $_REQUEST['routine_returntype'];
    }
    $retval['returnlength']    = isset($_REQUEST['routine_returnlength']) ? htmlentities($_REQUEST['routine_returnlength'], ENT_QUOTES) : '';
    $retval['definition']      = isset($_REQUEST['routine_definition'])   ? htmlspecialchars($_REQUEST['routine_definition']) : '';
    $retval['isdeterministic'] = '';
    if (isset($_REQUEST['routine_isdeterministic']) && strtolower($_REQUEST['routine_isdeterministic']) == 'on') {
        $retval['isdeterministic'] = " checked='checked'";
    }
    $retval['definer'] = isset($_REQUEST['routine_definer']) ? htmlentities($_REQUEST['routine_definer'], ENT_QUOTES) : '';
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
    $retval['comment'] = isset($_REQUEST['routine_comment']) ? htmlentities($_REQUEST['routine_comment'], ENT_QUOTES) : '';

    return $retval;
} // end function getFormInputFromRequest()

/**
 * Displays a form used to add/edit a routine
 */
function displayRoutineEditor($mode, $operation, $routine, $errors) {
    global $db, $table, $url_query, $param_directions, $param_datatypes, $param_sqldataaccess;

    // Error handling
    $message = '';
    if (count($errors)) {
        $message = PMA_Message::error(__('<b>One or more errors have occured while processing your request:</b>'));
        $message->addString('<ul>');
        foreach ($errors as $num => $string) {
            $message->addString('<li>' . $string . '</li>');
        }
        $message->addString('</ul>');
        $message = $message->getDisplay() . "\n";
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
    $colspan = 3;
    $direction_header = '';
    if ($routine['type'] == 'PROCEDURE') {
        $colspan = 4;
        $direction_header = "            <th>" . __('Direction') . "</th>\n";
    }
    $disable_remove_parameter = '';
    if (! $routine['num_params']) {
        $disable_remove_parameter = " color: gray;' disabled='disabled";
    }

    // Create the output
    $retval  = "";
    $retval .= "<!-- START " . strtoupper($mode) . " ROUTINE FORM -->\n\n";
    $retval .= $message;
    $retval .= "<form action='db_routines.php?$url_query' method='post'>\n";
    $retval .= "<input name='{$mode}routine' type='hidden' value='1' />\n";
    $retval .= PMA_generate_common_hidden_inputs($db, $table) . "\n";
    $retval .= "<fieldset>\n";
    $retval .= "<legend>" . __('Details') . "</legend>\n";
    $retval .= "<table id='rte_table'>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Routine Name') . "</td>\n";
    $retval .= "    <td><input type='text' name='routine_name' value='{$routine['name']}' /></td>\n";
    $retval .= "</tr>\n";
    $retval .= "<tr>\n";
    $retval .= "    <td>" . __('Type') . "</td>\n";
    $retval .= "    <td>\n";
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
    $retval .= "        <table>\n";
    $retval .= "        <tr>\n";
    $retval .= $direction_header;
    $retval .= "            <th>" . __('Name') . "</th>\n";
    $retval .= "            <th>" . __('Type') . "</th>\n";
    $retval .= "            <th>" . __('Length/Values') . "</th>\n";
    $retval .= "        </tr>";
    for ($i=0; $i<$routine['num_params']; $i++) { // each parameter
        $retval .= "        <tr>\n";
        if ($routine['type'] == 'PROCEDURE') {
            $retval .= "            <td><select name='routine_param_dir[$i]'>\n";
            foreach ($param_directions as $key => $value) {
                $selected = "";
                if (! empty($routine['param_dir']) && $routine['param_dir'][$i] == $value) {
                    $selected = " selected='selected'";
                }
                $retval .= "                <option$selected>$value</option>\n";
            }
            $retval .= "            </select></td>\n";
        }
        $retval .= "            <td><input name='routine_param_name[$i]' type='text'\n";
        $retval .= "                       value='{$routine['param_name'][$i]}' /></td>\n";
        $retval .= "            <td><select name='routine_param_type[$i]'>";
        $retval .= getSupportedDatatypes(true, $routine['param_type'][$i]) . "\n";
        $retval .= "            </select></td>\n";
        $retval .= "            <td><input name='routine_param_length[$i]' type='text'\n";
        $retval .= "                       value='{$routine['param_length'][$i]}' /></td>\n";
        $retval .= "        </tr>\n";
    }
    $retval .= "        <tr>\n";
    $retval .= "            <td colspan='$colspan'>\n";
    $retval .= "                <input style='width: 49%;' type='submit' \n";
    $retval .= "                       name='routine_addparameter'\n";
    $retval .= "                       value='" . __('Add another parameter') . "'>\n";
    $retval .= "                <input style='width: 49%;$disable_remove_parameter' type='submit' \n";
    $retval .= "                       name='routine_removeparameter'\n";
    $retval .= "                       value='" . __('Remove last parameter') . "'>\n";
    $retval .= "            </td>\n";
    $retval .= "        </tr>\n";
    $retval .= "        </table>\n";
    // parameter handling end
    $retval .= "    </td>\n";
    $retval .= "</tr>\n";
    if ($routine['type'] == 'FUNCTION') {
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Return Type') . "</td>\n";
        $retval .= "    <td><select name='routine_returntype'>\n";
        $retval .= getSupportedDatatypes(true, $routine['returntype']) . "\n";
        $retval .= "    </select></td>\n";
        $retval .= "</tr>\n";
        $retval .= "<tr>\n";
        $retval .= "    <td>" . __('Return Length/Values') . "</td>\n";
        $retval .= "    <td><input type='text' name='routine_returnlength'\n";
        $retval .= "               value='{$routine['returnlength']}' /></td>\n";
        $retval .= "</tr>\n";
    }
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
    $retval .= "<fieldset class='tblFooters'>\n";
    $retval .= "    <input type='submit' name='routine_process_{$mode}routine'\n";
    $retval .= "           value='" . __('Go') . "' />\n";
    $retval .= "</fieldset>\n";
    $retval .= "</form>\n\n";
    $retval .= "<!-- END " . strtoupper($mode) . " ROUTINE FORM -->\n\n";

    return $retval;
} // displayRoutineEditor()

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
if (! empty($_GET['exportroutine']) && ! empty($_GET['routinename']) && ! empty($_GET['routinetype'])) {
    /**
     * Display the export for a routine. This is for when JS is disabled.
     */
    if ($create_proc = PMA_DBI_get_definition($db, $_GET['routinetype'], $_GET['routinename'])) {
        echo '<fieldset>' . "\n"
           . ' <legend>' . sprintf(__('Export for routine "%s"'), $_GET['routinename']) . '</legend>' . "\n"
           . '<textarea cols="40" rows="15" style="width: 100%;">' . $create_proc . '</textarea>' . "\n"
           . '</fieldset>';
    }
} else if (! empty($_REQUEST['routine_process_addroutine']) || ! empty($_REQUEST['routine_process_editroutine'])) {
    /**
     * Handle a request to create/edit a routine
     */

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
                    $params .= $_REQUEST['routine_param_dir'][$i] . " " . $_REQUEST['routine_param_name'][$i] . " "
                            . $_REQUEST['routine_param_type'][$i];
                } else if ($_REQUEST['routine_type'] == 'FUNCTION') {
                    $params .= $_REQUEST['routine_param_name'][$i] . " " . $_REQUEST['routine_param_type'][$i];
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
    if (! empty($_REQUEST['routine_sqlsecutiry'])) {
        $query .= 'SQL SECURITY ' . $_REQUEST['routine_sqlsecutiry'] . ' ';
    }
    if (! empty($_REQUEST['routine_definition'])) {
        $query .= $_REQUEST['routine_definition'];
    } else {
        $routine_errors[] = __('You must provide a routine Definition.');
    }
    if (! count($routine_errors)) {
        // Execute the created queries
        // FIXME: should only execute DROP on edit, not add
        // TODO: need to keep a backup copy of the routine, in case the DROP is successful, but the CREATE fails!
        $res = PMA_DBI_query("DROP PROCEDURE IF EXISTS " . PMA_backquote($_REQUEST['routine_name']));
        $res = PMA_DBI_query("DROP FUNCTION IF EXISTS " . PMA_backquote($_REQUEST['routine_name']));
        $res = PMA_DBI_query($query);
        // If the query fails, an error message will be automatically
        // shown, so here we only show a success message.
        $message = PMA_Message::success(__('Routine %1$s has been created.'));
        $message->addParam(PMA_backquote($_REQUEST['routine_name']));
        $message->display();
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
        } else {
            $routine = getFormInputFromRequest();
        }
        $mode = 'edit';
    }
    // Show form
    echo displayRoutineEditor($mode, $operation, $routine, $routine_errors);
    require './libraries/footer.inc.php';
    // exit;
}

/**
 * Generate the conditional classes that will be used to attach jQuery events to links.
 */
$conditional_class_add    = '';
$conditional_class_edit   = '';
$conditional_class_drop   = '';
$conditional_class_export = '';
if ($GLOBALS['cfg']['AjaxEnable']) {
    $conditional_class_add    = 'class="add_routine_anchor"';
    $conditional_class_edit   = 'class="edit_routine_anchor"';
    $conditional_class_drop   = 'class="drop_procedure_anchor"';
    $conditional_class_export = 'class="export_procedure_anchor"';
}

/**
 * Display a list of available routines
 */

$routines = PMA_DBI_fetch_result('SELECT SPECIFIC_NAME,ROUTINE_NAME,ROUTINE_TYPE,DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA= \'' . PMA_sqlAddslashes($db,true) . '\';');

echo '<fieldset>' . "\n";
echo ' <legend>' . __('Routines') . '</legend>' . "\n";

if (! $routines) {
    echo __('There are no routines to display.');
} else {
    echo '<div style="display: none;" id="no_routines">' . __('There are no routines to display.') . '</div>';
    echo '<table class="data" id="routine_list">';
    echo sprintf('<tr>
                      <th>%s</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>%s</th>
                      <th>%s</th>
                </tr>',
          __('Name'),
          __('Type'),
          __('Return type'));
    $ct=0;
    $delimiter = '//';
    foreach ($routines as $routine) {

        // information_schema (at least in MySQL 5.0.45)
        // does not return the routine parameters
        // so we rely on PMA_DBI_get_definition() which
        // uses SHOW CREATE

        $create_proc = PMA_DBI_get_definition($db, $routine['ROUTINE_TYPE'], $routine['SPECIFIC_NAME']);
        $definition = 'DROP ' . $routine['ROUTINE_TYPE'] . ' ' . PMA_backquote($routine['SPECIFIC_NAME']) . $delimiter . "\n"
            .  $create_proc . "\n";

        //if ($routine['ROUTINE_TYPE'] == 'PROCEDURE') {
        //    $sqlUseProc  = 'CALL ' . $routine['SPECIFIC_NAME'] . '()';
        //} else {
        //    $sqlUseProc = 'SELECT ' . $routine['SPECIFIC_NAME'] . '()';
            /* this won't get us far: to really use the function
               i'd need to know how many parameters the function needs and then create
               something to ask for them. As i don't see this directly in
               the table i am afraid that requires parsing the ROUTINE_DEFINITION
               and i don't really need that now so i simply don't offer
               a method for running the function*/
        //}
        if ($routine['ROUTINE_TYPE'] == 'PROCEDURE') {
            $sqlDropProc = 'DROP PROCEDURE IF EXISTS ' . PMA_backquote($routine['SPECIFIC_NAME']);
        } else {
            $sqlDropProc = 'DROP FUNCTION IF EXISTS ' . PMA_backquote($routine['SPECIFIC_NAME']);
        }

        echo sprintf('<tr class="%s">
                          <td><span class="drop_sql" style="display:none;">%s</span><strong>%s</strong></td>
                          <td>%s</td>
                          <td>%s</td>
                          <td><div class="create_sql" style="display: none;">%s</div>%s</td>
                          <td>%s</td>
                          <td>%s</td>
                          <td>%s</td>
                     </tr>',
                     ($ct%2 == 0) ? 'even' : 'odd',
                     $sqlDropProc,
                     $routine['ROUTINE_NAME'],
                     '<a ' . $conditional_class_edit . ' href="db_routines.php?' . $url_query
                           . '&amp;editroutine=1'
                           . '&amp;routine_name=' . urlencode($routine['SPECIFIC_NAME'])
                           . '">' . $titles['Edit'] . '</a>',
                     ! empty($definition) ? PMA_linkOrButton('#', $titles['Execute']) : '&nbsp;',
                     $create_proc,
                     '<a ' . $conditional_class_export . ' href="db_routines.php?' . $url_query
                           . '&amp;exportroutine=1'
                           . '&amp;routinename=' . urlencode($routine['SPECIFIC_NAME'])
                           . '&amp;routinetype=' . urlencode($routine['ROUTINE_TYPE'])
                           . '">' . $titles['Export'] . '</a>',
                     '<a ' . $conditional_class_drop. ' href="sql.php?' . $url_query
                           . '&amp;sql_query=' . urlencode($sqlDropProc)
                           . '" >' . $titles['Drop'] . '</a>',
                     $routine['ROUTINE_TYPE'],
                     $routine['DTD_IDENTIFIER']);
        $ct++;
    }
    echo '</table>';
}
echo '</fieldset>' . "\n";

/**
 * Display the form for adding a new routine
 */
echo '<fieldset>' . "\n"
   . '    <a href="db_routines.php?' . $url_query . '&amp;addroutine=1" class="' . $conditional_class_add . '">' . "\n"
   . PMA_getIcon('b_routine_add.png') . __('Add a new Routine') . '</a>' . "\n"
   . PMA_showMySQLDocu('SQL-Syntax', 'CREATE_PROCEDURE')
   . '</fieldset>' . "\n";

?>
