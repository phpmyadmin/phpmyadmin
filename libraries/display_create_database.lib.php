<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for creating database (if user has privileges for that)
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/check_user_privileges.lib.php';

if ($is_create_db_priv) {
    // The user is allowed to create a db
    ?>
        <form method="post" action="db_create.php" id="create_database_form" class="ajax"><strong>
            <?php echo '<label for="text_create_db">' 
                       . PMA_Util::getImage('b_newdb.png')
                       . " " . __('Create database')
                       . '</label>&nbsp;'
                       . PMA_Util::showMySQLDocu('SQL-Syntax', 'CREATE_DATABASE'); ?></strong><br />
            <?php echo PMA_generate_common_hidden_inputs('', '', 5); ?>
            <input type="hidden" name="reload" value="1" />
            <input type="text" name="new_db" value="<?php echo $db_to_create; ?>" maxlength="64" class="textfield" id="text_create_db"/>
    <?php
    include_once './libraries/mysql_charsets.lib.php';
    echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_COLLATION, 'db_collation', null, null, true, 5);

    if (! empty($dbstats)) {
        echo '<input type="hidden" name="dbstats" value="1" />';
    }
    ?>
            <input type="submit" value="<?php echo __('Create'); ?>" id="buttonGo" />
        </form>
    <?php
} else {
    ?>
    <!-- db creation no privileges message -->
        <strong><?php echo __('Create database') . ':&nbsp;' . PMA_Util::showMySQLDocu('SQL-Syntax', 'CREATE_DATABASE'); ?></strong><br />
        <?php
              echo '<span class="noPrivileges">'
                 . PMA_Util::getImage('s_error2.png', '', array('hspace' => 2, 'border' => 0, 'align' => 'middle'))
                 . '' . __('No Privileges') .'</span>';
} // end create db form or message
?>
