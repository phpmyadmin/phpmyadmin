<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check parameters
 */
require_once './libraries/server_common.inc.php';

PMA_checkParameters(array('is_superuser', 'url_query'), false);

?>
