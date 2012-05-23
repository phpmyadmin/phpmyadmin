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

$header = PMA_Header::getInstance();
$header->setBodyId('bodythemes');
$header->setTitle('phpMyAdmin - ' . __('Theme'));
$header->disableMenu();
$header->display();

?>
<h1>phpMyAdmin - <?php echo __('Theme'); ?></h1>
<p><a href="<?php echo PMA_linkURL('http://www.phpmyadmin.net/home_page/themes.php'); ?>#pma_<?php echo preg_replace('/([0-9]*)\.([0-9]*)\..*/', '\1_\2', PMA_VERSION); ?>" class="_blank"><?php echo __('Get more themes!'); ?></a></p>
<?php
$_SESSION['PMA_Theme_Manager']->printPreviews();
?>
</body>
</html>
