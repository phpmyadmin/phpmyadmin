<?php
/* get some globals */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');

/* Theme Select */
$path_to_themes = $cfg['ThemePath'] . '/';
require_once('./libraries/select_theme.lib.php');

/* set language and charset */
require_once('./libraries/header_http.inc.php');

/* Gets the font sizes to use */
PMA_setFontSizes();
/* remove vertical scroll bar bug in ie */
echo "<?xml version=\"1.0\" encoding=\"" . $GLOBALS['charset'] . "\"?".">";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">

<head>
<title>phpMyAdmin <?php echo PMA_VERSION; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
<meta http-equiv="imagetoolbar" content="no">
<script language="JavaScript" type="text/javascript">
<!--
    /* added 2004-06-10 by Michael Keck
     *       we need this for Backwards-Compatibility and resolving problems
     *       with non DOM browsers, which may have problems with css 2 (like NC 4)
    */
    var isDOM      = (typeof(document.getElementsByTagName) != 'undefined'
                      && typeof(document.createElement) != 'undefined')
                   ? 1 : 0;
    var isIE4      = (typeof(document.all) != 'undefined'
                      && parseInt(navigator.appVersion) >= 4)
                   ? 1 : 0;
    var isNS4      = (typeof(document.layers) != 'undefined')
                   ? 1 : 0;
    var capable    = (isDOM || isIE4 || isNS4)
                   ? 1 : 0;
    // Uggly fix for Opera and Konqueror 2.2 that are half DOM compliant
    if (capable) {
        if (typeof(window.opera) != 'undefined') {
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if ((browserName.indexOf('konqueror 7') == 0)) {
                capable = 0;
            }
        } else if (typeof(navigator.userAgent) != 'undefined') {
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if ((browserName.indexOf('konqueror') > 0) && (browserName.indexOf('konqueror/3') == 0)) {
                capable = 0;
            }
        } // end if... else if...
    } // end if
    document.writeln('<link rel="stylesheet" type="text/css" href="<?php echo defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : './'; ?>css/phpmyadmin.css.php?lang=<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>&amp;js_frame=right&amp;js_isDOM=' + isDOM + '" />');
//-->
</script>
<noscript>
    <link rel="stylesheet" type="text/css" href="<?php echo defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : './'; ?>css/phpmyadmin.css.php?lang=<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>&amp;js_frame=right" />
</noscript>
<script language="JavaScript">
<!--
    function takeThis(what){
        if (window.opener && window.opener.document.forms['setTheme'].elements['set_theme']) {
            window.opener.document.forms['setTheme'].elements['set_theme'].value = what;
            window.opener.document.forms['setTheme'].submit();
            self.close();
        } else {
            alert('<?php echo sprintf($strNoThemeSupport, $cfg['ThemePath']); ?>');
            self.close();
        }
    }
//-->
</script>
</head>

<body bgcolor="<?php echo $cfg['RightBgColor']; ?>">
    <table border="0" align="center" cellpadding="3" cellspacing="1">
        <tr>
            <th class="tblHeaders"><b>phpMyAdmin - <?php echo $strTheme; ?></b></th>
        </tr>
        <tr>
            <td><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td>
        </tr>
        <?php
if ($handleThemes = opendir($path_to_themes)) { // open themes
    while (false !== ($PMA_Theme = readdir($handleThemes))) {  // get screens
        if ($PMA_Theme != "." && $PMA_Theme != "..") {
            $screen_directory = $path_to_themes . $PMA_Theme;

            // check for theme requires/name
            unset($theme_name, $theme_generation, $theme_version);
            @include($path_to_themes . $PMA_Theme . '/info.inc.php');

            // did it set correctly?
            if (!isset($theme_name, $theme_generation, $theme_version))
                continue; // invalid theme

            if ($theme_generation != PMA_THEME_GENERATION)
                continue; // different generation

            if ($theme_version < PMA_THEME_VERSION)
                continue; // too old version


            if (is_dir($screen_directory) && @file_exists($screen_directory.'/screen.png')) { // if screen exists then output
        ?>
        <tr>
            <th align="left">
                <?php
                echo '<b>' . htmlspecialchars($theme_name) . '</b>';
                ?>
            </th>
        </tr>
        <tr>
            <td align="center" bgcolor="<?php echo $cfg['BgcolorOne']; ?>" class="navNorm">
                <script language="JavaScript">
                <!--
                    document.write('<a href="#top" onclick="takeThis(\'<?php echo $PMA_Theme; ?>\'); return false;">');
                    document.write('<img src="<?php echo $screen_directory; ?>/screen.png" border="1" ');
                    if (document.getElementById) {
                        document.write('style="border: 1px solid #000000;" ');
                    }
                    document.write('alt="<?php echo htmlspecialchars(addslashes($theme_name)); ?>" ');
                    document.write('title="<?php echo htmlspecialchars(addslashes($theme_name)); ?>" />');
                    document.write('</a><br />');
                    document.write('[ <b><a href="#top" onclick="takeThis(\'<?php echo $PMA_Theme; ?>\'); return false;">');
                    document.write('<?php echo addslashes($strTakeIt); ?>');
                    document.write('</a></b> ]');
                //-->
                </script>
                <noscript>
                    <?php
                echo '<img src="' . $screen_directory . '/screen.png" border="1" alt="' . htmlspecialchars($theme_name) . ' - Theme" />';
                    ?>
                </noscript>
            </td>
        </tr>
        <tr>
            <td><img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>" width="1" height="1" border="0" alt="" /></td>
        </tr>
<?php
            } // end 'screen output'
        } // end 'check theme'
    } // end 'get screens'
    closedir($handleThemes);
} // end 'open themes'
?>
    </table>
</body>
</html>
