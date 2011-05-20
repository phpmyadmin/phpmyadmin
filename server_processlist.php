<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/server_common.inc.php';
require './libraries/server_links.inc.php';


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 's_process.png" width="16" height="16" border="0" hspace="2" align="middle" alt="" />' : '')
   . '    ' . __('Processes') . "\n"
   . '</h2>' . "\n";

/**
 * Kills a selected process
 */
if (!empty($_REQUEST['kill'])) {
    if (PMA_DBI_try_query('KILL ' . $_REQUEST['kill'] . ';')) {
        $message = PMA_Message::success(__('Thread %s was successfully killed.'));
    } else {
        $message = PMA_Message::error(__('phpMyAdmin was unable to kill thread %s. It probably has already been closed.'));
    }
    $message->addParam($_REQUEST['kill']);
    $message->display();
}

$url_params = array();

if (! empty($_REQUEST['full'])) {
    $sql_query = 'SHOW FULL PROCESSLIST';
    $url_params['full'] = 1;
    $full_text_link = 'server_processlist.php' . PMA_generate_common_url(array(), 'html', '?');
} else {
    $sql_query = 'SHOW PROCESSLIST';
    $full_text_link = 'server_processlist.php' . PMA_generate_common_url(array('full' => 1));
}
$result = PMA_DBI_query($sql_query);

/**
 * Displays the page
 */
?>
<table id="tableprocesslist" class="data">
<thead>
<tr>
    <?php if (!PMA_DRIZZLE): ?>
    <th><a href="<?php echo $full_text_link; ?>"
            title="<?php echo empty($full) ? __('Show Full Queries') : __('Truncate Shown Queries'); ?>">
        <img src="<?php echo $pmaThemeImage . 's_' . (empty($_REQUEST['full']) ? 'full' : 'partial'); ?>text.png"
            alt="<?php echo empty($_REQUEST['full']) ? __('Show Full Queries') : __('Truncate Shown Queries'); ?>" />
        </a></th>
    <?php else: ?>
    <th></th>
    <?php endif; ?>
    <th><?php echo __('ID'); ?></th>
    <th><?php echo __('User'); ?></th>
    <th><?php echo __('Host'); ?></th>
    <th><?php echo __('Database'); ?></th>
    <th><?php echo __('Command'); ?></th>
    <th><?php echo __('Time'); ?></th>
    <th><?php echo __('Status'); ?></th>
    <th><?php echo __('SQL query'); ?></th>
</tr>
</thead>
<tbody>
<?php
$odd_row = true;
while($process = PMA_DBI_fetch_assoc($result)) {
    if (PMA_DRIZZLE) {
        // Drizzle uses uppercase keys
        foreach ($process as $k => $v) {
            $k = $k !== 'DB'
                ? $k = ucfirst(strtolower($k))
                : 'db';
            $process[$k] = $v;
        }
    }
    $url_params['kill'] = $process['Id'];
    $kill_process = 'server_processlist.php' . PMA_generate_common_url($url_params);
    ?>
<tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?>">
    <td><a href="<?php echo $kill_process ; ?>"><?php echo __('Kill'); ?></a></td>
    <td class="value"><?php echo $process['Id']; ?></td>
    <td><?php echo PMA_DRIZZLE ? $process['Username'] : $process['User']; ?></td>
    <td><?php echo $process['Host']; ?></td>
    <td><?php echo ((! isset($process['db']) || ! strlen($process['db'])) ? '<i>' . __('None') . '</i>' : $process['db']); ?></td>
    <td><?php echo $process['Command']; ?></td>
    <td class="value"><?php echo $process['Time']; ?></td>
    <td><?php echo (empty($process['State']) ? '---' : $process['State']); ?></td>
    <td><?php echo (empty($process['Info']) ? '---' : PMA_SQP_formatHtml(PMA_SQP_parse($process['Info']))); ?></td>
</tr>
    <?php
    $odd_row = ! $odd_row;
}
?>
</tbody>
</table>
<?php

/**
 * Sends the footer
 */
require './libraries/footer.inc.php';
?>
