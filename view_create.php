<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of VIEWs
 *
 * @todo js error when view name is empty (strFormEmpty)
 * @todo (also validate if js is disabled, after form submission?)
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $text_dir;

require_once ROOT_PATH . 'libraries/common.inc.php';
require ROOT_PATH . 'libraries/db_common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

$url_params['goto'] = 'tbl_structure.php';
$url_params['back'] = 'view_create.php';

/** @var Template $template */
$template = $containerBuilder->get('template');

$view_algorithm_options = [
    'UNDEFINED',
    'MERGE',
    'TEMPTABLE',
];

$view_with_options = [
    'CASCADED',
    'LOCAL',
];

$view_security_options = [
    'DEFINER',
    'INVOKER',
];

// View name is a compulsory field
if (isset($_POST['view']['name'])
    && empty($_POST['view']['name'])
) {
    $message = Message::error(__('View name can not be empty!'));
    $response->addJSON(
        'message',
        $message
    );
    $response->setRequestStatus(false);
    exit;
}

if (isset($_POST['createview']) || isset($_POST['alterview'])) {
    /**
     * Creates the view
     */
    $sep = "\r\n";

    if (isset($_POST['createview'])) {
        $sql_query = 'CREATE';
        if (isset($_POST['view']['or_replace'])) {
            $sql_query .= ' OR REPLACE';
        }
    } else {
        $sql_query = 'ALTER';
    }

    if (Core::isValid($_POST['view']['algorithm'], $view_algorithm_options)) {
        $sql_query .= $sep . ' ALGORITHM = ' . $_POST['view']['algorithm'];
    }

    if (! empty($_POST['view']['definer'])) {
        if (strpos($_POST['view']['definer'], '@') === false) {
            $sql_query .= $sep . 'DEFINER='
                . Util::backquote($_POST['view']['definer']);
        } else {
            $arr = explode('@', $_POST['view']['definer']);
            $sql_query .= $sep . 'DEFINER=' . Util::backquote($arr[0]);
            $sql_query .= '@' . Util::backquote($arr[1]) . ' ';
        }
    }

    if (isset($_POST['view']['sql_security']) && in_array($_POST['view']['sql_security'], $view_security_options)) {
        $sql_query .= $sep . ' SQL SECURITY '
            . $_POST['view']['sql_security'];
    }

    $sql_query .= $sep . ' VIEW '
        . Util::backquote($_POST['view']['name']);

    if (! empty($_POST['view']['column_names'])) {
        $sql_query .= $sep . ' (' . $_POST['view']['column_names'] . ')';
    }

    $sql_query .= $sep . ' AS ' . $_POST['view']['as'];

    if (isset($_POST['view']['with'])) {
        if (in_array($_POST['view']['with'], $view_with_options)) {
            $sql_query .= $sep . ' WITH ' . $_POST['view']['with']
                . '  CHECK OPTION';
        }
    }

    if (! $dbi->tryQuery($sql_query)) {
        if (! isset($_POST['ajax_dialog'])) {
            $message = Message::rawError($dbi->getError());
            return;
        }

        $response->addJSON(
            'message',
            Message::error(
                "<i>" . htmlspecialchars($sql_query) . "</i><br><br>"
                . $dbi->getError()
            )
        );
        $response->setRequestStatus(false);
        exit;
    }

    // If different column names defined for VIEW
    $view_columns = [];
    if (isset($_POST['view']['column_names'])) {
        $view_columns = explode(',', $_POST['view']['column_names']);
    }

    $column_map = $dbi->getColumnMapFromSql(
        $_POST['view']['as'],
        $view_columns
    );

    $systemDb = $dbi->getSystemDatabase();
    $pma_transformation_data = $systemDb->getExistingTransformationData(
        $GLOBALS['db']
    );

    if ($pma_transformation_data !== false) {
        // SQL for store new transformation details of VIEW
        $new_transformations_sql = $systemDb->getNewTransformationDataSql(
            $pma_transformation_data,
            $column_map,
            $_POST['view']['name'],
            $GLOBALS['db']
        );

        // Store new transformations
        if ($new_transformations_sql != '') {
            $dbi->tryQuery($new_transformations_sql);
        }
    }
    unset($pma_transformation_data);

    if (! isset($_POST['ajax_dialog'])) {
        $message = Message::success();
        include ROOT_PATH . 'tbl_structure.php';
    } else {
        $response->addJSON(
            'message',
            Util::getMessage(
                Message::success(),
                $sql_query
            )
        );
        $response->setRequestStatus(true);
    }

    exit;
}

$sql_query = ! empty($_POST['sql_query']) ? $_POST['sql_query'] : '';

// prefill values if not already filled from former submission
$view = [
    'operation' => 'create',
    'or_replace' => '',
    'algorithm' => '',
    'definer' => '',
    'sql_security' => '',
    'name' => '',
    'column_names' => '',
    'as' => $sql_query,
    'with' => '',
];

// Used to prefill the fields when editing a view
if (isset($_GET['db']) && isset($_GET['table'])) {
    $item = $dbi->fetchSingleRow(
        sprintf(
            "SELECT `VIEW_DEFINITION`, `CHECK_OPTION`, `DEFINER`,
            `SECURITY_TYPE`
            FROM `INFORMATION_SCHEMA`.`VIEWS`
            WHERE TABLE_SCHEMA='%s'
            AND TABLE_NAME='%s';",
            $dbi->escapeString($_GET['db']),
            $dbi->escapeString($_GET['table'])
        )
    );
    $createView = $dbi->getTable($_GET['db'], $_GET['table'])
        ->showCreate();

    // CREATE ALGORITHM=<ALGORITHM> DE...
    $parts = explode(" ", substr($createView, 17));
    $item['ALGORITHM'] = $parts[0];

    $view['operation'] = 'alter';
    $view['definer'] = $item['DEFINER'];
    $view['sql_security'] = $item['SECURITY_TYPE'];
    $view['name'] = $_GET['table'];
    $view['as'] = $item['VIEW_DEFINITION'];
    $view['with'] = $item['CHECK_OPTION'];
    $view['algorithm'] = $item['ALGORITHM'];

    if (empty($view['as']) && is_string($createView)) {
        $parser = new Parser($createView);
        /**
         * @var CreateStatement $stmt
         */
        $stmt = $parser->statements[0];
        $view['as'] = isset($stmt->body) ? TokensList::build($stmt->body) : $view['as'];
    }
}

if (Core::isValid($_POST['view'], 'array')) {
    $view = array_merge($view, $_POST['view']);
}

$url_params['db'] = $GLOBALS['db'];
$url_params['reload'] = 1;

echo $template->render('view_create', [
    'ajax_dialog' => isset($_POST['ajax_dialog']),
    'text_dir' => $text_dir,
    'url_params' => $url_params,
    'view' => $view,
    'view_algorithm_options' => $view_algorithm_options,
    'view_with_options' => $view_with_options,
    'view_security_options' => $view_security_options,
]);
