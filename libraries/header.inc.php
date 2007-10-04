<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

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

    // generate title
    $title = str_replace(
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

    if (in_array('functions.js', $GLOBALS['js_include'])) {
        $js_messages['strFormEmpty'] = $GLOBALS['strFormEmpty'];
        $js_messages['strNotNumber'] = $GLOBALS['strNotNumber'];

        if (!$is_superuser && !$GLOBALS['cfg']['AllowUserDropDatabase']) {
            $js_messages['strNoDropDatabases'] = $GLOBALS['strNoDropDatabases'];
        } else {
            $js_messages['strNoDropDatabases'] = '';
        }

        if ($GLOBALS['cfg']['Confirm']) {
            $js_messages['strDoYouReally'] = $GLOBALS['strDoYouReally'];
            $js_messages['strDropDatabaseStrongWarning'] = $GLOBALS['strDropDatabaseStrongWarning'];
        } else {
            $js_messages['strDoYouReally'] = '';
            $js_messages['strDropDatabaseStrongWarning'] = '';
        }
    } elseif (in_array('indexes.js', $GLOBALS['js_include'])) {
        $js_messages['strFormEmpty'] = $GLOBALS['strFormEmpty'];
        $js_messages['strNotNumber'] = $GLOBALS['strNotNumber'];
    }

    if (in_array('server_privileges.js', $GLOBALS['js_include'])) {
        $js_messages['strHostEmpty'] = $GLOBALS['strHostEmpty'];
        $js_messages['strUserEmpty'] = $GLOBALS['strUserEmpty'];
        $js_messages['strPasswordEmpty'] = $GLOBALS['strPasswordEmpty'];
        $js_messages['strPasswordNotSame'] = $GLOBALS['strPasswordNotSame'];
    }

    $GLOBALS['js_include'][] = 'tooltip.js';
    ?>
    <script type="text/javascript">
    // <![CDATA[
    // Updates the title of the frameset if possible (ns4 does not allow this)
    if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown'
        && typeof(parent.document.title) == 'string') {
        parent.document.title = '<?php echo PMA_sanitize(PMA_escapeJsString($title)); ?>';
    }

    var PMA_messages = new Array();
    <?php
    foreach ($js_messages as $name => $js_message) {
        echo "PMA_messages['" . $name . "'] = '" . PMA_escapeJsString($js_message) . "';\n";
    }
    ?>
    // ]]>
    </script>

    <?php
    foreach ($GLOBALS['js_include'] as $js_script_file) {
        echo '<script src="./js/' . $js_script_file . '" type="text/javascript"></script>' . "\n";
    }

    // Reloads the navigation frame via JavaScript if required
    PMA_reloadNavigation();
    ?>
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
                    $comment = PMA_getDbComment($GLOBALS['db']);
                    /**
                     * Displays table comment
                     */
                    if (! empty($comment)) {
                        echo '<span class="table_comment"'
                           . ' id="span_table_comment">&quot;'
                           . htmlspecialchars($comment)
                           . '&quot;</span>' . "\n";
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
