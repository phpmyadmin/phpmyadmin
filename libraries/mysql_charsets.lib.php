<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (PMA_MYSQL_INT_VERSION >= 40100){

    $res = PMA_mysql_query('SHOW CHARACTER SET;', $userlink)
        or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW CHARACTER SET;');

    $mysql_charsets = array();
    while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {
        $mysql_charsets[] = $row['Charset'];
        $mysql_charsets_maxlen[$row['Charset']] = $row['Maxlen'];
        $mysql_charsets_descriptions[$row['Charset']] = $row['Description'];
    }
    @mysql_free_result($res);
    unset($res, $row);

    $res = PMA_mysql_query('SHOW COLLATION;', $userlink)
        or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW COLLATION;');

    sort($mysql_charsets, SORT_STRING);

    $mysql_collations = array_flip($mysql_charsets);
    $mysql_default_collations = array();;
    while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {
        if (!is_array($mysql_collations[$row['Charset']])) {
            $mysql_collations[$row['Charset']] = array($row['Collation']);
        } else {
            $mysql_collations[$row['Charset']][] = $row['Collation'];
        }
        if ((isset($row['D']) && $row['D'] == 'Y') || (isset($row['Default']) && $row['Default'] == 'Yes')) {
            $mysql_default_collations[$row['Charset']] = $row['Collation'];
        }
    }

    $mysql_collations_count = 0;
    foreach($mysql_collations AS $key => $value) {
        $mysql_collations_count += count($mysql_collations[$key]);
        sort($mysql_collations[$key], SORT_STRING);
        reset($mysql_collations[$key]);
    }

    @mysql_free_result($res);
    unset($res, $row);

    function PMA_getCollationDescr($collation) {
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
            case 'lithuanian':
                $descr = $GLOBALS['strLithuanian'];
                break;
            case 'swedish':
                $descr = $GLOBALS['strSwedish'];
                break;
            case 'turkish':
                $descr = $GLOBALS['strTurkish'];
                break;
            case 'ukrainian':
                $descr = $GLOBALS['strUkrainian'];
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
                    case 'greek':
                        $descr = $GLOBALS['strGreek'];
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
            default: return '';
        }
        if (!empty($parts[2])) {
            if ($parts[2] == 'ci') {
                $descr .= ', ' . $GLOBALS['strCaseInsensitive'];
            } elseif ($parts[2] == 'cs') {
                $descr .= ', ' . $GLOBALS['strCaseSensitive'];
            }
        }
        return $descr;
    }

}

?>
