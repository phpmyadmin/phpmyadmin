<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for creating a table (if user has privileges for that)
 *
 * for MySQL >= 4.1.0, we should be able to detect if user has a CREATE
 * privilege by looking at SHOW GRANTS output;
 * for < 4.1.0, it could be more difficult because the logic tries to
 * detect the current host and it might be expressed in many ways; also
 * on a shared server, the user might be unable to define a controluser
 * that has the proper rights to the "mysql" db;
 * so we give up and assume that user has the right to create a table
 *
 * Note: in this case we could even skip the following "foreach" logic
 *
 * Addendum, 2006-01-19: ok, I give up. We got some reports about servers
 * where the hostname field in mysql.user is not the same as the one
 * in mysql.db for a user. In this case, SHOW GRANTS does not return
 * the db-specific privileges. And probably, those users are on a shared
 * server, so can't set up a control user with rights to the "mysql" db.
 * We cannot reliably detect the db-specific privileges, so no more
 * warnings about the lack of privileges for CREATE TABLE. Tested
 * on MySQL 5.0.18.
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

$is_create_table_priv = true;

?>
    <form id="create_table_form_minimal" method="post" action="tbl_create.php">
<fieldset>
    <legend>
<?php
if (in_array(
        $GLOBALS['cfg']['ActionLinksMode'],
        array('icons', 'both')
    )
) {
    echo PMA_Util::getImage('b_newtbl.png');
}
echo __('Create table');
?>
    </legend>
    <?php echo PMA_generate_common_hidden_inputs($db); ?>
    <div class="formelement">
        <?php echo __('Name'); ?>:
        <input type="text" name="table" maxlength="64" size="30" />
    </div>
    <div class="formelement">
        <?php echo __('Number of columns'); ?>:
        <input type="text" name="num_fields" size="2" />
    </div>
    <div class="clearfloat"></div>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>
