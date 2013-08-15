<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for multi submit forms
 *
 * @usedby  mult_submits.inc.php
 *  
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Gets table primary key
 *
 * @param string $db    name of db
 * @param string $table name of table
 *
 * @return string
 */
function PMA_getKeyForTablePrimary($db, $table)
{
    $GLOBALS['dbi']->selectDb($db);
    $result = $GLOBALS['dbi']->query(
        'SHOW KEYS FROM ' . PMA_Util::backquote($table) . ';'
    );
    $primary = '';
    while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
        // Backups the list of primary keys
        if ($row['Key_name'] == 'PRIMARY') {
            $primary .= $row['Column_name'] . ', ';
        }
    } // end while
    $GLOBALS['dbi']->freeResult($result);
    
    return $primary;
}

/**
 * Gets HTML for replace_prefix_tbl or copy_tbl_change_prefix
 *
 * @param string $what        mult_submit type
 * @param string $action      action type
 * @param array  $_url_params URL params
 *
 * @return string
 */
function PMA_getHtmlForReplacePrefixTable($what, $action, $_url_params)
{
    $html  = '<form action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<fieldset class = "input">';
    $html .= '<legend>';
    if ($what == 'replace_prefix_tbl') {
        $html .= __('Replace table prefix:');
    } else {
        $html .= __('Copy table with prefix:');
    }
    $html .= '</legend>';
    $html .= '<table>';
    $html .= '<tr>';
    $html .= '<td>' . __('From') . '</td>';
    $html .= '<td>';
    $html .= '<input type="text" name="from_prefix" id="initialPrefix" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td>' . __('To') . '</td>';
    $html .= '<td>';
    $html .= '<input type="text" name="to_prefix" id="newPrefix" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';
    $html .= '</fieldset>';
    $html .= '<fieldset class="tblFooters">';
    $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
    $html .= '<input type="submit" value="' . __('Submit') . '" id="buttonYes" />';
    $html .= '</fieldset>';
    $html .= '</form>';
    
    return $html;
}

/**
 * Gets HTML for add_prefix_tbl
 *
 * @param string $action      action type
 * @param array  $_url_params URL params
 *
 * @return string
 */
function PMA_getHtmlForAddPrefixTable($action, $_url_params)
{
    $html  = '<form action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<fieldset class = "input">';
    $html .= '<legend>' . __('Add table prefix:') . '</legend>';
    $html .= '<table>';
    $html .= '<tr>';
    $html .= '<td>' . __('Add prefix') . '</td>';
    $html .= '<td>';
    $html .= '<input type="text" name="add_prefix" id="txtPrefix" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '</table>';
    $html .= '</fieldset>';
    $html .= '<fieldset class="tblFooters">';
    $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
    $html .= '<input type="submit" value="' . __('Submit') . '" id="buttonYes" />';
    $html .= '</fieldset>';
    $html .= '</form>';
    
    return $html;
}

/**
 * Gets HTML for other mult_submits actions
 *
 * @param string $what        mult_submit type
 * @param string $action      action type
 * @param array  $_url_params URL params
 * @param array  $full_query  full sql query string
 *
 * @return string
 */
function PMA_getHtmlForOtherActions($what, $action, $_url_params, $full_query)
{
    $html  = '<fieldset class="confirmation">';
    $html .= '<legend>';
    if ($what == 'drop_db') {
        $html .=  __('You are about to DESTROY a complete database!') . ' ';
    }
    $html .= __('Do you really want to execute the following query?');
    $html .= '</legend>';
    $html .= '<code>' . $full_query . '</code>';
    $html .= '</fieldset>';
    $html .= '<fieldset class="tblFooters">';
    $html .= '<form action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    // Display option to disable foreign key checks while dropping tables
    if ($what == 'drop_tbl') {
        $html .= '<div id="foreignkeychk">';
        $html .= '<span class="fkc_switch">';
        $html .= __('Foreign key check:');
        $html .= '</span>';
        $html .= '<span class="checkbox">';
        $html .= '<input type="checkbox" name="fk_check" value="1" ' 
            . 'id="fkc_checkbox"';
        $default_fk_check_value = $GLOBALS['dbi']->fetchValue(
            'SHOW VARIABLES LIKE \'foreign_key_checks\';', 0, 1
        ) == 'ON';
        if ($default_fk_check_value) {
            $html .= ' checked="checked"';
        }
        $html .= '/></span>';
        $html .= '<span id="fkc_status" class="fkc_switch">';
        $html .= ($default_fk_check_value) ? __('(Enabled)') : __('(Disabled)');
        $html .= '</span>';
        $html .= '</div>';
    }
    $html .= '<input type="hidden" name="mult_btn" value="' . __('Yes') . '" />';
    $html .= '<input type="submit" value="' . __('Yes') . '" id="buttonYes" />';
    $html .= '</form>';

    $html .= '<form action="' . $action . '" method="post">';
    $html .= PMA_URL_getHiddenInputs($_url_params);
    $html .= '<input type="hidden" name="mult_btn" value="' . __('No') . '" />';
    $html .= '<input type="submit" value="' . __('No') . '" id="buttonNo" />';
    $html .= '</form>';
    $html .= '</fieldset>';
    
    return $html;
}

?>
