<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays add field form and handles it
 *
 * @package PhpMyAdmin
 */

/**
 * Get some core libraries
 */
require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_structure.js');

// Check parameters
PMA_Util::checkParameters(array('db', 'table'));


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_sql.php' . PMA_URL_getCommon(
    array(
        'db' => $db, 'table' => $table
    )
);

/**
 * The form used to define the field to add has been submitted
 */
$abort = false;

// check number of fields to be created
if (isset($_REQUEST['submit_num_fields'])) {
    if (isset($_REQUEST['orig_after_field'])) {
        $_REQUEST['after_field'] = $_REQUEST['orig_after_field'];
    }
    if (isset($_REQUEST['orig_field_where'])) {
        $_REQUEST['field_where'] = $_REQUEST['orig_field_where'];
    }
    $num_fields = min(
        intval($_REQUEST['orig_num_fields']) + intval($_REQUEST['added_fields']),
        4096
    );
    $regenerate = true;
} elseif (isset($_REQUEST['num_fields']) && intval($_REQUEST['num_fields']) > 0) {
    $num_fields = min(4096, intval($_REQUEST['num_fields']));
} else {
    $num_fields = 1;
}

if (isset($_REQUEST['do_save_data'])) {
    //avoid an incorrect calling of PMA_updateColumns() via
    //tbl_structure.php below
    unset($_REQUEST['do_save_data']);

    include_once 'libraries/create_addfield.lib.php';

    list($result, $sql_query) = PMA_tryColumnCreationQuery($db, $table, $err_url);

    if ($result === true) {
        // If comments were sent, enable relation stuff
        include_once 'libraries/transformations.lib.php';

        // Update comment table for mime types [MIME]
        if (isset($_REQUEST['field_mimetype'])
            && is_array($_REQUEST['field_mimetype'])
            && $cfg['BrowseMIME']
        ) {
            /** @var PMA_String $pmaString */
            $pmaString = $GLOBALS['PMA_String'];
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                    && /*overload*/mb_strlen($_REQUEST['field_name'][$fieldindex])
                ) {
                    PMA_setMIME(
                        $db, $table,
                        $_REQUEST['field_name'][$fieldindex],
                        $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex],
                        $_REQUEST['field_input_transformation'][$fieldindex],
                        $_REQUEST['field_input_transformation_options'][$fieldindex]
                    );
                }
            }
        }

        // Go back to the structure sub-page
        $message = PMA_Message::success(
            __('Table %1$s has been altered successfully.')
        );
        $message->addParam($table);
        $response->addJSON(
            'message', PMA_Util::getMessage($message, $sql_query, 'success')
        );
        exit;
    } else {
        $error_message_html = PMA_Util::mysqlDie('', '', false, $err_url, false);
        $response->addHTML($error_message_html);
        $response->isSuccess(false);
        exit;
    }
} // end do alter table

/**
 * Displays the form used to define the new field
 */
if ($abort == false) {
    /**
     * Gets tables informations
     */
    include_once 'libraries/tbl_common.inc.php';
    include_once 'libraries/tbl_info.inc.php';

    $active_page = 'tbl_structure.php';
    /**
     * Display the form
     */
    $action = 'tbl_addfield.php';
    include_once 'libraries/tbl_columns_definition_form.inc.php';
}
?>
