<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * phpMyAdmin Theme Manager
 * 2004-05-20: Michael Keck <mail_at_michaelkeck_dot_de>
 *
 * The theme manager checks the directory /themes/ for subdirectories
 * which are the themes.
 * If you're building a new theme for PMA, your theme should include
 * make a folder theme_name/ in the directory /themes which includes
 * a subdirectory css/.
 * In the css-directory you should (if you want) edit the follow files:
 *    - theme_left.css.php      // includes css-styles for the left frame
 *    - theme_right.css.php     // includes css-styles for the main frame
 *    - theme_print.css.php     // includes css-styles for printing
 *
 * If you want to use default themes for left, right or print
 * so you need not to build the css-file and PMA will use its own css.
 * If you want to use own images for your theme, you should make all
 * images (buttons, symbols, arrows) wich are included in the default
 * images directory PMA and store them into the subdirectory /img/ of
 * your theme.
 * Note:
 *     The images must be named as in the default images directory of
 *     PMA and they must have the same size in pixels.
 *     You can only use own images, if you've edit own css files.
 */

/**
 * We need some elements of the superglobal $_SERVER array.
 */
require_once('./libraries/grab_globals.lib.php');
global $PHP_SELF;
/**
 * theme manager
 */
$PMA_ThemeDefault = FALSE;
$PMA_ThemeAvailable = FALSE;
if ($cfg['ThemeManager']){
    $PMA_ThemeAvailable = TRUE;
}

if ($PMA_ThemeAvailable == TRUE){ // check after default theme
    $tmp_path_default = $cfg['ThemePath'] . '/' .$cfg['ThemeDefault'];
    if (isset($cfg['ThemeDefault']) && is_dir($tmp_path_default)){
        $PMA_ThemeDefault = TRUE;
    }
} // end check default theme

if ($PMA_ThemeAvailable == TRUE) { // themeManager is available
    if ($handleThemes = opendir($cfg['ThemePath'])) { // check for themes directory
        while (FALSE !== ($PMA_Theme = readdir($handleThemes))) { // get themes
            if ($PMA_Theme != "." && $PMA_Theme != ".." && $PMA_Theme != 'CVS') { // file check
                if (@is_dir($cfg['ThemePath'].'/'.$PMA_Theme)) { // check the theme
                    // check for theme requires/name
                    unset($theme_name, $theme_generation, $theme_version);
                    @include($cfg['ThemePath'] . '/' . $PMA_Theme . '/info.inc.php');

                    // did it set correctly?
                    if (!isset($theme_name, $theme_version, $theme_generation))
                        continue; // invalid theme

                    if ($theme_generation != PMA_THEME_GENERATION)
                        continue; // different generation

                    if ($theme_version < PMA_THEME_VERSION)
                        continue; // too old version

                    $available_themes_choices[]=$PMA_Theme;
                    $available_themes_choices_names[$PMA_Theme] = $theme_name;
                } // end check the theme
            } // end file check
        } // end get themes
    } // end check for themes directory
    closedir($handleThemes);
} // end themeManger

if (!isset($pma_uri_parts)) { // cookie-setup if needed
    $pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
    $cookie_path   = substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/'));
    $is_https      = (isset($pma_uri_parts['scheme']) && $pma_uri_parts['scheme'] == 'https') ? 1 : 0;
} // end cookie setup

if (isset($set_theme)) { // if user submit a theme
    setcookie('pma_theme', $set_theme, time() + 60*60*24*30, $cookie_path, '', $is_https);
} else { // else check if user have a theme cookie
    if (!isset($_COOKIE['pma_theme']) || empty($_COOKIE['pma_theme'])) {
        if ($PMA_ThemeDefault == TRUE) {
            if (basename($PHP_SELF) == 'index.php') {
                setcookie('pma_theme', $cfg['ThemeDefault'], time() + 60*60*24*30, $cookie_path, '', $is_https);
            }
            $pmaTheme=$cfg['ThemeDefault'];
        }else{
            if (basename($PHP_SELF) == 'index.php') {
                setcookie('pma_theme', 'original', time() + 60*60*24*30, $cookie_path, '', $is_https);
            }
            $pmaTheme='original';
        }
    } else {
        $pmaTheme=$_COOKIE['pma_theme'];
        if (basename($PHP_SELF) == 'index.php') {
            setcookie('pma_theme', $pmaTheme, time() + 60*60*24*30, $cookie_path, '', $is_https);
        }
    }
} // end if
?>
