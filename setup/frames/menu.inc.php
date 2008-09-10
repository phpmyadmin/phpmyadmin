<?php
/**
 * Menu items 
 * 
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

$separator = PMA_get_arg_separator('html');
?>
<ul>
	<li><a href="index.php"><?php echo $GLOBALS['str']['Overview'] ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=features"><?php echo $GLOBALS['str']['Formset_features'] ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=left_frame"><?php echo $GLOBALS['str']['Form_Left_frame'] ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=main_frame"><?php echo $GLOBALS['str']['Form_Main_frame'] ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=import"><?php echo $GLOBALS['str']['Form_Import'] ?></a></li>
	<li><a href="?page=form<?php echo $separator ?>formset=export"><?php echo $GLOBALS['str']['Form_Export'] ?></a></li>
</ul>
