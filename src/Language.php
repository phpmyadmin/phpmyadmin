<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;
use function _bindtextdomain;
use function _setlocale;
use function _textdomain;
use function addcslashes;
use function function_exists;
use function in_array;
use function preg_match;
use function setlocale;
use function str_contains;
use function str_replace;
use function strcmp;

/**
 * Language object
 */
class Language
{
    protected string $regex;

    /**
     * Constructs the Language object
     *
     * @param string $code   Language code
     * @param string $name   English name
     * @param string $native Native name
     * @param string $regex  Match regular expression
     * @param string $mysql  MySQL locale code
     */
    public function __construct(
        protected string $code,
        protected string $name,
        protected string $native,
        string $regex,
        protected string $mysql,
    ) {
        if (! str_contains($regex, '[-_]')) {
            $regex = str_replace('|', '([-_][[:alpha:]]{2,3})?|', $regex);
        }

        $this->regex = $regex;
    }

    /**
     * Returns native name for language
     */
    public function getNativeName(): string
    {
        return $this->native;
    }

    /**
     * Returns English name for language
     */
    public function getEnglishName(): string
    {
        return $this->name;
    }

    /**
     * Returns verbose name for language
     */
    public function getName(): string
    {
        if ($this->native !== '') {
            return $this->name . ' - ' . $this->native;
        }

        return $this->name;
    }

    /**
     * Returns language code
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Returns MySQL locale code, can be empty
     */
    public function getMySQLLocale(): string
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
    public function cmp(Language $other): int
    {
        return strcmp($this->name, $other->name);
    }

    /**
     * Checks whether language is currently active.
     */
    public function isActive(): bool
    {
        return $GLOBALS['lang'] == $this->code;
    }

    /**
     * Checks whether language matches HTTP header Accept-Language.
     *
     * @param string $header Header content
     */
    public function matchesAcceptLanguage(string $header): bool
    {
        $pattern = '/^('
            . addcslashes($this->regex, '/')
            . ')(;q=[0-9]\\.[0-9])?$/i';

        return preg_match($pattern, $header) === 1;
    }

    /**
     * Checks whether language matches HTTP header User-Agent
     *
     * @param string $header Header content
     */
    public function matchesUserAgent(string $header): bool
    {
        $pattern = '/(\(|\[|;[[:space:]])('
            . addcslashes($this->regex, '/')
            . ')(;|\]|\))/i';

        return preg_match($pattern, $header) === 1;
    }

    /**
     * Checks whether language is RTL
     */
    public function isRTL(): bool
    {
        return in_array($this->code, ['ar', 'fa', 'he', 'ur'], true);
    }

    /**
     * Activates given translation
     */
    public function activate(): void
    {
        $GLOBALS['lang'] = $this->code;

        // Set locale
        _setlocale(0, $this->code);
        _bindtextdomain('phpmyadmin', LOCALE_PATH);
        _textdomain('phpmyadmin');
        // Set PHP locale as well
        if (function_exists('setlocale')) {
            setlocale(0, $this->code);
        }

        /* Text direction for language */
        LanguageManager::$textDir = $this->isRTL() ? 'rtl' : 'ltr';

        /* TCPDF */
        $GLOBALS['l'] = [];

        /* TCPDF settings */
        $GLOBALS['l']['a_meta_charset'] = 'UTF-8';
        $GLOBALS['l']['a_meta_dir'] = LanguageManager::$textDir;
        $GLOBALS['l']['a_meta_language'] = $this->code;

        /* TCPDF translations */
        $GLOBALS['l']['w_page'] = __('Page number:');

        /* Show possible warnings from language selection */
        LanguageManager::getInstance()->showWarnings();
    }
}
