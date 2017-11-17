<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MySQL charset metadata and manipulations
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Util;

/**
 * Class used to manage MySQL charsets
 *
 * @package PhpMyAdmin
 */
class Charsets
{
    /**
     * MySQL charsets map
     *
     * @var array
     */
    public static $mysql_charset_map = array(
        'big5'         => 'big5',
        'cp-866'       => 'cp866',
        'euc-jp'       => 'ujis',
        'euc-kr'       => 'euckr',
        'gb2312'       => 'gb2312',
        'gbk'          => 'gbk',
        'iso-8859-1'   => 'latin1',
        'iso-8859-2'   => 'latin2',
        'iso-8859-7'   => 'greek',
        'iso-8859-8'   => 'hebrew',
        'iso-8859-8-i' => 'hebrew',
        'iso-8859-9'   => 'latin5',
        'iso-8859-13'  => 'latin7',
        'iso-8859-15'  => 'latin1',
        'koi8-r'       => 'koi8r',
        'shift_jis'    => 'sjis',
        'tis-620'      => 'tis620',
        'utf-8'        => 'utf8',
        'windows-1250' => 'cp1250',
        'windows-1251' => 'cp1251',
        'windows-1252' => 'latin1',
        'windows-1256' => 'cp1256',
        'windows-1257' => 'cp1257',
    );

    private static $_charsets = array();
    private static $_charsets_descriptions = array();
    private static $_collations = array();
    private static $_default_collations = array();

    /**
     * Loads charset data from the MySQL server.
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param boolean           $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return void
     */
    private static function loadCharsets(DatabaseInterface $dbi, $disableIs)
    {
        /* Data already loaded */
        if (count(self::$_charsets) > 0) {
            return;
        }

        if ($disableIs) {
            $sql = 'SHOW CHARACTER SET';
        } else {
            $sql = 'SELECT `CHARACTER_SET_NAME` AS `Charset`,'
                . ' `DESCRIPTION` AS `Description`'
                . ' FROM `information_schema`.`CHARACTER_SETS`';
        }
        $res = $dbi->query($sql);

        self::$_charsets = array();
        while ($row = $dbi->fetchAssoc($res)) {
            $name = $row['Charset'];
            self::$_charsets[] = $name;
            self::$_charsets_descriptions[$name] = $row['Description'];
        }
        $dbi->freeResult($res);

        sort(self::$_charsets, SORT_STRING);
    }

    /**
     * Loads collation data from the MySQL server.
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param boolean           $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return void
     */
    private static function loadCollations(DatabaseInterface $dbi, $disableIs)
    {
        /* Data already loaded */
        if (count(self::$_collations) > 0) {
            return;
        }

        if ($disableIs) {
            $sql = 'SHOW COLLATION';
        } else {
            $sql = 'SELECT `CHARACTER_SET_NAME` AS `Charset`,'
                . ' `COLLATION_NAME` AS `Collation`, `IS_DEFAULT` AS `Default`'
                . ' FROM `information_schema`.`COLLATIONS`';
        }

        $res = $dbi->query($sql);
        while ($row = $dbi->fetchAssoc($res)) {
            $char_set_name = $row['Charset'];
            $name = $row['Collation'];
            self::$_collations[$char_set_name][] = $name;
            if ($row['Default'] == 'Yes' || $row['Default'] == '1') {
                self::$_default_collations[$char_set_name] = $name;
            }
        }
        $dbi->freeResult($res);

        foreach (self::$_collations as $key => $value) {
            sort(self::$_collations[$key], SORT_STRING);
        }
    }

    /**
     * Get MySQL charsets
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param boolean           $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return array
     */
    public static function getMySQLCharsets(DatabaseInterface $dbi, $disableIs)
    {
        self::loadCharsets($dbi, $disableIs);
        return self::$_charsets;
    }

    /**
     * Get MySQL charsets descriptions
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param boolean           $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return array
     */
    public static function getMySQLCharsetsDescriptions(DatabaseInterface $dbi, $disableIs)
    {
        self::loadCharsets($dbi, $disableIs);
        return self::$_charsets_descriptions;
    }

    /**
     * Get MySQL collations
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param boolean           $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return array
     */
    public static function getMySQLCollations(DatabaseInterface $dbi, $disableIs)
    {
        self::loadCollations($dbi, $disableIs);
        return self::$_collations;
    }

    /**
     * Get MySQL default collations
     *
     * @param DatabaseInterface $dbi       DatabaseInterface instance
     * @param boolean           $disableIs Disable use of INFORMATION_SCHEMA
     *
     * @return array
     */
    public static function getMySQLCollationsDefault(DatabaseInterface $dbi, $disableIs)
    {
        self::loadCollations($dbi, $disableIs);
        return self::$_default_collations;
    }

    /**
     * Generate charset dropdown box
     *
     * @param DatabaseInterface $dbi            DatabaseInterface instance
     * @param boolean           $disableIs      Disable use of INFORMATION_SCHEMA
     * @param string            $name           Element name
     * @param string            $id             Element id
     * @param null|string       $default        Default value
     * @param bool              $label          Label
     * @param bool              $submitOnChange Submit on change
     *
     * @return string
     */
    public static function getCharsetDropdownBox(
        DatabaseInterface $dbi,
        $disableIs,
        $name = null,
        $id = null,
        $default = null,
        $label = true,
        $submitOnChange = false
    ) {
        self::loadCharsets($dbi, $disableIs);
        if (empty($name)) {
            $name = 'character_set';
        }

        $return_str  = '<select lang="en" dir="ltr" name="'
            . htmlspecialchars($name) . '"'
            . (empty($id) ? '' : ' id="' . htmlspecialchars($id) . '"')
            . ($submitOnChange ? ' class="autosubmit"' : '') . '>' . "\n";
        if ($label) {
            $return_str .= '<option value="">'
                . __('Charset')
                . '</option>' . "\n";
        }
        $return_str .= '<option value=""></option>' . "\n";
        foreach (self::$_charsets as $current_charset) {
            $current_cs_descr
                = empty(self::$_charsets_descriptions[$current_charset])
                ? $current_charset
                : self::$_charsets_descriptions[$current_charset];

            $return_str .= '<option value="' . $current_charset
                . '" title="' . $current_cs_descr . '"'
                . ($default == $current_charset ? ' selected="selected"' : '') . '>'
                . $current_charset . '</option>' . "\n";
        }
        $return_str .= '</select>' . "\n";

        return $return_str;
    }

    /**
     * Generate collation dropdown box
     *
     * @param DatabaseInterface $dbi            DatabaseInterface instance
     * @param boolean           $disableIs      Disable use of INFORMATION_SCHEMA
     * @param string            $name           Element name
     * @param string            $id             Element id
     * @param null|string       $default        Default value
     * @param bool              $label          Label
     * @param bool              $submitOnChange Submit on change
     *
     * @return string
     */
    public static function getCollationDropdownBox(
        DatabaseInterface $dbi,
        $disableIs,
        $name = null,
        $id = null,
        $default = null,
        $label = true,
        $submitOnChange = false
    ) {
        self::loadCharsets($dbi, $disableIs);
        self::loadCollations($dbi, $disableIs);
        if (empty($name)) {
            $name = 'collation';
        }

        $return_str  = '<select lang="en" dir="ltr" name="'
            . htmlspecialchars($name) . '"'
            . (empty($id) ? '' : ' id="' . htmlspecialchars($id) . '"')
            . ($submitOnChange ? ' class="autosubmit"' : '') . '>' . "\n";
        if ($label) {
            $return_str .= '<option value="">'
                . __('Collation')
                . '</option>' . "\n";
        }
        $return_str .= '<option value=""></option>' . "\n";
        foreach (self::$_charsets as $current_charset) {
            $current_cs_descr
                = empty(self::$_charsets_descriptions[$current_charset])
                ? $current_charset
                : self::$_charsets_descriptions[$current_charset];

            $return_str .= '<optgroup label="' . $current_charset
                . '" title="' . $current_cs_descr . '">' . "\n";
            foreach (self::$_collations[$current_charset] as $current_collation) {
                $return_str .= '<option value="' . $current_collation
                    . '" title="' . self::getCollationDescr($current_collation) . '"'
                    . ($default == $current_collation ? ' selected="selected"' : '')
                    . '>'
                    . $current_collation . '</option>' . "\n";
            }
            $return_str .= '</optgroup>' . "\n";
        }
        $return_str .= '</select>' . "\n";

        return $return_str;
    }

    /**
     * Returns description for given collation
     *
     * @param string $collation MySQL collation string
     *
     * @return string collation description
     */
    public static function getCollationDescr($collation)
    {
        $parts = explode('_', $collation);

        $name = __('Unknown');
        $variant = null;
        $suffixes = array();
        $unicode = false;
        $unknown = false;

        $level = 0;
        foreach ($parts as $part) {
            if ($level == 0) {
                /* Next will be language */
                $level = 1;
                /* First should be charset */
                switch ($part) {
                    case 'binary':
                        $name = _pgettext('Collation', 'Binary');
                        break;
                    // Unicode charsets
                    case 'utf8mb4':
                        $variant = 'UCA 4.0.0';
                        // Fall through to other unicode
                    case 'ucs2':
                    case 'utf8':
                    case 'utf16':
                    case 'utf16le':
                    case 'utf16be':
                    case 'utf32':
                        $name = _pgettext('Collation', 'Unicode');
                        $unicode = true;
                        break;
                    // West European charsets
                    case 'ascii':
                    case 'cp850':
                    case 'dec8':
                    case 'hp8':
                    case 'latin1':
                    case 'macroman':
                        $name = _pgettext('Collation', 'West European');
                        break;
                    // Central European charsets
                    case 'cp1250':
                    case 'cp852':
                    case 'latin2':
                    case 'macce':
                        $name = _pgettext('Collation', 'Central European');
                        break;
                    // Russian charsets
                    case 'cp866':
                    case 'koi8r':
                        $name = _pgettext('Collation', 'Russian');
                        break;
                    // Simplified Chinese charsets
                    case 'gb2312':
                    case 'gbk':
                        $name = _pgettext('Collation', 'Simplified Chinese');
                        break;
                    // Japanese charsets
                    case 'sjis':
                    case 'ujis':
                    case 'cp932':
                    case 'eucjpms':
                        $name = _pgettext('Collation', 'Japanese');
                        break;
                    // Baltic charsets
                    case 'cp1257':
                    case 'latin7':
                        $name = _pgettext('Collation', 'Baltic');
                        break;
                    // Other
                    case 'armscii8':
                    case 'armscii':
                        $name = _pgettext('Collation', 'Armenian');
                        break;
                    case 'big5':
                        $name = _pgettext('Collation', 'Traditional Chinese');
                        break;
                    case 'cp1251':
                        $name = _pgettext('Collation', 'Cyrillic');
                        break;
                    case 'cp1256':
                        $name = _pgettext('Collation', 'Arabic');
                        break;
                    case 'euckr':
                        $name = _pgettext('Collation', 'Korean');
                        break;
                    case 'hebrew':
                        $name = _pgettext('Collation', 'Hebrew');
                        break;
                    case 'geostd8':
                        $name = _pgettext('Collation', 'Georgian');
                        break;
                    case 'greek':
                        $name = _pgettext('Collation', 'Greek');
                        break;
                    case 'keybcs2':
                        $name = _pgettext('Collation', 'Czech-Slovak');
                        break;
                    case 'koi8u':
                        $name = _pgettext('Collation', 'Ukrainian');
                        break;
                    case 'latin5':
                        $name = _pgettext('Collation', 'Turkish');
                        break;
                    case 'swe7':
                        $name = _pgettext('Collation', 'Swedish');
                        break;
                    case 'tis620':
                        $name = _pgettext('Collation', 'Thai');
                        break;
                    default:
                        $name = _pgettext('Collation', 'Unknown');
                        $unknown = true;
                        break;
                }
                continue;
            }
            if ($level == 1) {
                /* Next will be variant unless changed later */
                $level = 4;
                /* Locale name or code */
                $found = true;
                switch ($part) {
                    case 'general':
                        break;
                    case 'bulgarian':
                    case 'bg':
                        $name = _pgettext('Collation', 'Bulgarian');
                        break;
                    case 'chinese':
                    case 'cn':
                        if ($unicode) {
                            $name = _pgettext('Collation', 'Chinese');
                        }
                        break;
                    case 'croatian':
                    case 'hr':
                        $name = _pgettext('Collation', 'Croatian');
                        break;
                    case 'czech':
                    case 'cs':
                        $name = _pgettext('Collation', 'Czech');
                        break;
                    case 'danish':
                    case 'da':
                        $name = _pgettext('Collation', 'Danish');
                        break;
                    case 'english':
                    case 'en':
                        $name = _pgettext('Collation', 'English');
                        break;
                    case 'esperanto':
                    case 'eo':
                        $name = _pgettext('Collation', 'Esperanto');
                        break;
                    case 'estonian':
                    case 'et':
                        $name = _pgettext('Collation', 'Estonian');
                        break;
                    case 'german1':
                        $name = _pgettext('Collation', 'German (dictionary order)');
                        break;
                    case 'german2':
                        $name = _pgettext('Collation', 'German (phone book order)');
                        break;
                    case 'german':
                    case 'de':
                        /* Name is set later */
                        $level = 2;
                        break;
                    case 'hungarian':
                    case 'hu':
                        $name = _pgettext('Collation', 'Hungarian');
                        break;
                    case 'icelandic':
                    case 'is':
                        $name = _pgettext('Collation', 'Icelandic');
                        break;
                    case 'japanese':
                    case 'ja':
                        $name = _pgettext('Collation', 'Japanese');
                        break;
                    case 'la':
                        $name = _pgettext('Collation', 'Classical Latin');
                        break;
                    case 'latvian':
                    case 'lv':
                        $name = _pgettext('Collation', 'Latvian');
                        break;
                    case 'lithuanian':
                    case 'lt':
                        $name = _pgettext('Collation', 'Lithuanian');
                        break;
                    case 'korean':
                    case 'ko':
                        $name = _pgettext('Collation', 'Korean');
                        break;
                    case 'myanmar':
                    case 'my':
                        $name = _pgettext('Collation', 'Burmese');
                        break;
                    case 'persian':
                        $name = _pgettext('Collation', 'Persian');
                        break;
                    case 'polish':
                    case 'pl':
                        $name = _pgettext('Collation', 'Polish');
                        break;
                    case 'roman':
                        $name = _pgettext('Collation', 'West European');
                        break;
                    case 'romanian':
                    case 'ro':
                        $name = _pgettext('Collation', 'Romanian');
                        break;
                    case 'si':
                    case 'sinhala':
                        $name = _pgettext('Collation', 'Sinhalese');
                        break;
                    case 'slovak':
                    case 'sl':
                        $name = _pgettext('Collation', 'Slovak');
                        break;
                    case 'slovenian':
                    case 'sl':
                        $name = _pgettext('Collation', 'Slovenian');
                        break;
                    case 'spanish':
                        $name = _pgettext('Collation', 'Spanish (modern)');
                        break;
                    case 'es':
                        /* Name is set later */
                        $level = 3;
                        break;
                    case 'spanish2':
                        $name = _pgettext('Collation', 'Spanish (traditional)');
                        break;
                    case 'swedish':
                        $name = _pgettext('Collation', 'Swedish');
                        break;
                    case 'thai':
                    case 'th':
                        $name = _pgettext('Collation', 'Thai');
                        break;
                    case 'turkish':
                    case 'tr':
                        $name = _pgettext('Collation', 'Turkish');
                        break;
                    case 'ukrainian':
                    case 'uk':
                        $name = _pgettext('Collation', 'Ukrainian');
                        break;
                    case 'vietnamese':
                    case 'vi':
                        $name = _pgettext('Collation', 'Vietnamese');
                        break;
                    case 'unicode':
                        if ($unknown) {
                            $name = _pgettext('Collation', 'Unicode');
                        }
                        break;
                    default:
                        $found = false;
                }
                if ($found) {
                    continue;
                }
                // Not parsed token, fall to next level
            }
            if ($level == 2) {
                /* Next will be variant */
                $level = 4;
                /* Germal variant */
                if ($part == 'pb') {
                    $name = _pgettext('Collation', 'German (phone book order)');
                    continue;
                }
                $name = _pgettext('Collation', 'German (dictionary order)');
                // Not parsed token, fall to next level
            }
            if ($level == 3) {
                /* Next will be variant */
                $level = 4;
                /* Spanish variant */
                if ($part == 'trad') {
                    $name = _pgettext('Collation', 'Spanish (traditional)');
                    continue;
                }
                $name = _pgettext('Collation', 'Spanish (modern)');
                // Not parsed token, fall to next level
            }
            if ($level == 4) {
                /* Next will be suffix */
                $level = 5;
                /* Variant */
                $found = true;
                switch ($part) {
                    case '0900':
                        $variant = 'UCA 9.0.0';
                        break;
                    case '520':
                        $variant = 'UCA 5.2.0';
                        break;
                    case 'mysql561':
                        $variant = 'MySQL 5.6.1';
                        break;
                    case 'mysql500':
                        $variant = 'MySQL 5.0.0';
                        break;
                    default:
                        $found = false;
                }
                if ($found) {
                    continue;
                }
                // Not parsed token, fall to next level
            }
            if ($level == 5) {
                /* Suffixes */
                switch ($part) {
                    case 'ci':
                        $suffixes[] = _pgettext('Collation variant', 'case-insensitive');
                        break;
                    case 'cs':
                        $suffixes[] = _pgettext('Collation variant', 'case-sensitive');
                        break;
                    case 'ai':
                        $suffixes[] = _pgettext('Collation variant', 'accent-insensitive');
                        break;
                    case 'as':
                        $suffixes[] = _pgettext('Collation variant', 'accent-sensitive');
                        break;
                    case 'w2':
                    case 'l2':
                        $suffixes[] = _pgettext('Collation variant', 'multi-level');
                        break;
                    case 'bin':
                        $suffixes[] = _pgettext('Collation variant', 'binary');
                        break;
                }
            }
        }

        $result = $name;
        if (! is_null($variant)) {
            $result .= ' (' . $variant . ')';
        }
        if (count($suffixes) > 0) {
            $result .= ', ' . implode(', ', $suffixes);
        }
        return $result;
    }
}
