<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/Template.class.php';

if (! isset($_REQUEST['create_edit_table'])) {
    include_once 'libraries/tbl_common.inc.php';
}

if (isset($_REQUEST['index'])) {
    if (is_array($_REQUEST['index'])) {
        // coming already from form
        $index = new PMA_Index($_REQUEST['index']);
    } else {
        $index = $GLOBALS['dbi']->getTable($db, $table)
            ->getIndex($_REQUEST['index']);
    }
} else {
    $index = new PMA_Index;
}

/**
 * Process the data from the edit/create index form,
 * run the query to build the new index
 * and moves back to "tbl_sql.php"
 */
if (isset($_REQUEST['do_save_data'])) {

    $error = false;

    $sql_query = $GLOBALS['dbi']->getTable($db, $table)
        ->getSqlQueryForIndexCreateOrEdit($index, $error);

    // If there is a request for SQL previewing.
    if (isset($_REQUEST['preview_sql'])) {

        PMA_Response::getInstance()->addJSON(
            'sql_data',
            PMA\Template::get('preview_sql')
                ->render(
                    array(
                        'query_data' => $sql_query
                    )
                )
        );
    } elseif (!$error) {

        $GLOBALS['dbi']->query($sql_query);
        if ($GLOBALS['is_ajax_request'] == true) {
            $message = PMA_Message::success(
                __('Table %1$s has been altered successfully.')
            );
            $message->addParam($table);
            $response = PMA_Response::getInstance();
            $response->addJSON(
                'message', PMA_Util::getMessage($message, $sql_query, 'success')
            );
            $response->addJSON('index_table', PMA_Index::getHtmlForIndexes($table, $db));
        } else {
            include 'tbl_structure.php';
        }
    } else {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', $error);
    }
    exit;
} // end builds the new index


/**
 * Display the form to edit/create an index
 */
require_once 'libraries/tbl_info.inc.php';

$add_fields = 0;
if (isset($_REQUEST['index']) && is_array($_REQUEST['index'])) {
    // coming already from form
    if (isset($_REQUEST['index']['columns']['names'])) {
        $add_fields = count($_REQUEST['index']['columns']['names'])
                    - $index->getColumnCount();
    }
    if (isset($_REQUEST['add_fields'])) {
        $add_fields += $_REQUEST['added_fields'];
    }
} elseif (isset($_REQUEST['create_index'])) {
    $add_fields = $_REQUEST['added_fields'];
} // end preparing form values

// Get fields and stores their name/type
if (isset($_REQUEST['create_edit_table'])) {
    $fields = json_decode($_REQUEST['columns'], true);
    $index_params = array(
        'Non_unique' => ($_REQUEST['index']['Index_choice'] == 'UNIQUE') ? '0' : '1'
    );
    $index->set($index_params);
    $add_fields = count($fields);
} else {
    $fields = $GLOBALS['dbi']->getTable($db, $table)->getNameAndTypeOfTheColumns();
}

$form_params = array(
    'db'    => $db,
    'table' => $table,
);

if (isset($_REQUEST['create_index'])) {
    $form_params['create_index'] = 1;
} elseif (isset($_REQUEST['old_index'])) {
    $form_params['old_index'] = $_REQUEST['old_index'];
} elseif (isset($_REQUEST['index'])) {
    $form_params['old_index'] = $_REQUEST['index'];
}

$response = PMA_Response::getInstance();
$response->addHTML(PMA\Template::get('index_form')
    ->render(array(
        'fields' => $fields,
        'index' => $index,
        'form_params' => $form_params,
        'add_fields' => $add_fields
    ))
);
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('indexes.js');
