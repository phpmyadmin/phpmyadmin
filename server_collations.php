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
/**
 * requirements
 */
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
   . '    ' . ($GLOBALS['cfg']['MainPageIconic']
    ? '<img class="icon" src="'. $GLOBALS['pmaThemeImage'] . 's_asci.png" alt="" />'
    : '')
   . '' . $strCharsetsAndCollations . "\n"
   . '</h2>' . "\n";

/**
 * Includes the required charset library
 */
require_once './libraries/mysql_charsets.lib.php';


/**
 * Outputs the result
 */
echo '<div id="div_mysql_charset_collations">' . "\n"
   . '<table class="data">' . "\n"
   . '<tr><th>' . $strCollation . '</th>' . "\n"
   . '    <th>' . $strDescription . '</th>' . "\n"
   . '</tr>' . "\n";

$i = 0;
$table_row_count = count($mysql_charsets) + $mysql_collations_count;

foreach ($mysql_charsets as $current_charset) {
    if ($i >= $table_row_count / 2) {
        $i = 0;
        echo '</table>' . "\n"
           . '<table class="data">' . "\n"
           . '<tr><th>' . $strCollation . '</th>' . "\n"
           . '    <th>' . $strDescription . '</th>' . "\n"
           . '</tr>' . "\n";
    }
    $i++;
    echo '<tr><th colspan="2" align="right">' . "\n"
       . '        ' . htmlspecialchars($current_charset) . "\n"
       . (empty($mysql_charsets_descriptions[$current_charset])
            ? ''
            : '        (<i>' . htmlspecialchars(
                $mysql_charsets_descriptions[$current_charset]) . '</i>)' . "\n")
       . '    </th>' . "\n"
       . '</tr>' . "\n";
    $odd_row = true;
    foreach ($mysql_collations[$current_charset] as $current_collation) {
        $i++;
        echo '<tr class="'
           . ($odd_row ? 'odd' : 'even')
           . ($mysql_default_collations[$current_charset] == $current_collation
                ? ' marked'
                : '')
           . ($mysql_collations_available[$current_collation] ? '' : ' disabled')
           . '">' . "\n"
           . '    <td>' . htmlspecialchars($current_collation) . '</td>' . "\n"
           . '    <td>' . PMA_getCollationDescr($current_collation) . '</td>' . "\n"
           . '</tr>' . "\n";
        $odd_row = !$odd_row;
    }
}
unset($table_row_count);
echo '</table>' . "\n"
   . '</div>' . "\n";

require_once './libraries/footer.inc.php';

?>
