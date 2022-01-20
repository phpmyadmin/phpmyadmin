<?php
/**
 * phpMyAdmin theme manager
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use const DIRECTORY_SEPARATOR;
use const E_USER_ERROR;
use const E_USER_WARNING;
use function array_key_exists;
use function closedir;
use function htmlspecialchars;
use function is_dir;
use function ksort;
use function opendir;
use function readdir;
use function sprintf;
use function trigger_error;

/**
 * phpMyAdmin theme manager
 */
class ThemeManager
{
    /**
     * ThemeManager instance
     *
     * @access private
     * @static
     * @var ThemeManager
     */
    private static $instance;

    /**
     * @var string file-system path to the theme folder
     * @access protected
     */
    private $themesPath;

    /** @var string path to theme folder as an URL */
    private $themesPathUrl;

    /** @var array available themes */
    public $themes = [];

    /** @var string  cookie name */
    public $cookieName = 'pma_theme';

    /** @var bool */
    public $perServer = false;

    /** @var string name of active theme */
    public $activeTheme = '';

    /** @var Theme Theme active theme */
    public $theme = null;

    /** @var string */
    public $themeDefault;

    /**
     * @const string The name of the fallback theme
     */
    public const FALLBACK_THEME = 'pmahomme';

    public function __construct()
    {
        $this->themes = [];
        $this->themeDefault = self::FALLBACK_THEME;
        $this->activeTheme = '';
        $this->themesPath = self::getThemesFsDir();
        $this->themesPathUrl = self::getThemesDir();

        if (! $this->checkThemeFolder($this->themesPath)) {
            return;
        }

        $this->setThemePerServer($GLOBALS['cfg']['ThemePerServer']);

        $this->loadThemes();

        $this->theme = new Theme();

        $config_theme_exists = true;

        if (! $this->checkTheme($GLOBALS['cfg']['ThemeDefault'])) {
            trigger_error(
                sprintf(
                    __('Default theme %s not found!'),
                    htmlspecialchars($GLOBALS['cfg']['ThemeDefault'])
                ),
                E_USER_ERROR
            );
            $config_theme_exists = false;
        } else {
            $this->themeDefault = $GLOBALS['cfg']['ThemeDefault'];
        }

        // check if user have a theme cookie
        $cookie_theme = $this->getThemeCookie();
        if ($cookie_theme && $this->setActiveTheme($cookie_theme)) {
            return;
        }

        if ($config_theme_exists) {
            // otherwise use default theme
            $this->setActiveTheme($this->themeDefault);
        } else {
            // or fallback theme
            $this->setActiveTheme(self::FALLBACK_THEME);
        }
    }

    /**
     * Returns the singleton ThemeManager object
     *
     * @return ThemeManager The instance
     */
    public static function getInstance(): ThemeManager
    {
        if (empty(self::$instance)) {
            self::$instance = new ThemeManager();
        }

        return self::$instance;
    }

    /**
     * sets if there are different themes per server
     *
     * @param bool $per_server Whether to enable per server flag
     *
     * @access public
     */
    public function setThemePerServer($per_server): void
    {
        $this->perServer = (bool) $per_server;
    }

    /**
     * Sets active theme
     *
     * @param string|null $theme theme name
     *
     * @return bool true on success
     *
     * @access public
     */
    public function setActiveTheme(?string $theme): bool
    {
        if (! $this->checkTheme($theme)) {
            trigger_error(
                sprintf(
                    __('Theme %s not found!'),
                    htmlspecialchars((string) $theme)
                ),
                E_USER_ERROR
            );

            return false;
        }

        $this->activeTheme = $theme;
        $this->theme = $this->themes[$theme];

        // need to set later
        //$this->setThemeCookie();

        return true;
    }

    /**
     * Returns name for storing theme
     *
     * @return string cookie name
     *
     * @access public
     */
    public function getThemeCookieName()
    {
        // Allow different theme per server
        if (isset($GLOBALS['server']) && $this->perServer) {
            return $this->cookieName . '-' . $GLOBALS['server'];
        }

        return $this->cookieName;
    }

    /**
     * returns name of theme stored in the cookie
     *
     * @return string|false theme name from cookie or false
     *
     * @access public
     */
    public function getThemeCookie()
    {
        global $PMA_Config;

        $name = $this->getThemeCookieName();
        if ($PMA_Config->issetCookie($name)) {
            return $PMA_Config->getCookie($name);
        }

        return false;
    }

    /**
     * save theme in cookie
     *
     * @return true
     *
     * @access public
     */
    public function setThemeCookie(): bool
    {
        $themeId = $this->theme !== null ? (string) $this->theme->id : '';
        $GLOBALS['PMA_Config']->setCookie(
            $this->getThemeCookieName(),
            $themeId,
            $this->themeDefault
        );
        // force a change of a dummy session variable to avoid problems
        // with the caching of phpmyadmin.css.php
        $GLOBALS['PMA_Config']->set('theme-update', $themeId);

        return true;
    }

    /**
     * Checks whether folder is valid for storing themes
     *
     * @param string $folder Folder name to test
     *
     * @access private
     */
    private function checkThemeFolder($folder): bool
    {
        if (! is_dir($folder)) {
            trigger_error(
                sprintf(
                    __('Theme path not found for theme %s!'),
                    htmlspecialchars($folder)
                ),
                E_USER_ERROR
            );

            return false;
        }

        return true;
    }

    /**
     * read all themes
     *
     * @access public
     */
    public function loadThemes(): bool
    {
        $this->themes = [];
        $handleThemes = opendir($this->themesPath);

        if ($handleThemes === false) {
            trigger_error(
                'phpMyAdmin-ERROR: cannot open themes folder: '
                . $this->themesPath,
                E_USER_WARNING
            );

            return false;
        }

        // check for themes directory
        while (($PMA_Theme = readdir($handleThemes)) !== false) {
            // Skip non dirs, . and ..
            if ($PMA_Theme === '.'
                || $PMA_Theme === '..'
                || ! @is_dir($this->themesPath . $PMA_Theme)
            ) {
                continue;
            }
            if (array_key_exists($PMA_Theme, $this->themes)) {
                continue;
            }
            $new_theme = Theme::load(
                $this->themesPathUrl . $PMA_Theme,
                $this->themesPath . $PMA_Theme . DIRECTORY_SEPARATOR
            );
            if (! $new_theme) {
                continue;
            }

            $new_theme->setId($PMA_Theme);
            $this->themes[$PMA_Theme] = $new_theme;
        }
        closedir($handleThemes);

        ksort($this->themes);

        return true;
    }

    /**
     * checks if given theme name is a known theme
     *
     * @param string|null $theme name fo theme to check for
     *
     * @access public
     */
    public function checkTheme(?string $theme): bool
    {
        return array_key_exists($theme ?? '', $this->themes);
    }

    /**
     * returns HTML selectbox
     *
     * @access public
     */
    public function getHtmlSelectBox(): string
    {
        $select_box = '';

        $select_box .= '<form name="setTheme" method="post"';
        $select_box .= ' action="index.php?route=/set-theme" class="disableAjax">';
        $select_box .= Url::getHiddenInputs();

        $theme_preview_href = '<a href="'
            . Url::getFromRoute('/themes') . '" target="themes" class="themeselect">';
        $select_box .=  $theme_preview_href . __('Theme:') . '</a>' . "\n";

        $select_box .=  '<select name="set_theme" lang="en" dir="ltr"'
            . ' class="autosubmit">';
        foreach ($this->themes as $each_theme_id => $each_theme) {
            $select_box .=  '<option value="' . $each_theme_id . '"';
            if ($this->activeTheme === $each_theme_id) {
                $select_box .=  ' selected="selected"';
            }
            $select_box .=  '>' . htmlspecialchars($each_theme->getName())
                . '</option>';
        }
        $select_box .= '</select>';
        $select_box .= '</form>';

        return $select_box;
    }

    /**
     * Renders the previews for all themes
     *
     * @access public
     */
    public function getPrintPreviews(): string
    {
        $retval = '';
        foreach ($this->themes as $each_theme) {
            $retval .= $each_theme->getPrintPreview();
        }

        return $retval;
    }

    public static function initializeTheme(): ?Theme
    {
        $themeManager = self::getInstance();

        return $themeManager->theme;
    }

    /**
     * Return the themes directory with a trailing slash
     */
    public static function getThemesFsDir(): string
    {
        return ROOT_PATH . 'themes' . DIRECTORY_SEPARATOR;
    }

    /**
     * Return the themes directory with a trailing slash as a relative public path
     */
    public static function getThemesDir(): string
    {
        return './themes/';// This is an URL
    }
}
