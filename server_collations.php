<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . '    ' . PMA_Util::getImage('s_asci.png')
   . '' . __('Character Sets and Collations') . "\n"
   . '</h2>' . "\n";

/**
 * Includes the required charset library
 */
require_once 'libraries/mysql_charsets.lib.php';


/**
 * Outputs the result
 */
echo '<div id="div_mysql_charset_collations">' . "\n"
   . '<table class="data noclick">' . "\n"
   . '<tr><th>' . __('Collation') . '</th>' . "\n"
   . '    <th>' . __('Description') . '</th>' . "\n"
   . '</tr>' . "\n";

$i = 0;
$table_row_count = count($mysql_charsets) + count($mysql_collations);

foreach ($mysql_charsets as $current_charset) {
    if ($i >= $table_row_count / 2) {
        $i = 0;
        echo '</table>' . "\n"
           . '<table class="data noclick">' . "\n"
           . '<tr><th>' . __('Collation') . '</th>' . "\n"
           . '    <th>' . __('Description') . '</th>' . "\n"
           . '</tr>' . "\n";
    }
    $i++;
    echo '<tr><th colspan="2" class="right">' . "\n"
       . '        ' . htmlspecialchars($current_charset) . "\n"
       . (empty($mysql_charsets_descriptions[$current_charset])
            ? ''
            : '        (<i>' . htmlspecialchars(
                $mysql_charsets_descriptions[$current_charset]
            ) . '</i>)' . "\n")
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

?>
