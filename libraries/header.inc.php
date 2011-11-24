<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/common.inc.php';
require_once './libraries/RecentTable.class.php';


/**
 * Add recently used table and reload the navigation.
 *
 * @param string $db Database name where the table is located.
 * @param string $table The table name
 */
function PMA_addRecentTable($db, $table)
{
    $tmp_result = PMA_RecentTable::getInstance()->add($db, $table);
    if ($tmp_result === true) {
        echo '<span class="hide" id="update_recent_tables"></span>';
    } else {
        $error = $tmp_result;
        $error->display();
    }
}

/**
 * This is not an Ajax request so we need to generate all this output.
 */
if (isset($GLOBALS['is_ajax_request']) && !$GLOBALS['is_ajax_request']) {

    if (empty($GLOBALS['is_header_sent'])) {

        /**
         * Gets a core script and starts output buffering work
         */
        include_once './libraries/ob.lib.php';
        PMA_outBufferPre();

        // if database storage for user preferences is transient, offer to load
        // exported settings from localStorage (detection will be done in JavaScript)
        $userprefs_offer_import = $GLOBALS['PMA_Config']->get('user_preferences') == 'session'
                && ! isset($_SESSION['userprefs_autoload']);
        if ($userprefs_offer_import) {
            $GLOBALS['js_include'][] = 'config.js';
        }

        // For re-usability, moved http-headers and stylesheets
        // to a seperate file. It can now be included by header.inc.php,
        // querywindow.php.

        include_once './libraries/header_http.inc.php';
        include_once './libraries/header_meta_style.inc.php';
        include_once './libraries/header_scripts.inc.php';
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
            include CUSTOM_HEADER_FILE;
        }


        // message of "Cookies required" displayed for auth_type http or config
        // note: here, the decoration won't work because without cookies,
        // our standard CSS is not operational
        if (empty($_COOKIE)) {
            PMA_Message::notice(__('Cookies must be enabled past this point.'))->display();
        }

        // offer to load user preferences from localStorage
        if ($userprefs_offer_import) {
            include_once './libraries/user_preferences.lib.php';
            PMA_userprefs_autoload_header();
        }

        if (!defined('PMA_DISPLAY_HEADING')) {
            define('PMA_DISPLAY_HEADING', 1);
        }

        // pass configuration for hint tooltip display
        // (to be used by PMA_createqTip in js/functions.js)
        if (! $GLOBALS['cfg']['ShowHint']) {
            echo '<span id="no_hint" class="hide"></span>';
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
            $separator = "<span class='separator item'>&nbsp;»</span>\n";
            $item = '<a href="%1$s?%2$s" class="item">';

                if ($GLOBALS['cfg']['NavigationBarIconic'] !== true) {
                    $item .= '%4$s: ';
                }
                $item .= '%3$s</a>' . "\n";
                echo "<div id='floating_menubar'></div>\n";
                echo "<div id='serverinfo'>\n";
                if ($GLOBALS['cfg']['NavigationBarIconic']) {
                    echo PMA_getImage('s_host.png', '', array('class' => 'item')) . "\n";
                }
                printf($item,
                        $GLOBALS['cfg']['DefaultTabServer'],
                        PMA_generate_common_url(),
                        htmlspecialchars($server_info),
                        __('Server'));

                if (strlen($GLOBALS['db'])) {

                    echo $separator;
                    if ($GLOBALS['cfg']['NavigationBarIconic']) {
                        echo PMA_getImage('s_db.png', '', array('class' => 'item')) . "\n";
                    }
                    printf($item,
                            $GLOBALS['cfg']['DefaultTabDatabase'],
                            PMA_generate_common_url($GLOBALS['db']),
                            htmlspecialchars($GLOBALS['db']),
                            __('Database'));
                    // if the table is being dropped, $_REQUEST['purge'] is set to '1'
                    // so do not display the table name in upper div
                    if (strlen($GLOBALS['table']) && ! (isset($_REQUEST['purge']) && $_REQUEST['purge'] == '1')) {
                        include_once './libraries/tbl_info.inc.php';

                        echo $separator;
                        if ($GLOBALS['cfg']['NavigationBarIconic']) {
                            $icon = isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view'] ? 'b_views.png' : 's_tbl.png';
                            echo PMA_getImage($icon, '', array('class' => 'item')) . "\n";
                        }
                        printf($item,
                            $GLOBALS['cfg']['DefaultTabTable'],
                            PMA_generate_common_url($GLOBALS['db'], $GLOBALS['table']),
                            str_replace(' ', '&nbsp;', htmlspecialchars($GLOBALS['table'])),
                            (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view'] ? __('View') : __('Table')));

                        /**
                         * Displays table comment
                         */
                        if (!empty($show_comment) && ! isset($GLOBALS['avoid_show_comment'])) {
                            if (strstr($show_comment, '; InnoDB free')) {
                                $show_comment = preg_replace('@; InnoDB free:.*?$@', '', $show_comment);
                            }
                            echo '<span class="table_comment" id="span_table_comment">'
                                .'&quot;' . htmlspecialchars($show_comment)
                                .'&quot;</span>' . "\n";
                        } // end if

                        // add recently used table and reload the navigation
                        if ($GLOBALS['cfg']['LeftRecentTable'] > 0) {
                            PMA_addRecentTable($GLOBALS['db'], $GLOBALS['table']);
                        }
                    } else {
                        // no table selected, display database comment if present
                        /**
                         * Settings for relations stuff
                         */
                        include_once './libraries/relation.lib.php';
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
            echo '<div class="clearfloat"></div>';
            echo '</div>';
        }
        /**
         * Sets a variable to remember headers have been sent
         */
        $GLOBALS['is_header_sent'] = true;
//end if (!$GLOBALS['is_ajax_request'])
} else {
    if (empty($GLOBALS['is_header_sent'])) {
        include_once './libraries/header_http.inc.php';
        $GLOBALS['is_header_sent'] = true;
    }
}
?>
