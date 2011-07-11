<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
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
    
    if(isset($_REQUEST['type'])) {
        switch($_REQUEST['type']) {
            case 'getval':
                $varValue = PMA_DBI_fetch_single_row('SHOW GLOBAL VARIABLES WHERE Variable_name="'.PMA_sqlAddslashes($_REQUEST['varName']).'";','NUM');
                exit($varValue[1]);
                break;
            case 'setval':
                $value = PMA_sqlAddslashes($_REQUEST['varValue']);
                if(!is_numeric($value)) $value="'".$value."'";
                
                if(! preg_match("/[^a-zA-Z0-9_]+/",$_REQUEST['varName']) && PMA_DBI_query('SET GLOBAL '.$_REQUEST['varName'].' = '.$value))
                    // Some values are rounded down etc.
                    $varValue = PMA_DBI_fetch_single_row('SHOW GLOBAL VARIABLES WHERE Variable_name="'.PMA_sqlAddslashes($_REQUEST['varName']).'";','NUM');
                    
                    exit(json_encode(array( 
                        'success' => true,
                        'variable' => formatVariable($_REQUEST['varName'],$varValue[1])
                        ))
                    );
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
   . ($cfg['MainPageIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 's_vars.png" width="16" height="16" alt="" />' : '')
   . '' . __('Server variables and settings') . "\n"
   . PMA_showMySQLDocu('server_system_variables','server_system_variables')
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
<script type="text/javascript">
pma_token = '<?php echo $_SESSION[' PMA_token ']; ?>';
url_query = '<?php echo str_replace('&amp;','&',$url_query);?>';
isSuperuser = <?php echo PMA_isSuperuser()?'true':'false'; ?>;
</script>

<fieldset id="tableFilter" style="display:none;">
<legend>Filters</legend>
<div class="formelement">
    <label for="filterText">Containing the word:</label>
    <input name="filterText" type="text" id="filterText" style="vertical-align: baseline;" />
</div>
</fieldset>
<table id="serverVariables" class="data filteredData">
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
    ?>
<tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?>">
    <th nowrap="nowrap"><?php echo htmlspecialchars(str_replace('_', ' ', $name)); ?></th>
    <td class="value"><?php echo formatVariable($name,$value); ?></td>
    <td class="value"><?php
    if (isset($VARIABLE_DOC_LINKS[$name]))    // To display variable documentation link
        echo PMA_showMySQLDocu($VARIABLE_DOC_LINKS[$name][1], $VARIABLE_DOC_LINKS[$name][1], false, $VARIABLE_DOC_LINKS[$name][2] . '_' . $VARIABLE_DOC_LINKS[$name][0]);
    ?></td>
    <?php
    if (isset($serverVarsSession[$name]) && $serverVarsSession[$name] != $value) {
        ?>
</tr>
<tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?> ">
    <td>(<?php echo __('Session value'); ?>)</td>
    <td class="value"><?php echo formatVariable($name,$serverVarsSession[$name]); ?></td>
    <td class="value"></td>
    <?php } ?>
</tr>
    <?php
    $odd_row = !$odd_row;
}
?>
</tbody>
</table>
<?php


/**
 * Sends the footer
 */
require './libraries/footer.inc.php';

function formatVariable($name,$value) {
    global $VARIABLE_DOC_LINKS;
    
    if (is_numeric($value)) {
        if(isset($VARIABLE_DOC_LINKS[$name][3]) && $VARIABLE_DOC_LINKS[$name][3]=='byte')
            return '<abbr title="'.PMA_formatNumber($value, 0).'">'.implode(' ',PMA_formatByteDown($value,3,3)).'</abbr>';
        else return PMA_formatNumber($value, 0);
    }
    return htmlspecialchars($value);
}

?>
