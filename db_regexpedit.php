<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles regular expression replace for table names
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.lib.php';

if (! defined('PHPMYADMIN')) {
    exit;
}

include_once './libraries/header.inc.php';

$post_params = array(
    'perform',
    'regexp',
    'replacement',
    'db'
);

foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

if (!empty($perform) && $perform = "true") {

    // Get table names that match regexp
    $tables = PMA_DBI_fetch_result('SHOW TABLES FROM '.$db.' WHERE Tables_in_'.$db.' REGEXP \''.$regexp.'\';');

    if (count($tables) != 0) {

        ?>
        <form action="import.php" method="post">

            <fieldset class="input">
                <legend><?php echo __('Result of the action:'); ?></legend>                        
                <table>
                    <?php

                    // Initialize query
                    $query = "RENAME TABLE ";

                    // For each table, call preg_replace on table name and create updating query
                    foreach ($tables as $old_name) {
                        $new_name = preg_replace("/".$regexp."/", $replacement, $old_name);

                        $query .= $old_name." to ".$new_name.", ";
                 
                        ?>

                        <tr><td><b><?php echo $old_name; ?></b></td><td> will be changed to </td><td><b><?php echo $new_name; ?></b></td></tr>

                        <?php

                    }

                    // Remove last ", " from query and add ; at the end
                    $pos = strrpos($query, ", ");

                    if ($pos !== false) {
                        $query = substr_replace($query, ";", $pos, 2);
                    }
                    
                    ?>
                </table>
            
            </fieldset>

            <input type="hidden" name="import_type" value="query" />
            <input type="hidden" name="format" value="sql" />
            <input type="hidden" name="sql_query" value="<?php echo htmlspecialchars($query); ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />

            <?php
                echo PMA_generate_common_hidden_inputs($_url_params);
            ?>

            <fieldset class="tblFooters">
                <button type="submit" name="mult_btn" value="<?php echo __('Confirm'); ?>" id="buttonYes"><?php echo __('Confirm'); ?></button>
            </fieldset>
        </form>

        <?php  
    } else {

        $message = PMA_Message::notice(__('No tables match provided expression.'));
        $message->display();

    }

} else {

    ?>
    <form action="db_regexpedit.php" method="post">
        <?php
            echo PMA_generate_common_hidden_inputs($_url_params);
        ?>    
        <input type="hidden" name="db" value="<?php echo $db; ?>" />
        <input type="hidden" name="perform" value="true" />
        <fieldset class = "input">
                    <legend><?php echo __('Change table names by regular expression'); ?></legend>
                    <table>
                    <tr>
                    <td><?php echo __('Regular expression - pattern'); ?></td><td><input type="text" name="regexp" id="regexp" /></td>
                    </tr>
                    <tr>
                    <td><?php echo __('Replacement'); ?> </td><td><input type="text" name="replacement" id="replacement" /></td>
                    </tr>
                    </table>
            </fieldset>
            <fieldset class="tblFooters">
                    <button type="submit" name="mult_btn" value="<?php echo __('Yes'); ?>" id="buttonYes"><?php echo __('Submit'); ?></button>
            </fieldset>
    </form>

    <?php
}

/**
 * Displays the footer
 */
require 'libraries/footer.inc.php';
?>
