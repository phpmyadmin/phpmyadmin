<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('libraries/Theme.class.php');

class PMA_Theme_Manager {

    /**
     * @var string path to theme folder
     */
    var $themes_path;

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
     * @var object PMA_Theme active theme
     */
    var $theme = NULL;

    function __construct() {
        $this->themes_path = trim( $GLOBALS['cfg']['ThemePath'] ) ;
        $this->per_server  = (bool) $GLOBALS['cfg']['ThemePerServer'];
        $this->theme = new PMA_Theme;

        if ( ! $this->_checkThemeFolder( $this->themes_path ) ) {
            return;
        }

        $this->loadThemes( $this->themes_path );

        if ( ! $this->checkTheme( $GLOBALS['cfg']['ThemeDefault'] ) ) {
            $GLOBALS['PMA_errors'][] = sprintf( $GLOBALS['strThemeDefaultNotFound'],
                $GLOBALS['cfg']['ThemeDefault'] );
            trigger_error(
                sprintf( $GLOBALS['strThemeDefaultNotFound'],
                    $GLOBALS['cfg']['ThemeDefault'] ),
                E_USER_WARNING );
            $GLOBALS['cfg']['ThemeDefault'] = false;
        }

        $this->theme_default = $GLOBALS['cfg']['ThemeDefault'];

        // check if user have a theme cookie
        if ( ! $this->getThemeCookie()
          || ! $this->setActiveTheme( $this->getThemeCookie() ) ) {
            if ( $GLOBALS['cfg']['ThemeDefault'] ) {
                $this->setActiveTheme( $GLOBALS['cfg']['ThemeDefault'] );
            } else {
                $this->setActiveTheme( 'original' );
            }
        }
    }

    function setActiveTheme( $theme = NULL ) {
        if ( ! $this->checkTheme( $theme ) ) {
            $GLOBALS['PMA_errors'][] = sprintf( $GLOBALS['strThemeNotFound'],
                $theme );
            trigger_error(
                sprintf( $GLOBALS['strThemeNotFound'], $theme ),
                E_USER_WARNING );
            return false;
        }

        $this->active_theme = $theme;
        $this->theme = $this->themes[$theme];

        // need to set later
        //$this->setThemeCookie();

        return true;
    }

    /**
     * @return  string  cookie name
     */
    function getThemeCookieName() {
        // Allow different theme per server
        if ( isset( $GLOBALS['server'] ) && $this->per_server ) {
            return $this->cookie_name . '-' . $GLOBALS['server'];
        } else {
            return $this->cookie_name;
        }
    }

    /**
     * returns name of theme stored in the cookie
     * @return  string  theme name from cookie
     */
    function getThemeCookie() {
        if ( isset( $_COOKIE[$this->getThemeCookieName()] ) ) {
            return $_COOKIE[$this->getThemeCookieName()];
        }

        return false;
    }

    /**
     * save theme in cookie
     *
     * @uses    PMA_setCookie();
     * @uses    PMA_Theme_Manager::getThemeCookieName()
     * @uses    PMA_Theme_Manager::$theme
     * @uses    PMA_Theme_Manager::$theme_default
     * @uses    PMA_Theme::getId()
     */
    function setThemeCookie() {
        PMA_setCookie( $this->getThemeCookieName(), $this->theme->id,
            $this->theme_default );
        return true;
    }

    /**
     * old PHP 4 constructor
     */
    function PMA_Theme_Manager() {
        $this->__construct();
    }

    /**
     * @private
     * @param   string $folder
     * @return  boolean
     */
    /*private*/ function _checkThemeFolder( $folder ) {
        if ( ! is_dir( $folder ) ) {
            $GLOBALS['PMA_errors'][] =
                sprintf( $GLOBALS['strThemePathNotFound'],
                    $folder );
            trigger_error(
                sprintf( $GLOBALS['strThemePathNotFound'],
                    $folder ),
                E_USER_WARNING );
            return false;
        }

        return true;
    }

    /**
     * read all themes
     */
    function loadThemes( $folder ) {
        $this->themes = array();

        if ( $handleThemes = opendir( $folder ) ) {
            // check for themes directory
            while (FALSE !== ($PMA_Theme = readdir($handleThemes))) {
                $new_theme = PMA_Theme::load( $folder . '/' . $PMA_Theme );
                if ( $new_theme ) {
                    $new_theme->setId( $PMA_Theme );
                    $this->themes[$PMA_Theme] = $new_theme;
                }
            } // end get themes
            closedir( $handleThemes );
        } else {
            return false;
        } // end check for themes directory

        ksort( $this->themes );
    }

    function checkTheme( $theme ) {
        if ( ! array_key_exists( $theme, $this->themes ) ) {
            return false;
        }

        return true;
    }

    function getHtmlSelectBox( $form = true ) {
        $select_box = '';

        if ( $form ) {
            $select_box .= '<form name="setTheme" method="post" action="index.php"'
                .' target="_parent">';
            $select_box .=  PMA_generate_common_hidden_inputs();
        }

        $theme_selected = FALSE;
        $theme_preview_path= './themes.php';
        $theme_preview_href = '<a href="' . $theme_preview_path . '" target="themes" onclick="'
                            . "window.open('" . $theme_preview_path . "','themes','left=10,top=20,width=510,height=350,scrollbars=yes,status=yes,resizable=yes');"
                            . '">';
        $select_box .=  $theme_preview_href . $GLOBALS['strTheme'] . '</a>:' . "\n";

        $select_box .=  '<select name="set_theme" xml:lang="en" dir="ltr"'
            .' onchange="this.form.submit();" >';
        foreach ( $this->themes as $each_theme_id => $each_theme ) {
            $select_box .=  '<option value="' . $each_theme_id . '"';
            if ( $this->active_theme === $each_theme_id ) {
                $select_box .=  ' selected="selected"';
            }
            $select_box .=  '>' . htmlspecialchars( $each_theme->getName() ) . '</option>';
        }
        $select_box .=  '</select>';

        if ( $form ) {
            $select_box .=  '<noscript><input type="submit" value="' . $GLOBALS['strGo'] . '" /></noscript>';
            $select_box .=  '</form>';
        }

        return $select_box;
    }

    /**
     * enables backward compatibility
     */
    function makeBc() {
        $GLOBALS['theme']           = $this->theme->getId();
        $GLOBALS['pmaThemePath']    = $this->theme->getPath();
        $GLOBALS['pmaThemeImage']   = $this->theme->getImgPath();

        /**
         * load layout file if exists
         */
        if ( @file_exists( $GLOBALS['pmaThemePath'] . 'layout.inc.php' ) ) {
            include( $GLOBALS['pmaThemePath'] . 'layout.inc.php' );
        }


    }

    /**
     * prints out preview for every theme
     *
     * @uses    $this->themes
     * @uses    PMA_Theme::printPreview()
     */
    function printPreviews() {
        foreach ( $this->themes as $each_theme ) {

            $each_theme->printPreview();
        } // end 'open themes'
    }
}
?>