<?php 
    require_once './libraries/common.inc.php';
    
require_once './libraries/header_http.inc.php';
require_once './libraries/header_meta_style.inc.php';
?>

</head>

<body>
    <form action="enum_editor.php" method="get">
        <div id="enum_editor_no_js">
            <h3><?php echo __('Values for the column "' . urldecode($_GET['field']) . '"'); ?></h3>
            <p><?php echo __('Enter each value in a separate field, enclosed in single quotes. If you ever need to put a backslash ("\") or a single quote ("\'") amongst those values, precede it with a backslash (for example \'\\\\xyz\' or \'a\\\'b\').'); ?></p>
            <div id="values">
            <?php
                $values = '';
                if (isset($_GET['values'])) {
                    $values = urldecode($_GET['values']);
                } elseif (isset($_GET['num_fields'])) {
                    for($field_num = 1; $field_num <= $_GET['num_fields']; $field_num++) {
                        $values .= $_GET['field' . $field_num] . ",";
                    }
                }

                $field_counter = 0;
                $stripped_values = array();
                foreach(split(",", $values) as $value) {
                    if(trim($value) != "") {
                        $field_counter++;
                        echo sprintf('<input type="text" size="30" value="%s" name="field' . $field_counter . '" />', htmlspecialchars($value));
                        $stripped_values[] = htmlspecialchars($value);
                    }
                }

                $total_fields = $field_counter;
                // If extra fields are added, display them
                if(isset($_GET['extra_fields'])) {
                    $total_fields += $_GET['extra_fields'];
                    for($i = $field_counter+1; $i <= $total_fields; $i++) {
                        echo '<input type="text" size="30" name="field' . $i . '"/>';
                    }
                }

            ?>
            </div>
            <p>
               <a href="enum_editor.php?token=<?php echo urlencode($_GET['token']); ?>&field=<?php echo urlencode($_GET['field']); ?>&extra_fields=<?php echo $_GET['extra_fields'] + 1; ?>&values=<?php echo $values; ?>">
                   + Restart insertion and add a new value
               </a>
            </p>
             <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>" />
             <input type="hidden" name="field" value="<?php echo $_GET['field']; ?>" />
             <input type="hidden" name="num_fields" value="<?php echo $total_fields; ?>" />
            <input type="submit" value="Go" />
        </form>

        <div id="enum_editor_output">
            <h3>Output</h3>
            <p>Copy and paste the joined values into the "Length/Values" field</p>
            <textarea id="joined_values" cols="95" rows="5"><?php echo join(",", $stripped_values); ?></textarea>
        </div>
    </div>
</body>
</html>