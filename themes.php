<?php
/* get some globals */
require_once('./libraries/common.lib.php');

/* Theme Select */
$path_to_themes = $cfg['ThemePath'] . '/';
require_once('./libraries/select_theme.lib.php');

/* set language and charset */
require_once('./libraries/header_http.inc.php');

/* Gets the font sizes to use */
PMA_setFontSizes();
/* HTML header */
$page_title = 'phpMyAdmin - ' . $strTheme;
require('./libraries/header_meta_style.inc.php');
?>
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
foreach ($available_themes_choices AS $PMA_Theme) {
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
} // end 'open themes'
?>
    </table>
</body>
</html>
