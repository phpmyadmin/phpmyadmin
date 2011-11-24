<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin-Designer
 */

require_once './libraries/pmd_common.php';

/**
 * If called directly from the designer, first save the positions
 */
if (! isset($scale)) {
    $no_die_save_pos = 1;
    include_once 'pmd_save_pos.php';
}

if (isset($mode)) {
    if ('create_export' != $mode && empty($pdf_page_number)) {
        die("<script>alert('Pages not found!');history.go(-2);</script>");
    }

    $pmd_table = PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['designer_coords']);
    $pma_table = PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']);
    $scale_q = PMA_sqlAddSlashes($scale);

    if ('create_export' == $mode) {
        $pdf_page_number = PMA_REL_create_page($newpage, $cfgRelation, $db);
        if ($pdf_page_number > 0) {
            $message = PMA_Message::success(__('Page has been created'));
            $mode = 'export';
        } else {
            $message = PMA_Message::error(__('Page creation failed'));
        }
    }

    $pdf_page_number_q = PMA_sqlAddSlashes($pdf_page_number);

    if ('export' == $mode) {
        $sql = "REPLACE INTO " . $pma_table . " (db_name, table_name, pdf_page_number, x, y) SELECT db_name, table_name, " . $pdf_page_number_q . ", ROUND(x/" . $scale_q . ") , ROUND(y/" . $scale_q . ") y FROM " . $pmd_table . " WHERE db_name = '" . PMA_sqlAddSlashes($db) . "'";

        PMA_query_as_controluser($sql, true, PMA_DBI_QUERY_STORE);
    }

    if ('import' == $mode) {
        PMA_query_as_controluser(
        'UPDATE ' . $pma_table . ',' . $pmd_table .
        ' SET ' . $pmd_table . '.`x`= ' . $pma_table . '.`x` * '. $scale_q . ',
        ' . $pmd_table . '.`y`= ' . $pma_table . '.`y` * '. $scale_q .'
        WHERE
        ' . $pmd_table . '.`db_name`=' . $pma_table . '.`db_name`
        AND
        ' . $pmd_table . '.`table_name` = ' . $pma_table . '.`table_name`
        AND
        ' . $pmd_table . '.`db_name`=\''. PMA_sqlAddSlashes($db) .'\'
        AND pdf_page_number = ' . $pdf_page_number_q . ';', true, PMA_DBI_QUERY_STORE);
    }
}

// no need to use pmd/styles
require_once './libraries/header_meta_style.inc.php';
?>
</head>
<body>
<br>
<div>
<?php
    if (!empty($message)) {
        $message->display();
    }
?>
  <form name="form1" method="post" action="pmd_pdf.php">
<?php
echo PMA_generate_common_hidden_inputs($db);
echo '<div>';
echo '<fieldset><legend>' . __('Import/Export coordinates for PDF schema') . '</legend>';

$choices = array();

$table_info_result = PMA_query_as_controluser('SELECT * FROM '
            . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
            . ' WHERE db_name = \'' . PMA_sqlAddSlashes($db) . '\'');

if (PMA_DBI_num_rows($table_info_result) > 0) {
    echo '<p>' . __('Page') . ':';
    echo '<select name="pdf_page_number">';

    while ($page = PMA_DBI_fetch_assoc($table_info_result)) {
        echo '<option value="' . $page['page_nr'] . '">';
        echo htmlspecialchars($page['page_descr']);
        echo '</option>';
    }
    echo '</select>';
    echo '</p>';
    $choices['import'] = __('Import from selected page');
    $choices['export'] = __('Export to selected page');
}
$choices['create_export'] = __('Create a page and export to it');

if (1 == count($choices)) {
    echo $choices['create_export'];
    echo '<input type="hidden" name="mode" value="create_export" />';
} else {
    PMA_display_html_radio('mode', $choices, $checked_choice = '', $line_break = true, $escape_label = false, $class = '');
}
echo '<br />';
echo '<label for="newpage">' . __('New page name: ') . '</label>';
echo '<input id="newpage" type="text" name="newpage" />';

echo '<p>' . __('Export/Import to scale') . ':';
?>
      <select name="scale">
        <option value="1">1:1</option>
        <option value="2">1:2</option>
        <option value="3" selected>1:3 (<?php echo __('recommended'); ?>)</option>
        <option value="4">1:4</option>
        <option value="5">1:5</option>
        </select>
      </p>
      <input type="submit" value="<?php echo __('Go'); ?>">
    </fieldset>
    </div>
  </form>
</div>
</body>
</html>

