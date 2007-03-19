<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/* $Id$ */

// Check parameters
if ( PMA_MYSQL_INT_VERSION >= 50002 ) {
    $url_query .= '&amp;goto=db_structure.php';

    $routines = PMA_DBI_fetch_result('SELECT SPECIFIC_NAME,ROUTINE_NAME,ROUTINE_TYPE,DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA= \'' . PMA_sqlAddslashes($db,true) . '\';');

    if ($routines) {
        echo '<table border="0">';
        echo sprintf('<tr>
                          <th>%s</th>
                          <th>&nbsp;</th>
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
            $drop_and_create = '\'DROP ' . $routine['ROUTINE_TYPE'] . ' ' . PMA_backquote($routine['SPECIFIC_NAME']) . $delimiter . "\n"
                . 'CREATE ' . $routine['ROUTINE_TYPE'] . ' ' . PMA_backquote($routine['SPECIFIC_NAME']) . '()' . "\n" . '\'';

            $sql = sprintf('SELECT CONCAT(' . $drop_and_create . ',ROUTINE_DEFINITION,\'\n//\') AS DEFINITION
                                    FROM information_schema.ROUTINES
                                    WHERE SPECIFIC_NAME=\'%s\'',
                                    $routine['SPECIFIC_NAME']);
            $definition = PMA_DBI_fetch_value($sql);

            if ($routine['ROUTINE_TYPE'] == 'PROCEDURE') {
                $sqlUseProc  = 'CALL ' . $routine['SPECIFIC_NAME'] . '()';
            } else {
                $sqlUseProc = 'SELECT ' . $routine['SPECIFIC_NAME'] . '()';
                /* this won't get us far: to really use the function
                   i'd need to know how many parameters the function needs and then create
                   something to ask for them. As i don't see this directly in
                   the table i am afraid that requires parsing the ROUTINE_DEFINITION
                   and i don't really need that now so i simply don't offer
                   a method for running the function*/
            }
            if ($routine['ROUTINE_TYPE'] == 'PROCEDURE') {
                $sqlDropProc = 'DROP PROCEDURE ' . $routine['SPECIFIC_NAME'];
            } else {
                $sqlDropProc = 'DROP FUNCTION ' . $routine['SPECIFIC_NAME'];
            }
            echo sprintf('<tr class="%s">
                              <td><b>%s</b></td>
                              <td>%s</td>
                              <td>%s</td>
                              <td>%s</td>
                              <td>%s</td>
                              <td>%s</td>
                         </tr>',
                         ($ct%2 == 0) ? 'even' : 'odd',
                         $routine['ROUTINE_NAME'],
                         ! empty($definition) ? '<a href="db_sql.php?' . $url_query . '&amp;sql_query=' . urlencode($definition) . '&amp;show_query=1&amp;delimiter=' . urlencode($delimiter) . '">' . $titles['Structure'] . '</a>' : '&nbsp;',
                         $routine['ROUTINE_TYPE'] == 'PROCEDURE' ? '<a href="sql.php?' . $url_query . '&sql_query=' . urlencode($sqlUseProc) . '">' . $titles['Browse'] . '</a>' : '&nbsp;',
                         '<a href="sql.php?' . $url_query . '&sql_query=' . urlencode($sqlDropProc) . '" onclick="return confirmLink(this, \'' . PMA_jsFormat($sqlDropProc, false) . '\')">' . $titles['Drop'] . '</a>',
                         $routine['ROUTINE_TYPE'],
                         $routine['DTD_IDENTIFIER']);
            $ct++;
        }
        echo '</table>';
    }
}
?>
