<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_variables.js');
$common_functions = PMA_CommonFunctions::getInstance();

PMA_addJSVar('pma_token', $_SESSION[' PMA_token ']);
PMA_addJSVar('url_query', str_replace('&amp;', '&', PMA_generate_common_url($db)));
PMA_addJSVar('is_superuser', PMA_isSuperuser() ? true : false);


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
                . $common_functions->sqlAddSlashes($_REQUEST['varName']) . '";', 'NUM'
            );
            if (isset($VARIABLE_DOC_LINKS[$_REQUEST['varName']][3])
                && $VARIABLE_DOC_LINKS[$_REQUEST['varName']][3] == 'byte'
            ) {
                exit(implode(' ', $common_functions->formatByteDown($varValue[1], 3, 3)));
            }
            exit($varValue[1]);
            break;

        case 'setval':
            $value = $_REQUEST['varValue'];

            if (isset($VARIABLE_DOC_LINKS[$_REQUEST['varName']][3])
                && $VARIABLE_DOC_LINKS[$_REQUEST['varName']][3] == 'byte'
                && preg_match('/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i', $value, $matches)
            ) {
                $exp = array('kb' => 1, 'kib' => 1, 'mb' => 2, 'mib' => 2, 'gb' => 3, 'gib' => 3);
                $value = floatval($matches[1]) * $common_functions->pow(1024, $exp[strtolower($matches[3])]);
            } else {
                $value = $common_functions->sqlAddSlashes($value);
            }

            if (! is_numeric($value)) {
                $value="'" . $value . "'";
            }

            $response = PMA_Response::getInstance();
            if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName'])
                && PMA_DBI_query('SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value)
            ) {
                // Some values are rounded down etc.
                $varValue = PMA_DBI_fetch_single_row(
                    'SHOW GLOBAL VARIABLES WHERE Variable_name="'
                    . $common_functions->sqlAddSlashes($_REQUEST['varName']) . '";', 'NUM'
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
echo '<h2>' . $common_functions->getImage('s_vars.png')
   . '' . __('Server variables and settings') . "\n"
   . $common_functions->showMySQLDocu('server_system_variables', 'server_system_variables')
   . '</h2>' . "\n";

/**
 * Sends the queries and buffers the results
 */
$serverVarsSession = PMA_DBI_fetch_result('SHOW SESSION VARIABLES;', 0, 1);
$serverVars = PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES;', 0, 1);


/**
 * Displays the page
 */
?>
<fieldset id="tableFilter" style="display:none;">
<legend><?php echo __('Filters'); ?></legend>
<div class="formelement">
    <label for="filterText"><?php echo __('Containing the word:'); ?></label>
    <input name="filterText" type="text" id="filterText" style="vertical-align: baseline;" />
</div>
</fieldset>
<table id="serverVariables" class="data filteredData noclick">
<thead>
<tr><th><?php echo __('Variable'); ?></th>
    <th class="valueHeader">
<?php
echo __('Session value') . ' / ' . __('Global value');
?>
    </th>
    <th><?php echo __('Documentation'); ?></th>
</tr>
</thead>
<tbody>
<?php
$odd_row = true;
foreach ($serverVars as $name => $value) {
    $has_session_value = isset($serverVarsSession[$name]) && $serverVarsSession[$name] != $value;
    $row_class = ($odd_row ? 'odd' : 'even') . ' ' . ($has_session_value ? 'diffSession' : '');
    ?>
<tr class="<?php echo $row_class; ?>">
    <th class="nowrap"><?php echo htmlspecialchars(str_replace('_', ' ', $name)); ?></th>
    <td class="value"><?php echo formatVariable($name, $value); ?></td>
    <td class="value"><?php
    // To display variable documentation link
    if (isset($VARIABLE_DOC_LINKS[$name])) {
        echo $common_functions->showMySQLDocu($VARIABLE_DOC_LINKS[$name][1], $VARIABLE_DOC_LINKS[$name][1], false, $VARIABLE_DOC_LINKS[$name][2] . '_' . $VARIABLE_DOC_LINKS[$name][0]);
    }
    ?></td>
    <?php
    if ($has_session_value) {
        ?>
</tr>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?> ">
    <td>(<?php echo __('Session value'); ?>)</td>
    <td class="value"><?php echo formatVariable($name, $serverVarsSession[$name]); ?></td>
    <td class="value"></td>
    <?php } ?>
</tr>
    <?php
    $odd_row = ! $odd_row;
}
?>
</tbody>
</table>
<?php

function formatVariable($name, $value)
{
    global $VARIABLE_DOC_LINKS;
    
    $common_functions = PMA_CommonFunctions::getInstance();

    if (is_numeric($value)) {
        if (isset($VARIABLE_DOC_LINKS[$name][3]) && $VARIABLE_DOC_LINKS[$name][3]=='byte') {
            return '<abbr title="'.$common_functions->formatNumber($value, 0).'">'.implode(' ', $common_functions->formatByteDown($value, 3, 3)).'</abbr>';
        } else {
            return $common_functions->formatNumber($value, 0);
        }
    }
    return htmlspecialchars($value);
}

?>
