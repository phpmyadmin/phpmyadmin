<?php
/**
 * set of common functions for sub tabs in server level `Users` page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Url;

/**
 * PhpMyAdmin\Server\Users class
 *
 * @package PhpMyAdmin
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
                'url' => Url::getFromRoute('/server/user_groups'),
                'params' => '',
            ];
        }

        $retval  = '<div class="row"><ul id="topmenu2">';
        foreach ($items as $item) {
            $class = '';
            if ($item['url'] === $selfUrl) {
                $class = ' class="tabactive"';
            }
            $retval .= '<li>';
            $retval .= '<a' . $class;
            $retval .= ' href="' . $item['url'] . $item['params'] . '">';
            $retval .= $item['name'];
            $retval .= '</a>';
            $retval .= '</li>';
        }
        $retval .= '</ul></div>';
        $retval .= '<div class="clearfloat"></div>';

        return $retval;
    }
}
