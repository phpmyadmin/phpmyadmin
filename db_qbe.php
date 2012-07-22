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
$response = PMA_Response::getInstance();

// Gets the relation settings
$cfgRelation = PMA_getRelationsParam();

// create new qbe search instance
$db_qbe = new PMA_DBQbe($GLOBALS['db']);

/**
 * Displays the Query by example form
 */
if ($cfgRelation['designerwork']) {
    $url = 'pmd_general.php' . PMA_generate_common_url(
        array_merge(
            $url_params,
            array('query' => 1)
        )
    );
    $response->addHTML(PMA_Message::notice(
        sprintf(
            __('Switch to %svisual builder%s'),
            '<a href="' . $url . '">',
            '</a>'
        )
    ));//->display();
}
$response->addHTML($db_qbe->getSelectionForm($cfgRelation));
?>
