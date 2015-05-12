<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Menu items
 *
 * @package PhpMyAdmin-Setup
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

$separator = PMA_get_arg_separator('html');
?>
<ul>
    <li><a href="index.php?<?php echo PMA_generate_common_url() ?>"><?php echo __('Overview') ?></a></li>
    <li><a href="?page=form<?php echo $separator . PMA_generate_common_url() . $separator ?>formset=Features"><?php echo __('Features') ?></a></li>
    <li><a href="?page=form<?php echo $separator . PMA_generate_common_url() . $separator ?>formset=Sql_queries"><?php echo __('SQL queries') ?></a></li>
    <li><a href="?page=form<?php echo $separator . PMA_generate_common_url() . $separator ?>formset=Navi_panel"><?php echo __('Navigation panel') ?></a></li>
    <li><a href="?page=form<?php echo $separator . PMA_generate_common_url() . $separator ?>formset=Main_panel"><?php echo __('Main panel') ?></a></li>
    <li><a href="?page=form<?php echo $separator . PMA_generate_common_url() . $separator ?>formset=Import"><?php echo __('Import') ?></a></li>
    <li><a href="?page=form<?php echo $separator . PMA_generate_common_url() . $separator ?>formset=Export"><?php echo __('Export') ?></a></li>
</ul>
