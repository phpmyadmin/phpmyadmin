<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Shared code for server pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Returns the html for the sub-page heading
 *
 * @param string $type Sub page type
 *
 * @return string
 */
function PMA_getSubPageHeader($type)
{
    $res = array();

    $res['plugins']['icon'] = 'b_engine.png';
    $res['plugins']['text'] = __('Plugins');
    
    $res['binlog']['icon'] = 's_tbl.png';
    $res['binlog']['text'] = __('Binary log');
    
    $res['collations']['icon'] = 's_asci.png';
    $res['collations']['text'] = __('Character Sets and Collations');
    
    $res['replication']['icon'] = 's_replication.png';
    $res['replication']['text'] = __('Replication');
    
    $html = '<h2>' . "\n"
        . PMA_Util::getImage($res[$type]['icon'])
        . '    ' . $res[$type]['text'] . "\n"
        . '</h2>' . "\n";
    return $html;
}

?>
