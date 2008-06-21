<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for creating a table (if user has privileges for that)
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/check_user_privileges.lib.php';

// for MySQL >= 4.1.0, we should be able to detect if user has a CREATE
// privilege by looking at SHOW GRANTS output;
// for < 4.1.0, it could be more difficult because the logic tries to
// detect the current host and it might be expressed in many ways; also
// on a shared server, the user might be unable to define a controluser
// that has the proper rights to the "mysql" db;
// so we give up and assume that user has the right to create a table
//
// Note: in this case we could even skip the following "foreach" logic

// Addendum, 2006-01-19: ok, I give up. We got some reports about servers
// where the hostname field in mysql.user is not the same as the one
// in mysql.db for a user. In this case, SHOW GRANTS does not return
// the db-specific privileges. And probably, those users are on a shared
// server, so can't set up a control user with rights to the "mysql" db.
// We cannot reliably detect the db-specific privileges, so no more
// warnings about the lack of privileges for CREATE TABLE. Tested
// on MySQL 5.0.18.

$is_create_table_priv = true;

/*
if (PMA_MYSQL_INT_VERSION >= 40100) {
    $is_create_table_priv = false;
} else {
    $is_create_table_priv = true;
}

foreach ($dbs_where_create_table_allowed as $allowed_db) {

    // if we find the exact db name, we stop here
    if ($allowed_db == $db) {
        $is_create_table_priv = TRUE;
        break;
    }

    // '*' indicates a global CREATE priv
    if ($allowed_db == '*') {
        $is_create_table_priv = TRUE;
        break;
    }

    if (ereg('%|_', $allowed_db)) {
        // take care of wildcards and escaped wildcards,
        // transforming them into regexp patterns
        $max_position = strlen($allowed_db) - 1;
        $i = 0;
        $pattern = '';
        while ($i <= $max_position) {
            if ($allowed_db[$i] == '\\'){
                if ($i < $max_position - 1 && $allowed_db[$i+1] == '_'){
                    $chunk = '_';
                    $i++;
                } elseif ($i < $max_position - 1 && $allowed_db[$i+1] == '%'){
                    $chunk = '%';
                    $i++;
                } else {
                    $chunk = $allowed_db[$i];
                }
            } elseif ($allowed_db[$i] == '_'){
                $chunk = '.';
            } elseif ($allowed_db[$i] == '%'){
                $chunk = '(.)*';
            } else {
                $chunk = $allowed_db[$i];
            }
            $pattern .= $chunk;
            $i++;
        } // end while
        unset($i, $max_position, $chunk);

        $matches = '';
        if (preg_match('@' .$pattern . '@i', $db, $matches)) {
            if ($matches[0] == $db) {
                $is_create_table_priv = TRUE;
                break;
                //TODO: maybe receive in $allowed_db also the db names
                // on which we cannot CREATE, and check them
                // in this foreach, because if a user is allowed to CREATE
                // on db foo% but forbidden on db foobar, he should not
                // see the Create table dialog
            }
        }
    }
} // end foreach
unset($i, $max_position, $chunk, $pattern);
*/
?>
<form method="post" action="tbl_create.php"
    onsubmit="return (emptyFormElements(this, 'table') &amp;&amp; checkFormElementInRange(this, 'num_fields', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidFieldCount']); ?>', 1))">
<fieldset>
    <legend>
<?php
if ($GLOBALS['cfg']['PropertiesIconic']) {
    echo '<img class="icon" src="' . $pmaThemeImage . 'b_newtbl.png" width="16" height="16" alt="" />';
}
echo sprintf($strCreateNewTable, PMA_getDbLink());
?>
    </legend>
<?php if ($is_create_table_priv) { ?>
    <?php echo PMA_generate_common_hidden_inputs($db); ?>
    <div class="formelement">
        <?php echo $strName; ?>:
        <input type="text" name="table" maxlength="64" size="30" />
    </div>
    <div class="formelement">
        <?php echo $strNumberOfFields; ?>:
        <input type="text" name="num_fields" size="2" />
    </div>
    <div class="clearfloat"></div>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" value="<?php echo $strGo; ?>" />
<?php } else { ?>
    <div class="error"><?php echo $strNoPrivileges; ?></div>
<?php } // end if else ?>
</fieldset>
</form>
