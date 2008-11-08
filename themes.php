<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * get some globals
 */
require_once './libraries/common.inc.php';

/* Theme Select */
$path_to_themes = $cfg['ThemePath'] . '/';

/* set language and charset */
require_once './libraries/header_http.inc.php';

/* HTML header */
$page_title = 'phpMyAdmin - ' . $strTheme;
require './libraries/header_meta_style.inc.php';
?>
<script type="text/javascript" language="javascript">
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
$_SESSION['PMA_Theme_Manager']->printPreviews();
?>
</body>
</html>
