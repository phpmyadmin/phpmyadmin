<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of VIEWs
 *
 * @todo js error when view name is empty (strFormEmpty)
 * @todo (also validate if js is disabled, after form submission?)
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

require_once './libraries/common.inc.php';

/**
 * Runs common work
 */
require './libraries/db_common.inc.php';
$url_params['goto'] = 'tbl_structure.php';
$url_params['back'] = 'view_create.php';

$response = Response::getInstance();

$view_algorithm_options = array(
    'UNDEFINED',
    'MERGE',
    'TEMPTABLE',
);

$view_with_options = array(
    'CASCADED',
    'LOCAL'
);

$view_security_options = array(
    'DEFINER',
    'INVOKER'
);

// View name is a compulsory field
if (isset($_POST['view']['name'])
    && empty($_POST['view']['name'])
) {
    $message = PhpMyAdmin\Message::error(__('View name can not be empty!'));
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
                . PhpMyAdmin\Util::backquote($_POST['view']['definer']);
        } else {
            $arr = explode('@', $_POST['view']['definer']);
            $sql_query .= $sep . 'DEFINER=' . PhpMyAdmin\Util::backquote($arr[0]);
            $sql_query .= '@' . PhpMyAdmin\Util::backquote($arr[1]) . ' ';
        }
    }

    if (isset($_POST['view']['sql_security'])) {
        if (in_array($_POST['view']['sql_security'], $view_security_options)) {
            $sql_query .= $sep . ' SQL SECURITY '
                . $_POST['view']['sql_security'];
        }
    }

    $sql_query .= $sep . ' VIEW '
        . PhpMyAdmin\Util::backquote($_POST['view']['name']);

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

    if (!$GLOBALS['dbi']->tryQuery($sql_query)) {
        if (! isset($_POST['ajax_dialog'])) {
            $message = PhpMyAdmin\Message::rawError($GLOBALS['dbi']->getError());
            return;
        }

        $response->addJSON(
            'message',
            PhpMyAdmin\Message::error(
                "<i>" . htmlspecialchars($sql_query) . "</i><br /><br />"
                . $GLOBALS['dbi']->getError()
            )
        );
        $response->setRequestStatus(false);
        exit;
    }

    // If different column names defined for VIEW
    $view_columns = array();
    if (isset($_POST['view']['column_names'])) {
        $view_columns = explode(',', $_POST['view']['column_names']);
    }

    $column_map = $GLOBALS['dbi']->getColumnMapFromSql(
        $_POST['view']['as'], $view_columns
    );

    $systemDb = $GLOBALS['dbi']->getSystemDatabase();
    $pma_transformation_data = $systemDb->getExistingTransformationData(
        $GLOBALS['db']
    );

    if ($pma_transformation_data !== false) {

        // SQL for store new transformation details of VIEW
        $new_transformations_sql = $systemDb->getNewTransformationDataSql(
            $pma_transformation_data, $column_map,
            $_POST['view']['name'], $GLOBALS['db']
        );

        // Store new transformations
        if ($new_transformations_sql != '') {
            $GLOBALS['dbi']->tryQuery($new_transformations_sql);
        }

    }
    unset($pma_transformation_data);

    if (! isset($_POST['ajax_dialog'])) {
        $message = PhpMyAdmin\Message::success();
        include 'tbl_structure.php';
    } else {
        $response->addJSON(
            'message',
            PhpMyAdmin\Util::getMessage(
                PhpMyAdmin\Message::success(),
                $sql_query
            )
        );
        $response->setRequestStatus(true);
    }

    exit;
}

$sql_query = ! empty($_POST['sql_query']) ? $_POST['sql_query'] : '';

// prefill values if not already filled from former submission
$view = array(
    'operation' => 'create',
    'or_replace' => '',
    'algorithm' => '',
    'definer' => '',
    'sql_security' => '',
    'name' => '',
    'column_names' => '',
    'as' => $sql_query,
    'with' => '',
    'algorithm' => '',
);

// Used to prefill the fields when editing a view
if (isset($_GET['db']) && isset($_GET['table'])) {
    $item = $GLOBALS['dbi']->fetchSingleRow(
        sprintf(
            "SELECT `VIEW_DEFINITION`, `CHECK_OPTION`, `DEFINER`,
            `SECURITY_TYPE`
            FROM `INFORMATION_SCHEMA`.`VIEWS`
            WHERE TABLE_SCHEMA='%s'
            AND TABLE_NAME='%s';",
            $GLOBALS['dbi']->escapeString($_GET['db']),
            $GLOBALS['dbi']->escapeString($_GET['table'])
        )
    );
    $createView = $GLOBALS['dbi']->getTable($_GET['db'], $_GET['table'])
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

}

if (Core::isValid($_POST['view'], 'array')) {
    $view = array_merge($view, $_POST['view']);
}

$url_params['db'] = $GLOBALS['db'];
$url_params['reload'] = 1;

echo Template::get('view_create')->render([
    'ajax_dialog' => isset($_POST['ajax_dialog']),
    'text_dir' => $text_dir,
    'url_params' => $url_params,
    'view' => $view,
    'view_algorithm_options' => $view_algorithm_options,
    'view_with_options' => $view_with_options,
    'view_security_options' => $view_security_options,
]);
