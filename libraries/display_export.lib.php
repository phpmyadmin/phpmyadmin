<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Get relations & co. status
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

// Check if we have native MS Excel export using PEAR class Spreadsheet_Excel_Writer
if (!empty($GLOBALS['cfg']['TempDir'])) {
    @include_once('Spreadsheet/Excel/Writer.php');
    if (class_exists('Spreadsheet_Excel_Writer')) {
        $xls = TRUE;
    } else {
        $xls = FALSE;
    }
} else {
    $xls = FALSE;
}

function PMA_exportCheckboxCheck($str) {
    if (isset($GLOBALS['cfg']['Export'][$str]) && $GLOBALS['cfg']['Export'][$str]) {
        echo ' checked="checked"';
    }
}

function PMA_exportIsActive($what, $val) {
    if (isset($GLOBALS['cfg']['Export'][$what]) &&  $GLOBALS['cfg']['Export'][$what] == $val) {
        echo ' checked="checked"';
    }
}

?>
<form method="post" action="export.php" name="dump">
<?php
$hide_structure = false;
$hide_sql       = false;
$hide_xml       = empty($db);
if ($export_type == 'server') {
    echo PMA_generate_common_hidden_inputs('', '', 1);
} elseif ($export_type == 'database') {
    echo PMA_generate_common_hidden_inputs($db, '', 1);
} else {
    echo PMA_generate_common_hidden_inputs($db, $table, 1);
    if (!isset($single_table)) {
        $hide_structure = true;
        $hide_sql       = true;
    } else {
        // just to keep this value for possible next display of this form after saving on server
        echo '    <input type="hidden" name="single_table" value="TRUE" />';
    }
}
echo '    <input type="hidden" name="export_type" value="' . $export_type . '" />';

if (isset($sql_query)) {
    echo '    <input type="hidden" name="sql_query" value="' . htmlspecialchars($sql_query) . '" />';
}
?>

    <script type="text/javascript">
    <!--
    function hide_them_all() {
        getElement("csv_options").style.display = 'none';
        getElement("excel_options").style.display = 'none';
        getElement("latex_options").style.display = 'none';
<?php if ($xls) { ?>
        getElement("xls_options").style.display = 'none';
<?php } ?>
<?php if (!$hide_sql) { ?>
        getElement("sql_options").style.display = 'none';
<?php } ?>
        getElement("none_options").style.display = 'none';
    }

    function show_checked_option() {
        hide_them_all();
        if (getElement('radio_dump_latex').checked) {
            getElement('latex_options').style.display = 'block';
<?php if ($xls) { ?>
        } else if (getElement('radio_dump_xls').checked) {
            getElement('xls_options').style.display = 'block';
<?php } ?>
<?php if (!$hide_sql) { ?>
        } else if (getElement('radio_dump_sql').checked) {
            getElement('sql_options').style.display = 'block';
<?php } ?>
<?php if (!$hide_xml) { ?>
        } else if (getElement('radio_dump_xml').checked) {
            getElement('none_options').style.display = 'block';
<?php } ?>
        } else if (getElement('radio_dump_csv').checked) {
            getElement('csv_options').style.display = 'block';
        } else if (getElement('radio_dump_excel').checked) {
            getElement('excel_options').style.display = 'block';
        } else {
            if (getElement('radio_dump_sql')) {
                getElement('radio_dump_sql').checked = true;
                getElement('sql_options').style.display = 'block';
            } else if (getElement('radio_dump_csv')) {
                getElement('radio_dump_csv').checked = true;
                getElement('csv_options').style.display = 'block';
            } else {
                getElement('none_options').style.display = 'block';
            }
        }
    }
    //-->
    </script>

    <table cellpadding="3" border="0" cellspacing="0">
    <tr>
        <th colspan="3" valign="top" nowrap="nowrap" class="tblHeaders"><?php echo $export_page_title; ?></th>
    </tr>
    <tr>
        <!-- Formats to export to -->
        <td nowrap="nowrap" valign="top" onclick="if (typeof(window.opera) != 'undefined')setTimeout('show_checked_option()', 1); return true">
            <table border="0" cellpadding="3" cellspacing="1">
                <tr><th align="left"><?php echo $strExport; ?></th></tr>
            <?php
            if (isset($multi_values) && $multi_values != '') {
                echo '                <tr><td bgcolor="' . $cfg['BgcolorOne'] . '">';
                echo $multi_values;
                echo '                </td></tr>';
            }
            ?>

<?php if (!$hide_sql) { ?>
            <!-- SQL -->
                <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <input type="radio" name="what" value="sql" id="radio_dump_sql" onclick="if (this.checked) { hide_them_all(); getElement('sql_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'sql'); ?> style="vertical-align: middle" /><label for="radio_dump_sql"><?php echo $strSQL; ?>&nbsp;</label>
                </td></tr>
<?php } ?>

            <!-- LaTeX table -->
                <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <input type="radio" name="what" value="latex" id="radio_dump_latex"  onclick="if (this.checked) { hide_them_all(); getElement('latex_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'latex'); ?> style="vertical-align: middle" /><label for="radio_dump_latex"><?php echo $strLaTeX; ?>&nbsp;</label>
                </td></tr>


<?php if ($xls) { ?>
            <!-- Native Excel -->
                <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <input type="radio" name="what" value="xls" id="radio_dump_xls"  onclick="if (this.checked) { hide_them_all(); getElement('xls_options').style.display = 'block'; getElement('checkbox_dump_asfile').checked = true;};  return true" <?php PMA_exportIsActive('format', 'xls'); ?> /><label for="radio_dump_xls"><?php echo $strStrucNativeExcel; ?></label>
               </td></tr>
<?php } ?>

            <!-- Excel CSV -->
                <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <input type="radio" name="what" value="excel" id="radio_dump_excel"  onclick="if (this.checked) { hide_them_all(); getElement('excel_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'excel'); ?> style="vertical-align: middle" /><label for="radio_dump_excel"><?php echo $strStrucExcelCSV; ?>&nbsp;</label>
                </td></tr>

            <!-- General CSV -->
                <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <input type="radio" name="what" value="csv" id="radio_dump_csv"  onclick="if (this.checked) { hide_them_all(); getElement('csv_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'csv'); ?> style="vertical-align: middle" /><label for="radio_dump_csv"><?php echo $strStrucCSV;?>&nbsp;</label>
                </td></tr>

<?php if (!$hide_xml) { ?>
            <!-- XML -->
                <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <input type="radio" name="what" value="xml" id="radio_dump_xml" onclick="if (this.checked) { hide_them_all(); getElement('none_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'xml'); ?> style="vertical-align: middle" /><label for="radio_dump_xml"><?php echo $strXML; ?>&nbsp;</label>
                </td></tr>
<?php } ?>
            </table>
        </td>
        <!-- ltr item -->
        <td valign="top"><img src="<?php echo $pmaThemeImage . 'item_ltr.png'; ?>" border="0" hspace="2" vspace="5" /></td>
        <!-- Options -->
        <td valign="top" id="options_td" width="400">

<?php if (!$hide_sql) { ?>
            <!-- SQL options -->
            <div id="sql_options">
            <table width="400" border="0" cellpadding="3" cellspacing="1">
                <tr>
                    <th align="left">
                    <?php
                    echo $strSQLOptions;
                    $goto_documentation = '<a href="./Documentation.html#faqexport" target="documentation">';
                    echo ($cfg['ReplaceHelpImg'] ? '' : '(')
                       . $goto_documentation
                       . ($cfg['ReplaceHelpImg'] ? '<img src="' . $pmaThemeImage . 'b_help.png" border="0" alt="' .$strDocu . '" width="11" height="11" hspace="2" align="middle" />' : $strDocu)
                       . '</a>' . ($cfg['ReplaceHelpImg'] ? '' : ')');
                    ?>
                    </th>
                </tr>
                <tr>
                    <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                        <?php echo $strAddHeaderComment; ?>: <input type="text" name="header_comment" value="<?php echo $cfg['Export']['sql_header_comment']; ?>" class="textfield" size="30" style="vertical-align: middle" /><br />
                        <input type="checkbox" name="use_transaction" value="yes" id="checkbox_use_transaction" <?php PMA_exportCheckboxCheck('sql_use_transaction'); ?> style="vertical-align: middle" /><label for="checkbox_use_transaction"><?php echo $strEncloseInTransaction; ?></label><br />

                        <input type="checkbox" name="disable_fk" value="yes" id="checkbox_disable_fk" <?php PMA_exportCheckboxCheck('sql_disable_fk'); ?> style="vertical-align: middle" /><label for="checkbox_disable_fk"><?php echo $strDisableForeignChecks; ?></label><br />
                    </td>
                </tr>
<?php
if ($export_type == 'server') {
?>
                <!-- For databases -->
                <tr>
                    <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                        <b><?php echo $strDatabaseExportOptions; ?>:</b><br />
                        <input type="checkbox" name="drop_database" value="yes" id="checkbox_drop_database" <?php PMA_exportCheckboxCheck('sql_drop_database'); ?> style="vertical-align: middle" /><label for="checkbox_drop_database"><?php echo $strAddDropDatabase; ?></label>
                    </td>
                </tr>

<?php
}
if (!$hide_structure) { ?>
                <!-- For structure -->
                <tr>
                    <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                        <input type="checkbox" name="sql_structure" value="structure" id="checkbox_sql_structure" <?php PMA_exportCheckboxCheck('sql_structure'); ?> onclick="if (!this.checked &amp;&amp; !getElement('checkbox_sql_data').checked) return false; else return true;" /><label for="checkbox_sql_structure"><b><?php echo $strStructure; ?>:</b></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="drop" value="1" id="checkbox_dump_drop" <?php PMA_exportCheckboxCheck('sql_drop_table'); ?> style="vertical-align: middle" /><label for="checkbox_dump_drop"><?php echo $strStrucDrop; ?></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="if_not_exists" value="1" id="checkbox_dump_if_not_exists" <?php PMA_exportCheckboxCheck('sql_if_not_exists'); ?> style="vertical-align: middle" /><label for="checkbox_dump_if_not_exists"><?php echo $strAddIfNotExists; ?></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="auto_increment" value="1" id="checkbox_auto_increment" <?php PMA_exportCheckboxCheck('sql_auto_increment'); ?> style="vertical-align: middle" /><label for="checkbox_auto_increment"><?php echo $strAddAutoIncrement; ?></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="use_backquotes" value="1" id="checkbox_dump_use_backquotes" <?php PMA_exportCheckboxCheck('sql_backquotes'); ?> style="vertical-align: middle" /><label for="checkbox_dump_use_backquotes"><?php echo $strUseBackquotes; ?></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<b><?php echo $strAddIntoComments; ?></b><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="sql_dates" value="yes" id="checkbox_sql_dates" <?php PMA_exportCheckboxCheck('sql_dates'); ?> style="vertical-align: middle" /><label for="checkbox_sql_dates"><?php echo $strCreationDates; ?></label><br />
<?php
    if (!empty($cfgRelation['relation'])) {
?>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="sql_relation" value="yes" id="checkbox_sql_use_relation" <?php PMA_exportCheckboxCheck('sql_relation'); ?> style="vertical-align: middle" /><label for="checkbox_sql_use_relation"><?php echo $strRelations; ?></label><br />
<?php
    } // end relation
    if (!empty($cfgRelation['commwork'])) {
?>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="sql_comments" value="yes" id="checkbox_sql_use_comments" <?php PMA_exportCheckboxCheck('sql_comments'); ?> style="vertical-align: middle" /><label for="checkbox_sql_use_comments"><?php echo $strComments; ?></label><br />
<?php
    } // end comments
    if ($cfgRelation['mimework']) {
?>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="sql_mime" value="yes" id="checkbox_sql_use_mime" <?php PMA_exportCheckboxCheck('sql_mime'); ?> style="vertical-align: middle" /><label for="checkbox_sql_use_mime"><?php echo $strMIME_MIMEtype; ?></label><br />
<?php
    } // end MIME
    if (PMA_MYSQL_INT_VERSION >= 40100) {
?>
                        <label for="select_sql_compat"><?php echo $strSQLExportCompatibility; ?>:&nbsp;</label><select name="sql_compat" id="select_sql_compat" style="vertical-align: middle">
                        <?php
                        /* FIXME: offer only those that have effect on actual version? */
                        $compats = array('NONE', 'ANSI', 'DB2', 'MAXDB', 'MSSQL', 'MYSQL323', 'MYSQL40', 'ORACLE', 'POSTGRESQL', 'TRADITIONAL');
                        foreach ($compats as $x) {
                            echo '<option value="' . $x . '"' . ($cfg['Export']['sql_compat'] == $x ? ' selected="selected"' : '' ) . '>' . $x . '</option>' . "\n";
                        }
                        ?>
                        </select>
                        <?php echo PMA_showMySQLDocu('manual_MySQL_Database_Administration', 'Server_SQL_mode') . "\n"; 
    }
    ?>
                    </td>
                </tr>
<?php
} // end STRUCTURE
?>

                <!-- For data -->
                <tr>
                    <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                        <input type="checkbox" name="sql_data" value="data" id="checkbox_sql_data" <?php PMA_exportCheckboxCheck('sql_data'); ?> onclick="if (!this.checked &amp;&amp; (!getElement('checkbox_sql_structure') || !getElement('checkbox_sql_structure').checked)) return false; else return true;" style="vertical-align: middle" /><label for="checkbox_sql_data"><b><?php echo $strData; ?>:</b></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="showcolumns" value="yes" id="checkbox_dump_showcolumns" <?php PMA_exportCheckboxCheck('sql_columns'); ?> style="vertical-align: middle" /><label for="checkbox_dump_showcolumns"><?php echo $strCompleteInserts; ?></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="extended_ins" value="yes" id="checkbox_dump_extended_ins" <?php PMA_exportCheckboxCheck('sql_extended'); ?> style="vertical-align: middle" /><label for="checkbox_dump_extended_ins"><?php echo $strExtendedInserts; ?></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="delayed" value="yes" id="checkbox_dump_delayed" <?php PMA_exportCheckboxCheck('sql_delayed'); ?> style="vertical-align: middle" /><label for="checkbox_dump_delayed"><?php echo $strDelayedInserts; ?></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="sql_ignore" value="yes" id="checkbox_dump_ignore" <?php PMA_exportCheckboxCheck('sql_ignore'); ?> style="vertical-align: middle" /><label for="checkbox_dump_ignore"><?php echo $strIgnoreInserts; ?></label><br />

                        &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="hexforbinary" value="yes" id="checkbox_hexforbinary" <?php PMA_exportCheckboxCheck('sql_hex_for_binary'); ?> style="vertical-align: middle" /><label for="checkbox_hexforbinary"><?php echo $strHexForBinary; ?></label><br />

                        <label for="select_sql_type"><?php echo $strSQLExportType; ?>:&nbsp;</label><select name="sql_type" id="select_sql_type" style="vertical-align: middle">
                            <option value="insert"<?php echo $cfg['Export']['sql_type'] == 'insert' ? ' selected="selected"' : ''; ?>>INSERT</option>
                            <option value="update"<?php echo $cfg['Export']['sql_type'] == 'update' ? ' selected="selected"' : ''; ?>>UPDATE</option>
                            <option value="replace"<?php echo $cfg['Export']['sql_type'] == 'replace' ? ' selected="selected"' : ''; ?>>REPLACE</option>
                        </select>
                    </td>
                </tr>
                </table>
            </div>
<?php
} // end SQL-OPTIONS
?>

            <!-- LaTeX options -->
            <div id="latex_options">
            <table width="400" border="0" cellpadding="3" cellspacing="1">
                <tr><th align="left"><?php echo $strLaTeXOptions; ?></th></tr>
                <tr>
                    <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                        <input type="checkbox" name="latex_caption" value="yes" id="checkbox_latex_show_caption" <?php PMA_exportCheckboxCheck('latex_caption'); ?> style="vertical-align: middle" /><label for="checkbox_latex_show_caption"><?php echo $strLatexIncludeCaption; ?></label><br />
                    </td>
                </tr>

<?php if (!$hide_structure) { ?>
                <!-- For structure -->
                <tr>
                    <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <input type="checkbox" name="latex_structure" value="structure" id="checkbox_latex_structure" <?php PMA_exportCheckboxCheck('latex_structure'); ?> onclick="if (!this.checked &amp;&amp; !getElement('checkbox_latex_data').checked) return false; else return true;" style="vertical-align: middle" /><label for="checkbox_latex_structure"><b><?php echo $strStructure; ?></b></label><br />
                    <table border="0" cellspacing="1" cellpadding="0">
                        <tr>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td><?php echo $strLatexCaption; ?>&nbsp;</td>
                            <td>
                                <input type="text" name="latex_structure_caption" size="30" value="<?php echo $strLatexStructure; ?>" class="textfield" style="vertical-align: middle" />
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td><?php echo $strLatexContinuedCaption; ?>&nbsp;</td>
                            <td>
                                <input type="text" name="latex_structure_continued_caption" size="30" value="<?php echo $strLatexStructure . ' ' . $strLatexContinued; ?>" class="textfield" style="vertical-align: middle" />
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td><?php echo $strLatexLabel; ?>&nbsp;</td>
                            <td>
                                <input type="text" name="latex_structure_label" size="30" value="<?php echo $cfg['Export']['latex_structure_label']; ?>" class="textfield" style="vertical-align: middle" />
                            </td>
                        </tr>
                    </table>
<?php
    if (!empty($cfgRelation['relation'])) {
?>
                    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="latex_relation" value="yes" id="checkbox_latex_use_relation" <?php PMA_exportCheckboxCheck('latex_relation'); ?> style="vertical-align: middle" /><label for="checkbox_latex_use_relation"><?php echo $strRelations; ?></label><br />
<?php
    } // end relation
    if ($cfgRelation['commwork']) {
?>
                    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="latex_comments" value="yes" id="checkbox_latex_use_comments" <?php PMA_exportCheckboxCheck('latex_comments'); ?> style="vertical-align: middle" /><label for="checkbox_latex_use_comments"><?php echo $strComments; ?></label><br />
<?php
    } // end comments
    if ($cfgRelation['mimework']) {
?>
                    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="latex_mime" value="yes" id="checkbox_latex_use_mime" <?php PMA_exportCheckboxCheck('latex_mime'); ?> style="vertical-align: middle" /><label for="checkbox_latex_use_mime"><?php echo $strMIME_MIMEtype; ?></label><br />
<?php
    } // end MIME
?>
                </td>
            </tr>
<?php
} // end STRUCTURE
?>
            <!-- For data -->
            <tr>
                <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <input type="checkbox" name="latex_data" value="data" id="checkbox_latex_data" <?php PMA_exportCheckboxCheck('latex_data'); ?> onclick="if (!this.checked &amp;&amp; (!getElement('checkbox_latex_structure') || !getElement('checkbox_latex_structure').checked)) return false; else return true;" style="vertical-align: middle" /><label for="checkbox_latex_data"><b><?php echo $strData; ?>:</b></label><br />
                    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="latex_showcolumns" value="yes" id="ch_latex_showcolumns" <?php PMA_exportCheckboxCheck('latex_columns'); ?> style="vertical-align: middle" /><label for="ch_latex_showcolumns"><?php echo $strColumnNames; ?></label><br />
                    <table border="0" cellspacing="1" cellpadding="0">
                        <tr>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td><?php echo $strLatexCaption; ?>&nbsp;</td>
                            <td>
                                <input type="text" name="latex_data_caption" size="30" value="<?php echo $strLatexContent; ?>" class="textfield" style="vertical-align: middle" />
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td><?php echo $strLatexContinuedCaption; ?>&nbsp;</td>
                            <td>
                                <input type="text" name="latex_data_continued_caption" size="30" value="<?php echo $strLatexContent . ' ' . $strLatexContinued; ?>" class="textfield" style="vertical-align: middle" />
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td><?php echo $strLatexLabel; ?>&nbsp;</td>
                            <td>
                                <input type="text" name="latex_data_label" size="30" value="<?php echo $cfg['Export']['latex_data_label']; ?>" class="textfield" style="vertical-align: middle" />
                            </td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td><?php echo $strReplaceNULLBy; ?>&nbsp;</td>
                            <td>
                                <input type="text" name="latex_replace_null" size="20" value="<?php echo $cfg['Export']['latex_null']; ?>" class="textfield" style="vertical-align: middle" />
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>
            </div>

            <!-- CSV options -->
            <div id="csv_options">
            <table width="400" border="0" cellpadding="3" cellspacing="1">
                <tr><th align="left">
                    <?php echo $strCSVOptions; ?><input type="hidden" name="csv_data" value="csv_data" />
                </th></tr>
                <tr>
                    <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <table border="0" cellspacing="1" cellpadding="0">
                    <tr>
                        <td>
                            <?php echo $strFieldsTerminatedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="separator" size="2" value="<?php echo $cfg['Export']['csv_separator']; ?>" class="textfield" style="vertical-align: middle" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strFieldsEnclosedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="enclosed" size="2" value="<?php echo $cfg['Export']['csv_enclosed']; ?>" class="textfield" style="vertical-align: middle" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strFieldsEscapedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="escaped" size="2" value="<?php echo $cfg['Export']['csv_escaped']; ?>" class="textfield" style="vertical-align: middle" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strLinesTerminatedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="add_character" size="2" value="<?php if ($cfg['Export']['csv_terminated'] == 'AUTO') echo ((PMA_whichCrlf() == "\n") ? '\n' : '\r\n'); else echo $cfg['Export']['csv_terminated']; ?>" class="textfield" style="vertical-align: middle" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strReplaceNULLBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="csv_replace_null" size="20" value="<?php echo $cfg['Export']['csv_null']; ?>" class="textfield" style="vertical-align: middle" />
                        </td>
                    </tr>
                </table>
                <input type="checkbox" name="showcsvnames" value="yes" id="checkbox_dump_showcsvnames" <?php PMA_exportCheckboxCheck('csv_columns'); ?> style="vertical-align: middle" /><label for="checkbox_dump_showcsvnames"><?php echo $strPutColNames; ?></label>
            </td>
        </tr>
        </table>
        </div>

        <!-- Excel options -->
        <div id="excel_options">
        <table width="400" border="0" cellpadding="3" cellspacing="1">
                <tr><th align="left">
                    <?php echo $strExcelOptions; ?>
                    <input type="hidden" name="excel_data" value="excel_data" />
                </th></tr>
                <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <table border="0" cellspacing="1" cellpadding="0">
                    <tr>
                        <td>
                            <?php echo $strReplaceNULLBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="excel_replace_null" size="20" value="<?php echo $cfg['Export']['excel_null']; ?>" class="textfield" style="vertical-align: middle" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="checkbox" name="showexcelnames" value="yes" id="checkbox_dump_showexcelnames" <?php PMA_exportCheckboxCheck('excel_columns'); ?> style="vertical-align: middle" /><label for="checkbox_dump_showexcelnames"><?php echo $strPutColNames; ?></label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="select_excel_edition">
                                <?php echo $strExcelEdition; ?>:&nbsp;
                            </label>
                        </td>
                        <td>
                            <select name="excel_edition" id="select_excel_edition" style="vertical-align: middle">
                                <option value="win"<?php echo $cfg['Export']['excel_edition'] == 'win' ? ' selected="selected"' : ''; ?>>Windows</option>
                                <option value="mac"<?php echo $cfg['Export']['excel_edition'] == 'mac' ? ' selected="selected"' : ''; ?>>Excel 2003 / Macintosh</option>
                            </select>
                        </td>
                    </tr>
                </table>
                </td></tr>
            </table>
            </div>


<?php if ($xls) { ?>
            <!-- Native Excel options -->
            <div id="xls_options">
                <table border="0" cellspacing="1" cellpadding="0" width="400">
                    <tr>
                        <th align="left">
                           <b><?php echo $strExcelOptions; ?></b>
                           <input type="hidden" name="xls_data" value="xls_data" />
                        </th>
                   </tr>
                   <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <table border="0" cellspacing="1" cellpadding="0">
                       <tr>
                           <td>
                            <?php echo $strReplaceNULLBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="xls_replace_null" size="20" value="<?php echo $cfg['Export']['xls_null']; ?>" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" name="xls_shownames" value="yes" id="checkbox_dump_xls_shownames" <?php PMA_exportCheckboxCheck('xls_columns'); ?> /><label for="checkbox_dump_xls_shownames"><?php echo $strPutColNames; ?></label>
                        </td>
                    </tr>
                </table>
                    </td></tr>
               </table>
            </div>
<?php } ?>

            <div id="none_options">
            <table width="400" border="0" cellpadding="3" cellspacing="1">
                <tr><th align="left"><?php echo $strXML; ?></th></tr>
                <tr><td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                    <?php echo $strNoOptions; ?>
                    <input type="hidden" name="xml_data" value="xml_data" />
                </td></tr>
            </table>
            </div>
            <script type="text/javascript">
            <!--
                show_checked_option();
            //-->
            </script>
        </td>
    </tr>
<?php
if (isset($table) && !empty($table) && !isset($num_tables)) {
?>
    <tr>
        <td colspan="3" align="center">
          <div style="background-color: <?php echo $cfg['BgcolorOne']; ?>; padding: 3px; margin: 1px;">
           <b><?php echo sprintf($strDumpXRows , '<input type="text" name="limit_to" size="5" value="' . (isset($unlim_num_rows)?$unlim_num_rows: PMA_countRecords($db, $table, TRUE)) . '" class="textfield" style="vertical-align: middle" onfocus="this.select()" style="vertical-align: middle; text-align: center;" />' , '<input type="text" name="limit_from" value="0" size="5" class="textfield" style="vertical-align: middle" onfocus="this.select()" style="vertical-align: middle; text-align: center;" />') . "\n"; ?></b>
          </div>
        </td>
    </tr>
<?php
}
?>

    <tr>
        <!-- Export to screen or to file -->
        <td colspan="3">
        <table width="100%" border="0" cellpadding="3" cellspacing="1">
        <tr>
            <th align="left">
            <input type="checkbox" name="asfile" value="sendit" id="checkbox_dump_asfile" <?php PMA_exportCheckboxCheck('asfile'); ?> style="vertical-align: middle" /><label for="checkbox_dump_asfile"><b><?php echo $strSend; ?></b></label>
            </th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
                <?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
                &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="onserver" value="saveit" id="checkbox_dump_onserver"  onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportCheckboxCheck('onserver'); ?> style="vertical-align: middle" /><label for="checkbox_dump_onserver"><?php echo sprintf($strSaveOnServer, htmlspecialchars($cfg['SaveDir'])); ?></label>,<br />
                &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="onserverover" value="saveitover" id="checkbox_dump_onserverover"  onclick="getElement('checkbox_dump_onserver').checked = true;getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportCheckboxCheck('onserver_overwrite'); ?> style="vertical-align: middle" /><label for="checkbox_dump_onserverover"><?php echo $strOverwriteExisting; ?></label>
                <br />
                <?php } ?>

                &nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strFileNameTemplate; ?>:&nbsp;
                <input type="text" name="filename_template"
                <?php
                    echo ' value="';
                    if ($export_type == 'database') {
                        if (isset($_COOKIE) && !empty($_COOKIE['pma_db_filename_template'])) {
                            echo $_COOKIE['pma_db_filename_template'];
                        } else {
                            echo '__DB__';
                        }
                    } elseif ($export_type == 'table') {
                        if (isset($_COOKIE) && !empty($_COOKIE['pma_table_filename_template'])) {
                            echo $_COOKIE['pma_table_filename_template'];
                        } else {
                            echo '__TABLE__';
                        }
                    } else {
                        if (isset($_COOKIE) && !empty($_COOKIE['pma_server_filename_template'])) {
                            echo $_COOKIE['pma_server_filename_template'];
                        } else {
                            echo '__SERVER__';
                        }
                    }
                    echo '" ';
                ?> style="vertical-align: middle" />
                (
                <input type="checkbox" name="remember_template" id="checkbox_remember_template" <?php PMA_exportCheckboxCheck('remember_file_template'); ?> style="vertical-align: middle" /><label for="checkbox_remember_template"><?php echo $strFileNameTemplateRemember; ?></label>
                )*

                <?php
                // charset of file
                if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
                    echo '<br /><label for="select_charset_of_file">' . $strCharsetOfFile . '</label>';
                    echo "\n";

                    $temp_charset = reset($cfg['AvailableCharsets']);
                    echo '<select id="select_charset_of_file" name="charset_of_file" size="1">' . "\n"
                            . '                <option value="' . $temp_charset . '"';
                    if ($temp_charset == $charset) {
                        echo ' selected="selected"';
                    }
                    echo '>' . $temp_charset . '</option>' . "\n";
                    while ($temp_charset = next($cfg['AvailableCharsets'])) {
                        echo '                <option value="' . $temp_charset . '"';
                        if ($temp_charset == $charset) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $temp_charset . '</option>' . "\n";
                    } // end while
                    echo '            </select>';
                } // end if
                echo "\n";
                ?>
                <br />
                &nbsp;&nbsp;&nbsp;&nbsp;<b><?php echo $strCompression; ?></b><br />
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="compression" value="none" id="radio_compression_none" onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportIsActive('compression', 'none'); ?> style="vertical-align: middle" /><label for="radio_compression_none"><?php echo $strNone; ?></label>
&nbsp;&nbsp;

<?php

// zip, gzip and bzip2 encode features
$is_zip  = (isset($cfg['ZipDump']) && $cfg['ZipDump'] && @function_exists('gzcompress'));
$is_gzip = (isset($cfg['GZipDump']) && $cfg['GZipDump'] && @function_exists('gzencode'));
$is_bzip = (isset($cfg['BZipDump']) && $cfg['BZipDump'] && @function_exists('bzcompress'));
if ($is_zip || $is_gzip || $is_bzip) {
    if ($is_zip) {
        ?>
                <input type="radio" name="compression" value="zip" id="radio_compression_zip" onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportIsActive('compression', 'zip'); ?> style="vertical-align: middle" /><label for="radio_compression_zip"><?php echo $strZip; ?></label><?php echo (($is_gzip || $is_bzip) ? '&nbsp;&nbsp;' : ''); ?>
        <?php
    }
    if ($is_gzip) {
        echo "\n"
        ?>
                <input type="radio" name="compression" value="gzip" id="radio_compression_gzip" onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportIsActive('compression', 'gzip'); ?> style="vertical-align: middle" /><label for="radio_compression_gzip"><?php echo $strGzip; ?></label><?php echo ($is_bzip ? '&nbsp;&nbsp;' : ''); ?>
        <?php
    }
    if ($is_bzip) {
        echo "\n"
        ?>
                <input type="radio" name="compression" value="bzip" id="radio_compression_bzip" onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportIsActive('compression', 'bzip'); ?> style="vertical-align: middle" /><label for="radio_compression_bzip"><?php echo $strBzip; ?></label>
        <?php
    }
}
echo "\n";
?>
            </td>
        </tr>
        </table>
        </td>
    </tr>

<?php
// Encoding setting form appended by Y.Kawada
if (function_exists('PMA_set_enc_form')) {
    ?>
    <tr>
        <!-- Japanese encoding setting -->
        <td colspan="3" align="center">
    <?php
    echo PMA_set_enc_form('            ');
    ?>
        </td>
    </tr>
    <?php
}
echo "\n";
?>
    <tr>
        <td colspan="3" align="right" class="tblFooters">
            <input type="submit" value="<?php echo $strGo; ?>" id="buttonGo" />
        </td>
    </tr>
    </table>
</form>
<br />
<table border="0" cellpadding="0" cellspacing="0" width="600">
<tr>
    <td valign="top">*&nbsp;</td>
    <td>
        <?php echo sprintf($strFileNameTemplateHelp, '<a href="http://www.php.net/manual/function.strftime.php" target="documentation" title="' . $strDocu . '">', '</a>') . "\n"; ?>

    </td>
</tr>
</table>
