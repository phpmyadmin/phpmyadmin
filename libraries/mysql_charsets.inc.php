<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MySQL charsets listings
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */

if (! PMA_Util::cacheExists('mysql_charsets')) {
    global $mysql_charsets, $mysql_charsets_descriptions,
        $mysql_charsets_available, $mysql_collations, $mysql_collations_available,
        $mysql_default_collations, $mysql_collations_flat;
    $sql = PMA_DRIZZLE
        ? 'SELECT * FROM data_dictionary.CHARACTER_SETS'
        : 'SELECT * FROM information_schema.CHARACTER_SETS';
    $res = $GLOBALS['dbi']->query($sql);

    $mysql_charsets = array();
    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $mysql_charsets[] = $row['CHARACTER_SET_NAME'];
        // never used
        //$mysql_charsets_maxlen[$row['Charset']] = $row['Maxlen'];
        $mysql_charsets_descriptions[$row['CHARACTER_SET_NAME']]
            = $row['DESCRIPTION'];
    }
    $GLOBALS['dbi']->freeResult($res);

    sort($mysql_charsets, SORT_STRING);

    $mysql_collations = array_flip($mysql_charsets);
    $mysql_default_collations = $mysql_collations_flat
        = $mysql_charsets_available = $mysql_collations_available = array();

    $sql = PMA_DRIZZLE
        ? 'SELECT * FROM data_dictionary.COLLATIONS'
        : 'SELECT * FROM information_schema.COLLATIONS';
    $res = $GLOBALS['dbi']->query($sql);
    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $char_set_name = PMA_DRIZZLE
            ? $row['DESCRIPTION']
            : $row['CHARACTER_SET_NAME'];
        if (! is_array($mysql_collations[$char_set_name])) {
            $mysql_collations[$char_set_name] = array($row['COLLATION_NAME']);
        } else {
            $mysql_collations[$char_set_name][] = $row['COLLATION_NAME'];
        }
        $mysql_collations_flat[] = $row['COLLATION_NAME'];
        if ($row['IS_DEFAULT'] == 'Yes' || $row['IS_DEFAULT'] == '1') {
            $mysql_default_collations[$char_set_name]
                = $row['COLLATION_NAME'];
        }
        //$mysql_collations_available[$row['Collation']]
        //    = ! isset($row['Compiled']) || $row['Compiled'] == 'Yes';
        $mysql_collations_available[$row['COLLATION_NAME']] = true;
        $mysql_charsets_available[$char_set_name]
            = !empty($mysql_charsets_available[$char_set_name])
            || !empty($mysql_collations_available[$row['COLLATION_NAME']]);
    }
    $GLOBALS['dbi']->freeResult($res);
    unset($res, $row);

    sort($mysql_collations_flat, SORT_STRING);
    foreach ($mysql_collations as $key => $value) {
        sort($mysql_collations[$key], SORT_STRING);
        reset($mysql_collations[$key]);
    }
    unset($key, $value);

    PMA_Util::cacheSet(
        'mysql_charsets', $GLOBALS['mysql_charsets']
    );
    PMA_Util::cacheSet(
        'mysql_charsets_descriptions', $GLOBALS['mysql_charsets_descriptions']
    );
    PMA_Util::cacheSet(
        'mysql_charsets_available', $GLOBALS['mysql_charsets_available']
    );
    PMA_Util::cacheSet(
        'mysql_collations', $GLOBALS['mysql_collations']
    );
    PMA_Util::cacheSet(
        'mysql_default_collations', $GLOBALS['mysql_default_collations']
    );
    PMA_Util::cacheSet(
        'mysql_collations_flat', $GLOBALS['mysql_collations_flat']
    );
    PMA_Util::cacheSet(
        'mysql_collations_available', $GLOBALS['mysql_collations_available']
    );
} else {
    $GLOBALS['mysql_charsets'] = PMA_Util::cacheGet(
        'mysql_charsets'
    );
    $GLOBALS['mysql_charsets_descriptions'] = PMA_Util::cacheGet(
        'mysql_charsets_descriptions'
    );
    $GLOBALS['mysql_charsets_available'] = PMA_Util::cacheGet(
        'mysql_charsets_available'
    );
    $GLOBALS['mysql_collations'] = PMA_Util::cacheGet(
        'mysql_collations'
    );
    $GLOBALS['mysql_default_collations'] = PMA_Util::cacheGet(
        'mysql_default_collations'
    );
    $GLOBALS['mysql_collations_flat'] = PMA_Util::cacheGet(
        'mysql_collations_flat'
    );
    $GLOBALS['mysql_collations_available'] = PMA_Util::cacheGet(
        'mysql_collations_available'
    );
}

define('PMA_CSDROPDOWN_COLLATION', 0);
define('PMA_CSDROPDOWN_CHARSET',   1);

/**
 * shared functions for mysql charsets
 */
require_once './libraries/mysql_charsets.lib.php';

?>
