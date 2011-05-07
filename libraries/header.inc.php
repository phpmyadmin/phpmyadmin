<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/common.inc.php';
require_once './libraries/RecentTable.class.php';


/**
 * This is not an Ajax request so we need to generate all this output.
 */
if (isset($GLOBALS['is_ajax_request']) && !$GLOBALS['is_ajax_request']) {

    if (empty($GLOBALS['is_header_sent'])) {

        /**
         * Gets a core script and starts output buffering work
         */
        require_once './libraries/ob.lib.php';
        PMA_outBufferPre();

        // if database storage for user preferences is transient, offer to load
        // exported settings from localStorage (detection will be done in JavaScript)
        $userprefs_offer_import = $GLOBALS['PMA_Config']->get('user_preferences') == 'session'
                && !isset($_SESSION['userprefs_autoload']);
        if ($userprefs_offer_import) {
            $GLOBALS['js_include'][] = 'config.js';
        }

        // For re-usability, moved http-headers and stylesheets
        // to a seperate file. It can now be included by header.inc.php,
        // querywindow.php.

        require_once './libraries/header_http.inc.php';
        require_once './libraries/header_meta_style.inc.php';
        require_once './libraries/header_scripts.inc.php';
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
        <?php

        // Include possible custom headers
        if (file_exists(CUSTOM_HEADER_FILE)) {
            require CUSTOM_HEADER_FILE;
        }


        // message of "Cookies required" displayed for auth_type http or config
        // note: here, the decoration won't work because without cookies,
        // our standard CSS is not operational
        if (empty($_COOKIE)) {
            PMA_Message::notice(__('Cookies must be enabled past this point.'))->display();
        }

        // offer to load user preferences from localStorage
        if ($userprefs_offer_import) {
            require_once './libraries/user_preferences.lib.php';
            PMA_userprefs_autoload_header();
        }

        if (!defined('PMA_DISPLAY_HEADING')) {
            define('PMA_DISPLAY_HEADING', 1);
        }

        /**
         * Display heading if needed. Design can be set in css file.
         */

        if (PMA_DISPLAY_HEADING && $GLOBALS['server'] > 0) {
            $server_info = (!empty($GLOBALS['cfg']['Server']['verbose'])
                            ? $GLOBALS['cfg']['Server']['verbose']
                            : $GLOBALS['cfg']['Server']['host'] . (empty($GLOBALS['cfg']['Server']['port'])
                                                                   ? ''
                                                                   : ':' . $GLOBALS['cfg']['Server']['port']
                                                                  )
                           );
            $item = '<a href="%1$s?%2$s" class="item">';
            if ($GLOBALS['cfg']['NavigationBarIconic']) {
                $separator = '        <span class="separator"><img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'item_' . $GLOBALS['text_dir'] . '.png" width="5" height="9" alt="-" /></span>' . "\n";
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
                        __('Server'),
                        's_host.png');

                if (strlen($GLOBALS['db'])) {

                    echo $separator;
                    printf($item,
                            $GLOBALS['cfg']['DefaultTabDatabase'],
                            PMA_generate_common_url($GLOBALS['db']),
                            htmlspecialchars($GLOBALS['db']),
                            __('Database'),
                            's_tbl.png');
                    // if the table is being dropped, $_REQUEST['purge'] is set
                    // (it always contains "1")
                    // so do not display the table name in upper div
                    if (strlen($GLOBALS['table']) && ! (isset($_REQUEST['purge']))) {
                        require_once './libraries/tbl_info.inc.php';

                        echo $separator;
                        printf($item,
                            $GLOBALS['cfg']['DefaultTabTable'],
                            PMA_generate_common_url($GLOBALS['db'], $GLOBALS['table']),
                            str_replace(' ', '&nbsp;', htmlspecialchars($GLOBALS['table'])),
                            (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view'] ? __('View') : __('Table')),
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

                        // add recently used table and reload the navigation
                        $result = RecentTable::getInstance()->add($GLOBALS['db'], $GLOBALS['table']);
                        if ($result === true) {
                            $GLOBALS['reload'] = true;
                            PMA_reloadNavigation();
                        } else {
                            $error = $result;
                            $error->display();
                        }
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
            }
            echo '</div>';
        }
        /**
         * Sets a variable to remember headers have been sent
         */
        $GLOBALS['is_header_sent'] = true;
} //end if(!$GLOBALS['is_ajax_request'])
else {
    if (empty($GLOBALS['is_header_sent'])) {
        require_once './libraries/header_http.inc.php';
        $GLOBALS['is_header_sent'] = true;
    }
}
?>
