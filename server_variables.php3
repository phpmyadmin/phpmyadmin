<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Does the common work
 */
require('./server_common.inc.php3');

/**
 * Handles some variables that may have been sent by the calling script
 */
if (isset($db)) {
    unset($db);
}
if (isset($table)) {
    unset($table);
}

/**
 * Displays the links
 */
require('./server_links.inc.php3');

/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . '    ' . $strServerVars . "\n"
   . '</h2>' . "\n";

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (!$is_superuser && !$cfg['ShowMysqlVars']) {
    echo $strNoPrivileges;
    include('./footer.inc.php3');
    exit;
}

/**
 * Sends the queries and buffers the result
 */
if (PMA_MYSQL_INT_VERSION >= 40003) {
    $res = @PMA_mysql_query('SHOW SESSION VARIABLES;', $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW VARIABLES;');
    while ($row = PMA_mysql_fetch_row($res)) {
        $serverVars[$row[0]] = $row[1];
    }
    @mysql_free_result($res);
    $res = @PMA_mysql_query('SHOW GLOBAL VARIABLES;', $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW VARIABLES;');
    while ($row = PMA_mysql_fetch_row($res)) {
        $serverVarsGlobal[$row[0]] = $row[1];
    }
    @mysql_free_result($res);
} else {
    $res = @PMA_mysql_query('SHOW VARIABLES;', $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW VARIABLES;');
    while ($row = PMA_mysql_fetch_row($res)) {
        $serverVars[$row[0]] = $row[1];
    }
    @mysql_free_result($res);
}
unset($res);
unset($row);

/**
 * Displays the page
 */
?>
<table border="0">
    <tr>
        <th>&nbsp;<?php echo $strVar; ?>&nbsp;</th>
<?php
echo '        <th>&nbsp;';
if (PMA_MYSQL_INT_VERSION >= 40003) {
    echo $strSessionValue . '&nbsp;</th>' . "\n"
       . '        <th>&nbsp;' . $strGlobalValue;
} else {
    echo $strValue;
}
echo '&nbsp;</th>' . "\n";
?>
    </tr>
<?php
$useBgcolorOne = TRUE;
while (list($name, $value) = each($serverVars)) {
?>
    <tr>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars(str_replace('_', ' ', $name)); ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars($value); ?>&nbsp;</td>
<?php
    if (PMA_MYSQL_INT_VERSION >= 40003) {
?>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars($serverVarsGlobal[$name]); ?>&nbsp;</td>
<?php
        $useBgcolorOne = !$useBgcolorOne;
    }
?>
    </tr>
<?php
}
?>
</table>
<?php

/**
 * Sends the footer
 */
require('./footer.inc.php3');

?>