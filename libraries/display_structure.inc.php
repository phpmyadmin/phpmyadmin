<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/Template.class.php';
require_once 'libraries/structure.lib.php';
require_once 'libraries/index.lib.php';
require_once 'libraries/tbl_info.inc.php';

if (! defined('PHPMYADMIN')) {
    exit;
}

global $db, $table, $tbl_is_view, $cfg, $db_is_system_schema,
       $tbl_storage_engine, $pmaThemeImage, $text_dir, $url_query, $showtable, $tbl_collation, $table_info_num_rows;
$response = PMA_Response::getInstance();

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
    PMA\Template::get('structure/table_structure_header')->render(
        array(
            'db_is_system_schema' => $db_is_system_schema,
            'tbl_is_view' => $tbl_is_view
        )
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
$rownum = 0;
$columns_list = array();
$save_row = array();
$odd_row = true;

$titles = array(
    'Change' => PMA_Util::getIcon('b_edit.png', __('Change')),
    'Drop' => PMA_Util::getIcon('b_drop.png', __('Drop')),
    'NoDrop' => PMA_Util::getIcon('b_drop.png', __('Drop')),
    'Primary' => PMA_Util::getIcon('b_primary.png', __('Primary')),
    'Index' => PMA_Util::getIcon('b_index.png', __('Index')),
    'Unique' => PMA_Util::getIcon('b_unique.png', __('Unique')),
    'Spatial' => PMA_Util::getIcon('b_spatial.png', __('Spatial')),
    'IdxFulltext' => PMA_Util::getIcon('b_ftext.png', __('Fulltext')),
    'NoPrimary' => PMA_Util::getIcon('bd_primary.png', __('Primary')),
    'NoIndex' => PMA_Util::getIcon('bd_index.png', __('Index')),
    'NoUnique' => PMA_Util::getIcon('bd_unique.png', __('Unique')),
    'NoSpatial' => PMA_Util::getIcon('bd_spatial.png', __('Spatial')),
    'NoIdxFulltext' => PMA_Util::getIcon('bd_ftext.png', __('Fulltext')),
    'DistinctValues' => PMA_Util::getIcon('b_browse.png', __('Distinct values'))
);

foreach ($fields as $row) {
    $save_row[] = $row;
    $rownum++;
    $columns_list[] = $row['Field'];

    $type = $row['Type'];
    $extracted_columnspec = PMA_Util::extractColumnSpec($row['Type']);

    $class_for_type = PMA_Util::getClassForType(
        $extracted_columnspec['type']
    );

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
        . strtolower(str_replace('_', '/', $mime_map[$row['Field']]['mimetype']));
    } else {
        $type_mime = '';
    }

    $attribute = $extracted_columnspec['attribute'];

    // prepare a common variable to reuse below; however,
    // in case of a VIEW, $create_table_fields is empty
    if (isset($create_table_fields[$row['Field']])) {
        $tempField = $create_table_fields[$row['Field']];
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
    if (in_array($field_name, $columns_with_index)) {
        $displayed_field_name .= PMA_Util::getImage(
            'bd_primary.png', __('Index')
        );
    }
    $response->addHTML(
        '<tr class="' . ($odd_row ? 'odd': 'even') . '">'
    );
    $odd_row = !$odd_row;
    $isInCentralColumns = in_array($row['Field'], $central_list) ? true : false;
    $response->addHTML(
        PMA\Template::get('structure/table_structure_row')->render(
            array(
                'row' => $row,
                'rownum' => $rownum,
                'displayed_field_name' => $displayed_field_name,
                'type_nowrap' => $class_for_type,
                'extracted_columnspec' => $extracted_columnspec,
                'type_mime' => $type_mime,
                'field_charset' => $field_charset,
                'attribute' => $attribute,
                'tbl_is_view' => $tbl_is_view,
                'db_is_system_schema' => $db_is_system_schema,
                'url_query' => $url_query,
                'field_encoded' => $field_encoded,
                'titles' => $titles,
                'table' => $table
            )
        )
    );

    if (! $tbl_is_view && ! $db_is_system_schema) {
        $response->addHTML(
            PMA\Template::get('structure/actions_in_table_structure')->render(
                array(
                    'type' => $type,
                    'tbl_storage_engine' => $tbl_storage_engine,
                    'primary' => $primary,
                    'field_name' => $field_name,
                    'url_query' => $url_query,
                    'titles' => $titles,
                    'row' => $row,
                    'rownum' => $rownum,
                    'columns_with_unique_index' => $columns_with_unique_index,
                    'isInCentralColumns' => $isInCentralColumns
                )
            )
        );
    } // end if (! $tbl_is_view && ! $db_is_system_schema)

    $response->addHTML('</tr>');

    unset($field_charset);
} // end foreach

$response->addHTML('</tbody></table>');

$response->addHTML(
    PMA\Template::get('structure/check_all_table_column')->render(
        array(
            'pmaThemeImage' => $pmaThemeImage,
            'text_dir' => $text_dir,
            'tbl_is_view' => $tbl_is_view,
            'db_is_system_schema' => $db_is_system_schema,
            'tbl_storage_engine' => $tbl_storage_engine
        )
    )
);

$response->addHTML(
    '</form><hr class="print_ignore"/>'
);
$response->addHTML(
    PMA\Template::get('structure/move_columns_dialog')->render()
);

/**
 * Work on the table
 */

$response->addHTML('<div id="structure-action-links">');

if ($tbl_is_view) {

    /** @var PMA_DatabaseInterface $dbi */
    $dbi = $GLOBALS['dbi'];
    $item = $dbi->fetchSingleRow(sprintf(
        "SELECT `VIEW_DEFINITION`, `CHECK_OPTION`, `DEFINER`, `SECURITY_TYPE`
            FROM `INFORMATION_SCHEMA`.`VIEWS`
            WHERE TABLE_SCHEMA='%s'
            AND TABLE_NAME='%s';",
        PMA_Util::sqlAddSlashes($GLOBALS['db']),
        PMA_Util::sqlAddSlashes($GLOBALS['table'])
    ));

    $createView = $dbi->getTable($GLOBALS['db'], $GLOBALS['table'])->showCreate();
    // get algorithm from $createView of the form CREATE ALGORITHM=<ALGORITHM> DE...
    $parts = explode(" ", substr($createView, 17));
    $item['ALGORITHM'] = $parts[0];

    $view = array(
        'operation' => 'alter',
        'definer' => $item['DEFINER'],
        'sql_security' => $item['SECURITY_TYPE'],
        'name' => $GLOBALS['table'],
        'as' => $item['VIEW_DEFINITION'],
        'with' => $item['CHECK_OPTION'],
        'algorithm' => $item['ALGORITHM'],
    );

    $url = 'view_create.php' . PMA_URL_getCommon($url_params) . '&amp;' . implode(
            '&amp;',
            array_map(
                function ($key, $val) {
                    return 'view[' . urlencode($key) . ']=' . urlencode($val);
                },
                array_keys($view), $view
            )
        );
    $response->addHTML(PMA_Util::linkOrButton(
        $url,
        PMA_Util::getIcon('b_edit.png', __('Edit view'), true)
    ));
}
$response->addHTML(
    PMA\Template::get('structure/optional_action_links')->render(
        array(
            'url_query' => $url_query,
            'tbl_is_view' => $tbl_is_view,
            'db_is_system_schema' => $db_is_system_schema
        )
    )
);

$response->addHTML('</div>');

if (! $tbl_is_view && ! $db_is_system_schema) {
    $response->addHTML('<br />');
    $response->addHTML(
        PMA\Template::get('structure/add_column')
            ->render(
                array(
                    'columns_list' => $columns_list,
                )
            )
    );
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
        PMA_Index::getHtmlForIndexes($GLOBALS['table'], $GLOBALS['db'])
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
    $tablestats = PMA_getTableStats(
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
