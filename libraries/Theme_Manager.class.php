<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin theme manager
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * phpMyAdmin theme manager
 *
 * @package PhpMyAdmin
 */
class PMA_Theme_Manager
{
    /**
     * @var string path to theme folder
     * @access protected
     */
    private $_themes_path;

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
     * @var PMA_Theme PMA_Theme active theme
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
        $this->init();
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
     * Returns path to folder containing themes
     *
     * @access public
     * @return string theme path
     */
    public function getThemesPath()
    {
        return $this->_themes_path;
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
     * Initialise the class
     *
     * @access public
     * @return void
     */
    public function init()
    {
        $this->themes = array();
        $this->theme_default = self::FALLBACK_THEME;
        $this->active_theme = '';

        if (! $this->setThemesPath($GLOBALS['cfg']['ThemePath'])) {
            return;
        }

        $this->setThemePerServer($GLOBALS['cfg']['ThemePerServer']);

        $this->loadThemes();

        $this->theme = new PMA_Theme;

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
        if (! $this->getThemeCookie()
            || ! $this->setActiveTheme($this->getThemeCookie())
        ) {
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
     * Checks configuration
     *
     * @access public
     * @return void
     */
    public function checkConfig()
    {
        if ($this->_themes_path != trim($GLOBALS['cfg']['ThemePath'])
            || $this->theme_default != $GLOBALS['cfg']['ThemeDefault']
        ) {
            $this->init();
        } else {
            // at least the theme path needs to be checked every time for new
            // themes, as there is no other way at the moment to keep track of
            // new or removed themes
            $this->loadThemes();
        }
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
        } else {
            return $this->cookie_name;
        }
    }

    /**
     * returns name of theme stored in the cookie
     *
     * @return string  theme name from cookie
     * @access public
     */
    public function getThemeCookie()
    {
        if (isset($_COOKIE[$this->getThemeCookieName()])) {
            return $_COOKIE[$this->getThemeCookieName()];
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

        if ($handleThemes = opendir($this->getThemesPath())) {
            // check for themes directory
            while (false !== ($PMA_Theme = readdir($handleThemes))) {
                // Skip non dirs, . and ..
                if ($PMA_Theme == '.'
                    || $PMA_Theme == '..'
                    || ! is_dir($this->getThemesPath() . '/' . $PMA_Theme)
                ) {
                    continue;
                }
                if (array_key_exists($PMA_Theme, $this->themes)) {
                    continue;
                }
                $new_theme = PMA_Theme::load(
                    $this->getThemesPath() . '/' . $PMA_Theme
                );
                if ($new_theme) {
                    $new_theme->setId($PMA_Theme);
                    $this->themes[$PMA_Theme] = $new_theme;
                }
            } // end get themes
            closedir($handleThemes);
        } else {
            trigger_error(
                'phpMyAdmin-ERROR: cannot open themes folder: '
                . $this->getThemesPath(),
                E_USER_WARNING
            );
            return false;
        } // end check for themes directory

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
        if (! array_key_exists($theme, $this->themes)) {
            return false;
        }

        return true;
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
            $select_box .= '<form name="setTheme" method="get"';
            $select_box .= ' action="index.php" class="disableAjax">';
            $select_box .=  PMA_URL_getHiddenInputs();
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
     * enables backward compatibility
     *
     * @return void
     * @access public
     */
    public function makeBc()
    {
        $GLOBALS['theme']           = $this->theme->getId();
        $GLOBALS['pmaThemePath']    = $this->theme->getPath();
        $GLOBALS['pmaThemeImage']   = $this->theme->getImgPath();

        /**
         * load layout file if exists
         */
        if (file_exists($this->theme->getLayoutFile())) {
            include $this->theme->getLayoutFile();
        }
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
     * returns PMA_Theme object for fall back theme
     *
     * @return PMA_Theme fall back theme
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
}
?>
