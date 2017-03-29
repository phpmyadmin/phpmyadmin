<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Header for the navigation panel
 *
 * @package PhpMyAdmin-Navigation
 */
namespace PMA\libraries\navigation;

use PMA;
use PMA\libraries\Template;
use PMA\libraries\URL;
use PMA\libraries\Sanitize;

/**
 * This class renders the logo, links, server selection,
 * which are then displayed at the top of the navigation panel
 *
 * @package PhpMyAdmin-Navigation
 */
class NavigationHeader
{
    /**
     * Renders the navigation
     *
     * @return String HTML
     */
    public function getDisplay()
    {
        if (empty($GLOBALS['url_query'])) {
            $GLOBALS['url_query'] = URL::getCommon();
        }
        $link_url = URL::getCommon(
            array(
                'ajax_request' => true,
            )
        );
        $class = ' class="list_container';
        if ($GLOBALS['cfg']['NavigationLinkWithMainPanel']) {
            $class .= ' synced';
        }
        if ($GLOBALS['cfg']['NavigationTreePointerEnable']) {
            $class .= ' highlight';
        }
        $class .= '"';
        $buffer = '<div id="pma_navigation">';
        $buffer .= '<div id="pma_navigation_resizer"></div>';
        $buffer .= '<div id="pma_navigation_collapser"></div>';
        $buffer .= '<div id="pma_navigation_content">';
        $buffer .= '<div id="pma_navigation_header">';
        $buffer .= sprintf(
            '<a class="hide navigation_url" href="navigation.php%s"></a>',
            $link_url
        );
        $buffer .= $this->_logo();
        $buffer .= $this->_links();
        $buffer .= $this->_serverChoice();
        $buffer .= PMA\libraries\Util::getImage(
            'ajax_clock_small.gif',
            __('Loading…'),
            array(
                'style' => 'visibility: hidden; display:none',
                'class' => 'throbber',
            )
        );
        $buffer .= '</div>'; // pma_navigation_header
        $buffer .= '<div id="pma_navigation_tree"' . $class . '>';

        return $buffer;
    }

    /**
     * Create the code for displaying the phpMyAdmin
     * logo based on configuration settings
     *
     * @return string HTML code for the logo
     */
    private function _logo()
    {
        // display Logo, depending on $GLOBALS['cfg']['NavigationDisplayLogo']
        if (!$GLOBALS['cfg']['NavigationDisplayLogo']) {
            return Template::get('navigation/logo')
                ->render(array('displayLogo' => false));
        }

        $logo = 'phpMyAdmin';
        if (@file_exists($GLOBALS['pmaThemeImage'] . 'logo_left.png')) {
            $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'logo_left.png" '
                . 'alt="' . $logo . '" id="imgpmalogo" />';
        } elseif (@file_exists($GLOBALS['pmaThemeImage'] . 'pma_logo2.png')) {
            $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo2.png" '
                . 'alt="' . $logo . '" id="imgpmalogo" />';
        }

        if (!$GLOBALS['cfg']['NavigationLogoLink']) {
            return Template::get('navigation/logo')
                ->render(
                    array(
                        'displayLogo' => true,
                        'useLogoLink' => false,
                        'logo'        => $logo,
                    )
                );
        }

        $useLogoLink = true;
        $linkAttriks = null;
        $logoLink = trim(
            htmlspecialchars($GLOBALS['cfg']['NavigationLogoLink'])
        );
        // prevent XSS, see PMASA-2013-9
        // if link has protocol, allow only http and https
        if (! Sanitize::checkLink($logoLink, true)) {
            $logoLink = 'index.php';
        }
        switch ($GLOBALS['cfg']['NavigationLogoLinkWindow']) {
        case 'new':
            $linkAttriks = 'target="_blank" rel="noopener noreferrer"';
            break;
        case 'main':
            // do not add our parameters for an external link
            $host = parse_url(
                $GLOBALS['cfg']['NavigationLogoLink'],
                PHP_URL_HOST
            );
            if (empty($host)) {
                $logoLink .= URL::getCommon();
            } else {
                $linkAttriks = 'target="_blank" rel="noopener noreferrer"';
            }
        }

        return Template::get('navigation/logo')
            ->render(
                array(
                    'displayLogo' => true,
                    'useLogoLink' => $useLogoLink,
                    'logoLink'    => $logoLink,
                    'linkAttribs' => $linkAttriks,
                    'logo'        => $logo,
                )
            );
    }

    /**
     * Creates the code for displaying the links
     * at the top of the navigation panel
     *
     * @return string HTML code for the links
     */
    private function _links()
    {
        // always iconic
        $showIcon = true;
        $showText = false;

        $retval = '<!-- LINKS START -->';
        $retval .= '<div id="navipanellinks">';
        $retval .= PMA\libraries\Util::getNavigationLink(
            'index.php' . URL::getCommon(),
            $showText,
            __('Home'),
            $showIcon,
            'b_home.png'
        );
        // if we have chosen server
        if ($GLOBALS['server'] != 0) {
            // Logout for advanced authentication
            if ($GLOBALS['cfg']['Server']['auth_type'] != 'config') {
                $text = __('Log out');
            } else {
                $text = __('Empty session data');
            }
            $link = 'logout.php' . $GLOBALS['url_query'];
            $retval .= PMA\libraries\Util::getNavigationLink(
                $link,
                $showText,
                $text,
                $showIcon,
                's_loggoff.png',
                '',
                true,
                '',
                array('logout')
            );
        }
        $retval .= PMA\libraries\Util::getNavigationLink(
            PMA\libraries\Util::getDocuLink('index'),
            $showText,
            __('phpMyAdmin documentation'),
            $showIcon,
            'b_docs.png',
            '',
            false,
            'documentation'
        );
        $retval .= PMA\libraries\Util::getNavigationLink(
            PMA\libraries\Util::getMySQLDocuURL('', ''),
            $showText,
            __('Documentation'),
            $showIcon,
            'b_sqlhelp.png',
            '',
            false,
            'mysql_doc'
        );
        $retval .= PMA\libraries\Util::getNavigationLink(
            '#',
            $showText,
            __('Navigation panel settings'),
            $showIcon,
            's_cog.png',
            'pma_navigation_settings_icon',
            false,
            '',
            defined('PMA_DISABLE_NAVI_SETTINGS') ? array('hide') : array()
        );
        $retval .= PMA\libraries\Util::getNavigationLink(
            '#',
            $showText,
            __('Reload navigation panel'),
            $showIcon,
            's_reload.png',
            'pma_navigation_reload'
        );
        $retval .= '</div>';
        $retval .= '<!-- LINKS ENDS -->';

        return $retval;
    }

    /**
     * Displays the MySQL servers choice form
     *
     * @return string HTML code for the MySQL servers choice
     */
    private function _serverChoice()
    {
        $retval = '';
        if ($GLOBALS['cfg']['NavigationDisplayServers']
            && count($GLOBALS['cfg']['Servers']) > 1
        ) {
            include_once './libraries/select_server.lib.php';
            $retval .= '<!-- SERVER CHOICE START -->';
            $retval .= '<div id="serverChoice">';
            $retval .= PMA_selectServer(true, true);
            $retval .= '</div>';
            $retval .= '<!-- SERVER CHOICE END -->';
        }

        return $retval;
    }
}
