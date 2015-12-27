<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Shared code for mysql charsets
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Generate charset dropdown box
 *
 * @param int         $type           Type
 * @param string      $name           Element name
 * @param string      $id             Element id
 * @param null|string $default        Default value
 * @param bool        $label          Label
 * @param bool        $submitOnChange Submit on change
 *
 * @return string
 */
function PMA_generateCharsetDropdownBox($type = PMA_CSDROPDOWN_COLLATION,
    $name = null, $id = null, $default = null, $label = true,
    $submitOnChange = false
) {
    global $mysql_charsets, $mysql_charsets_descriptions,
        $mysql_charsets_available, $mysql_collations, $mysql_collations_available;

    if (empty($name)) {
        if ($type == PMA_CSDROPDOWN_COLLATION) {
            $name = 'collation';
        } else {
            $name = 'character_set';
        }
    }

    $return_str  = '<select lang="en" dir="ltr" name="'
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
                    . ($default == $current_collation ? ' selected="selected"' : '')
                    . '>'
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

/**
 * Generate the charset query part
 *
 * @param string           $collation Collation
 * @param boolean optional $override  force 'CHARACTER SET' keyword
 *
 * @return string
 */
function PMA_generateCharsetQueryPart($collation, $override = false)
{
    if (!PMA_DRIZZLE) {
        list($charset) = explode('_', $collation);
        $keyword = ' CHARSET=';

        if ($override) {
            $keyword = ' CHARACTER SET ';
        }
        return $keyword . $charset
            . ($charset == $collation ? '' : ' COLLATE ' . $collation);
    } else {
        return ' COLLATE ' . $collation;
    }
}

/**
 * returns collation of given db
 *
 * @param string $db name of db
 *
 * @return string  collation of $db
 */
function PMA_getDbCollation($db)
{
    if ($GLOBALS['dbi']->isSystemSchema($db)) {
        // We don't have to check the collation of the virtual
        // information_schema database: We know it!
        return 'utf8_general_ci';
    }

    if (! $GLOBALS['cfg']['Server']['DisableIS']) {
        // this is slow with thousands of databases
        $sql = PMA_DRIZZLE
            ? 'SELECT DEFAULT_COLLATION_NAME FROM data_dictionary.SCHEMAS'
            . ' WHERE SCHEMA_NAME = \'' . PMA_Util::sqlAddSlashes($db)
            . '\' LIMIT 1'
            : 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA'
            . ' WHERE SCHEMA_NAME = \'' . PMA_Util::sqlAddSlashes($db)
            . '\' LIMIT 1';
        return $GLOBALS['dbi']->fetchValue($sql);
    } else {
        $GLOBALS['dbi']->selectDb($db);
        $return = $GLOBALS['dbi']->fetchValue('SELECT @@collation_database');
        if ($db !== $GLOBALS['db']) {
            $GLOBALS['dbi']->selectDb($GLOBALS['db']);
        }
        return $return;
    }
}

/**
 * returns default server collation from show variables
 *
 * @return string  $server_collation
 */
function PMA_getServerCollation()
{
    return $GLOBALS['dbi']->fetchValue('SELECT @@collation_server');
}

/**
 * returns description for given collation
 *
 * @param string $collation MySQL collation string
 *
 * @return string  collation description
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
    case 'sinhala':
        $descr = __('Sinhalese');
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
    case 'vietnamese':
        $descr = __('Vietnamese');
        break;
    /** @noinspection PhpMissingBreakStatementInspection */
    case 'bin':
        $is_bin = true;
        // no break; statement here, continuing with 'general' section:
    case 'general':
        switch ($parts[0]) {
        // Unicode charsets
        case 'ucs2':
        case 'utf8':
        case 'utf8mb4':
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
