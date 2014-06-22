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

if (! PMA_Util::cacheExists('mysql_charsets', null)) {
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
        if (! is_array($mysql_collations[$row['CHARACTER_SET_NAME']])) {
            $mysql_collations[$row['CHARACTER_SET_NAME']]
                = array($row['COLLATION_NAME']);
        } else {
            $mysql_collations[$row['CHARACTER_SET_NAME']][] = $row['COLLATION_NAME'];
        }
        $mysql_collations_flat[] = $row['COLLATION_NAME'];
        if ($row['IS_DEFAULT'] == 'Yes' || $row['IS_DEFAULT'] == '1') {
            $mysql_default_collations[$row['CHARACTER_SET_NAME']]
                = $row['COLLATION_NAME'];
        }
        //$mysql_collations_available[$row['Collation']]
        //    = ! isset($row['Compiled']) || $row['Compiled'] == 'Yes';
        $mysql_collations_available[$row['COLLATION_NAME']] = true;
        $mysql_charsets_available[$row['CHARACTER_SET_NAME']]
            = !empty($mysql_charsets_available[$row['CHARACTER_SET_NAME']])
            || !empty($mysql_collations_available[$row['COLLATION_NAME']]);
    }
    $GLOBALS['dbi']->freeResult($res);
    unset($res, $row);

    if (PMA_DRIZZLE
        && isset($mysql_collations['utf8_general_ci'])
        && isset($mysql_collations['utf8'])
    ) {
        $mysql_collations['utf8'] = $mysql_collations['utf8_general_ci'];
        $mysql_default_collations['utf8']
            = $mysql_default_collations['utf8_general_ci'];
        $mysql_charsets_available['utf8']
            = $mysql_charsets_available['utf8_general_ci'];
        unset(
            $mysql_collations['utf8_general_ci'],
            $mysql_default_collations['utf8_general_ci'],
            $mysql_charsets_available['utf8_general_ci']
        );
    }

    sort($mysql_collations_flat, SORT_STRING);
    foreach ($mysql_collations as $key => $value) {
        sort($mysql_collations[$key], SORT_STRING);
        reset($mysql_collations[$key]);
    }
    unset($key, $value);

    PMA_Util::cacheSet(
        'mysql_charsets', $GLOBALS['mysql_charsets'], null
    );
    PMA_Util::cacheSet(
        'mysql_charsets_descriptions', $GLOBALS['mysql_charsets_descriptions'], null
    );
    PMA_Util::cacheSet(
        'mysql_charsets_available', $GLOBALS['mysql_charsets_available'], null
    );
    PMA_Util::cacheSet(
        'mysql_collations', $GLOBALS['mysql_collations'], null
    );
    PMA_Util::cacheSet(
        'mysql_default_collations', $GLOBALS['mysql_default_collations'], null
    );
    PMA_Util::cacheSet(
        'mysql_collations_flat', $GLOBALS['mysql_collations_flat'], null
    );
    PMA_Util::cacheSet(
        'mysql_collations_available', $GLOBALS['mysql_collations_available'], null
    );
} else {
    $GLOBALS['mysql_charsets'] = PMA_Util::cacheGet(
        'mysql_charsets', null
    );
    $GLOBALS['mysql_charsets_descriptions'] = PMA_Util::cacheGet(
        'mysql_charsets_descriptions', null
    );
    $GLOBALS['mysql_charsets_available'] = PMA_Util::cacheGet(
        'mysql_charsets_available', null
    );
    $GLOBALS['mysql_collations'] = PMA_Util::cacheGet(
        'mysql_collations', null
    );
    $GLOBALS['mysql_default_collations'] = PMA_Util::cacheGet(
        'mysql_default_collations', null
    );
    $GLOBALS['mysql_collations_flat'] = PMA_Util::cacheGet(
        'mysql_collations_flat', null
    );
    $GLOBALS['mysql_collations_available'] = PMA_Util::cacheGet(
        'mysql_collations_available', null
    );
}

define('PMA_CSDROPDOWN_COLLATION', 0);
define('PMA_CSDROPDOWN_CHARSET',   1);

/**
 * shared functions for mysql charsets
 */
require_once './libraries/mysql_charsets.lib.php';

?>
