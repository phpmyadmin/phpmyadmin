<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays a form for editing ENUM and SET values with more space (as an alternative to doing it in tbl_alter.php)
 * This form is only for users with JavaScript disabled -- users with JavaScript enabled will see a different form
 * defined in tbl_properties.inc.php
 * @package phpMyAdmin
 */

require_once './libraries/common.inc.php';
require_once './libraries/header_http.inc.php';
require_once './libraries/header_meta_style.inc.php';
?>

</head>

<body>
    <form action="enum_editor.php" method="get">
        <div id="enum_editor_no_js">
            <h3><?php printf(__('Values for the column "%s"'), htmlspecialchars($_GET['field'])); ?></h3>
            <p><?php echo __('Enter each value in a separate field.'); ?></p>
            <div id="values">
            <?php
                $values = array();
                if (isset($_GET['values'])) { // This page was displayed when the "add a new value" link or the link in tbl_alter.php was clicked
                    $values = explode(',', urldecode($_GET['values']));
                } elseif (isset($_GET['num_fields'])) { // This page was displayed from submitting this form
                    for($field_num = 1; $field_num <= $_GET['num_fields']; $field_num++) {
                        $values[] = "'" . str_replace(array("'", '\\'), array("''", '\\\\'), $_GET['field' . $field_num]) . "'";
                    }
                }
                // Display the values in text fields, excluding empty strings
                $field_counter = 0;
                foreach($values as $value) {
                    if(trim($value) != "") {
                        $field_counter++;
                        echo sprintf('<input type="text" size="30" value="%s" name="field' . $field_counter . '" />', htmlspecialchars(str_replace(array("''", '\\\\', "\\'"), array("'", '\\', "'"), substr($value, 1, -1))));
                    }
                }

                $total_fields = $field_counter;
                // If extra empty fields are added, display them
                if(isset($_GET['extra_fields'])) {
                    $total_fields += $_GET['extra_fields'];
                    for($i = $field_counter+1; $i <= $total_fields; $i++) {
                        echo '<input type="text" size="30" name="field' . $i . '"/>';
                    }
                } else {
                    $_GET['extra_fields'] = 0;
                }

            ?>
            </div>
            <p>
               <a href="enum_editor.php<?php echo PMA_generate_common_url(array('field' => $_GET['field'], 'extra_fields' => $_GET['extra_fields'] + 1, 'values' => join(',', $values))); ?>">
<?php echo __('+ Restart insertion and add a new value'); ?>
               </a>
            </p>
            <?php echo PMA_generate_common_hidden_inputs(); ?>
             <input type="hidden" name="field" value="<?php echo htmlspecialchars($_GET['field']); ?>" />
             <input type="hidden" name="num_fields" value="<?php echo $total_fields; ?>" />
             <input type="submit" value="<?php echo __('Go'); ?>" />
        </form>

        <div id="enum_editor_output">
        <h3><?php echo __('Output'); ?></h3>
        <p><?php echo __('Copy and paste the joined values into the "Length/Values" field'); ?></p>
            <textarea id="joined_values" cols="95" rows="5"><?php echo htmlspecialchars(join(",", $values)); ?></textarea>
        </div>
    </div>
</body>
</html>
