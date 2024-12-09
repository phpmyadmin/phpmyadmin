<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Exceptions\ConfigException;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Routing\Routing;
use PhpMyAdmin\Theme\ThemeManager;
use Throwable;

use function __;
use function array_key_last;
use function array_replace_recursive;
use function array_slice;
use function count;
use function defined;
use function error_reporting;
use function explode;
use function fclose;
use function file_exists;
use function filemtime;
use function fileperms;
use function fopen;
use function fread;
use function function_exists;
use function gd_info;
use function implode;
use function ini_get;
use function is_array;
use function is_bool;
use function is_dir;
use function is_numeric;
use function is_readable;
use function is_string;
use function is_writable;
use function mb_strtolower;
use function md5;
use function min;
use function mkdir;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function parse_url;
use function preg_match;
use function realpath;
use function rtrim;
use function setcookie;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function stripos;
use function strtolower;
use function sys_get_temp_dir;
use function time;
use function trim;

use const CHANGELOG_FILE;
use const DIRECTORY_SEPARATOR;
use const PHP_OS;
use const PHP_URL_PATH;
use const PHP_URL_SCHEME;

/**
 * Configuration handling
 *
 * @psalm-import-type ServerSettingsType from Server
 * @psalm-import-type SettingsType from Settings
 */
class Config
{
    public static self|null $instance = null;

    /** @var mixed[]   default configuration settings */
    public array $default;

    /** @var mixed[]   configuration settings, without user preferences applied */
    public array $baseSettings;

    /** @psalm-var SettingsType */
    public array $settings;

    /** @var string  config source */
    public string $source = '';

    /** @var int     source modification time */
    public int $sourceMtime = 0;

    public bool $errorConfigFile = false;

    private bool $isHttps = false;

    public Settings $config;
    /** @var int<0, max> */
    public int $server = 0;

    /** @var array<string,string|null> $tempDir */
    private static array $tempDir = [];

    private bool $hasSelectedServer = false;

    /** @psalm-var ServerSettingsType */
    public array $selectedServer;

    private bool $isSetup = false;

    public function __construct()
    {
        $this->config = new Settings([]);
        $config = $this->config->asArray();
        $this->default = $config;
        $this->settings = $config;
        $this->baseSettings = $config;
        $this->selectedServer = (new Server())->asArray();
    }

    /** @deprecated Use dependency injection instead. */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setSetup(bool $isSetup): void
    {
        $this->isSetup = $isSetup;
    }

    public function isSetup(): bool
    {
        return $this->isSetup;
    }

    /**
     * @param string|null $source source to read config from
     *
     * @throws ConfigException
     */
    public function loadAndCheck(string|null $source = null): void
    {
        // functions need to refresh in case of config file changed goes in PhpMyAdmin\Config::load()
        $this->load($source);

        // other settings, independent of config file, comes in
        $this->checkSystem();

        $this->isHttps = $this->isHttps();
        $this->baseSettings = $this->settings;
    }

    /**
     * sets system and application settings
     */
    public function checkSystem(): void
    {
        $this->checkWebServerOs();
        $this->checkGd2();
        $this->checkClient();
        $this->checkUpload();
        $this->checkUploadSize();
        $this->checkOutputCompression();
    }

    /**
     * whether to use gzip output compression or not
     */
    public function checkOutputCompression(): void
    {
        // If zlib output compression is set in the php configuration file, no
        // output buffering should be run
        if (ini_get('zlib.output_compression')) {
            $this->set('OBGzip', false);
        }

        // enable output-buffering (if set to 'auto')
        if (strtolower((string) $this->get('OBGzip')) !== 'auto') {
            return;
        }

        $this->set('OBGzip', true);
    }

    /**
     * Sets the client platform based on user agent
     *
     * @param string $userAgent the user agent
     */
    private function setClientPlatform(string $userAgent): void
    {
        if (str_contains($userAgent, 'Win')) {
            $this->set('PMA_USR_OS', 'Win');
        } elseif (str_contains($userAgent, 'Mac')) {
            $this->set('PMA_USR_OS', 'Mac');
        } elseif (str_contains($userAgent, 'Linux')) {
            $this->set('PMA_USR_OS', 'Linux');
        } elseif (str_contains($userAgent, 'Unix')) {
            $this->set('PMA_USR_OS', 'Unix');
        } elseif (str_contains($userAgent, 'OS/2')) {
            $this->set('PMA_USR_OS', 'OS/2');
        } else {
            $this->set('PMA_USR_OS', 'Other');
        }
    }

    /**
     * Determines platform (OS), browser and version of the user
     * Based on a phpBuilder article:
     *
     * @see http://www.phpbuilder.net/columns/tim20000821.php
     */
    public function checkClient(): void
    {
        $httpUserAgent = '';
        if (Core::getEnv('HTTP_USER_AGENT') !== '') {
            $httpUserAgent = Core::getEnv('HTTP_USER_AGENT');
        }

        // 1. Platform
        $this->setClientPlatform($httpUserAgent);

        // 2. browser and version
        // (must check everything else before Mozilla)

        $isMozilla = preg_match('@Mozilla/([0-9]\.[0-9]{1,2})@', $httpUserAgent, $mozillaVersion) === 1;

        if (preg_match('@Opera(/| )([0-9]\.[0-9]{1,2})@', $httpUserAgent, $logVersion) === 1) {
            $this->set('PMA_USR_BROWSER_VER', $logVersion[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'OPERA');
        } elseif (preg_match('@(MS)?IE ([0-9]{1,2}\.[0-9]{1,2})@', $httpUserAgent, $logVersion) === 1) {
            $this->set('PMA_USR_BROWSER_VER', $logVersion[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'IE');
        } elseif (preg_match('@Trident/(7)\.0@', $httpUserAgent, $logVersion) === 1) {
            $this->set('PMA_USR_BROWSER_VER', (string) ((int) $logVersion[1] + 4));
            $this->set('PMA_USR_BROWSER_AGENT', 'IE');
        } elseif (preg_match('@OmniWeb/([0-9]{1,3})@', $httpUserAgent, $logVersion) === 1) {
            $this->set('PMA_USR_BROWSER_VER', $logVersion[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'OMNIWEB');
            // Konqueror 2.2.2 says Konqueror/2.2.2
            // Konqueror 3.0.3 says Konqueror/3
        } elseif (preg_match('@(Konqueror/)(.*)(;)@', $httpUserAgent, $logVersion) === 1) {
            $this->set('PMA_USR_BROWSER_VER', $logVersion[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'KONQUEROR');
            // must check Chrome before Safari
        } elseif ($isMozilla && preg_match('@Chrome/([0-9.]*)@', $httpUserAgent, $logVersion) === 1) {
            $this->set('PMA_USR_BROWSER_VER', $logVersion[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'CHROME');
            // newer Safari
        } elseif ($isMozilla && preg_match('@Version/(.*) Safari@', $httpUserAgent, $logVersion) === 1) {
            $this->set('PMA_USR_BROWSER_VER', $logVersion[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
            // older Safari
        } elseif ($isMozilla && preg_match('@Safari/([0-9]*)@', $httpUserAgent, $logVersion) === 1) {
            $this->set('PMA_USR_BROWSER_VER', $mozillaVersion[1] . '.' . $logVersion[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
            // Firefox
        } elseif (
            ! str_contains($httpUserAgent, 'compatible')
            && preg_match('@Firefox/([\w.]+)@', $httpUserAgent, $logVersion) === 1
        ) {
            $this->set('PMA_USR_BROWSER_VER', $logVersion[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'FIREFOX');
        } elseif (preg_match('@rv:1\.9(.*)Gecko@', $httpUserAgent) === 1) {
            $this->set('PMA_USR_BROWSER_VER', '1.9');
            $this->set('PMA_USR_BROWSER_AGENT', 'GECKO');
        } elseif ($isMozilla) {
            $this->set('PMA_USR_BROWSER_VER', $mozillaVersion[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        } else {
            $this->set('PMA_USR_BROWSER_VER', 0);
            $this->set('PMA_USR_BROWSER_AGENT', 'OTHER');
        }
    }

    /**
     * Whether GD2 is present
     */
    public function checkGd2(): void
    {
        if ($this->get('GD2Available') === 'yes') {
            $this->set('PMA_IS_GD2', 1);

            return;
        }

        if ($this->get('GD2Available') === 'no') {
            $this->set('PMA_IS_GD2', 0);

            return;
        }

        if (! function_exists('imagecreatetruecolor')) {
            $this->set('PMA_IS_GD2', 0);

            return;
        }

        if (function_exists('gd_info')) {
            $gdInfo = gd_info();
            if (str_contains($gdInfo['GD Version'], '2.')) {
                $this->set('PMA_IS_GD2', 1);

                return;
            }
        }

        $this->set('PMA_IS_GD2', 0);
    }

    /**
     * Whether the os php is running on is windows or not
     */
    public function checkWebServerOs(): void
    {
        // Default to Unix or Equiv
        $this->set('PMA_IS_WINDOWS', false);
        // If PHP_OS is defined then continue
        if (! defined('PHP_OS')) {
            return;
        }

        if (stripos(PHP_OS, 'win') !== false && stripos(PHP_OS, 'darwin') === false) {
            // Is it some version of Windows
            $this->set('PMA_IS_WINDOWS', true);
        } elseif (stripos(PHP_OS, 'OS/2') !== false) {
            // Is it OS/2 (No file permissions like Windows)
            $this->set('PMA_IS_WINDOWS', true);
        }
    }

    /**
     * loads configuration from $source, usually the config file
     * should be called on object creation
     *
     * @param string|null $source config file
     *
     * @throws ConfigException
     */
    public function load(string|null $source = null): bool
    {
        if ($source !== null) {
            $this->setSource($source);
        }

        if (! $this->checkConfigSource()) {
            return false;
        }

        /** @var mixed $cfg */
        $cfg = [];

        /**
         * Parses the configuration file, we throw away any errors or
         * output.
         */
        $canUseErrorReporting = function_exists('error_reporting');
        $oldErrorReporting = null;
        if ($canUseErrorReporting) {
            $oldErrorReporting = error_reporting(0);
        }

        ob_start();
        try {
            /** @psalm-suppress UnresolvableInclude */
            $evalResult = include $this->getSource();
        } catch (Throwable) {
            throw new ConfigException('Failed to load phpMyAdmin configuration.');
        }

        ob_end_clean();

        if ($canUseErrorReporting) {
            error_reporting($oldErrorReporting);
        }

        if ($evalResult === false) {
            $this->errorConfigFile = true;
        } else {
            $this->errorConfigFile = false;
            $this->sourceMtime = (int) filemtime($this->getSource());
        }

        if (is_array($cfg)) {
            $this->config = new Settings($cfg);
        }

        $this->settings = array_replace_recursive($this->settings, $this->config->asArray());

        return true;
    }

    /**
     * Sets the connection collation
     */
    private function setConnectionCollation(): void
    {
        $collationConnection = $this->get('DefaultConnectionCollation');
        $dbi = DatabaseInterface::getInstance();
        if (empty($collationConnection) || $collationConnection === $dbi->getDefaultCollation()) {
            return;
        }

        $dbi->setCollation($collationConnection);
    }

    /**
     * Loads user preferences and merges them with current config
     * must be called after control connection has been established
     */
    public function loadUserPreferences(ThemeManager $themeManager, bool $isMinimumCommon = false): void
    {
        $cacheKey = 'server_' . Current::$server;
        if (Current::$server > 0 && ! $isMinimumCommon) {
            // cache user preferences, use database only when needed
            if (
                ! isset($_SESSION['cache'][$cacheKey]['userprefs'])
                || $_SESSION['cache'][$cacheKey]['config_mtime'] < $this->sourceMtime
            ) {
                $dbi = DatabaseInterface::getInstance();
                $userPreferences = new UserPreferences($dbi, new Relation($dbi), new Template());
                $prefs = $userPreferences->load();
                $_SESSION['cache'][$cacheKey]['userprefs'] = $userPreferences->apply($prefs['config_data']);
                $_SESSION['cache'][$cacheKey]['userprefs_mtime'] = $prefs['mtime'];
                $_SESSION['cache'][$cacheKey]['userprefs_type'] = $prefs['type'];
                $_SESSION['cache'][$cacheKey]['config_mtime'] = $this->sourceMtime;
            }
        } elseif (Current::$server === 0 || ! isset($_SESSION['cache'][$cacheKey]['userprefs'])) {
            $this->set('user_preferences', false);

            return;
        }

        $configData = $_SESSION['cache'][$cacheKey]['userprefs'];
        // type is 'db' or 'session'
        $this->set('user_preferences', $_SESSION['cache'][$cacheKey]['userprefs_type']);
        $this->set('user_preferences_mtime', $_SESSION['cache'][$cacheKey]['userprefs_mtime']);

        if (isset($configData['Server']) && is_array($configData['Server'])) {
            $serverConfig = array_replace_recursive($this->selectedServer, $configData['Server']);
            $this->selectedServer = (new Server($serverConfig))->asArray();
        }

        // load config array
        $this->settings = array_replace_recursive($this->settings, $configData);
        $this->config = new Settings($this->settings);

        if ($isMinimumCommon) {
            return;
        }

        // settings below start really working on next page load, but
        // changes are made only in index.php so everything is set when
        // in frames

        // save theme
        if ($themeManager->getThemeCookie() || isset($_REQUEST['set_theme'])) {
            if (
                (! isset($configData['ThemeDefault'])
                && $themeManager->theme->getId() !== 'original')
                || isset($configData['ThemeDefault'])
                && $configData['ThemeDefault'] != $themeManager->theme->getId()
            ) {
                $this->setUserValue(
                    null,
                    'ThemeDefault',
                    $themeManager->theme->getId(),
                    'original',
                );
            }
        } elseif (
            $this->settings['ThemeDefault'] != $themeManager->theme->getId()
            && $themeManager->checkTheme($this->settings['ThemeDefault'])
        ) {
            // no cookie - read default from settings
            $themeManager->setActiveTheme($this->settings['ThemeDefault']);
            $themeManager->setThemeCookie();
        }

        // save language
        if ($this->issetCookie('pma_lang') || isset($_POST['lang'])) {
            if (
                (! isset($configData['lang'])
                && $GLOBALS['lang'] !== 'en')
                || isset($configData['lang'])
                && $GLOBALS['lang'] != $configData['lang']
            ) {
                $this->setUserValue(null, 'lang', $GLOBALS['lang'], 'en');
            }
        } elseif (isset($configData['lang'])) {
            $languageManager = LanguageManager::getInstance();
            // read language from settings
            $language = $languageManager->getLanguage($configData['lang']);
            if ($language !== false) {
                $languageManager->activate($language);
                $this->setCookie('pma_lang', $language->getCode());
            }
        }

        // set connection collation
        $this->setConnectionCollation();
    }

    /**
     * Sets config value which is stored in user preferences (if available)
     * or in a cookie.
     *
     * If user preferences are not yet initialized, option is applied to
     * global config and added to a update queue, which is processed
     * by {@link loadUserPreferences()}
     *
     * @param string|null $cookieName   can be null
     * @param string      $cfgPath      configuration path
     * @param mixed       $newCfgValue  new value
     * @param string|null $defaultValue default value
     */
    public function setUserValue(
        string|null $cookieName,
        string $cfgPath,
        mixed $newCfgValue,
        string|null $defaultValue = null,
    ): true|Message {
        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences($dbi, new Relation($dbi), new Template());
        $result = true;
        // use permanent user preferences if possible
        $prefsType = $this->get('user_preferences');
        if ($prefsType) {
            if ($defaultValue === null) {
                $defaultValue = Core::arrayRead($cfgPath, $this->default);
            }

            $result = $userPreferences->persistOption($cfgPath, $newCfgValue, $defaultValue);
        }

        if ($prefsType !== 'db' && $cookieName) {
            // fall back to cookies
            if ($defaultValue === null) {
                $defaultValue = Core::arrayRead($cfgPath, $this->settings);
            }

            $this->setCookie($cookieName, (string) $newCfgValue, $defaultValue);
        }

        Core::arrayWrite($cfgPath, $this->settings, $newCfgValue);

        return $result;
    }

    /**
     * Reads value stored by {@link setUserValue()}
     *
     * @param string $cookieName cookie name
     * @param mixed  $cfgValue   config value
     */
    public function getUserValue(string $cookieName, mixed $cfgValue): mixed
    {
        $cookieExists = ! empty($this->getCookie($cookieName));
        $prefsType = $this->get('user_preferences');
        if ($prefsType === 'db') {
            // permanent user preferences value exists, remove cookie
            if ($cookieExists) {
                $this->removeCookie($cookieName);
            }
        } elseif ($cookieExists) {
            return $this->getCookie($cookieName);
        }

        // return value from $cfg array
        return $cfgValue;
    }

    /**
     * set source
     *
     * @param string $source source
     */
    public function setSource(string $source): void
    {
        $this->source = trim($source);
    }

    /** @throws ConfigException */
    public function checkConfigSource(): bool
    {
        if ($this->getSource() === '') {
            // no configuration file set at all
            return false;
        }

        if (! @file_exists($this->getSource())) {
            $this->sourceMtime = 0;

            return false;
        }

        if (! @is_readable($this->getSource())) {
            // manually check if file is readable
            // might be bug #3059806 Supporting running from CIFS/Samba shares

            $contents = false;
            $handle = @fopen($this->getSource(), 'r');
            if ($handle !== false) {
                $contents = @fread($handle, 1); // reading 1 byte is enough to test
                fclose($handle);
            }

            if ($contents === false) {
                $this->sourceMtime = 0;

                throw new ConfigException(sprintf(
                    function_exists('__')
                        ? __('Existing configuration file (%s) is not readable.')
                        : 'Existing configuration file (%s) is not readable.',
                    $this->getSource(),
                ));
            }
        }

        return true;
    }

    /**
     * verifies the permissions on config file (if asked by configuration)
     * (must be called after config.inc.php has been merged)
     *
     * @throws ConfigException
     */
    public function checkPermissions(): void
    {
        // Check for permissions (on platforms that support it):
        if (! $this->get('CheckConfigurationPermissions') || ! @file_exists($this->getSource())) {
            return;
        }

        $perms = @fileperms($this->getSource());
        if ($perms === false || ! ($perms & 2)) {
            return;
        }

        // This check is normally done after loading configuration
        $this->checkWebServerOs();
        if ($this->get('PMA_IS_WINDOWS') === true) {
            return;
        }

        $this->sourceMtime = 0;

        throw new ConfigException(__('Wrong permissions on configuration file, should not be world writable!'));
    }

    /**
     * Checks for errors (must be called after config.inc.php has been merged)
     *
     * @throws ConfigException
     */
    public function checkErrors(): void
    {
        if (! $this->errorConfigFile) {
            return;
        }

        $error = '[strong]' . __('Failed to read configuration file!') . '[/strong]'
            . '[br][br]'
            . __('This usually means there is a syntax error in it.');

        throw new ConfigException(Sanitize::convertBBCode($error));
    }

    /**
     * returns specific config setting
     *
     * @param string $setting config setting
     *
     * @return mixed|null value
     */
    public function get(string $setting): mixed
    {
        return $this->settings[$setting] ?? null;
    }

    /**
     * sets configuration variable
     *
     * @param string $setting configuration option
     * @param mixed  $value   new value for configuration option
     */
    public function set(string $setting, mixed $value): void
    {
        if (isset($this->settings[$setting]) && $this->settings[$setting] === $value) {
            return;
        }

        $this->settings[$setting] = $value;
        $this->config = new Settings($this->settings);
    }

    /**
     * returns source for current config
     *
     * @return string  config source
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * checks if upload is enabled
     */
    public function checkUpload(): void
    {
        if (! ini_get('file_uploads')) {
            $this->set('enable_upload', false);

            return;
        }

        $this->set('enable_upload', true);
        // if set "php_admin_value file_uploads Off" in httpd.conf
        // ini_get() also returns the string "Off" in this case:
        if (strtolower(ini_get('file_uploads')) !== 'off') {
            return;
        }

        $this->set('enable_upload', false);
    }

    /**
     * Maximum upload size as limited by PHP
     * Used with permission from Moodle (https://moodle.org/) by Martin Dougiamas
     *
     * this section generates max_upload_size in bytes
     */
    public function checkUploadSize(): void
    {
        $fileSize = ini_get('upload_max_filesize');

        if (! $fileSize) {
            $fileSize = '5M';
        }

        $size = Core::getRealSize($fileSize);
        $postSize = ini_get('post_max_size');

        if ($postSize) {
            $size = min($size, Core::getRealSize($postSize));
        }

        $this->set('max_upload_size', $size);
    }

    /**
     * Checks if protocol is https
     *
     * This function checks if the https protocol on the active connection.
     */
    public function isHttps(): bool
    {
        /** @var mixed $isHttps */
        $isHttps = $this->get('is_https');
        if (is_bool($isHttps)) {
            return $isHttps;
        }

        $url = $this->get('PmaAbsoluteUri');

        $isHttps = false;
        if (! empty($url) && parse_url($url, PHP_URL_SCHEME) === 'https') {
            $isHttps = true;
        } elseif (strtolower(Core::getEnv('HTTP_SCHEME')) === 'https') {
            $isHttps = true;
        } elseif (strtolower(Core::getEnv('HTTPS')) === 'on') {
            $isHttps = true;
        } elseif (stripos(Core::getEnv('REQUEST_URI'), 'https:') === 0) {
            $isHttps = true;
        } elseif (strtolower(Core::getEnv('HTTP_HTTPS_FROM_LB')) === 'on') {
            // A10 Networks load balancer
            $isHttps = true;
        } elseif (strtolower(Core::getEnv('HTTP_FRONT_END_HTTPS')) === 'on') {
            $isHttps = true;
        } elseif (strtolower(Core::getEnv('HTTP_X_FORWARDED_PROTO')) === 'https') {
            $isHttps = true;
        } elseif (strtolower(Core::getEnv('HTTP_CLOUDFRONT_FORWARDED_PROTO')) === 'https') {
            // Amazon CloudFront, issue #15621
            $isHttps = true;
        } elseif (Util::getProtoFromForwardedHeader(Core::getEnv('HTTP_FORWARDED')) === 'https') {
            // RFC 7239 Forwarded header
            $isHttps = true;
        } elseif (Core::getEnv('SERVER_PORT') == 443) {
            $isHttps = true;
        }

        $this->set('is_https', $isHttps);

        return $isHttps;
    }

    /**
     * Get phpMyAdmin root path
     */
    public function getRootPath(): string
    {
        $url = $this->get('PmaAbsoluteUri');

        if (! empty($url)) {
            $path = parse_url($url, PHP_URL_PATH);
            if (! empty($path)) {
                if (! str_ends_with($path, '/')) {
                    return $path . '/';
                }

                return $path;
            }
        }

        $parsedUrlPath = Routing::getCleanPathInfo();

        $parts = explode('/', $parsedUrlPath);

        /* Remove filename */
        if (str_ends_with($parts[count($parts) - 1], '.php')) {
            $parts = array_slice($parts, 0, count($parts) - 1);
        }

        /* Remove extra path from javascript calls */
        if (defined('PMA_PATH_TO_BASEDIR')) {
            $parts = array_slice($parts, 0, count($parts) - 1);
        }

        // Add one more part to make the path end in slash unless it already ends with slash
        if (count($parts) < 2 || $parts[array_key_last($parts)] !== '') {
            $parts[] = '';
        }

        return implode('/', $parts);
    }

    /**
     * removes cookie
     *
     * @param string $cookieName name of cookie to remove
     */
    public function removeCookie(string $cookieName): bool
    {
        $httpCookieName = $this->getCookieName($cookieName);

        if ($this->issetCookie($cookieName)) {
            unset($_COOKIE[$httpCookieName]);
        }

        if (defined('TESTSUITE')) {
            return true;
        }

        return setcookie(
            $httpCookieName,
            '',
            time() - 3600,
            $this->getRootPath(),
            '',
            $this->isHttps,
        );
    }

    /**
     * sets cookie if value is different from current cookie value,
     * or removes if value is equal to default
     *
     * @param string      $cookie   name of cookie to remove
     * @param string      $value    new cookie value
     * @param string|null $default  default value
     * @param int|null    $validity validity of cookie in seconds (default is one month)
     * @param bool        $httponly whether cookie is only for HTTP (and not for scripts)
     */
    public function setCookie(
        string $cookie,
        string $value,
        string|null $default = null,
        int|null $validity = null,
        bool $httponly = true,
    ): bool {
        if ($value !== '' && $value === $default) {
            // default value is used
            if ($this->issetCookie($cookie)) {
                // remove cookie
                return $this->removeCookie($cookie);
            }

            return false;
        }

        if ($value === '' && $this->issetCookie($cookie)) {
            // remove cookie, value is empty
            return $this->removeCookie($cookie);
        }

        $httpCookieName = $this->getCookieName($cookie);

        if (! $this->issetCookie($cookie) || $this->getCookie($cookie) !== $value) {
            // set cookie with new value
            /* Calculate cookie validity */
            if ($validity === null) {
                /* Valid for one month */
                $validity = time() + 2592000;
            } elseif ($validity === 0) {
                /* Valid for session */
                $validity = 0;
            } else {
                $validity += time();
            }

            if (defined('TESTSUITE')) {
                $_COOKIE[$httpCookieName] = $value;

                return true;
            }

            /** @psalm-var 'Lax'|'Strict'|'None' $cookieSameSite */
            $cookieSameSite = $this->get('CookieSameSite');

            $optionalParams = [
                'expires' => $validity,
                'path' => $this->getRootPath(),
                'domain' => '',
                'secure' => $this->isHttps,
                'httponly' => $httponly,
                'samesite' => $cookieSameSite,
            ];

            return setcookie($httpCookieName, $value, $optionalParams);
        }

        // cookie has already $value as value
        return true;
    }

    /**
     * get cookie
     *
     * @param string $cookieName The name of the cookie to get
     *
     * @return mixed|null result of getCookie()
     */
    public function getCookie(string $cookieName): mixed
    {
        return $_COOKIE[$this->getCookieName($cookieName)] ?? null;
    }

    /**
     * Get the real cookie name
     *
     * @param string $cookieName The name of the cookie
     */
    public function getCookieName(string $cookieName): string
    {
        return ($this->isHttps ? '__Secure-' : '') . $cookieName . ($this->isHttps ? '_https' : '');
    }

    /**
     * isset cookie
     *
     * @param string $cookieName The name of the cookie to check
     */
    public function issetCookie(string $cookieName): bool
    {
        return isset($_COOKIE[$this->getCookieName($cookieName)]);
    }

    /**
     * Wrapper for footer/header rendering
     *
     * @param string $filename File to check and render
     * @param string $id       Div ID
     */
    private static function renderCustom(string $filename, string $id): string
    {
        $retval = '';
        if (@file_exists($filename)) {
            $retval .= '<div id="' . $id . '" class="d-print-none">';
            ob_start();
            include $filename;
            $retval .= ob_get_clean();
            $retval .= '</div>';
        }

        return $retval;
    }

    /**
     * Renders user configured footer
     */
    public static function renderFooter(): string
    {
        return self::renderCustom(CUSTOM_FOOTER_FILE, 'pma_footer');
    }

    /**
     * Renders user configured footer
     */
    public static function renderHeader(): string
    {
        return self::renderCustom(CUSTOM_HEADER_FILE, 'pma_header');
    }

    /**
     * Returns temporary dir path
     *
     * @param string $name Directory name
     */
    public function getTempDir(string $name): string|null
    {
        if (isset(self::$tempDir[$name])) {
            return self::$tempDir[$name];
        }

        $path = $this->get('TempDir');
        if (empty($path)) {
            $path = null;
        } else {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
            if (! @is_dir($path)) {
                @mkdir($path, 0770, true);
            }

            if (! @is_dir($path) || ! @is_writable($path)) {
                $path = null;
            }
        }

        self::$tempDir[$name] = $path;

        return $path;
    }

    /**
     * Returns temporary directory
     */
    public function getUploadTempDir(): string|null
    {
        // First try configured temp dir
        // Fallback to PHP upload_tmp_dir
        $dirs = [$this->getTempDir('upload'), ini_get('upload_tmp_dir'), sys_get_temp_dir()];

        foreach ($dirs as $dir) {
            if (! empty($dir) && @is_writable($dir)) {
                return realpath($dir);
            }
        }

        return null;
    }

    /** @return int<0, max> */
    public function selectServer(mixed $serverParamFromRequest): int
    {
        $serverNumber = 0;
        if (is_numeric($serverParamFromRequest)) {
            $serverNumber = (int) $serverParamFromRequest;
            $serverNumber = $serverNumber >= 1 ? $serverNumber : 0;
        } elseif (is_string($serverParamFromRequest) && $serverParamFromRequest !== '') {
            /** Lookup server by name (see FAQ 4.8) */
            foreach ($this->config->Servers as $i => $server) {
                if ($server->host === $serverParamFromRequest || $server->verbose === $serverParamFromRequest) {
                    $serverNumber = $i;
                    break;
                }

                $verboseToLower = mb_strtolower($server->verbose);
                $serverToLower = mb_strtolower($serverParamFromRequest);
                if ($verboseToLower === $serverToLower || md5($verboseToLower) === $serverToLower) {
                    $serverNumber = $i;
                    break;
                }
            }
        }

        /**
         * If no server is selected, make sure that $this->settings['Server'] is empty (so
         * that nothing will work), and skip server authentication.
         * We do NOT exit here, but continue on without logging into any server.
         * This way, the welcome page will still come up (with no server info) and
         * present a choice of servers in the case that there are multiple servers
         * and '$this->settings['ServerDefault'] = 0' is set.
         */
        if (isset($this->config->Servers[$serverNumber])) {
            $this->hasSelectedServer = true;
            $this->selectedServer = $this->config->Servers[$serverNumber]->asArray();
            $this->settings['Server'] = $this->selectedServer;
        } elseif (isset($this->config->Servers[$this->config->ServerDefault])) {
            $this->hasSelectedServer = true;
            $serverNumber = $this->config->ServerDefault;
            $this->selectedServer = $this->config->Servers[$this->config->ServerDefault]->asArray();
            $this->settings['Server'] = $this->selectedServer;
        } else {
            $this->hasSelectedServer = false;
            $serverNumber = 0;
            $this->selectedServer = (new Server())->asArray();
            $this->settings['Server'] = [];
        }

        $this->server = $serverNumber;

        return $this->server;
    }

    /**
     * Return connection parameters for the database server
     */
    public static function getConnectionParams(Server $currentServer, ConnectionType $connectionType): Server
    {
        if ($connectionType !== ConnectionType::ControlUser) {
            if ($currentServer->host !== '' && $currentServer->port !== '') {
                return $currentServer;
            }

            $server = $currentServer->asArray();
            $server['host'] = $server['host'] === '' ? 'localhost' : $server['host'];
            $server['port'] = $server['port'] === '' ? '0' : $server['port'];

            return new Server($server);
        }

        $server = [
            'user' => $currentServer->controlUser,
            'password' => $currentServer->controlPass,
            'host' => $currentServer->controlHost !== '' ? $currentServer->controlHost : $currentServer->host,
            'port' => '0',
            'socket' => null,
            'compress' => null,
            'ssl' => null,
            'ssl_key' => null,
            'ssl_cert' => null,
            'ssl_ca' => null,
            'ssl_ca_path' => null,
            'ssl_ciphers' => null,
            'ssl_verify' => null,
            'hide_connection_errors' => null,
        ];

        // Share the settings if the host is same
        if ($server['host'] === $currentServer->host) {
            $server['port'] = $currentServer->port !== '' ? $currentServer->port : '0';
            $server['socket'] = $currentServer->socket;
            $server['compress'] = $currentServer->compress;
            $server['ssl'] = $currentServer->ssl;
            $server['ssl_key'] = $currentServer->sslKey;
            $server['ssl_cert'] = $currentServer->sslCert;
            $server['ssl_ca'] = $currentServer->sslCa;
            $server['ssl_ca_path'] = $currentServer->sslCaPath;
            $server['ssl_ciphers'] = $currentServer->sslCiphers;
            $server['ssl_verify'] = $currentServer->sslVerify;
            $server['hide_connection_errors'] = $currentServer->hideConnectionErrors;
        }

        // Set configured port
        if ($currentServer->controlPort !== '') {
            $server['port'] = $currentServer->controlPort;
        }

        // Set any configuration with control_ prefix
        $server['socket'] = $currentServer->controlSocket ?? $server['socket'];
        $server['compress'] = $currentServer->controlCompress ?? $server['compress'];
        $server['ssl'] = $currentServer->controlSsl ?? $server['ssl'];
        $server['ssl_key'] = $currentServer->controlSslKey ?? $server['ssl_key'];
        $server['ssl_cert'] = $currentServer->controlSslCert ?? $server['ssl_cert'];
        $server['ssl_ca'] = $currentServer->controlSslCa ?? $server['ssl_ca'];
        $server['ssl_ca_path'] = $currentServer->controlSslCaPath ?? $server['ssl_ca_path'];
        $server['ssl_ciphers'] = $currentServer->controlSslCiphers ?? $server['ssl_ciphers'];
        $server['ssl_verify'] = $currentServer->controlSslVerify ?? $server['ssl_verify'];
        $server['hide_connection_errors'] = $currentServer->controlHideConnectionErrors
            ?? $server['hide_connection_errors'];

        if ($server['host'] === '') {
            $server['host'] = 'localhost';
        }

        return new Server($server);
    }

    /**
     * Get LoginCookieValidity from preferences cache.
     *
     * No generic solution for loading preferences from cache as some settings
     * need to be kept for processing in loadUserPreferences().
     *
     * @see loadUserPreferences()
     */
    public function getLoginCookieValidityFromCache(int $server): void
    {
        $cacheKey = 'server_' . $server;

        if (! isset($_SESSION['cache'][$cacheKey]['userprefs']['LoginCookieValidity'])) {
            return;
        }

        $value = $_SESSION['cache'][$cacheKey]['userprefs']['LoginCookieValidity'];
        $this->set('LoginCookieValidity', $value);
        $this->settings['LoginCookieValidity'] = $value;
    }

    public function getSettings(): Settings
    {
        return $this->config;
    }

    public function hasSelectedServer(): bool
    {
        return $this->hasSelectedServer;
    }

    public function getChangeLogFilePath(): string
    {
        return CHANGELOG_FILE;
    }
}
