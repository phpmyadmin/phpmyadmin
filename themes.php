<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * get some globals
 */
require './libraries/common.inc.php';

/* Theme Select */
$path_to_themes = $cfg['ThemePath'] . '/';

/* set language and charset */
require './libraries/header_http.inc.php';

/* HTML header */
$page_title = 'phpMyAdmin - ' . __('Theme');
require './libraries/header_meta_style.inc.php';
require './libraries/header_scripts.inc.php';
?>
</head>

<body id="bodythemes">
<h1>phpMyAdmin - <?php echo __('Theme'); ?></h1>
<p><a href="<?php echo PMA_linkURL('http://www.phpmyadmin.net/home_page/themes.php'); ?>#pma_<?php echo preg_replace('/([0-9]*)\.([0-9]*)\..*/', '\1_\2', PMA_VERSION); ?>"><?php echo __('Get more themes!'); ?></a></p>
<?php
$_SESSION['PMA_Theme_Manager']->printPreviews();
?>
</body>
</html>
