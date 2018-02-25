<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * View manipulations
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 *
 */
require_once './libraries/common.inc.php';

$pma_table = new Table($GLOBALS['table'], $GLOBALS['db']);

/**
 * Load JavaScript files
 */
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_operations.js');

/**
 * Runs common work
 */
require './libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=view_operations.php&amp;back=view_operations.php';
$url_params['goto'] = $url_params['back'] = 'view_operations.php';

$operations = new Operations();

/**
 * Updates if required
 */
$_message = new Message;
$_type = 'success';
if (isset($_REQUEST['submitoptions'])) {

    if (isset($_REQUEST['new_name'])) {
        if ($pma_table->rename($_REQUEST['new_name'])) {
            $_message->addText($pma_table->getLastMessage());
            $result = true;
            $GLOBALS['table'] = $pma_table->getName();
            /* Force reread after rename */
            $pma_table->getStatusInfo(null, true);
            $reload = true;
        } else {
            $_message->addText($pma_table->getLastError());
            $result = false;
        }
    }

    $warning_messages = $operations->getWarningMessagesArray();
}

if (isset($result)) {
    // set to success by default, because result set could be empty
    // (for example, a table rename)
    if (empty($_message->getString())) {
        if ($result) {
            $_message->addText(
                __('Your SQL query has been executed successfully.')
            );
        } else {
            $_message->addText(__('Error'));
        }
        // $result should exist, regardless of $_message
        $_type = $result ? 'success' : 'error';
    }
    if (! empty($warning_messages)) {
        $_message->addMessagesString($warning_messages);
        $_message->isError(true);
        unset($warning_messages);
    }
    echo Util::getMessage(
        $_message, $sql_query, $_type
    );
}
unset($_message, $_type);

$url_params['goto'] = 'view_operations.php';
$url_params['back'] = 'view_operations.php';

/**
 * Displays the page
 */
?>
<!-- Table operations -->
<div>
<form method="post" action="view_operations.php">
<?php echo Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']); ?>
<input type="hidden" name="reload" value="1" />
<fieldset>
    <legend><?php echo __('Operations'); ?></legend>

    <table>
    <!-- Change view name -->
    <tr><td><?php echo __('Rename view to'); ?></td>
        <td><input type="text" name="new_name" onfocus="this.select()"
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
        'sql_query' => 'DROP VIEW ' . Util::backquote(
            $GLOBALS['table']
        ),
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
echo '<div>';
echo '<fieldset class="caution">';
echo '<legend>' , __('Delete data or table') , '</legend>';

echo '<ul>';
echo $operations->getDeleteDataOrTablelink(
    $drop_view_url_params,
    'DROP VIEW',
    __('Delete the view (DROP)'),
    'drop_view_anchor'
);
echo '</ul>';
echo '</fieldset>';
echo '</div>';
