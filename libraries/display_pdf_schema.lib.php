<!-- PDF schema -->
<form method="post" action="pdf_schema.php">
<fieldset>
    <legend>
    <?php
    echo PMA_generate_common_hidden_inputs($db);
    if ($cfg['PropertiesIconic']) {
        echo '<img class="icon" src="' . $pmaThemeImage . 'b_view.png"'
            .' alt="" width="16" height="16" />';
    }
    echo __('Display PDF schema');
    ?>:
    </legend>
    <?php
    if (isset($test_rs)) {
    ?>
    <label for="pdf_page_number_opt"><?php echo __('Page number:'); ?></label>
    <select name="pdf_page_number" id="pdf_page_number_opt">
    <?php
    while ($pages = @PMA_DBI_fetch_assoc($test_rs)) {
        echo '                <option value="' . $pages['page_nr'] . '">'
            . $pages['page_nr'] . ': ' . htmlspecialchars($pages['page_descr']) . '</option>' . "\n";
    } // end while
    PMA_DBI_free_result($test_rs);
    unset($test_rs);
    ?>
    </select><br />
    <?php } else { ?>
    <input type="hidden" name="pdf_page_number" value="<?php echo htmlspecialchars($chpage); ?>" />
    <?php } ?>

    <input type="checkbox" name="show_grid" id="show_grid_opt" />
    <label for="show_grid_opt"><?php echo __('Show grid'); ?></label><br />
    <input type="checkbox" name="show_color" id="show_color_opt"
        checked="checked" />
    <label for="show_color_opt"><?php echo __('Show color'); ?></label><br />
    <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" />
    <label for="show_table_dim_opt"><?php echo __('Show dimension of tables'); ?>
        </label><br />
    <input type="checkbox" name="all_tab_same_wide" id="all_tab_same_wide" />
    <label for="all_tab_same_wide"><?php echo __('Display all tables with the same width'); ?>
        </label><br />
    <input type="checkbox" name="with_doc" id="with_doc" checked="checked" />
    <label for="with_doc"><?php echo __('Data Dictionary'); ?></label><br />
    <input type="checkbox" name="show_keys" id="show_keys" />
    <label for="show_keys"><?php echo __('Only show keys'); ?></label><br />

    <label for="orientation_opt"><?php echo __('Data Dictionary Format'); ?></label>
    <select name="orientation" id="orientation_opt">
        <option value="L"><?php echo __('Landscape');?></option>
        <option value="P"><?php echo __('Portrait');?></option>
    </select><br />

    <label for="paper_opt"><?php echo __('Paper size'); ?></label>
    <select name="paper" id="paper_opt">
    <?php
        foreach ($cfg['PDFPageSizes'] AS $key => $val) {
            echo '<option value="' . $val . '"';
            if ($val == $cfg['PDFDefaultPageSize']) {
                echo ' selected="selected"';
            }
            echo ' >' . $val . '</option>' . "\n";
        }
    ?>
    </select>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>

