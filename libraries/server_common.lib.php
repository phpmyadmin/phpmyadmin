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
 * @param string $link Link to the official MySQL documentation
 *
 * @return string
 */
function PMA_getHtmlForSubPageHeader($type, $link='')
{
    //array contains Sub page icon and text
    $header = array();

    $header['variables']['icon'] = 's_vars.png';
    $header['variables']['text'] = __('Server variables and settings');

    $header['engines']['icon'] = 'b_engine.png';
    $header['engines']['text'] = __('Storage Engines');

    $header['plugins']['icon'] = 'b_engine.png';
    $header['plugins']['text'] = __('Plugins');
    
    $header['binlog']['icon'] = 's_tbl.png';
    $header['binlog']['text'] = __('Binary log');
    
    $header['collations']['icon'] = 's_asci.png';
    $header['collations']['text'] = __('Character Sets and Collations');
    
    $header['replication']['icon'] = 's_replication.png';
    $header['replication']['text'] = __('Replication');
    
    $header['database_statistics']['icon'] = 's_db.png';
    $header['database_statistics']['text'] = __('Databases statistics');
    
    $header['databases']['icon'] = 's_db.png';
    $header['databases']['text'] = __('Databases');
    
    $html = '<h2>' . "\n"
        . PMA_Util::getImage($header[$type]['icon'])
        . '    ' . $header[$type]['text'] . "\n"
        . $link . '</h2>' . "\n";
    return $html;
}

?>
