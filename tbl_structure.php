<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.lib.php';
require_once './libraries/relation.lib.php';

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();


/**
 * Drop multiple fields if required
 */

// workaround for IE problem:
if (isset($submit_mult_change_x)) {
    $submit_mult = $strChange;
} elseif (isset($submit_mult_drop_x)) {
    $submit_mult = $strDrop;
} elseif (isset($submit_mult_primary_x)) {
    $submit_mult = $strPrimary;
} elseif (isset($submit_mult_index_x)) {
    $submit_mult = $strIndex;
} elseif (isset($submit_mult_unique_x)) {
    $submit_mult = $strUnique;
} elseif (isset($submit_mult_fulltext_x)) {
    $submit_mult = $strIdxFulltext;
}

if ((!empty($submit_mult) && isset($selected_fld))
    || isset($mult_btn)) {
    $action = 'tbl_structure.php';
    $err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);
    require_once('./libraries/header.inc.php');
    require_once './libraries/tbl_links.inc.php';
    require './libraries/mult_submits.inc.php';
}

/**
 * Runs common work
 */
require_once './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_structure.php&amp;back=tbl_structure.php';

/**
 * Prepares the table structure display
 */

/**
 * Gets tables informations
 */
require_once './libraries/tbl_info.inc.php';

/**
 * Show result of multi submit operation
 */
if ((!empty($submit_mult) && isset($selected_fld))
    || isset($mult_btn)) {
    $message = $strSuccess;
}

/**
 * Displays top menu links
 */
require_once './libraries/tbl_links.inc.php';

// 2. Gets table keys and retains them
$result      = PMA_DBI_query('SHOW INDEX FROM ' . PMA_backquote($table) . ';');
$primary     = '';
$ret_keys    = array();
$pk_array    = array(); // will be use to emphasis prim. keys in the table view
while ($row = PMA_DBI_fetch_assoc($result)) {
    $ret_keys[]  = $row;
    // Backups the list of primary keys
    if ($row['Key_name'] == 'PRIMARY') {
        $primary .= $row['Column_name'] . ', ';
        $pk_array[$row['Column_name']] = 1;
    }
} // end while
PMA_DBI_free_result($result);

// 3. Get fields
$fields_rs   = PMA_DBI_query('SHOW FULL FIELDS FROM ' . PMA_backquote($table) . ';', null, PMA_DBI_QUERY_STORE);
$fields_cnt  = PMA_DBI_num_rows($fields_rs);

// Get more complete field information
// For now, this is done just for MySQL 4.1.2+ new TIMESTAMP options
// but later, if the analyser returns more information, it
// could be executed for any MySQL version and replace
// the info given by SHOW FULL FIELDS FROM.
//
// We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
// SHOW FULL FIELDS or INFORMATION_SCHEMA incorrectly says NULL
// and SHOW CREATE TABLE says NOT NULL (tested
// in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

$show_create_table = PMA_DBI_fetch_value(
        'SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table),
        0, 1);
$analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

/**
 * prepare table infos
 */
// action titles (image or string)
$titles = array();
if ($cfg['PropertiesIconic'] == true) {
    if ($cfg['PropertiesIconic'] === 'both') {
        $iconic_spacer = '<div class="nowrap">';
    } else {
        $iconic_spacer = '';
    }

    // images replaced 2004-05-08 by mkkeck
    $titles['Change']        = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_edit.png" alt="' . $strChange . '" title="' . $strChange . '" />';
    $titles['Drop']          = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" />';
    $titles['NoDrop']        = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" />';
    $titles['Primary']       = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_primary.png" alt="' . $strPrimary . '" title="' . $strPrimary . '" />';
    $titles['Index']         = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_index.png" alt="' . $strIndex . '" title="' . $strIndex . '" />';
    $titles['Unique']        = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_unique.png" alt="' . $strUnique . '" title="' . $strUnique . '" />';
    $titles['IdxFulltext']   = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_ftext.png" alt="' . $strIdxFulltext . '" title="' . $strIdxFulltext . '" />';
    $titles['NoPrimary']     = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'bd_primary.png" alt="' . $strPrimary . '" title="' . $strPrimary . '" />';
    $titles['NoIndex']       = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'bd_index.png" alt="' . $strIndex . '" title="' . $strIndex . '" />';
    $titles['NoUnique']      = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'bd_unique.png" alt="' . $strUnique . '" title="' . $strUnique . '" />';
    $titles['NoIdxFulltext'] = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'bd_ftext.png" alt="' . $strIdxFulltext . '" title="' . $strIdxFulltext . '" />';
    $titles['BrowseDistinctValues']        = $iconic_spacer
        . '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_browse.png" alt="' . $strBrowseDistinctValues . '" title="' . $strBrowseDistinctValues . '" />';

    if ($cfg['PropertiesIconic'] === 'both') {
        $titles['Change']               .= $strChange . '</div>';
        $titles['Drop']                 .= $strDrop . '</div>';
        $titles['NoDrop']               .= $strDrop . '</div>';
        $titles['Primary']              .= $strPrimary . '</div>';
        $titles['Index']                .= $strIndex . '</div>';
        $titles['Unique']               .= $strUnique . '</div>';
        $titles['IdxFulltext'  ]        .= $strIdxFulltext . '</div>';
        $titles['NoPrimary']            .= $strPrimary . '</div>';
        $titles['NoIndex']              .= $strIndex . '</div>';
        $titles['NoUnique']             .= $strUnique . '</div>';
        $titles['NoIdxFulltext']        .= $strIdxFulltext . '</div>';
        $titles['BrowseDistinctValues'] .= $strBrowseDistinctValues . '</div>';
    }
} else {
    $titles['Change']               = $strChange;
    $titles['Drop']                 = $strDrop;
    $titles['NoDrop']               = $strDrop;
    $titles['Primary']              = $strPrimary;
    $titles['Index']                = $strIndex;
    $titles['Unique']               = $strUnique;
    $titles['IdxFulltext']          = $strIdxFulltext;
    $titles['NoPrimary']            = $strPrimary;
    $titles['NoIndex']              = $strIndex;
    $titles['NoUnique']             = $strUnique;
    $titles['NoIdxFulltext']        = $strIdxFulltext;
    $titles['BrowseDistinctValues'] = $strBrowseDistinctValues;
}

/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 */
/* TABLE INFORMATION */
// table header
$i = 0;
?>
<form method="post" action="tbl_structure.php" name="fieldsForm" id="fieldsForm">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<table id="tablestructure" class="data">
<thead>
<tr>
    <th id="th<?php echo ++$i; ?>"></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strField; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strType; ?></th>
<?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '    <th id="th' . ++$i . '">' . $strCollation . '</th>' . "\n" : ''; ?>
    <th id="th<?php echo ++$i; ?>"><?php echo $strAttr; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strNull; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strDefault; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strExtra; ?></th>
<?php if ($db_is_information_schema || $tbl_is_view) { ?>
    <th id="th<?php echo ++$i; ?>"><?php echo $strView; ?></th>
<?php } else { ?>
    <th colspan="7" id="th<?php echo ++$i; ?>"><?php echo $strAction; ?></th>
<?php } ?>
</tr>
</thead>
<tbody>
<?php
unset($i);


// table body

// prepare comments
$comments_map = array();
$mime_map = array();

if ($GLOBALS['cfg']['ShowPropertyComments']) {
    require_once './libraries/relation.lib.php';
    require_once './libraries/transformations.lib.php';

    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork'] || PMA_MYSQL_INT_VERSION >= 40100) {
        $comments_map = PMA_getComments($db, $table);

        if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
            $mime_map = PMA_getMIME($db, $table, true);
        }
    }
}

$rownum    = 0;
$aryFields = array();
$checked   = (!empty($checkall) ? ' checked="checked"' : '');
$save_row  = array();
$odd_row   = true;
while ($row = PMA_DBI_fetch_assoc($fields_rs)) {
    $save_row[] = $row;
    $rownum++;
    $aryFields[]      = $row['Field'];

    $type             = $row['Type'];
    $type_and_length = PMA_extract_type_length($row['Type']);

    // reformat mysql query output - staybyte - 9. June 2001
    // loic1: set or enum types: slashes single quotes inside options
    if (preg_match('@^(set|enum)\((.+)\)$@i', $type, $tmp)) {
        $tmp[2]       = substr(preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1);
        $type         = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';

        // for the case ENUM('&#8211;','&ldquo;')
        $type         = htmlspecialchars($type);

        $type_nowrap  = '';

        $binary       = 0;
        $unsigned     = 0;
        $zerofill     = 0;
    } else {
        $type_nowrap  = ' nowrap="nowrap"';
        // strip the "BINARY" attribute, except if we find "BINARY(" because
        // this would be a BINARY or VARBINARY field type
        if (!preg_match('@BINARY[\(]@i', $type)) {
            $type         = preg_replace('@BINARY@i', '', $type);
        }
        $type         = preg_replace('@ZEROFILL@i', '', $type);
        $type         = preg_replace('@UNSIGNED@i', '', $type);
        if (empty($type)) {
            $type     = ' ';
        }

        if (!preg_match('@BINARY[\(]@i', $row['Type'])) {
            $binary           = stristr($row['Type'], 'blob') || stristr($row['Type'], 'binary');
        } else {
            $binary           = false;
        }

        $unsigned     = stristr($row['Type'], 'unsigned');
        $zerofill     = stristr($row['Type'], 'zerofill');
    }

    // rabus: Divide charset from the rest of the type definition (MySQL >= 4.1)
    unset($field_charset);
    if (PMA_MYSQL_INT_VERSION >= 40100) {
        if ((substr($type, 0, 4) == 'char'
            || substr($type, 0, 7) == 'varchar'
            || substr($type, 0, 4) == 'text'
            || substr($type, 0, 8) == 'tinytext'
            || substr($type, 0, 10) == 'mediumtext'
            || substr($type, 0, 8) == 'longtext'
            || substr($type, 0, 3) == 'set'
            || substr($type, 0, 4) == 'enum'
            ) && !$binary) {
            if (strpos($type, ' character set ')) {
                $type = substr($type, 0, strpos($type, ' character set '));
            }
            if (!empty($row['Collation'])) {
                $field_charset = $row['Collation'];
            } else {
                $field_charset = '';
            }
        } else {
            $field_charset = '';
        }
    }

    // garvin: Display basic mimetype [MIME]
    if ($cfgRelation['commwork'] && $cfgRelation['mimework'] && $cfg['BrowseMIME'] && isset($mime_map[$row['Field']]['mimetype'])) {
        $type_mime = '<br />MIME: ' . str_replace('_', '/', $mime_map[$row['Field']]['mimetype']);
    } else {
        $type_mime = '';
    }

    $attribute     = ' ';
    if ($binary) {
        $attribute = 'BINARY';
    }
    if ($unsigned) {
        $attribute = 'UNSIGNED';
    }
    if ($zerofill) {
        $attribute = 'UNSIGNED ZEROFILL';
    }

    // MySQL 4.1.2+ TIMESTAMP options
    // (if on_update_current_timestamp is set, then it's TRUE)
    if (isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['on_update_current_timestamp'])) {
        $attribute = 'ON UPDATE CURRENT_TIMESTAMP';
    }

    // here, we have a TIMESTAMP that SHOW FULL FIELDS reports as having the
    // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
    // the latter.
    if (!empty($analyzed_sql[0]['create_table_fields'][$row['Field']]['type']) && $analyzed_sql[0]['create_table_fields'][$row['Field']]['type'] == 'TIMESTAMP' && $analyzed_sql[0]['create_table_fields'][$row['Field']]['timestamp_not_null']) {
        $row['Null'] = '';
    }


    if (!isset($row['Default'])) {
        if ($row['Null'] == 'YES') {
            $row['Default'] = '<i>NULL</i>';
        }
    } else {
        $row['Default'] = htmlspecialchars($row['Default']);
    }

    $field_encoded = urlencode($row['Field']);
    $field_name    = htmlspecialchars($row['Field']);

    // garvin: underline commented fields and display a hover-title (CSS only)

    $comment_style = '';
    if (isset($comments_map[$row['Field']])) {
        $field_name = '<span style="border-bottom: 1px dashed black;" title="' . htmlspecialchars($comments_map[$row['Field']]) . '">' . $field_name . '</span>';
    }

    if (isset($pk_array[$row['Field']])) {
        $field_name = '<u>' . $field_name . '</u>';
    }
    echo "\n";
    ?>
<tr class="<?php echo $odd_row ? 'odd': 'even'; $odd_row = !$odd_row; ?>">
    <td align="center">
        <input type="checkbox" name="selected_fld[]" value="<?php echo $field_encoded; ?>" id="checkbox_row_<?php echo $rownum; ?>" <?php echo $checked; ?> />
    </td>
    <th nowrap="nowrap"><label for="checkbox_row_<?php echo $rownum; ?>"><?php echo $field_name; ?></label></th>
    <td<?php echo $type_nowrap; ?>><bdo dir="ltr" xml:lang="en"><?php echo $type; echo $type_mime; ?></bdo></td>
<?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '    <td>' . (empty($field_charset) ? '' : '<dfn title="' . PMA_getCollationDescr($field_charset) . '">' . $field_charset . '</dfn>') . '</td>' . "\n" : '' ?>
    <td nowrap="nowrap" style="font-size: 70%"><?php echo $attribute; ?></td>
    <td><?php echo (($row['Null'] == 'YES') ? $strYes : $strNo); ?></td>
    <td nowrap="nowrap"><?php
    if (isset($row['Default'])) { 
        if ($type_and_length['type'] == 'bit') {
            echo PMA_printable_bit_value($row['Default'], $type_and_length['length']);
        } else {
            echo $row['Default']; 
        }
    } ?></td>
    <td nowrap="nowrap"><?php echo $row['Extra']; ?></td>
    <td align="center">
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT COUNT(*) AS ' . PMA_backquote($strRows) . ', ' . PMA_backquote($row['Field']) . ' FROM ' . PMA_backquote($table) . ' GROUP BY ' . PMA_backquote($row['Field']) . ' ORDER BY ' . PMA_backquote($row['Field'])); ?>">
            <?php echo $titles['BrowseDistinctValues']; ?></a>
    </td>
    <?php if (! $tbl_is_view && ! $db_is_information_schema) { ?>
    <td align="center">
        <a href="tbl_alter.php?<?php echo $url_query; ?>&amp;field=<?php echo $field_encoded; ?>">
            <?php echo $titles['Change']; ?></a>
    </td>
    <td align="center">
        <?php
        // loic1: Drop field only if there is more than one field in the table
        if ($fields_cnt > 1) {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP ' . PMA_backquote($row['Field'])); ?>&amp;cpurge=1&amp;purgekey=<?php echo urlencode($row['Field']); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strFieldHasBeenDropped, htmlspecialchars($row['Field']))); ?>"
            onclick="return confirmLink(this, 'ALTER TABLE <?php echo PMA_jsFormat($table); ?> DROP <?php echo PMA_jsFormat($row['Field']); ?>')">
            <?php echo $titles['Drop']; ?></a>
            <?php
        } else {
            echo "\n" . '        ' . $titles['NoDrop'];
        }
        echo "\n";
        ?>
    </td>
    <td align="center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_type) {
            echo $titles['NoPrimary'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . (empty($primary) ? '' : ' DROP PRIMARY KEY,') . ' ADD PRIMARY KEY(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAPrimaryKey, htmlspecialchars($row['Field']))); ?>"
            onclick="return confirmLink(this, 'ALTER TABLE <?php echo PMA_jsFormat($table) . (empty($primary) ? '' : ' DROP PRIMARY KEY,'); ?> ADD PRIMARY KEY(<?php echo PMA_jsFormat($row['Field']); ?>)')">
            <?php echo $titles['Primary']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <td align="center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_type) {
            echo $titles['NoUnique'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD UNIQUE(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex, htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Unique']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <td align="center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_type) {
            echo $titles['NoIndex'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD INDEX(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex, htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Index']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <?php
        if ((!empty($tbl_type) && $tbl_type == 'MYISAM')
            // FULLTEXT is possible on TEXT, CHAR and VARCHAR
            && (strpos(' ' . $type, 'text') || strpos(' ' . $type, 'char'))) {
            echo "\n";
            ?>
    <td align="center" nowrap="nowrap">
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD FULLTEXT(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex, htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['IdxFulltext']; ?></a>
    </td>
            <?php
        } else {
            echo "\n";
        ?>
    <td align="center" nowrap="nowrap">
        <?php echo $titles['NoIdxFulltext'] . "\n"; ?>
    </td>
        <?php
        } // end if... else...
        echo "\n";
    } // end if (! $tbl_is_view && ! $db_is_information_schema)
    ?>
</tr>
    <?php
    unset($field_charset);
} // end while

echo '</tbody>' . "\n"
    .'</table>' . "\n";

$checkall_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);
?>

<img class="selectallarrow" src="<?php echo $pmaThemeImage . 'arrow_' . $text_dir . '.png'; ?>"
    width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
<a href="<?php echo $checkall_url; ?>&amp;checkall=1"
    onclick="if (markAllRows('fieldsForm')) return false;">
    <?php echo $strCheckAll; ?></a>
/
<a href="<?php echo $checkall_url; ?>"
    onclick="if (unMarkAllRows('fieldsForm')) return false;">
    <?php echo $strUncheckAll; ?></a>

<i><?php echo $strWithChecked; ?></i>

<?php
if ($cfg['PropertiesIconic']) {
    PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_browse', $strBrowse, 'b_browse.png');
} else {
    echo '<input type="submit" name="submit_mult" value="' . $strChange . '" title="' . $strChange . '" />' . "\n";
}
if (! $tbl_is_view && ! $db_is_information_schema) {
    if ($cfg['PropertiesIconic']) {
        PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_change', $strChange, 'b_edit.png');
        // Drop button if there is at least two fields
        if ($fields_cnt > 1) {
            PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_drop', $strDrop, 'b_drop.png');
        }
        if ('ARCHIVE' != $tbl_type) {
            PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_primary', $strPrimary, 'b_primary.png');
            PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_unique', $strUnique, 'b_unique.png');
            PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_index', $strIndex, 'b_index.png');
        }
        if ((!empty($tbl_type) && $tbl_type == 'MYISAM')) {
            PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_fulltext', $strIdxFulltext, 'b_ftext.png');
        }
    } else {
        // Drop button if there is at least two fields
        if ($fields_cnt > 1) {
            echo '<i>' . $strOr . '</i>' . "\n"
               . '<input type="submit" name="submit_mult" value="' . $strDrop . '" title="' . $strDrop . '" />' . "\n";
        }
        if ('ARCHIVE' != $tbl_type) {
            echo '<i>' . $strOr . '</i>' . "\n"
                . '<input type="submit" name="submit_mult" value="' . $strPrimary . '" title="' . $strPrimary . '" />' . "\n";
            echo '<i>' . $strOr . '</i>' . "\n"
                . '<input type="submit" name="submit_mult" value="' . $strIndex . '" title="' . $strIndex . '" />' . "\n";
            echo '<i>' . $strOr . '</i>' . "\n"
                . '<input type="submit" name="submit_mult" value="' . $strUnique . '" title="' . $strUnique . '" />' . "\n";
        }
        if ((!empty($tbl_type) && $tbl_type == 'MYISAM')) {
            echo '<i>' . $strOr . '</i>' . "\n"
               . '<input type="submit" name="submit_mult" value="' . $strIdxFulltext . '" title="' . $strIdxFulltext . '" />' . "\n";
        }
    }
}
?>
</form>
<hr />

<?php
/**
 * Work on the table
 */
?>
<a href="tbl_printview.php?<?php echo $url_query; ?>"><?php
if ($cfg['PropertiesIconic']) {
    echo '<img class="icon" src="' . $pmaThemeImage . 'b_print.png" width="16" height="16" alt="' . $strPrintView . '"/>';
}
echo $strPrintView;
?></a>

<?php
if (! $tbl_is_view && ! $db_is_information_schema) {

    // if internal relations are available, or the table type is INNODB
    // ($tbl_type comes from libraries/tbl_info.inc.php)
    if ($cfgRelation['relwork'] || $tbl_type=="INNODB") {
        ?>
<a href="tbl_relation.php?<?php echo $url_query; ?>"><?php
        if ($cfg['PropertiesIconic']) {
            echo '<img class="icon" src="' . $pmaThemeImage . 'b_relations.png" width="16" height="16" alt="' . $strRelationView . '"/>';
        }
        echo $strRelationView;
        ?></a>
        <?php
    }
    ?>
<a href="sql.php?<?php echo $url_query; ?>&amp;session_max_rows=all&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table) . ' PROCEDURE ANALYSE()'); ?>"><?php
    if ($cfg['PropertiesIconic']) {
        echo '<img class="icon" src="' . $pmaThemeImage . 'b_tblanalyse.png" width="16" height="16" alt="' . $strStructPropose . '" />';
    }
    echo $strStructPropose;
    ?></a><?php
    echo PMA_showMySQLDocu('Extending_MySQL', 'procedure_analyse') . "\n";
    ?><br />
<form method="post" action="tbl_addfield.php"
    onsubmit="return checkFormElementInRange(this, 'num_fields', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidFieldAddCount']); ?>', 1)">
    <?php
    echo PMA_generate_common_hidden_inputs($db, $table);
    if ($cfg['PropertiesIconic']) {
        echo '<img class="icon" src="' . $pmaThemeImage . 'b_insrow.png" width="16" height="16" alt="' . $strAddNewField . '"/>';
    }
    echo sprintf($strAddFields, '<input type="text" name="num_fields" size="2" maxlength="2" value="1" style="vertical-align: middle" onfocus="this.select()" />');
    ?>
<input type="radio" name="field_where" id="radio_field_where_last" value="last" checked="checked" /><label for="radio_field_where_last"><?php echo $strAtEndOfTable; ?></label>
<input type="radio" name="field_where" id="radio_field_where_first" value="first" /><label for="radio_field_where_first"><?php echo $strAtBeginningOfTable; ?></label>
<input type="radio" name="field_where" id="radio_field_where_after" value="after" /><?php
    $fieldOptions = '</label><select name="after_field" style="vertical-align: middle" onclick="this.form.field_where[2].checked=true" onchange="this.form.field_where[2].checked=true">';
    foreach ($aryFields as $fieldname) {
        $fieldOptions .= '<option value="' . htmlspecialchars($fieldname) . '">' . htmlspecialchars($fieldname) . '</option>' . "\n";
    }
    unset($aryFields);
    $fieldOptions .= '</select><label for="radio_field_where_after">';
    echo str_replace('<label for="radio_field_where_after"></label>', '', '<label for="radio_field_where_after">' . sprintf($strAfter, $fieldOptions) . '</label>') . "\n";
    ?>
<input type="submit" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
</form>
<hr />
    <?php
}

/**
 * If there are more than 20 rows, displays browse/select/insert/empty/drop
 * links again
 */
if ($fields_cnt > 20) {
    require './libraries/tbl_links.inc.php';
} // end if ($fields_cnt > 20)
echo "\n\n";

/**
 * Displays indexes
 */
echo '<div id="tablestatistics">' . "\n";
// $tbl_type is a global variable from libraries/tbl_info.inc.php
if (! $tbl_is_view && ! $db_is_information_schema && 'ARCHIVE' !=  $tbl_type) {
    define('PMA_IDX_INCLUDED', 1);
    require './tbl_indexes.php';
}

/**
 * Displays Space usage and row statistics
 */
// BEGIN - Calc Table Space - staybyte - 9 June 2001
// loic1, 22 feb. 2002: updated with patch from
//                      Joshua Nye <josh at boxcarmedia.com> to get valid
//                      statistics whatever is the table type
if ($cfg['ShowStats']) {
    $nonisam     = false;
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');
    if (isset($showtable['Type']) && !preg_match('@ISAM|HEAP@i', $showtable['Type'])) {
        $nonisam = true;
    }

    // Gets some sizes
    $mergetable     = false;
    if (isset($showtable['Type']) && $showtable['Type'] == 'MRG_MyISAM') {
        $mergetable = true;
    }
    // this is to display for example 261.2 MiB instead of 268k KiB
    $max_digits = 5;
    $decimals = 1;
    list($data_size, $data_unit)         = PMA_formatByteDown($showtable['Data_length'], $max_digits, $decimals);
    if ($mergetable == false) {
        list($index_size, $index_unit)   = PMA_formatByteDown($showtable['Index_length'], $max_digits, $decimals);
    }
    if (isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
        list($free_size, $free_unit)     = PMA_formatByteDown($showtable['Data_free'], $max_digits, $decimals);
        list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free'], $max_digits, $decimals);
    } else {
        list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'], $max_digits, $decimals);
    }
    list($tot_size, $tot_unit)           = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'], $max_digits, $decimals);
    if ($table_info_num_rows > 0) {
        list($avg_size, $avg_unit)       = PMA_formatByteDown(($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1);
    }

    // Displays them
    $odd_row = false;
    ?>

    <a name="showusage"></a>
    <?php if (! $tbl_is_view && ! $db_is_information_schema) { ?>
    <table id="tablespaceusage" class="data">
    <caption class="tblHeaders"><?php echo $strSpaceUsage; ?></caption>
    <thead>
    <tr>
        <th><?php echo $strType; ?></th>
        <th colspan="2"><?php echo $strUsage; ?></th>
    </tr>
    </thead>
    <tbody>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strData; ?></th>
        <td class="value"><?php echo $data_size; ?></td>
        <td class="unit"><?php echo $data_unit; ?></td>
    </tr>
        <?php
        if (isset($index_size)) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strIndex; ?></th>
        <td class="value"><?php echo $index_size; ?></td>
        <td class="unit"><?php echo $index_unit; ?></td>
    </tr>
            <?php
        }
        if (isset($free_size)) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?> warning">
        <th class="name"><?php echo $strOverhead; ?></th>
        <td class="value"><?php echo $free_size; ?></td>
        <td class="unit"><?php echo $free_unit; ?></td>
    </tr>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strEffective; ?></th>
        <td class="value"><?php echo $effect_size; ?></td>
        <td class="unit"><?php echo $effect_unit; ?></td>
    </tr>
            <?php
        }
        if (isset($tot_size) && $mergetable == false) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strTotalUC; ?></th>
        <td class="value"><?php echo $tot_size; ?></td>
        <td class="unit"><?php echo $tot_unit; ?></td>
    </tr>
            <?php
        }
        // Optimize link if overhead
        if (isset($free_size) && ($tbl_type == 'MYISAM' || $tbl_type == 'BDB')) {
            ?>
    <tr class="tblFooters">
        <td colspan="3" align="center">
            <a href="sql.php?<?php echo $url_query; ?>&pos=0&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . PMA_backquote($table)); ?>"><?php
            if ($cfg['PropertiesIconic']) {
               echo '<img class="icon" src="' . $pmaThemeImage . 'b_tbloptimize.png" width="16" height="16" alt="' . $strOptimizeTable. '" />';
            }
            echo $strOptimizeTable;
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
    <caption class="tblHeaders"><?php echo $strRowsStatistic; ?></caption>
    <thead>
    <tr>
        <th><?php echo $strStatement; ?></th>
        <th><?php echo $strValue; ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (isset($showtable['Row_format'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strFormat; ?></th>
        <td class="value"><?php
        if ($showtable['Row_format'] == 'Fixed') {
            echo $strFixed;
        } elseif ($showtable['Row_format'] == 'Dynamic') {
            echo $strDynamic;
        } else {
            echo $showtable['Row_format'];
        }
        ?></td>
    </tr>
        <?php
    }
    if (PMA_MYSQL_INT_VERSION >= 40100 && !empty($tbl_collation)) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strCollation; ?></th>
        <td class="value"><?php
            echo '<dfn title="' . PMA_getCollationDescr($tbl_collation) . '">' . $tbl_collation . '</dfn>';
            ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Rows'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strRows; ?></th>
        <td class="value"><?php echo PMA_formatNumber($showtable['Rows'], 0); ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strRowLength; ?> &oslash;</th>
        <td class="value"><?php echo PMA_formatNumber($showtable['Avg_row_length'], 0); ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == false) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strRowSize; ?> &oslash;</th>
        <td class="value"><?php echo $avg_size . ' ' . $avg_unit; ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Auto_increment'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strNext; ?> Autoindex</th>
        <td class="value"><?php echo PMA_formatNumber($showtable['Auto_increment'], 0); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Create_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strStatCreateTime; ?></th>
        <td class="value"><?php echo PMA_localisedDate(strtotime($showtable['Create_time'])); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Update_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strStatUpdateTime; ?></th>
        <td class="value"><?php echo PMA_localisedDate(strtotime($showtable['Update_time'])); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Check_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strStatCheckTime; ?></th>
        <td class="value"><?php echo PMA_localisedDate(strtotime($showtable['Check_time'])); ?></td>
    </tr>
        <?php
    }
    ?>
    </tbody>
    </table>
    <?php
}
// END - Calc Table Space

require './libraries/tbl_triggers.lib.php';

echo '<div class="clearfloat"></div>' . "\n";
echo '</div>' . "\n";

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
