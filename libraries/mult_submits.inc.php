<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Prepares the work and runs some other scripts if required
 */
if (! empty($submit_mult)
 && $submit_mult != __('With selected:')
 && (! empty($selected_db)
  || ! empty($selected_tbl)
  || ! empty($selected_fld)
  || ! empty($rows_to_delete))) {
    define('PMA_SUBMIT_MULT', 1);
    if (isset($selected_db) && !empty($selected_db)) {
        // coming from server database view - do something with selected databases
        $selected     = $selected_db;
        $what         = 'drop_db';
    } elseif (isset($selected_tbl) && !empty($selected_tbl)) {
        // coming from database structure view - do something with selected tables
        if ($submit_mult == 'print') {
            include './tbl_printview.php';
        } else {
           $selected = $selected_tbl;
           switch ($submit_mult) {
               case 'add_prefix_tbl':
               case 'replace_prefix_tbl':
               case 'copy_tbl_change_prefix':
               case 'drop_db':
               case 'drop_tbl':
               case 'empty_tbl':
                   $what = $submit_mult;
                   break;
               case 'check_tbl':
               case 'optimize_tbl':
               case 'repair_tbl':
               case 'analyze_tbl':
                   $query_type = $submit_mult;
                   unset($submit_mult);
                   $mult_btn   = __('Yes');
                   break;
               case 'export':
                   unset($submit_mult);
                   include 'db_export.php';
                   exit;
                   break;
           } // end switch
        }
    } elseif (isset($selected_fld) && !empty($selected_fld)) {
        // coming from table structure view - do something with selected columns/fileds
        $selected     = $selected_fld;
        switch ($submit_mult) {
            case 'drop':
                $what     = 'drop_fld';
                break;
            case 'primary':
                // Gets table primary key
                PMA_DBI_select_db($db);
                $result      = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($table) . ';');
                $primary     = '';
                while ($row = PMA_DBI_fetch_assoc($result)) {
                    // Backups the list of primary keys
                    if ($row['Key_name'] == 'PRIMARY') {
                        $primary .= $row['Column_name'] . ', ';
                    }
                } // end while
                PMA_DBI_free_result($result);
                if (empty($primary)) {
                    // no primary key, so we can safely create new
                    unset($submit_mult);
                    $query_type = 'primary_fld';
                    $mult_btn   = __('Yes');
                } else {
                    // primary key exists, so lets as user
                    $what = 'primary_fld';
                }
                break;
            case 'index':
                unset($submit_mult);
                $query_type = 'index_fld';
                $mult_btn   = __('Yes');
                break;
            case 'unique':
                unset($submit_mult);
                $query_type = 'unique_fld';
                $mult_btn   = __('Yes');
                break;
            case 'spatial':
                unset($submit_mult);
                $query_type = 'spatial_fld';
                $mult_btn   = __('Yes');
                break;
            case 'ftext':
                unset($submit_mult);
                $query_type = 'fulltext_fld';
                $mult_btn   = __('Yes');
                break;
            case 'change':
                include './tbl_alter.php';
                break;
            case 'browse':
                // this should already be handled by tbl_structure.php
        }
    } else {
        // coming from browsing - do something with selected rows
        $what = 'row_delete';
        $selected = $rows_to_delete;
    }
} // end if


/**
 * Displays the confirmation form if required
 */
if (!empty($submit_mult) && !empty($what)) {
    unset($message);

    include_once './libraries/header.inc.php';
    if (strlen($table)) {
        include './libraries/tbl_common.php';
        $url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';
        include './libraries/tbl_info.inc.php';
        include_once './libraries/tbl_links.inc.php';
    } elseif (strlen($db)) {
        include './libraries/db_common.inc.php';
        include './libraries/db_info.inc.php';
    } else {
        include_once './libraries/server_common.inc.php';
        include_once './libraries/server_links.inc.php';
    }

    // Builds the query
    $full_query     = '';
    if ($what == 'drop_tbl') {
        $full_query_views = '';
    }
    $selected_cnt   = count($selected);
    $i = 0;
    foreach ($selected AS $idx => $sval) {
        switch ($what) {
            case 'row_delete':
                $full_query .= htmlspecialchars($sval)
                    . ';<br />';
                break;
            case 'drop_db':
                $full_query .= 'DROP DATABASE '
                    . PMA_backquote(htmlspecialchars($sval))
                    . ';<br />';
                $reload = 1;
                break;

            case 'drop_tbl':
                $current = $sval;
                if (!empty($views) && in_array($current, $views)) {
                    $full_query_views .= (empty($full_query_views) ? 'DROP VIEW ' : ', ')
                        . PMA_backquote(htmlspecialchars($current));
                } else {
                    $full_query .= (empty($full_query) ? 'DROP TABLE ' : ', ')
                        . PMA_backquote(htmlspecialchars($current));
                }
                break;

            case 'empty_tbl':
                $full_query .= 'TRUNCATE ';
                $full_query .= PMA_backquote(htmlspecialchars($sval))
                            . ';<br />';
                break;

            case 'primary_fld':
                if ($full_query == '') {
                    $full_query .= 'ALTER TABLE '
                        . PMA_backquote(htmlspecialchars($table))
                        . '<br />&nbsp;&nbsp;DROP PRIMARY KEY,'
                        . '<br />&nbsp;&nbsp; ADD PRIMARY KEY('
                        . '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                        . PMA_backquote(htmlspecialchars($sval))
                        . ',';
                } else {
                    $full_query .= '<br />&nbsp;&nbsp;&nbsp;&nbsp; '
                        . PMA_backquote(htmlspecialchars($sval))
                        . ',';
                }
                if ($i == $selected_cnt-1) {
                    $full_query = preg_replace('@,$@', ');<br />', $full_query);
                }
                break;

            case 'drop_fld':
                if ($full_query == '') {
                    $full_query .= 'ALTER TABLE '
                        . PMA_backquote(htmlspecialchars($table));
                }
                $full_query .= '<br />&nbsp;&nbsp;DROP '
                    . PMA_backquote(htmlspecialchars($sval))
                    . ',';
                if ($i == $selected_cnt - 1) {
                    $full_query = preg_replace('@,$@', ';<br />', $full_query);
                }
                break;
        } // end switch
        $i++;
    }
    if ($what == 'drop_tbl') {
        if (!empty($full_query)) {
            $full_query .= ';<br />' . "\n";
        }
        if (!empty($full_query_views)) {
            $full_query .= $full_query_views . ';<br />' . "\n";
        }
        unset($full_query_views);
    }

    // Displays the confirmation form
    $_url_params = array(
        'query_type' => $what,
        'reload' => (! empty($reload) ? 1 : 0),
    );
    if (strpos(' ' . $action, 'db_') == 1) {
        $_url_params['db']= $db;
    } elseif (strpos(' ' . $action, 'tbl_') == 1 || $what == 'row_delete') {
        $_url_params['db']= $db;
        $_url_params['table']= $table;
    }
    foreach ($selected as $idx => $sval) {
        $_url_params['selected'][] = $sval;
    }
    if ($what == 'drop_tbl' && !empty($views)) {
        foreach ($views as $current) {
            $_url_params['views'][] = $current;
       }
    }
    if ($what == 'row_delete') {
        $_url_params['original_sql_query'] = $original_sql_query;
        if (! empty($original_url_query)) {
            $_url_params['original_url_query'] = $original_url_query;
        }
    }
    ?>
<form action="<?php echo $action; ?>" method="post">
    <?php
    echo PMA_generate_common_hidden_inputs($_url_params);
    ?>
<?php if ($what == 'replace_prefix_tbl' || $what == 'copy_tbl_change_prefix') { ?>
        <fieldset class = "input">
                <legend><?php echo ($what == 'replace_prefix_tbl' ? __('Replace table prefix') : __('Copy table with prefix')) ?>:</legend>
                <table>
                <tr>
                <td><?php echo __('From'); ?></td><td><input type="text" name="from_prefix" id="initialPrefix"</td>
                </tr>
                <tr>
                <td><?php echo __('To'); ?> </td><td><input type="text" name="to_prefix" id="newPrefix"</td>
                </tr>
                </table>
        </fieldset>
        <fieldset class="tblFooters">
                <button type="submit" name="mult_btn" value="<?php echo __('Yes'); ?>" id="buttonYes"><?php echo __('Submit'); ?></button>
        </fieldset>
    <?php
        } elseif ($what == 'add_prefix_tbl') { ?>
        <fieldset class = "input">
                <legend><?php echo __('Add table prefix') ?>:</legend>
                <table>
                <tr>
                <td><?php echo __('Add prefix'); ?></td>     <td><input type="text" name="add_prefix" id="txtPrefix"</td>
                </tr>
                </table>
        </fieldset>
        <fieldset class="tblFooters">
                <button type="submit" name="mult_btn" value="<?php echo __('Yes'); ?>" id="buttonYes"><?php echo __('Submit'); ?></button>
        </fieldset>
    <?php
        } else { ?>
    <fieldset class="confirmation">
        <legend><?php echo ($what == 'drop_db' ? __('You are about to DESTROY a complete database!') . '&nbsp;' : '') . __('Do you really want to '); ?>:</legend>
        <tt><?php echo $full_query; ?></tt>
    </fieldset>
    <fieldset class="tblFooters">
        <input type="submit" name="mult_btn" value="<?php echo __('Yes'); ?>" id="buttonYes" />
        <input type="submit" name="mult_btn" value="<?php echo __('No'); ?>" id="buttonNo" />
    </fieldset>
    <?php
    }
    echo '</form>';
    include './libraries/footer.inc.php';

} elseif ($mult_btn == __('Yes')) {
/**
 * Executes the query - dropping rows, columns/fields, tables or dbs
 */
    if ($query_type == 'drop_db' || $query_type == 'drop_tbl' || $query_type == 'drop_fld') {
        include_once './libraries/relation_cleanup.lib.php';
    }

    $sql_query      = '';
    if ($query_type == 'drop_tbl') {
        $sql_query_views = '';
    }
    $selected_cnt   = count($selected);
    $run_parts      = false; // whether to run query after each pass
    $use_sql        = false; // whether to include sql.php at the end (to display results)

    if ($query_type == 'primary_fld') {
        // Gets table primary key
        PMA_DBI_select_db($db);
        $result      = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($table) . ';');
        $primary     = '';
        while ($row = PMA_DBI_fetch_assoc($result)) {
            // Backups the list of primary keys
            if ($row['Key_name'] == 'PRIMARY') {
                $primary .= $row['Column_name'] . ', ';
            }
        } // end while
        PMA_DBI_free_result($result);
    }

    $rebuild_database_list = false;

    for ($i = 0; $i < $selected_cnt; $i++) {
        switch ($query_type) {
            case 'row_delete':
                $a_query = $selected[$i];
                $run_parts = true;
                break;

            case 'drop_db':
                PMA_relationsCleanupDatabase($selected[$i]);
                $a_query   = 'DROP DATABASE '
                           . PMA_backquote($selected[$i]);
                $reload    = 1;
                $run_parts = true;
                $rebuild_database_list = true;
                break;

            case 'drop_tbl':
                PMA_relationsCleanupTable($db, $selected[$i]);
                $current = $selected[$i];
                if (!empty($views) && in_array($current, $views)) {
                    $sql_query_views .= (empty($sql_query_views) ? 'DROP VIEW ' : ', ')
                              . PMA_backquote($current);
                } else {
                    $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ')
                               . PMA_backquote($current);
                }
                $reload    = 1;
                break;

            case 'check_tbl':
                $sql_query .= (empty($sql_query) ? 'CHECK TABLE ' : ', ')
                           . PMA_backquote($selected[$i]);
                $use_sql    = true;
                break;

            case 'optimize_tbl':
                $sql_query .= (empty($sql_query) ? 'OPTIMIZE TABLE ' : ', ')
                           . PMA_backquote($selected[$i]);
                $use_sql    = true;
                break;

            case 'analyze_tbl':
                $sql_query .= (empty($sql_query) ? 'ANALYZE TABLE ' : ', ')
                           . PMA_backquote($selected[$i]);
                $use_sql    = true;
                break;

            case 'repair_tbl':
                $sql_query .= (empty($sql_query) ? 'REPAIR TABLE ' : ', ')
                           . PMA_backquote($selected[$i]);
                $use_sql    = true;
                break;

            case 'empty_tbl':
                $a_query = 'TRUNCATE ';
                $a_query .= PMA_backquote($selected[$i]);
                $run_parts = true;
                break;

            case 'drop_fld':
                PMA_relationsCleanupColumn($db, $table, $selected[$i]);
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) : ',')
                           . ' DROP ' . PMA_backquote($selected[$i])
                           . (($i == $selected_cnt-1) ? ';' : '');
                break;

            case 'primary_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . (empty($primary) ? '' : ' DROP PRIMARY KEY,') . ' ADD PRIMARY KEY( ' : ', ')
                           . PMA_backquote($selected[$i])
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;

            case 'index_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . ' ADD INDEX( ' : ', ')
                           . PMA_backquote($selected[$i])
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;

            case 'unique_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . ' ADD UNIQUE( ' : ', ')
                           . PMA_backquote($selected[$i])
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;

            case 'spatial_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . ' ADD SPATIAL( ' : ', ')
                           . PMA_backquote($selected[$i])
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;

            case 'fulltext_fld':
                $sql_query .= (empty($sql_query) ? 'ALTER TABLE ' . PMA_backquote($table) . ' ADD FULLTEXT( ' : ', ')
                           . PMA_backquote($selected[$i])
                           . (($i == $selected_cnt-1) ? ');' : '');
                break;

        case 'add_prefix_tbl':
                $newtablename = $add_prefix . $selected[$i];
                $a_query = 'ALTER TABLE ' . PMA_backquote($selected[$i]) . ' RENAME ' . PMA_backquote($newtablename) ; // ADD PREFIX TO TABLE NAME
                $run_parts = true;
                break;

            case 'replace_prefix_tbl':
                $current = $selected[$i];
                $newtablename = preg_replace("/^" . $from_prefix . "/", $to_prefix, $current);
                $a_query = 'ALTER TABLE ' . PMA_backquote($selected[$i]) . ' RENAME ' . PMA_backquote($newtablename) ; // CHANGE PREFIX PATTERN
                $run_parts = true;
                break;

            case 'copy_tbl_change_prefix':
                $current = $selected[$i];
                $newtablename = preg_replace("/^" . $from_prefix . "/", $to_prefix, $current);
                $a_query = 'CREATE TABLE ' . PMA_backquote($newtablename) . ' SELECT * FROM ' . PMA_backquote($selected[$i]) ; // COPY TABLE AND CHANGE PREFIX PATTERN
                $run_parts = true;
                break;

        } // end switch

        // All "DROP TABLE", "DROP FIELD", "OPTIMIZE TABLE" and "REPAIR TABLE"
        // statements will be run at once below
        if ($run_parts) {
            $sql_query .= $a_query . ';' . "\n";
            if ($query_type != 'drop_db') {
                PMA_DBI_select_db($db);
            }
            $result = PMA_DBI_query($a_query);
        } // end if
    } // end for

    if ($query_type == 'drop_tbl') {
        if (!empty($sql_query)) {
        $sql_query .= ';';
    } elseif (!empty($sql_query_views)) {
        $sql_query = $sql_query_views . ';';
            unset($sql_query_views);
        }
    }

    if ($use_sql) {
        include './sql.php';
    } elseif (!$run_parts) {
        PMA_DBI_select_db($db);
        $result = PMA_DBI_try_query($sql_query);
        if ($result && !empty($sql_query_views)) {
            $sql_query .= ' ' . $sql_query_views . ';';
            $result = PMA_DBI_try_query($sql_query_views);
            unset($sql_query_views);
        }

        if (! $result) {
            $message = PMA_Message::error(PMA_DBI_getError());
        }
    }
    if ($rebuild_database_list) {
        // avoid a problem with the database list navigator
        // when dropping a db from server_databases
        $GLOBALS['pma']->databases->build();
    }
} else {
    $message = PMA_Message::success(__('No change'));
}
?>
