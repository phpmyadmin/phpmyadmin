<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (PMA_MYSQL_INT_VERSION >= 40100){

    $res = PMA_DBI_query('SHOW CHARACTER SET;');

    $mysql_charsets = array();
    while ($row = PMA_DBI_fetch_assoc($res)) {
        $mysql_charsets[] = $row['Charset'];
        $mysql_charsets_maxlen[$row['Charset']] = $row['Maxlen'];
        $mysql_charsets_descriptions[$row['Charset']] = $row['Description'];
    }
    @PMA_DBI_free_result($res);
    unset($res, $row);

    $res = PMA_DBI_query('SHOW COLLATION;');

    $mysql_charsets_count = count($mysql_charsets);
    sort($mysql_charsets, SORT_STRING);

    $mysql_collations = array_flip($mysql_charsets);
    $mysql_default_collations = $mysql_collations_flat = array();;
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
    }

    $mysql_collations_count = count($mysql_collations_flat);
    sort($mysql_collations_flat, SORT_STRING);
    foreach ($mysql_collations AS $key => $value) {
        sort($mysql_collations[$key], SORT_STRING);
        reset($mysql_collations[$key]);
    }

    @PMA_DBI_free_result($res);
    unset($res, $row);

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

    function PMA_getDbCollation($db) {
        global $userlink;
        if (PMA_MYSQL_INT_VERSION >= 40101) {
            // MySQL 4.1.0 does not support seperate charset settings
            // for databases.
            $res = PMA_DBI_query('SHOW CREATE DATABASE ' . PMA_backquote($db) . ';', NULL, PMA_DBI_QUERY_STORE);
            $row = PMA_DBI_fetch_row($res);
            PMA_DBI_free_result($res);
            $tokenized = explode(' ', $row[1]);
            unset($row, $res, $sql_query);

            for ($i = 1; $i + 3 < count($tokenized); $i++) {
                if ($tokenized[$i] == 'DEFAULT' && $tokenized[$i + 1] == 'CHARACTER' && $tokenized[$i + 2] == 'SET') {
                    // We've found the character set!
                    if (isset($tokenized[$i + 5]) && $tokenized[$i + 4] == 'COLLATE') {
                        return $tokenized[$i + 5]; // We found the collation!
                    } else {
                        // We did not find the collation, so let's return the
                        // default collation for the charset we've found.
                        return $GLOBALS['mysql_default_collations'][$tokenized [$i + 3]];
                    }
                }
            }
        }
        return '';
    }

    define('PMA_CSDROPDOWN_COLLATION', 0);
    define('PMA_CSDROPDOWN_CHARSET',   1);

    function PMA_generateCharsetDropdownBox($type = PMA_CSDROPDOWN_COLLATION, $name = NULL, $id = NULL, $default = NULL, $label = TRUE, $indent = 0, $submitOnChange = FALSE) {
        global $mysql_charsets, $mysql_charsets_descriptions, $mysql_collations;

        if (empty($name)) {
            if ($type == PMA_CSDROPDOWN_COLLATION) {
                $name = 'collation';
            } else {
                $name = 'character_set';
            }
        }

        $spacer = '';
        for ($i = 1; $i <= $indent; $i++) $spacer .= '    ';

        $return_str  = $spacer . '<select name="' . htmlspecialchars($name) . '"' . (empty($id) ? '' : ' id="' . htmlspecialchars($id) . '"') . ($submitOnChange ? ' onchange="this.form.submit();"' : '') . '>' . "\n";
        if ($label) {
            $return_str .= $spacer . '    <option value="">' . ($type == PMA_CSDROPDOWN_COLLATION ? $GLOBALS['strCollation'] : $GLOBALS['strCharset']) . '</option>' . "\n";
        }
        $return_str .= $spacer . '    <option value=""></option>' . "\n";
        foreach ($mysql_charsets as $current_charset) {
            $current_cs_descr = empty($mysql_charsets_descriptions[$current_charset]) ? $current_charset : $mysql_charsets_descriptions[$current_charset];
            if ($type == PMA_CSDROPDOWN_COLLATION) {
                $return_str .= $spacer . '    <optgroup label="' . $current_charset . '" title="' . $current_cs_descr . '">' . "\n";
                foreach ($mysql_collations[$current_charset] as $current_collation) {
                    $return_str .= $spacer . '        <option value="' . $current_collation . '" title="' . PMA_getCollationDescr($current_collation) . '"' . ($default == $current_collation ? ' selected="selected"' : '') . '>' . $current_collation . '</option>' . "\n";
                }
                $return_str .= $spacer . '    </optgroup>' . "\n";
            } else {
                $return_str .= $spacer . '    <option value="' . $current_charset . '" title="' . $current_cs_descr . '"' . ($default == $current_charset ? ' selected="selected"' : '') . '>' . $current_charset . '</option>' . "\n";
            }
        }
        $return_str .= $spacer . '</select>' . "\n";

        return $return_str;
    }

    function PMA_generateCharsetQueryPart($collation) {
        list($charset) = explode('_', $collation);
        return ' CHARACTER SET ' . $charset . ($charset == $collation ? '' : ' COLLATE ' . $collation);
    }

}

?>
