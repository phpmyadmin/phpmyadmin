<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Get the values of the variables posted or sent to this script and display
 * the headers
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/relation.lib.php');


/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();


/**
 * A query has been submitted -> execute it, else display the headers
 */
if (isset($submit_sql) && preg_match('@^SELECT@i', $encoded_sql_query)) {
    $goto      = 'db_details.php';
    $zero_rows = htmlspecialchars($strSuccess);
    $sql_query = urldecode($encoded_sql_query);
    require('./sql.php');
    exit();
} else {
    $sub_part  = '_qbe';
    require('./db_details_common.php');
    $url_query .= '&amp;goto=db_details_qbe.php';
    require('./db_details_db_info.php');
}

if (isset($submit_sql) && !preg_match('@^SELECT@i', $encoded_sql_query)) {
    echo '<p class="warning">' . $strHaveToShow . '</p>';
}


/**
 * Initialize some variables
 */
if (empty($Columns)) {
    $Columns  = 3;  // Initial number of columns
}
if (!isset($Add_Col)) {
    $Add_Col  = '';
}
if (!isset($Add_Row)) {
    $Add_Row  = '';
}
if (!isset($Rows)) {
    $Rows     = '';
}
if (!isset($InsCol)) {
    $InsCol   = '';
}
if (!isset($DelCol)) {
    $DelCol   = '';
}
if (!isset($prev_Criteria)) {
    $prev_Criteria = '';
}
// workaround for a PHP3 problem
if (!isset($Criteria)) {
    //$Criteria = '';
    $Criteria = array();
    for ($i = 0; $i < $Columns; $i++) {
        $Criteria[$i] = '';
    }
}
if (!isset($InsRow)) {
//    $InsRow   = '';
    $InsRow = array();
    for ($i = 0; $i < $Columns; $i++) {
        $InsRow[$i] = '';
    }
}
if (!isset($DelRow)) {
//    $DelRow   = '';
    $DelRow = array();
    for ($i = 0; $i < $Columns; $i++) {
        $DelRow[$i] = '';
    }
}
if (!isset($AndOrRow)) {
//    $AndOrRow = '';
    $AndOrRow = array();
    for ($i = 0; $i < $Columns; $i++) {
        $AndOrRow[$i] = '';
    }
}
if (!isset($AndOrCol)) {
//    $AndOrCol = '';
    $AndOrCol = array();
    for ($i = 0; $i < $Columns; $i++) {
        $AndOrCol[$i] = '';
    }
}
// minimum width
$wid          = 12;
$col          = $Columns + $Add_Col;
if ($col < 0) {
    $col      = 0;
}
$row          = $Rows + $Add_Row;
if ($row < 0) {
    $row      = 0;
}


/**
 * Prepares the form
 */
$tbl_result     = PMA_mysql_list_tables($db);
$tbl_result_cnt = mysql_num_rows($tbl_result);
$i              = 0;
$k              = 0;

// The tables list sent by a previously submitted form
if (!empty($TableList)) {
    $cnt_table_list = count($TableList);
    for ($x = 0; $x < $cnt_table_list; $x++) {
        $tbl_names[urldecode($TableList[$x])] = ' selected="selected"';
    }
} // end if

// The tables list gets from MySQL
while ($i < $tbl_result_cnt) {
    $tbl             = PMA_mysql_tablename($tbl_result, $i);
    $fld_results     = @PMA_mysql_list_fields_alternate($db, $tbl) or PMA_mysqlDie(PMA_mysql_error(), 'PMA_mysql_list_fields_alternate(' . $db . ', ' . $tbl . ')', FALSE, $err_url);
    $fld_results_cnt = ($fld_results) ? count($fld_results) : 0;
    $j               = 0;

    if (empty($tbl_names[$tbl]) && !empty($TableList)) {
        $tbl_names[$tbl] = '';
    } else {
        $tbl_names[$tbl] = ' selected="selected"';
    } //  end if

    // The fields list per selected tables
    if ($tbl_names[$tbl] == ' selected="selected"') {
        $fld[$k++]   =  PMA_backquote($tbl) . '.*';
        while ($j < $fld_results_cnt) {
            $fld[$k] = PMA_convert_display_charset($fld_results[$j]['Field']);
            $fld[$k] = PMA_backquote($tbl) . '.' . PMA_backquote($fld[$k]);

            // increase the width if necessary
            if (strlen($fld[$k]) > $wid) {
                $wid = strlen($fld[$k]);
            } //end if

            $k++;
            $j++;
        } // end while
    } // end if

    $i++;
} // end if
mysql_free_result($tbl_result);

// largest width found
$realwidth = $wid . 'ex';


/**
 * Displays the form
 */
?>

<!-- Query by example form -->
<form action="db_details_qbe.php" method="post">
    <table border="<?php echo $cfg['Border']; ?>">

    <!-- Fields row -->
    <tr>
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfg['ThBgcolor']; ?>">
            <b><?php echo $strField; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <select style="width: <?php echo $realwidth; ?>" name="Field[<?php echo $z; ?>]" size="1">
                <option value=""></option>
        <?php
        echo "\n";
        for ($y = 0; $y < sizeof($fld); $y++) {
            if ($fld[$y] == '') {
                $sel = ' selected="selected"';
            } else {
                $sel = '';
            }
            echo '                ';
            echo '<option value="' . htmlspecialchars($fld[$y]) . '"' . $sel . '>' . htmlspecialchars($fld[$y]) . '</option>' . "\n";
        } // end for
        ?>
            </select>
        </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($DelCol) && isset($DelCol[$x]) && $DelCol[$x] == 'on') {
        continue;
    }
    ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <select style="width: <?php echo $realwidth; ?>" name="Field[<?php echo $z; ?>]" size="1">
                <option value=""></option>
    <?php
    echo "\n";
    for ($y = 0; $y < sizeof($fld); $y++) {
        if (isset($Field[$x]) && $fld[$y] == urldecode($Field[$x])) {
            $curField[$z] = urldecode($Field[$x]);
            $sel          = ' selected="selected"';
        } else {
            $sel          = '';
        } // end if
        echo '                ';
        echo '<option value="' . htmlspecialchars($fld[$y]) . '"' . $sel . '>' . htmlspecialchars($fld[$y]) . '</option>' . "\n";
    } // end for
    ?>
            </select>
        </td>
    <?php
    $z++;
    echo "\n";
} // end for
?>
    </tr>

    <!-- Sort row -->
    <tr>
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfg['ThBgcolor']; ?>">
            <b><?php echo $strSort; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
            <select style="width: <?php echo $realwidth; ?>" name="Sort[<?php echo $z; ?>]" size="1">
                <option value=""></option>
                <option value="ASC"><?php echo $strAscending; ?></option>
                <option value="DESC"><?php echo $strDescending; ?></option>
            </select>
        </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($DelCol) && isset($DelCol[$x]) && $DelCol[$x] == 'on') {
        continue;
    }
    ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
            <select style="width: <?php echo $realwidth; ?>" name="Sort[<?php echo $z; ?>]" size="1">
                <option value=""></option>
    <?php
    echo "\n";

    // If they have chosen all fields using the * selector,
    // then sorting is not available
    // Robbat2 - Fix for Bug #570698
    if (isset($Sort[$x]) && isset($Field[$x]) && (substr(urldecode($Field[$x]),-2) == '.*')) {
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
    <tr>
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfg['ThBgcolor']; ?>">
            <b><?php echo $strShow; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <input type="checkbox" name="Show[<?php echo $z; ?>]" />
        </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($DelCol) && isset($DelCol[$x]) && $DelCol[$x] == 'on') {
        continue;
    }
    if (isset($Show[$x])) {
        $checked     = ' checked="checked"';
        $curShow[$z] = $Show[$x];
    } else {
        $checked     =  '';
    }
    ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
            <input type="checkbox" name="Show[<?php echo $z; ?>]"<?php echo $checked; ?> />
        </td>
    <?php
    $z++;
    echo "\n";
} // end for
?>
    </tr>

    <!-- Criteria row -->
    <tr>
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfg['ThBgcolor']; ?>">
            <b><?php echo $strCriteria; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
            <input type="text" name="Criteria[<?php echo $z; ?>]" value="" class="textfield" style="width: <?php echo $realwidth; ?>" size="20" />
        </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($DelCol) && isset($DelCol[$x]) && $DelCol[$x] == 'on') {
        continue;
    }
    if (isset($Criteria[$x])) {
        $stripped_Criteria = $Criteria[$x];
    }
    if ((empty($prev_Criteria) || !isset($prev_Criteria[$x]))
        || urldecode($prev_Criteria[$x]) != htmlspecialchars($stripped_Criteria)) {
        $curCriteria[$z]   = $stripped_Criteria;
        $encoded_Criteria  = urlencode($stripped_Criteria);
    } else {
        $curCriteria[$z]   = urldecode($prev_Criteria[$x]);
        $encoded_Criteria  = $prev_Criteria[$x];
    }
    ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
            <input type="hidden" name="prev_Criteria[<?php echo $z; ?>]" value="<?php echo $encoded_Criteria; ?>" />
            <input type="text" name="Criteria[<?php echo $z; ?>]" value="<?php echo htmlspecialchars($stripped_Criteria); ?>" class="textfield" style="width: <?php echo $realwidth; ?>" size="20" />
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
for ($y = 0; $y <= $row; $y++) {
    $bgcolor = ($y % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
    if (isset($InsRow[$y]) && $InsRow[$y] == 'on') {
        $chk['or']  = ' checked="checked"';
        $chk['and'] = '';
        ?>
    <tr>
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
            <!-- Row controls -->
            <table bgcolor="<?php echo $bgcolor; ?>">
            <tr>
                <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
                    <small><?php echo $strQBEIns; ?>&nbsp;:</small>
                    <input type="checkbox" name="InsRow[<?php echo $w; ?>]" />
                </td>
                <td align="<?php echo $cell_align_right; ?>">
                    <b><?php echo $strAnd; ?>&nbsp;:</b>
                </td>
                <td>
                    <input type="radio" name="AndOrRow[<?php echo $w; ?>]" value="and"<?php echo $chk['and']; ?> />
                    &nbsp;
                </td>
            </tr>
            <tr>
                <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
                    <small><?php echo $strQBEDel; ?>&nbsp;:</small>
                    <input type="checkbox" name="DelRow[<?php echo $w; ?>]" />
                </td>
                <td align="<?php echo $cell_align_right; ?>">
                    <b><?php echo $strOr; ?>&nbsp;:</b>
                </td>
                <td>
                    <input type="radio" name="AndOrRow[<?php echo $w; ?>]" value="or"<?php echo $chk['or']; ?> />
                    &nbsp;
                </td>
            </tr>
            </table>
        </td>
        <?php
        $z = 0;
        for ($x = 0; $x < $col; $x++) {
            if ($InsCol[$x] == 'on') {
                echo "\n";
                $or = 'Or' . $w . '[' . $z . ']';
                ?>
        <td align="center" bgcolor="<?php echo $bgcolor; ?>">
            <textarea cols="20" rows="2" style="width: <?php echo $realwidth; ?>" name="<?php echo $or; ?>" dir="<?php echo $text_dir; ?>"></textarea>
        </td>
                <?php
                $z++;
            } // end if
            if ($DelCol[$x] == 'on') {
                continue;
            }

            echo "\n";
            $or = 'Or' . $w . '[' . $z . ']';
            ?>
        <td align="center" bgcolor="<?php echo $bgcolor; ?>">
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
    } // end if

    if (isset($DelRow[$y]) && $DelRow[$y] == 'on') {
        continue;
    }

    if (isset($AndOrRow[$y])) {
        $curAndOrRow[$w] = $AndOrRow[$y];
    }
    if (isset($AndOrRow[$y]) && $AndOrRow[$y] == 'and') {
        $chk['and'] =  ' checked="checked"';
        $chk['or']  =  '';
    } else {
        $chk['or']  =  ' checked="checked"';
        $chk['and'] =  '';
    }
    echo "\n";
    ?>
    <tr>
        <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
            <!-- Row controls -->
            <table bgcolor="<?php echo $bgcolor; ?>">
            <tr>
                <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
                    <small><?php echo $strQBEIns; ?>&nbsp;:</small>
                    <input type="checkbox" name="InsRow[<?php echo $w; ?>]" />
                </td>
                <td align="<?php echo $cell_align_right; ?>">
                    <b><?php echo $strAnd; ?>&nbsp;:</b>
                </td>
                <td>
                    <input type="radio" name="AndOrRow[<?php echo $w; ?>]" value="and"<?php echo $chk['and']; ?> />
                </td>
            </tr>
            <tr>
                <td align="<?php echo $cell_align_right; ?>" nowrap="nowrap">
                    <small><?php echo $strQBEDel; ?>&nbsp;:</small>
                    <input type="checkbox" name="DelRow[<?php echo $w; ?>]" />
                </td>
                <td align="<?php echo $cell_align_right; ?>">
                    <b><?php echo $strOr; ?>&nbsp;:</b>
                </td>
                <td>
                    <input type="radio" name="AndOrRow[<?php echo $w; ?>]" value="or"<?php echo $chk['or']; ?> />
                </td>
            </tr>
            </table>
        </td>
    <?php
    $z = 0;
    for ($x = 0; $x < $col; $x++) {
        if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
            echo "\n";
            $or = 'Or' . $w . '[' . $z . ']';
            ?>
        <td align="center" bgcolor="<?php echo $bgcolor; ?>">
            <textarea cols="20" rows="2" style="width: <?php echo $realwidth; ?>" name="<?php echo $or; ?>" dir="<?php echo $text_dir; ?>"></textarea>
        </td>
            <?php
            $z++;
        } // end if
        if (!empty($DelCol) && isset($DelCol[$x]) && $DelCol[$x] == 'on') {
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
        <td align="center" bgcolor="<?php echo $bgcolor; ?>">
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
} // end for
?>

    <!-- Modify columns -->
    <tr>
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfg['ThBgcolor']; ?>">
            <b><?php echo $strModify; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        $curAndOrCol[$z] = $AndOrCol[$y];
        if ($AndOrCol[$z] == 'or') {
            $chk['or']  = ' checked="checked"';
            $chk['and'] = '';
        } else {
            $chk['and'] = ' checked="checked"';
            $chk['or']  = '';
        }
        ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
            <b><?php echo $strOr; ?>&nbsp;:</b>
            <input type="radio" name="AndOrCol[<?php echo $z; ?>]" value="or"<?php echo $chk['or']; ?> />
            &nbsp;&nbsp;<b><?php echo $strAnd; ?>&nbsp;:</b>
            <input type="radio" name="AndOrCol[<?php echo $z; ?>]" value="and"<?php echo $chk['and']; ?> />
            <br />
            <?php echo $strQBEIns . "\n"; ?>
            <input type="checkbox" name="InsCol[<?php echo $z; ?>]" />
            &nbsp;&nbsp;<?php echo $strQBEDel . "\n"; ?>
            <input type="checkbox" name="DelCol[<?php echo $z; ?>]" />
        </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($DelCol) && isset($DelCol[$x]) && $DelCol[$x] == 'on') {
        continue;
    }

    if (isset($AndOrCol[$y])) {
        $curAndOrCol[$z] = $AndOrCol[$y];
    }
    if (isset($AndOrCol[$z]) && $AndOrCol[$z] == 'or') {
        $chk['or']  = ' checked="checked"';
        $chk['and'] = '';
    } else {
        $chk['and'] = ' checked="checked"';
        $chk['or']  = '';
    }
    ?>
        <td align="center" bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">
            <b><?php echo $strOr; ?>&nbsp;:</b>
            <input type="radio" name="AndOrCol[<?php echo $z; ?>]" value="or"<?php echo $chk['or']; ?> />
            &nbsp;&nbsp;<b><?php echo $strAnd; ?>&nbsp;:</b>
            <input type="radio" name="AndOrCol[<?php echo $z; ?>]" value="and"<?php echo $chk['and']; ?> />
            <br />
            <?php echo $strQBEIns . "\n"; ?>
            <input type="checkbox" name="InsCol[<?php echo $z; ?>]" />
            &nbsp;&nbsp;<?php echo $strQBEDel . "\n"; ?>
            <input type="checkbox" name="DelCol[<?php echo $z; ?>]" />
        </td>
    <?php
    $z++;
    echo "\n";
} // end for
?>
    </tr>
    </table>


    <!-- Other controls -->
    <table border="0">
    <tr>
        <td valign="top">
            <table border="0" align="<?php echo $cell_align_left; ?>">
            <tr>
                <td rowspan="4" valign="top">
                    <?php echo $strUseTables; ?>&nbsp;:
                    <br />
                    <select name="TableList[]" size="7" multiple="multiple">
<?php
foreach($tbl_names AS $key => $val) {
    echo '                        ';
    echo '<option value="' . htmlspecialchars($key) . '"' . $val . '>' . htmlspecialchars($key) . '</option>' . "\n";
}
?>
                    </select>
                </td>
                <td align="<?php echo $cell_align_right; ?>" valign="bottom">
                    <input type="hidden" value="<?php echo htmlspecialchars($db); ?>" name="db" />
                    <input type="hidden" value="<?php echo $z; ?>" name="Columns" />
<?php
$w--;
?>
                    <input type="hidden" value="<?php echo $w; ?>" name="Rows" />
                    <?php echo $strAddDeleteRow; ?>&nbsp;:
                    <select size="1" name="Add_Row">
                        <option value="-3">-3</option>
                        <option value="-2">-2</option>
                        <option value="-1">-1</option>
                        <option value="0" selected="selected">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td align="<?php echo $cell_align_right; ?>" valign="bottom">
                    <?php echo $strAddDeleteColumn; ?>&nbsp;:
                    <select size="1" name="Add_Col">
                        <option value="-3">-3</option>
                        <option value="-2">-2</option>
                        <option value="-1">-1</option>
                        <option value="0" selected="selected">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </td>
            </tr>
            <!-- Generates a query -->
            <tr align="center" valign="top">
                <td>
                    <input type="submit" name="modify" value="<?php echo $strUpdateQuery; ?>" />
                    <?php echo PMA_generate_common_hidden_inputs(); ?>
                </td>
            </tr>
            <!-- Executes a query -->
            <tr align="center" valign="top">
                <td>
                    <input type="submit" name="submit_sql" value="<?php echo $strRunQuery; ?>" />
                </td>
            </tr>
            </table>
        </td>
        <td>
            <!-- Displays the current query -->
            <?php echo sprintf($strQueryOnDb, htmlspecialchars($db)); ?><br />
<textarea cols="30" rows="7" name="sql_query" dir="<?php echo $text_dir; ?>">
<?php
// 1. SELECT
$last_select = 0;
$encoded_qry = '';
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
    $encoded_qry .= urlencode('SELECT ' . $qry_select . "\n");
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
    foreach($Field AS $value) {
        $parts             = explode('.', $value);
        if (!empty($parts[0]) && !empty($parts[1])) {
            $tab_raw       = urldecode($parts[0]);
            $tab           = str_replace('`', '', $tab_raw);
            $tab_all[$tab] = $tab;

            $col_raw       = urldecode($parts[1]);
            $col_all[]     = $tab . '.' . str_replace('`', '', $col_raw);
         }
    } // end while

    // Check 'where' clauses
    if ($cfgRelation['relwork'] && count($tab_all) > 0) {
        // Now we need all tables that we have in the where clause
        $crit_cnt         = count($Criteria);
        for ($x = 0; $x < $crit_cnt; $x++) {
            $curr_tab     = explode('.', urldecode($Field[$x]));
            if (!empty($curr_tab[0]) && !empty($curr_tab[1])) {
                $tab_raw  = urldecode($curr_tab[0]);
                $tab      = str_replace('`', '', $tab_raw);

                $col_raw  = urldecode($curr_tab[1]);
                $col1     = str_replace('`', '', $col_raw);
                $col1     = $tab . '.' . $col1;
                // Now we know that our array has the same numbers as $Criteria
                // we can check which of our columns has a where clause
                if (!empty($Criteria[$x])) {
                    if (substr($Criteria[$x], 0, 1) == '=' || stristr($Criteria[$x], 'is')) {
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
            foreach($tab_all AS $tab) {
                $ind_qry  = 'SHOW INDEX FROM ' . PMA_backquote($tab);
                $ind_rs   = PMA_mysql_query($ind_qry);
                while ($ind = PMA_mysql_fetch_array($ind_rs)) {
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
            } else if (isset($col_index) && count($col_index) > 0) {
                $col_cand = $col_index;
                $needsort = 1;
            } else if (isset($col_where) && count($col_where) > 0) {
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
                foreach($col_cand AS $col => $is_where) {
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
                foreach($col_cand AS $tab) {
                    if ($checked_tables[$tab] != 1 ) {
                        $rows_qry = 'SELECT COUNT(1) AS anz '
                                  . 'FROM ' . PMA_backquote($tab);
                        $rows_rs  = PMA_mysql_query($rows_qry);
                        while ($res = PMA_mysql_fetch_array($rows_rs)) {
                            $tsize[$tab] = $res['anz'];
                        }
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

        /**
         * Removes unwanted entries from an array (PHP3 compliant)
         *
         * @param   array  the array to work with
         * @param   array  the list of keys to remove
         *
         * @return  array  the cleaned up array
         *
         * @access  private
         */
        function PMA_arrayShort($array, $key)
        {
            foreach($array AS $k => $v) {
                if ($k != $key) {
                    $reta[$k] = $v;
                }
            }
            if (!isset($reta)) {
                $reta = array();
            }

            return $reta;
        } // end of the "PMA_arrayShort()" function


        /**
         * Finds all related tables
         *
         * @param   string   wether to go from master to foreign or vice versa
         *
         * @return  boolean  always TRUE
         *
         * @global  array    the list of tables that we still couldn't connect
         * @global  array    the list of allready connected tables
         * @global  string   the current databse name
         * @global  string   the super user connection id
         * @global  array    the list of relation settings
         *
         * @access  private
         */
        function PMA_getRelatives($from) {
            global $tab_left, $tab_know, $fromclause;
            global $dbh, $db, $cfgRelation;

            if ($from == 'master') {
                $to    = 'foreign';
            } else {
                $to    = 'master';
            }
            $in_know = '(\'' . implode('\', \'', $tab_know) . '\')';
            $in_left = '(\'' . implode('\', \'', $tab_left) . '\')';

            $rel_query = 'SELECT *'
                       . ' FROM ' . PMA_backquote($cfgRelation['relation'])
                       . ' WHERE ' . $from . '_db   = \'' . PMA_sqlAddslashes($db) . '\''
                       . ' AND ' . $to   . '_db   = \'' . PMA_sqlAddslashes($db) . '\''
                       . ' AND ' . $from . '_table IN ' . $in_know
                       . ' AND ' . $to   . '_table IN ' . $in_left;
            if (isset($dbh)) {
                PMA_mysql_select_db($cfgRelation['db'], $dbh);
                $relations = @PMA_mysql_query($rel_query, $dbh) or PMA_mysqlDie(PMA_mysql_error($dbh), $rel_query, '', $err_url_0);
                PMA_mysql_select_db($db, $dbh);
            } else {
                PMA_mysql_select_db($cfgRelation['db']);
                $relations = @PMA_mysql_query($rel_query) or PMA_mysqlDie('', $rel_query, '', $err_url_0);
                PMA_mysql_select_db($db);
            }
            while ($row = PMA_mysql_fetch_array($relations)) {
                $found_table                = $row[$to . '_table'];
                if (isset($tab_left[$found_table])) {
                    $fromclause             .= "\n" . ' LEFT JOIN '
                                            . PMA_backquote($row[$to . '_table']) . ' ON '
                                            . PMA_backquote($row[$from . '_table']) . '.'
                                            . PMA_backquote($row[$from . '_field']) . ' = '
                                            . PMA_backquote($row[$to . '_table']) . '.'
                                            . PMA_backquote($row[$to . '_field']) . ' ';
                    $tab_know[$found_table] = $found_table;
                    $tab_left               = PMA_arrayShort($tab_left, $found_table);
                }
            } // end while

            return TRUE;
        } // end of the "PMA_getRelatives()" function


        $tab_left          = PMA_arrayShort($tab_all, $master);
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

                foreach($tab_left AS $tab) {
                    $emerg    .= ', ' . $tab;
                    $tab_left = PMA_arrayShort($tab_left, $tab);
                }
            }
        } // end while
        $qry_from = $master . $emerg . $fromclause;
    } // end if ($cfgRelation['relwork'] && count($tab_all) > 0)

} // end count($Field) > 0

// In case relations are not defined, just generate the FROM clause
// from the list of tables, however we don't generate any JOIN

if (empty($qry_from) && isset($tab_all)) {
    $qry_from = implode(', ', $tab_all);
}
// Now let's see what we got
if (!empty($qry_from)) {
    $encoded_qry  .= urlencode('FROM ' . $qry_from . "\n");
    echo 'FROM ' . htmlspecialchars($qry_from) . "\n";
}

// 3. WHERE
$qry_where          = '';
$criteria_cnt       = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($curField[$x]) && !empty($curCriteria[$x]) && $x && isset($last_where)) {
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
    $encoded_qry .= urlencode('WHERE ' . $qry_where . "\n");
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
    $encoded_qry .= urlencode('ORDER BY ' . $qry_orderby);
    echo 'ORDER BY ' . htmlspecialchars($qry_orderby) . "\n";
}
?>
</textarea>
            <input type="hidden" name="encoded_sql_query" value="<?php echo $encoded_qry; ?>" />
        </td>
    </tr>
    </table>

</form>


<?php
/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
