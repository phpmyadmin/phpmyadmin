<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Get relations & co. status
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

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
<?php if (!$hide_sql) { ?>
        getElement("sql_options").style.display = 'none';
<?php } ?>
        getElement("none_options").style.display = 'none';
    }

    function show_checked_option() {
        hide_them_all();
        if (getElement('radio_dump_latex').checked) {
            getElement('latex_options').style.display = 'block';
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

    <table cellpadding="5" border="0" cellspacing="0" align="center">
    <tr>

        <!-- Formats to export to -->
        <td nowrap="nowrap" valign="top" onclick="if (typeof(window.opera) != 'undefined')setTimeout('show_checked_option()', 1); return true">
            <fieldset <?php echo ((!isset($multi_values) || isset($multi_values) && $multi_values == '') ? 'style="height: 220px;"' : ''); ?>>
            <legend><?php echo $strExport; ?></legend>
            <br>
            <?php
            if (isset($multi_values) && $multi_values != '') {
                echo $multi_values;
            }
            ?>

<?php if (!$hide_sql) { ?>
            <!-- SQL -->
            <input type="radio" name="what" value="sql" id="radio_dump_sql" onclick="if(this.checked) { hide_them_all(); getElement('sql_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'sql'); ?> />
            <label for="radio_dump_sql"><?php echo $strSQL; ?></label>
            <br /><br />
<?php } ?>

            <!-- LaTeX table -->
            <input type="radio" name="what" value="latex" id="radio_dump_latex"  onclick="if(this.checked) { hide_them_all(); getElement('latex_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'latex'); ?> />
            <label for="radio_dump_latex"><?php echo $strLaTeX; ?></label>
            <br /><br />

            <!-- Excel CSV -->
            <input type="radio" name="what" value="excel" id="radio_dump_excel"  onclick="if(this.checked) { hide_them_all(); getElement('excel_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'excel'); ?> />
            <label for="radio_dump_excel"><?php echo $strStrucExcelCSV; ?></label>
            <br /><br />
            <!-- General CSV -->
            <input type="radio" name="what" value="csv" id="radio_dump_csv"  onclick="if(this.checked) { hide_them_all(); getElement('csv_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'csv'); ?> />
            <label for="radio_dump_csv"><?php echo $strStrucCSV;?></label>


<?php if (!$hide_xml) { ?>
            <br /><br />

            <!-- XML -->
            <input type="radio" name="what" value="xml" id="radio_dump_xml" onclick="if(this.checked) { hide_them_all(); getElement('none_options').style.display = 'block'; }; return true" <?php PMA_exportIsActive('format', 'xml'); ?> />
            <label for="radio_dump_xml"><?php echo $strXML; ?></label>&nbsp;&nbsp;
<?php } ?>
            </fieldset>
        </td>
        <!-- Options -->
        <td valign="top" id="options_td" width="400">

<?php if (!$hide_sql) { ?>
            <!-- SQL options -->
            <fieldset id="sql_options">
                <legend><?php echo $strSQLOptions; ?> (<a href="./Documentation.html#faqexport" target="documentation"><?php echo $strDocu; ?></a>)</legend>
<?php
if ($export_type == 'server') {
?>
                <!-- For databases -->
                <fieldset>
                    <legend>
                        <?php echo $strDatabaseExportOptions; ?>
                    </legend>
                    <input type="checkbox" name="drop_database" value="yes" id="checkbox_drop_database" <?php PMA_exportCheckboxCheck('sql_drop_database'); ?> />
                    <label for="checkbox_drop_database"><?php echo $strAddDropDatabase; ?></label><br />
                </fieldset>
<?php
}
?>
<?php if (!$hide_structure) { ?>
                <!-- For structure -->
                <fieldset>
                    <legend>
                        <input type="checkbox" name="sql_structure" value="structure" id="checkbox_sql_structure" <?php PMA_exportCheckboxCheck('sql_structure'); ?> onclick="if(!this.checked && !getElement('checkbox_sql_data').checked) return false; else return true;" />
                        <label for="checkbox_sql_structure"><?php echo $strStructure; ?></label><br />
                    </legend>

                    <input type="checkbox" name="drop" value="1" id="checkbox_dump_drop" <?php PMA_exportCheckboxCheck('sql_drop_table'); ?> />
                    <label for="checkbox_dump_drop"><?php echo $strStrucDrop; ?></label><br />
                    <input type="checkbox" name="auto_increment" value="1" id="checkbox_auto_increment" <?php PMA_exportCheckboxCheck('sql_auto_increment'); ?> />
                    <label for="checkbox_auto_increment"><?php echo $strAddAutoIncrement; ?></label><br />
                    <input type="checkbox" name="use_backquotes" value="1" id="checkbox_dump_use_backquotes" <?php PMA_exportCheckboxCheck('sql_backquotes'); ?> />
                    <label for="checkbox_dump_use_backquotes"><?php echo $strUseBackquotes; ?></label><br />
                    <fieldset>
                        <legend><?php echo $strAddIntoComments; ?></legend>
                        <input type="checkbox" name="sql_dates" value="yes" id="checkbox_sql_dates" <?php PMA_exportCheckboxCheck('sql_dates'); ?> />
                        <label for="checkbox_sql_dates"><?php echo $strCreationDates; ?></label><br />
<?php
if (!empty($cfgRelation['relation'])) {
?>
                        <input type="checkbox" name="sql_relation" value="yes" id="checkbox_sql_use_relation" <?php PMA_exportCheckboxCheck('sql_relation'); ?> />
                        <label for="checkbox_sql_use_relation"><?php echo $strRelations; ?></label><br />
<?php
 } // end relation

if (!empty($cfgRelation['commwork'])) {
?>
                        <input type="checkbox" name="sql_comments" value="yes" id="checkbox_sql_use_comments" <?php PMA_exportCheckboxCheck('sql_comments'); ?> />
                        <label for="checkbox_sql_use_comments"><?php echo $strComments; ?></label><br />
<?php
} // end comments

if ($cfgRelation['mimework']) {
     ?>
                        <input type="checkbox" name="sql_mime" value="yes" id="checkbox_sql_use_mime" <?php PMA_exportCheckboxCheck('sql_mime'); ?> />
                        <label for="checkbox_sql_use_mime"><?php echo $strMIME_MIMEtype; ?></label><br />
<?php
} // end MIME
?>
                     </fieldset>
                </fieldset>
<?php } ?>

                <!-- For data -->
                <fieldset>
                    <legend>
                        <input type="checkbox" name="sql_data" value="data" id="checkbox_sql_data" <?php PMA_exportCheckboxCheck('sql_data'); ?> onclick="if(!this.checked && (!getElement('checkbox_sql_structure') || !getElement('checkbox_sql_structure').checked)) return false; else return true;" />
                        <label for="checkbox_sql_data"><?php echo $strData; ?></label><br />
                    </legend>
                    <input type="checkbox" name="showcolumns" value="yes" id="checkbox_dump_showcolumns" <?php PMA_exportCheckboxCheck('sql_columns'); ?> />
                    <label for="checkbox_dump_showcolumns"><?php echo $strCompleteInserts; ?></label><br />
                    <input type="checkbox" name="extended_ins" value="yes" id="checkbox_dump_extended_ins" <?php PMA_exportCheckboxCheck('sql_extended'); ?> />
                    <label for="checkbox_dump_extended_ins"><?php echo $strExtendedInserts; ?></label><br />
                    <input type="checkbox" name="delayed" value="yes" id="checkbox_dump_delayed" <?php PMA_exportCheckboxCheck('sql_delayed'); ?> />
                    <label for="checkbox_dump_delayed"><?php echo $strDelayedInserts; ?></label><br />

                    <label for="select_sql_type">
                        <?php echo $strSQLExportType; ?>:&nbsp;
                    </label>
                    <select name="sql_type" id="select_sql_type" />
                        <option value="insert"<?php echo $cfg['Export']['sql_type'] == 'insert' ? ' selected="selected"' : ''; ?>>INSERT</option>
                        <option value="update"<?php echo $cfg['Export']['sql_type'] == 'update' ? ' selected="selected"' : ''; ?>>UPDATE</option>
                        <option value="replace"<?php echo $cfg['Export']['sql_type'] == 'replace' ? ' selected="selected"' : ''; ?>>REPLACE</option>
                    </select>
                </fieldset>
            </fieldset>
<?php } ?>

             <!-- LaTeX options -->
             <fieldset id="latex_options">
                 <legend><?php echo $strLaTeXOptions; ?></legend>

                     <input type="checkbox" name="latex_caption" value="yes" id="checkbox_latex_show_caption" <?php PMA_exportCheckboxCheck('latex_caption'); ?> />
                     <label for="checkbox_latex_show_caption"><?php echo $strLatexIncludeCaption; ?></label><br />

<?php if (!$hide_structure) { ?>
                 <!-- For structure -->
                 <fieldset>
                     <legend>
                         <input type="checkbox" name="latex_structure" value="structure" id="checkbox_latex_structure" <?php PMA_exportCheckboxCheck('latex_structure'); ?> onclick="if(!this.checked && !getElement('checkbox_latex_data').checked) return false; else return true;" />
                         <label for="checkbox_latex_structure"><?php echo $strStructure; ?></label><br />
                     </legend>
                    <table border="0" cellspacing="1" cellpadding="0">
                        <tr>
                            <td>
                                <?php echo $strLatexCaption; ?>&nbsp;
                            </td>
                            <td>
                                <input type="text" name="latex_structure_caption" size="30" value="<?php echo $strLatexStructure; ?>" class="textfield" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo $strLatexContinuedCaption; ?>&nbsp;
                            </td>
                            <td>
                                <input type="text" name="latex_structure_continued_caption" size="30" value="<?php echo $strLatexStructure . ' ' . $strLatexContinued; ?>" class="textfield" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo $strLatexLabel; ?>&nbsp;
                            </td>
                            <td>
                                <input type="text" name="latex_structure_label" size="30" value="<?php echo $cfg['Export']['latex_structure_label']; ?>" class="textfield" />
                            </td>
                        </tr>
                    </table>
<?php
if (!empty($cfgRelation['relation'])) {
?>
                     <input type="checkbox" name="latex_relation" value="yes" id="checkbox_latex_use_relation" <?php PMA_exportCheckboxCheck('latex_relation'); ?> />
                     <label for="checkbox_latex_use_relation"><?php echo $strRelations; ?></label><br />
<?php
 } // end relation

if ($cfgRelation['commwork']) {
     ?>
                     <input type="checkbox" name="latex_comments" value="yes" id="checkbox_latex_use_comments" <?php PMA_exportCheckboxCheck('latex_comments'); ?> />
                     <label for="checkbox_latex_use_comments"><?php echo $strComments; ?></label><br />
<?php
} // end comments

if ($cfgRelation['mimework']) {
     ?>
                     <input type="checkbox" name="latex_mime" value="yes" id="checkbox_latex_use_mime" <?php PMA_exportCheckboxCheck('latex_mime'); ?> />
                     <label for="checkbox_latex_use_mime"><?php echo $strMIME_MIMEtype; ?></label><br />
<?php
} // end MIME
?>
                 </fieldset>
<?php } ?>

                 <!-- For data -->
                 <fieldset>
                     <legend>
                         <input type="checkbox" name="latex_data" value="data" id="checkbox_latex_data" <?php PMA_exportCheckboxCheck('latex_data'); ?> onclick="if(!this.checked && (!getElement('checkbox_latex_structure') || !getElement('checkbox_latex_structure').checked)) return false; else return true;" />
                         <label for="checkbox_latex_data"><?php echo $strData; ?></label><br />
                     </legend>
                     <input type="checkbox" name="latex_showcolumns" value="yes" id="ch_latex_showcolumns" <?php PMA_exportCheckboxCheck('latex_columns'); ?> />
                     <label for="ch_latex_showcolumns"><?php echo $strColumnNames; ?></label><br />
                    <table border="0" cellspacing="1" cellpadding="0">
                        <tr>
                            <td>
                                <?php echo $strLatexCaption; ?>&nbsp;
                            </td>
                            <td>
                                <input type="text" name="latex_data_caption" size="30" value="<?php echo $strLatexContent; ?>" class="textfield" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo $strLatexContinuedCaption; ?>&nbsp;
                            </td>
                            <td>
                                <input type="text" name="latex_data_continued_caption" size="30" value="<?php echo $strLatexContent . ' ' . $strLatexContinued; ?>" class="textfield" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo $strLatexLabel; ?>&nbsp;
                            </td>
                            <td>
                                <input type="text" name="latex_data_label" size="30" value="<?php echo $cfg['Export']['latex_data_label']; ?>" class="textfield" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo $strReplaceNULLBy; ?>&nbsp;
                            </td>
                            <td>
                                <input type="text" name="latex_replace_null" size="20" value="<?php echo $cfg['Export']['latex_null']; ?>" class="textfield" />
                            </td>
                        </tr>
                    </table>
                 </fieldset>
             </fieldset>

             <!-- CSV options -->
            <fieldset id="csv_options">
                <legend><?php echo $strCSVOptions; ?></legend>
                <input type="hidden" name="csv_data" value="csv_data" />
                <table border="0" cellspacing="1" cellpadding="0">
                    <tr>
                        <td>
                            <?php echo $strFieldsTerminatedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="separator" size="2" value="<?php echo $cfg['Export']['csv_separator']; ?>" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strFieldsEnclosedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="enclosed" size="2" value="<?php echo $cfg['Export']['csv_enclosed']; ?>" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strFieldsEscapedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="escaped" size="2" value="<?php echo $cfg['Export']['csv_escaped']; ?>" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strLinesTerminatedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="add_character" size="2" value="<?php if ($cfg['Export']['csv_terminated'] == 'AUTO') echo ((PMA_whichCrlf() == "\n") ? '\n' : '\r\n'); else echo $cfg['Export']['csv_terminated']; ?>" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strReplaceNULLBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="csv_replace_null" size="20" value="<?php echo $cfg['Export']['csv_null']; ?>" class="textfield" />
                        </td>
                    </tr>
                </table>
                <input type="checkbox" name="showcsvnames" value="yes" id="checkbox_dump_showcsvnames" <?php PMA_exportCheckboxCheck('csv_columns'); ?> />
                <label for="checkbox_dump_showcsvnames"><?php echo $strPutColNames; ?></label>
            </fieldset>

            <!-- Excel options -->
            <fieldset id="excel_options">
                <legend><?php echo $strExcelOptions; ?></legend>
                <input type="hidden" name="excel_data" value="excel_data" />
                <table border="0" cellspacing="1" cellpadding="0">
                    <tr>
                        <td>
                            <?php echo $strReplaceNULLBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="excel_replace_null" size="20" value="<?php echo $cfg['Export']['excel_null']; ?>" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="checkbox" name="showexcelnames" value="yes" id="checkbox_dump_showexcelnames" <?php PMA_exportCheckboxCheck('excel_columns'); ?> />
                            <label for="checkbox_dump_showexcelnames"><?php echo $strPutColNames; ?></label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="select_excel_edition">
                                <?php echo $strExcelEdition; ?>:&nbsp;
                            </label>
                        </td>
                        <td>
                            <select name="excel_edition" id="select_excel_edition" />
                                <option value="win"<?php echo $cfg['Export']['excel_edition'] == 'win' ? ' selected="selected"' : ''; ?>>Windows</option>
                                <option value="mac"<?php echo $cfg['Export']['excel_edition'] == 'mac' ? ' selected="selected"' : ''; ?>>Macintosh</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </fieldset>

            <fieldset id="none_options">
                <legend><?php echo $strNoOptions; ?></legend>
                <input type="hidden" name="xml_data" value="xml_data" />
            </fieldset>

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
        <td colspan="2" align="center">
            <fieldset>
                <?php echo sprintf($strDumpXRows , '<input type="text" name="limit_to" size="5" value="' . (isset($unlim_num_rows)?$unlim_num_rows: PMA_countRecords($db, $table, TRUE)) . '" class="textfield" style="vertical-align: middle" onfocus="this.select()" />' , '<input type="text" name="limit_from" value="0" size="5" class="textfield" style="vertical-align: middle" onfocus="this.select()" />') . "\n"; ?>
            </fieldset>
        </td>
    </tr>
<?php
}
?>

    <tr>
        <!-- Export to screen or to file -->
        <td colspan="2">
            <fieldset>
                <legend>
                    <input type="checkbox" name="asfile" value="sendit" id="checkbox_dump_asfile" <?php PMA_exportCheckboxCheck('asfile'); ?> />
                    <label for="checkbox_dump_asfile"><?php echo $strSend; ?></label>
                </legend>

                <?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
                <input type="checkbox" name="onserver" value="saveit" id="checkbox_dump_onserver"  onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportCheckboxCheck('onserver'); ?> />
                <label for="checkbox_dump_onserver"><?php echo sprintf($strSaveOnServer, htmlspecialchars($cfg['SaveDir'])); ?></label>,
                <input type="checkbox" name="onserverover" value="saveitover" id="checkbox_dump_onserverover"  onclick="getElement('checkbox_dump_onserver').checked = true;getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportCheckboxCheck('onserver_overwrite'); ?> />
                <label for="checkbox_dump_onserverover"><?php echo $strOverwriteExisting; ?></label>
                <br />
                <?php } ?>

                <?php echo $strFileNameTemplate; ?>&nbsp;:
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
                ?>
                />
                (
                <input type="checkbox" name="remember_template" id="checkbox_remember_template" <?php PMA_exportCheckboxCheck('remember_file_template'); ?> />
                <label for="checkbox_remember_template"><?php echo $strFileNameTemplateRemember; ?></label>
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

                <fieldset>
                    <legend><?php echo $strCompression; ?></legend>

                    <input type="radio" name="compression" value="none" id="radio_compression_none" onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportIsActive('compression', 'none'); ?> />
                    <label for="radio_compression_none"><?php echo $strNone; ?></label>&nbsp;

<?php

// zip, gzip and bzip2 encode features
$is_zip  = (isset($cfg['ZipDump']) && $cfg['ZipDump'] && @function_exists('gzcompress'));
$is_gzip = (isset($cfg['GZipDump']) && $cfg['GZipDump'] && @function_exists('gzencode'));
$is_bzip = (isset($cfg['BZipDump']) && $cfg['BZipDump'] && @function_exists('bzcompress'));
if ($is_zip || $is_gzip || $is_bzip) {
    if ($is_zip) {
        ?>
                <input type="radio" name="compression" value="zip" id="radio_compression_zip" onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportIsActive('compression', 'zip'); ?> />
                <label for="radio_compression_zip"><?php echo $strZip; ?></label><?php echo (($is_gzip || $is_bzip) ? '&nbsp;' : ''); ?>
        <?php
    }
    if ($is_gzip) {
        echo "\n"
        ?>
                <input type="radio" name="compression" value="gzip" id="radio_compression_gzip" onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportIsActive('compression', 'gzip'); ?> />
                <label for="radio_compression_gzip"><?php echo $strGzip; ?></label><?php echo ($is_bzip ? '&nbsp;' : ''); ?>
        <?php
    }
    if ($is_bzip) {
        echo "\n"
        ?>
                <input type="radio" name="compression" value="bzip" id="radio_compression_bzip" onclick="getElement('checkbox_dump_asfile').checked = true;" <?php PMA_exportIsActive('compression', 'bzip'); ?> />
                <label for="radio_compression_bzip"><?php echo $strBzip; ?></label>
        <?php
    }
}
echo "\n";
?>
            </fieldset>
        </td>
    </tr>

<?php
// Encoding setting form appended by Y.Kawada
if (function_exists('PMA_set_enc_form')) {
    ?>
    <tr>
        <!-- Japanese encoding setting -->
        <td colspan="2" align="center">
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
        <td colspan="2" align="center">
            <input type="submit" value="<?php echo $strGo; ?>" />
        </td>
    </tr>
    </table>
</form>
<table align="center">
<tr>
    <td valign="top">*&nbsp;</td>
    <td>
        <?php echo sprintf($strFileNameTemplateHelp, '<a href="http://www.php.net/manual/function.strftime.php" target="documentation">', '</a>') . "\n"; ?>

    </td>
</tr>
</table>
