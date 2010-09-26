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
if (true == $cfg['PropertiesIconic']) {
    $titles['Browse']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_browse.png" alt="' . __('Browse') . '" title="' . __('Browse') . '" />';
    $titles['NoBrowse']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_browse.png" alt="' . __('Browse') . '" title="' . __('Browse') . '" />';
    $titles['Search']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_select.png" alt="' . __('Search') . '" title="' . __('Search') . '" />';
    $titles['NoSearch']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_select.png" alt="' . __('Search') . '" title="' . __('Search') . '" />';
    $titles['Insert']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_insrow.png" alt="' . __('Insert') . '" title="' . __('Insert') . '" />';
    $titles['NoInsert']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_insrow.png" alt="' . __('Insert') . '" title="' . __('Insert') . '" />';
    $titles['Structure']  = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_props.png" alt="' . __('Structure') . '" title="' . __('Structure') . '" />';
    $titles['Drop']       = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_drop.png" alt="' . __('Drop') . '" title="' . __('Drop') . '" />';
    $titles['NoDrop']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_drop.png" alt="' . __('Drop') . '" title="' . __('Drop') . '" />';
    $titles['Empty']      = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_empty.png" alt="' . __('Empty') . '" title="' . __('Empty') . '" />';
    $titles['NoEmpty']    = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_empty.png" alt="' . __('Empty') . '" title="' . __('Empty') . '" />';

    if ('both' === $cfg['PropertiesIconic']) {
        $titles['Browse']     .= __('Browse');
        $titles['Search']     .= __('Search');
        $titles['NoBrowse']   .= __('Browse');
        $titles['NoSearch']   .= __('Search');
        $titles['Insert']     .= __('Insert');
        $titles['NoInsert']   .= __('Insert');
        $titles['Structure']  .= __('Structure');
        $titles['Drop']       .= __('Drop');
        $titles['NoDrop']     .= __('Drop');
        $titles['Empty']      .= __('Empty');
        $titles['NoEmpty']    .= __('Empty');
    }
} else {
    $titles['Browse']     = __('Browse');
    $titles['Search']     = __('Search');
    $titles['NoBrowse']   = __('Browse');
    $titles['NoSearch']   = __('Search');
    $titles['Insert']     = __('Insert');
    $titles['NoInsert']   = __('Insert');
    $titles['Structure']  = __('Structure');
    $titles['Drop']       = __('Drop');
    $titles['NoDrop']     = __('Drop');
    $titles['Empty']      = __('Empty');
    $titles['NoEmpty']    = __('Empty');
}
?>
