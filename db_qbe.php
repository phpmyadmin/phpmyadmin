<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * query by example the whole database
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/DBQbe.class.php';
require_once 'libraries/bookmark.lib.php';
require_once 'libraries/sql.lib.php';

$response = PMA_Response::getInstance();

// Gets the relation settings
$cfgRelation = PMA_getRelationsParam();

/**
 * A query has been submitted -> (maybe) execute it
 */
$message_to_display = false;
if (isset($_REQUEST['submit_sql']) && ! empty($sql_query)) {
    if (! preg_match('@^SELECT@i', $sql_query)) {
        $message_to_display = true;
    } else {
        $goto      = 'db_sql.php';

        // Parse and analyze the query
        include_once 'libraries/parse_analyze.inc.php';

        PMA_executeQueryAndSendQueryResponse(
            $analyzed_sql_results, false, $_REQUEST['db'], null, null, null, null,
            false, null, null, null, null, $goto, $pmaThemeImage, null, null, null,
            $sql_query, null, null
        );
    }
}

$sub_part  = '_qbe';
require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_qbe.php';
$url_params['goto'] = 'db_qbe.php';
require 'libraries/db_info.inc.php';

if ($message_to_display) {
    PMA_Message::error(__('You have to choose at least one column to display'))->display();
}
unset($message_to_display);

// create new qbe search instance
$db_qbe = new PMA_DBQbe($GLOBALS['db']);

/**
 * Displays the Query by example form
 */
if ($cfgRelation['designerwork']) {
    $url = 'pmd_general.php' . PMA_URL_getCommon(
        array_merge(
            $url_params,
            array('query' => 1)
        )
    );
    $response->addHTML(
        PMA_Message::notice(
            sprintf(
                __('Switch to %svisual builder%s'),
                '<a href="' . $url . '">',
                '</a>'
            )
        )
    );
}
$response->addHTML($db_qbe->getSelectionForm($cfgRelation));
?>
