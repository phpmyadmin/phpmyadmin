<?php
/* $Id$ */


/**
 * Confirmation form
 */
if (!empty($submit_mult)
    && (!empty($selected_db) || !empty($selected_tbl) || !empty($selected_fld))) {

    if (get_magic_quotes_gpc()) {
        $submit_mult = stripslashes($submit_mult);
    }
    if (!empty($selected_db)) {
        $what     = 'drop_db';
        $selected = $selected_db;
    } else if (!empty($selected_tbl)) {
        $what     = (($submit_mult == $strDrop) ? 'drop_tbl' : 'empty_tbl');
        $selected = $selected_tbl;
    } else {
        $what     = 'drop_fld';
        $selected = $selected_fld;
    }

    // Builds the query
    $full_query     = '';
    $selected_cnt   = count($selected);
    for ($i = 0; $i < $selected_cnt; $i++) {
        switch ($what) {
            case 'drop_db':
                $full_query .= 'DROP DATABASE '
                            . backquote(htmlspecialchars(urldecode($selected[$i])))
                            . ';<br />';
                break;

            case 'drop_tbl':
                $full_query .= 'DROP TABLE '
                            . backquote(htmlspecialchars(urldecode($selected[$i])))
                            . ';<br />';
                break;

            case 'empty_tbl':
                $full_query .= 'DELETE FROM '
                            . backquote(htmlspecialchars(urldecode($selected[$i])))
                            . ';<br />';
                break;

            case 'drop_fld':
                $full_query .= 'ALTER TABLE '
                            . backquote(htmlspecialchars($table))
                            . ' DROP '
                            . backquote(htmlspecialchars(urldecode($selected[$i])))
                            . ';<br />';
                break;
        } // end switch
    }

    // Displays the form
    echo $strDoYouReally . '&nbsp;:<br />' . "\n";
    echo '<tt>' . $full_query . '</tt>&nbsp;?<br/>' . "\n";
    ?>
<form action="<?php echo $action; ?>" method="post">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <?php
    echo "\n";
    if ($action == 'db_details.php3') {
        echo '    <input type="hidden" name="db" value="' . $db . '" />' . "\n";
    } else if ($action == 'tbl_properties.php3') {
        echo '    <input type="hidden" name="db" value="' . $db . '" />' . "\n";
        echo '    <input type="hidden" name="table" value="' . $table . '" />' . "\n";
    }
    for ($i = 0; $i < $selected_cnt; $i++) {
        echo '    <input type="hidden" name="selected[]" value="' . $selected[$i] . '" />' . "\n";
    }
    ?>
    <input type="hidden" name="query_type" value="<?php echo $what; ?>" />
    <input type="submit" name="btnDrop" value="<?php echo $strYes; ?>" />
    <input type="submit" name="btnDrop" value="<?php echo $strNo; ?>" />
</form>
    <?php
    echo"\n";

    include('./footer.inc.php3');
    exit();
}

/**
 * Executes the query
 */
else if ((get_magic_quotes_gpc() && stripslashes($btnDrop) == $strYes)
         || $btnDrop == $strYes) {

    $sql_query      = '';
    $selected_cnt   = count($selected);
    for ($i = 0; $i < $selected_cnt; $i++) {
        switch ($query_type) {
            case 'drop_db':
                $a_query = 'DROP DATABASE '
                         . backquote(urldecode($selected[$i]));
                $reload  = 'true';
                break;

            case 'drop_tbl':
                $a_query = 'DROP TABLE '
                         . backquote(urldecode($selected[$i]));
                $reload  = 'true';
                break;

            case 'empty_tbl':
                $a_query = 'DELETE FROM '
                         . backquote(urldecode($selected[$i]));
                break;

            case 'drop_fld':
                $a_query = 'ALTER TABLE '
                         . backquote($table)
                         . ' DROP '
                         . backquote(urldecode($selected[$i]));
                break;
        } // end switch
        $sql_query .= $a_query . ';' . "\n";

        if ($query_type != 'drop_db') {
            mysql_select_db($db);
        }
        $result = @mysql_query($a_query) or mysql_die('', $a_query, FALSE);
    } // end for

    show_message($strSuccess);
}

?>
