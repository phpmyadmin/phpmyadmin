<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Header for the navigation panel
 *
 * @package PhpMyAdmin-Navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This class renders the logo, links, server selection and recent tables,
 * which are then displayed at the top of the naviagtion panel
 *
 * @package PhpMyAdmin-Navigation
 */
class PMA_NavigationHeader
{
    /**
     * Renders the navigation
     *
     * @return void
     */
    public function getDisplay()
    {
        if (empty($GLOBALS['url_query'])) {
            $GLOBALS['url_query'] = PMA_generate_common_url();
        }
        $link_url = PMA_generate_common_url(
            array(
                'ajax_request' => true
            )
        );
        $class = ' class="list_container';
        if ($GLOBALS['cfg']['NavigationTreePointerEnable']) {
            $class .= ' highlight';
        }
        $class .= '"';
        $buffer  = '<div id="pma_navigation">';
        $buffer .= '<div id="pma_navigation_resizer"></div>';
        $buffer .= '<div id="pma_navigation_collapser"></div>';
        $buffer .= '<div id="pma_navigation_content">';
        $buffer .= sprintf(
            '<a class="hide navigation_url" href="navigation.php%s"></a>',
            $link_url
        );
        $buffer .= $this->_logo();
        $buffer .= $this->_links();
        $buffer .= $this->_serverChoice();
        $buffer .= $this->_recent();
        $buffer .= PMA_Util::getImage(
            'ajax_clock_small.gif',
            __('Loading'),
            array('style' => 'visibility: hidden;', 'class' => 'throbber')
        );
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
        $retval = '<!-- LOGO START -->';
        // display Logo, depending on $GLOBALS['cfg']['NavigationDisplayLogo']
        if ($GLOBALS['cfg']['NavigationDisplayLogo']) {
            $logo = 'phpMyAdmin';
            if (@file_exists($GLOBALS['pmaThemeImage'] . 'logo_left.png')) {
                $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'logo_left.png" '
                    . 'alt="' . $logo . '" id="imgpmalogo" />';
            } elseif (@file_exists($GLOBALS['pmaThemeImage'] . 'pma_logo2.png')) {
                $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo2.png" '
                    . 'alt="' . $logo . '" id="imgpmalogo" />';
            }
            $retval .= '<div id="pmalogo">';
            if ($GLOBALS['cfg']['NavigationLogoLink']) {
                $logo_link = trim(htmlspecialchars($GLOBALS['cfg']['NavigationLogoLink']));
                // prevent XSS, see PMASA-2013-9
                // if link has protocol, allow only http and https
                if (preg_match('/^[a-z]+:/i', $logo_link)
                    && ! preg_match('/^https?:/i', $logo_link)) {
                    $logo_link = 'index.php';
                }
                $retval .= '    <a href="' . $logo_link;
                switch ($GLOBALS['cfg']['NavigationLogoLinkWindow']) {
                case 'new':
                    $retval .= '" target="_blank"';
                    break;
                case 'main':
                    // do not add our parameters for an external link
                    if (substr(strtolower($GLOBALS['cfg']['NavigationLogoLink']), 0, 4) !== '://') {
                        $retval .= '?' . $GLOBALS['url_query'] . '"';
                    } else {
                        $retval .= '" target="_blank"';
                    }
                }
                $retval .= '>';
                $retval .= $logo;
                $retval .= '</a>';
            } else {
                $retval .= $logo;
            }
            $retval .= '</div>';
        }
        $retval .= '<!-- LOGO END -->';
        return $retval;
    }

    /**
     * Renders a single link for the top of the navigation panel
     *
     * @param string $link        The url for the link
     * @param bool   $showText    Whether to show the text or to
     *                            only use it for title attributes
     * @param string $text        The text to display and use for title attributes
     * @param bool   $showIcon    Whether to show the icon
     * @param string $icon        The filename of the icon to show
     * @param string $linkId      Value to use for the ID attribute
     * @param string $disableAjax Whether to disable ajax page loading for this link
     * @param string $linkTarget  The name of the target frame for the link
     *
     * @return string HTML code for one link
     */
    private function _getLink(
        $link,
        $showText,
        $text,
        $showIcon,
        $icon,
        $linkId = '',
        $disableAjax = false,
        $linkTarget = ''
    ) {
        $retval = '<a href="' . $link . '"';
        if (! empty($linkId)) {
            $retval .= ' id="' . $linkId . '"';
        }
        if (! empty($linkTarget)) {
            $retval .= ' target="' . $linkTarget . '"';
        }
        if ($disableAjax) {
            $retval .= ' class="disableAjax"';
        }
        $retval .= ' title="' . $text . '">';
        if ($showIcon) {
            $retval .= PMA_Util::getImage(
                $icon,
                $text
            );
        }
        if ($showText) {
            $retval .= $text;
        }
        $retval .= '</a>';
        if ($showText) {
            $retval .= '<br />';
        }
        return $retval;
    }

    /**
     * Creates the code for displaying the links
     * at the top of the navigation frame
     *
     * @return string HTML code for the links
     */
    private function _links()
    {
        // always iconic
        $showIcon = true; 
        $showText = false; 

        $retval  = '<!-- LINKS START -->';
        $retval .= '<div id="leftframelinks">';
        $retval .= $this->_getLink(
            'index.php?' . PMA_generate_common_url(),
            $showText,
            __('Home'),
            $showIcon,
            'b_home.png'
        );
        // if we have chosen server
        if ($GLOBALS['server'] != 0) {
            // Logout for advanced authentication
            if ($GLOBALS['cfg']['Server']['auth_type'] != 'config') {
                $link  = 'index.php?' . $GLOBALS['url_query'];
                $link .= '&amp;old_usr=' . urlencode($GLOBALS['PHP_AUTH_USER']);
                $retval .= $this->_getLink(
                    $link,
                    $showText,
                    __('Log out'),
                    $showIcon,
                    's_loggoff.png',
                    '',
                    true
                );
            }
            $link  = 'querywindow.php?';
            $link .= PMA_generate_common_url($GLOBALS['db'], $GLOBALS['table']);
            $link .= '&amp;no_js=true';
            $retval .= $this->_getLink(
                $link,
                $showText,
                __('Query window'),
                $showIcon,
                'b_selboard.png',
                'pma_open_querywindow',
                true
            );
        }
        $retval .= $this->_getLink(
            PMA_Util::getDocuLink('index'),
            $showText,
            __('phpMyAdmin documentation'),
            $showIcon,
            'b_docs.png',
            '',
            false,
            'documentation'
        );
        if ($showIcon) {
            $retval .= PMA_Util::showMySQLDocu('', '', true);
        }
        if ($showText) {
            // PMA_showMySQLDocu always spits out an icon,
            // we just replace it with some perl regexp.
            $link = preg_replace(
                '/<img[^>]+>/i',
                __('Documentation'),
                PMA_Util::showMySQLDocu('', '', true)
            );
            $retval .= $link;
            $retval .= '<br />';
        }
        $retval .= $this->_getLink(
            '#',
            $showText,
            __('Reload navigation frame'),
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

    /**
     * Displays a drop-down choice of most recently used tables
     *
     * @return string HTML code for the Recent tables
     */
    private function _recent()
    {
        $retval = '';
        // display recently used tables
        if ($GLOBALS['cfg']['NumRecentTables'] > 0) {
            $retval .= '<!-- RECENT START -->';
            $retval .= '<div id="recentTableList">';
            $retval .= '<form method="post" ';
            $retval .= 'action="' . $GLOBALS['cfg']['DefaultTabTable'] . '">';
            $retval .= PMA_generate_common_hidden_inputs(
                array(
                    'db' => '',
                    'table' => '',
                    'server' => $GLOBALS['server']
                )
            );
            $retval .= PMA_RecentTable::getInstance()->getHtmlSelect();
            $retval .= '</form>';
            $retval .= '</div>';
            $retval .= '<!-- RECENT END -->';
        }
        return $retval;
    }
}
?>
