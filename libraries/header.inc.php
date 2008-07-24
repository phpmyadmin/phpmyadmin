<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/common.inc.php';

if (empty($GLOBALS['is_header_sent'])) {

    /**
     * Gets a core script and starts output buffering work
     */
    require_once './libraries/common.inc.php';
    require_once './libraries/ob.lib.php';
    PMA_outBufferPre();

    // garvin: For re-usability, moved http-headers and stylesheets
    // to a seperate file. It can now be included by header.inc.php,
    // querywindow.php.

    require_once './libraries/header_http.inc.php';
    require_once './libraries/header_meta_style.inc.php';

    // Cross-framing protection
    if ( false === $GLOBALS['cfg']['AllowThirdPartyFraming']) {
    ?>
    <script type="text/javascript">
    try {
        // can't access this if on a different domain
        var topdomain = top.document.domain;
        // double-check just for sure
        if (topdomain != self.document.domain) {
            alert("Redirecting...");
            top.location.replace(self.document.URL.substring(0, self.document.URL.lastIndexOf("/")+1));
        }
    }
    catch(e) {
            alert("Redirecting... (error: " + e);
            top.location.replace(self.document.URL.substring(0, self.document.URL.lastIndexOf("/")+1));
    }

    </script>
    <?php
    }
    // generate title
    $title     = str_replace(
                    array(
                        '@HTTP_HOST@',
                        '@SERVER@',
                        '@VERBOSE@',
                        '@VSERVER@',
                        '@DATABASE@',
                        '@TABLE@',
                        '@PHPMYADMIN@',
                        ),
                    array(
                        PMA_getenv('HTTP_HOST') ? PMA_getenv('HTTP_HOST') : '',
                        isset($GLOBALS['cfg']['Server']['host']) ? $GLOBALS['cfg']['Server']['host'] : '',
                        isset($GLOBALS['cfg']['Server']['verbose']) ? $GLOBALS['cfg']['Server']['verbose'] : '',
                        !empty($GLOBALS['cfg']['Server']['verbose']) ? $GLOBALS['cfg']['Server']['verbose'] : (isset($GLOBALS['cfg']['Server']['host']) ? $GLOBALS['cfg']['Server']['host'] : ''),
                        $GLOBALS['db'],
                        $GLOBALS['table'],
                        'phpMyAdmin ' . PMA_VERSION,
                        ),
                    !empty($GLOBALS['table']) ? $GLOBALS['cfg']['TitleTable'] :
                    (!empty($GLOBALS['db']) ? $GLOBALS['cfg']['TitleDatabase'] :
                    (!empty($GLOBALS['cfg']['Server']['host']) ? $GLOBALS['cfg']['TitleServer'] :
                    $GLOBALS['cfg']['TitleDefault']))
                    );
    // here, the function does not exist with this configuration: $cfg['ServerDefault'] = 0;
    $is_superuser    = function_exists('PMA_isSuperuser') && PMA_isSuperuser();
    ?>
    <script type="text/javascript">
    // <![CDATA[
    // Updates the title of the frameset if possible (ns4 does not allow this)
    if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown'
        && typeof(parent.document.title) == 'string') {
        parent.document.title = '<?php echo PMA_sanitize(str_replace('\'', '\\\'', $title)); ?>';
    }
    <?php
    // Add some javascript instructions if required
    if (isset($js_to_run) && $js_to_run == 'functions.js') {
        echo "\n";
        ?>
    // js form validation stuff
    var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strFormEmpty']); ?>';
    var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotNumber']); ?>';
    var noDropDbMsg = '<?php echo (!$is_superuser && !$GLOBALS['cfg']['AllowUserDropDatabase'])
        ? str_replace('\'', '\\\'', $GLOBALS['strNoDropDatabases']) : ''; ?>';
    var confirmMsg  = '<?php echo(($GLOBALS['cfg']['Confirm']) ? str_replace('\'', '\\\'', $GLOBALS['strDoYouReally']) : ''); ?>';
    var confirmMsgDropDB  = '<?php echo(($GLOBALS['cfg']['Confirm']) ? str_replace('\'', '\\\'', $GLOBALS['strDropDatabaseStrongWarning']) : ''); ?>';
    // ]]>
    </script>
    <script src="./js/functions.js" type="text/javascript"></script>
        <?php
    } elseif (isset($js_to_run) && $js_to_run == 'user_password.js') {
        echo "\n";
        ?>
    // js form validation stuff
    var jsHostEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strHostEmpty']); ?>';
    var jsUserEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strUserEmpty']); ?>';
    var jsPasswordEmpty   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordEmpty']); ?>';
    var jsPasswordNotSame = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordNotSame']); ?>';
    // ]]>
    </script>
    <script src="./js/user_password.js" type="text/javascript"></script>
        <?php
    } elseif (isset($js_to_run) && $js_to_run == 'server_privileges.js') {
        echo "\n";
        ?>
    // js form validation stuff
    var jsHostEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strHostEmpty']); ?>';
    var jsUserEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strUserEmpty']); ?>';
    var jsPasswordEmpty   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordEmpty']); ?>';
    var jsPasswordNotSame = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordNotSame']); ?>';
    // ]]>
    </script>
    <script src="./js/server_privileges.js" type="text/javascript"></script>
    <script src="./js/functions.js" type="text/javascript"></script>
        <?php
    } elseif (isset($js_to_run) && $js_to_run == 'indexes.js') {
        echo "\n";
        ?>
    // js index validation stuff
    var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strFormEmpty']); ?>';
    var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotNumber']); ?>';
    // ]]>
    </script>
    <script src="./js/indexes.js" type="text/javascript"></script>
        <?php
    } elseif (isset($js_to_run) && $js_to_run == 'tbl_change.js') {
        echo "\n";
        ?>
    // ]]>
    </script>
    <script src="./js/tbl_change.js" type="text/javascript"></script>
        <?php
    } else {
        echo "\n";
        ?>
    // ]]>
    </script>
        <?php
    }
    echo "\n";

    // Reloads the navigation frame via JavaScript if required
    PMA_reloadNavigation();
    ?>
    <script src="./js/tooltip.js" type="text/javascript"></script>
    <meta name="OBGZip" content="<?php echo ($GLOBALS['cfg']['OBGzip'] ? 'true' : 'false'); ?>" />
    <?php /* remove vertical scroll bar bug in ie */ ?>
    <!--[if IE 6]>
    <style type="text/css">
    /* <![CDATA[ */
    html {
        overflow-y: scroll;
    }
    /* ]]> */
    </style>
    <![endif]-->
</head>

<body>
<div id="TooltipContainer" onmouseover="holdTooltip();" onmouseout="swapTooltip('default');"></div>
    <?php

    // Include possible custom headers
    if (file_exists('./config.header.inc.php')) {
        require './config.header.inc.php';
    }


    // message of "Cookies required" displayed for auth_type http or config
    // note: here, the decoration won't work because without cookies,
    // our standard CSS is not operational
    if (empty($_COOKIE)) {
         echo '<div class="notice">' . $GLOBALS['strCookiesRequired'] . '</div>' . "\n";
    }

    if (!defined('PMA_DISPLAY_HEADING')) {
        define('PMA_DISPLAY_HEADING', 1);
    }

    /**
     * Display heading if needed. Design can be set in css file.
     */

    if (PMA_DISPLAY_HEADING) {
        $server_info = (!empty($GLOBALS['cfg']['Server']['verbose'])
                        ? $GLOBALS['cfg']['Server']['verbose']
                        : $GLOBALS['cfg']['Server']['host'] . (empty($GLOBALS['cfg']['Server']['port'])
                                                               ? ''
                                                               : ':' . $GLOBALS['cfg']['Server']['port']
                                                              )
                       );
        $item = '<a href="%1$s?%2$s" class="item">';
        if ($GLOBALS['cfg']['NavigationBarIconic']) {
            $separator = '        <span class="separator"><img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'item_ltr.png" width="5" height="9" alt="-" /></span>' . "\n";
            $item .= '        <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . '%5$s" width="16" height="16" alt="" /> ' . "\n";
        } else {
            $separator = '        <span class="separator"> - </span>' . "\n";
        }

        if ($GLOBALS['cfg']['NavigationBarIconic'] !== true) {
            $item .= '%4$s: ';
        }
        $item .= '%3$s</a>' . "\n";

        echo '<div id="serverinfo">' . "\n";
        printf($item,
                $GLOBALS['cfg']['DefaultTabServer'],
                PMA_generate_common_url(),
                htmlspecialchars($server_info),
                $GLOBALS['strServer'],
                's_host.png');

        if (strlen($GLOBALS['db'])) {

            echo $separator;
            printf($item,
                    $GLOBALS['cfg']['DefaultTabDatabase'],
                    PMA_generate_common_url($GLOBALS['db']),
                    htmlspecialchars($GLOBALS['db']),
                    $GLOBALS['strDatabase'],
                    's_db.png');

            if (strlen($GLOBALS['table'])) {
                require_once './libraries/tbl_info.inc.php';

                echo $separator;
                printf($item,
                        $GLOBALS['cfg']['DefaultTabTable'],
                        PMA_generate_common_url($GLOBALS['db'], $GLOBALS['table']),
                        str_replace(' ', '&nbsp;', htmlspecialchars($GLOBALS['table'])),
                        (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view'] ? $GLOBALS['strView'] : $GLOBALS['strTable']),
                        (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view'] ? 'b_views' : 's_tbl') . '.png');

                /**
                 * Displays table comment
                 * @uses $show_comment from libraries/tbl_info.inc.php
                 * @uses $GLOBALS['avoid_show_comment'] from tbl_relation.php
                 */
                if (!empty($show_comment) && !isset($GLOBALS['avoid_show_comment'])) {
                    if (strstr($show_comment, '; InnoDB free')) {
                        $show_comment = preg_replace('@; InnoDB free:.*?$@', '', $show_comment);
                    }
                    echo '<span class="table_comment" id="span_table_comment">'
                        .'&quot;' . htmlspecialchars($show_comment)
                        .'&quot;</span>' . "\n";
                } // end if
            } else {
                // no table selected, display database comment if present
                /**
                 * Settings for relations stuff
                 */
                require_once './libraries/relation.lib.php';
                $cfgRelation = PMA_getRelationsParam();

                // Get additional information about tables for tooltip is done
                // in libraries/db_info.inc.php only once
                if ($cfgRelation['commwork']) {
                    $comment = PMA_getComments($GLOBALS['db']);

                    /**
                     * Displays table comment
                     */
                    if (is_array($comment) && ! empty($comment)) {
                        echo '<span class="table_comment"'
                            .' id="span_table_comment">&quot;'
                            .htmlspecialchars(implode(' ', $comment))
                            .'&quot;</span>' . "\n";
                    } // end if
                }
            }
        }
        echo '</div>';

    }
    /**
     * Sets a variable to remember headers have been sent
     */
    $GLOBALS['is_header_sent'] = true;
}
?>
