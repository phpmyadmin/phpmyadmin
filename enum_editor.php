<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays a form for editing ENUM and SET values with more
 * space (as an alternative to doing it in tbl_alter.php).
 * This form is only for users with JavaScript disabled,
 * users with JavaScript enabled will see a jQuery dialog.
 *
 * @package PhpMyAdmin
 */

require_once './libraries/common.inc.php';
require_once './libraries/header_http.inc.php';
require_once './libraries/header_meta_style.inc.php';
?>
</head>
<body>
    <form action="enum_editor.php" method="get">
        <?php echo PMA_generate_common_hidden_inputs(); ?>
        <input type="hidden" name="field" value="<?php echo htmlspecialchars($_GET['field']); ?>" />
        <fieldset class="enum_editor_no_js">
        <legend><?php echo __('ENUM/SET editor'); ?></legend>
        <div class="enum_editor_no_js">
            <h3>
            <?php
                if (empty($_GET['field'])) {
                    echo __('Values for a new column');
                } else {
                    printf(__('Values for column %s'), '"' . htmlspecialchars($_GET['field']) . '"');
                }
            ?>
            </h3>
            <p><?php echo PMA_getImage('s_info.png') . __('Enter each value in a separate field'); ?></p>
            <table id="values">
            <?php
                // Get the enum values
                $values = array();
                // If the values are in an array
                if (isset($_GET['values']) && is_array($_GET['values'])) {
                     // then this page was called from itself via the "Add a value", "Drop" or "Go" buttons
                    $values = $_GET['values'];
                    foreach ($values as $key => $value) {
                        $values[$key] = htmlentities($value);
                    }
                } elseif (isset($_GET['values']) && is_string($_GET['values'])) {
                    // Parse the values from a string
                    $values = PMA_parseEnumSetValues($_GET['values']);
                }
                // Escape double quotes
                foreach ($values as $key => $value) {
                    $values[$key] = str_replace('"', "&quote;", $value);
                }
                // If there are no values, maybe the user is about to make a
                // new list so we add a few for him/her to get started with.
                if (! count($values)
                    || (count($values) == 1 && strlen($values[0]) == 0)
                ) {
                    array_push($values, '', '', '');
                }
                // Add an empty value, if there was a request to do so
                if (! empty($_GET['add_field'])) {
                    $values[] = '';
                }
                // Remove a value, given a valid index, from the list
                // of values, if there was a request to do so.
                if (isset($_GET['drop']) && is_array($_GET['drop'])) {
                    foreach ($_GET['drop'] as $index => $value) {
                        if ((int)$index == $index
                            && $index > 0
                            && $index <= count($values)
                        ) {
                            unset($values[$index]);
                        }
                    }
                }
                // Display the values in text fields
                $field_counter = 0;
                foreach ($values as $value) {
                    $field_counter++;
                    echo sprintf(
                        '<tr><td><input class="text" type="text" size="30" value="%s" name="values[' . $field_counter . ']" />' . "\n",
                        $value
                    );
                    echo '</td><td>';
                    echo '<input class="drop" type="submit" value="' . __('Drop') . '" name="drop[' . $field_counter . ']" />' . "\n";
                    echo '</td></tr>' . "\n";
                }
            ?>
                <tr><td>
                    <input type="submit" class="submit" value="<?php echo __('Go'); ?>" />
                </td><td>
                    <input type="submit" class="submit" name="add_field" value="<?php echo __('Add a value'); ?>" />
                </td></tr>
            </table>
        </div>
        <hr class='enum_editor_no_js' />
        <div id="enum_editor_output">
        <h3><?php echo __('Output'); ?></h3>
        <p><?php echo PMA_getImage('s_info.png') . __('Copy and paste the joined values into the "Length/Values" field'); ?></p>
            <?php
                // Escape quotes and slashes for usage with MySQL
                foreach ($values as $key => $value) {
                    $values[$key]  = "'";
                    $values[$key] .= str_replace(
                        array("'", "\\", "&#39;", "&#92;"),
                        array("''", '\\\\', "''", '\\\\'),
                        $value
                    );
                    $values[$key] .= "'";
                }
                // Print out the values as a string
            ?>
            <textarea id="joined_values" cols="95" rows="5"><?php echo join(",", $values); ?></textarea>
        </div>
        </fieldset>
    </form>
</body>
</html>
