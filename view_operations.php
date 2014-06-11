<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * View manipulations
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

$pma_table = new PMA_Table($GLOBALS['table'], $GLOBALS['db']);

/**
 * functions implementation for this script
 */
require_once 'libraries/operations.lib.php';

/**
 * Runs common work
 */
require './libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=view_operations.php&amp;back=view_operations.php';
$url_params['goto'] = $url_params['back'] = 'view_operations.php';

/**
 * Gets tables informations
 */

require './libraries/tbl_info.inc.php';
$reread_info = false;

/**
 * Updates if required
 */
if (isset($_REQUEST['submitoptions'])) {
    $_message = '';
    $warning_messages = array();

    if (isset($_REQUEST['new_name'])) {
        if ($pma_table->rename($_REQUEST['new_name'])) {
            $_message .= $pma_table->getLastMessage();
            $result = true;
            $GLOBALS['table'] = $pma_table->getName();
            $reread_info = true;
            $reload = true;
        } else {
            $_message .= $pma_table->getLastError();
            $result = false;
        }
    }
}

if (isset($result)) {
    // set to success by default, because result set could be empty
    // (for example, a table rename)
    $_type = 'success';
    if (empty($_message)) {
        $_message = $result
            ? __('Your SQL query has been executed successfully.')
            : __('Error');
        // $result should exist, regardless of $_message
        $_type = $result ? 'success' : 'error';
    }
    if (! empty($warning_messages)) {
        $_message = new PMA_Message;
        $_message->addMessages($warning_messages);
        $_message->isError(true);
        unset($warning_messages);
    }
    echo PMA_Util::getMessage(
        $_message, $sql_query, $_type, $is_view = true
    );
    unset($_message, $_type);
}

$url_params['goto'] = 'view_operations.php';
$url_params['back'] = 'view_operations.php';

/**
 * Displays the page
 */
?>
<!-- Table operations -->
<div class="operations_half_width">
<form method="post" action="view_operations.php">
<?php echo PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table']); ?>
<input type="hidden" name="reload" value="1" />
<fieldset>
    <legend><?php echo __('Operations'); ?></legend>

    <table>
    <!-- Change view name -->
    <tr><td><?php echo __('Rename view to'); ?></td>
        <td><input type="text" size="20" name="new_name" onfocus="this.select()"
                value="<?php echo htmlspecialchars($GLOBALS['table']); ?>"
                required />
        </td>
    </tr>
    </table>
</fieldset>
<fieldset class="tblFooters">
        <input type="hidden" name="submitoptions" value="1" />
        <input type="submit" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>
</div>
<?php
$drop_view_url_params = array_merge(
    $url_params,
    array(
        'sql_query' => 'DROP VIEW ' . PMA_Util::backquote($GLOBALS['table']),
        'goto' => 'tbl_structure.php',
        'reload' => '1',
        'purge' => '1',
        'message_to_show' => sprintf(
            __('View %s has been dropped.'),
            htmlspecialchars($GLOBALS['table'])
        ),
        'table' => $GLOBALS['table']
    )
);
echo '<div class="operations_half_width">';
echo '<fieldset class="caution">';
echo '<legend>' . __('Delete data or table') . '</legend>';

echo '<ul>';
echo PMA_getDeleteDataOrTableLink(
    $drop_view_url_params,
    'DROP VIEW',
    __('Delete the view (DROP)'),
    'drop_view_anchor'
);
echo '</ul>';
echo '</fieldset>';
echo '</div>';
?>
