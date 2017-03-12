<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MySQL charset metadata and manipulations
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;
use PMA\libraries\Util;

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
     * @return void
     */
    public static function loadCharsets()
    {
        /* Data already loaded */
        if (count(self::$_charsets) > 0) {
            return;
        }

        $sql = 'SELECT * FROM information_schema.CHARACTER_SETS';
        $res = $GLOBALS['dbi']->query($sql);

        self::$_charsets = array();
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
            $name = $row['CHARACTER_SET_NAME'];
            self::$_charsets[] = $name;
            self::$_charsets_descriptions[$name] = $row['DESCRIPTION'];
        }
        $GLOBALS['dbi']->freeResult($res);

        sort(self::$_charsets, SORT_STRING);
    }

    /**
     * Loads collation data from the MySQL server.
     *
     * @return void
     */
    public static function loadCollations()
    {
        /* Data already loaded */
        if (count(self::$_collations) > 0) {
            return;
        }

        $sql = 'SELECT * FROM information_schema.COLLATIONS';
        $res = $GLOBALS['dbi']->query($sql);
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
            $char_set_name = $row['CHARACTER_SET_NAME'];
            $name = $row['COLLATION_NAME'];
            self::$_collations[$char_set_name][] = $name;
            if ($row['IS_DEFAULT'] == 'Yes' || $row['IS_DEFAULT'] == '1') {
                self::$_default_collations[$char_set_name] = $name;
            }
        }
        $GLOBALS['dbi']->freeResult($res);

        foreach (self::$_collations as $key => $value) {
            sort(self::$_collations[$key], SORT_STRING);
        }
    }

    public static function getMySQLCharsets()
    {
        self::loadCharsets();
        return self::$_charsets;
    }

    public static function getMySQLCharsetsDescriptions()
    {
        self::loadCharsets();
        return self::$_charsets_descriptions;
    }

    public static function getMySQLCollations()
    {
        self::loadCollations();
        return self::$_collations;
    }

    public static function getMySQLCollationsDefault()
    {
        self::loadCollations();
        return self::$_default_collations;
    }

    /**
     * Generate charset dropdown box
     *
     * @param string      $name           Element name
     * @param string      $id             Element id
     * @param null|string $default        Default value
     * @param bool        $label          Label
     * @param bool        $submitOnChange Submit on change
     *
     * @return string
     */
    public static function getCharsetDropdownBox(
        $name = null, $id = null, $default = null, $label = true,
        $submitOnChange = false
    ) {
        self::loadCharsets();
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
     * @param string      $name           Element name
     * @param string      $id             Element id
     * @param null|string $default        Default value
     * @param bool        $label          Label
     * @param bool        $submitOnChange Submit on change
     *
     * @return string
     */
    public static function getCollationDropdownBox(
        $name = null, $id = null, $default = null, $label = true,
        $submitOnChange = false
    ) {
        self::loadCharsets();
        self::loadCollations();
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
     * returns description for given collation
     *
     * @param string $collation MySQL collation string
     *
     * @return string  collation description
     */
    public static function getCollationDescr($collation)
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
        case 'myanmar':
            $descr = __('Burmese');
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
            case 'utf16':
            case 'utf16le':
            case 'utf16be':
            case 'utf32':
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
                $descr .= ', ' . __('binary collation');
            }
            break;
        default: $descr = __('unknown');
        }
        if (!empty($parts[2])) {
            if ($parts[2] == 'ci') {
                $descr .= ', ' . __('case-insensitive collation');
            } elseif ($parts[2] == 'cs') {
                $descr .= ', ' . __('case-sensitive collation');
            }
        }

        return $descr;
    }
}
