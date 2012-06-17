<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server variables
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_variables.js');

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';

/**
 * Required to display documentation links
 */
require 'libraries/server_variables_doc.php';

/**
 * Ajax request
 */

if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
    // Send with correct charset
    if (isset($_REQUEST['type'])) {
        switch($_REQUEST['type']) {
        case 'getval':
            header('Content-Type: text/html; charset=UTF-8');
            $varValue = PMA_DBI_fetch_single_row(
                'SHOW GLOBAL VARIABLES WHERE Variable_name="'
                . PMA_Util::sqlAddSlashes($_REQUEST['varName']) . '";',
                'NUM'
            );
            if (isset($VARIABLE_DOC_LINKS[$_REQUEST['varName']][3])
                && $VARIABLE_DOC_LINKS[$_REQUEST['varName']][3] == 'byte'
            ) {
                exit(
                    implode(
                        ' ', PMA_Util::formatByteDown($varValue[1], 3, 3)
                    )
                );
            }
            exit($varValue[1]);
            break;

        case 'setval':
            $value = $_REQUEST['varValue'];

            if (isset($VARIABLE_DOC_LINKS[$_REQUEST['varName']][3])
                && $VARIABLE_DOC_LINKS[$_REQUEST['varName']][3] == 'byte'
                && preg_match(
                    '/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i',
                    $value,
                    $matches
                )
            ) {
                $exp = array(
                    'kb' => 1,
                    'kib' => 1,
                    'mb' => 2,
                    'mib' => 2,
                    'gb' => 3,
                    'gib' => 3
                );
                $value = floatval($matches[1]) * PMA_Util::pow(
                    1024,
                    $exp[strtolower($matches[3])]
                );
            } else {
                $value = PMA_Util::sqlAddSlashes($value);
            }

            if (! is_numeric($value)) {
                $value="'" . $value . "'";
            }

            $response = PMA_Response::getInstance();
            if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName'])
                && PMA_DBI_query(
                    'SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value
                )
            ) {
                // Some values are rounded down etc.
                $varValue = PMA_DBI_fetch_single_row(
                    'SHOW GLOBAL VARIABLES WHERE Variable_name="'
                    . PMA_Util::sqlAddSlashes($_REQUEST['varName'])
                    . '";', 'NUM'
                );
                $response->addJSON(
                    'variable',
                    formatVariable($_REQUEST['varName'], $varValue[1])
                );
            } else {
                $response->isSuccess(false);
                $response->addJSON(
                    'error',
                    __('Setting variable failed')
                );
            }
            exit;
            break;
        }
    }
}

/**
 * Displays the sub-page heading
 */
$output = '<h2>' . PMA_Util::getImage('s_vars.png')
    . '' . __('Server variables and settings') . "\n"
    . PMA_Util::showMySQLDocu(
        'server_system_variables', 'server_system_variables'
    )
    . '</h2>' . "\n";

/**
 * Link templates
 */
$url = htmlspecialchars('server_variables.php?' . PMA_generate_common_url($db));
$output .= '<a href="#" class="editLink hide" onclick="return editVariable(this);">';
$output .= PMA_Util::getIcon('b_edit.png', __('Edit')) . '</a>';
$output .= '<a href="' . $url . '" class="ajax saveLink hide">';
$output .= PMA_Util::getIcon('b_save.png', __('Save')) . '</a> ';
$output .= '<a href="#" class="cancelLink hide">';
$output .= PMA_Util::getIcon('b_close.png', __('Cancel')) . '</a> ';


/**
 * Sends the queries and buffers the results
 */
$serverVarsSession = PMA_DBI_fetch_result('SHOW SESSION VARIABLES;', 0, 1);
$serverVars = PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES;', 0, 1);


/**
 * Displays the page
 */
$output .= '<fieldset id="tableFilter" style="display:none;">'
    . '<legend>' . __('Filters') . '</legend>'
    . '<div class="formelement">'
    . '<label for="filterText">' .  __('Containing the word:') . '</label>'
    . '<input name="filterText" type="text" id="filterText"'
    . ' style="vertical-align: baseline;" />'
    . '</div>'
    . '</fieldset>';

$output .= '<table id="serverVariables" class="data filteredData noclick">'
    . '<thead>'
    . '<tr><th>' .  __('Variable') . '</th>'
    . '<th class="valueHeader">'
    . __('Session value') . ' / ' . __('Global value')
    . '</th>'
    . '<th>' . __('Documentation') . '</th>'
    . '</tr>'
    . '</thead>'
    . '<tbody>';

$odd_row = true;
foreach ($serverVars as $name => $value) {
    $has_session_value = isset($serverVarsSession[$name])
        && $serverVarsSession[$name] != $value;
    $row_class = ($odd_row ? 'odd' : 'even') . ' '
        . ($has_session_value ? 'diffSession' : '');

    $output .= '<tr class="' . $row_class . '">'
        . '<th class="nowrap">' . htmlspecialchars(str_replace('_', ' ', $name))
        . '</th>'
        . '<td class="value' . (PMA_isSuperuser() ? ' editable' : '') . '">'
        . formatVariable($name, $value)
        . '</td>'
        . '<td class="value">';

    // To display variable documentation link
    if (isset($VARIABLE_DOC_LINKS[$name])) {
        $output .= PMA_Util::showMySQLDocu(
            $VARIABLE_DOC_LINKS[$name][1],
            $VARIABLE_DOC_LINKS[$name][1],
            false,
            $VARIABLE_DOC_LINKS[$name][2] . '_' . $VARIABLE_DOC_LINKS[$name][0]
        );
    }

    $output .= '</td>';

    if ($has_session_value) {
        $output .= '</tr>'
            . '<tr class="' . ($odd_row ? 'odd' : 'even') . '">'
            . '<td>(' . __('Session value') . ')</td>'
            . '<td class="value">' . formatVariable($name, $serverVarsSession[$name]) . '</td>'
            . '<td class="value"></td>';
    }

    $output .= '</tr>';
    $odd_row = ! $odd_row;
}
$output .= '</tbody>'
    . '</table>';

$response->addHtml($output);

/**
 * Format Variable
 *
 * @param string  $name  variable name
 * @param numeric $value variable value
 *
 * @return formatted string
 */
function formatVariable($name, $value)
{
    global $VARIABLE_DOC_LINKS;

    if (is_numeric($value)) {
        if (isset($VARIABLE_DOC_LINKS[$name][3])
            && $VARIABLE_DOC_LINKS[$name][3]=='byte'
        ) {
            return '<abbr title="'
                . PMA_Util::formatNumber($value, 0) . '">'
                . implode(' ', PMA_Util::formatByteDown($value, 3, 3))
                . '</abbr>';
        } else {
            return PMA_Util::formatNumber($value, 0);
        }
    }
    return htmlspecialchars($value);
}

?>
