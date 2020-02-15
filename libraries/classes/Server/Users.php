<?php
/**
 * set of common functions for sub tabs in server level `Users` page
 */
declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Url;

/**
 * PhpMyAdmin\Server\Users class
 */
class Users
{
    /**
     * Get HTML for secondary level menu tabs on 'Users' page
     *
     * @param string $selfUrl Url of the file
     *
     * @return string HTML for secondary level menu tabs on 'Users' page
     */
    public static function getHtmlForSubMenusOnUsersPage($selfUrl)
    {
        $items = [
            [
                'name' => __('User accounts overview'),
                'url' => Url::getFromRoute('/server/privileges'),
                'params' => Url::getCommon(['viewing_mode' => 'server'], '&'),
            ],
        ];

        if ($GLOBALS['dbi']->isSuperuser()) {
            $items[] = [
                'name' => __('User groups'),
                'url' => Url::getFromRoute('/server/user-groups'),
                'params' => '',
            ];
        }

        $retval = '<div class="row"><ul class="nav nav-pills m-2">';
        foreach ($items as $item) {
            $class = '';
            if ($item['url'] === $selfUrl) {
                $class = ' active';
            }
            $retval .= '<li class="nav-item">';
            $retval .= '<a class="nav-link' . $class;
            $retval .= '" href="' . $item['url'] . $item['params'] . '">';
            $retval .= $item['name'];
            $retval .= '</a>';
            $retval .= '</li>';
        }
        $retval .= '</ul></div>';

        return $retval;
    }
}
