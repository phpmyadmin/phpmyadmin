<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server collations page
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * Does the common work
 */
require_once 'libraries/server_common.inc.php';

require_once 'libraries/server_collations.lib.php';

/**
 * Includes the required charset library
 */
require_once 'libraries/mysql_charsets.inc.php';

$response = PMA_Response::getInstance();

$response->addHTML(PMA_getHtmlForSubPageHeader('collations'));
$response->addHTML(
    PMA_getHtmlForCharsets(
        $mysql_charsets,
        $mysql_collations,
        $mysql_charsets_descriptions,
        $mysql_default_collations,
        $mysql_collations_available
    )
);

?>
