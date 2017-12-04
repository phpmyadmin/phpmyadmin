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
use PhpMyAdmin\Url;
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

if (empty($sql_query)) {
    $sql_query = '';
}

// View name is a compulsory field
if (isset($_REQUEST['view']['name'])
    && empty($_REQUEST['view']['name'])
) {
    $message = PhpMyAdmin\Message::error(__('View name can not be empty!'));
    $response->addJSON(
        'message',
        $message
    );
    $response->setRequestStatus(false);
    exit;
}

if (isset($_REQUEST['createview']) || isset($_REQUEST['alterview'])) {
    /**
     * Creates the view
     */
    $sep = "\r\n";

    if (isset($_REQUEST['createview'])) {
        $sql_query = 'CREATE';
        if (isset($_REQUEST['view']['or_replace'])) {
            $sql_query .= ' OR REPLACE';
        }
    } else {
        $sql_query = 'ALTER';
    }

    if (Core::isValid($_REQUEST['view']['algorithm'], $view_algorithm_options)) {
        $sql_query .= $sep . ' ALGORITHM = ' . $_REQUEST['view']['algorithm'];
    }

    if (! empty($_REQUEST['view']['definer'])) {
        if (strpos($_REQUEST['view']['definer'], '@') === false) {
            $sql_query .= $sep . 'DEFINER='
                . PhpMyAdmin\Util::backquote($_REQUEST['view']['definer']);
        } else {
            $arr = explode('@', $_REQUEST['view']['definer']);
            $sql_query .= $sep . 'DEFINER=' . PhpMyAdmin\Util::backquote($arr[0]);
            $sql_query .= '@' . PhpMyAdmin\Util::backquote($arr[1]) . ' ';
        }
    }

    if (isset($_REQUEST['view']['sql_security'])) {
        if (in_array($_REQUEST['view']['sql_security'], $view_security_options)) {
            $sql_query .= $sep . ' SQL SECURITY '
                . $_REQUEST['view']['sql_security'];
        }
    }

    $sql_query .= $sep . ' VIEW '
        . PhpMyAdmin\Util::backquote($_REQUEST['view']['name']);

    if (! empty($_REQUEST['view']['column_names'])) {
        $sql_query .= $sep . ' (' . $_REQUEST['view']['column_names'] . ')';
    }

    $sql_query .= $sep . ' AS ' . $_REQUEST['view']['as'];

    if (isset($_REQUEST['view']['with'])) {
        if (in_array($_REQUEST['view']['with'], $view_with_options)) {
            $sql_query .= $sep . ' WITH ' . $_REQUEST['view']['with']
                . '  CHECK OPTION';
        }
    }

    if (!$GLOBALS['dbi']->tryQuery($sql_query)) {
        if (! isset($_REQUEST['ajax_dialog'])) {
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
    if (isset($_REQUEST['view']['column_names'])) {
        $view_columns = explode(',', $_REQUEST['view']['column_names']);
    }

    $column_map = $GLOBALS['dbi']->getColumnMapFromSql(
        $_REQUEST['view']['as'], $view_columns
    );

    $systemDb = $GLOBALS['dbi']->getSystemDatabase();
    $pma_transformation_data = $systemDb->getExistingTransformationData(
        $GLOBALS['db']
    );

    if ($pma_transformation_data !== false) {

        // SQL for store new transformation details of VIEW
        $new_transformations_sql = $systemDb->getNewTransformationDataSql(
            $pma_transformation_data, $column_map,
            $_REQUEST['view']['name'], $GLOBALS['db']
        );

        // Store new transformations
        if ($new_transformations_sql != '') {
            $GLOBALS['dbi']->tryQuery($new_transformations_sql);
        }

    }
    unset($pma_transformation_data);

    if (! isset($_REQUEST['ajax_dialog'])) {
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
);

if (Core::isValid($_REQUEST['view'], 'array')) {
    $view = array_merge($view, $_REQUEST['view']);
}

$url_params['db'] = $GLOBALS['db'];
$url_params['reload'] = 1;

echo Template::get('view_create')->render([
    'ajax_dialog'           => isset($_REQUEST['ajax_dialog']),
    'text_dir'              => $text_dir,
    'url_params'            => $url_params,
    'view'                  => $view,
]);
