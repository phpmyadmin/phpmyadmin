<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (empty($GLOBALS['is_header_sent'])) {

    /**
     * Gets a core script and starts output buffering work
     */
    require_once('./libraries/common.lib.php');
    require_once('./libraries/ob.lib.php');
    if ($GLOBALS['cfg']['OBGzip']) {
        $GLOBALS['ob_mode'] = PMA_outBufferModeGet();
        if ($GLOBALS['ob_mode']) {
            PMA_outBufferPre($GLOBALS['ob_mode']);
        }
    }

    // garvin: For re-usability, moved http-headers and stylesheets
    // to a seperate file. It can now be included by header.inc.php,
    // queryframe.php, querywindow.php.

    require_once('./libraries/header_http.inc.php');
    require_once('./libraries/header_meta_style.inc.php');
    /* replaced 2004-05-05 by Michael Keck (mkkeck)
    $title     = '';
    if (isset($GLOBALS['db'])) {
        $title .= str_replace('\'', '\\\'', $GLOBALS['db']);
    }
    if (isset($GLOBALS['table'])) {
        $title .= (empty($title) ? '' : '.') . str_replace('\'', '\\\'', $GLOBALS['table']);
    }
    if (!empty($GLOBALS['cfg']['Server']) && isset($GLOBALS['cfg']['Server']['host'])) {
        $title .= (empty($title) ? 'phpMyAdmin ' : ' ')
               . sprintf($GLOBALS['strRunning'], (empty($GLOBALS['cfg']['Server']['verbose']) ? str_replace('\'', '\\\'', $GLOBALS['cfg']['Server']['host']) : str_replace('\'', '\\\'', $GLOBALS['cfg']['Server']['verbose'])));
    }
    $title     .= (empty($title) ? '' : ' - ') . 'phpMyAdmin ' . PMA_VERSION;
    */
    /* the new one
     * 2004-05-05: replaced by Michael Keck (mkkeck)
     */
    $title     = '';
    if ($cfg['ShowHttpHostTitle']) {
        $title .= (empty($GLOBALS['cfg']['SetHttpHostTitle']) && isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $GLOBALS['cfg']['SetHttpHostTitle']) . ' >> ';
    }
    if (!empty($GLOBALS['cfg']['Server']) && isset($GLOBALS['cfg']['Server']['host'])) {
        $title.=str_replace('\'', '\\\'', $GLOBALS['cfg']['Server']['host']);
    }
    if (isset($GLOBALS['db'])) {
        $title .= ' >> ' . str_replace('\'', '\\\'', $GLOBALS['db']);
    }
    if (isset($GLOBALS['table'])) {
        $title .= (empty($title) ? '' : ' ') . ' >> ' . str_replace('\'', '\\\'', $GLOBALS['table']);
    }
    $title .= ' | phpMyAdmin ' . PMA_VERSION;
    ?>
    <script type="text/javascript" language="javascript">
    <!--
    // Updates the title of the frameset if possible (ns4 does not allow this)
    if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown'
        && typeof(parent.document.title) == 'string') {
        parent.document.title = '<?php echo $title; ?>';
    }
    <?php
    // Add some javascript instructions if required
    if (isset($js_to_run) && $js_to_run == 'functions.js') {
        echo "\n";
        ?>
    // js form validation stuff
    var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strFormEmpty']); ?>';
    var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotNumber']); ?>';
    var errorMsg2   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotValidNumber']); ?>';
    var noDropDbMsg = '<?php echo((!$GLOBALS['cfg']['AllowUserDropDatabase']) ? str_replace('\'', '\\\'', $GLOBALS['strNoDropDatabases']) : ''); ?>';
    var confirmMsg  = '<?php echo(($GLOBALS['cfg']['Confirm']) ? str_replace('\'', '\\\'', $GLOBALS['strDoYouReally']) : ''); ?>';
    var confirmMsgDropDB  = '<?php echo(($GLOBALS['cfg']['Confirm']) ? str_replace('\'', '\\\'', $GLOBALS['strDropDatabaseStrongWarning']) : ''); ?>';
    //-->
    </script>
    <script src="libraries/functions.js" type="text/javascript" language="javascript"></script>
        <?php
    } else if (isset($js_to_run) && $js_to_run == 'user_password.js') {
        echo "\n";
        ?>
    // js form validation stuff
    var jsHostEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strHostEmpty']); ?>';
    var jsUserEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strUserEmpty']); ?>';
    var jsPasswordEmpty   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordEmpty']); ?>';
    var jsPasswordNotSame = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordNotSame']); ?>';
    //-->
    </script>
    <script src="libraries/user_password.js" type="text/javascript" language="javascript"></script>
        <?php
    } else if (isset($js_to_run) && $js_to_run == 'server_privileges.js') {
        echo "\n";
        ?>
    // js form validation stuff
    var jsHostEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strHostEmpty']); ?>';
    var jsUserEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strUserEmpty']); ?>';
    var jsPasswordEmpty   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordEmpty']); ?>';
    var jsPasswordNotSame = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordNotSame']); ?>';
    //-->
    </script>
    <script src="libraries/server_privileges.js" type="text/javascript" language="javascript"></script>
        <?php
    } else if (isset($js_to_run) && $js_to_run == 'indexes.js') {
        echo "\n";
        ?>
    // js index validation stuff
    var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strFormEmpty']); ?>';
    var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotNumber']); ?>';
    var errorMsg2   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotValidNumber']); ?>';
    //-->
    </script>
    <script src="libraries/indexes.js" type="text/javascript" language="javascript"></script>
        <?php
    } else if (isset($js_to_run) && $js_to_run == 'tbl_change.js') {
        echo "\n";
        ?>
    //-->
    </script>
    <script src="libraries/tbl_change.js" type="text/javascript" language="javascript"></script>
        <?php
    } else {
        echo "\n";
        ?>
    //-->
    </script>
        <?php
    }
    echo "\n";

    // Reloads the navigation frame via JavaScript if required
    PMA_reloadNavigation();
    ?>
        <meta name="OBGZip" content="<?php echo ($cfg['OBGzip'] ? 'true' : 'false'); ?>" />
    </head>


    <?php
    if ($GLOBALS['cfg']['RightBgImage'] != '') {
        $bkg_img = ' background="' . $GLOBALS['cfg']['RightBgImage'] . '"';
    } else {
        $bkg_img = '';
    }
    ?>
    <body bgcolor="<?php echo $GLOBALS['cfg']['RightBgColor'] . '"' . $bkg_img; ?>>
    <?php
    include('./config.header.inc.php');

    if (!defined('PMA_DISPLAY_HEADING')) {
        define('PMA_DISPLAY_HEADING', 1);
    }

    /**
     * Display heading if needed. Design can be set in css file.
     */

    if (PMA_DISPLAY_HEADING) {
        echo '<table border="0" cellpadding="0" cellspacing="0" id="serverinfo">' . "\n"
           . '    <tr>' . "\n";
        $header_url_qry = '?' . PMA_generate_common_url();
        $server_info = (!empty($cfg['Server']['verbose'])
                        ? $cfg['Server']['verbose']
                        : $server_info = $cfg['Server']['host'] . (empty($cfg['Server']['port'])
                                                                   ? ''
                                                                   : ':' . $cfg['Server']['port']
                                                                  )
                       );
        echo '        '
           . '<td class="serverinfo">' . $GLOBALS['strServer'] . ':&nbsp;'
           . '<a href="' . $GLOBALS['cfg']['DefaultTabServer'] . '?' . PMA_generate_common_url() . '">';
        if ($GLOBALS['cfg']['MainPageIconic']) {
            echo '<img src="' . $GLOBALS['pmaThemeImage'] . 's_host.png" width="16" height="16" border="0" alt="' . htmlspecialchars($server_info) . '" />';
        }
        echo htmlspecialchars($server_info) . '</a>' . "\n"
           . '</td>' . "\n\n";

        if (!empty($GLOBALS['db'])) {
            echo '        '
               . '<td class="serverinfo"><div></div></td>' . "\n" . '            '
               . '<td class="serverinfo">' . $GLOBALS['strDatabase'] . ':&nbsp;'
               . '<a href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . PMA_generate_common_url($GLOBALS['db']) . '">';
            if ($GLOBALS['cfg']['MainPageIconic']) {
                echo '<img src="' . $GLOBALS['pmaThemeImage'] . 's_db.png" width="16" height="16" border="0" alt="' . htmlspecialchars($GLOBALS['db']) . '" />';
            }
            echo htmlspecialchars($GLOBALS['db']) . '</a>' . "\n"
               . '</td>' . "\n\n";

            if (!empty($GLOBALS['table'])) {
                echo '        '
                   . '<td class="serverinfo"><div></div></td>' . "\n" . '            '
                   . '<td class="serverinfo">' . $GLOBALS['strTable'] . ':&nbsp;'
                   . '<a href="' . $GLOBALS['cfg']['DefaultTabTable'] . '?' . PMA_generate_common_url($GLOBALS['db'], $GLOBALS['table']) . '">';
                if ($GLOBALS['cfg']['MainPageIconic']) {
                    echo '<img src="' . $GLOBALS['pmaThemeImage'] . 's_tbl.png" width="16" height="16" border="0" alt="' . htmlspecialchars($GLOBALS['table']) . '" />';
                }
                echo htmlspecialchars($GLOBALS['table']) . '</a>' . "\n"
                   . '</td>' . "\n\n";
            }
        }
        echo '    </tr>' . "\n" . '</table>';
    }
    /**
     * Sets a variable to remember headers have been sent
     */
    $GLOBALS['is_header_sent'] = TRUE;
}

?>
