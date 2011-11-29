<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * no need for variables importing
 * @ignore
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'server_variables.js';

PMA_AddJSVar('pma_token', $_SESSION[' PMA_token ']);
PMA_AddJSVar('url_query', str_replace('&amp;', '&', PMA_generate_common_url($db)));
PMA_AddJSVar('is_superuser', PMA_isSuperuser() ? true : false);


/**
 * Does the common work
 */
require './libraries/server_common.inc.php';

/**
 * Required to display documentation links
 */
require './libraries/server_variables_doc.php';

/**
 * Ajax request
 */

if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
    // Send with correct charset
    header('Content-Type: text/html; charset=UTF-8');

    if (isset($_REQUEST['type'])) {
        switch($_REQUEST['type']) {
            case 'getval':
                $varValue = PMA_DBI_fetch_single_row('SHOW GLOBAL VARIABLES WHERE Variable_name="' . PMA_sqlAddslashes($_REQUEST['varName']) . '";', 'NUM');
                if (isset($VARIABLE_DOC_LINKS[$_REQUEST['varName']][3])
                    && $VARIABLE_DOC_LINKS[$_REQUEST['varName']][3] == 'byte') {
                    exit(implode(' ', PMA_formatByteDown($varValue[1], 3, 3)));
                }
                exit($varValue[1]);
                break;

            case 'setval':
                $value = $_REQUEST['varValue'];

                if (isset($VARIABLE_DOC_LINKS[$_REQUEST['varName']][3])
                   && $VARIABLE_DOC_LINKS[$_REQUEST['varName']][3] == 'byte'
                   && preg_match('/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i', $value, $matches)) {
                    $exp = array('kb' => 1, 'kib' => 1, 'mb' => 2, 'mib' => 2, 'gb' => 3, 'gib' => 3);
                    $value = floatval($matches[1]) * pow(1024, $exp[strtolower($matches[3])]);
                } else {
                    $value = PMA_sqlAddslashes($value);
                }

                if (! is_numeric($value)) $value="'" . $value . "'";

                if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName']) && PMA_DBI_query('SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value)) {
                    // Some values are rounded down etc.
                    $varValue = PMA_DBI_fetch_single_row('SHOW GLOBAL VARIABLES WHERE Variable_name="' . PMA_sqlAddslashes($_REQUEST['varName']) . '";', 'NUM');

                    exit(json_encode(array(
                        'success' => true,
                        'variable' => formatVariable($_REQUEST['varName'], $varValue[1])
                        ))
                    );
                }

                exit(json_encode(array(
                    'success' => false,
                    'error' => __('Setting variable failed')
                    ))
                );
                break;
        }
    }
}

/**
 * Displays the links
 */
require './libraries/server_links.inc.php';


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($cfg['MainPageIconic'] ? PMA_getImage('s_vars.png') : '')
   . '' . __('Server variables and settings') . "\n"
   . PMA_showMySQLDocu('server_system_variables', 'server_system_variables')
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
    <th nowrap="nowrap"><?php echo htmlspecialchars(str_replace('_', ' ', $name)); ?></th>
    <td class="value"><?php echo formatVariable($name, $value); ?></td>
    <td class="value"><?php
    // To display variable documentation link
    if (isset($VARIABLE_DOC_LINKS[$name]))
        echo PMA_showMySQLDocu($VARIABLE_DOC_LINKS[$name][1], $VARIABLE_DOC_LINKS[$name][1], false, $VARIABLE_DOC_LINKS[$name][2] . '_' . $VARIABLE_DOC_LINKS[$name][0]);
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

    if (is_numeric($value)) {
        if (isset($VARIABLE_DOC_LINKS[$name][3]) && $VARIABLE_DOC_LINKS[$name][3]=='byte')
            return '<abbr title="'.PMA_formatNumber($value, 0).'">'.implode(' ', PMA_formatByteDown($value, 3, 3)).'</abbr>';
        else return PMA_formatNumber($value, 0);
    }
    return htmlspecialchars($value);
}

/**
 * Sends the footer
 */
require './libraries/footer.inc.php';

?>
