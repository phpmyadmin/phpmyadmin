<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/structure.lib.php';
require_once 'libraries/index.lib.php';
require_once 'libraries/tbl_info.inc.php';

if (! defined('PHPMYADMIN')) {
    exit;
}

/* TABLE INFORMATION */
// table header


$HideStructureActions = '';
if ($GLOBALS['cfg']['HideStructureActions'] === true) {
    $HideStructureActions .= ' HideStructureActions';
}

$html_form = '<form method="post" action="tbl_structure.php" name="fieldsForm" '
. 'id="fieldsForm" class="ajax' . $HideStructureActions . '">';

$response->addHTML($html_form);
$response->addHTML(PMA_URL_getHiddenInputs($db, $table));

$tabletype = '<input type="hidden" name="table_type" value=';
if ($db_is_system_schema) {
    $tabletype .= '"information_schema" />';
} else if ($tbl_is_view) {
    $tabletype .= '"view" />';
} else {
    $tabletype .= '"table" />';
}
$response->addHTML($tabletype);

$tablestructure = '<table id="tablestructure" class="data topmargin">';
$response->addHTML($tablestructure);


$response->addHTML(
    PMA_getHtmlForTableStructureHeader(
        $db_is_system_schema,
        $tbl_is_view
    )
);

$response->addHTML('<tbody>');

// table body

// prepare comments
$comments_map = array();
$mime_map = array();

if ($GLOBALS['cfg']['ShowPropertyComments']) {
    include_once 'libraries/transformations.lib.php';
    $comments_map = PMA_getComments($db, $table);
    if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
        $mime_map = PMA_getMIME($db, $table, true);
    }
}
require_once 'libraries/central_columns.lib.php';
$central_list = PMA_getCentralColumnsFromTable($db, $table);
$rownum    = 0;
$columns_list = array();
$save_row  = array();
$odd_row   = true;
foreach ($fields as $row) {
    $save_row[] = $row;
    $rownum++;
    $columns_list[]   = $row['Field'];

    $type             = $row['Type'];
    $extracted_columnspec = PMA_Util::extractColumnSpec($row['Type']);

    if ('set' == $extracted_columnspec['type']
        || 'enum' == $extracted_columnspec['type']
    ) {
        $type_nowrap  = '';
    } else {
        $type_nowrap  = ' class="nowrap"';
    }
    $type         = $extracted_columnspec['print_type'];
    if (empty($type)) {
        $type     = ' ';
    }

    $field_charset = '';
    if ($extracted_columnspec['can_contain_collation']
        && ! empty($row['Collation'])
    ) {
        $field_charset = $row['Collation'];
    }

    // Display basic mimetype [MIME]
    if ($cfgRelation['commwork']
        && $cfgRelation['mimework']
        && $cfg['BrowseMIME']
        && isset($mime_map[$row['Field']]['mimetype'])
    ) {
        $type_mime = '<br />MIME: '
        . str_replace('_', '/', $mime_map[$row['Field']]['mimetype']);
    } else {
        $type_mime = '';
    }

    $attribute = $extracted_columnspec['attribute'];

    // prepare a common variable to reuse below; however,
    // in case of a VIEW, $analyzed_sql[0]['create_table_fields'] is empty
    if (isset($analyzed_sql[0]['create_table_fields'][$row['Field']])) {
        $tempField = $analyzed_sql[0]['create_table_fields'][$row['Field']];
    } else {
        $tempField = array();
    }

    // MySQL 4.1.2+ TIMESTAMP options
    // (if on_update_current_timestamp is set, then it's TRUE)
    if (isset($tempField['on_update_current_timestamp'])) {
        $attribute = 'on update CURRENT_TIMESTAMP';
    }

    // here, we have a TIMESTAMP that SHOW FULL COLUMNS reports as having the
    // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
    // the latter.
    if (! empty($tempField['type'])
        && $tempField['type'] == 'TIMESTAMP'
        && $tempField['timestamp_not_null']
    ) {
        $row['Null'] = '';
    }


    if (! isset($row['Default'])) {
        if ($row['Null'] == 'YES') {
            $row['Default'] = '<i>NULL</i>';
        }
    } else {
        $row['Default'] = htmlspecialchars($row['Default']);
    }

    $field_encoded = urlencode($row['Field']);
    $field_name    = htmlspecialchars($row['Field']);
    $displayed_field_name = $field_name;

    // underline commented fields and display a hover-title (CSS only)

    if (isset($comments_map[$row['Field']])) {
        $displayed_field_name = '<span class="commented_column" title="'
        . htmlspecialchars($comments_map[$row['Field']]) . '">'
        . $field_name . '</span>';
    }

    if ($primary && $primary->hasColumn($field_name)) {
        $displayed_field_name .= PMA_Util::getImage(
            'b_primary.png', __('Primary')
        );
    }
    $response->addHTML(
        '<tr class="' . ($odd_row ? 'odd': 'even') . '">'
    );
    $odd_row = !$odd_row;
    $isInCentralColumns = in_array($row['Field'], $central_list)?true:false;
    $response->addHTML(
        PMA_getHtmlTableStructureRow(
            $row, $rownum, $displayed_field_name,
            $type_nowrap, $extracted_columnspec, $type_mime,
            $field_charset, $attribute, $tbl_is_view,
            $db_is_system_schema, $url_query, $field_encoded, $titles, $table
        )
    );

    if (! $tbl_is_view && ! $db_is_system_schema) {
        $response->addHTML(
            PMA_getHtmlForActionsInTableStructure(
                $type, $tbl_storage_engine, $primary,
                $field_name, $url_query, $titles, $row, $rownum,
                $columns_with_unique_index,
                $isInCentralColumns
            )
        );
    } // end if (! $tbl_is_view && ! $db_is_system_schema)

    $response->addHTML('</tr>');

    unset($field_charset);
} // end foreach

$response->addHTML('</tbody></table>');

$response->addHTML(
    PMA_getHtmlForCheckAllTableColumn(
        $pmaThemeImage, $text_dir, $tbl_is_view,
        $db_is_system_schema, $tbl_storage_engine
    )
);

$response->addHTML(
    '</form><hr />'
);
$response->addHTML(
    PMA_getHtmlDivForMoveColumnsDialog()
);

/**
 * Work on the table
 */

$response->addHTML('<div id="structure-action-links">');

if ($tbl_is_view) {
    $response->addHTML(PMA_getHtmlForEditView($url_params));
}
$response->addHTML(
    PMA_getHtmlForOptionalActionLinks(
        $url_query, $tbl_is_view, $db_is_system_schema,
        $tbl_storage_engine, $cfgRelation
    )
);

$response->addHTML('</div>');

if (! $tbl_is_view && ! $db_is_system_schema) {
    $response->addHTML('<br />');
    $response->addHTML(PMA_getHtmlForAddColumn($columns_list));
}

/**
 * Displays indexes
 */

if (! $tbl_is_view
    && ! $db_is_system_schema
    && 'ARCHIVE' !=  $tbl_storage_engine
) {
    //return the list of index
    $response->addJSON(
        'indexes_list',
        PMA_Index::getView($GLOBALS['table'], $GLOBALS['db'])
    );
    $response->addHTML(PMA_getHtmlForDisplayIndexes());
}

/**
 * Displays Space usage and row statistics
 */
// BEGIN - Calc Table Space
// Get valid statistics whatever is the table type
if ($cfg['ShowStats']) {
    //get table stats in HTML format
    $tablestats = PMA_getHtmlForDisplayTableStats(
        $showtable, $table_info_num_rows, $tbl_is_view,
        $db_is_system_schema, $tbl_storage_engine,
        $url_query, $tbl_collation
    );
    //returning the response in JSON format to be used by Ajax
    $response->addJSON('tableStat', $tablestats);
    $response->addHTML($tablestats);
}
// END - Calc Table Space

$response->addHTML(
    '<div class="clearfloat"></div>'
);
?>
