<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server variables
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_variables.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_variables.js');

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';

/**
 * Array of documentation links
 */
$variable_doc_links = PMA_getArrayForDocumentLinks();

/**
 * Ajax request
 */

if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
    if (isset($_REQUEST['type'])) {
        if ($_REQUEST['type'] === 'getval') {
            PMA_getAjaxReturnForGetVal($variable_doc_links);
        } else if ($_REQUEST['type'] === 'setval') {
            PMA_getAjaxReturnForSetVal($variable_doc_links);
        }
        exit;
    }
}

/**
 * Displays the sub-page heading
 */
$doc_link = PMA_Util::showMySQLDocu('server_system_variables');
$response->addHtml(PMA_getHtmlForSubPageHeader('variables', $doc_link));

/**
 * Sends the queries and buffers the results
 */
$serverVarsResult = $GLOBALS['dbi']->tryQuery('SHOW SESSION VARIABLES;');

if ($serverVarsResult !== false) {

    $serverVarsSession = array();
    while ($arr = $GLOBALS['dbi']->fetchRow($serverVarsResult)) {
        $serverVarsSession[$arr[0]] = $arr[1];
    }
    $GLOBALS['dbi']->freeResult($serverVarsResult);

    $serverVars = $GLOBALS['dbi']->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);

    /**
     * Link templates
     */
    $response->addHtml(PMA_getHtmlForLinkTemplates());

    /**
     * Displays the page
     */
    $response->addHtml(
        PMA_getHtmlForServerVariables(
            $variable_doc_links, $serverVars, $serverVarsSession
        )
    );
} else {
    /**
     * Display the error message
     */
    $response->addHTML(
        PMA_Message::error(
            sprintf(
                __('Not enough privilege to view server variables and settings. %s'),
                PMA_Util::showMySQLDocu(
                    'server-system-variables',
                    false,
                    'sysvar_show_compatibility_56'
                )
            )
        )->getDisplay()
    );
}

exit;
