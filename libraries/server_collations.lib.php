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
 * @param Array $mysqlCharsets      Mysql Charsets list
 * @param Array $mysqlCollations    Mysql Collations list
 * @param Array $mysqlCharsetsDesc  Charsets descriptions
 * @param Array $mysqlDftCollations Default Collations list
 * @param Array $mysqlCollAvailable Available Collations list
 *
 * @return string
 */
function PMA_getHtmlForCharsets($mysqlCharsets, $mysqlCollations,
    $mysqlCharsetsDesc, $mysqlDftCollations,
    $mysqlCollAvailable
) {
    /**
     * Outputs the result
     */
    $html = '<div id="div_mysql_charset_collations">' . "\n"
        . '<table class="data noclick">' . "\n"
        . '<tr><th id="collationHeader">' . __('Collation') . '</th>' . "\n"
        . '    <th>' . __('Description') . '</th>' . "\n"
        . '</tr>' . "\n";

    $table_row_count = count($mysqlCharsets) + count($mysqlCollations);

    foreach ($mysqlCharsets as $current_charset) {

        $html .= '<tr><th colspan="2" class="right">' . "\n"
            . '        ' . htmlspecialchars($current_charset) . "\n"
            . (empty($mysqlCharsetsDesc[$current_charset])
                ? ''
                : '        (<i>' . htmlspecialchars(
                    $mysqlCharsetsDesc[$current_charset]
                ) . '</i>)' . "\n")
            . '    </th>' . "\n"
            . '</tr>' . "\n";

        $html .= PMA_getHtmlForCollationCurrentCharset(
            $current_charset,
            $mysqlCollations,
            $mysqlDftCollations,
            $mysqlCollAvailable
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
 * @param String $currCharset        Current Charset
 * @param Array  $mysqlColl          Collations list
 * @param Array  $mysqlDefaultColl   Default Collations list
 * @param Array  $mysqlCollAvailable Available Collations list
 *
 * @return string
 */
function PMA_getHtmlForCollationCurrentCharset(
    $currCharset, $mysqlColl,
    $mysqlDefaultColl, $mysqlCollAvailable
) {
    $odd_row = true;
    $html = '';
    foreach ($mysqlColl[$currCharset] as $current_collation) {

        $html .= '<tr class="'
            . ($odd_row ? 'odd' : 'even')
            . ($mysqlDefaultColl[$currCharset] == $current_collation
                ? ' marked'
                : '')
            . ($mysqlCollAvailable[$current_collation] ? '' : ' disabled')
            . '">' . "\n"
            . '    <td>' . htmlspecialchars($current_collation) . '</td>' . "\n"
            . '    <td>' . PMA_getCollationDescr($current_collation) . '</td>' . "\n"
            . '</tr>' . "\n";
        $odd_row = !$odd_row;
    }
    return $html;
}
