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

$common_functions = PMA_CommonFunctions::getInstance();

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
        echo '<a href="' . htmlspecialchars($GLOBALS['cfg']['LeftLogoLink']);
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
    echo '<a target="frame_content" href="main.php?' . $query_url . '"'
        .' title="' . __('Home') . '">'
        . $common_functions->getImage('b_home.png', __('Home'))
        .'</a>' . "\n";
    // if we have chosen server
    if ($server != 0) {
        // Logout for advanced authentication
        if ($GLOBALS['cfg']['Server']['auth_type'] != 'config') {
            echo '<a href="index.php?' . $query_url . '&amp;old_usr='
                .urlencode($PHP_AUTH_USER) . '" target="_parent"'
                .' title="' . __('Log out') . '" >'
                . $common_functions->getImage('s_loggoff.png', __('Log out'))
                .'</a>' . "\n";
        } // end if ($GLOBALS['cfg']['Server']['auth_type'] != 'config'

        $anchor = 'querywindow.php?' . PMA_generate_common_url($db, $table);

        echo '<a href="' . $anchor . '&amp;no_js=true"'
            .' title="' . __('Query window') . '"';
        echo ' onclick="if (window.parent.open_querywindow()) return false;"';
        echo '>' . $common_functions->getImage('b_selboard.png', __('Query window')) . '</a>' . "\n";
    } // end if ($server != 0)

    echo '    <a href="Documentation.html" target="documentation"'
        .' title="' . __('phpMyAdmin documentation') . '" >';

    echo $common_functions->getImage('b_docs.png', __('phpMyAdmin documentation'));
    echo '</a>';

    echo $common_functions->showMySQLDocu('', '', true) . "\n";

    $params = array('uniqid' => uniqid());
    if (!empty($GLOBALS['db'])) {
        $params['db'] = $GLOBALS['db'];
    }
    echo '<a href="navigation.php?' . PMA_generate_common_url($params)
        . '" title="' . __('Reload navigation frame') . '" target="frame_navigation">';
    echo $common_functions->getImage('s_reload.png', __('Reload navigation frame'));
    echo '</a>';

echo '</div>' . "\n";

/**
 * Displays the MySQL servers choice form
 */
if ($GLOBALS['cfg']['LeftDisplayServers'] && (count($GLOBALS['cfg']['Servers']) > 1 || $server == 0 && count($GLOBALS['cfg']['Servers']) == 1)) {
    echo '<div id="serverinfo">';
    include './libraries/select_server.lib.php';
    PMA_selectServer(true, true);
    echo '</div><br />';
} // end if LeftDisplayServers
?>
