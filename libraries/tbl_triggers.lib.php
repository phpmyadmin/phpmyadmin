<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

$url_query .= '&amp;goto=tbl_structure.php';

$triggers = PMA_DBI_get_triggers($db, $table);

if ($triggers) {
    echo '<div id="tabletriggers">' . "\n";
    echo '<table class="data">' . "\n";
    echo ' <caption class="tblHeaders">' . $strTriggers . '</caption>'  . "\n";
    echo sprintf('<tr>
                          <th>%s</th>
                          <th>&nbsp;</th>
                          <th>&nbsp;</th>
                          <th>%s</th>
                          <th>%s</th>
                    </tr>',
              $strName,
              $strTime,
              $strEvent);
    $ct=0;
    $delimiter = '//';
    foreach ($triggers as $trigger) {
        $drop_and_create = $trigger['drop'] . $delimiter . "\n" . $trigger['create'] . "\n";

        echo sprintf('<tr class="%s">
                              <td><b>%s</b></td>
                              <td>%s</td>
                              <td>%s</td>
                              <td>%s</td>
                              <td>%s</td>
                         </tr>',
                         ($ct%2 == 0) ? 'even' : 'odd',
                         $trigger['name'],
                         '<a href="tbl_sql.php?' . $url_query . '&amp;sql_query=' . urlencode($drop_and_create) . '&amp;show_query=1&amp;delimiter=' . urlencode($delimiter) . '">' . $titles['Change'] . '</a>',
                         '<a href="sql.php?' . $url_query . '&sql_query=' . urlencode($trigger['drop']) . '" onclick="return confirmLink(this, \'' . PMA_jsFormat($trigger['drop'], false) . '\')">' . $titles['Drop'] . '</a>',
                         $trigger['action_timing'],
                         $trigger['event_manipulation']);
        $ct++;
    }
    echo '</table>';
    echo '</div>' . "\n";
}
?>
