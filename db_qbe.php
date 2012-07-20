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
require_once 'libraries/DbQbe.class.php';
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();

/**
 * Sets globals from $_POST patterns, for Or* variables 
 * (additional criteria lines)
 */

$post_patterns = array(
    '/^Or/i'
);
foreach (array_keys($_POST) as $post_key) {
    foreach ($post_patterns as $one_post_pattern) {
        if (preg_match($one_post_pattern, $post_key)) {
            $GLOBALS[$post_key] = $_POST[$post_key];
        }
    }
}

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
    PMA_Message::notice(
        sprintf(
            __('Switch to %svisual builder%s'),
            '<a href="' . $url . '">',
            '</a>'
        )
    )->display();
}
$response->addHTML($db_qbe->getSelectionForm($cfgRelation));
?>
