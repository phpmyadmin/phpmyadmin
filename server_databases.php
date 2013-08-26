<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server databases
 *
 * @package PhpMyAdmin
 */

/**
 * Does the common work
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/server_databases.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_databases.js');

if (! PMA_DRIZZLE) {
    include_once 'libraries/replication.inc.php';
} else {
    $replication_types = array();
    $replication_info = null;
}
require 'libraries/build_html_for_db.lib.php';

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'mult_btn',
    'query_type',
    'selected'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

list($sort_by, $sort_order) = PMA_getListForSortDatabase();

$dbstats    = empty($_REQUEST['dbstats']) ? 0 : 1;
$pos        = empty($_REQUEST['pos']) ? 0 : (int) $_REQUEST['pos'];


/**
 * Drops multiple databases
 */
// workaround for IE behavior (it returns some coordinates based on where
// the mouse was on the Drop image):
if (isset($_REQUEST['drop_selected_dbs_x'])) {
    $_REQUEST['drop_selected_dbs'] = true;
}

if ((isset($_REQUEST['drop_selected_dbs']) || isset($_REQUEST['query_type']))
    && ($is_superuser || $cfg['AllowUserDropDatabase'])
) {
    if (! isset($_REQUEST['selected_dbs']) && ! isset($_REQUEST['query_type'])) {
        $message = PMA_Message::error(__('No databases selected.'));
    } else {
        $action = 'server_databases.php';
        $submit_mult = 'drop_db';
        $err_url = 'server_databases.php?' . PMA_URL_getCommon();
        if (isset($_REQUEST['selected_dbs'])
            && !isset($_REQUEST['is_js_confirmed'])
        ) {
            $selected_db = $_REQUEST['selected_dbs'];
        }
        if (isset($_REQUEST['is_js_confirmed'])) {
            $_REQUEST = array(
                'query_type' => $submit_mult,
                'selected' => $_REQUEST['selected_dbs'],
                'mult_btn' => __('Yes'),
                'db' => $GLOBALS['db'],
                'table' => $GLOBALS['table']);
        }
        include 'libraries/mult_submits.inc.php';
        unset($action, $submit_mult, $err_url, $selected_db, $GLOBALS['db']);
        if (empty($message)) {
            if ($mult_btn == __('Yes')) {
                $number_of_databases = count($selected);
            } else {
                $number_of_databases = 0;
            }
            $message = PMA_Message::success(
                _ngettext(
                    '%1$d database has been dropped successfully.',
                    '%1$d databases have been dropped successfully.',
                    $number_of_databases
                )
            );
            $message->addParam($number_of_databases);
        }
    }
    if ($GLOBALS['is_ajax_request'] && $message instanceof PMA_Message) {
        $response = PMA_Response::getInstance();
        $response->isSuccess($message->isSuccess());
        $response->addJSON('message', $message);
        exit;
    }
}

/**
 * Displays the sub-page heading
 */
$header_type = $dbstats ? "database_statistics" : "databases";
$response->addHTML(PMA_getHtmlForSubPageHeader($header_type));

/**
 * Displays For Create database.
 */
$html = '';
if ($cfg['ShowCreateDb']) {
    $html .= '<ul><li id="li_create_database" class="no_bullets">' . "\n";
    include 'libraries/display_create_database.lib.php';
    $html .= '    </li>' . "\n";
    $html .= '</ul>' . "\n";
}

/**
 * Gets the databases list
 */
if ($server > 0) {
    $databases = $GLOBALS['dbi']->getDatabasesFull(
        null, $dbstats, null, $sort_by, $sort_order, $pos, true
    );
    $databases_count = count($GLOBALS['pma']->databases);
} else {
    $databases_count = 0;
}


/**
 * Displays the page
 */
if ($databases_count > 0) {
    $html .= PMA_getHtmlForDatabase(
        $databases, 
        $databases_count, 
        $pos, 
        $dbstats, 
        $sort_by, 
        $sort_order, 
        $is_superuser, 
        $cfg, 
        $replication_types, 
        $replication_info, 
        $url_query
    );
} else {
    $html .= __('No databases');
}
unset($databases_count);

$response->addHTML($html);

?>
