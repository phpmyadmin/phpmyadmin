<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/server_common.inc.php';
require './libraries/server_links.inc.php';


/**
 * Kills a selected process
 */
if (!empty($_REQUEST['kill'])) {
    if (PMA_DBI_try_query('KILL ' . $_REQUEST['kill'] . ';')) {
        $message = PMA_Message::success('strThreadSuccessfullyKilled');
    } else {
        $message = PMA_Message::error('strCouldNotKill');
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
<tr><td><a href="<?php echo $full_text_link; ?>"
            title="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>">
        <img src="<?php echo $pmaThemeImage . 's_' . (empty($_REQUEST['full']) ? 'full' : 'partial'); ?>text.png"
            width="50" height="20" alt="<?php echo empty($_REQUEST['full']) ? $strShowFullQueries : $strTruncateQueries; ?>" />
        </a></td>
    <th><?php echo $strId; ?></th>
    <th><?php echo $strUser; ?></th>
    <th><?php echo $strHost; ?></th>
    <th><?php echo $strDatabase; ?></th>
    <th><?php echo $strCommand; ?></th>
    <th><?php echo $strTime; ?></th>
    <th><?php echo $strStatus; ?></th>
    <th><?php echo $strSQLQuery; ?></th>
</tr>
</thead>
<tbody>
<?php
$odd_row = true;
while($process = PMA_DBI_fetch_assoc($result)) {
    $url_params['kill'] = $process['Id'];
    $kill_process = 'server_processlist.php' . PMA_generate_common_url($url_params);
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
    <td><a href="<?php echo $kill_process ; ?>"><?php echo $strKill; ?></a></td>
    <td class="value"><?php echo $process['Id']; ?></td>
    <td><?php echo $process['User']; ?></td>
    <td><?php echo $process['Host']; ?></td>
    <td><?php echo ((! isset($process['db']) || ! strlen($process['db'])) ? '<i>' . $strNone . '</i>' : $process['db']); ?></td>
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
require_once './libraries/footer.inc.php';
?>
