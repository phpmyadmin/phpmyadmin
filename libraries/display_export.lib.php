<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once './libraries/Table.class.php';

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
$hide_xml       = (bool) (isset($db) && strlen($db));
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
        echo '<input type="hidden" name="single_table" value="TRUE" />' . "\n";
    }
}
echo '<input type="hidden" name="export_type" value="' . $export_type . '" />' . "\n";

if (isset($sql_query)) {
    echo '<input type="hidden" name="sql_query" value="' . htmlspecialchars($sql_query) . '" />' . "\n";
}
?>

<script type="text/javascript" language="javascript">
//<![CDATA[
function hide_them_all() {
    document.getElementById("csv_options").style.display = 'none';
    document.getElementById("excel_options").style.display = 'none';
    document.getElementById("latex_options").style.display = 'none';
    document.getElementById("htmlexcel_options").style.display = 'none';
    document.getElementById("htmlword_options").style.display = 'none';
    document.getElementById("pdf_options").style.display = 'none';
<?php if ($xls) { ?>
    document.getElementById("xls_options").style.display = 'none';
<?php } ?>
<?php if (!$hide_sql) { ?>
    document.getElementById("sql_options").style.display = 'none';
<?php } ?>
    document.getElementById("none_options").style.display = 'none';
}

function show_checked_option() {
    hide_them_all();
    if (document.getElementById('radio_dump_latex').checked) {
        document.getElementById('latex_options').style.display = 'block';
    } else if (document.getElementById('radio_dump_htmlexcel').checked) {
        document.getElementById('htmlexcel_options').style.display = 'block';
    } else if (document.getElementById('radio_dump_pdf').checked) {
        document.getElementById('pdf_options').style.display = 'block';
    } else if (document.getElementById('radio_dump_htmlword').checked) {
        document.getElementById('htmlword_options').style.display = 'block';
<?php if ($xls) { ?>
    } else if (document.getElementById('radio_dump_xls').checked) {
        document.getElementById('xls_options').style.display = 'block';
<?php } ?>
<?php if (!$hide_sql) { ?>
    } else if (document.getElementById('radio_dump_sql').checked) {
        document.getElementById('sql_options').style.display = 'block';
<?php } ?>
<?php if (!$hide_xml) { ?>
    } else if (document.getElementById('radio_dump_xml').checked) {
        document.getElementById('none_options').style.display = 'block';
<?php } ?>
    } else if (document.getElementById('radio_dump_csv').checked) {
        document.getElementById('csv_options').style.display = 'block';
    } else if (document.getElementById('radio_dump_excel').checked) {
        document.getElementById('excel_options').style.display = 'block';
    } else {
        if (document.getElementById('radio_dump_sql')) {
            document.getElementById('radio_dump_sql').checked = true;
            document.getElementById('sql_options').style.display = 'block';
        } else if (document.getElementById('radio_dump_csv')) {
            document.getElementById('radio_dump_csv').checked = true;
            document.getElementById('csv_options').style.display = 'block';
        } else {
            document.getElementById('none_options').style.display = 'block';
        }
    }
}
//]]>
</script>

<fieldset id="fieldsetexport">
<legend><?php echo $export_page_title; ?></legend>

<?php
/* 
 * this table is needed to fix rendering in Opera <= 9 and Safari <= 2
 * normaly just the two fieldset would have float: left
 */
?>
<table><tr><td>

<div id="div_container_exportoptions">
<fieldset id="exportoptions">
<legend><?php echo $strExport; ?></legend>

    <?php if ( ! empty( $multi_values ) ) { ?>
    <div class="formelementrow">
        <?php echo $multi_values; ?>
    </div>
    <?php } ?>

<?php if ( ! $hide_sql ) { /* SQL */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="sql" id="radio_dump_sql"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('sql_options').style.display = 'block';
                }; return true"
            <?php PMA_exportIsActive('format', 'sql'); ?> />
            <label for="radio_dump_sql"><?php echo $strSQL; ?></label>
    </div>
<?php } /* LaTeX table */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="latex" id="radio_dump_latex"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('latex_options').style.display = 'block';
                }; return true"
            <?php PMA_exportIsActive('format', 'latex'); ?> />
        <label for="radio_dump_latex"><?php echo $strLaTeX; ?></label>
    </div>
<?php /* PDF  */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="pdf" id="radio_dump_pdf"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('pdf_options').style.display = 'block';
                }; return true"
            <?php PMA_exportIsActive('format', 'pdf'); ?> />
        <label for="radio_dump_pdf"><?php echo $strPDF; ?></label>
    </div>
<?php /* HTML Excel */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="htmlexcel" id="radio_dump_htmlexcel"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('htmlexcel_options').style.display = 'block';
                    document.getElementById('checkbox_dump_asfile').checked = true;
                };  return true"
            <?php PMA_exportIsActive('format', 'htmlexcel'); ?> />
        <label for="radio_dump_htmlexcel"><?php echo $strHTMLExcel; ?></label>
    </div>
<?php /* HTML Word */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="htmlword" id="radio_dump_htmlword"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('htmlword_options').style.display = 'block';
                    document.getElementById('checkbox_dump_asfile').checked = true;
                };  return true"
            <?php PMA_exportIsActive('format', 'htmlword'); ?> />
        <label for="radio_dump_htmlword"><?php echo $strHTMLWord; ?></label>
    </div>
<?php if ($xls) { /*  Native Excel */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="xls" id="radio_dump_xls"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('xls_options').style.display = 'block';
                    document.getElementById('checkbox_dump_asfile').checked = true;
                };  return true"
            <?php PMA_exportIsActive('format', 'xls'); ?> />
        <label for="radio_dump_xls"><?php echo $strStrucNativeExcel; ?></label>
    </div>
<?php } /* Excel CSV */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="excel" id="radio_dump_excel"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('excel_options').style.display = 'block';
                }; return true"
            <?php PMA_exportIsActive('format', 'excel'); ?> />
        <label for="radio_dump_excel"><?php echo $strStrucExcelCSV; ?></label>
    </div>
<?php /* General CSV */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="csv" id="radio_dump_csv"
            onclick="if
                (this.checked) {
                    hide_them_all();
                    document.getElementById('csv_options').style.display = 'block';
                 }; return true"
             <?php PMA_exportIsActive('format', 'csv'); ?> />
        <label for="radio_dump_csv"><?php echo $strStrucCSV;?></label>
    </div>
<?php if (!$hide_xml) { /* XML */ ?>
    <div class="formelementrow">
        <input type="radio" name="what" value="xml" id="radio_dump_xml"
            onclick="
                if (this.checked) {
                    hide_them_all();
                    document.getElementById('none_options').style.display = 'block';
                }; return true"
            <?php PMA_exportIsActive('format', 'xml'); ?> />
        <label for="radio_dump_xml"><?php echo $strXML; ?></label>
    </div>
<?php } ?>

</fieldset>
</div>

</td><td>

<div id="div_container_sub_exportoptions">
<?php if ( ! $hide_sql ) { /* SQL options */ ?>
<fieldset id="sql_options">
    <legend>
    <?php
    echo $strSQLOptions;
    $goto_documentation = '<a href="./Documentation.html#faqexport" target="documentation">';
    echo ( $cfg['ReplaceHelpImg'] ? '' : '(' )
       . $goto_documentation
       . ( $cfg['ReplaceHelpImg'] ?
             '<img class="icon" src="' . $pmaThemeImage . 'b_help.png" alt="'
             .$strDocu . '" width="11" height="11" />'
           : $strDocu )
       . '</a>' . ($cfg['ReplaceHelpImg'] ? '' : ')');
    ?>
    </legend>
    <div class="formelementrow">
        <?php echo $strAddHeaderComment; ?>:<br />
        <input type="text" name="header_comment" size="30"
            value="<?php echo $cfg['Export']['sql_header_comment']; ?>" />
    </div>

    <div class="formelementrow">
        <input type="checkbox" name="use_transaction" value="yes"
            id="checkbox_use_transaction"
            <?php PMA_exportCheckboxCheck('sql_use_transaction'); ?> />
        <label for="checkbox_use_transaction">
            <?php echo $strEncloseInTransaction; ?></label>
    </div>

    <div class="formelementrow">
        <input type="checkbox" name="disable_fk" value="yes"
            id="checkbox_disable_fk"
            <?php PMA_exportCheckboxCheck('sql_disable_fk'); ?> />
        <label for="checkbox_disable_fk">
            <?php echo $strDisableForeignChecks; ?></label>
    </div>
<?php if (PMA_MYSQL_INT_VERSION >= 40100) { ?>
    <label for="select_sql_compat">
        <?php echo $strSQLExportCompatibility; ?>:</label>
    <select name="sql_compat" id="select_sql_compat">
        <?php
        $compats = array('NONE');
        if (PMA_MYSQL_INT_VERSION >= 40101) {
            $compats[] = 'ANSI';
            $compats[] = 'DB2';
            $compats[] = 'MAXDB';
            $compats[] = 'MYSQL323';
            $compats[] = 'MYSQL40';
            $compats[] = 'MSSQL';
            $compats[] = 'ORACLE';
            $compats[] = 'POSTGRESQL';
            if (PMA_MYSQL_INT_VERSION >= 50001) {
                $compats[] = 'TRADITIONAL';
            }
        }
        foreach ($compats as $x) {
            echo '<option value="' . $x . '"'
                . ($cfg['Export']['sql_compat'] == $x ? ' selected="selected"' : '' )
                . '>' . $x . '</option>' . "\n";
        }
        ?>
    </select>
        <?php echo PMA_showMySQLDocu('manual_MySQL_Database_Administration',
            'Server_SQL_mode') . "\n";
    } ?>
<?php if ( $export_type == 'server' ) { /* For databases */ ?>
    <fieldset>
        <legend><?php echo $strDatabaseExportOptions; ?></legend>
        <input type="checkbox" name="drop_database" value="yes"
            id="checkbox_drop_database"
            <?php PMA_exportCheckboxCheck('sql_drop_database'); ?> />
        <label for="checkbox_drop_database">
            <?php echo $strAddDropDatabase; ?></label>
    </fieldset>
<?php } if ( ! $hide_structure ) { /* SQL structure */ ?>
    <fieldset>
        <legend>
            <input type="checkbox" name="sql_structure" value="structure"
                id="checkbox_sql_structure"
                <?php PMA_exportCheckboxCheck('sql_structure'); ?>
                onclick="
                    if (!this.checked &amp;&amp; !document.getElementById('checkbox_sql_data').checked)
                        return false;
                    else return true;" />
            <label for="checkbox_sql_structure">
                <?php echo $strStructure; ?></label>
        </legend>

        <input type="checkbox" name="drop" value="1" id="checkbox_dump_drop"
            <?php PMA_exportCheckboxCheck('sql_drop_table'); ?> />
        <label for="checkbox_dump_drop">
            <?php echo $strStrucDrop; ?></label><br />

        <input type="checkbox" name="if_not_exists" value="1"
            id="checkbox_dump_if_not_exists"
            <?php PMA_exportCheckboxCheck('sql_if_not_exists'); ?> />
        <label for="checkbox_dump_if_not_exists">
            <?php echo $strAddIfNotExists; ?></label><br />

        <input type="checkbox" name="sql_auto_increment" value="1"
            id="checkbox_auto_increment"
            <?php PMA_exportCheckboxCheck('sql_auto_increment'); ?> />
        <label for="checkbox_auto_increment">
            <?php echo $strAddAutoIncrement; ?></label><br />

        <input type="checkbox" name="use_backquotes" value="1"
            id="checkbox_dump_use_backquotes"
            <?php PMA_exportCheckboxCheck('sql_backquotes'); ?> />
        <label for="checkbox_dump_use_backquotes">
            <?php echo $strUseBackquotes; ?></label><br />

        <b><?php echo $strAddIntoComments; ?>:</b><br />

        <input type="checkbox" name="sql_dates" value="yes"
            id="checkbox_sql_dates"
            <?php PMA_exportCheckboxCheck('sql_dates'); ?> />
        <label for="checkbox_sql_dates">
            <?php echo $strCreationDates; ?></label><br />
<?php if (!empty($cfgRelation['relation'])) { ?>
        <input type="checkbox" name="sql_relation" value="yes"
            id="checkbox_sql_use_relation"
            <?php PMA_exportCheckboxCheck('sql_relation'); ?> />
        <label for="checkbox_sql_use_relation"><?php echo $strRelations; ?></label><br />
<?php } if (!empty($cfgRelation['commwork']) && PMA_MYSQL_INT_VERSION < 40100) { ?>
        <input type="checkbox" name="sql_comments" value="yes"
            id="checkbox_sql_use_comments"
            <?php PMA_exportCheckboxCheck('sql_comments'); ?> />
        <label for="checkbox_sql_use_comments"><?php echo $strComments; ?></label><br />
<?php } if ($cfgRelation['mimework']) { ?>
        <input type="checkbox" name="sql_mime" value="yes"
            id="checkbox_sql_use_mime"
            <?php PMA_exportCheckboxCheck('sql_mime'); ?> />
        <label for="checkbox_sql_use_mime"><?php echo $strMIME_MIMEtype; ?></label><br />
<?php } ?>
    </fieldset>
<?php
    } /* end SQL STRUCTURE */
/* SQL data */
?>
    <fieldset>
        <legend>
            <input type="checkbox" name="sql_data" value="data"
                id="checkbox_sql_data" <?php PMA_exportCheckboxCheck('sql_data'); ?>
                onclick="
                    if (!this.checked &amp;&amp; (!document.getElementById('checkbox_sql_structure') || !document.getElementById('checkbox_sql_structure').checked))
                        return false;
                    else return true;" />
            <label for="checkbox_sql_data">
                <?php echo $strData; ?></label>
        </legend>
        <input type="checkbox" name="showcolumns" value="yes"
            id="checkbox_dump_showcolumns"
            <?php PMA_exportCheckboxCheck('sql_columns'); ?> />
        <label for="checkbox_dump_showcolumns">
            <?php echo $strCompleteInserts; ?></label><br />

        <input type="checkbox" name="extended_ins" value="yes"
            id="checkbox_dump_extended_ins"
            <?php PMA_exportCheckboxCheck('sql_extended'); ?> />
        <label for="checkbox_dump_extended_ins">
            <?php echo $strExtendedInserts; ?></label><br />

        <label for="input_max_query_size">
            <?php echo $strMaximalQueryLength; ?>:</label>
        <input type="text" name="max_query_size" id="input_max_query_size"
            value="<?php echo $cfg['Export']['sql_max_query_size'];?>" /><br />

        <input type="checkbox" name="delayed" value="yes"
            id="checkbox_dump_delayed"
            <?php PMA_exportCheckboxCheck('sql_delayed'); ?> />
        <label for="checkbox_dump_delayed">
            <?php echo $strDelayedInserts; ?></label><br />

        <input type="checkbox" name="sql_ignore" value="yes"
            id="checkbox_dump_ignore"
            <?php PMA_exportCheckboxCheck('sql_ignore'); ?> />
        <label for="checkbox_dump_ignore">
            <?php echo $strIgnoreInserts; ?></label><br />

        <input type="checkbox" name="hexforbinary" value="yes"
            id="checkbox_hexforbinary"
            <?php PMA_exportCheckboxCheck('sql_hex_for_binary'); ?> />
        <label for="checkbox_hexforbinary">
            <?php echo $strHexForBinary; ?></label><br />

        <label for="select_sql_type">
            <?php echo $strSQLExportType; ?>:</label>
        <select name="sql_type" id="select_sql_type">
            <option value="insert"<?php echo $cfg['Export']['sql_type'] == 'insert' ? ' selected="selected"' : ''; ?>>INSERT</option>
            <option value="update"<?php echo $cfg['Export']['sql_type'] == 'update' ? ' selected="selected"' : ''; ?>>UPDATE</option>
            <option value="replace"<?php echo $cfg['Export']['sql_type'] == 'replace' ? ' selected="selected"' : ''; ?>>REPLACE</option>
        </select>
    </fieldset>
</fieldset>
    <?php
} // end SQL-OPTIONS
?>

<?php /* LaTeX options */ ?>
<fieldset id="latex_options">
    <legend><?php echo $strLaTeXOptions; ?></legend>

    <div class="formelementrow">
        <input type="checkbox" name="latex_caption" value="yes"
            id="checkbox_latex_show_caption"
            <?php PMA_exportCheckboxCheck('latex_caption'); ?> />
        <label for="checkbox_latex_show_caption">
            <?php echo $strLatexIncludeCaption; ?></label>
    </div>

<?php if ( ! $hide_structure ) { /* LaTeX structure */ ?>
    <fieldset>
        <legend>
            <input type="checkbox" name="latex_structure" value="structure"
                id="checkbox_latex_structure"
                <?php PMA_exportCheckboxCheck('latex_structure'); ?>
                onclick="
                    if (!this.checked &amp;&amp; !document.getElementById('checkbox_latex_data').checked)
                        return false;
                    else return true;" />
            <label for="checkbox_latex_structure">
                <?php echo $strStructure; ?></label>
        </legend>

        <table>
        <tr><td><label for="latex_structure_caption">
                    <?php echo $strLatexCaption; ?></label></td>
            <td><input type="text" name="latex_structure_caption" size="30"
                    value="<?php echo $strLatexStructure; ?>"
                    id="latex_structure_caption" />
            </td>
        </tr>
        <tr><td><label for="latex_structure_continued_caption">
                    <?php echo $strLatexContinuedCaption; ?></label></td>
            <td><input type="text" name="latex_structure_continued_caption"
                    value="<?php echo $strLatexStructure . ' ' . $strLatexContinued; ?>"
                    size="30" id="latex_structure_continued_caption" />
            </td>
        </tr>
        <tr><td><label for="latex_structure_label">
                    <?php echo $strLatexLabel; ?></label></td>
            <td><input type="text" name="latex_structure_label" size="30"
                    value="<?php echo $cfg['Export']['latex_structure_label']; ?>"
                    id="latex_structure_label" />
            </td>
        </tr>
        </table>

    <?php if ( ! empty( $cfgRelation['relation']) ) { ?>
        <input type="checkbox" name="latex_relation" value="yes"
            id="checkbox_latex_use_relation"
            <?php PMA_exportCheckboxCheck('latex_relation'); ?> />
        <label for="checkbox_latex_use_relation">
            <?php echo $strRelations; ?></label><br />
    <?php } if ( $cfgRelation['commwork'] ) { ?>
        <input type="checkbox" name="latex_comments" value="yes"
            id="checkbox_latex_use_comments"
            <?php PMA_exportCheckboxCheck('latex_comments'); ?> />
        <label for="checkbox_latex_use_comments">
            <?php echo $strComments; ?></label><br />
    <?php } if ( $cfgRelation['mimework'] ) { ?>
        <input type="checkbox" name="latex_mime" value="yes"
            id="checkbox_latex_use_mime"
            <?php PMA_exportCheckboxCheck('latex_mime'); ?> />
        <label for="checkbox_latex_use_mime">
            <?php echo $strMIME_MIMEtype; ?></label><br />
    <?php } ?>
    </fieldset>
    <?php
} // end LaTeX STRUCTURE
/* LaTeX data */
?>
    <fieldset>
        <legend>
            <input type="checkbox" name="latex_data" value="data"
                id="checkbox_latex_data"
                <?php PMA_exportCheckboxCheck('latex_data'); ?>
                onclick="
                    if (!this.checked &amp;&amp; (!document.getElementById('checkbox_latex_structure') || !document.getElementById('checkbox_latex_structure').checked))
                        return false;
                    else return true;" />
            <label for="checkbox_latex_data">
                <?php echo $strData; ?></label>
        </legend>
        <input type="checkbox" name="latex_showcolumns" value="yes"
            id="ch_latex_showcolumns"
            <?php PMA_exportCheckboxCheck('latex_columns'); ?> />
        <label for="ch_latex_showcolumns">
            <?php echo $strColumnNames; ?></label><br />
        <table>
        <tr><td><label for="latex_data_caption">
                    <?php echo $strLatexCaption; ?></label></td>
            <td><input type="text" name="latex_data_caption" size="30"
                    value="<?php echo $strLatexContent; ?>"
                    id="latex_data_caption" />
            </td>
        </tr>
        <tr><td><label for="latex_data_continued_caption">
                    <?php echo $strLatexContinuedCaption; ?></label></td>
            <td><input type="text" name="latex_data_continued_caption" size="30"
                    value="<?php echo $strLatexContent . ' ' . $strLatexContinued; ?>"
                    id="latex_data_continued_caption" />
            </td>
        </tr>
        <tr><td><label for="latex_data_label">
                    <?php echo $strLatexLabel; ?></label></td>
            <td><input type="text" name="latex_data_label" size="30"
                    value="<?php echo $cfg['Export']['latex_data_label']; ?>"
                    id="latex_data_label" />
            </td>
        </tr>
        <tr><td><label for="latex_replace_null">
                    <?php echo $strReplaceNULLBy; ?></label></td>
            <td><input type="text" name="latex_replace_null" size="20"
                    value="<?php echo $cfg['Export']['latex_null']; ?>"
                    id="latex_replace_null" />
            </td>
        </tr>
        </table>
    </fieldset>
</fieldset>

<?php /* CSV options */ ?>
<fieldset id="csv_options">
    <input type="hidden" name="csv_data" value="csv_data" />
    <legend><?php echo $strCSVOptions; ?></legend>

    <table>
    <tr><td><label for="export_separator">
                <?php echo $strFieldsTerminatedBy; ?></label></td>
        <td><input type="text" name="export_separator" size="2"
                id="export_separator"
                value="<?php echo $cfg['Export']['csv_separator']; ?>" />
        </td>
    </tr>
    <tr><td><label for="enclosed">
                <?php echo $strFieldsEnclosedBy; ?></label></td>
        <td><input type="text" name="enclosed" size="2"
                id="enclosed"
                value="<?php echo $cfg['Export']['csv_enclosed']; ?>" />
        </td>
    </tr>
    <tr><td><label for="escaped">
                <?php echo $strFieldsEscapedBy; ?></label></td>
        <td><input type="text" name="escaped" size="2"
                id="escaped"
                value="<?php echo $cfg['Export']['csv_escaped']; ?>" />
        </td>
    </tr>
    <tr><td><label for="add_character">
                <?php echo $strLinesTerminatedBy; ?></label></td>
        <td><input type="text" name="add_character" size="2"
                id="add_character"
                value="<?php if ($cfg['Export']['csv_terminated'] == 'AUTO') echo ((PMA_whichCrlf() == "\n") ? '\n' : '\r\n'); else echo $cfg['Export']['csv_terminated']; ?>" />
        </td>
    </tr>
    <tr><td><label for="csv_replace_null">
                <?php echo $strReplaceNULLBy; ?></label></td>
        <td><input type="text" name="csv_replace_null" size="20"
                id="csv_replace_null"
                value="<?php echo $cfg['Export']['csv_null']; ?>" />
        </td>
    </tr>
    </table>
    <input type="checkbox" name="showcsvnames" value="yes"
        id="checkbox_dump_showcsvnames"
        <?php PMA_exportCheckboxCheck('csv_columns'); ?>  />
    <label for="checkbox_dump_showcsvnames">
        <?php echo $strPutColNames; ?></label>
</fieldset>

<?php /* Excel options */ ?>
<fieldset id="excel_options">
    <input type="hidden" name="excel_data" value="excel_data" />
    <legend><?php echo $strExcelOptions; ?></legend>

    <table>
    <tr><td><label for="excel_replace_null">
                <?php echo $strReplaceNULLBy; ?></label>
        </td>
        <td><input type="text" name="excel_replace_null" size="20"
                id="excel_replace_null"
                value="<?php echo $cfg['Export']['excel_null']; ?>" />
        </td>
    </tr>
    <tr><td><label for="select_excel_edition">
                <?php echo $strExcelEdition; ?>:
            </label>
        </td>
        <td><select name="excel_edition" id="select_excel_edition">
                <option value="win"<?php echo $cfg['Export']['excel_edition'] == 'win' ? ' selected="selected"' : ''; ?>>Windows</option>
                <option value="mac"<?php echo $cfg['Export']['excel_edition'] == 'mac' ? ' selected="selected"' : ''; ?>>Excel 2003 / Macintosh</option>
            </select>
        </td>
    </tr>
    </table>

    <input type="checkbox" name="showexcelnames" value="yes"
        id="checkbox_dump_showexcelnames"
        <?php PMA_exportCheckboxCheck('excel_columns'); ?> />
    <label for="checkbox_dump_showexcelnames">
        <?php echo $strPutColNames; ?></label>
</fieldset>

<?php /* HTML Excel options */ ?>
<fieldset id="htmlexcel_options">
    <input type="hidden" name="htmlexcel_data" value="htmlexcel_data" />
    <legend><?php echo $strHTMLExcelOptions; ?></legend>

    <div class="formelementrow">
        <label for="htmlexcel_replace_null"><?php echo $strReplaceNULLBy; ?></label>
        <input type="text" name="htmlexcel_replace_null" size="20"
            value="<?php echo $cfg['Export']['htmlexcel_null']; ?>"
            id="htmlexcel_replace_null" />
    </div>

    <div class="formelementrow">
        <input type="checkbox" name="htmlexcel_shownames" value="yes"
            id="checkbox_dump_htmlexcel_shownames"
            <?php PMA_exportCheckboxCheck('htmlexcel_columns'); ?> />
        <label for="checkbox_dump_htmlexcel_shownames">
            <?php echo $strPutColNames; ?></label>
    </div>
</fieldset>

<?php /* HTML Word options */ ?>
<fieldset id="htmlword_options">
    <legend><?php echo $strHTMLWordOptions; ?></legend>

    <div class="formelementrow">
        <input type="checkbox" name="htmlword_structure" value="structure"
            id="checkbox_htmlword_structure"
            <?php PMA_exportCheckboxCheck('htmlword_structure'); ?>
            onclick="
                if (!this.checked &amp;&amp; (!document.getElementById('checkbox_htmlword_data') || !document.getElementById('checkbox_htmlword_data').checked))
                    return false;
                else return true;" />
        <label for="checkbox_htmlword_structure">
            <?php echo $strStructure; ?></label>
    </div>

    <fieldset>
        <legend>
            <input type="checkbox" name="htmlword_data" value="data"
                id="checkbox_htmlword_data"
                <?php PMA_exportCheckboxCheck('htmlword_data'); ?>
                onclick="
                    if (!this.checked &amp;&amp; (!document.getElementById('checkbox_htmlword_structure') || !document.getElementById('checkbox_htmlword_structure').checked))
                        return false;
                    else return true;" />
            <label for="checkbox_htmlword_data">
                <?php echo $strData; ?></label>
        </legend>

        <div class="formelementrow">
            <label for="htmlword_replace_null">
                <?php echo $strReplaceNULLBy; ?></label>
            <input id="htmlword_replace_null" type="text" size="20"
                name="htmlword_replace_null"
                value="<?php echo $cfg['Export']['htmlword_null']; ?>" />
        </div>

        <div class="formelementrow">
            <input type="checkbox" name="htmlword_shownames" value="yes"
                id="checkbox_dump_htmlword_shownames"
                <?php PMA_exportCheckboxCheck('htmlword_columns'); ?> />
            <label for="checkbox_dump_htmlword_shownames">
                <?php echo $strPutColNames; ?></label>
        </div>
    </fieldset>
</fieldset>

<?php if ( $xls ) { /* Native Excel options */ ?>
<fieldset id="xls_options">
    <input type="hidden" name="xls_data" value="xls_data" />
    <legend><?php echo $strExcelOptions; ?></legend>

    <div class="formelementrow">
        <label for="xls_replace_null"><?php echo $strReplaceNULLBy; ?></label>
        <input type="text" name="xls_replace_null" size="20"
            value="<?php echo $cfg['Export']['xls_null']; ?>"
            id="xls_replace_null" />
    </div>

    <div class="formelementrow">
        <input type="checkbox" name="xls_shownames" value="yes"
            id="checkbox_dump_xls_shownames"
            <?php PMA_exportCheckboxCheck('xls_columns'); ?> />
        <label for="checkbox_dump_xls_shownames">
            <?php echo $strPutColNames; ?></label>
    </div>
</fieldset>
<?php } /* end if ( $xls ) */ ?>

<?php /* PDF options */ ?>
<fieldset id="pdf_options">
    <input type="hidden" name="pdf_data" value="pdf_data" />
    <legend><?php echo $strPDFOptions; ?></legend>

    <div class="formelementrow">
        <label for="pdf_report_title"><?php echo $strPDFReportTitle; ?></label>
        <input type="text" name="pdf_report_title" size="50"
            value="<?php echo $cfg['Export']['pdf_report_title']; ?>"
            id="pdf_report_title" />
    </div>
</fieldset>

<fieldset id="none_options">
    <legend><?php echo $strXML; ?></legend>
    <?php echo $strNoOptions; ?>
    <input type="hidden" name="xml_data" value="xml_data" />
</fieldset>

</td></tr></table>

<script type="text/javascript" language="javascript">
//<![CDATA[
    show_checked_option();
//]]>
</script>

<?php if ( isset($table) && strlen($table) && ! isset( $num_tables ) ) { ?>
    <div class="formelementrow">
        <?php
        echo sprintf( $strDumpXRows,
            '<input type="text" name="limit_to" size="5" value="'
            . ( isset( $unlim_num_rows ) ? $unlim_num_rows : PMA_Table::countRecords( $db, $table, TRUE ) )
            . '" onfocus="this.select()" />',
            '<input type="text" name="limit_from" value="0" size="5"'
            .' onfocus="this.select()" /> ');
        ?>
    </div>
<?php } ?>
</fieldset>

<fieldset>
    <legend>
        <input type="checkbox" name="asfile" value="sendit"
            id="checkbox_dump_asfile" <?php PMA_exportCheckboxCheck('asfile'); ?> />
        <label for="checkbox_dump_asfile"><?php echo $strSend; ?></label>
    </legend>

    <?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
    <input type="checkbox" name="onserver" value="saveit"
        id="checkbox_dump_onserver"
        onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
        <?php PMA_exportCheckboxCheck('onserver'); ?> />
    <label for="checkbox_dump_onserver">
        <?php echo sprintf($strSaveOnServer, htmlspecialchars(PMA_userDir($cfg['SaveDir']))); ?>
    </label>,<br />
    <input type="checkbox" name="onserverover" value="saveitover"
        id="checkbox_dump_onserverover"
        onclick="document.getElementById('checkbox_dump_onserver').checked = true;
            document.getElementById('checkbox_dump_asfile').checked = true;"
        <?php PMA_exportCheckboxCheck('onserver_overwrite'); ?> />
    <label for="checkbox_dump_onserverover">
        <?php echo $strOverwriteExisting; ?></label>
    <br />
    <?php } ?>

    <label for="filename_template">
        <?php echo $strFileNameTemplate; ?>
        <sup>(1)</sup></label>:
    <input type="text" name="filename_template" id="filename_template"
    <?php
        echo ' value="';
        if ($export_type == 'database') {
            if (isset($_COOKIE) && !empty($_COOKIE['pma_db_filename_template'])) {
                echo $_COOKIE['pma_db_filename_template'];
            } else {
                echo $GLOBALS['cfg']['Export']['file_template_database'];
            }
        } elseif ($export_type == 'table') {
            if (isset($_COOKIE) && !empty($_COOKIE['pma_table_filename_template'])) {
                echo $_COOKIE['pma_table_filename_template'];
            } else {
                echo $GLOBALS['cfg']['Export']['file_template_table'];
            }
        } else {
            if (isset($_COOKIE) && !empty($_COOKIE['pma_server_filename_template'])) {
                echo $_COOKIE['pma_server_filename_template'];
            } else {
                echo $GLOBALS['cfg']['Export']['file_template_server'];
            }
        }
        echo '" />';
    ?>

    (
    <input type="checkbox" name="remember_template"
        id="checkbox_remember_template"
        <?php PMA_exportCheckboxCheck('remember_file_template'); ?> />
    <label for="checkbox_remember_template">
        <?php echo $strFileNameTemplateRemember; ?></label>
    )

    <div class="formelementrow">
    <?php
    // charset of file
    if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
        echo '        <label for="select_charset_of_file">'
            . $strCharsetOfFile . '</label>' . "\n";

        $temp_charset = reset($cfg['AvailableCharsets']);
        echo '        <select id="select_charset_of_file" name="charset_of_file" size="1">' . "\n";
        foreach ($cfg['AvailableCharsets'] as $key => $temp_charset) {
            echo '            <option value="' . $temp_charset . '"';
            if ((empty($cfg['Export']['charset']) && $temp_charset == $charset)
              || $temp_charset == $cfg['Export']['charset']) {
                echo ' selected="selected"';
            }
            echo '>' . $temp_charset . '</option>' . "\n";
        } // end foreach
        echo '        </select>';
    } // end if
    ?>
    </div>

<?php
// zip, gzip and bzip2 encode features
$is_zip  = ( $cfg['ZipDump']  && @function_exists('gzcompress') );
$is_gzip = ( $cfg['GZipDump'] && @function_exists('gzencode') );
$is_bzip = ( $cfg['BZipDump'] && @function_exists('bzcompress') );

if ( $is_zip || $is_gzip || $is_bzip ) { ?>
    <div class="formelementrow">
        <?php echo $strCompression; ?>:
        <input type="radio" name="compression" value="none"
            id="radio_compression_none"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'none'); ?> />
        <label for="radio_compression_none"><?php echo $strNone; ?></label>
    <?php
    if ($is_zip) { ?>
        <input type="radio" name="compression" value="zip"
            id="radio_compression_zip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'zip'); ?> />
        <label for="radio_compression_zip"><?php echo $strZip; ?></label>
    <?php } if ($is_gzip) { ?>
        <input type="radio" name="compression" value="gzip"
            id="radio_compression_gzip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'gzip'); ?> />
        <label for="radio_compression_gzip"><?php echo $strGzip; ?></label>
    <?php } if ($is_bzip) { ?>
        <input type="radio" name="compression" value="bzip"
            id="radio_compression_bzip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'bzip2'); ?> />
        <label for="radio_compression_bzip"><?php echo $strBzip; ?></label>
    <?php } ?>
    </div>
<?php } else { ?>
    <input type="hidden" name="compression" value="none" />
<?php } ?>
</fieldset>

<?php if (function_exists('PMA_set_enc_form')) { ?>
<!-- Encoding setting form appended by Y.Kawada -->
<!-- Japanese encoding setting -->
<fieldset>
<?php echo PMA_set_enc_form('            '); ?>
</fieldset>
<?php } ?>

<fieldset class="tblFooters">
    <input type="submit" value="<?php echo $strGo; ?>" id="buttonGo" />
</fieldset>
</form>

<div class="notice">
    <sup id="FileNameTemplateHelp">(1)</sup>
    <?php
    $trans = '__SERVER__/' . $strFileNameTemplateDescriptionServer;
    if ($export_type == 'database' || $export_type == 'table') {
        $trans .= ', __DB__/' . $strFileNameTemplateDescriptionDatabase;
    }
    if ($export_type == 'table') {
        $trans .= ', __TABLE__/' . $strFileNameTemplateDescriptionTable;
    }
    echo sprintf($strFileNameTemplateDescription,
        '<a href="http://www.php.net/strftime" target="documentation" title="'
        . $strDocu . '">', '</a>', $trans); ?>
</div>
