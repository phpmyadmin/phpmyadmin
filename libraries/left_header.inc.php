<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * displays the pma logo, links and db and server selection in left frame
 * or queryframe
 *
 */

if ( empty( $query_url ) ) {
    $db     = empty( $db )      ? '' : $db;
    $table  = empty( $table )   ? '' : $table;
    $query_url = PMA_generate_common_url( $db, $table );
}

// display Logo, depending on $GLOBALS['cfg']['LeftDisplayLogo']
if ( $GLOBALS['cfg']['LeftDisplayLogo'] ) {
    $logo = 'phpMyAdmin';
    if ( @file_exists( $GLOBALS['pmaThemeImage'] . 'logo_left.png' ) ) {
        $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'logo_left.png" '
            .'alt="' . $logo . '" />';
    } elseif ( @file_exists( $GLOBALS['pmaThemeImage'] . 'pma_logo2.png' ) ) {
        $logo = '<img src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo2.png" '
            .'alt="' . $logo . '" />';
    }
    
    echo '<div id="pmalogo">' . "\n"
        .'<a href="http://www.phpmyadmin.net/" target="_blank">'
        .$logo . '</a>' . "\n"
        .'</div>' . "\n";
} // end of display logo
?>
<div id="leftframelinks">
<?php
    echo '<a href="main.php?' . $query_url . '" target="phpmain' . $hash . '"'
        .' title="' . $strHome . '">'
        .( $GLOBALS['cfg']['MainPageIconic']
            ? '<img src="' . $pmaThemeImage . 'b_home.png" width="16" '
                .' height="16" alt="' . $strHome . '"'
                .' align="middle" />'
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
                    ? '<img src="' . $pmaThemeImage . 's_loggoff.png" '
                     .' width="16" height="16" alt="' . $strLogout . '" />'
                    : $strLogout )
                .'</a>' . "\n";
        } // end if ($GLOBALS['cfg']['Server']['auth_type'] != 'config'

        if ( $GLOBALS['cfg']['QueryFrame'] ) {
            $anchor = 'querywindow.php?' . PMA_generate_common_url( '', '' );
            
            if ($GLOBALS['cfg']['MainPageIconic']) {
                $query_frame_link_text = 
                    '<img src="' . $pmaThemeImage . 'b_selboard.png"'
                    .' width="16" height="16" alt="' . $strQueryFrame . '" />';
            } else {
                echo '<br />' . "\n";
                $query_frame_link_text = $strQueryFrame;
            }
        echo '<a href="' . $anchor . '&amp;no_js=true"'
            .' target="phpmain' . $hash . '"'
            .' title="' . $strQueryFrame . '"';
        if ( $GLOBALS['cfg']['QueryFrameJS'] ) {
            echo ' onclick="javascript:open_querywindow(\'' . $anchor . '\');'
                .' return false;"';
        }
        echo '>' . $query_frame_link_text . '</a>' . "\n";
        } // end if ($GLOBALS['cfg']['QueryFrame'])
    } // end if ($server != 0)

if ($GLOBALS['cfg']['MainPageIconic']) {
    echo '    <a href="Documentation.html" target="documentation"'
        .' title="' . $strPmaDocumentation . '" >'
        .'<img src="' . $pmaThemeImage . 'b_docs.png" width="16" height="16"'
        .' alt="' . $strPmaDocumentation . '" /></a>' . "\n";
    echo '    <a href="' . $GLOBALS['cfg']['MySQLManualBase'] . '"'
        .' target="documentation" title="MySQL - ' . $strDocu . '">'
        .'<img src="' . $GLOBALS['pmaThemeImage'] . 'b_sqlhelp.png" width="16"'
        .' height="16" alt="MySQL - ' . $strDocu . '" /></a>' . "\n";
}
echo '</div>' . "\n";

if ( $GLOBALS['cfg']['LeftDisplayServers'] ) {
    $show_server_left = TRUE;
    include('./libraries/select_server.lib.php');
} // end if LeftDisplayServers

if ( $num_dbs > 1 && $cfg['LeftFrameLight'] ) {
    ?>
    <div id="databaseList">
    <form method="post" action="index.php" name="left" target="_parent"
        onclick="this.form.target='nav'; this.form.action='left.php'; return true;">
    <input type="hidden" name="hash" value="<?php echo $hash; ?>" />
    <label for="lightm_db"><?php echo $strDatabase; ?></label>
    <?php
    echo PMA_generate_common_hidden_inputs() . "\n";
    echo PMA_getHtmlSelectDb( $db ) . "\n";
    echo '<noscript>' . "\n"
        .'<input type="submit" name="Go" value="' . $strGo . '" />' . "\n"
        .'</noscript>' . "\n"
        .'</form>' . "\n"
        .'</div>' . "\n";

}
?>
