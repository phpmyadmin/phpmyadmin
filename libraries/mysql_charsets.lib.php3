<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!defined('PMA_MYSQL_CHARSETS_LIB_INCLUDED')){
    define('PMA_MYSQL_CHARSETS_LIB_INCLUDED', 1);

    $res = PMA_mysql_query('SHOW CHARACTER SET;', $userlink)
        or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW CHARACTER SET;');

    $mysql_charsets = array();
    while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {
        $mysql_charsets[] = $row['Charset'];
        $mysql_charsets_maxlen[$row['Charset']] = $row['Maxlen'];
        $mysql_charsets_descriptions[$row['Charset']] = $row['Description'];
    }
    @mysql_free_result($res);
    unset($res);
    unset($row);

    $res = PMA_mysql_query('SHOW COLLATION;', $userlink)
        or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW COLLATION;');

    if (PMA_PHP_INT_VERSION >= 40000) {
        sort($mysql_charsets, SORT_STRING);
    } else {
        sort($mysql_charsets);
    }

    $mysql_collations = array_flip($mysql_charsets);
    $mysql_default_collations = array();;
    while ($row = PMA_mysql_fetch_array($res, MYSQL_ASSOC)) {
        if (!is_array($mysql_collations[$row['Charset']])) {
            $mysql_collations[$row['Charset']] = array($row['Collation']);
        } else {
            $mysql_collations[$row['Charset']][] = $row['Collation'];
        }
        if ($row['D'] == 'Y') {
            $mysql_default_collations[$row['Charset']] = $row['Collation'];
        }
    }

    reset($mysql_collations);
    $mysql_collations_count = 0;
    while (list($key, $value) = each($mysql_collations)) {
        $mysql_collations_count += count($mysql_collations[$key]);
        if (PMA_PHP_INT_VERSION >= 40000) {
            sort($mysql_collations[$key], SORT_STRING);
        } else {
            sort($mysql_collations[$key]);
        }
        reset($mysql_collations[$key]);
    }
    reset($mysql_collations);
    
    @mysql_free_result($res);
    unset($res);
    unset($row);

    function PMA_getCollationDescr($collation) {
        $parts = explode('_', $collation);
        if (count($parts) == 1) {
            return '';
        }
        $descr = '';
        switch ($parts[1]) {
            case 'bin':
                $descr = $GLOBALS['strBinary'];
                break;
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
            case 'general':
                $descr = $GLOBALS['strMultilingual'];
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

} // $__PMA_MYSQL_CHARSETS_LIB__

?>
