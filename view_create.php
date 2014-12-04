<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of VIEWs
 *
 * @todo js error when view name is empty (strFormEmpty)
 * @todo (also validate if js is disabled, after form submission?)
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Runs common work
 */
require './libraries/db_common.inc.php';
$url_params['goto'] = 'tbl_structure.php';
$url_params['back'] = 'view_create.php';

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

    if (PMA_isValid($_REQUEST['view']['algorithm'], $view_algorithm_options)) {
        $sql_query .= $sep . ' ALGORITHM = ' . $_REQUEST['view']['algorithm'];
    }

    if (! empty($_REQUEST['view']['definer'])) {
        $sql_query .= $sep . ' DEFINER = ' . $_REQUEST['view']['definer'];
    }

    if (isset($_REQUEST['view']['sql_security'])) {
        if (in_array($_REQUEST['view']['sql_security'], $view_security_options)) {
            $sql_query .= $sep . ' SQL SECURITY '
                . $_REQUEST['view']['sql_security'];
        }
    }

    $sql_query .= $sep . ' VIEW ' . PMA_Util::backquote($_REQUEST['view']['name']);

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

    if ($GLOBALS['dbi']->tryQuery($sql_query)) {

        include_once './libraries/tbl_views.lib.php';

        // If different column names defined for VIEW
        $view_columns = array();
        if (isset($_REQUEST['view']['column_names'])) {
            $view_columns = explode(',', $_REQUEST['view']['column_names']);
        }

        $column_map = PMA_getColumnMap($_REQUEST['view']['as'], $view_columns);
        $pma_tranformation_data = PMA_getExistingTransformationData($GLOBALS['db']);

        if ($pma_tranformation_data !== false) {

            // SQL for store new transformation details of VIEW
            $new_transformations_sql = PMA_getNewTransformationDataSql(
                $pma_tranformation_data, $column_map, $_REQUEST['view']['name'],
                $GLOBALS['db']
            );

            // Store new transformations
            if ($new_transformations_sql != '') {
                $GLOBALS['dbi']->tryQuery($new_transformations_sql);
            }

        }
        unset($pma_tranformation_data);

        if (! isset($_REQUEST['ajax_dialog'])) {
            $message = PMA_Message::success();
            include 'tbl_structure.php';
        } else {
            $response = PMA_Response::getInstance();
            $response->addJSON(
                'message',
                PMA_Util::getMessage(
                    PMA_Message::success(), $sql_query
                )
            );
            $response->isSuccess(true);
        }

        exit;

    } else {
        if (! isset($_REQUEST['ajax_dialog'])) {
            $message = PMA_Message::rawError($GLOBALS['dbi']->getError());
        } else {
            $response = PMA_Response::getInstance();
            $response->addJSON(
                'message',
                PMA_Message::error(
                    "<i>" . htmlspecialchars($sql_query) . "</i><br /><br />"
                    . $GLOBALS['dbi']->getError()
                )
            );
            $response->isSuccess(false);
            exit;
        }
    }
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

if (PMA_isValid($_REQUEST['view'], 'array')) {
    $view = array_merge($view, $_REQUEST['view']);
}

$url_params['db'] = $GLOBALS['db'];
$url_params['reload'] = 1;

/**
 * Displays the page
 */
$htmlString = '<!-- CREATE VIEW options -->'
    . '<div id="div_view_options">'
    . '<form method="post" action="view_create.php">'
    . PMA_URL_getHiddenInputs($url_params)
    . '<fieldset>'
    . '<legend>'
    . (isset($_REQUEST['ajax_dialog']) ?
        __('Details') :
        ($view['operation'] == 'create' ? __('Create view') : __('Edit view'))
    )
    . '</legend>'
    . '<table class="rte_table">';

if ($view['operation'] == 'create') {
    $htmlString .= '<tr>'
        . '<td class="nowrap"><label for="or_replace">OR REPLACE</label></td>'
        . '<td><input type="checkbox" name="view[or_replace]" id="or_replace"';
    if ($view['or_replace']) {
        $htmlString .= ' checked="checked"';
    }
    $htmlString .= ' value="1" /></td></tr>';
}

$htmlString .= '<tr>'
    . '<td class="nowrap"><label for="algorithm">ALGORITHM</label></td>'
    . '<td><select name="view[algorithm]" id="algorithm">';
foreach ($view_algorithm_options as $option) {
    $htmlString .= '<option value="' . htmlspecialchars($option) . '"';
    if ($view['algorithm'] === $option) {
        $htmlString .= ' selected="selected"';
    }
    $htmlString .= '>' . htmlspecialchars($option) . '</option>';
}
$htmlString .= '</select>'
    . '</td></tr>';

$htmlString .= '<tr><td class="nowrap">' . __('Definer') . '</td>'
    . '<td><input type="text" maxlength="100" size="50" name="view[definer]"'
    . ' value="' . htmlspecialchars($view['definer']) . '" />'
    . '</td></tr>';

$htmlString .= '<tr><td class="nowrap">SQL SECURITY</td>'
    . '<td><select name="view[sql_security]">'
    . '<option value=""></option>';
foreach ($view_security_options as $option) {
    $htmlString .= '<option value="' . htmlspecialchars($option) . '"';
    if ($option == $view['sql_security']) {
        $htmlString .= ' selected="selected"';
    }
    $htmlString .= '>' . htmlspecialchars($option) . '</option>';
}
$htmlString .= '<select>'
    . '</td></tr>';

if ($view['operation'] == 'create') {
    $htmlString .= '<tr><td class="nowrap">' . __('VIEW name') . '</td>'
        . '<td><input type="text" size="20" name="view[name]"'
        . ' onfocus="this.select()"'
        . ' value="' . htmlspecialchars($view['name']) . '" />'
        . '</td></tr>';
} else {
    $htmlString .= '<tr><td><input type="hidden" name="view[name]"'
        . ' value="' . htmlspecialchars($view['name']) . '" />'
        . '</td></tr>';
}

$htmlString .= '<tr><td class="nowrap">' . __('Column names') . '</td>'
    . '<td><input type="text" maxlength="100" size="50" name="view[column_names]"'
    . ' onfocus="this.select()"'
    . ' value="' . htmlspecialchars($view['column_names']) . '" />'
    . '</td></tr>';

$htmlString .= '<tr><td class="nowrap">AS</td>'
    . '<td>'
    . '<textarea name="view[as]" rows="' . $cfg['TextareaRows'] . '"'
    . ' cols="' . $cfg['TextareaCols'] . '" dir="' . $text_dir . '"';
if ($GLOBALS['cfg']['TextareaAutoSelect'] || true) {
    $htmlString .= ' onclick="selectContent(this, sql_box_locked, true)"';
}
$htmlString .= '>' . htmlspecialchars($view['as']) . '</textarea>'
    . '</td></tr>';

$htmlString .= '<tr><td class="nowrap">WITH CHECK OPTION</td>'
    . '<td><select name="view[with]">'
    . '<option value=""></option>';
foreach ($view_with_options as $option) {
    $htmlString .= '<option value="' . htmlspecialchars($option) . '"';
    if ($option == $view['with']) {
        $htmlString .= ' selected="selected"';
    }
    $htmlString .= '>' . htmlspecialchars($option) . '</option>';
}
$htmlString .= '<select>'
    . '</td></tr>';

$htmlString .= '</table>'
    . '</fieldset>';

if (! isset($_REQUEST['ajax_dialog'])) {
    $htmlString .= '<fieldset class="tblFooters">'
        . '<input type="hidden" name="'
        . ($view['operation'] == 'create' ? 'createview' : 'alterview' )
        . '" value="1" />'
        . '<input type="submit" name="" value="' . __('Go') . '" />'
        . '</fieldset>';
} else {
    $htmlString .= '<input type="hidden" name="'
        . ($view['operation'] == 'create' ? 'createview' : 'alterview' )
        . '" value="1" />'
        . '<input type="hidden" name="ajax_dialog" value="1" />'
        . '<input type="hidden" name="ajax_request" value="1" />';
}

$htmlString .= '</form>'
    . '</div>';

echo $htmlString;
?>
