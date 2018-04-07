<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin theme manager
 *
 * @package PhpMyAdmin
 */
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
    var $themes = array();

    /**
     * @var string  cookie name
     */
    var $cookie_name = 'pma_theme';

    /**
     * @var boolean
     */
    var $per_server = false;

    /**
     * @var string name of active theme
     */
    var $active_theme = '';

    /**
     * @var Theme Theme active theme
     */
    var $theme = null;

    /**
     * @var string
     */
    var $theme_default;

    /**
     * @const string The name of the fallback theme
     */
    const FALLBACK_THEME = 'pmahomme';

    /**
     * Constructor for Theme Manager class
     *
     * @access public
     */
    public function __construct()
    {
        $this->themes = array();
        $this->theme_default = self::FALLBACK_THEME;
        $this->active_theme = '';

        if (! $this->setThemesPath('./themes/')) {
            return;
        }

        $this->setThemePerServer($GLOBALS['cfg']['ThemePerServer']);

        $this->loadThemes();

        $this->theme = new Theme;

        if (! $this->checkTheme($GLOBALS['cfg']['ThemeDefault'])) {
            trigger_error(
                sprintf(
                    __('Default theme %s not found!'),
                    htmlspecialchars($GLOBALS['cfg']['ThemeDefault'])
                ),
                E_USER_ERROR
            );
            $GLOBALS['cfg']['ThemeDefault'] = false;
        }

        $this->theme_default = $GLOBALS['cfg']['ThemeDefault'];

        // check if user have a theme cookie
        $cookie_theme = $this->getThemeCookie();
        if (! $cookie_theme || ! $this->setActiveTheme($cookie_theme)) {
            if ($GLOBALS['cfg']['ThemeDefault']) {
                // otherwise use default theme
                $this->setActiveTheme($this->theme_default);
            } else {
                // or fallback theme
                $this->setActiveTheme(self::FALLBACK_THEME);
            }
        }
    }

    /**
     * Returns the singleton Response object
     *
     * @return Response object
     */
    public static function getInstance()
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
     * @return string  theme name from cookie
     * @access public
     */
    public function getThemeCookie()
    {
        $name = $this->getThemeCookieName();
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
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
        $this->themes = array();

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
                || ! @is_dir($this->_themes_path . $PMA_Theme)
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

        $theme_preview_path= './themes.php';
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
     * returns Theme object for fall back theme
     *
     * @return Theme fall back theme
     * @access public
     */
    public function getFallBackTheme()
    {
        if (isset($this->themes[self::FALLBACK_THEME])) {
            return $this->themes[self::FALLBACK_THEME];
        }

        return false;
    }

    /**
     * prints css data
     *
     * @return bool
     * @access public
     */
    public function printCss()
    {
        if ($this->theme->loadCss()) {
            return true;
        }

        // if loading css for this theme failed, try default theme css
        $fallback_theme = $this->getFallBackTheme();
        if ($fallback_theme && $fallback_theme->loadCss()) {
            return true;
        }

        return false;
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

        /**
         * load layout file if exists
         */
        if (@file_exists($GLOBALS['PMA_Theme']->getLayoutFile())) {
            include $GLOBALS['PMA_Theme']->getLayoutFile();
        }
    }
}
