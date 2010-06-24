<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */

if (! PMA_cacheExists('mysql_charsets_count', true)) {
    $res = PMA_DBI_query('SHOW CHARACTER SET;');

    $mysql_charsets = array();
    while ($row = PMA_DBI_fetch_assoc($res)) {
        $mysql_charsets[] = $row['Charset'];
        // never used
        //$mysql_charsets_maxlen[$row['Charset']] = $row['Maxlen'];
        $mysql_charsets_descriptions[$row['Charset']] = $row['Description'];
    }
    PMA_DBI_free_result($res);

    $mysql_charsets_count = count($mysql_charsets);
    sort($mysql_charsets, SORT_STRING);

    $mysql_collations = array_flip($mysql_charsets);
    $mysql_default_collations = $mysql_collations_flat = $mysql_charsets_available = $mysql_collations_available = array();

    $res = PMA_DBI_query('SHOW COLLATION;');
    while ($row = PMA_DBI_fetch_assoc($res)) {
        if (!is_array($mysql_collations[$row['Charset']])) {
            $mysql_collations[$row['Charset']] = array($row['Collation']);
        } else {
            $mysql_collations[$row['Charset']][] = $row['Collation'];
        }
        $mysql_collations_flat[] = $row['Collation'];
        if ((isset($row['D']) && $row['D'] == 'Y') || (isset($row['Default']) && $row['Default'] == 'Yes')) {
            $mysql_default_collations[$row['Charset']] = $row['Collation'];
        }
        //$mysql_collations_available[$row['Collation']] = !isset($row['Compiled']) || $row['Compiled'] == 'Yes';
        $mysql_collations_available[$row['Collation']] = TRUE;
        $mysql_charsets_available[$row['Charset']] =
            !empty($mysql_charsets_available[$row['Charset']])
         || !empty($mysql_collations_available[$row['Collation']]);
    }
    PMA_DBI_free_result($res);
    unset($res, $row);

    $mysql_collations_count = count($mysql_collations_flat);
    sort($mysql_collations_flat, SORT_STRING);
    foreach ($mysql_collations AS $key => $value) {
        sort($mysql_collations[$key], SORT_STRING);
        reset($mysql_collations[$key]);
    }
    unset($key, $value);

    PMA_cacheSet('mysql_charsets', $GLOBALS['mysql_charsets'], true);
    PMA_cacheSet('mysql_charsets_descriptions', $GLOBALS['mysql_charsets_descriptions'], true);
    PMA_cacheSet('mysql_charsets_count', $GLOBALS['mysql_charsets_count'], true);
    PMA_cacheSet('mysql_charsets_available', $GLOBALS['mysql_charsets_available'], true);
    PMA_cacheSet('mysql_collations', $GLOBALS['mysql_collations'], true);
    PMA_cacheSet('mysql_default_collations', $GLOBALS['mysql_default_collations'], true);
    PMA_cacheSet('mysql_collations_flat', $GLOBALS['mysql_collations_flat'], true);
    PMA_cacheSet('mysql_collations_count', $GLOBALS['mysql_collations_count'], true);
    PMA_cacheSet('mysql_collations_available', $GLOBALS['mysql_collations_available'], true);
} else {
    $GLOBALS['mysql_charsets']                  = PMA_cacheGet('mysql_charsets', true);
    $GLOBALS['mysql_charsets_descriptions']     = PMA_cacheGet('mysql_charsets_descriptions', true);
    $GLOBALS['mysql_charsets_count']            = PMA_cacheGet('mysql_charsets_count', true);
    $GLOBALS['mysql_charsets_available']        = PMA_cacheGet('mysql_charsets_available', true);
    $GLOBALS['mysql_collations']                = PMA_cacheGet('mysql_collations', true);
    $GLOBALS['mysql_default_collations']        = PMA_cacheGet('mysql_default_collations', true);
    $GLOBALS['mysql_collations_flat']           = PMA_cacheGet('mysql_collations_flat', true);
    $GLOBALS['mysql_collations_count']          = PMA_cacheGet('mysql_collations_count', true);
    $GLOBALS['mysql_collations_available']      = PMA_cacheGet('mysql_collations_available', true);
}

define('PMA_CSDROPDOWN_COLLATION', 0);
define('PMA_CSDROPDOWN_CHARSET',   1);

function PMA_generateCharsetDropdownBox($type = PMA_CSDROPDOWN_COLLATION,
    $name = null, $id = null, $default = null, $label = TRUE, $indent = 0,
    $submitOnChange = FALSE, $displayUnavailable = FALSE)
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
        . ($submitOnChange ? ' onchange="this.form.submit();"' : '') . '>' . "\n";
    if ($label) {
        $return_str .= '<option value="">'
            . ($type == PMA_CSDROPDOWN_COLLATION ? $GLOBALS['strCollation'] : $GLOBALS['strCharset'])
            . '</option>' . "\n";
    }
    $return_str .= '<option value=""></option>' . "\n";
    foreach ($mysql_charsets as $current_charset) {
        if (!$mysql_charsets_available[$current_charset]) {
            continue;
        }
        $current_cs_descr =
            empty($mysql_charsets_descriptions[$current_charset])
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

function PMA_generateCharsetQueryPart($collation) {
    list($charset) = explode('_', $collation);
    return ' CHARACTER SET ' . $charset . ($charset == $collation ? '' : ' COLLATE ' . $collation);
}

/**
 * returns collation of given db
 *
 * @uses    PMA_DBI_fetch_value()
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_sqlAddSlashes()
 * @uses    $GLOBALS['db']
 * @param   string  $db     name of db
 * @return  string  collation of $db
 */
function PMA_getDbCollation($db) {
    if ($db == 'information_schema') {
        // We don't have to check the collation of the virtual
        // information_schema database: We know it!
        return 'utf8_general_ci';
    }

    if (! $GLOBALS['cfg']['Server']['DisableIS']) {
        // this is slow with thousands of databases
        return PMA_DBI_fetch_value('SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = \'' . PMA_sqlAddSlashes($db) . '\' LIMIT 1;');
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
 * @uses    PMA_DBI_fetch_value()
 * @return  string  $server_collation
 */
function PMA_getServerCollation() {
    return PMA_DBI_fetch_value(
        'SHOW VARIABLES LIKE \'collation_server\'', 0, 1);
}

/**
 * returns description for given collation
 *
 * @uses    is_array()
 * @uses    explode()
 * @uses    count()
 * @uses    $GLOBALS['str[Languages|Sorting]']
 *
 * @param   string  $collation  MySQL collation string
 * @return  string  collation description
 */
function PMA_getCollationDescr($collation) {
    static $collation_cache;

    if (!is_array($collation_cache)) {
        $collation_cache = array();
    } elseif (isset($collation_cache[$collation])) {
        return $collation_cache[$collation];
    }

    if ($collation == 'binary') {
        return $GLOBALS['strBinary'];
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
            $descr = $GLOBALS['strBulgarian'];
            break;
        case 'chinese':
            if ($parts[0] == 'gb2312' || $parts[0] == 'gbk') {
                $descr = $GLOBALS['strSimplifiedChinese'];
            } elseif ($parts[0] == 'big5') {
                $descr = $GLOBALS['strTraditionalChinese'];
            }
            break;
        case 'ci':
            $descr = $GLOBALS['strCaseInsensitive'];
            break;
        case 'cs':
            $descr = $GLOBALS['strCaseSensitive'];
            break;
        case 'croatian':
            $descr = $GLOBALS['strCroatian'];
            break;
        case 'czech':
            $descr = $GLOBALS['strCzech'];
            break;
        case 'danish':
            $descr = $GLOBALS['strDanish'];
            break;
        case 'english':
            $descr = $GLOBALS['strEnglish'];
            break;
        case 'esperanto':
            $descr = $GLOBALS['strEsperanto'];
            break;
        case 'estonian':
            $descr = $GLOBALS['strEstonian'];
            break;
        case 'german1':
            $descr = $GLOBALS['strGerman'] . ' (' . $GLOBALS['strDictionary'] . ')';
            break;
        case 'german2':
            $descr = $GLOBALS['strGerman'] . ' (' . $GLOBALS['strPhoneBook'] . ')';
            break;
        case 'hungarian':
            $descr = $GLOBALS['strHungarian'];
            break;
        case 'icelandic':
            $descr = $GLOBALS['strIcelandic'];
            break;
        case 'japanese':
            $descr = $GLOBALS['strJapanese'];
            break;
        case 'latvian':
            $descr = $GLOBALS['strLatvian'];
            break;
        case 'lithuanian':
            $descr = $GLOBALS['strLithuanian'];
            break;
        case 'korean':
            $descr = $GLOBALS['strKorean'];
            break;
        case 'persian':
            $descr = $GLOBALS['strPersian'];
            break;
        case 'polish':
            $descr = $GLOBALS['strPolish'];
            break;
        case 'roman':
            $descr = $GLOBALS['strWestEuropean'];
            break;
        case 'romanian':
            $descr = $GLOBALS['strRomanian'];
            break;
        case 'slovak':
            $descr = $GLOBALS['strSlovak'];
            break;
        case 'slovenian':
            $descr = $GLOBALS['strSlovenian'];
            break;
        case 'spanish':
            $descr = $GLOBALS['strSpanish'];
            break;
        case 'spanish2':
            $descr = $GLOBALS['strTraditionalSpanish'];
            break;
        case 'swedish':
            $descr = $GLOBALS['strSwedish'];
            break;
        case 'thai':
            $descr = $GLOBALS['strThai'];
            break;
        case 'turkish':
            $descr = $GLOBALS['strTurkish'];
            break;
        case 'ukrainian':
            $descr = $GLOBALS['strUkrainian'];
            break;
        case 'unicode':
            $descr = $GLOBALS['strUnicode'] . ' (' . $GLOBALS['strMultilingual'] . ')';
            break;
        case 'bin':
            $is_bin = TRUE;
        case 'general':
            switch ($parts[0]) {
                // Unicode charsets
                case 'ucs2':
                case 'utf8':
                    $descr = $GLOBALS['strUnicode'] . ' (' . $GLOBALS['strMultilingual'] . ')';
                    break;
                // West European charsets
                case 'ascii':
                case 'cp850':
                case 'dec8':
                case 'hp8':
                case 'latin1':
                case 'macroman':
                    $descr = $GLOBALS['strWestEuropean'] . ' (' . $GLOBALS['strMultilingual'] . ')';
                    break;
                // Central European charsets
                case 'cp1250':
                case 'cp852':
                case 'latin2':
                case 'macce':
                    $descr = $GLOBALS['strCentralEuropean'] . ' (' . $GLOBALS['strMultilingual'] . ')';
                    break;
                // Russian charsets
                case 'cp866':
                case 'koi8r':
                    $descr = $GLOBALS['strRussian'];
                    break;
                // Simplified Chinese charsets
                case 'gb2312':
                case 'gbk':
                    $descr = $GLOBALS['strSimplifiedChinese'];
                    break;
                // Japanese charsets
                case 'sjis':
                case 'ujis':
                case 'cp932':
                case 'eucjpms':
                    $descr = $GLOBALS['strJapanese'];
                    break;
                // Baltic charsets
                case 'cp1257':
                case 'latin7':
                    $descr = $GLOBALS['strBaltic'] . ' (' . $GLOBALS['strMultilingual'] . ')';
                    break;
                // Other
                case 'armscii8':
                case 'armscii':
                    $descr = $GLOBALS['strArmenian'];
                    break;
                case 'big5':
                    $descr = $GLOBALS['strTraditionalChinese'];
                    break;
                case 'cp1251':
                    $descr = $GLOBALS['strCyrillic'] . ' (' . $GLOBALS['strMultilingual'] . ')';
                    break;
                case 'cp1256':
                    $descr = $GLOBALS['strArabic'];
                    break;
                case 'euckr':
                    $descr = $GLOBALS['strKorean'];
                    break;
                case 'hebrew':
                    $descr = $GLOBALS['strHebrew'];
                    break;
                case 'geostd8':
                    $descr = $GLOBALS['strGeorgian'];
                    break;
                case 'greek':
                    $descr = $GLOBALS['strGreek'];
                    break;
                case 'keybcs2':
                    $descr = $GLOBALS['strCzechSlovak'];
                    break;
                case 'koi8u':
                    $descr = $GLOBALS['strUkrainian'];
                    break;
                case 'latin5':
                    $descr = $GLOBALS['strTurkish'];
                    break;
                case 'swe7':
                    $descr = $GLOBALS['strSwedish'];
                    break;
                case 'tis620':
                    $descr = $GLOBALS['strThai'];
                    break;
                default:
                    $descr = $GLOBALS['strUnknown'];
                    break;
            }
            if (!empty($is_bin)) {
                $descr .= ', ' . $GLOBALS['strBinary'];
            }
            break;
        default: $descr = $GLOBALS['strUnknown'];
    }
    if (!empty($parts[2])) {
        if ($parts[2] == 'ci') {
            $descr .= ', ' . $GLOBALS['strCaseInsensitive'];
        } elseif ($parts[2] == 'cs') {
            $descr .= ', ' . $GLOBALS['strCaseSensitive'];
        }
    }

    $collation_cache[$collation] = $descr;
    return $descr;
}
?>
