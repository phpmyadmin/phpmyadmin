<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * displays the pma logo, links and db and server selection in left frame
 *
 */

if ( empty( $query_url ) ) {
    $db     = ! isset( $db )      ? '' : $db;
    $table  = ! isset( $table )   ? '' : $table;
    $query_url = PMA_generate_common_url( $db, $table );
}

// display Logo, depending on $GLOBALS['cfg']['LeftDisplayLogo']
if ( $GLOBALS['cfg']['LeftDisplayLogo'] ) {
    $logo = 'phpMyAdmin';
    if ( @file_exists( $GLOBALS['pmaThemeImage'] . 'logo_left.png' ) ) {
        $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'logo_left.png" '
            .'alt="' . $logo . '" id="imgpmalogo" />';
    } elseif ( @file_exists( $GLOBALS['pmaThemeImage'] . 'pma_logo2.png' ) ) {
        $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo2.png" '
            .'alt="' . $logo . '" id="imgpmalogo" />';
    }

    echo '<div id="pmalogo">' . "\n"
        .'<a href="' . $GLOBALS['cfg']['LeftLogoLink'] . '" target="_blank">'
        .$logo . '</a>' . "\n"
        .'</div>' . "\n";
} // end of display logo
?>
<div id="leftframelinks">
<?php
    echo '<a href="main.php?' . $query_url . '"'
        .' title="' . $strHome . '">'
        .( $GLOBALS['cfg']['MainPageIconic']
            ? '<img class="icon" src="' . $pmaThemeImage . 'b_home.png" width="16" '
                .' height="16" alt="' . $strHome . '" />'
            : $strHome )
        .'</a>' . "\n";
    // if we have chosen server
    if ( $server != 0 ) {
        // Logout for advanced authentication
        if ( $GLOBALS['cfg']['Server']['auth_type'] != 'config' ) {
            echo ($GLOBALS['cfg']['MainPageIconic'] ? '' : ' - ');
            echo '<a href="index.php?' . $query_url . '&amp;old_usr='
                .urlencode($PHP_AUTH_USER) . '" target="_parent"'
                .' title="' . $strLogout . '" >'
                .( $GLOBALS['cfg']['MainPageIconic']
                    ? '<img class="icon" src="' . $pmaThemeImage . 's_loggoff.png" '
                     .' width="16" height="16" alt="' . $strLogout . '" />'
                    : $strLogout )
                .'</a>' . "\n";
        } // end if ($GLOBALS['cfg']['Server']['auth_type'] != 'config'

        $anchor = 'querywindow.php?' . PMA_generate_common_url( $db, $table );

        if ($GLOBALS['cfg']['MainPageIconic']) {
            $query_frame_link_text =
                '<img class="icon" src="' . $pmaThemeImage . 'b_selboard.png"'
                .' width="16" height="16" alt="' . $strQueryFrame . '" />';
        } else {
            echo '<br />' . "\n";
            $query_frame_link_text = $strQueryFrame;
        }
        echo '<a href="' . $anchor . '&amp;no_js=true"'
            .' title="' . $strQueryFrame . '"';
        echo ' onclick="javascript:window.parent.open_querywindow();'
            .' return false;"';
        echo '>' . $query_frame_link_text . '</a>' . "\n";
    } // end if ($server != 0)

if ($GLOBALS['cfg']['MainPageIconic']) {
    echo '    <a href="Documentation.html" target="documentation"'
        .' title="' . $strPmaDocumentation . '" >'
        .'<img class="icon" src="' . $pmaThemeImage . 'b_docs.png" width="16" height="16"'
        .' alt="' . $strPmaDocumentation . '" /></a>' . "\n";
    echo '    ' . PMA_showMySQLDocu('', '', TRUE) . "\n";
}
echo '</div>' . "\n";

/**
 * Displays the MySQL servers choice form
 */
if ($GLOBALS['cfg']['LeftDisplayServers'] && (count($GLOBALS['cfg']['Servers']) > 1 || $server == 0 && count($GLOBALS['cfg']['Servers']) == 1)) {
    include('./libraries/select_server.lib.php');
    PMA_select_server(true, true);
    echo '<hr />';
} // end if LeftDisplayServers
?>
