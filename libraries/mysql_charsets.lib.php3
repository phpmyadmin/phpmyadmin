<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!defined('PMA_MYSQL_CHARSETS_LIB_INCLUDED')){
    define('PMA_MYSQL_CHARSETS_LIB_INCLUDED', 1);

    $res = PMA_mysql_query('SHOW VARIABLES LIKE "character_sets";', $userlink)
        or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW VARIABLES LIKE "character sets";');
    $row = PMA_mysql_fetch_row($res);
    @mysql_free_result($res);
    unset($res);

    $charsets_tmp = explode(' ', $row[1]);
    unset($row);

    $mysql_charsets = array();

    for ($i = 0; isset($charsets_tmp[$i]); $i++) {
        if (strpos(' ' . $charsets_tmp[$i], '_')) {
            $current = substr($charsets_tmp[$i], 0, strpos($charsets_tmp[$i], '_'));
        } else {
            $current = $charsets_tmp[$i];
        }
        if (!in_array($current, $mysql_charsets)) {
            $mysql_charsets[] = $current;
        }
    }

    unset($charsets_tmp);
    unset($i);
    unset($current);
    
    if (PMA_PHP_INT_VERSION >= 40000) {
        sort($mysql_charsets, SORT_STRING);
    } else {
        sort($mysql_charsets);
    }

} // $__PMA_MYSQL_CHARSETS_LIB__

?>
