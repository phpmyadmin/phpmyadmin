<?php 
    require_once './libraries/common.inc.php';
    
require_once './libraries/header_http.inc.php';
require_once './libraries/header_meta_style.inc.php';
?>

</head>

<body>
    <form action="enum_editor.php" method="get">
        <div id="enum_editor_no_js">
            <h3>Values for the column "<?php echo $_GET['field']; ?>"</h3>
            <p>Enter each value in a separate field.</p>
            <div id="values">
            <?php
                // Get the new values from the submitted form or the old ones from tbl_alter.php
                $values = '';
                for($i = 1; $i <= $_GET['num_fields']; $i++) {
                    $input_name = "field" . $i;
                    $values .= $_GET[$input_name] . ',';
                }
                if (isset($_GET['values'])) {
                    $values = urldecode($_GET['values']);
                }
                // Display the input fields containing each of the values, removing the empty ones
                $field_counter = 0;
                $stripped_values = array();
                foreach(split(",", $values) as $value) {
                    if(trim($value) != "") {
                        $field_counter++;
                        echo '<input type="text" size="30" value=' . $value . ' name="field' . $field_counter . '" />';
                        $stripped_values[] = $value;
                    }
                }
                // If extra fields are added, display them
                if($_GET['add_extra_fields']) {
                    $extra_fields = $_GET['extra_fields'];
                    $total_fields = $extra_fields + $field_counter;
                    for($i = ($field_counter+1); $i <= $total_fields; $i++) {
                        echo '<input type="text" size="30" name="field' . $i . '"/>';
                    }
                } else {
                    $total_fields = $field_counter;
                }
            ?>
            </div>
            <p>
                <input type="checkbox" name="add_extra_fields"> Add <input type="text" value="1" name="extra_fields" size="2"/> more values
            </p>
             <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>" />
             <input type="hidden" name="num_fields" value="<?php echo $total_fields; ?>" />
             <input type="hidden" name="field" value="<?php echo $_GET['field']; ?>" />
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