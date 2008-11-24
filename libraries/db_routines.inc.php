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
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Append goto to ulr_query.
 */
$url_query .= '&amp;goto=db_structure.php';

$routines = PMA_DBI_fetch_result('SELECT SPECIFIC_NAME,ROUTINE_NAME,ROUTINE_TYPE,DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA= \'' . PMA_sqlAddslashes($db,true) . '\';');

if ($routines) {
    PMA_generate_slider_effect('routines', $strRoutines);
    echo '<fieldset>' . "\n";
    echo ' <legend>' . $strRoutines . '</legend>' . "\n";
    echo '<table border="0">';
    echo sprintf('<tr>
                      <th>%s</th>
                      <th>&nbsp;</th>
                      <th>&nbsp;</th>
                      <th>%s</th>
                      <th>%s</th>
                </tr>',
          $strName,
          $strType,
          $strRoutineReturnType);
    $ct=0;
    $delimiter = '//';
    foreach ($routines as $routine) {

        // information_schema (at least in MySQL 5.0.45)
        // does not return the routine parameters
        // so we rely on PMA_DBI_get_definition() which
        // uses SHOW CREATE

        $definition = 'DROP ' . $routine['ROUTINE_TYPE'] . ' ' . PMA_backquote($routine['SPECIFIC_NAME']) . $delimiter . "\n"
            .  PMA_DBI_get_definition($db, $routine['ROUTINE_TYPE'], $routine['SPECIFIC_NAME'])
            . "\n";

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
            $sqlDropProc = 'DROP PROCEDURE ' . PMA_backquote($routine['SPECIFIC_NAME']);
        } else {
            $sqlDropProc = 'DROP FUNCTION ' . PMA_backquote($routine['SPECIFIC_NAME']);
        }
        echo sprintf('<tr class="%s">
                          <td><strong>%s</strong></td>
                          <td>%s</td>
                          <td>%s</td>
                          <td>%s</td>
                          <td>%s</td>
                     </tr>',
                     ($ct%2 == 0) ? 'even' : 'odd',
                     $routine['ROUTINE_NAME'],
                     ! empty($definition) ? PMA_linkOrButton('db_sql.php?' . $url_query . '&amp;sql_query=' . urlencode($definition) . '&amp;show_query=1&amp;delimiter=' . urlencode($delimiter), $titles['Structure']) : '&nbsp;',
                     '<a href="sql.php?' . $url_query . '&amp;sql_query=' . urlencode($sqlDropProc) . '" onclick="return confirmLink(this, \'' . PMA_jsFormat($sqlDropProc, false) . '\')">' . $titles['Drop'] . '</a>',
                     $routine['ROUTINE_TYPE'],
                     $routine['DTD_IDENTIFIER']);
        $ct++;
    }
    echo '</table>';
    echo '</fieldset>' . "\n";
    echo '</div>' . "\n";
}
?>
