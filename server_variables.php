<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
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
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($cfg['MainPageIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 's_vars.png" width="16" height="16" alt="" />' : '')
   . '' . $strServerVars . "\n"
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
<tr><th><?php echo $strVar; ?></th>
    <th>
<?php
echo $strSessionValue . ' / ' . $strGlobalValue;
?>
    </th>
</tr>
</thead>
<tbody>
<?php
$odd_row = true;
foreach ($serverVars as $name => $value) {
    ?>
<tr class="<?php
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
    <?php
    if ($serverVarsGlobal[$name] !== $value) {
        ?>
</tr>
<tr class="<?php
    echo $odd_row ? 'odd' : 'even';
    ?> marked">
    <td>(<?php echo $strGlobalValue; ?>)</td>
    <td class="value"><?php
    if ($is_numeric) {
        echo PMA_formatNumber($serverVarsGlobal[$name], 0);
    } else {
        echo htmlspecialchars($serverVarsGlobal[$name]);
    }
    ?></td>
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
require_once './libraries/footer.inc.php';

?>
