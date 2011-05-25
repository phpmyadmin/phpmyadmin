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
$param_datatypes     = getSupportedDatatypes();
$param_directions    = array('IN', 'OUT', 'INOUT');
$param_sqldataaccess = array('', 'CONTAINS SQL', 'NO SQL', 'READS SQL DATA', 'MODIFIES SQL DATA');

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
                        $retval .= "<option >$subvalue</option>";
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
}

/**
 * This function will generate the values that are required to complete the "Add new routine" form
 * It is especially necessary to handle the 'Add another parameter' and 'Remove last parameter'
 * functionalities when JS is disabled.
 */
function getFormInputFromRequest()
{
    global $_REQUEST, $param_directions, $param_datatypes, $param_sqldataaccess;

    $retval = array();
    $retval['name'] = isset($_REQUEST['routine_name']) ? htmlspecialchars($_REQUEST['routine_name']) : '';
    $retval['type']         = 'PROCEDURE';
    $retval['type_toggle']  = 'FUNCTION';
    if (! empty($_REQUEST['routine_changetype']) && isset($_REQUEST['routine_type'])) {
        if ($_REQUEST['routine_type'] == 'PROCEDURE') {
            $retval['type']         = 'FUNCTION';
            $retval['type_toggle']  = 'PROCEDURE';
        } else if ($_REQUEST['routine_type'] == 'FUNCTION') {
            $retval['type']         = 'PROCEDURE';
            $retval['type_toggle']  = 'FUNCTION';
        }
     } else if (isset($_REQUEST['routine_type'])) {
        if ($_REQUEST['routine_type'] == 'PROCEDURE') {
            $retval['type']         = 'PROCEDURE';
            $retval['type_toggle']  = 'FUNCTION';
        } else if ($_REQUEST['routine_type'] == 'FUNCTION') {
            $retval['type']         = 'FUNCTION';
            $retval['type_toggle']  = 'PROCEDURE';
        }
    }
    $retval['param_dir']  = array();
    $retval['param_name'] = array();
    $retval['param_type'] = array();
    $retval['num_params'] = 0;
    if (isset($_REQUEST['routine_param_dir']) && isset($_REQUEST['routine_param_name']) && isset($_REQUEST['routine_param_type'])
        && is_array($_REQUEST['routine_param_dir']) && is_array($_REQUEST['routine_param_name']) && is_array($_REQUEST['routine_param_type'])) {
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
        $temp_num_params = 0;
        $retval['param_name'] = $_REQUEST['routine_param_name'];
        foreach ($retval['param_name'] as $key => $value) {
            $retval['param_name'][$key] = htmlspecialchars($value);
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
    }
    $retval['returntype'] = '';
    if (isset($_REQUEST['routine_returntype']) && in_array($_REQUEST['routine_returntype'], $param_datatypes, true)) {
        $retval['returntype'] = $_REQUEST['routine_returntype'];
    }
    $retval['returnlength']    = isset($_REQUEST['routine_returnlength']) ? htmlspecialchars($_REQUEST['routine_returnlength']) : '';
    $retval['definition']      = isset($_REQUEST['routine_definition'])   ? htmlspecialchars($_REQUEST['routine_definition']) : '';
    $retval['isdeterministic'] = '';
    if (isset($_REQUEST['routine_isdeterministic']) && strtolower($_REQUEST['routine_isdeterministic']) == 'on') {
        $retval['isdeterministic'] = " checked='checked'";
    }
    $retval['definer'] = isset($_REQUEST['routine_definer']) ? htmlspecialchars($_REQUEST['routine_definer']) : '';
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
    $retval['comment'] = isset($_REQUEST['routine_comment']) ? htmlspecialchars($_REQUEST['routine_comment']) : '';

    return $retval;
} // end function getFormInputFromRequest()

/**
 *  ### MAIN ##########################################################################################################
 */

// $url_query .= '&amp;goto=db_routines.php' . rawurlencode("?db=$db"); // FIXME

/**
 * Get all available routines
 */
$routines = PMA_DBI_fetch_result('SELECT SPECIFIC_NAME,ROUTINE_NAME,ROUTINE_TYPE,DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA= \'' . PMA_sqlAddslashes($db,true) . '\';');

/**
 * Generate the conditional classes that will be used to attach jQuery events to links.
 */
$conditional_class_add    = '';
$conditional_class_drop   = '';
$conditional_class_export = '';
if ($GLOBALS['cfg']['AjaxEnable']) {
    $conditional_class_add    = 'class="add_routine_anchor"';
    $conditional_class_drop   = 'class="drop_procedure_anchor"';
    $conditional_class_export = 'class="export_procedure_anchor"';
}

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
} else if (! empty($_REQUEST['routine_process_addroutine'])) {
    /**
     * Handle a request to create a routine
     */
//    var_dump($_REQUEST);
    $definer = '';
    if (! empty($_REQUEST['routine_definer'])) {
        $definer = 'DEFINER ' . PMA_sqlAddSlashes($_REQUEST['routine_definer']);
    }
    $name = PMA_sqlAddSlashes($_REQUEST['routine_name']);
    $params = '';
    if (! empty($_REQUEST['routine_param_dir']) && ! empty($_REQUEST['routine_param_name']) && ! empty($_REQUEST['routine_param_type'])
        && is_array($_REQUEST['routine_param_dir']) && is_array($_REQUEST['routine_param_name']) && is_array($_REQUEST['routine_param_type'])) {
        // FIXME: this is just wrong right now...
        for ($i=0; $i<count($_REQUEST['routine_param_dir']); $i++) {
            $params .= $_REQUEST['routine_param_dir'][$i] . " " . $_REQUEST['routine_param_name'][$i] . " " . $_REQUEST['routine_param_type'][$i];
            if ($i != count($_REQUEST['routine_param_dir'])-1) {
                $params .= ",";
            }
        }
        $params = "(" . $params . ")";
    }
    $returns = '';
    if ($_REQUEST['routine_type'] == 'FUNCTION') {
        $returns = "RETURNS {$_REQUEST['routine_returntype']}({$_REQUEST['routine_returnlength']})";
    }
    $comment = '';
    if (! empty($_REQUEST['routine_comment'])) {
        $comment = "COMMENT '{$_REQUEST['routine_comment']}'";
    }
    $deterministic = '';
    if (! empty($_REQUEST['routine_isdeterministic'])) {
        $deterministic = 'DETERMINISTIC';
    } else {
        $deterministic = 'NOT DETERMINISTIC';
    }
    $sqldataaccess = '';
    if (! empty($_REQUEST['routine_sqldataaccess']) && in_array($_REQUEST['routine_sqldataaccess'], $param_sqldataaccess, true)) {
        $sqldataaccess = $_REQUEST['routine_sqldataaccess'];
    }
    $sqlsecurity = '';
    if (! empty($_REQUEST['routine_sqlsecutiry'])) {
        $sqlsecurity = "SQL SECURITY" . $_REQUEST['routine_sqlsecutiry'];
    }
    $definition = '';
    if (! empty($_REQUEST['routine_definition'])) {
        $definition = 'BEGIN ' . $_REQUEST['routine_definition'] . ' END';
    }
    
    $query = "DELIMITER //
                CREATE $definer {$_REQUEST['routine_type']} $name $params $returns $comment $deterministic $sqldataaccess $sqlsecurity $definition
                //";
    var_dump($query);
    exit;
} else if (! empty($_REQUEST['addroutine']) || ! empty($_REQUEST['routine_addparameter']) || ! empty($_REQUEST['routine_removeparameter']) || ! empty($_REQUEST['routine_changetype'])) {
    /**
     * Display a form used to create a new routine
     */

    // Get variables from the request (if any)
    $routine = getFormInputFromRequest();

    // Show form
    if ($GLOBALS['is_ajax_request'] != true) {
        echo "<h2>" . __("Create Routine") . "</h2>\n";
    }
    echo '<form action="db_routines.php?' . $url_query . '" method="post" >' . "\n"
       . PMA_generate_common_hidden_inputs($db, $table)
       . "<fieldset>\n"
       . ' <legend>' . __('Details') . '</legend>' . "\n";
    echo "<table id='rte_table'>\n";

    echo "<tr><td>" . __('Routine Name') . "</td><td><input type='text' name='routine_name' value='{$routine['name']}'/></td></tr>\n";
    echo "<tr><td>" . __('Type') . "</td><td>
                    <input name='routine_type' type='hidden' value='{$routine['type']}' />
                    <div style='width: 49%; float: left; text-align: center; font-weight: bold;'>{$routine['type']}</div>
                    <input style='width: 49%;' type='submit' name='routine_changetype' value='".sprintf(__('Change to %s'), $routine['type_toggle'])."' />
          </td></tr>\n";

    echo "<tr><td>" . __('Parameters') . "</td><td>\n";
// parameter handling start
    echo "<table>";
    echo "<tr><th>" . __('Direction') . "</th><th>" . __('Name') . "</th><th>" . __('Type') . "</th></tr>";
    if (! empty($_REQUEST['routine_addparameter']) || !$routine['num_params']) {
        $routine['param_dir'][]  = '';
        $routine['param_name'][] = '';
        $routine['param_type'][] = '';
        $routine['num_params']++;
    } else if (! empty($_REQUEST['routine_removeparameter'])) {
        unset($routine['param_dir'][$routine['num_params']-1]);
        unset($routine['param_name'][$routine['num_params']-1]);
        unset($routine['param_type'][$routine['num_params']-1]);
        $routine['num_params']--;
    }
    for ($i=0; $i<$routine['num_params']; $i++) {
        echo "
                <tr><td>
                <select name='routine_param_dir[$i]'>";
        foreach ($param_directions as $key => $value) {
            if ($routine['param_dir'][$i] == $value) {
                echo "<option selected='selected'>$value</option>";
            } else {
                echo "<option>$value</option>";
            }
        }
        echo "
                </select>
                </td><td>
                <input name='routine_param_name[$i]' type='text' value='{$routine['param_name'][$i]}' />
                </td><td>
                <select name='routine_param_type[$i]'>";
        echo getSupportedDatatypes(true, $routine['param_type'][$i]);
        echo "
                </select>
                </td></tr>";
    }
    echo "<tr><td colspan='3'>
                <input style='width: 49%;' type='submit' name='routine_addparameter' value='" . __('Add another parameter') . "'>
                <input style='width: 49%;' type='submit' name='routine_removeparameter' value='" . __('Remove last parameter') . "'>
          </td></tr>";
    echo "</table>";
// parameter handling end

    echo "</td></tr>\n";

    if ($routine['type'] == 'FUNCTION') {
        echo "<tr><td>" . __('Return Type') . "</td><td>
                                            <select name='routine_returntype'>";
        echo getSupportedDatatypes(true, $routine['returntype']);
        echo "
                                            </select>
              </td></tr>\n";
        echo "<tr><td>" . __('Return Length/Values') . "</td><td><input type='text' name='routine_returnlength' value='{$routine['returnlength']}' /></td></tr>\n";
    }

    echo "<tr><td>" . __('Definition') . "</td><td><textarea name='routine_definition'>{$routine['definition']}</textarea></td></tr>\n";
    echo "<tr><td>" . __('Is Deterministic') . "</td><td><input type='checkbox' name='routine_isdeterministic' {$routine['isdeterministic']}/></td></tr>\n";
    echo "<tr><td>" . __('Definer') . "</td><td><input type='text' name='routine_definer' value='{$routine['definer']}'/></td></tr>\n";
    echo "<tr><td>" . __('Security Type') . "</td><td>
                                        <select name='routine_securitytype'>
                                            <option value='DEFINER'{$routine['securitytype_definer']}>DEFINER</option>
                                            <option value='INVOKER'{$routine['securitytype_invoker']}>INVOKER</option>
                                        </select>
          </td></tr>\n";
    echo "<tr><td>" . __('SQL Data Access') . "</td><td>
                                        <select name='routine_sqldataaccess'>";
    foreach ($param_sqldataaccess as $key => $value) {
        if ($routine['sqldataaccess'] == $value) {
            echo "<option selected='selected'>$value</option>";
        } else {
            echo "<option>$value</option>";
        }
    }
    echo "
                                        </select>
          </td></tr>\n";
    echo "<tr><td>" . __('Comment') . "</td><td><input type='text' name='routine_comment' value='{$routine['comment']}'/></td></tr>\n";
    echo "</table>\n";
    echo "</fieldset>\n";
    echo '<fieldset class="tblFooters">';
    echo '    <input type="submit" name="routine_process_addroutine" value="' . __('Go') . '" />';
    echo '</fieldset>';
    echo "</form>\n";
    exit;
}

/**
 * Display a list of available routines
 */
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
                     ! empty($definition) ? PMA_linkOrButton('db_sql.php?' . $url_query
                                               . '&amp;sql_query=' . urlencode($definition)
                                               . '&amp;show_query=1&amp;db_query_force=1'
                                               . '&amp;delimiter=' . urlencode($delimiter), $titles['Edit']) : '&nbsp;',
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
