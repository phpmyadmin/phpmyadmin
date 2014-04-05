<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server Character Sets and Collations
 *
 * @usedby  server_collations.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Returns the html for server Character Sets and Collations.
 *
 * @param Array $mysql_charsets              Mysql Charsets list
 * @param Array $mysql_collations            Mysql Collations list
 * @param Array $mysql_charsets_descriptions Charsets descriptions
 * @param Array $mysql_default_collations    Default Collations list
 * @param Array $mysql_collations_available  Available Collations list
 *
 * @return string
 */
function PMA_getHtmlForCharsets($mysql_charsets, $mysql_collations,
    $mysql_charsets_descriptions, $mysql_default_collations,
    $mysql_collations_available
) {
    /**
     * Outputs the result
     */
    $html = '<div id="div_mysql_charset_collations">' . "\n"
        . '<table class="data noclick">' . "\n"
        . '<tr><th>' . __('Collation') . '</th>' . "\n"
        . '    <th>' . __('Description') . '</th>' . "\n"
        . '</tr>' . "\n";

    $i = 0;
    $table_row_count = count($mysql_charsets) + count($mysql_collations);

    foreach ($mysql_charsets as $current_charset) {
        if ($i >= $table_row_count / 2) {
            $i = 0;
            $html .= '</table>' . "\n"
                . '<table class="data noclick">' . "\n"
                . '<tr><th>' . __('Collation') . '</th>' . "\n"
                . '    <th>' . __('Description') . '</th>' . "\n"
                . '</tr>' . "\n";
        }
        $i++;
        $html .= '<tr><th colspan="2" class="right">' . "\n"
            . '        ' . htmlspecialchars($current_charset) . "\n"
            . (empty($mysql_charsets_descriptions[$current_charset])
                ? ''
                : '        (<i>' . htmlspecialchars(
                    $mysql_charsets_descriptions[$current_charset]
                ) . '</i>)' . "\n")
            . '    </th>' . "\n"
            . '</tr>' . "\n";

        $html .= PMA_getHtmlForCollationCurrentCharset(
            $current_charset,
            $mysql_collations,
            $i,
            $mysql_default_collations,
            $mysql_collations_available
        );
    }
    unset($table_row_count);
    $html .= '</table>' . "\n"
        . '</div>' . "\n";

    return $html;
}

/**
 * Returns the html for Collations of Current Charset.
 *
 * @param String $current_charset            Current Charset
 * @param Array  $mysql_collations           Collations list
 * @param int    &$i                         Display Index
 * @param Array  $mysql_default_collations   Default Collations list
 * @param Array  $mysql_collations_available Available Collations list
 *
 * @return string
 */
function PMA_getHtmlForCollationCurrentCharset(
    $current_charset, $mysql_collations, &$i,
    $mysql_default_collations, $mysql_collations_available
) {
    $odd_row = true;
    $html = '';
    foreach ($mysql_collations[$current_charset] as $current_collation) {
        $i++;
        $html .= '<tr class="'
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
    return $html;
}
?>
