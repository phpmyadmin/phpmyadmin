<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays the pma logo, links and db and server selection in left frame
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (empty($query_url)) {
    // avoid putting here $db because it could display a db name
    // to which the next user does not have access
    $query_url = PMA_generate_common_url();
}

// display Logo, depending on $GLOBALS['cfg']['LeftDisplayLogo']
if ($GLOBALS['cfg']['LeftDisplayLogo']) {
    $logo = 'phpMyAdmin';
    if (@file_exists($GLOBALS['pmaThemeImage'] . 'logo_left.png')) {
        $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'logo_left.png" '
            .'alt="' . $logo . '" id="imgpmalogo" />';
    } elseif (@file_exists($GLOBALS['pmaThemeImage'] . 'pma_logo2.png')) {
        $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo2.png" '
            .'alt="' . $logo . '" id="imgpmalogo" />';
    }

    echo '<div id="pmalogo">' . "\n";
    if ($GLOBALS['cfg']['LeftLogoLink']) {
        $logo_link = trim(htmlspecialchars($GLOBALS['cfg']['LeftLogoLink']));
        // prevent XSS, see PMASA-2013-9
        // if link has protocol, allow only http and https
        if (preg_match('/^[a-z]+:/i', $logo_link)
            && ! preg_match('/^https?:/i', $logo_link)) {
            $logo_link = 'main.php';
        }
        echo '<a href="' . $logo_link;
        switch ($GLOBALS['cfg']['LeftLogoLinkWindow']) {
            case 'new':
                echo '" target="_blank"';
                break;
            case 'main':
                // do not add our parameters for an external link
                if (substr(strtolower($GLOBALS['cfg']['LeftLogoLink']), 0, 4) !== '://') {
                    echo '?' . $query_url . '" target="frame_content"';
                } else {
                    echo '" target="_blank"';
                }
        }
        echo '>' . $logo . '</a>' . "\n";
    } else {
        echo $logo . "\n";
    }
    echo '</div>' . "\n";
} // end of display logo
?>
<div id="leftframelinks">
<?php
    echo '<a href="main.php?' . $query_url . '"'
        .' title="' . __('Home') . '">'
        .($GLOBALS['cfg']['MainPageIconic']
            ? PMA_getImage('b_home.png', __('Home'))
            : __('Home'))
        .'</a>' . "\n";
    // if we have chosen server
    if ($server != 0) {
        // Logout for advanced authentication
        if ($GLOBALS['cfg']['Server']['auth_type'] != 'config') {
            echo ($GLOBALS['cfg']['MainPageIconic'] ? '' : ' - ');
            echo '<a href="index.php?' . $query_url . '&amp;old_usr='
                .urlencode($PHP_AUTH_USER) . '" target="_parent"'
                .' title="' . __('Log out') . '" >'
                .($GLOBALS['cfg']['MainPageIconic']
                    ? PMA_getImage('s_loggoff.png', __('Log out'))
                    : __('Log out'))
                .'</a>' . "\n";
        } // end if ($GLOBALS['cfg']['Server']['auth_type'] != 'config'

        $anchor = 'querywindow.php?' . PMA_generate_common_url($db, $table);

        if ($GLOBALS['cfg']['MainPageIconic']) {
            $query_frame_link_text = PMA_getImage('b_selboard.png', __('Query window'));
        } else {
            echo '<br />' . "\n";
            $query_frame_link_text = __('Query window');
        }
        echo '<a href="' . $anchor . '&amp;no_js=true"'
            .' title="' . __('Query window') . '"';
        echo ' onclick="if (window.parent.open_querywindow()) return false;"';
        echo '>' . $query_frame_link_text . '</a>' . "\n";
    } // end if ($server != 0)

    echo '    <a href="Documentation.html" target="documentation"'
        .' title="' . __('phpMyAdmin documentation') . '" >';

    if ($GLOBALS['cfg']['MainPageIconic']) {
        echo PMA_getImage('b_docs.png', __('phpMyAdmin documentation'));
    } else {
        echo '<br />' . __('phpMyAdmin documentation');
    }
    echo '</a>';

    $documentation_link = PMA_showMySQLDocu('', '', true);
    if ($GLOBALS['cfg']['MainPageIconic']) {
        echo $documentation_link . "\n";
    } else {
        preg_match('/<a[^>]*>/', $documentation_link, $matches);
        $link = $matches[0];
        echo substr($link, 0, strlen($link) - 1) . ' title="' . __('Documentation') . '" >'
            . '<br />' . __('Documentation') . '</a>';
    }

    $params = array('uniqid' => uniqid());
    if (!empty($GLOBALS['db'])) {
        $params['db'] = $GLOBALS['db'];
    }
    echo '<a href="navigation.php?' . PMA_generate_common_url($params)
        . '" title="' . __('Reload navigation frame') . '" target="frame_navigation">';
    if ($GLOBALS['cfg']['MainPageIconic']) {
        echo PMA_getImage('s_reload.png', __('Reload navigation frame'));
    } else {
        echo '<br />' . __('Reload navigation frame');
    }
    echo '</a>';

echo '</div>' . "\n";

/**
 * Displays the MySQL servers choice form
 */
if ($GLOBALS['cfg']['LeftDisplayServers'] && (count($GLOBALS['cfg']['Servers']) > 1 || $server == 0 && count($GLOBALS['cfg']['Servers']) == 1)) {
    echo '<div id="serverinfo">';
    include './libraries/select_server.lib.php';
    PMA_select_server(true, true);
    echo '</div><br />';
} // end if LeftDisplayServers
?>
