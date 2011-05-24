<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$url_query .= '&amp;goto=tbl_triggers.php';

$triggers = PMA_DBI_get_triggers($db, $table);

$conditional_class_add    = '';
$conditional_class_drop   = '';
$conditional_class_export = '';
if ($GLOBALS['cfg']['AjaxEnable']) {
    $conditional_class_add    = 'class="add_trigger_anchor"';
    $conditional_class_drop   = 'class="drop_trigger_anchor"';
    $conditional_class_export = 'class="export_trigger_anchor"';
}

/**
 * Display the export for a trigger. This is for when JS is disabled.
 */
if (! empty($_GET['exporttrigger']) && ! empty($_GET['triggername'])) {
    foreach ($triggers as $trigger) {
        if ($trigger['name'] === $_GET['triggername']) {
            echo '<fieldset>' . "\n"
               . ' <legend>' . sprintf(__('Export for trigger "%s"'), $trigger['name']) . '</legend>' . "\n"
               . '<textarea cols="40" rows="15" style="width: 100%;">' . $trigger['create'] . '</textarea>' . "\n"
               . '</fieldset>';
        }
    }
}

/**
 * Display a list of available triggers
 */
echo '<fieldset>' . "\n";
echo ' <legend>' . __('Triggers') . '</legend>' . "\n";
if (! $triggers) {
    echo __('There are no triggers to display.');
} else {
    echo '<div style="display: none;" id="no_triggers">' . __('There are no triggers to display.') . '</div>';
    echo '<table class="data" id="trigger_list">' . "\n";

    // Print table header
    echo "<tr>\n<th>" . __('Name') . "</th>\n";
    if (empty($table)) {
        // if we don't have a table name, we will be showing the per-database list.
        // so we must specify which table each trigger belongs to
        echo "<th>" . __('Table') . "</th>\n";
    }
    echo "<th>&nbsp;</th>\n";
    echo "<th>&nbsp;</th>\n";
    echo "<th>&nbsp;</th>\n";
    echo "<th>" . __('Time') . "</th>\n";
    echo "<th>" . __('Event') . "</th>\n";
    echo "</tr>";

    $ct=0;
    $delimiter = '//';
    // Print table contents
    foreach ($triggers as $trigger) {
        $drop_and_create = $trigger['drop'] . $delimiter . "\n" . $trigger['create'] . "\n";
        $row = ($ct%2 == 0) ? 'even' : 'odd';
        $editlink = PMA_linkOrButton('tbl_sql.php?' . $url_query . '&amp;sql_query='
                  . urlencode($drop_and_create) . '&amp;show_query=1&amp;delimiter=' . urlencode($delimiter), $titles['Edit']);
        $exprlink = '<a ' . $conditional_class_export . ' href="db_triggers.php?' . $url_query
                  . '&amp;exporttrigger=1'
                  . '&amp;triggername=' . urlencode($trigger['name'])
                  . '">' . $titles['Export'] . '</a>';
        $droplink = '<a ' . $conditional_class_drop . ' href="sql.php?' . $url_query . '&amp;sql_query='
                  . urlencode($trigger['drop']) . '" >' . $titles['Drop'] . '</a>';

        echo "<tr class='noclick $row'>\n";
        echo "<td><span class='drop_sql' style='display:none;'>{$trigger['drop']}</span>";
        echo "<strong>{$trigger['name']}</strong></td>\n";
        if (empty($table)) {
            echo "<td><a href='tbl_triggers.php?db=$db&amp;table={$trigger['table']}'>";
            echo $trigger['table'] . "</a></td>\n";
        }
        echo "<td>$editlink</td>\n";
        echo "<td><div class='create_sql' style='display: none;'>{$trigger['create']}</div>$exprlink</td>\n";
        echo "<td>$droplink</td>\n";
        echo "<td>{$trigger['action_timing']}</td>\n";
        echo "<td>{$trigger['event_manipulation']}</td>\n";
        echo "</tr>\n";
        $ct++;
    }
    echo '</table>';
}
echo '</fieldset>';

/**
 * Display the form for adding a new trigger
 */
echo '<fieldset>' . "\n"
   . '    <a href="tbl_triggers.php?' . $url_query . '&amp;addtrigger=1" class="' . $conditional_class_add . '">' . "\n"
   . PMA_getIcon('b_trigger_add.png') . __('Add a new Trigger') . '</a>' . "\n"
   . '</fieldset>' . "\n";

?>
