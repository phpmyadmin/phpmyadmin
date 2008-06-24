<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Runs common work
 */
require './libraries/db_common.inc.php';
$url_params['goto'] = $url_params['back'] = 'view_create.php';

if (isset($_POST['submitoptions'])) {
    /**
     * Creates the view
     */
    $message = '';
    $sep = "\r\n";
    $create_query = 'CREATE' . $sep;
    if (isset($_POST['or_replace'])) {
        $create_query .= ' OR REPLACE' . $sep;
    }
    if (isset($_POST['algorithm'])) {
        $create_query .= ' ALGORITHM = ' . $_POST['algorithm'] . $sep;
    }
    $create_query .= ' VIEW ' . PMA_backquote($_POST['view_name']) . $sep;

    if (!empty($_POST['column_names'])) {
        $create_query .= ' (' . $_POST['column_names'] . ')' . $sep;
    }

    $create_query .= ' AS ' . $_POST['sql_statement'] . $sep;

    if (isset($_POST['cascaded']) || isset($_POST['local']) || isset($_POST['check_option'])) {
        $create_query .= ' WITH ';
    }

    if (isset($_POST['cascaded'])) {
        $create_query .= ' CASCADED ';
    }

    if (isset($_POST['local'])) {
        $create_query .= ' LOCAL ';
    }

    if (isset($_POST['check_option'])) {
        $create_query .= ' CHECK OPTION ';
    }

    $message        .= PMA_DBI_query($create_query) ? $strSuccess : $strError;

    // to display the CREATE VIEW query
    $sql_query = $create_query;

    require './' .  $cfg['DefaultTabDatabase'];
    exit();

} else {
    /**
     * Displays top menu links
     * We use db links because a VIEW is not necessarily on a single table
     */
    $num_tables = 0;
    require_once './libraries/db_links.inc.php';

    $url_params['goto'] = 'view_create.php';
    $url_params['back'] = 'view_create.php';

    /**
     * Displays the page
     *
     * @todo js error when view name is empty (strFormEmpty)
     * @todo (also validate if js is disabled, after form submission?)
     */

?>
<!-- CREATE VIEW options -->
<div id="div_view_options">
<form method="post" action="view_create.php">
<?php echo PMA_generate_common_hidden_inputs($GLOBALS['db']); ?>
<input type="hidden" name="reload" value="1" />
<fieldset>
    <legend>CREATE VIEW</legend>

    <table>
    <tr><td><label for="or_replace">OR REPLACE</label></td>
        <td><input type="checkbox" name="or_replace" id="or_replace"
                value="1" />
        </td>
    </tr>
    <tr>
        <td><label for="algorithm">ALGORITHM</label></td>
        <td><select name="algorithm" id="algorithm">
                <option value="UNDEFINED">UNDEFINED</option>
                <option value="MERGE">MERGE</option>
                <option value="TEMPTABLE">TEMPTABLE</option>
            </select>
        </td>
    </tr>
    <tr><td><?php echo $strViewName; ?></td>
        <td><input type="text" size="20" name="view_name" onfocus="this.select()"
                value="" />
        </td>
    </tr>

    <tr><td><?php echo $strColumnNames; ?></td>
        <td><input type="text" maxlength="100" size="50" name="column_names" onfocus="this.select()"
                value="" />
        </td>
    </tr>

    <tr><td><?php echo 'AS' ?></td>
        <td>
            <textarea name="sql_statement" rows="<?php echo $cfg['TextareaRows']; ?>" cols="<?php echo $cfg['TextareaCols']; ?>" dir="<?php echo $text_dir; ?>" onfocus="this.select();"><?php echo htmlspecialchars($sql_query); ?></textarea> 
        </td>
    </tr>
    <tr><td>WITH</td>
        <td>
            <input type="checkbox" name="cascaded" id="cascaded" value="1" />
            <label for="cascaded">CASCADED</label>
            <input type="checkbox" name="local" id="local" value="1" />
            <label for="local">LOCAL</label>
            <input type="checkbox" name="check_option" id="check_option" value="1" />
            <label for="check_option">CHECK OPTION</label>
        </td>
    </tr>
    </table>
</fieldset>
<fieldset class="tblFooters">
        <input type="submit" name="submitoptions" value="<?php echo $strGo; ?>" />
</fieldset>
</form>
</div>
<?php
/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';

} // end if
?>
