<?php
/**
 * Menu items
 *
 * @package    phpMyAdmin-setup
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

$separator = PMA_get_arg_separator('html');
?>
<ul>
	<li><a href="index.php"><?php echo __('Overview') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=features"><?php echo __('Features') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=left_frame"><?php echo __('Navigation frame') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=main_frame"><?php echo __('Main frame') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=import"><?php echo __('Import') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=export"><?php echo __('Export') ?></a></li>
</ul>
