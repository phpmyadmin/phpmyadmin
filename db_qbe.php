<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * query by example the whole database
 *
 * @version $Id$
 */

/**
 * requirements
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';
require_once './libraries/relation.lib.php';


/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();


/**
 * A query has been submitted -> execute it, else display the headers
 */
if (isset($_REQUEST['submit_sql']) && ! empty($sql_query)) {
    $goto      = 'db_sql.php';
    $zero_rows = htmlspecialchars($GLOBALS['strSuccess']);
    require './sql.php';
    exit;
} else {
    $sub_part  = '_qbe';
    require './libraries/db_common.inc.php';
    $url_query .= '&amp;goto=db_qbe.php';
    $url_params['goto'] = 'db_qbe.php';
    require './libraries/db_info.inc.php';
}

if (isset($_REQUEST['submit_sql'])
 && ! preg_match('@^SELECT@i', $sql_query)) {
    PMA_Message::warning('strHaveToShow')->display();
}


/**
 * Initialize some variables
 */
$col_cnt = PMA_ifSetOr($_REQUEST['col_cnt'], 3, 'numeric');
$add_col = PMA_ifSetOr($_REQUEST['add_col'], 0, 'numeric');
$add_row = PMA_ifSetOr($_REQUEST['add_row'], 0, 'numeric');

$rows    = PMA_ifSetOr($_REQUEST['rows'],    0, 'numeric');
$ins_col = PMA_ifSetOr($_REQUEST['add_col'], null, 'array');
$del_col = PMA_ifSetOr($_REQUEST['add_row'], null, 'array');

$prev_criteria = isset($_REQUEST['prev_criteria'])
    ? $_REQUEST['prev_criteria']
    : array();
$criteria = isset($_REQUEST['criteria'])
    ? $_REQUEST['criteria']
    : array_fill(0, $col_cnt, '');

$ins_row = isset($_REQUEST['ins_row'])
    ? $_REQUEST['ins_row']
    : array_fill(0, $col_cnt, '');
$del_row = isset($_REQUEST['del_row'])
    ? $_REQUEST['del_row']
    : array_fill(0, $col_cnt, '');
$and_or_row = isset($_REQUEST['and_or_row'])
    ? $_REQUEST['and_or_row']
    : array_fill(0, $col_cnt, '');
$and_or_col = isset($_REQUEST['and_or_col'])
    ? $_REQUEST['and_or_col']
    : array_fill(0, $col_cnt, '');

// minimum width
$form_column_width = 12;
$col = max($col_cnt + $add_col, 0);
$row = max($rows + $add_row, 0);


// The tables list sent by a previously submitted form
if (PMA_isValid($_REQUEST['TableList'], 'array')) {
    foreach ($_REQUEST['TableList'] as $each_table) {
        $tbl_names[$each_table] = ' selected="selected"';
    }
} // end if


// this was a work in progress, deactivated for now
//$columns = PMA_DBI_get_columns_full($GLOBALS['db']);
//$tables  = PMA_DBI_get_columns_full($GLOBALS['db']);


/**
 * Prepares the form
 */
$tbl_result     = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($db) . ';', null, PMA_DBI_QUERY_STORE);
$tbl_result_cnt = PMA_DBI_num_rows($tbl_result);
if (0 == $tbl_result_cnt) {
    PMA_Message::error('strNoTablesFound')->display();
    require_once './libraries/footer.inc.php';
    exit;
}

// The tables list gets from MySQL
while (list($tbl) = PMA_DBI_fetch_row($tbl_result)) {
    $fld_results = PMA_DBI_get_fields($db, $tbl);

    if (empty($tbl_names[$tbl]) && !empty($_REQUEST['TableList'])) {
        $tbl_names[$tbl] = '';
    } else {
        $tbl_names[$tbl] = ' selected="selected"';
    } //  end if

    // The fields list per selected tables
    if ($tbl_names[$tbl] == ' selected="selected"') {
        $each_table = PMA_backquote($tbl);
        $fld[]  = $each_table . '.*';
        foreach ($fld_results as $each_field) {
            $each_field = $each_table . '.' . PMA_backquote($each_field['Field']);
            $fld[] = $each_field;

            // increase the width if necessary
            $form_column_width = max(strlen($each_field), $form_column_width);
        } // end foreach
    } // end if
} // end while
PMA_DBI_free_result($tbl_result);

// largest width found
$realwidth = $form_column_width . 'ex';


/**
 * Displays the Query by example form
 */

/**
 * Enter description here...
 *
 * @param array     $columns
 * @param numeric   $column_number
 * @param string    $selected
 */
function showColumnSelectCell($columns, $column_number, $selected = '')
{
    ?>
    <td align="center">
        <select name="Field[<?php echo $column_number; ?>]" size="1">
            <option value="">&nbsp;</option>
    <?php
    foreach ($columns as $column) {
        if ($column === $selected) {
            $sel = ' selected="selected"';
        } else {
            $sel = '';
        }
        echo '<option value="' . htmlspecialchars($column) . '"' . $sel . '>'
            . str_replace(' ', '&nbsp;', htmlspecialchars($column)) . '</option>' . "\n";
    }
    ?>
        </select>
    </td>
    <?php
}

?>
<fieldset>
<form action="db_qbe.php" method="post">
<table class="data" style="width: 100%;">
<tr class="odd noclick">
    <th><?php echo $strField; ?>:</th>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (isset($ins_col[$x]) && $ins_col[$x] == 'on') {
        showColumnSelectCell($fld, $z);
        $z++;
    }

    if (! empty($del_col) && isset($del_col[$x]) && $del_col[$x] == 'on') {
        continue;
    }

    $selected = '';
    if (isset($Field[$x])) {
        $selected = $Field[$x];
        $curField[$z] = $Field[$x];
    }
    showColumnSelectCell($fld, $z, $selected);
    $z++;
} // end for
?>
</tr>

<!-- Sort row -->
<tr class="even noclick">
    <th><?php echo $strSort; ?>:</th>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($ins_col) && isset($ins_col[$x]) && $ins_col[$x] == 'on') {
        ?>
    <td align="center">
        <select style="width: <?php echo $realwidth; ?>" name="Sort[<?php echo $z; ?>]" size="1">
            <option value="">&nbsp;</option>
            <option value="ASC"><?php echo $strAscending; ?></option>
            <option value="DESC"><?php echo $strDescending; ?></option>
        </select>
    </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($del_col) && isset($del_col[$x]) && $del_col[$x] == 'on') {
        continue;
    }
    ?>
    <td align="center">
        <select style="width: <?php echo $realwidth; ?>" name="Sort[<?php echo $z; ?>]" size="1">
            <option value="">&nbsp;</option>
    <?php
    echo "\n";

    // If they have chosen all fields using the * selector,
    // then sorting is not available
    // Robbat2 - Fix for Bug #570698
    if (isset($Sort[$x]) && isset($Field[$x])
     && substr($Field[$x], -2) == '.*') {
        $Sort[$x] = '';
    } //end if

    if (isset($Sort[$x]) && $Sort[$x] == 'ASC') {
        $curSort[$z] = $Sort[$x];
        $sel         = ' selected="selected"';
    } else {
        $sel         = '';
    } // end if
    echo '                ';
    echo '<option value="ASC"' . $sel . '>' . $strAscending . '</option>' . "\n";
    if (isset($Sort[$x]) && $Sort[$x] == 'DESC') {
        $curSort[$z] = $Sort[$x];
        $sel         = ' selected="selected"';
    } else {
        $sel         = '';
    } // end if
    echo '                ';
    echo '<option value="DESC"' . $sel . '>' . $strDescending . '</option>' . "\n";
    ?>
        </select>
    </td>
    <?php
    $z++;
    echo "\n";
} // end for
?>
</tr>

<!-- Show row -->
<tr class="odd noclick">
    <th><?php echo $strShow; ?>:</th>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($ins_col) && isset($ins_col[$x]) && $ins_col[$x] == 'on') {
        ?>
    <td align="center">
        <input type="checkbox" name="Show[<?php echo $z; ?>]" />
    </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($del_col) && isset($del_col[$x]) && $del_col[$x] == 'on') {
        continue;
    }
    if (isset($Show[$x])) {
        $checked     = ' checked="checked"';
        $curShow[$z] = $Show[$x];
    } else {
        $checked     =  '';
    }
    ?>
    <td align="center">
        <input type="checkbox" name="Show[<?php echo $z; ?>]"<?php echo $checked; ?> />
    </td>
    <?php
    $z++;
    echo "\n";
} // end for
?>
</tr>

<!-- Criteria row -->
<tr class="even noclick">
    <th><?php echo $strCriteria; ?>:</th>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($ins_col) && isset($ins_col[$x]) && $ins_col[$x] == 'on') {
        ?>
    <td align="center">
        <input type="text" name="criteria[<?php echo $z; ?>]" value="" class="textfield" style="width: <?php echo $realwidth; ?>" size="20" />
    </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($del_col) && isset($del_col[$x]) && $del_col[$x] == 'on') {
        continue;
    }
    if (isset($criteria[$x])) {
        $stripped_Criteria = $criteria[$x];
    }
    if ((empty($prev_criteria) || !isset($prev_criteria[$x]))
        || $prev_criteria[$x] != htmlspecialchars($stripped_Criteria)) {
        $curCriteria[$z]   = $stripped_Criteria;
    } else {
        $curCriteria[$z]   = $prev_criteria[$x];
    }
    ?>
    <td align="center">
        <input type="hidden" name="prev_criteria[<?php echo $z; ?>]" value="<?php echo htmlspecialchars($curCriteria[$z]); ?>" />
        <input type="text" name="criteria[<?php echo $z; ?>]" value="<?php echo htmlspecialchars($stripped_Criteria); ?>" class="textfield" style="width: <?php echo $realwidth; ?>" size="20" />
    </td>
    <?php
    $z++;
    echo "\n";
} // end for
?>
</tr>

<!-- And/Or columns and rows -->
<?php
$w = 0;
$odd_row = true;
for ($y = 0; $y <= $row; $y++) {
    if (isset($ins_row[$y]) && $ins_row[$y] == 'on') {
        $chk['or']  = ' checked="checked"';
        $chk['and'] = '';
        ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?> noclick">
    <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
        <!-- Row controls -->
        <table cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
                <small><?php echo $strQBEIns; ?>:</small>
                <input type="checkbox" name="ins_row[<?php echo $w; ?>]" />
            </td>
            <td align="<?php echo $cell_align_right; ?>">
                <b><?php echo $strAnd; ?>:</b>
            </td>
            <td>
                <input type="radio" name="and_or_row[<?php echo $w; ?>]" value="and"<?php echo $chk['and']; ?> />
                &nbsp;
            </td>
        </tr>
        <tr>
            <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
                <small><?php echo $strQBEDel; ?>:</small>
                <input type="checkbox" name="del_row[<?php echo $w; ?>]" />
            </td>
            <td align="<?php echo $cell_align_right; ?>">
                <b><?php echo $strOr; ?>:</b>
            </td>
            <td>
                <input type="radio" name="and_or_row[<?php echo $w; ?>]" value="or"<?php echo $chk['or']; ?> />
                &nbsp;
            </td>
        </tr>
        </table>
    </td>
        <?php
        $z = 0;
        for ($x = 0; $x < $col; $x++) {
            if (isset($ins_col[$x]) && $ins_col[$x] == 'on') {
                echo "\n";
                $or = 'Or' . $w . '[' . $z . ']';
                ?>
    <td align="center">
        <textarea cols="20" rows="2" style="width: <?php echo $realwidth; ?>" name="<?php echo $or; ?>" dir="<?php echo $text_dir; ?>"></textarea>
    </td>
                <?php
                $z++;
            } // end if
            if (isset($del_col[$x]) && $del_col[$x] == 'on') {
                continue;
            }

            echo "\n";
            $or = 'Or' . $w . '[' . $z . ']';
            ?>
    <td align="center">
        <textarea cols="20" rows="2" style="width: <?php echo $realwidth; ?>" name="<?php echo $or; ?>" dir="<?php echo $text_dir; ?>"></textarea>
    </td>
            <?php
            $z++;
        } // end for
        $w++;
        echo "\n";
        ?>
</tr>
        <?php
        $odd_row =! $odd_row;
    } // end if

    if (isset($del_row[$y]) && $del_row[$y] == 'on') {
        continue;
    }

    if (isset($and_or_row[$y])) {
        $curAndOrRow[$w] = $and_or_row[$y];
    }
    if (isset($and_or_row[$y]) && $and_or_row[$y] == 'and') {
        $chk['and'] =  ' checked="checked"';
        $chk['or']  =  '';
    } else {
        $chk['or']  =  ' checked="checked"';
        $chk['and'] =  '';
    }
    echo "\n";
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?> noclick">
    <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
        <!-- Row controls -->
        <table border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
                <small><?php echo $strQBEIns; ?>:</small>
                <input type="checkbox" name="ins_row[<?php echo $w; ?>]" />
            </td>
            <td align="<?php echo $cell_align_right; ?>">
                <b><?php echo $strAnd; ?>:</b>
            </td>
            <td>
                <input type="radio" name="and_or_row[<?php echo $w; ?>]" value="and"<?php echo $chk['and']; ?> />
            </td>
        </tr>
        <tr>
            <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
                <small><?php echo $strQBEDel; ?>:</small>
                <input type="checkbox" name="del_row[<?php echo $w; ?>]" />
            </td>
            <td align="<?php echo $cell_align_right; ?>">
                <b><?php echo $strOr; ?>:</b>
            </td>
            <td>
                <input type="radio" name="and_or_row[<?php echo $w; ?>]" value="or"<?php echo $chk['or']; ?> />
            </td>
        </tr>
        </table>
    </td>
    <?php
    $z = 0;
    for ($x = 0; $x < $col; $x++) {
        if (!empty($ins_col) && isset($ins_col[$x]) && $ins_col[$x] == 'on') {
            echo "\n";
            $or = 'Or' . $w . '[' . $z . ']';
            ?>
    <td align="center">
        <textarea cols="20" rows="2" style="width: <?php echo $realwidth; ?>" name="<?php echo $or; ?>" dir="<?php echo $text_dir; ?>"></textarea>
    </td>
            <?php
            $z++;
        } // end if
        if (!empty($del_col) && isset($del_col[$x]) && $del_col[$x] == 'on') {
            continue;
        }

        echo "\n";
        $or = 'Or' . $y;
        if (!isset(${$or})) {
            ${$or} = '';
        }
        if (!empty(${$or}) && isset(${$or}[$x])) {
            $stripped_or = ${$or}[$x];
        } else {
            $stripped_or     = '';
        }
        ?>
    <td align="center">
        <textarea cols="20" rows="2" style="width: <?php echo $realwidth; ?>" name="Or<?php echo $w . '[' . $z . ']'; ?>" dir="<?php echo $text_dir; ?>"><?php echo htmlspecialchars($stripped_or); ?></textarea>
    </td>
        <?php
        if (!empty(${$or}) && isset(${$or}[$x])) {
            ${'cur' . $or}[$z] = ${$or}[$x];
        }
        $z++;
    } // end for
    $w++;
    echo "\n";
    ?>
</tr>
    <?php
    echo "\n";
    $odd_row =! $odd_row;
} // end for
?>
<!-- Modify columns -->
<tr class="even noclick">
    <th><?php echo $strModify; ?>:</th>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($ins_col) && isset($ins_col[$x]) && $ins_col[$x] == 'on') {
        $curAndOrCol[$z] = $and_or_col[$y];
        if ($and_or_col[$z] == 'or') {
            $chk['or']  = ' checked="checked"';
            $chk['and'] = '';
        } else {
            $chk['and'] = ' checked="checked"';
            $chk['or']  = '';
        }
        ?>
    <td align="center">
        <b><?php echo $strOr; ?>:</b>
        <input type="radio" name="and_or_col[<?php echo $z; ?>]" value="or"<?php echo $chk['or']; ?> />
        &nbsp;&nbsp;<b><?php echo $strAnd; ?>:</b>
        <input type="radio" name="and_or_col[<?php echo $z; ?>]" value="and"<?php echo $chk['and']; ?> />
        <br />
        <?php echo $strQBEIns . "\n"; ?>
        <input type="checkbox" name="ins_col[<?php echo $z; ?>]" />
        &nbsp;&nbsp;<?php echo $strQBEDel . "\n"; ?>
        <input type="checkbox" name="del_col[<?php echo $z; ?>]" />
    </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($del_col) && isset($del_col[$x]) && $del_col[$x] == 'on') {
        continue;
    }

    if (isset($and_or_col[$y])) {
        $curAndOrCol[$z] = $and_or_col[$y];
    }
    if (isset($and_or_col[$z]) && $and_or_col[$z] == 'or') {
        $chk['or']  = ' checked="checked"';
        $chk['and'] = '';
    } else {
        $chk['and'] = ' checked="checked"';
        $chk['or']  = '';
    }
    ?>
    <td align="center">
        <b><?php echo $strOr; ?>:</b>
        <input type="radio" name="and_or_col[<?php echo $z; ?>]" value="or"<?php echo $chk['or']; ?> />
        &nbsp;&nbsp;<b><?php echo $strAnd; ?>:</b>
        <input type="radio" name="and_or_col[<?php echo $z; ?>]" value="and"<?php echo $chk['and']; ?> />
        <br />
        <?php echo $strQBEIns . "\n"; ?>
        <input type="checkbox" name="ins_col[<?php echo $z; ?>]" />
        &nbsp;&nbsp;<?php echo $strQBEDel . "\n"; ?>
        <input type="checkbox" name="del_col[<?php echo $z; ?>]" />
    </td>
    <?php
    $z++;
    echo "\n";
} // end for
?>
    </tr>
</table>

<!-- Other controls -->
<?php
$w--;
$url_params['db']       = $db;
$url_params['col_cnt']  = $z;
$url_params['rows']     = $w;
echo PMA_generate_common_hidden_inputs($url_params);
?>
</fieldset>
<fieldset class="tblFooters">
<table border="0" cellpadding="2" cellspacing="1">
<tr>
    <td nowrap="nowrap">
        <?php echo $strAddDeleteRow; ?>:
        <select size="1" name="add_row" style="vertical-align: middle">
            <option value="-3">-3</option>
            <option value="-2">-2</option>
            <option value="-1">-1</option>
            <option value="0" selected="selected">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
        </select>
    </td>
    <td width="10">&nbsp;</td>
    <td nowrap="nowrap"><?php echo $strAddDeleteColumn; ?>:
        <select size="1" name="add_col" style="vertical-align: middle">
            <option value="-3">-3</option>
            <option value="-2">-2</option>
            <option value="-1">-1</option>
            <option value="0" selected="selected">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
        </select>
    </td>
    <td width="10">&nbsp;</td>
    <!-- Generates a query -->
    <td><input type="submit" name="modify" value="<?php echo $strUpdateQuery; ?>" /></td>
</tr>
</table>
</fieldset>

<table>
<tr><td>
        <fieldset>
            <legend><?php echo $strUseTables; ?></legend>
<?php
$strTableListOptions = '';
$numTableListOptions = 0;
foreach ($tbl_names as $key => $val) {
    $strTableListOptions .= '                        ';
    $strTableListOptions .= '<option value="' . htmlspecialchars($key) . '"' . $val . '>'
        . str_replace(' ', '&nbsp;', htmlspecialchars($key)) . '</option>' . "\n";
    $numTableListOptions++;
}
?>
            <select name="TableList[]" multiple="multiple" id="listTable"
                size="<?php echo ($numTableListOptions > 30) ? '15' : '7'; ?>">
                <?php echo $strTableListOptions; ?>
            </select>
        </fieldset>
        <fieldset class="tblFooters">
            <input type="submit" name="modify" value="<?php echo $strUpdateQuery; ?>" />
        </fieldset>
    </td>
    <td width="20">&nbsp;</td>
    <td>
        <fieldset>
            <legend><?php echo sprintf($strQueryOnDb, PMA_getDbLink($db)); ?>
                </legend>
            <textarea cols="30" name="sql_query" id="textSqlquery"
                rows="<?php echo ($numTableListOptions > 30) ? '15' : '7'; ?>"
                dir="<?php echo $text_dir; ?>">
<?php
// 1. SELECT
$last_select = 0;
if (!isset($qry_select)) {
    $qry_select         = '';
}
for ($x = 0; $x < $col; $x++) {
    if (!empty($curField[$x]) && isset($curShow[$x]) && $curShow[$x] == 'on') {
        if ($last_select) {
            $qry_select .=  ', ';
        }
        $qry_select     .= $curField[$x];
        $last_select    = 1;
    }
} // end for
if (!empty($qry_select)) {
    echo  'SELECT ' . htmlspecialchars($qry_select) . "\n";
}

// 2. FROM

// Create LEFT JOINS out of Relations
// Code originally by Mike Beck <mike.beck@ibmiller.de>
// If we can use Relations we could make some left joins.
// First find out if relations are available in this database.

// First we need the really needed Tables - those in TableList might still be
// all Tables.
if (isset($Field) && count($Field) > 0) {
    // Initialize some variables
    $tab_all    = array();
    $col_all    = array();
    $tab_wher   = array();
    $tab_know   = array();
    $tab_left   = array();
    $col_where  = array();
    $fromclause = '';

    // We only start this if we have fields, otherwise it would be dumb
    foreach ($Field as $value) {
        $parts             = explode('.', $value);
        if (!empty($parts[0]) && !empty($parts[1])) {
            $tab_raw       = $parts[0];
            $tab           = str_replace('`', '', $tab_raw);
            $tab_all[$tab] = $tab;

            $col_raw       = $parts[1];
            $col_all[]     = $tab . '.' . str_replace('`', '', $col_raw);
         }
    } // end while

    // Check 'where' clauses
    if ($cfgRelation['relwork'] && count($tab_all) > 0) {
        // Now we need all tables that we have in the where clause
        $crit_cnt         = count($criteria);
        for ($x = 0; $x < $crit_cnt; $x++) {
            $curr_tab     = explode('.', $Field[$x]);
            if (!empty($curr_tab[0]) && !empty($curr_tab[1])) {
                $tab_raw  = $curr_tab[0];
                $tab      = str_replace('`', '', $tab_raw);

                $col_raw  = $curr_tab[1];
                $col1     = str_replace('`', '', $col_raw);
                $col1     = $tab . '.' . $col1;
                // Now we know that our array has the same numbers as $criteria
                // we can check which of our columns has a where clause
                if (!empty($criteria[$x])) {
                    if (substr($criteria[$x], 0, 1) == '=' || stristr($criteria[$x], 'is')) {
                        $col_where[$col] = $col1;
                        $tab_wher[$tab]  = $tab;
                    }
                } // end if
            } // end if
        } // end for

        // Cleans temp vars w/o further use
        unset($tab_raw);
        unset($col_raw);
        unset($col1);

        if (count($tab_wher) == 1) {
            // If there is exactly one column that has a decent where-clause
            // we will just use this
            $master = key($tab_wher);
        } else {
            // Now let's find out which of the tables has an index
            // (When the control user is the same as the normal user
            // because he is using one of his databases as pmadb,
            // the last db selected is not always the one where we need to work)
            PMA_DBI_select_db($db);

            foreach ($tab_all as $tab) {
                $ind_rs   = PMA_DBI_query('SHOW INDEX FROM ' . PMA_backquote($tab) . ';');
                while ($ind = PMA_DBI_fetch_assoc($ind_rs)) {
                    $col1 = $tab . '.' . $ind['Column_name'];
                    if (isset($col_all[$col1])) {
                        if ($ind['non_unique'] == 0) {
                            if (isset($col_where[$col1])) {
                                $col_unique[$col1] = 'Y';
                            } else {
                                $col_unique[$col1] = 'N';
                            }
                        } else {
                            if (isset($col_where[$col1])) {
                                $col_index[$col1] = 'Y';
                            } else {
                                $col_index[$col1] = 'N';
                            }
                        }
                    }
                } // end while (each col of tab)
            } // end while (each tab)
            // now we want to find the best.
            if (isset($col_unique) && count($col_unique) > 0) {
                $col_cand = $col_unique;
                $needsort = 1;
            } elseif (isset($col_index) && count($col_index) > 0) {
                $col_cand = $col_index;
                $needsort = 1;
            } elseif (isset($col_where) && count($col_where) > 0) {
                $col_cand = $tab_wher;
                $needsort = 0;
            } else {
                $col_cand = $tab_all;
                $needsort = 0;
            }

            // If we came up with $col_unique (very good) or $col_index (still
            // good) as $col_cand we want to check if we have any 'Y' there
            // (that would mean that they were also found in the whereclauses
            // which would be great). if yes, we take only those
            if ($needsort == 1) {
                foreach ($col_cand as $col => $is_where) {
                    $tab           = explode('.', $col);
                    $tab           = $tab[0];
                    if ($is_where == 'Y') {
                        $vg[$col]  = $tab;
                    } else {
                        $sg[$col]  = $tab;
                    }
                }
                if (isset($vg)) {
                    $col_cand      = $vg;
                    // Candidates restricted in index+where
                } else {
                    $col_cand      = $sg;
                    // None of the candidates where in a where-clause
                }
            }

            // If our array of candidates has more than one member we'll just
            // find the smallest table.
            // Of course the actual query would be faster if we check for
            // the Criteria which gives the smallest result set in its table,
            // but it would take too much time to check this
            if (count($col_cand) > 1) {
                // Of course we only want to check each table once
                $checked_tables = $col_cand;
                foreach ($col_cand as $tab) {
                    if ($checked_tables[$tab] != 1) {
                        $tsize[$tab] = PMA_Table::countRecords($db, $tab, true, false);
                        $checked_tables[$tab] = 1;
                    }
                    $csize[$tab] = $tsize[$tab];
                }
                asort($csize);
                reset($csize);
                $master = key($csize); // Smallest
            } else {
                reset($col_cand);
                $master = current($col_cand); // Only one single candidate
            }
        } // end if (exactly one where clause)

        unset($tab_all[$master]);
        $tab_know[$master] = $master;

        $run   = 0;
        $emerg = '';
        while (count($tab_left) > 0) {
            if ($run % 2 == 0) {
                PMA_getRelatives('master');
            } else {
                PMA_getRelatives('foreign');
            }
            $run++;
            if ($run > 5) {

                foreach ($tab_left as $tab) {
                    $emerg .= ', ' . PMA_backquote($tab);
                    unset($tab_left[$tab]);
                }
            }
        } // end while
        $qry_from = PMA_backquote($master) . $emerg . $fromclause;
    } // end if ($cfgRelation['relwork'] && count($tab_all) > 0)

} // end count($Field) > 0

// In case relations are not defined, just generate the FROM clause
// from the list of tables, however we don't generate any JOIN

if (empty($qry_from) && isset($tab_all)) {
    $qry_from = implode(', ', $tab_all);
}
// Now let's see what we got
if (!empty($qry_from)) {
    echo 'FROM ' . htmlspecialchars($qry_from) . "\n";
}

// 3. WHERE
$qry_where          = '';
$criteria_cnt       = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($curField[$x]) && !empty($curCriteria[$x]) && $x && isset($last_where) && isset($curAndOrCol)) {
        $qry_where  .= ' ' . strtoupper($curAndOrCol[$last_where]) . ' ';
    }
    if (!empty($curField[$x]) && !empty($curCriteria[$x])) {
        $qry_where  .= '(' . $curField[$x] . ' ' . $curCriteria[$x] . ')';
        $last_where = $x;
        $criteria_cnt++;
    }
} // end for
if ($criteria_cnt > 1) {
    $qry_where      = '(' . $qry_where . ')';
}
// OR rows ${'cur' . $or}[$x]
if (!isset($curAndOrRow)) {
    $curAndOrRow          = array();
}
for ($y = 0; $y <= $row; $y++) {
    $criteria_cnt         = 0;
    $qry_orwhere          = '';
    $last_orwhere         = '';
    for ($x = 0; $x < $col; $x++) {
        if (!empty($curField[$x]) && !empty(${'curOr' . $y}[$x]) && $x) {
            $qry_orwhere  .= ' ' . strtoupper($curAndOrCol[$last_orwhere]) . ' ';
        }
        if (!empty($curField[$x]) && !empty(${'curOr' . $y}[$x])) {
            $qry_orwhere  .= '(' . $curField[$x]
                          .  ' '
                          .  ${'curOr' . $y}[$x]
                          .  ')';
            $last_orwhere = $x;
            $criteria_cnt++;
        }
    } // end for
    if ($criteria_cnt > 1) {
        $qry_orwhere      = '(' . $qry_orwhere . ')';
    }
    if (!empty($qry_orwhere)) {
        $qry_where .= "\n"
                   .  strtoupper(isset($curAndOrRow[$y]) ? $curAndOrRow[$y] . ' ' : '')
                   .  $qry_orwhere;
    } // end if
} // end for

if (!empty($qry_where) && $qry_where != '()') {
    echo 'WHERE ' . htmlspecialchars($qry_where) . "\n";
} // end if


// 4. ORDER BY
$last_orderby = 0;
if (!isset($qry_orderby)) {
    $qry_orderby      = '';
}
for ($x = 0; $x < $col; $x++) {
    if ($last_orderby && $x && !empty($curField[$x]) && !empty($curSort[$x])) {
        $qry_orderby  .=  ', ';
    }
    if (!empty($curField[$x]) && !empty($curSort[$x])) {
        // if they have chosen all fields using the * selector,
        // then sorting is not available
        // Robbat2 - Fix for Bug #570698
        if (substr($curField[$x], -2) != '.*') {
            $qry_orderby  .=  $curField[$x] . ' ' . $curSort[$x];
            $last_orderby = 1;
        }
    }
} // end for
if (!empty($qry_orderby)) {
    echo 'ORDER BY ' . htmlspecialchars($qry_orderby) . "\n";
}
?>
        </textarea>
        </fieldset>
        <fieldset class="tblFooters">
            <input type="submit" name="submit_sql" value="<?php echo $strRunQuery; ?>" />
        </fieldset>
    </td>
</tr>
</table>
</form>
<?php
/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
