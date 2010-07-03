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
	<li><a href="?page=form<?php echo $separator ?>formset=Features"><?php echo __('Features') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=Sql_queries"><?php echo __('SQL queries') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=Left_frame"><?php echo __('Navigation frame') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=Main_frame"><?php echo __('Main frame') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=Import"><?php echo __('Import') ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=Export"><?php echo __('Export') ?></a></li>
</ul>
