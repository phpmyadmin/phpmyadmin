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
 * Does the common work
 */
require_once './libraries/server_common.inc.php';


/**
 * Kills a selected process
 */
if (!empty($kill)) {
    if (PMA_DBI_try_query('KILL ' . $kill . ';')) {
        $message = sprintf($strThreadSuccessfullyKilled, $kill);
    } else {
        $message = sprintf($strCouldNotKill, $kill);
    }
}


/**
 * Displays the links
 */
require './libraries/server_links.inc.php';


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($cfg['MainPageIconic'] ? '<img class="icon" src="' . $pmaThemeImage . 's_process.png" width="16" height="16" alt="" />' : '')
   . $strProcesslist . "\n"
   . '</h2>' . "\n";


/**
 * Sends the query
 */
$sql_query = 'SHOW' . (empty($full) ? '' : ' FULL') . ' PROCESSLIST';
$result = PMA_DBI_query($sql_query);

PMA_showMessage($GLOBALS['strSuccess']);


/**
 * Displays the page
 */
?>
<table id="tableprocesslist" class="data">
<thead>
<tr><td><a href="./server_processlist.php?<?php echo $url_query . (empty($full) ? '&amp;full=1' : ''); ?>"
            title="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>">
        <img src="<?php echo $pmaThemeImage . 's_' . (empty($full) ? 'full' : 'partial'); ?>text.png"
            width="50" height="20" alt="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>" />
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
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
    <td><a href="./server_processlist.php?<?php echo $url_query . '&amp;kill=' . $process['Id']; ?>"><?php echo $strKill; ?></a></td>
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
