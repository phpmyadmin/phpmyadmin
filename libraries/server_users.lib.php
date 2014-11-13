<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of common functions for sub tabs in server level `Users` page
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Get HTML for secondary level menu tabs on 'Users' page
 *
 * @param string $selfUrl Url of the file
 *
 * @return string HTML for secondary level menu tabs on 'Users' page
 */
function PMA_getHtmlForSubMenusOnUsersPage($selfUrl)
{
    $url_params = PMA_URL_getCommon();
    $items = array(
        array(
            'name' => __('Users overview'),
            'url' => 'server_privileges.php',
            'specific_params' => '&viewing_mode=server'
        )
    );

    if ($GLOBALS['is_superuser']) {
        $items[] = array(
            'name' => __('User groups'),
            'url' => 'server_user_groups.php',
            'specific_params' => ''
        );
    }

    $retval  = '<ul id="topmenu2">';
    foreach ($items as $item) {
        $class = '';
        if ($item['url'] === $selfUrl) {
            $class = ' class="tabactive"';
        }
        $retval .= '<li>';
        $retval .= '<a' . $class;
        $retval .= ' href="' . $item['url']
            . $url_params . $item['specific_params'] . '">';
        $retval .= $item['name'];
        $retval .= '</a>';
        $retval .= '</li>';
    }
    $retval .= '</ul>';
    $retval .= '<div class="clearfloat"></div>';

    return $retval;
}
?>