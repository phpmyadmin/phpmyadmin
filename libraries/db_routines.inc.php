<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @todo Support seeing the "results" of the called procedure or
 *       function. This needs further reseach because a procedure
 *       does not necessarily contain a SELECT statement that
 *       produces something to see. But it seems we could at least
 *       get the number of rows affected. We would have to
 *       use the CLIENT_MULTI_RESULTS flag to get the result set
 *       and also the call status. All this does not fit well with
 *       our current sql.php.
 *       Of course the interface would need a way to pass calling parameters.
 *       Also, support DEFINER (like we do in export).
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// $url_query .= '&amp;goto=db_routines.php' . rawurlencode("?db=$db");

$routines = PMA_DBI_fetch_result('SELECT SPECIFIC_NAME,ROUTINE_NAME,ROUTINE_TYPE,DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA= \'' . PMA_sqlAddslashes($db,true) . '\';');

echo '<fieldset>' . "\n";
echo ' <legend>' . __('Routines') . '</legend>' . "\n";

if (! $routines) {
    echo __('There are no routines to display.');
} else {
    echo '<div style="display: none;" id="no_routines">' . __('There are no routines to display.') . '</div>';
    echo '<table class="data" id="routine_list">';
    echo sprintf('<tr>
                      <th>%s</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>%s</th>
                      <th>%s</th>
                </tr>',
          __('Name'),
          __('Type'),
          __('Return type'));
    $ct=0;
    $delimiter = '//';
    $conditional_class_add    = '';
    $conditional_class_drop   = '';
    $conditional_class_export = '';
    if ($GLOBALS['cfg']['AjaxEnable']) {
        $conditional_class_add    = 'class="add_routine_anchor"';
        $conditional_class_drop   = 'class="drop_procedure_anchor"';
        $conditional_class_export = 'class="export_procedure_anchor"';
    }
    foreach ($routines as $routine) {

        // information_schema (at least in MySQL 5.0.45)
        // does not return the routine parameters
        // so we rely on PMA_DBI_get_definition() which
        // uses SHOW CREATE

        $create_proc = PMA_DBI_get_definition($db, $routine['ROUTINE_TYPE'], $routine['SPECIFIC_NAME']);
        $definition = 'DROP ' . $routine['ROUTINE_TYPE'] . ' ' . PMA_backquote($routine['SPECIFIC_NAME']) . $delimiter . "\n"
            .  $create_proc . "\n";

        //if ($routine['ROUTINE_TYPE'] == 'PROCEDURE') {
        //    $sqlUseProc  = 'CALL ' . $routine['SPECIFIC_NAME'] . '()';
        //} else {
        //    $sqlUseProc = 'SELECT ' . $routine['SPECIFIC_NAME'] . '()';
            /* this won't get us far: to really use the function
               i'd need to know how many parameters the function needs and then create
               something to ask for them. As i don't see this directly in
               the table i am afraid that requires parsing the ROUTINE_DEFINITION
               and i don't really need that now so i simply don't offer
               a method for running the function*/
        //}
        if ($routine['ROUTINE_TYPE'] == 'PROCEDURE') {
            $sqlDropProc = 'DROP PROCEDURE IF EXISTS ' . PMA_backquote($routine['SPECIFIC_NAME']);
        } else {
            $sqlDropProc = 'DROP FUNCTION IF EXISTS ' . PMA_backquote($routine['SPECIFIC_NAME']);
        }

        echo sprintf('<tr class="%s">
                          <td><span class="drop_sql" style="display:none;">%s</span><strong>%s</strong></td>
                          <td>%s</td>
                          <td>%s</td>
                          <td><div class="create_sql" style="display: none;">%s</div>%s</td>
                          <td>%s</td>
                          <td>%s</td>
                     </tr>',
                     ($ct%2 == 0) ? 'even' : 'odd',
                     $sqlDropProc,
                     $routine['ROUTINE_NAME'],
                     ! empty($definition) ? PMA_linkOrButton('db_sql.php?' . $url_query . '&amp;sql_query=' . urlencode($definition) . '&amp;show_query=1&amp;db_query_force=1&amp;delimiter=' . urlencode($delimiter), $titles['Edit']) : '&nbsp;',
                     ! empty($definition) ? PMA_linkOrButton('#', $titles['Execute']) : '&nbsp;',
                     $create_proc,
                     '<a ' . $conditional_class_export . ' href="#" >' . $titles['Export'] . '</a>',
                     '<a ' . $conditional_class_drop. ' href="sql.php?' . $url_query . '&amp;sql_query=' . urlencode($sqlDropProc) . '" >' . $titles['Drop'] . '</a>',
                     $routine['ROUTINE_TYPE'],
                     $routine['DTD_IDENTIFIER']);
        $ct++;
    }
    echo '</table>';
}
echo '</fieldset>' . "\n";

/**
 * Display the form for adding a new routine
 */
echo '<fieldset>' . "\n"
   . '    <a href="db_routines.php?' . $GLOBALS['url_query'] . '&amp;addroutine=1" class="' . $conditional_class_add . '">' . "\n"
   . PMA_getIcon('b_routine_add.png') . __('Add a new Routine') . '</a>' . "\n"
   . '</fieldset>' . "\n";

?>
