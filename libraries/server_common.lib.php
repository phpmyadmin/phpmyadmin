<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Shared code for server pages
 *
 * @package PhpMyAdmin
 */

/**
 * Returns the html for the sub-page heading
 *
 * @param string $type     Sub page type
 * @param string $link     Link to the official MySQL documentation
 * @param bool   $is_image Display image or icon, true: image, false: icon
 *
 * @return string
 */
function PMA_getHtmlForSubPageHeader($type, $link='', $is_image=true)
{
    //array contains Sub page icon and text
    $header = array();

    $header['variables']['image'] = 's_vars.png';
    $header['variables']['text'] = __('Server variables and settings');

    $header['engines']['image'] = 'b_engine.png';
    $header['engines']['text'] = __('Storage Engines');

    $header['plugins']['image'] = 'b_engine.png';
    $header['plugins']['text'] = __('Plugins');

    $header['binlog']['image'] = 's_tbl.png';
    $header['binlog']['text'] = __('Binary log');

    $header['collations']['image'] = 's_asci.png';
    $header['collations']['text'] = __('Character Sets and Collations');

    $header['replication']['image'] = 's_replication.png';
    $header['replication']['text'] = __('Replication');

    $header['database_statistics']['image'] = 's_db.png';
    $header['database_statistics']['text'] = __('Databases statistics');

    $header['databases']['image'] = 's_db.png';
    $header['databases']['text'] = __('Databases');

    $header['privileges']['image'] = 'b_usrlist.png';
    $header['privileges']['text'] = __('Privileges');

    if ($is_image) {
        $html = '<h2>' . "\n"
            . PMA\libraries\Util::getImage($header[$type]['image'])
            . '    ' . $header[$type]['text'] . "\n"
            . $link . '</h2>' . "\n";
    } else {
        $html = '<h2>' . "\n"
            . PMA\libraries\Util::getIcon($header[$type]['image'])
            . '    ' . $header[$type]['text'] . "\n"
            . $link . '</h2>' . "\n";
    }
    return $html;
}

