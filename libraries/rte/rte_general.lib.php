<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * General functions.
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Message;
use PMA\libraries\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check result
 *
 * @param resource|bool $result          Query result
 * @param string        $error           Error to add
 * @param string        $createStatement Query
 * @param array         $errors          Errors
 *
 * @return array
 */
function checkResult($result, $error, $createStatement, $errors)
{
    if ($result) {
        return $errors;
    }

    // OMG, this is really bad! We dropped the query,
    // failed to create a new one
    // and now even the backup query does not execute!
    // This should not happen, but we better handle
    // this just in case.
    $errors[] = $error . '<br />'
        . __('The backed up query was:')
        . "\"" . htmlspecialchars($createStatement) . "\"" . '<br />'
        . __('MySQL said: ') . $GLOBALS['dbi']->getError(null);

    return $errors;
}

/**
 * Send TRI or EVN editor via ajax or by echoing.
 *
 * @param string $type      TRI or EVN
 * @param string $mode      Editor mode 'add' or 'edit'
 * @param array  $item      Data necessary to create the editor
 * @param string $title     Title of the editor
 * @param string $db        Database
 * @param string $operation Operation 'change' or ''
 *
 * @return void
 */
function PMA_RTE_sendEditor($type, $mode, $item, $title, $db, $operation = null)
{
    $response = Response::getInstance();
    if ($item !== false) {
        // Show form
        if ($type == 'TRI') {
            $editor = PMA_TRI_getEditorForm($mode, $item);
        } else { // EVN
            $editor = PMA_EVN_getEditorForm($mode, $operation, $item);
        }
        if ($response->isAjax()) {
            $response->addJSON('message', $editor);
            $response->addJSON('title', $title);
        } else {
            echo "\n\n<h2>$title</h2>\n\n$editor";
            unset($_POST);
        }
        exit;
    } else {
        $message  = __('Error in processing request:') . ' ';
        $message .= sprintf(
            PMA_RTE_getWord('not_found'),
            htmlspecialchars(PMA\libraries\Util::backquote($_REQUEST['item_name'])),
            htmlspecialchars(PMA\libraries\Util::backquote($db))
        );
        $message = Message::error($message);
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            $response->addJSON('message', $message);
            exit;
        } else {
            $message->display();
        }
    }
}
