<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$events = PMA_DBI_fetch_result('SELECT EVENT_NAME, EVENT_TYPE FROM information_schema.EVENTS WHERE EVENT_SCHEMA= \'' . PMA_sqlAddslashes($db,true) . '\';');

if ($events) {
    PMA_generate_slider_effect('events', $strEvents);
    echo '<fieldset>' . "\n";
    echo ' <legend>' . $strEvents . '</legend>' . "\n";
    echo '<table border="0">';
    echo sprintf('<tr>
                      <th>%s</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>%s</th>
                </tr>',
          $strName,
          $strType);
    $ct=0;
    $delimiter = '//';
    foreach ($events as $event) {

        // information_schema (at least in MySQL 5.1.22) does not return
        // the full CREATE EVENT statement in a way that could be useful for us
        // so we rely on PMA_DBI_get_definition() which uses SHOW CREATE EVENT

        $definition = 'DROP EVENT ' . PMA_backquote($event['EVENT_NAME']) . $delimiter . "\n"
            .  PMA_DBI_get_definition($db, 'EVENT', $event['EVENT_NAME'])
            . "\n";

        $sqlDrop = 'DROP EVENT ' . PMA_backquote($event['EVENT_NAME']);
        echo sprintf('<tr class="%s">
                          <td><strong>%s</strong></td>
                          <td>%s</td>
                          <td>%s</td>
                          <td>%s</td>
                     </tr>',
                     ($ct%2 == 0) ? 'even' : 'odd',
                     $event['EVENT_NAME'],
                     ! empty($definition) ? PMA_linkOrButton('db_sql.php?' . $url_query . '&amp;sql_query=' . urlencode($definition) . '&amp;show_query=1&amp;db_query_force=1&amp;delimiter=' . urlencode($delimiter), $titles['Structure']) : '&nbsp;',
                     '<a href="sql.php?' . $url_query . '&amp;sql_query=' . urlencode($sqlDrop) . '" onclick="return confirmLink(this, \'' . PMA_jsFormat($sqlDrop, false) . '\')">' . $titles['Drop'] . '</a>',
                     $event['EVENT_TYPE']);
        $ct++;
    }
    echo '</table>';
    echo '</fieldset>' . "\n";
    echo '</div>' . "\n";
}
?>
