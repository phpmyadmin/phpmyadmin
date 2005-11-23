<?php
/* get some globals */
require_once('./libraries/common.lib.php');

/* Theme Select */
$path_to_themes = $cfg['ThemePath'] . '/';
require_once('./libraries/select_theme.lib.php');

/* set language and charset */
require_once('./libraries/header_http.inc.php');

/* HTML header */
$page_title = 'phpMyAdmin - ' . $strTheme;
require('./libraries/header_meta_style.inc.php');
?>
<script language="JavaScript" type="text/javascript">
// <![CDATA[
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
// ]]>
</script>
</head>

<body id="bodythemes">
<h1>phpMyAdmin - <?php echo $strTheme; ?></h1>
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

    if (is_dir($screen_directory) && @file_exists($screen_directory.'/screen.png')) {
        // if screen exists then output
        ?>
<h2><?php echo htmlspecialchars( $theme_name ); ?></h2>

<p> <a href="index.php?set_theme=<?php echo $PMA_Theme; ?>&amp;<?php echo PMA_generate_common_url(); ?>"
        target="_top"
        onclick="takeThis('<?php echo addslashes( $PMA_Theme ); ?>'); return false;">
    <img src="<?php echo $screen_directory; ?>/screen.png" border="1"
        alt="<?php echo htmlspecialchars( $theme_name ); ?>"
        title="<?php echo htmlspecialchars( $theme_name ); ?>" /><br />
    [ <b><?php echo $strTakeIt; ?></b> ]</a>
</p>
        <?php
    } // end 'screen output'
} // end 'open themes'
?>
</body>
</html>
