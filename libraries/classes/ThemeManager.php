<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin theme manager
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Theme;
use PhpMyAdmin\Url;

/**
 * phpMyAdmin theme manager
 *
 * @package PhpMyAdmin
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
    private static $_instance;

    /**
     * @var string path to theme folder
     * @access protected
     */
    private $_themes_path = './themes/';

    /**
     * @var array available themes
     */
    public $themes = [];

    /**
     * @var string  cookie name
     */
    public $cookie_name = 'pma_theme';

    /**
     * @var boolean
     */
    public $per_server = false;

    /**
     * @var string name of active theme
     */
    public $active_theme = '';

    /**
     * @var Theme Theme active theme
     */
    public $theme = null;

    /**
     * @var string
     */
    public $theme_default;

    /**
     * @const string The name of the fallback theme
     */
    public const FALLBACK_THEME = 'pmahomme';

    /**
     * Constructor for Theme Manager class
     *
     * @access public
     */
    public function __construct()
    {
        $this->themes = [];
        $this->theme_default = self::FALLBACK_THEME;
        $this->active_theme = '';

        if (! $this->setThemesPath('./themes/')) {
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
            $this->theme_default = $GLOBALS['cfg']['ThemeDefault'];
        }

        // check if user have a theme cookie
        $cookie_theme = $this->getThemeCookie();
        if (! $cookie_theme || ! $this->setActiveTheme($cookie_theme)) {
            if ($config_theme_exists) {
                // otherwise use default theme
                $this->setActiveTheme($this->theme_default);
            } else {
                // or fallback theme
                $this->setActiveTheme(self::FALLBACK_THEME);
            }
        }
    }

    /**
     * Returns the singleton ThemeManager object
     *
     * @return ThemeManager The instance
     */
    public static function getInstance(): ThemeManager
    {
        if (empty(self::$_instance)) {
            self::$_instance = new ThemeManager();
        }
        return self::$_instance;
    }

    /**
     * sets path to folder containing the themes
     *
     * @param string $path path to themes folder
     *
     * @access public
     * @return boolean success
     */
    public function setThemesPath($path)
    {
        if (! $this->_checkThemeFolder($path)) {
            return false;
        }

        $this->_themes_path = trim($path);
        return true;
    }

    /**
     * sets if there are different themes per server
     *
     * @param boolean $per_server Whether to enable per server flag
     *
     * @access public
     * @return void
     */
    public function setThemePerServer($per_server)
    {
        $this->per_server  = (bool) $per_server;
    }

    /**
     * Sets active theme
     *
     * @param string $theme theme name
     *
     * @access public
     * @return bool true on success
     */
    public function setActiveTheme($theme = null)
    {
        if (! $this->checkTheme($theme)) {
            trigger_error(
                sprintf(
                    __('Theme %s not found!'),
                    htmlspecialchars($theme)
                ),
                E_USER_ERROR
            );
            return false;
        }

        $this->active_theme = $theme;
        $this->theme = $this->themes[$theme];

        // need to set later
        //$this->setThemeCookie();

        return true;
    }

    /**
     * Returns name for storing theme
     *
     * @return string cookie name
     * @access public
     */
    public function getThemeCookieName()
    {
        // Allow different theme per server
        if (isset($GLOBALS['server']) && $this->per_server) {
            return $this->cookie_name . '-' . $GLOBALS['server'];
        }

        return $this->cookie_name;
    }

    /**
     * returns name of theme stored in the cookie
     *
     * @return string|bool theme name from cookie or false
     * @access public
     */
    public function getThemeCookie()
    {
        /** @var Config $PMA_Config */
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
     * @return bool true
     * @access public
     */
    public function setThemeCookie()
    {
        $GLOBALS['PMA_Config']->setCookie(
            $this->getThemeCookieName(),
            $this->theme->id,
            $this->theme_default
        );
        // force a change of a dummy session variable to avoid problems
        // with the caching of phpmyadmin.css.php
        $GLOBALS['PMA_Config']->set('theme-update', $this->theme->id);
        return true;
    }

    /**
     * Checks whether folder is valid for storing themes
     *
     * @param string $folder Folder name to test
     *
     * @return boolean
     * @access private
     */
    private function _checkThemeFolder($folder)
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
     * @return bool true
     * @access public
     */
    public function loadThemes()
    {
        $this->themes = [];

        if (false === ($handleThemes = opendir($this->_themes_path))) {
            trigger_error(
                'phpMyAdmin-ERROR: cannot open themes folder: '
                . $this->_themes_path,
                E_USER_WARNING
            );
            return false;
        }

        // check for themes directory
        while (false !== ($PMA_Theme = readdir($handleThemes))) {
            // Skip non dirs, . and ..
            if ($PMA_Theme == '.'
                || $PMA_Theme == '..'
                || ! @is_dir(ROOT_PATH . $this->_themes_path . $PMA_Theme)
            ) {
                continue;
            }
            if (array_key_exists($PMA_Theme, $this->themes)) {
                continue;
            }
            $new_theme = Theme::load(
                $this->_themes_path . $PMA_Theme
            );
            if ($new_theme) {
                $new_theme->setId($PMA_Theme);
                $this->themes[$PMA_Theme] = $new_theme;
            }
        } // end get themes
        closedir($handleThemes);

        ksort($this->themes);
        return true;
    }

    /**
     * checks if given theme name is a known theme
     *
     * @param string $theme name fo theme to check for
     *
     * @return bool
     * @access public
     */
    public function checkTheme($theme)
    {
        return array_key_exists($theme, $this->themes);
    }

    /**
     * returns HTML selectbox, with or without form enclosed
     *
     * @param boolean $form whether enclosed by from tags or not
     *
     * @return string
     * @access public
     */
    public function getHtmlSelectBox($form = true)
    {
        $select_box = '';

        if ($form) {
            $select_box .= '<form name="setTheme" method="post"';
            $select_box .= ' action="index.php" class="disableAjax">';
            $select_box .= Url::getHiddenInputs();
        }

        $theme_preview_path = './themes.php';
        $theme_preview_href = '<a href="'
            . $theme_preview_path . '" target="themes" class="themeselect">';
        $select_box .=  $theme_preview_href . __('Theme:') . '</a>' . "\n";

        $select_box .=  '<select name="set_theme" lang="en" dir="ltr"'
            . ' class="autosubmit">';
        foreach ($this->themes as $each_theme_id => $each_theme) {
            $select_box .=  '<option value="' . $each_theme_id . '"';
            if ($this->active_theme === $each_theme_id) {
                $select_box .=  ' selected="selected"';
            }
            $select_box .=  '>' . htmlspecialchars($each_theme->getName())
                . '</option>';
        }
        $select_box .=  '</select>';

        if ($form) {
            $select_box .=  '</form>';
        }

        return $select_box;
    }

    /**
     * Renders the previews for all themes
     *
     * @return string
     * @access public
     */
    public function getPrintPreviews()
    {
        $retval = '';
        foreach ($this->themes as $each_theme) {
            $retval .= $each_theme->getPrintPreview();
        } // end 'open themes'
        return $retval;
    }

    /**
     * Theme initialization
     *
     * @return void
     * @access public
     */
    public static function initializeTheme()
    {
        $tmanager = self::getInstance();

        /**
         * the theme object
         *
         * @global Theme $GLOBALS['PMA_Theme']
         */
        $GLOBALS['PMA_Theme'] = $tmanager->theme;

        // BC
        /**
         * the theme path
         * @global string $GLOBALS['pmaThemePath']
         */
        $GLOBALS['pmaThemePath']    = $GLOBALS['PMA_Theme']->getPath();
        /**
         * the theme image path
         * @global string $GLOBALS['pmaThemeImage']
         */
        $GLOBALS['pmaThemeImage']   = $GLOBALS['PMA_Theme']->getImgPath();
    }
}
