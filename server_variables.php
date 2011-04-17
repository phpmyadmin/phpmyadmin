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

/**
 * Does the common work
 */
require './libraries/server_common.inc.php';


/**
 * Displays the links
 */
require './libraries/server_links.inc.php';


/**
 * Required to display documentation links
 */
require './libraries/server_variables_doc.php';

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
$serverVars = PMA_DBI_fetch_result('SHOW SESSION VARIABLES;', 0, 1);
$serverVarsGlobal = PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES;', 0, 1);


/**
 * Displays the page
 */
?>
<table class="data">
<thead>
<tr><th><?php echo __('Variable'); ?></th>
    <th>
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
<tr class="noclick <?php
    echo $odd_row ? 'odd' : 'even';
    if ($serverVarsGlobal[$name] !== $value) {
        echo ' marked';
    }
    ?>">
    <th nowrap="nowrap">
        <?php echo htmlspecialchars(str_replace('_', ' ', $name)); ?></th>
    <td class="value"><?php
    if (strlen($value) < 16 && is_numeric($value)) {
        echo PMA_formatNumber($value, 0);
        $is_numeric = true;
    } else {
        echo htmlspecialchars($value);
        $is_numeric = false;
    }
    ?></td>
    <td class="value"><?php
    if (isset($VARIABLE_DOC_LINKS[$name]))    // To display variable documentation link
        echo PMA_showMySQLDocu($VARIABLE_DOC_LINKS[$name][1], $VARIABLE_DOC_LINKS[$name][1], false, $VARIABLE_DOC_LINKS[$name][2] . '_' . $VARIABLE_DOC_LINKS[$name][0]);
    ?></td>
    <?php
    if ($serverVarsGlobal[$name] !== $value) {
        ?>
</tr>
<tr class="noclick <?php
    echo $odd_row ? 'odd' : 'even';
    ?> marked">
    <td>(<?php echo __('Global value'); ?>)</td>
    <td class="value"><?php
    if ($is_numeric) {
        echo PMA_formatNumber($serverVarsGlobal[$name], 0);
    } else {
        echo htmlspecialchars($serverVarsGlobal[$name]);
    }
    ?></td>
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

?>
