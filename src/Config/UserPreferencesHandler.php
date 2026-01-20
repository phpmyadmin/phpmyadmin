<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Theme\ThemeManager;

use function array_replace_recursive;
use function is_array;

class UserPreferencesHandler
{
    /** @var ''|'db'|'session' */
    public string $storageType = '';

    /** @var mixed[]   default configuration settings */
    public array $defaultSettings;

    public function __construct(
        private readonly Config $config,
        private readonly DatabaseInterface $dbi,
        private readonly UserPreferences $userPreferences,
        private readonly LanguageManager $languageManager,
        private readonly ThemeManager $themeManager,
    ) {
        $this->defaultSettings = (new Settings([]))->asArray();
    }

    /**
     * Loads user preferences and merges them with current config
     * must be called after control connection has been established
     */
    public function loadUserPreferences(bool $isMinimumCommon = false): void
    {
        $cacheKey = 'server_' . Current::$server;
        if (Current::$server > 0 && ! $isMinimumCommon) {
            // cache user preferences, use database only when needed
            if (
                ! isset($_SESSION['cache'][$cacheKey]['userprefs'])
                || $_SESSION['cache'][$cacheKey]['config_mtime'] < $this->config->sourceMtime
            ) {
                $prefs = $this->userPreferences->load();
                $_SESSION['cache'][$cacheKey]['userprefs'] = $this->userPreferences->apply($prefs['config_data']);
                $_SESSION['cache'][$cacheKey]['userprefs_mtime'] = $prefs['mtime'];
                $_SESSION['cache'][$cacheKey]['userprefs_type'] = $prefs['type'];
                $_SESSION['cache'][$cacheKey]['config_mtime'] = $this->config->sourceMtime;
            }
        } elseif (Current::$server === 0 || ! isset($_SESSION['cache'][$cacheKey]['userprefs'])) {
            $this->storageType = '';

            return;
        }

        $configData = $_SESSION['cache'][$cacheKey]['userprefs'];
        // type is 'db' or 'session'
        $this->storageType = $_SESSION['cache'][$cacheKey]['userprefs_type'];
        $this->config->set('user_preferences_mtime', $_SESSION['cache'][$cacheKey]['userprefs_mtime']);

        if (isset($configData['Server']) && is_array($configData['Server'])) {
            $serverConfig = array_replace_recursive($this->config->selectedServer, $configData['Server']);
            $this->config->selectedServer = (new Server($serverConfig))->asArray();
        }

        // load config array
        $this->config->settings = array_replace_recursive($this->config->settings, $configData);
        $this->config->config = new Settings($this->config->settings);

        if ($isMinimumCommon) {
            return;
        }

        // settings below start really working on next page load, but
        // changes are made only in index.php so everything is set when
        // in frames

        // save theme
        if ($this->themeManager->getThemeCookie() !== '' || isset($_REQUEST['set_theme'])) {
            if (
                (! isset($configData['ThemeDefault'])
                    && $this->themeManager->theme->getId() !== 'original')
                || isset($configData['ThemeDefault'])
                && $configData['ThemeDefault'] !== $this->themeManager->theme->getId()
            ) {
                $this->setUserValue(
                    null,
                    'ThemeDefault',
                    $this->themeManager->theme->getId(),
                    'original',
                );
            }
        } elseif (
            $this->config->config->ThemeDefault !== $this->themeManager->theme->getId()
            && $this->themeManager->themeExists($this->config->config->ThemeDefault)
        ) {
            // no cookie - read default from settings
            $this->themeManager->setActiveTheme($this->config->config->ThemeDefault);
            $this->themeManager->setThemeCookie();
        }

        // save language
        if ($this->config->issetCookie('pma_lang') || isset($_POST['lang'])) {
            if (
                (! isset($configData['lang'])
                    && Current::$lang !== 'en')
                || isset($configData['lang'])
                && Current::$lang !== $configData['lang']
            ) {
                $this->setUserValue(null, 'lang', Current::$lang, 'en');
            }
        } elseif (isset($configData['lang'])) {
            // read language from settings
            $language = $this->languageManager->getLanguage($configData['lang']);
            if ($language !== false) {
                $this->languageManager->activate($language);
                $this->config->setCookie('pma_lang', $language->getCode());
            }
        }

        // set connection collation
        $this->setConnectionCollation();
    }

    /**
     * Get LoginCookieValidity from preferences cache.
     *
     * No generic solution for loading preferences from cache as some settings
     * need to be kept for processing in {@link loadUserPreferences()}.
     */
    public function getLoginCookieValidityFromCache(int $server): void
    {
        $cacheKey = 'server_' . $server;

        if (! isset($_SESSION['cache'][$cacheKey]['userprefs']['LoginCookieValidity'])) {
            return;
        }

        $value = $_SESSION['cache'][$cacheKey]['userprefs']['LoginCookieValidity'];
        $this->config->set('LoginCookieValidity', $value);
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
        $result = true;
        // use permanent user preferences if possible
        if ($this->storageType !== '') {
            if ($defaultValue === null) {
                $defaultValue = Core::arrayRead($cfgPath, $this->defaultSettings);
            }

            $result = $this->userPreferences->persistOption($cfgPath, $newCfgValue, $defaultValue);
        }

        if ($this->storageType !== 'db' && $cookieName) {
            // fall back to cookies
            if ($defaultValue === null) {
                $defaultValue = Core::arrayRead($cfgPath, $this->config->settings);
            }

            $this->config->setCookie($cookieName, (string) $newCfgValue, $defaultValue);
        }

        Core::arrayWrite($cfgPath, $this->config->settings, $newCfgValue);

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
        $cookieExists = ! empty($this->config->getCookie($cookieName));
        if ($this->storageType === 'db') {
            // permanent user preferences value exists, remove cookie
            if ($cookieExists) {
                $this->config->removeCookie($cookieName);
            }
        } elseif ($cookieExists) {
            return $this->config->getCookie($cookieName);
        }

        // return value from $cfg array
        return $cfgValue;
    }

    private function setConnectionCollation(): void
    {
        $collationConnection = $this->config->config->DefaultConnectionCollation;
        if ($collationConnection === '' || $collationConnection === $this->dbi->getDefaultCollation()) {
            return;
        }

        $this->dbi->setCollation($collationConnection);
    }
}
