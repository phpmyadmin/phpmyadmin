<?php
/* $Id$ */


/**
 * Gets the variables sent or posted to this script, then displays headers
 */
require('./grab_globals.inc.php3');
if (!isset($message)) {
    include('./header.inc.php3');
} else {
    show_message($message);
}


/**
 * Get the list and count of the tables
 */
$tables     = mysql_list_tables($db);
$num_tables = @mysql_numrows($tables);


/**
 * If there is at least one table, displays the printer friendly view, else
 * an error message
 */
// No table
if ($num_tables == 0) {
    echo $strNoTablesFound;
}
// At least one table
else {
    $i = 0;
    ?>

<!-- The tables list -->
<table border="<?php echo($cfgBorder); ?>">
<tr>
    <th><?php echo ucfirst($strTable); ?></th>
    <th><?php echo ucfirst($strRecords); ?></th>
</tr>
    <?php
    while ($i < $num_tables) {
        $table   = mysql_tablename($tables, $i);
        $bgcolor = ($i % 2) ? $cfgBgcolorOne : $bgcolor = $cfgBgcolorTwo;
        echo "\n";
        ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
    <td nowrap="nowrap">
        <b><?php echo htmlspecialchars($table); ?>&nbsp;</b>
    </td>
    <td align="right" nowrap="nowrap">
        &nbsp;<?php count_records($db, $table); ?> 
    </td>
</tr>
        <?php
        $i++;
    } // end while
    echo "\n";
    ?>
</table>
    <?php
} // end if


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
