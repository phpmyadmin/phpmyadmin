<?php
/* $Id$ */


/**
 * Gets the values of the variables posted or sent to this script and displays
 * the headers
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');


/**
 * A query has been submitted -> executes it, else displays the headers
 */
if (isset($submit_sql)) {
    $goto      = 'db_details.php3';
    $zero_rows = htmlspecialchars($strSuccess);
    $sql_query = urldecode($encoded_sql_query);
    if (get_magic_quotes_gpc()) {
        $sql_query = addslashes($sql_query);
    }
    include('./sql.php3');
    exit();
} else {
    include('./header.inc.php3');
}


/**
 * Initializes some variables
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
$wid          = 12;
$widem        = $wid . 'em';
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
$tbl_result     = mysql_list_tables($db);
$tbl_result_cnt = mysql_num_rows($tbl_result);
$i              = 0;
$k              = 0;

// The tables list sent by a previously submitted form
if (!empty($TableList)) {
    for ($x = 0; $x < sizeof($TableList); $x++) {
        $tbl_names[$TableList[$x]] = ' selected="selected"';
    }
} // end if

// The tables list gets from MySQL
while ($i < $tbl_result_cnt) {
    $tbl             = mysql_tablename($tbl_result, $i);
    $fld_results     = mysql_list_fields($db, $tbl);
    $fld_results_cnt = mysql_num_fields($fld_results);
    $j               = 0;

    if (empty($tbl_names[$tbl]) && !empty($TableList)) {
        $tbl_names[$tbl] = '';
    } else {
        $tbl_names[$tbl] = ' selected="selected"';
    } //  end if

    // The fields list per selected tables
    if ($tbl_names[$tbl] == ' selected="selected"') {
        $fld[$k++] =  PMA_backquote($tbl) . '.*';
        while ($j < $fld_results_cnt) {
            $fld[$k] = mysql_field_name($fld_results, $j);
            $fld[$k] = PMA_backquote($tbl) . '.' . PMA_backquote($fld[$k]);
            $k++;
            $j++;
        } // end while
    } // end if
    mysql_free_result($fld_results);

    $i++;
} // end if
mysql_free_result($tbl_result);


/**
 * Displays the form
 */
?>

<!-- Query by example form -->
<form action="tbl_qbe.php3" method="post">
    <table border="<?php echo $cfgBorder; ?>">

    <!-- Fields row -->
    <tr>
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfgThBgcolor; ?>">
            <b><?php echo $strField; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        ?>
        <td align="center" bgcolor="<?php echo $cfgBgcolorOne; ?>">
            <select style="width: <?php echo $widem; ?>" name="Field[<?php echo $z; ?>]" size="1">
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
            echo '<option value="' . urlencode($fld[$y]) . '"' . $sel . '>' . htmlspecialchars($fld[$y]) . '</option>' . "\n";
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
        <td align="center" bgcolor="<?php echo $cfgBgcolorOne; ?>">
            <select style="width: <?php echo $widem; ?>" name="Field[<?php echo $z; ?>]" size="1">
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
        echo '<option value="' . urlencode($fld[$y]) . '"' . $sel . '>' . htmlspecialchars($fld[$y]) . '</option>' . "\n";
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
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfgThBgcolor; ?>">
            <b><?php echo $strSort; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        ?>
        <td align="center" bgcolor="<?php echo $cfgBgcolorTwo; ?>">
            <select style="width: <?php echo $widem; ?>" name="Sort[<?php echo $z; ?>]" size="1">
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
        <td align="center" bgcolor="<?php echo $cfgBgcolorTwo; ?>">
            <select style="width: <?php echo $widem; ?>" name="Sort[<?php echo $z; ?>]" size="1">
                <option value=""></option>
    <?php
    echo "\n";
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
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfgThBgcolor; ?>">
            <b><?php echo $strShow; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        ?>
        <td align="center" bgcolor="<?php echo $cfgBgcolorOne; ?>">
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
        <td align="center" bgcolor="<?php echo $cfgBgcolorOne; ?>">
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
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfgThBgcolor; ?>">
            <b><?php echo $strCriteria; ?>&nbsp;:&nbsp;</b>
        </td>
<?php
$z = 0;
for ($x = 0; $x < $col; $x++) {
    if (!empty($InsCol) && isset($InsCol[$x]) && $InsCol[$x] == 'on') {
        ?>
        <td align="center" bgcolor="<?php echo $cfgBgcolorTwo; ?>">
            <input type="text" name="Criteria[<?php echo $z; ?>]" value="" style="width: <?php echo $widem; ?>" size="20" />
        </td>
        <?php
        $z++;
    } // end if
    echo "\n";

    if (!empty($DelCol) && isset($DelCol[$x]) && $DelCol[$x] == 'on') {
        continue;
    }
    if (isset($Criteria[$x])) {
        if (get_magic_quotes_gpc()) {
            $stripped_Criteria = stripslashes($Criteria[$x]);
        } else {
            $stripped_Criteria = $Criteria[$x];
        }
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
        <td align="center" bgcolor="<?php echo $cfgBgcolorTwo; ?>">
            <input type="hidden" name="prev_Criteria[<?php echo $z; ?>]" value="<?php echo $encoded_Criteria; ?>" />
            <input type="text" name="Criteria[<?php echo $z; ?>]" value="<?php echo htmlspecialchars($stripped_Criteria); ?>" style="width: <?php echo $widem; ?>" size="20" />
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
    $bgcolor = ($y % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
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
            <textarea cols="20" rows="2" style="width: <?php echo $widem; ?>" name="<?php echo $or; ?>"></textarea>
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
            <textarea cols="20" rows="2" style="width: <?php echo $widem; ?>" name="<?php echo $or; ?>"></textarea>
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
            <textarea cols="20" rows="2" style="width: <?php echo $widem; ?>" name="<?php echo $or; ?>"></textarea>
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
            if (get_magic_quotes_gpc()) {
                $stripped_or = stripslashes(${$or}[$x]);
            } else {
                $stripped_or = ${$or}[$x];
            }
        } else {
            $stripped_or     = '';
        }
        ?>
        <td align="center" bgcolor="<?php echo $bgcolor; ?>">
            <textarea cols="20" rows="2" style="width: <?php echo $widem; ?>" name="Or<?php echo $w . '[' . $z . ']'; ?>"><?php echo htmlspecialchars($stripped_or); ?></textarea>
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
        <td align="<?php echo $cell_align_right; ?>" bgcolor="<?php echo $cfgThBgcolor; ?>">
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
        <td align="center" bgcolor="<?php echo $cfgBgcolorTwo; ?>">
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
        <td align="center" bgcolor="<?php echo $cfgBgcolorTwo; ?>">
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
while (list($key, $val) = each($tbl_names)) {
    echo '                        ';
    echo '<option value="' . urlencode($key) . '"' . $val . '>' . htmlspecialchars($key) . '</option>' . "\n";
}
?>
                    </select>
                </td>
                <td align="<?php echo $cell_align_right; ?>" valign="bottom">
                    <input type="hidden" value="<?php echo $db; ?>" name="db" />
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
                    <input type="hidden" name="server" value="<?php echo $server; ?>" />
                    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
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
<textarea cols="30" rows="7" name="sql_query">
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
if (!isset($TableList)) {
    $TableList    = array();
}
if (!isset($qry_from)) {
    $qry_from     = '';
}
for ($x = 0; $x < sizeof($TableList); $x++) {
    if ($x) {
        $qry_from .=  ', ';
    }
    $qry_from     .=  PMA_backquote(urldecode($TableList[$x]));
} // end for
if (!empty($qry_from)) {
    $encoded_qry .= urlencode('FROM ' . $qry_from . "\n");
    echo  'FROM ' . htmlspecialchars($qry_from) . "\n";
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

// OR rows ${"cur".$or}[$x]
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
                          .  (get_magic_quotes_gpc() ? stripslashes(${'curOr' . $y}[$x]) : ${'curOr' . $y}[$x])
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
        $qry_orderby  .=  $curField[$x] . ' ' . $curSort[$x];
        $last_orderby = 1;
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
require('./footer.inc.php3');
?>
