<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * View manipulations
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $sql_query, $url_query;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

$pma_table = new Table($GLOBALS['table'], $GLOBALS['db']);

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('table/operations.js');

/** @var Template $template */
$template = $containerBuilder->get('template');

/**
 * Runs common work
 */
require ROOT_PATH . 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=view_operations.php&amp;back=view_operations.php';
$url_params['goto'] = $url_params['back'] = 'view_operations.php';

/** @var Relation $relation */
$relation = $containerBuilder->get('relation');
$operations = new Operations($dbi, $relation);

/**
 * Updates if required
 */
$_message = new Message();
$_type = 'success';
if (isset($_POST['submitoptions'])) {
    if (isset($_POST['new_name'])) {
        if ($pma_table->rename($_POST['new_name'])) {
            $_message->addText($pma_table->getLastMessage());
            $result = true;
            $GLOBALS['table'] = $pma_table->getName();
            /* Force reread after rename */
            $pma_table->getStatusInfo(null, true);
            $reload = true;
        } else {
            $_message->addText($pma_table->getLastError());
            $result = false;
        }
    }

    $warning_messages = $operations->getWarningMessagesArray();
}

if (isset($result)) {
    // set to success by default, because result set could be empty
    // (for example, a table rename)
    if (empty($_message->getString())) {
        if ($result) {
            $_message->addText(
                __('Your SQL query has been executed successfully.')
            );
        } else {
            $_message->addText(__('Error'));
        }
        // $result should exist, regardless of $_message
        $_type = $result ? 'success' : 'error';
    }
    if (! empty($warning_messages)) {
        $_message->addMessagesString($warning_messages);
        $_message->isError(true);
        unset($warning_messages);
    }
    echo Util::getMessage(
        $_message,
        $sql_query,
        $_type
    );
}
unset($_message, $_type);

$url_params['goto'] = 'view_operations.php';
$url_params['back'] = 'view_operations.php';

$drop_view_url_params = array_merge(
    $url_params,
    [
        'sql_query' => 'DROP VIEW ' . Util::backquote($GLOBALS['table']),
        'goto' => 'tbl_structure.php',
        'reload' => '1',
        'purge' => '1',
        'message_to_show' => sprintf(
            __('View %s has been dropped.'),
            $GLOBALS['table']
        ),
        'table' => $GLOBALS['table'],
    ]
);

echo $template->render('table/operations/view', [
    'db' => $GLOBALS['db'],
    'table' => $GLOBALS['table'],
    'delete_data_or_table_link' => $operations->getDeleteDataOrTablelink(
        $drop_view_url_params,
        'DROP VIEW',
        __('Delete the view (DROP)'),
        'drop_view_anchor'
    ),
]);
