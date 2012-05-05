<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/RecentTable.class.php';
require_once 'libraries/Menu.class.php';


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

        /* remove vertical scroll bar bug in ie */ ?>
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

        echo "<noscript>\n";
        PMA_message::error(__("Javascript must be enabled past this point"))->display();
        echo "</noscript>\n";

        // offer to load user preferences from localStorage
        if ($userprefs_offer_import) {
            include_once './libraries/user_preferences.lib.php';
            PMA_userprefs_autoload_header();
        }

        // add recently used table and reload the navigation
        if (strlen($GLOBALS['table']) && $GLOBALS['cfg']['LeftRecentTable'] > 0) {
            PMA_addRecentTable($GLOBALS['db'], $GLOBALS['table']);
        }

        if (! defined('PMA_DISPLAY_HEADING')) {
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
            PMA_Menu::getInstance()->display();
        }
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
