<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/*
 * Whether we export single table or more
 */
$export_single = (!isset($multi_tables) || $multi_tables == '') && (isset($table));
?>

<form method="post" action="tbl_dump.php3" name="<?php echo $tbl_dump_form_name; ?>">
    <?php 
if ($export_single) {
    echo '    ' . PMA_generate_common_hidden_inputs($db, $table);
} else {
    echo '    ' . PMA_generate_common_hidden_inputs($db);
}

if (isset($sql_query)) {
    echo '    <input type="hidden" name="sql_query" value="' . urlencode($sql_query) . '" />';
}
    ?>

    <script type="text/javascript">
    <!--
    function hide_them_all() {
        getElement("csv_options").style.display = 'none';
        getElement("excel_options").style.display = 'none';
        getElement("latex_options").style.display = 'none';
        getElement("sql_options").style.display = 'none';
        getElement("none_options").style.display = 'none';
    }
    //-->
    </script>

    <table cellpadding="5" border="0" cellspacing="0" align="center">
    <tr>

        <!-- Formats to export to -->
        <td nowrap="nowrap" valign="top">
            <fieldset <?php echo ((!isset($multi_tables) || isset($multi_tables) && $multi_tables == '') ? 'style="height: 220px;"' : ''); ?>>
            <legend><?php echo $strExport; ?></legend>
            <br>
            <?php
            if (isset($multi_tables) && $multi_tables != '') {
                echo $multi_tables;
            }
            ?>
            
            <!-- SQL -->
            <input type="radio" name="what" value="sql" id="radio_dump_sql" checked="checked" onclick="if(this.checked) { hide_them_all(); getElement('sql_options').style.display = 'block'; }; return true" />
            <label for="radio_dump_sql"><?php echo $strSQL; ?></label>
            <br /><br />

            <!-- LaTeX table -->
            <input type="radio" name="what" value="latex" id="radio_dump_latex"  onclick="if(this.checked) { hide_them_all(); getElement('latex_options').style.display = 'block'; }; return true" />
            <label for="radio_dump_latex"><?php echo $strLaTeX; ?></label>
            <br /><br />

            <!-- Excel CSV -->
            <input type="radio" name="what" value="excel" id="radio_dump_excel"  onclick="if(this.checked) { hide_them_all(); getElement('excel_options').style.display = 'block'; }; return true" />
            <label for="radio_dump_excel"><?php echo $strStrucExcelCSV; ?></label>
            <br /><br />
            <!-- General CSV -->
            <input type="radio" name="what" value="csv" id="radio_dump_csv"  onclick="if(this.checked) { hide_them_all(); getElement('csv_options').style.display = 'block'; }; return true" />
            <label for="radio_dump_csv"><?php echo $strStrucCSV;?></label>
            <br /><br />

            <!-- XML -->
            <input type="radio" name="what" value="xml" id="radio_dump_xml" onclick="if(this.checked) { hide_them_all(); getElement('none_options').style.display = 'block'; }; return true" />
            <label for="radio_dump_xml"><?php echo $strXML; ?></label>&nbsp;&nbsp;
            </fieldset>
        </td>
        <!-- Options -->
        <td valign="top" id="options_td" width="400">
        
            <!-- SQL options -->
            <fieldset id="sql_options">
                <legend><?php echo $strSQLOptions; ?> (<a href="./Documentation.html#faqexport" target="documentation"><?php echo $strDocu; ?></a>)</legend>

                <!-- For structure -->
                <fieldset>
                    <legend>
                        <input type="checkbox" name="sql_structure" value="structure" id="checkbox_sql_structure" checked="checked" onclick="if(!this.checked && !getElement('checkbox_sql_data').checked) return false; else return true;" />
                        <label for="checkbox_sql_structure"><?php echo $strStructure; ?></label><br />
                    </legend>
                    <input type="checkbox" name="drop" value="1" id="checkbox_dump_drop" />
                    <label for="checkbox_dump_drop"><?php echo $strStrucDrop; ?></label><br />
<?php
// Add backquotes checkbox
if (PMA_MYSQL_INT_VERSION >= 32306) {
    ?>
                    <input type="checkbox" name="use_backquotes" value="1" id="checkbox_dump_use_backquotes" checked="checked" />
                    <label for="checkbox_dump_use_backquotes"><?php echo $strUseBackquotes; ?></label><br />
    <?php
} // end backquotes feature
echo "\n";

// garvin: whether to show column comments
require('./libraries/relation.lib.php3');
$cfgRelation = PMA_getRelationsParam();

if ($cfgRelation['commwork']) {
    ?>
                    <input type="checkbox" name="use_comments" value="1" id="checkbox_dump_use_comments" />
                    <label for="checkbox_dump_use_comments"><?php echo $strDumpComments; ?></label><br />
    <?php
} // end dump comments
echo "\n";
?>
                </fieldset>

                <!-- For data -->
                <fieldset>
                    <legend>
                        <input type="checkbox" name="sql_data" value="data" id="checkbox_sql_data" checked="checked" onclick="if(!this.checked && !getElement('checkbox_sql_structure').checked) return false; else return true;" />
                        <label for="checkbox_sql_data"><?php echo $strData; ?></label><br />
                    </legend>
                    <input type="checkbox" name="showcolumns" value="yes" id="checkbox_dump_showcolumns" />
                    <label for="checkbox_dump_showcolumns"><?php echo $strCompleteInserts; ?></label><br />
                    <input type="checkbox" name="extended_ins" value="yes" id="checkbox_dump_extended_ins" />
                    <label for="checkbox_dump_extended_ins"><?php echo $strExtendedInserts; ?></label><br />
                </fieldset>
            </fieldset>
             
             <!-- LaTeX options -->
             <fieldset id="latex_options">
                 <legend><?php echo $strLaTeXOptions; ?></legend>
 
                 <!-- For structure -->
                 <fieldset>
                     <legend>
                         <input type="checkbox" name="ltx_structure" value="structure" id="checkbox_ltx_structure" checked="checked" onclick="if(!this.checked && !getElement('checkbox_ltx_data').checked) return false; else return true;" />
                         <label for="checkbox_ltx_structure"><?php echo $strStructure; ?></label><br />
                     </legend>
 <?php
 
 // garvin: whether to show column comments
 require('./libraries/relation.lib.php3');
 $cfgRelation = PMA_getRelationsParam();
 
 if (!empty($cfgRelation['relation'])) {
     ?>
                     <input type="checkbox" name="ltx_relation" value="yes" id="checkbox_ltx_use_relation" checked="checked" />
                     <label for="checkbox_ltx_use_relation"><?php echo $strRelations; ?></label><br />
     <?php
 } // end relation
 
 if ($cfgRelation['commwork']) {
     ?>
                     <input type="checkbox" name="ltx_comments" value="yes" id="checkbox_ltx_use_comments" checked="checked" />
                     <label for="checkbox_ltx_use_comments"><?php echo $strComments; ?></label><br />
     <?php
 } // end comments
 
 if ($cfgRelation['mimework']) {
     ?>
                     <input type="checkbox" name="ltx_mime" value="yes" id="checkbox_ltx_use_mime" checked="checked" />
                     <label for="checkbox_ltx_use_mime"><?php echo $strMIME_MIMEtype; ?></label><br />
     <?php
 } // end MIME
 echo "\n";
 ?>
                 </fieldset>
 
                 <!-- For data -->
                 <fieldset>
                     <legend>
                         <input type="checkbox" name="ltx_data" value="data" id="checkbox_ltx_data" checked="checked" onclick="if(!this.checked && !getElement('checkbox_ltx_structure').checked) return false; else return true;" />
                         <label for="checkbox_ltx_data"><?php echo $strData; ?></label><br />
                     </legend>
                     <input type="checkbox" name="ltx_showcolumns" value="yes" id="ch_ltx_showcolumns" checked="checked" />
                     <label for="ch_ltx_showcolumns"><?php echo $strColumnNames; ?></label><br />
                    <table border="0" cellspacing="1" cellpadding="0">
                        <tr>
                            <td>
                                <?php echo $strReplaceNULLBy; ?>&nbsp;
                            </td>
                            <td>
                                <input type="text" name="ltx_replace_null" size="20" value="\textit{NULL}" class="textfield" />
                            </td>
                        </tr>
                    </table>
                 </fieldset>
             </fieldset>
            
             <!-- CSV options -->
            <fieldset id="csv_options">
                <legend><?php echo $strCSVOptions; ?></legend>
                <table border="0" cellspacing="1" cellpadding="0">
                    <tr>
                        <td>
                            <?php echo $strFieldsTerminatedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="separator" size="2" value=";" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strFieldsEnclosedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="enclosed" size="2" value="&quot;" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strFieldsEscapedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="escaped" size="2" value="\" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strFieldsTerminatedBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="add_character" size="2" value="<?php echo ((PMA_whichCrlf() == "\n") ? '\n' : '\r\n'); ?>" class="textfield" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo $strReplaceNULLBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="csv_replace_null" size="20" value="NULL" class="textfield" />
                        </td>
                    </tr>
                </table>
                <input type="checkbox" name="showcsvnames" value="yes" id="checkbox_dump_showcsvnames" />
                <label for="checkbox_dump_showcsvnames"><?php echo $strPutColNames; ?></label>
            </fieldset>
            
            <!-- Excel options -->
            <fieldset id="excel_options">
                <legend><?php echo $strExcelOptions; ?></legend>
                <table border="0" cellspacing="1" cellpadding="0">
                    <tr>
                        <td>
                            <?php echo $strReplaceNULLBy; ?>&nbsp;
                        </td>
                        <td>
                            <input type="text" name="excel_replace_null" size="20" value="NULL" class="textfield" />
                        </td>
                    </tr>
                </table>
            </fieldset>
            
            <fieldset id="none_options">
                <legend><?php echo $strNoOptions; ?></legend>
            </fieldset>

            <script type="text/javascript">
            <!--
                hide_them_all();
                if (document.getElementById('radio_dump_sql').checked) {
                    getElement('sql_options').style.display = 'block';
                } else if (document.getElementById('radio_dump_latex').checked) {
                    getElement('latex_options').style.display = 'block';
                } else if (document.getElementById('radio_dump_csv').checked) {
                    getElement('csv_options').style.display = 'block';
                } else if (document.getElementById('radio_dump_excel').checked) {
                    getElement('excel_options').style.display = 'block';
                } else {
                    getElement('none_options').style.display = 'block';
                }
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
                    <input type="checkbox" name="asfile" value="sendit" id="checkbox_dump_asfile" />
                    <label for="checkbox_dump_asfile"><?php echo $strSend; ?></label>
                </legend>
                
                <?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
                <input type="checkbox" name="onserver" value="saveit" id="checkbox_dump_onserver"  onclick="getElement('checkbox_dump_asfile').checked = true;" />
                <label for="checkbox_dump_onserver"><?php echo sprintf($strSaveOnServer, htmlspecialchars($cfg['SaveDir'])); ?></label>,
                <input type="checkbox" name="onserverover" value="saveitover" id="checkbox_dump_onserverover"  onclick="getElement('checkbox_dump_onserver').checked = true;getElement('checkbox_dump_asfile').checked = true;" />
                <label for="checkbox_dump_onserverover"><?php echo $strOverwriteExisting; ?></label>
                <br />
                <?php } ?>
                
                <?php echo $strFileNameTemplate; ?>&nbsp;:
                <input type="text" name="filename_template"
                <?php 
                    echo ' value="';
                    if (!$export_single) {
                        if (isset($_COOKIE) && !empty($_COOKIE['pma_db_filename_template'])) {
                            echo $_COOKIE['pma_db_filename_template'];
                        } elseif (isset($HTTP_COOKIE_VARS) && !empty($HTTP_COOKIE_VARS['pma_db_filename_template'])) {
                            echo $HTTP_COOKIE_VARS['pma_db_filename_template'];
                        } else {
                            echo '__DB__'; 
                        }
                    } else {
                        if (isset($_COOKIE) && !empty($_COOKIE['pma_table_filename_template'])) {
                            echo $_COOKIE['pma_table_filename_template'];
                        } elseif (isset($HTTP_COOKIE_VARS) && !empty($HTTP_COOKIE_VARS['pma_table_filename_template'])) {
                            echo $HTTP_COOKIE_VARS['pma_table_filename_template'];
                        } else {
                            echo '__TABLE__'; 
                        }
                    }
                    echo '" ';
                ?>
                />
                (
                <input type="checkbox" name="remember_template" checked="checked" id="checkbox_remember_template" />
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

                    <input type="radio" name="compression" value="none" id="radio_compression_none" checked="checked" onclick="getElement('checkbox_dump_asfile').checked = true;" />
                    <label for="radio_compression_none"><?php echo $strNone; ?></label>&nbsp;

<?php

// zip, gzip and bzip2 encode features
if (PMA_PHP_INT_VERSION >= 40004) {
    $is_zip  = (isset($cfg['ZipDump']) && $cfg['ZipDump'] && @function_exists('gzcompress'));
    $is_gzip = (isset($cfg['GZipDump']) && $cfg['GZipDump'] && @function_exists('gzencode'));
    $is_bzip = (isset($cfg['BZipDump']) && $cfg['BZipDump'] && @function_exists('bzcompress'));
    if ($is_zip || $is_gzip || $is_bzip) {
        if ($is_zip) {
            ?>
                    <input type="radio" name="compression" value="zip" id="radio_compression_zip" onclick="getElement('checkbox_dump_asfile').checked = true;" />
                    <label for="radio_compression_zip"><?php echo $strZip; ?></label><?php echo (($is_gzip || $is_bzip) ? '&nbsp;' : ''); ?>
            <?php
        }
        if ($is_gzip) {
            echo "\n"
            ?>
                    <input type="radio" name="compression" value="gzip" id="radio_compression_gzip" onclick="getElement('checkbox_dump_asfile').checked = true;" />
                    <label for="radio_compression_gzip"><?php echo $strGzip; ?></label><?php echo ($is_bzip ? '&nbsp;' : ''); ?>
            <?php
        }
        if ($is_bzip) {
            echo "\n"
            ?>
                    <input type="radio" name="compression" value="bzip" id="radio_compression_bzip" onclick="getElement('checkbox_dump_asfile').checked = true;" />
                    <label for="radio_compression_bzip"><?php echo $strBzip; ?></label>
            <?php
        }
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
