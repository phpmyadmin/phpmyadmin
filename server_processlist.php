<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Does the common work
 */
require_once('./server_common.inc.php');


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
require('./server_links.inc.php');


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($cfg['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 's_process.png" width="16" height="16" border="0" hspace="2" align="middle" />' : '' )
   . '    ' . $strProcesslist . "\n"
   . '</h2>' . "\n";


/**
 * Sends the query and buffers the result
 */
$serverProcesses = array();
$sql_query = 'SHOW' . (empty($full) ? '' : ' FULL') . ' PROCESSLIST';
$res = PMA_DBI_query($sql_query);
while ($row = PMA_DBI_fetch_assoc($res)) {
    $serverProcesses[] = $row;
}
@PMA_DBI_free_result($res);
unset($res);
unset($row);

PMA_showMessage($GLOBALS['strSuccess']);


/**
 * Displays the page
 */
?>
<table border="0" cellpadding="2" cellspacing="1">
    <tr>
        <td><a href="./server_processlist.php?<?php echo $url_query . (empty($full) ? '&amp;full=1' : ''); ?>" title="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>"><img src="<?php echo $pmaThemeImage . 's_' . (empty($full) ? 'full' : 'partial'); ?>text.png" width="50" height="20" border="0" alt="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>" /></a></td>
        <th>&nbsp;<?php echo $strId; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strUser; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strHost; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strDatabase; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strCommand; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strTime; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strStatus; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strSQLQuery; ?>&nbsp;</th>
    </tr>
<?php
$useBgcolorOne = TRUE;
foreach ($serverProcesses AS $name => $value) {
?>
    <tr>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<a href="./server_processlist.php?<?php echo $url_query . '&amp;kill=' . $value['Id']; ?>"><?php echo $strKill; ?></a>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo $value['Id']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $value['User']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $value['Host']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo (empty($value['db']) ? '<i>' . $strNone . '</i>' : $value['db']); ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $value['Command']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo $value['Time']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo (empty($value['State']) ? '---' : $value['State']); ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo (empty($value['Info']) ? '---' : PMA_SQP_formatHtml(PMA_SQP_parse($value['Info']))); ?>&nbsp;</td>
    </tr>
<?php
    $useBgcolorOne = !$useBgcolorOne;
}
?>
<?php
?>
</table>
<?php


/**
 * Sends the footer
 */
require_once('./footer.inc.php');

?>
