<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./header.inc.php3');


/**
 * Modifications have been submitted -> updates the table
 */
if (isset($submit)) {
    // Some fields have been urlencoded or double quotes have been translated
    // to "&quot;" in tbl_properties.php3
    $field_orig[0] = urldecode($field_orig[0]);
    if (str_replace('"', '&quot;', $field_orig[0]) == $field_name[0]) {
        $field_name[0] = $field_orig[0];
    }
    $field_default_orig[0] = urldecode($field_default_orig[0]);
    if (str_replace('"', '&quot;', $field_default_orig[0]) == $field_default[0]) {
        $field_default[0] = $field_default_orig[0];
    }
    
    if (!isset($query)) {
        $query = '';
    }
    $query .= ' ' . backquote($field_orig[0]) . ' ' . backquote($field_name[0]) . ' ' . $field_type[0] . ' ';
    if ($field_length[0] != '') {
        $query .= '(' . $field_length[0] . ') ';
    }
    if ($field_attribute[0] != '') {
        $query .= $field_attribute[0] . ' ';
    }
    if ($field_default[0] != '') {
        $query .= 'DEFAULT \'' . sql_addslashes($field_default[0]) . '\' ';
    }
    $query .= $field_null[0] . ' ' . $field_extra[0];
    if (get_magic_quotes_gpc()) {
        $query = stripslashes($query);
    }

   // Optimization fix - 2 May 2001 - Robbat2
   $sql_query = 'ALTER TABLE ' . backquote($db) . '.' . backquote($table) . ' CHANGE ' . $query;
   $result    = mysql_query($sql_query) or mysql_die();
   $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenAltered;
   include('./tbl_properties.php3');
   exit();
}


/**
 * No modifications yet required -> displays the table fields
 */
else {
    if (get_magic_quotes_gpc()) {
        $field = sql_addslashes(stripslashes($field), TRUE);
    } else {
        $field = sql_addslashes($field, TRUE);
    }
    $result     = mysql_query('SHOW FIELDS FROM ' . backquote($db) . '.' . backquote($table) . " LIKE '$field'") or mysql_die();
    $num_fields = mysql_num_rows($result);
    $action     = 'tbl_alter.php3';
    include('./tbl_properties.inc.php3');
}


/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
