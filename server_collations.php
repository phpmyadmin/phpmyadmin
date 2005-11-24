<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * Does the common work
 */
require('./libraries/server_common.inc.php');


/**
 * Displays the links
 */
require('./libraries/server_links.inc.php');


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . '    ' . ($GLOBALS['cfg']['MainPageIconic'] ? '<img class="icon" src="'. $GLOBALS['pmaThemeImage'] . 's_asci.png" alt="" />' : '')
   . '' . $strCharsetsAndCollations . "\n"
   . '</h2>' . "\n";


/**
 * Checks the MySQL version
 */
if (PMA_MYSQL_INT_VERSION < 40100) {
    // TODO: Some nice Message :-)
    require_once('./libraries/footer.inc.php');
}


/**
 * Includes the required charset library
 */
require_once('./libraries/mysql_charsets.lib.php');


/**
 * Outputs the result
 */
echo '<table border="0">' . "\n"
   . '    <tr>' . "\n"
   . '        <td valign="top">' . "\n"
   . '            <table border="0" cellpadding="2" cellspacing="1">' . "\n"
   . '                <tr>' . "\n"
   . '                <th>' . "\n"
   . '                    ' . $strCollation . "\n"
   . '                </th>' . "\n"
   . '                <th>' . "\n"
   . '                    ' . $strDescription . "\n"
   . '                </th>' . "\n"
   . '            </tr>' . "\n";

$i = 0;
$table_row_count = count($mysql_charsets) + $mysql_collations_count;

foreach ($mysql_charsets as $current_charset) {
    if ($i >= $table_row_count / 2) {
        $i = 0;
        echo '            </table>' . "\n"
           . '        </td>' . "\n"
           . '        <td valign="top">' . "\n"
           . '            <table border="0" cellpadding="2" cellspacing="1">' . "\n"
           . '                <tr>' . "\n"
           . '                <th>' . "\n"
           . '                    ' . $strCollation . "\n"
           . '                </th>' . "\n"
           . '                <th>' . "\n"
           . '                    ' . $strDescription . "\n"
           . '                </th>' . "\n"
           . '            </tr>' . "\n";
    }
    $i++;
    echo '            <tr>' . "\n"
       . '                <th colspan="2" align="right">' . "\n"
       . '                    &nbsp;<b>' . htmlspecialchars($current_charset) . '</b>' . "\n"
       . (empty($mysql_charsets_descriptions[$current_charset]) ? '' : '                    (<i>' . htmlspecialchars($mysql_charsets_descriptions[$current_charset]) . '</i>)&nbsp;' . "\n")
       . '                </th>' . "\n"
       . '            </tr>' . "\n";
    $useBgcolorOne = TRUE;
    foreach ($mysql_collations[$current_charset] as $current_collation) {
        $i++;
        echo '            <tr' . ($mysql_collations_available[$current_collation] ? '' : ' class="disabled"') . '>' . "\n"
           . '                <td bgcolor="' . ($mysql_default_collations[$current_charset] == $current_collation ? $cfg['BrowseMarkerColor'] : ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'])) . '">' . "\n"
           . '                    &nbsp;' . htmlspecialchars($current_collation) . '&nbsp;' . "\n"
           . '                </td>' . "\n"
           . '                <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
           . '                    &nbsp;' . PMA_getCollationDescr($current_collation) . '&nbsp;' . "\n"
           . '                </td>' . "\n"
           . '            </tr>' . "\n";
        $useBgcolorOne = !$useBgcolorOne;
    }
}
unset($table_row_count);
echo '            </table>' . "\n"
   . '        </td>' . "\n"
   . '    </tr>' . "\n"
   . '</table>' . "\n";

require_once('./libraries/footer.inc.php');

?>
