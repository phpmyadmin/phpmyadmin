<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold the PMA\libraries\LanguageManager class
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use PMA\libraries\LanguageManager;


/**
 * Language object
 *
 * @package PhpMyAdmin
 */
class Language
{
    protected $code;
    protected $name;
    protected $native;
    protected $regex;
    protected $mysql;

    /**
     * Constructs the Language object
     *
     * @param string $code   Language code
     * @param string $name   English name
     * @param string $native Native name
     * @param string $regex  Match regullar expression
     * @param string $mysql  MySQL locale code
     *
     */
    public function __construct($code, $name, $native, $regex, $mysql)
    {
        $this->code = $code;
        $this->name = $name;
        $this->native = $native;
        if (strpos($regex, '[-_]') === false) {
            $regex = str_replace('|', '([-_][[:alpha:]]{2,3})?|', $regex);
        }
        $this->regex = $regex;
        $this->mysql = $mysql;
    }

    /**
     * Returns native name for language
     *
     * @return string
     */
    public function getNativeName()
    {
        return $this->native;
    }

    /**
     * Returns English name for language
     *
     * @return string
     */
    public function getEnglishName()
    {
        return $this->name;
    }

    /**
     * Returns verbose name for language
     *
     * @return string
     */
    public function getName()
    {
        if (! empty($this->native)) {
            return $this->native . ' - ' . $this->name;
        } else {
            return $this->name;
        }
    }

    /**
     * Returns language code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Returns MySQL locale code, can be empty
     *
     * @return string
     */
    public function getMySQLLocale()
    {
        return $this->mysql;
    }

    /**
     * Compare function used for sorting
     *
     * @param Language $other Other object to compare
     *
     * @return int same as strcmp
     */
    public function cmp($other)
    {
        return strcmp($this->name, $other->name);
    }

    /**
     * Checks whether language is currently active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $GLOBALS['lang'] == $this->code;
    }

    /**
     * Checks whether language matches HTTP header Accept-Language.
     *
     * @param string $header Header content
     *
     * @return bool
     */
    public function matchesAcceptLanguage($header)
    {
        $pattern = '/^('
            . addcslashes($this->regex, '/')
            . ')(;q=[0-9]\\.[0-9])?$/i';
        return preg_match($pattern, $header);
    }

    /**
     * Checks whether language matches HTTP header User-Agent
     *
     * @param string $header Header content
     *
     * @return bool
     */
    public function matchesUserAgent($header)
    {
        $pattern = '/(\(|\[|;[[:space:]])('
            . addcslashes($this->regex, '/')
            . ')(;|\]|\))/i';
        return preg_match($pattern, $header);
    }

    /**
     * Checks whether langauge is RTL
     *
     * @return bool
     */
    public function isRTL()
    {
        return in_array($this->code, array('ar', 'fa', 'he', 'ur'));
    }

    /**
     * Activates given translation
     *
     * @return bool
     */
    public function activate()
    {
        $GLOBALS['lang'] = $this->code;

        // Set locale
        _setlocale(0, $this->code);
        _bindtextdomain('phpmyadmin', LOCALE_PATH);
        _textdomain('phpmyadmin');

        /* Text direction for language */
        if ($this->isRTL()) {
            $GLOBALS['text_dir'] = 'rtl';
        } else {
            $GLOBALS['text_dir'] = 'ltr';
        }

        /* TCPDF */
        $GLOBALS['l'] = array();

        /* TCPDF settings */
        $GLOBALS['l']['a_meta_charset'] = 'UTF-8';
        $GLOBALS['l']['a_meta_dir'] = $GLOBALS['text_dir'];
        $GLOBALS['l']['a_meta_language'] = $this->code;

        /* TCPDF translations */
        $GLOBALS['l']['w_page'] = __('Page number:');

        /* Show possible warnings from langauge selection */
        LanguageManager::getInstance()->showWarnings();
    }
}
