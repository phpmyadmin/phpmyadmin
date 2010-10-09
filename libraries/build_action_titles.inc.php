<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$titles = array();

$titles['Browse']     = PMA_getIcon('b_browse.png', __('Browse'), true);
$titles['NoBrowse']   = PMA_getIcon('bd_browse.png', __('Browse'), true);
$titles['Search']     = PMA_getIcon('b_select.png', __('Search'), true);
$titles['NoSearch']   = PMA_getIcon('bd_select.png', __('Search'), true);
$titles['Insert']     = PMA_getIcon('b_insrow.png', __('Insert'), true);
$titles['NoInsert']   = PMA_getIcon('bd_insrow.png', __('Insert'), true);
$titles['Structure']  = PMA_getIcon('b_props.png', __('Structure'), true);
$titles['Drop']       = PMA_getIcon('b_drop.png', __('Drop'), true);
$titles['NoDrop']     = PMA_getIcon('bd_drop.png', __('Drop'), true);
$titles['Empty']      = PMA_getIcon('b_empty.png', __('Empty'), true);
$titles['NoEmpty']    = PMA_getIcon('bd_empty.png', __('Empty'), true);

?>
