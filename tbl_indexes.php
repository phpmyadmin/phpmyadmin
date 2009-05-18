<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/Index.class.php';
require_once './libraries/tbl_common.php';

// Get fields and stores their name/type
$fields = array();
foreach (PMA_DBI_get_fields($db, $table) as $row) {
    if (preg_match('@^(set|enum)\((.+)\)$@i', $row['Type'], $tmp)) {
        $tmp[2]         = substr(preg_replace('@([^,])\'\'@', '\\1\\\'',
            ',' . $tmp[2]), 1);
        $fields[$row['Field']] = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
    } else {
        $fields[$row['Field']] = $row['Type'];
    }
} // end while

// Prepares the form values
if (isset($_REQUEST['index'])) {
    if (is_array($_REQUEST['index'])) {
        // coming already from form
        $index = new PMA_Index($_REQUEST['index']);
    } else {
        $index = PMA_Index::singleton($db, $table, $_REQUEST['index']);
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

    // $sql_query is the one displayed in the query box
    $sql_query = 'ALTER TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table);

    // Drops the old index
    if (! empty($_REQUEST['old_index'])) {
        if ($_REQUEST['old_index'] == 'PRIMARY') {
            $sql_query .= ' DROP PRIMARY KEY,';
        } else {
            $sql_query .= ' DROP INDEX ' . PMA_backquote($_REQUEST['old_index']) . ',';
        }
    } // end if

    // Builds the new one
    switch ($index->getType()) {
        case 'PRIMARY':
            if ($index->getName() == '') {
                $index->setName('PRIMARY');
            } elseif ($index->getName() != 'PRIMARY') {
                $error = PMA_Message::error('strPrimaryKeyName');
            }
            $sql_query .= ' ADD PRIMARY KEY';
            break;
        case 'FULLTEXT':
        case 'UNIQUE':
        case 'INDEX':
            if ($index->getName() == 'PRIMARY') {
                $error = PMA_Message::error('strCantRenameIdxToPrimary');
            }
            $sql_query .= ' ADD ' . $index->getType() . ' '
                . ($index->getName() ? PMA_backquote($index->getName()) : '');
            break;
    } // end switch

    $index_fields = array();
    foreach ($index->getColumns() as $key => $column) {
        $index_fields[$key] = PMA_backquote($column->getName());
        if ($column->getSubPart()) {
            $index_fields[$key] .= '(' . $column->getSubPart() . ')';
        }
    } // end while

    if (empty($index_fields)){
        $error = PMA_Message::error('strNoIndexPartsDefined');
    } else {
        $sql_query .= ' (' . implode(', ', $index_fields) . ')';
    }

    if (! $error) {
        PMA_DBI_query($sql_query);
        $message = PMA_Message::success('strTableAlteredSuccessfully');
        $message->addParam($table);

        $active_page = 'tbl_structure.php';
        require './tbl_structure.php';
        exit;
    } else {
        $error->display();
    }
} // end builds the new index


/**
 * Display the form to edit/create an index
 */

// Displays headers (if needed)
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'indexes.js';

require_once './libraries/tbl_info.inc.php';
require_once './libraries/tbl_links.inc.php';

if (isset($_REQUEST['index']) && is_array($_REQUEST['index'])) {
    // coming already from form
    $add_fields =
        count($_REQUEST['index']['columns']['names']) - $index->getColumnCount();
    if (isset($_REQUEST['add_fields'])) {
        $add_fields += $_REQUEST['added_fields'];
    }
} elseif (isset($_REQUEST['create_index'])) {
    $add_fields = $_REQUEST['added_fields'];
} else {
    $add_fields = 1;
}

// end preparing form values
?>

<form action="./tbl_indexes.php" method="post" name="index_frm"
    onsubmit="if (typeof(this.elements['index'].disabled) != 'undefined') {
        this.elements['index'].disabled = false}">
<?php
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

echo PMA_generate_common_hidden_inputs($form_params);
?>
<fieldset>
    <legend>
<?php
echo (isset($_REQUEST['create_index'])
    ? $strCreateIndexTopic
    : $strModifyIndexTopic);
?>
    </legend>

<div class="formelement">
<label for="input_index_name"><?php echo $strIndexName; ?></label>
<input type="text" name="index[Key_name]" id="input_index_name" size="25"
    value="<?php echo htmlspecialchars($index->getName()); ?>" onfocus="this.select()" />
</div>

<div class="formelement">
<label for="select_index_type"><?php echo $strIndexType; ?></label>
<select name="index[Index_type]" id="select_index_type" onchange="return checkIndexName()">
    <?php echo $index->generateIndexSelector(); ?>
</select>
<?php echo PMA_showMySQLDocu('SQL-Syntax', 'ALTER_TABLE'); ?>
</div>


<br class="clearfloat" />
<?php
PMA_Message::warning('strPrimaryKeyWarning')->display();
?>

<table>
<thead>
<tr><th><?php echo $strField; ?></th>
    <th><?php echo $strSize; ?></th>
</tr>
</thead>
<tbody>
<?php
$odd_row = true;
foreach ($index->getColumns() as $column) {
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
    <td><select name="index[columns][names][]">
            <option value="">-- <?php echo $strIgnore; ?> --</option>
    <?php
    foreach ($fields as $field_name => $field_type) {
        if ($index->getType() != 'FULLTEXT'
         || preg_match('/(char|text)/i', $field_type)) {
            echo '<option value="' . htmlspecialchars($field_name) . '"'
                 . (($field_name == $column->getName()) ? ' selected="selected"' : '') . '>'
                 . htmlspecialchars($field_name) . ' [' . $field_type . ']'
                 . '</option>' . "\n";
        }
    } // end foreach $fields
    ?>
        </select>
    </td>
    <td><input type="text" size="5" onfocus="this.select()"
            name="index[columns][sub_parts][]" value="<?php echo $column->getSubPart(); ?>" />
    </td>
</tr>
    <?php
    $odd_row = !$odd_row;
} // end foreach $edited_index_info['Sequences']
for ($i = 0; $i < $add_fields; $i++) {
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
    <td><select name="index[columns][names][]">
            <option value="">-- <?php echo $strIgnore; ?> --</option>
    <?php
    foreach ($fields as $field_name => $field_type) {
        echo '<option value="' . htmlspecialchars($field_name) . '">'
             . htmlspecialchars($field_name) . ' [' . $field_type . ']'
             . '</option>' . "\n";
    } // end foreach $fields
    ?>
        </select>
    </td>
    <td><input type="text" size="5" onfocus="this.select()"
            name="index[columns][sub_parts][]" value="" />
    </td>
</tr>
    <?php
    $odd_row = !$odd_row;
} // end foreach $edited_index_info['Sequences']
?>
</tbody>
</table>
</fieldset>

<fieldset class="tblFooters">
    <input type="submit" name="do_save_data" value="<?php echo $strSave; ?>" />
<?php
echo $strOr . ' ';
echo sprintf($strAddToIndex,
        '<input type="text" name="added_fields" size="2" value="1"'
    .' onfocus="this.select()" />') . "\n";
echo '<input type="submit" name="add_fields" value="' . $strGo . '"'
    .' onclick="return checkFormElementInRange(this.form,'
    ." 'added_fields', '" . PMA_jsFormat($GLOBALS['strInvalidColumnCount']) . "', 1"
    .')" />' . "\n";
?>
</fieldset>
</form>
<?php

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
