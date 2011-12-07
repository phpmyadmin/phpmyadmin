<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (! PMA_cacheExists('mysql_charsets', true)) {
    $sql = PMA_DRIZZLE
        ? 'SELECT * FROM data_dictionary.CHARACTER_SETS'
        : 'SELECT * FROM information_schema.CHARACTER_SETS';
    $res = PMA_DBI_query($sql);

    $mysql_charsets = array();
    while ($row = PMA_DBI_fetch_assoc($res)) {
        $mysql_charsets[] = $row['CHARACTER_SET_NAME'];
        // never used
        //$mysql_charsets_maxlen[$row['Charset']] = $row['Maxlen'];
        $mysql_charsets_descriptions[$row['CHARACTER_SET_NAME']] = $row['DESCRIPTION'];
    }
    PMA_DBI_free_result($res);

    sort($mysql_charsets, SORT_STRING);

    $mysql_collations = array_flip($mysql_charsets);
    $mysql_default_collations = $mysql_collations_flat = $mysql_charsets_available = $mysql_collations_available = array();

    $sql = PMA_DRIZZLE
        ? 'SELECT * FROM data_dictionary.COLLATIONS'
        : 'SELECT * FROM information_schema.COLLATIONS';
    $res = PMA_DBI_query($sql);
    while ($row = PMA_DBI_fetch_assoc($res)) {
        if (! is_array($mysql_collations[$row['CHARACTER_SET_NAME']])) {
            $mysql_collations[$row['CHARACTER_SET_NAME']] = array($row['COLLATION_NAME']);
        } else {
            $mysql_collations[$row['CHARACTER_SET_NAME']][] = $row['COLLATION_NAME'];
        }
        $mysql_collations_flat[] = $row['COLLATION_NAME'];
        if ($row['IS_DEFAULT'] == 'Yes' || $row['IS_DEFAULT'] == '1') {
            $mysql_default_collations[$row['CHARACTER_SET_NAME']] = $row['COLLATION_NAME'];
        }
        //$mysql_collations_available[$row['Collation']] = ! isset($row['Compiled']) || $row['Compiled'] == 'Yes';
        $mysql_collations_available[$row['COLLATION_NAME']] = true;
        $mysql_charsets_available[$row['CHARACTER_SET_NAME']]
            = !empty($mysql_charsets_available[$row['CHARACTER_SET_NAME']])
            || !empty($mysql_collations_available[$row['COLLATION_NAME']]);
    }
    PMA_DBI_free_result($res);
    unset($res, $row);

    if (PMA_DRIZZLE && isset($mysql_collations['utf8_general_ci']) && isset($mysql_collations['utf8'])) {
        $mysql_collations['utf8'] = $mysql_collations['utf8_general_ci'];
        $mysql_default_collations['utf8'] = $mysql_default_collations['utf8_general_ci'];
        $mysql_charsets_available['utf8'] = $mysql_charsets_available['utf8_general_ci'];
        unset($mysql_collations['utf8_general_ci'], $mysql_default_collations['utf8_general_ci'],
            $mysql_charsets_available['utf8_general_ci']);
    }

    sort($mysql_collations_flat, SORT_STRING);
    foreach ($mysql_collations AS $key => $value) {
        sort($mysql_collations[$key], SORT_STRING);
        reset($mysql_collations[$key]);
    }
    unset($key, $value);

    PMA_cacheSet('mysql_charsets', $GLOBALS['mysql_charsets'], true);
    PMA_cacheSet('mysql_charsets_descriptions', $GLOBALS['mysql_charsets_descriptions'], true);
    PMA_cacheSet('mysql_charsets_available', $GLOBALS['mysql_charsets_available'], true);
    PMA_cacheSet('mysql_collations', $GLOBALS['mysql_collations'], true);
    PMA_cacheSet('mysql_default_collations', $GLOBALS['mysql_default_collations'], true);
    PMA_cacheSet('mysql_collations_flat', $GLOBALS['mysql_collations_flat'], true);
    PMA_cacheSet('mysql_collations_available', $GLOBALS['mysql_collations_available'], true);
} else {
    $GLOBALS['mysql_charsets']                  = PMA_cacheGet('mysql_charsets', true);
    $GLOBALS['mysql_charsets_descriptions']     = PMA_cacheGet('mysql_charsets_descriptions', true);
    $GLOBALS['mysql_charsets_available']        = PMA_cacheGet('mysql_charsets_available', true);
    $GLOBALS['mysql_collations']                = PMA_cacheGet('mysql_collations', true);
    $GLOBALS['mysql_default_collations']        = PMA_cacheGet('mysql_default_collations', true);
    $GLOBALS['mysql_collations_flat']           = PMA_cacheGet('mysql_collations_flat', true);
    $GLOBALS['mysql_collations_available']      = PMA_cacheGet('mysql_collations_available', true);
}

define('PMA_CSDROPDOWN_COLLATION', 0);
define('PMA_CSDROPDOWN_CHARSET',   1);

function PMA_generateCharsetDropdownBox($type = PMA_CSDROPDOWN_COLLATION,
    $name = null, $id = null, $default = null, $label = true, $indent = 0,
    $submitOnChange = false, $displayUnavailable = false)
{
    global $mysql_charsets, $mysql_charsets_descriptions,
        $mysql_charsets_available, $mysql_collations, $mysql_collations_available;

    if (empty($name)) {
        if ($type == PMA_CSDROPDOWN_COLLATION) {
            $name = 'collation';
        } else {
            $name = 'character_set';
        }
    }

    $return_str  = '<select xml:lang="en" dir="ltr" name="'
        . htmlspecialchars($name) . '"'
        . (empty($id) ? '' : ' id="' . htmlspecialchars($id) . '"')
        . ($submitOnChange ? ' class="autosubmit"' : '') . '>' . "\n";
    if ($label) {
        $return_str .= '<option value="">'
            . ($type == PMA_CSDROPDOWN_COLLATION ? __('Collation') : __('Charset'))
            . '</option>' . "\n";
    }
    $return_str .= '<option value=""></option>' . "\n";
    foreach ($mysql_charsets as $current_charset) {
        if (!$mysql_charsets_available[$current_charset]) {
            continue;
        }
        $current_cs_descr
            = empty($mysql_charsets_descriptions[$current_charset])
            ? $current_charset
            : $mysql_charsets_descriptions[$current_charset];

        if ($type == PMA_CSDROPDOWN_COLLATION) {
            $return_str .= '<optgroup label="' . $current_charset
                . '" title="' . $current_cs_descr . '">' . "\n";
            foreach ($mysql_collations[$current_charset] as $current_collation) {
                if (!$mysql_collations_available[$current_collation]) {
                    continue;
                }
                $return_str .= '<option value="' . $current_collation
                    . '" title="' . PMA_getCollationDescr($current_collation) . '"'
                    . ($default == $current_collation ? ' selected="selected"' : '') . '>'
                    . $current_collation . '</option>' . "\n";
            }
            $return_str .= '</optgroup>' . "\n";
        } else {
            $return_str .= '<option value="' . $current_charset
                . '" title="' . $current_cs_descr . '"'
                . ($default == $current_charset ? ' selected="selected"' : '') . '>'
                . $current_charset . '</option>' . "\n";
        }
    }
    $return_str .= '</select>' . "\n";

    return $return_str;
}

function PMA_generateCharsetQueryPart($collation)
{
    if (!PMA_DRIZZLE) {
        list($charset) = explode('_', $collation);
        return ' CHARACTER SET ' . $charset . ($charset == $collation ? '' : ' COLLATE ' . $collation);
    } else {
        return ' COLLATE ' . $collation;
    }
}

/**
 * returns collation of given db
 *
 * @param string  $db     name of db
 * @return  string  collation of $db
 */
function PMA_getDbCollation($db)
{
    if (PMA_is_system_schema($db)) {
        // We don't have to check the collation of the virtual
        // information_schema database: We know it!
        return 'utf8_general_ci';
    }

    if (! $GLOBALS['cfg']['Server']['DisableIS']) {
        // this is slow with thousands of databases
        $sql = PMA_DRIZZLE
            ? 'SELECT DEFAULT_COLLATION_NAME FROM data_dictionary.SCHEMAS WHERE SCHEMA_NAME = \'' . PMA_sqlAddSlashes($db) . '\' LIMIT 1'
            : 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = \'' . PMA_sqlAddSlashes($db) . '\' LIMIT 1';
        return PMA_DBI_fetch_value($sql);
    } else {
        PMA_DBI_select_db($db);
        $return = PMA_DBI_fetch_value('SHOW VARIABLES LIKE \'collation_database\'', 0, 1);
        if ($db !== $GLOBALS['db']) {
            PMA_DBI_select_db($GLOBALS['db']);
        }
        return $return;
    }
}

/**
 * returns default server collation from show variables
 *
 * @return  string  $server_collation
 */
function PMA_getServerCollation()
{
    return PMA_DBI_fetch_value(
        'SHOW VARIABLES LIKE \'collation_server\'', 0, 1);
}

/**
 * returns description for given collation
 *
 *
 * @param string  $collation  MySQL collation string
 * @return  string  collation description
 */
function PMA_getCollationDescr($collation)
{
    if ($collation == 'binary') {
        return __('Binary');
    }
    $parts = explode('_', $collation);
    if (count($parts) == 1) {
        $parts[1] = 'general';
    } elseif ($parts[1] == 'ci' || $parts[1] == 'cs') {
        $parts[2] = $parts[1];
        $parts[1] = 'general';
    }
    $descr = '';
    switch ($parts[1]) {
    case 'bulgarian':
        $descr = __('Bulgarian');
        break;
    case 'chinese':
        if ($parts[0] == 'gb2312' || $parts[0] == 'gbk') {
            $descr = __('Simplified Chinese');
        } elseif ($parts[0] == 'big5') {
            $descr = __('Traditional Chinese');
        }
        break;
    case 'ci':
        $descr = __('case-insensitive');
        break;
    case 'cs':
        $descr = __('case-sensitive');
        break;
    case 'croatian':
        $descr = __('Croatian');
        break;
    case 'czech':
        $descr = __('Czech');
        break;
    case 'danish':
        $descr = __('Danish');
        break;
    case 'english':
        $descr = __('English');
        break;
    case 'esperanto':
        $descr = __('Esperanto');
        break;
    case 'estonian':
        $descr = __('Estonian');
        break;
    case 'german1':
        $descr = __('German') . ' (' . __('dictionary') . ')';
        break;
    case 'german2':
        $descr = __('German') . ' (' . __('phone book') . ')';
        break;
    case 'hungarian':
        $descr = __('Hungarian');
        break;
    case 'icelandic':
        $descr = __('Icelandic');
        break;
    case 'japanese':
        $descr = __('Japanese');
        break;
    case 'latvian':
        $descr = __('Latvian');
        break;
    case 'lithuanian':
        $descr = __('Lithuanian');
        break;
    case 'korean':
        $descr = __('Korean');
        break;
    case 'persian':
        $descr = __('Persian');
        break;
    case 'polish':
        $descr = __('Polish');
        break;
    case 'roman':
        $descr = __('West European');
        break;
    case 'romanian':
        $descr = __('Romanian');
        break;
    case 'slovak':
        $descr = __('Slovak');
        break;
    case 'slovenian':
        $descr = __('Slovenian');
        break;
    case 'spanish':
        $descr = __('Spanish');
        break;
    case 'spanish2':
        $descr = __('Traditional Spanish');
        break;
    case 'swedish':
        $descr = __('Swedish');
        break;
    case 'thai':
        $descr = __('Thai');
        break;
    case 'turkish':
        $descr = __('Turkish');
        break;
    case 'ukrainian':
        $descr = __('Ukrainian');
        break;
    case 'unicode':
        $descr = __('Unicode') . ' (' . __('multilingual') . ')';
        break;
    case 'bin':
        $is_bin = true;
    case 'general':
        switch ($parts[0]) {
        // Unicode charsets
        case 'ucs2':
        case 'utf8':
            $descr = __('Unicode') . ' (' . __('multilingual') . ')';
            break;
        // West European charsets
        case 'ascii':
        case 'cp850':
        case 'dec8':
        case 'hp8':
        case 'latin1':
        case 'macroman':
            $descr = __('West European') . ' (' . __('multilingual') . ')';
            break;
        // Central European charsets
        case 'cp1250':
        case 'cp852':
        case 'latin2':
        case 'macce':
            $descr = __('Central European') . ' (' . __('multilingual') . ')';
            break;
        // Russian charsets
        case 'cp866':
        case 'koi8r':
            $descr = __('Russian');
            break;
        // Simplified Chinese charsets
        case 'gb2312':
        case 'gbk':
            $descr = __('Simplified Chinese');
            break;
        // Japanese charsets
        case 'sjis':
        case 'ujis':
        case 'cp932':
        case 'eucjpms':
            $descr = __('Japanese');
            break;
        // Baltic charsets
        case 'cp1257':
        case 'latin7':
            $descr = __('Baltic') . ' (' . __('multilingual') . ')';
            break;
        // Other
        case 'armscii8':
        case 'armscii':
            $descr = __('Armenian');
            break;
        case 'big5':
            $descr = __('Traditional Chinese');
            break;
        case 'cp1251':
            $descr = __('Cyrillic') . ' (' . __('multilingual') . ')';
            break;
        case 'cp1256':
            $descr = __('Arabic');
            break;
        case 'euckr':
            $descr = __('Korean');
            break;
        case 'hebrew':
            $descr = __('Hebrew');
            break;
        case 'geostd8':
            $descr = __('Georgian');
            break;
        case 'greek':
            $descr = __('Greek');
            break;
        case 'keybcs2':
            $descr = __('Czech-Slovak');
            break;
        case 'koi8u':
            $descr = __('Ukrainian');
            break;
        case 'latin5':
            $descr = __('Turkish');
            break;
        case 'swe7':
            $descr = __('Swedish');
            break;
        case 'tis620':
            $descr = __('Thai');
            break;
        default:
            $descr = __('unknown');
            break;
        }
        if (!empty($is_bin)) {
            $descr .= ', ' . __('Binary');
        }
        break;
    default: $descr = __('unknown');
    }
    if (!empty($parts[2])) {
        if ($parts[2] == 'ci') {
            $descr .= ', ' . __('case-insensitive');
        } elseif ($parts[2] == 'cs') {
            $descr .= ', ' . __('case-sensitive');
        }
    }

    return $descr;
}
?>
