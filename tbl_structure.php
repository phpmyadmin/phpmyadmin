<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like fields/columns, indexes, size, rows
 * and allows manipulation of indexes and columns/fields
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.lib.php';

/**
 * Function implementations for this script
 */
require_once 'libraries/structure.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_structure.js');
$scripts->addFile('indexes.js');
$common_functions = PMA_CommonFunctions::getInstance();

/**
 * handle multiple field commands if required
 *
 * submit_mult_*_x comes from IE if <input type="img" ...> is used
 */
if (isset($_REQUEST['submit_mult_change_x'])) {
    $submit_mult = 'change';
} elseif (isset($_REQUEST['submit_mult_drop_x'])) {
    $submit_mult = 'drop';
} elseif (isset($_REQUEST['submit_mult_primary_x'])) {
    $submit_mult = 'primary';
} elseif (isset($_REQUEST['submit_mult_index_x'])) {
    $submit_mult = 'index';
} elseif (isset($_REQUEST['submit_mult_unique_x'])) {
    $submit_mult = 'unique';
} elseif (isset($_REQUEST['submit_mult_spatial_x'])) {
    $submit_mult = 'spatial';
} elseif (isset($_REQUEST['submit_mult_fulltext_x'])) {
    $submit_mult = 'ftext';
} elseif (isset($_REQUEST['submit_mult_browse_x'])) {
    $submit_mult = 'browse';
} elseif (isset($_REQUEST['submit_mult'])) {
    $submit_mult = $_REQUEST['submit_mult'];
} elseif (isset($_REQUEST['mult_btn']) && $_REQUEST['mult_btn'] == __('Yes')) {
    $submit_mult = 'row_delete';
    if (isset($_REQUEST['selected'])) {
        $_REQUEST['selected_fld'] = $_REQUEST['selected'];
    }
}

if (! empty($submit_mult) && isset($_REQUEST['selected_fld'])) {
    $err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);
    if ($submit_mult == 'browse') {
        // browsing the table displaying only selected fields/columns
        $GLOBALS['active_page'] = 'sql.php';
        $sql_query = '';
        foreach ($_REQUEST['selected_fld'] as $idx => $sval) {
            if ($sql_query == '') {
                $sql_query .= 'SELECT ' . $common_functions->backquote($sval);
            } else {
                $sql_query .=  ', ' . $common_functions->backquote($sval);
            }
        }

        // what is this htmlspecialchars() for??
        //$sql_query .= ' FROM ' . backquote(htmlspecialchars($table));
        $sql_query .= ' FROM ' . $common_functions->backquote($db)
            . '.' . $common_functions->backquote($table);
        include 'sql.php';
        exit;
    } else {
        // handle multiple field commands
        // handle confirmation of deleting multiple fields/columns
        $action = 'tbl_structure.php';
        include 'libraries/mult_submits.inc.php';

        if (empty($message)) {
            $message = PMA_Message::success();
        }
    }
}

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * Runs common work
 */
require_once 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_structure.php&amp;back=tbl_structure.php';
$url_params['goto'] = 'tbl_structure.php';
$url_params['back'] = 'tbl_structure.php';

/**
 * Prepares the table structure display
 */


/**
 * Gets tables informations
 */
require_once 'libraries/tbl_info.inc.php';

require_once 'libraries/Index.class.php';

// 2. Gets table keys and retains them
// @todo should be: $server->db($db)->table($table)->primary()
$primary = PMA_Index::getPrimary($table, $db);

$columns_with_unique_index = array();
foreach (PMA_Index::getFromTable($table, $db) as $index) {
    if ($index->isUnique() && $index->getChoice() == 'UNIQUE') {
        $columns = $index->getColumns();
        foreach ($columns as $column_name => $dummy) {
            $columns_with_unique_index[$column_name] = 1;
        }
    }
}
unset($index, $columns, $column_name, $dummy);

// 3. Get fields
$fields = (array) PMA_DBI_get_columns($db, $table, null, true);

// Get more complete field information
// For now, this is done just for MySQL 4.1.2+ new TIMESTAMP options
// but later, if the analyser returns more information, it
// could be executed for any MySQL version and replace
// the info given by SHOW FULL COLUMNS FROM.
//
// We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
// SHOW FULL COLUMNS or INFORMATION_SCHEMA incorrectly says NULL
// and SHOW CREATE TABLE says NOT NULL (tested
// in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

$show_create_table = PMA_DBI_fetch_value(
    'SHOW CREATE TABLE ' . $common_functions->backquote($db) . '.' . $common_functions->backquote($table),
    0, 1
);
$analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

/**
 * prepare table infos
 */
// action titles (image or string)
$titles = array();
$titles['Change']               = $common_functions->getIcon('b_edit.png', __('Change'));
$titles['Drop']                 = $common_functions->getIcon('b_drop.png', __('Drop'));
$titles['NoDrop']               = $common_functions->getIcon('b_drop.png', __('Drop'));
$titles['Primary']              = $common_functions->getIcon('b_primary.png', __('Primary'));
$titles['Index']                = $common_functions->getIcon('b_index.png', __('Index'));
$titles['Unique']               = $common_functions->getIcon('b_unique.png', __('Unique'));
$titles['Spatial']              = $common_functions->getIcon('b_spatial.png', __('Spatial'));
$titles['IdxFulltext']          = $common_functions->getIcon('b_ftext.png', __('Fulltext'));
$titles['NoPrimary']            = $common_functions->getIcon('bd_primary.png', __('Primary'));
$titles['NoIndex']              = $common_functions->getIcon('bd_index.png', __('Index'));
$titles['NoUnique']             = $common_functions->getIcon('bd_unique.png', __('Unique'));
$titles['NoSpatial']            = $common_functions->getIcon('bd_spatial.png', __('Spatial'));
$titles['NoIdxFulltext']        = $common_functions->getIcon('bd_ftext.png', __('Fulltext'));
$titles['DistinctValues']       = $common_functions->getIcon('b_browse.png', __('Distinct values'));

// hidden action titles (image and string)
$hidden_titles = array();
$hidden_titles['DistinctValues']       = $common_functions->getIcon('b_browse.png', __('Distinct values'), true);
$hidden_titles['Primary']              = $common_functions->getIcon('b_primary.png', __('Add primary key'), true);
$hidden_titles['NoPrimary']            = $common_functions->getIcon('bd_primary.png', __('Add primary key'), true);
$hidden_titles['Index']                = $common_functions->getIcon('b_index.png', __('Add index'), true);
$hidden_titles['NoIndex']              = $common_functions->getIcon('bd_index.png', __('Add index'), true);
$hidden_titles['Unique']               = $common_functions->getIcon('b_unique.png', __('Add unique index'), true);
$hidden_titles['NoUnique']             = $common_functions->getIcon('bd_unique.png', __('Add unique index'), true);
$hidden_titles['Spatial']              = $common_functions->getIcon('b_spatial.png', __('Add SPATIAL index'), true);
$hidden_titles['NoSpatial']            = $common_functions->getIcon('bd_spatial.png', __('Add SPATIAL index'), true);
$hidden_titles['IdxFulltext']          = $common_functions->getIcon('b_ftext.png', __('Add FULLTEXT index'), true);
$hidden_titles['NoIdxFulltext']        = $common_functions->getIcon('bd_ftext.png', __('Add FULLTEXT index'), true);

/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 */
/* TABLE INFORMATION */
// table header
$i = 0;
?>
<form method="post" action="tbl_structure.php" name="fieldsForm" id="fieldsForm" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : '');?>>
    <?php echo PMA_generate_common_hidden_inputs($db, $table);
    echo '<input type="hidden" name="table_type" value=';
    if ($db_is_information_schema) {
         echo '"information_schema" />';
    } else if ($tbl_is_view) {
         echo '"view" />';
    } else {
         echo '"table" />';
    } ?>

<table id="tablestructure" class="data<?php
if ($GLOBALS['cfg']['PropertiesIconic'] === true) {
    echo ' PropertiesIconic';
} ?>">
<?php
    echo PMA_getHtmlForStructuretableTableHeader(
        $db_is_information_schema,
        $tbl_is_view
    );
?>
<tbody>
    

<?php
unset($i);

// table body

// prepare comments
$comments_map = array();
$mime_map = array();

if ($GLOBALS['cfg']['ShowPropertyComments']) {
    include_once 'libraries/transformations.lib.php';

    //$cfgRelation = PMA_getRelationsParam();

    $comments_map = PMA_getComments($db, $table);

    if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
        $mime_map = PMA_getMIME($db, $table, true);
    }
}

$rownum    = 0;
$columns_list = array();
$checked   = (!empty($checkall) ? ' checked="checked"' : '');
$save_row  = array();
$odd_row   = true;
foreach ($fields as $row) {
    $save_row[] = $row;
    $rownum++;
    $columns_list[]   = $row['Field'];

    $type             = $row['Type'];
    $extracted_columnspec = $common_functions->extractColumnSpec($row['Type']);

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

    $attribute     = $extracted_columnspec['attribute'];

    // MySQL 4.1.2+ TIMESTAMP options
    // (if on_update_current_timestamp is set, then it's TRUE)
    if (isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['on_update_current_timestamp'])) {
        $attribute = 'on update CURRENT_TIMESTAMP';
    }

    // here, we have a TIMESTAMP that SHOW FULL COLUMNS reports as having the
    // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
    // the latter.
    if (! empty($analyzed_sql[0]['create_table_fields'][$row['Field']]['type'])
        && $analyzed_sql[0]['create_table_fields'][$row['Field']]['type'] == 'TIMESTAMP'
        && $analyzed_sql[0]['create_table_fields'][$row['Field']]['timestamp_not_null']
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
        $displayed_field_name = '<u>' . $field_name . '</u>';
    }
    echo "\n";
    ?>
<tr class="<?php echo $odd_row ? 'odd': 'even'; $odd_row = !$odd_row; ?>">
    <td class="center">
        <input type="checkbox" class="checkall" name="selected_fld[]" value="<?php echo htmlspecialchars($row['Field']); ?>" id="checkbox_row_<?php echo $rownum; ?>" <?php echo $checked; ?> />
    </td>
    <td class="right">
        <?php echo $rownum; ?>
    </td>
    <th class="nowrap"><label for="checkbox_row_<?php echo $rownum; ?>"><?php echo $displayed_field_name; ?></label></th>
    <td<?php echo $type_nowrap; ?>><bdo dir="ltr" lang="en"><?php echo $extracted_columnspec['displayed_type']; echo $type_mime; ?></bdo></td>
    <td><?php echo (empty($field_charset) ? '' : '<dfn title="' . PMA_getCollationDescr($field_charset) . '">' . $field_charset . '</dfn>'); ?></td>
    <td class="column_attribute nowrap"><?php echo $attribute; ?></td>
    <td><?php echo (($row['Null'] == 'YES') ? __('Yes') : __('No')); ?></td>
    <td class="nowrap"><?php
    if (isset($row['Default'])) {
        if ($extracted_columnspec['type'] == 'bit') {
            // here, $row['Default'] contains something like b'010'
            echo $common_functions->convertBitDefaultValue($row['Default']);
        } else {
            echo $row['Default'];
        }
    } else {
        echo '<i>' . _pgettext('None for default', 'None') . '</i>';
    } ?></td>
    <td class="nowrap"><?php echo strtoupper($row['Extra']); ?></td>
    <?php if (! $tbl_is_view && ! $db_is_information_schema) { ?>
    <td class="edit center">
        <a href="tbl_alter.php?<?php echo $url_query; ?>&amp;field=<?php echo $field_encoded; ?>">
            <?php echo $titles['Change']; ?></a>
    </td>
    <td class="drop center">
        <a <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="drop_column_anchor"' : ''); ?> href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' DROP ' . $common_functions->backquote($row['Field']) . ';'); ?>&amp;dropped_column=<?php echo urlencode($row['Field']); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('Column %s has been dropped'), htmlspecialchars($row['Field']))); ?>" >
            <?php echo $titles['Drop']; ?></a>
    </td>
    <?php }
    if (! $tbl_is_view && ! $db_is_information_schema) { ?>
    <td class="primary replaced_by_more center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_storage_engine || ($primary && $primary->hasColumn($field_name))) {
            echo $titles['NoPrimary'] . "\n";
            $primary_enabled = false;
        } else {
            echo "\n";
            ?>
        <a class="add_primary_key_anchor" href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ($primary ? ' DROP PRIMARY KEY,' : '') . ' ADD PRIMARY KEY(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('A primary key has been added on %s'), htmlspecialchars($row['Field']))); ?>" >
            <?php echo $titles['Primary']; ?></a>
            <?php $primary_enabled = true;
        }
        echo "\n";
        ?>
    </td>
    <td class="unique replaced_by_more center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_storage_engine || isset($columns_with_unique_index[$field_name])) {
            echo $titles['NoUnique'] . "\n";
            $unique_enabled = false;
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' ADD UNIQUE(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('An index has been added on %s'), htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Unique']; ?></a>
            <?php $unique_enabled = true;
        }
        echo "\n";
        ?>
    </td>
    <td class="index replaced_by_more center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_storage_engine) {
            echo $titles['NoIndex'] . "\n";
            $index_enabled = false;
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' ADD INDEX(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('An index has been added on %s'), htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Index']; ?></a>
            <?php
            $index_enabled = true;
        }
        echo "\n";
        ?>
    </td>
        <?php
        if (!PMA_DRIZZLE) { ?>
    <td class="spatial replaced_by_more center">

        <?php
        $spatial_types = array(
            'geometry', 'point', 'linestring', 'polygon', 'multipoint',
            'multilinestring', 'multipolygon', 'geomtrycollection'
        );
        if (! in_array($type, $spatial_types) || 'MYISAM' != $tbl_storage_engine) {
            echo $titles['NoSpatial'] . "\n";
            $spatial_enabled = false;
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' ADD SPATIAL(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('An index has been added on %s'), htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Spatial']; ?></a>
            <?php
            $spatial_enabled = true;
        }
        echo "\n";
        ?>
    </td>
    <?php
        // FULLTEXT is possible on TEXT, CHAR and VARCHAR
        if (! empty($tbl_storage_engine) && ($tbl_storage_engine == 'MYISAM' || $tbl_storage_engine == 'ARIA' || $tbl_storage_engine == 'MARIA' || ($tbl_storage_engine == 'INNODB' && PMA_MYSQL_INT_VERSION >= 50604))
            && (strpos(' ' . $type, 'text') || strpos(' ' . $type, 'char'))
        ) {
            echo "\n";
            ?>
    <td class="fulltext replaced_by_more center nowrap">
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' ADD FULLTEXT(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('An index has been added on %s'), htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['IdxFulltext']; ?></a>
            <?php $fulltext_enabled = true; ?>
    </td>
            <?php
        } else {
            echo "\n";
        ?>
    <td class="fulltext replaced_by_more center nowrap">
        <?php echo $titles['NoIdxFulltext'] . "\n"; ?>
        <?php $fulltext_enabled = false; ?>
    </td>
        <?php
            }
        } // end if... else...
?>
    <td class="browse replaced_by_more center">
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT COUNT(*) AS ' . $common_functions->backquote(__('Rows')) . ', ' . $common_functions->backquote($row['Field']) . ' FROM ' . $common_functions->backquote($table) . ' GROUP BY ' . $common_functions->backquote($row['Field']) . ' ORDER BY ' . $common_functions->backquote($row['Field'])); ?>">            <?php echo $titles['DistinctValues']; ?></a>
    </td>
        <?php
        if ($GLOBALS['cfg']['PropertiesIconic'] !== true && $GLOBALS['cfg']['HideStructureActions'] === true) { ?>
    <td class="more_opts" id="more_opts<?php echo $rownum; ?>">
        <?php echo $common_functions->getImage('more.png', __('Show more actions')); ?> <?php echo __('More'); ?>
        <div class="structure_actions_dropdown" id="row_<?php echo $rownum; ?>">
            <div  class="<?php echo ($GLOBALS['cfg']['AjaxEnable'] ? 'action_primary ' : ''); ?>replace_in_more">
                <?php
                if (isset($primary_enabled)) {
                     if ($primary_enabled) { ?>
                          <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ($primary ? ' DROP PRIMARY KEY,' : '') . ' ADD PRIMARY KEY(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('A primary key has been added on %s'), htmlspecialchars($row['Field']))); ?>">
                             <?php echo $hidden_titles['Primary']; ?>
                         </a>
                     <?php
                     } else {
                         echo $hidden_titles['NoPrimary'];
                     }
                } ?>
            </div>
            <div class="action_unique replace_in_more">
                <?php
                if (isset($unique_enabled)) {
                     if ($unique_enabled) { ?>
                         <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' ADD UNIQUE(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('An index has been added on %s'), htmlspecialchars($row['Field']))); ?>">
                             <?php echo $hidden_titles['Unique']; ?>
                         </a>
                     <?php
                     } else {
                         echo $hidden_titles['NoUnique'];
                     }
                } ?>
            </div>
            <div class="action_index replace_in_more">
               <?php
                if (isset($index_enabled)) {
                     if ($index_enabled) { ?>
                         <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' ADD INDEX(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('An index has been added on %s'), htmlspecialchars($row['Field']))); ?>">
                             <?php echo $hidden_titles['Index']; ?>
                         </a>
                     <?php
                     } else {
                         echo $hidden_titles['NoIndex'];
                     }
                  } ?>
            </div>
            <?php if (!PMA_DRIZZLE) { ?>
            <div class="action_spatial replace_in_more">
                <?php
                if (isset($spatial_enabled)) {
                    if ($spatial_enabled) { ?>
                        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' ADD SPATIAL(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('An index has been added on %s'), htmlspecialchars($row['Field']))); ?>">
                            <?php echo $hidden_titles['Spatial']; ?>
                        </a>
                    <?php
                    } else {
                        echo $hidden_titles['NoSpatial'];
                    }
                } ?>
            </div>
            <div class="action_fulltext replace_in_more">
                <?php
                if (isset($fulltext_enabled)) {
                     if ($fulltext_enabled) { ?>
                         <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . $common_functions->backquote($table) . ' ADD FULLTEXT(' . $common_functions->backquote($row['Field']) . ');'); ?>&amp;message_to_show=<?php echo urlencode(sprintf(__('An index has been added on %s'), htmlspecialchars($row['Field']))); ?>">
                             <?php echo $hidden_titles['IdxFulltext']; ?>
                         </a>
                     <?php
                     } else {
                         echo $hidden_titles['NoIdxFulltext'];
                     }
                } ?>
            </div>
            <div class="action_browse replace_in_more">
                <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT COUNT(*) AS ' . $common_functions->backquote(__('Rows')) . ', ' . $common_functions->backquote($row['Field']) . ' FROM ' . $common_functions->backquote($table) . ' GROUP BY ' . $common_functions->backquote($row['Field']) . ' ORDER BY ' . $common_functions->backquote($row['Field'])); ?>&amp;browse_distinct=1">
                    <?php echo $hidden_titles['DistinctValues']; ?>
                </a>
            </div>
            <?php } ?>
        </div>
    </td>
    <?php
        } // end if (GLOBALS['cfg']['PropertiesIconic'] !== true)
    } // end if (! $tbl_is_view && ! $db_is_information_schema)
    ?>
</tr>
    <?php
    unset($field_charset);
} // end foreach

echo '</tbody>' . "\n"
    .'</table>' . "\n";

$checkall_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);
?>

<img class="selectallarrow" src="<?php echo $pmaThemeImage . 'arrow_' . $text_dir . '.png'; ?>"
    width="38" height="22" alt="<?php echo __('With selected:'); ?>" />
<input type="checkbox" id="checkall" title="<?php echo __('Check All'); ?>" />
<label for="checkall"><?php echo __('Check All'); ?></label>

<i style="margin-left: 2em"><?php echo __('With selected:'); ?></i>

<?php
echo $common_functions->getButtonOrImage(
    'submit_mult', 'mult_submit', 'submit_mult_browse',
    __('Browse'), 'b_browse.png', 'browse'
);

if (! $tbl_is_view && ! $db_is_information_schema) {
    echo $common_functions->getButtonOrImage(
        'submit_mult', 'mult_submit', 'submit_mult_change',
        __('Change'), 'b_edit.png', 'change'
    );
    echo $common_functions->getButtonOrImage(
        'submit_mult', 'mult_submit', 'submit_mult_drop',
        __('Drop'), 'b_drop.png', 'drop'
    );
    if ('ARCHIVE' != $tbl_storage_engine) {
        echo $common_functions->getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_primary',
            __('Primary'), 'b_primary.png', 'primary'
        );
        echo $common_functions->getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_unique',
            __('Unique'), 'b_unique.png', 'unique'
        );
        echo $common_functions->getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_index',
            __('Index'), 'b_index.png', 'index'
        );
    }

    if (! empty($tbl_storage_engine) && $tbl_storage_engine == 'MYISAM') {
        echo $common_functions->getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_spatial',
            __('Spatial'), 'b_spatial.png', 'spatial'
        );
    }
    if (! empty($tbl_storage_engine)
        && ($tbl_storage_engine == 'MYISAM'
        || $tbl_storage_engine == 'ARIA'
        || $tbl_storage_engine == 'MARIA')
    ) {
        echo $common_functions->getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_fulltext',
            __('Fulltext'), 'b_ftext.png', 'ftext'
        );
    }
}
?>
</form>
<hr />

<div id="move_columns_dialog" title="<?php echo __('Move columns'); ?>" style="display: none">
    <p><?php echo __('Move the columns by dragging them up and down.'); ?></p>
    <form action="tbl_alter.php">
        <div>
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <ul>
            </ul>
        </div>
    </form>
</div>

<?php
/**
 * Work on the table
 */

if ($tbl_is_view) {
    $create_view = PMA_DBI_get_definition($db, 'VIEW', $table);
    $create_view = preg_replace('@^CREATE@', 'ALTER', $create_view);
    echo $common_functions->linkOrButton(
        'tbl_sql.php' . PMA_generate_common_url(
            $url_params +
            array(
                'sql_query' => $create_view,
                'show_query' => '1',
            )
        ),
        $common_functions->getIcon('b_edit.png', __('Edit view'), true)
    );
}
?>

<a href="tbl_printview.php?<?php echo $url_query; ?>"><?php
echo $common_functions->getIcon('b_print.png', __('Print view'), true);
?></a>

<?php
if (! $tbl_is_view && ! $db_is_information_schema) {

    // if internal relations are available, or foreign keys are supported
    // ($tbl_storage_engine comes from libraries/tbl_info.inc.php)
    if ($cfgRelation['relwork']
        || $common_functions->isForeignKeySupported($tbl_storage_engine)
    ) {
        ?>
<a href="tbl_relation.php?<?php echo $url_query; ?>"><?php
        echo $common_functions->getIcon('b_relations.png', __('Relation view'), true);
        ?></a>
        <?php
    }

    if (!PMA_DRIZZLE) {
        ?>
<a href="sql.php?<?php echo $url_query; ?>&amp;session_max_rows=all&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . $common_functions->backquote($table) . ' PROCEDURE ANALYSE()'); ?>"><?php
        echo $common_functions->getIcon('b_tblanalyse.png', __('Propose table structure'), true);
        ?></a><?php
        echo $common_functions->showMySQLDocu('Extending_MySQL', 'procedure_analyse') . "\n";
    }

    if (PMA_Tracker::isActive()) {
        echo '<a href="tbl_tracking.php?' . $url_query . '">';
        echo $common_functions->getIcon('eye.png', __('Track table'), true);
        echo '</a>';
    }
    ?>

    <a href="#" id="move_columns_anchor"><?php
    echo $common_functions->getIcon('b_move.png', __('Move columns'), true);
    ?></a>

    <br />
<form method="post" action="tbl_addfield.php" id="addColumns" name="addColumns" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : '');?>
    onsubmit="return checkFormElementInRange(this, 'num_fields', '<?php echo str_replace('\'', '\\\'', __('You have to add at least one column.')); ?>', 1)">
    <?php
    echo PMA_generate_common_hidden_inputs($db, $table);
    if ($cfg['PropertiesIconic']) {
        echo $common_functions->getImage('b_insrow.png', __('Add column'));
    }
    echo sprintf(__('Add %s column(s)'), '<input type="text" name="num_fields" size="2" maxlength="2" value="1" onfocus="this.select()" />');

    // I tried displaying the drop-down inside the label but with Firefox
    // the drop-down was blinking
    $column_selector = '<select name="after_field" onclick="this.form.field_where[2].checked=true" onchange="this.form.field_where[2].checked=true">';
    foreach ($columns_list as $one_column_name) {
        $column_selector .= '<option value="' . htmlspecialchars($one_column_name) . '">' . htmlspecialchars($one_column_name) . '</option>';
    }
    unset($columns_list, $one_column_name);
    $column_selector .= '</select>';

    $choices = array(
        'last'  => __('At End of Table'),
        'first' => __('At Beginning of Table'),
        'after' => sprintf(__('After %s'), '')
    );
    echo $common_functions->getRadioFields(
        'field_where', $choices, 'last', false
    );
    echo $column_selector;
    unset($column_selector, $choices);
    ?>
<input type="submit" value="<?php echo __('Go'); ?>" />
</form>
<iframe class="IE_hack"></iframe>
<hr />
<div id="index_div" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?> >
    <?php
}

/**
 * Displays indexes
 */

if (! $tbl_is_view
    && ! $db_is_information_schema
    && 'ARCHIVE' !=  $tbl_storage_engine
) {
    echo $common_functions->getDivForSliderEffect('indexes', __('Indexes'));

    /**
     * Display indexes
     */
    echo PMA_Index::getView($table, $db);
    ?>
        <fieldset class="tblFooters" style="text-align: left;">
            <form action="tbl_indexes.php" method="post">
                <?php
                echo PMA_generate_common_hidden_inputs($db, $table);
                echo sprintf(
                    __('Create an index on &nbsp;%s&nbsp;columns'),
                    '<input type="text" size="2" name="added_fields" value="1" />'
                );
                ?>
                <input type="hidden" name="create_index" value="1" />
                <input class="add_index<?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' ajax' : '');?>" type="submit" value="<?php echo __('Go'); ?>" />
            </form>
        </fieldset>
    </div>
</div>
    <?php
}

/**
 * Displays Space usage and row statistics
 */
// BEGIN - Calc Table Space
// Get valid statistics whatever is the table type
if ($cfg['ShowStats']) {
    echo '<div id="tablestatistics">';
    if (empty($showtable)) {
        $showtable = PMA_Table::sGetStatusInfo(
            $GLOBALS['db'], $GLOBALS['table'], null, true
        );
    }

    $nonisam     = false;
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');
    if (isset($showtable['Type'])
        && ! preg_match('@ISAM|HEAP@i', $showtable['Type'])
    ) {
        $nonisam = true;
    }

    // Gets some sizes

    $mergetable = PMA_Table::isMerge($GLOBALS['db'], $GLOBALS['table']);

    // this is to display for example 261.2 MiB instead of 268k KiB
    $max_digits = 3;
    $decimals = 1;
    list($data_size, $data_unit) = $common_functions->formatByteDown(
        $showtable['Data_length'], $max_digits, $decimals
    );
    if ($mergetable == false) {
        list($index_size, $index_unit) = $common_functions->formatByteDown(
            $showtable['Index_length'], $max_digits, $decimals
        );
    }
    // InnoDB returns a huge value in Data_free, do not use it
    if (! $is_innodb
        && isset($showtable['Data_free'])
        && $showtable['Data_free'] > 0
    ) {
        list($free_size, $free_unit) = $common_functions->formatByteDown(
            $showtable['Data_free'], $max_digits, $decimals
        );
        list($effect_size, $effect_unit) = $common_functions->formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free'],
            $max_digits, $decimals
        );
    } else {
        list($effect_size, $effect_unit) = $common_functions->formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'],
            $max_digits, $decimals
        );
    }
    list($tot_size, $tot_unit) = $common_functions->formatByteDown(
        $showtable['Data_length'] + $showtable['Index_length'],
        $max_digits, $decimals
    );
    if ($table_info_num_rows > 0) {
        list($avg_size, $avg_unit) = $common_functions->formatByteDown(
            ($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1
        );
    }

    // Displays them
    $odd_row = false;
    ?>

    <fieldset>
    <legend><?php echo __('Information'); ?></legend>
    <a id="showusage"></a>
    <?php if (! $tbl_is_view && ! $db_is_information_schema) { ?>
    <table id="tablespaceusage" class="data">
    <caption class="tblHeaders"><?php echo __('Space usage'); ?></caption>
    <tbody>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Data'); ?></th>
        <td class="value"><?php echo $data_size; ?></td>
        <td class="unit"><?php echo $data_unit; ?></td>
    </tr>
        <?php
        if (isset($index_size)) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Index'); ?></th>
        <td class="value"><?php echo $index_size; ?></td>
        <td class="unit"><?php echo $index_unit; ?></td>
    </tr>
            <?php
        }
        if (isset($free_size)) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?> error">
        <th class="name"><?php echo __('Overhead'); ?></th>
        <td class="value"><?php echo $free_size; ?></td>
        <td class="unit"><?php echo $free_unit; ?></td>
    </tr>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Effective'); ?></th>
        <td class="value"><?php echo $effect_size; ?></td>
        <td class="unit"><?php echo $effect_unit; ?></td>
    </tr>
            <?php
        }
        if (isset($tot_size) && $mergetable == false) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Total'); ?></th>
        <td class="value"><?php echo $tot_size; ?></td>
        <td class="unit"><?php echo $tot_unit; ?></td>
    </tr>
            <?php
        }
        // Optimize link if overhead
        if (isset($free_size) && !PMA_DRIZZLE && ($tbl_storage_engine == 'MYISAM' || $tbl_storage_engine == 'ARIA' || $tbl_storage_engine == 'MARIA' || $tbl_storage_engine == 'BDB')) {
            ?>
    <tr class="tblFooters">
        <td colspan="3" class="center">
            <a href="sql.php?<?php echo $url_query; ?>&pos=0&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . $common_functions->backquote($table)); ?>"><?php
            echo $common_functions->getIcon('b_tbloptimize.png', __('Optimize table'));
            ?></a>
        </td>
    </tr>
            <?php
        }
        ?>
    </tbody>
    </table>
        <?php
    }
    $odd_row = false;
    ?>
    <table id="tablerowstats" class="data">
    <caption class="tblHeaders"><?php echo __('Row Statistics'); ?></caption>
    <tbody>
    <?php
    if (isset($showtable['Row_format'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Format'); ?></th>
        <td class="value"><?php
        if ($showtable['Row_format'] == 'Fixed') {
            echo __('static');
        } elseif ($showtable['Row_format'] == 'Dynamic') {
            echo __('dynamic');
        } else {
            echo $showtable['Row_format'];
        }
        ?></td>
    </tr>
        <?php
    }
    if (! empty($showtable['Create_options'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Options'); ?></th>
        <td class="value"><?php
        if ($showtable['Create_options'] == 'partitioned') {
            echo __('partitioned');
        } else {
            echo $showtable['Create_options'];
        }
        ?></td>
    </tr>
        <?php
    }
    if (!empty($tbl_collation)) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Collation'); ?></th>
        <td class="value"><?php
            echo '<dfn title="' . PMA_getCollationDescr($tbl_collation) . '">' . $tbl_collation . '</dfn>';
            ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Rows'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Rows'); ?></th>
        <td class="value"><?php echo $common_functions->formatNumber($showtable['Rows'], 0); ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Row length'); ?> &oslash;</th>
        <td class="value"><?php echo $common_functions->formatNumber($showtable['Avg_row_length'], 0); ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == false) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Row size'); ?> &oslash;</th>
        <td class="value"><?php echo $avg_size . ' ' . $avg_unit; ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Auto_increment'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Next autoindex'); ?></th>
        <td class="value"><?php echo $common_functions->formatNumber($showtable['Auto_increment'], 0); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Create_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Creation'); ?></th>
        <td class="value"><?php echo $common_functions->localisedDate(strtotime($showtable['Create_time'])); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Update_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Last update'); ?></th>
        <td class="value"><?php echo $common_functions->localisedDate(strtotime($showtable['Update_time'])); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Check_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo __('Last check'); ?></th>
        <td class="value"><?php echo $common_functions->localisedDate(strtotime($showtable['Check_time'])); ?></td>
    </tr>
        <?php
    }
    ?>
    </tbody>
    </table>
    </fieldset>
    <!-- close tablestatistics div -->
    </div>

    <?php
}
// END - Calc Table Space

echo '<div class="clearfloat"></div>' . "\n";

?>
