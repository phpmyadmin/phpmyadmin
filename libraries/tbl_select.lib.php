<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for the table-search page and zoom-search page
 *
 * Funtion PMA_tbl_getFields : Returns the fields of a table 
 * Funtion PMA_tbl_search_getWhereClause : Returns the where clause for query generation 
 *
 * @package phpMyAdmin
 */

function PMA_tbl_getFields($table,$db) {
    
    // Gets the list and number of fields

    $result     = PMA_DBI_query('SHOW FULL FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db) . ';', null, PMA_DBI_QUERY_STORE);
    $fields_cnt = PMA_DBI_num_rows($result);
    $fields_list = $fields_null = $fields_type = $fields_collation = array();
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $fields_list[] = $row['Field'];
        $type          = $row['Type'];
        // reformat mysql query output
        if (strncasecmp($type, 'set', 3) == 0
            || strncasecmp($type, 'enum', 4) == 0) {
            $type = str_replace(',', ', ', $type);
        } else {

            // strip the "BINARY" attribute, except if we find "BINARY(" because
            // this would be a BINARY or VARBINARY field type
            if (!preg_match('@BINARY[\(]@i', $type)) {
                $type = preg_replace('@BINARY@i', '', $type);
            }
            $type = preg_replace('@ZEROFILL@i', '', $type);
            $type = preg_replace('@UNSIGNED@i', '', $type);

            $type = strtolower($type);
        }
        if (empty($type)) {
            $type = '&nbsp;';
        }
        $fields_null[] = $row['Null'];
        $fields_type[] = $type;
        $fields_collation[] = !empty($row['Collation']) && $row['Collation'] != 'NULL'
                          ? $row['Collation']
                          : '';
    } // end while
    PMA_DBI_free_result($result);
    unset($result, $type);

    return array($fields_list,$fields_type,$fields_collation,$fields_null);
   
}

function PMA_tbl_search_getWhereClause($fields, $names, $types, $collations, $func_type, $unaryFlag){
	
            //Return the where clause for query generation based on the inputs provided in the tbl_select.php form
	    
            if($unaryFlag){
		$fields = '';
                $w = PMA_backquote($names) . ' ' . $func_type;

            } elseif (strncasecmp($types, 'enum', 4) == 0) {
                if (!empty($fields)) {
                    if (! is_array($fields)) {
                        $fields = explode(',', $fields);
                    }
                    $enum_selected_count = count($fields);
                    if ($func_type == '=' && $enum_selected_count > 1) {
                        $func_type    = 'IN';
                        $parens_open  = '(';
                        $parens_close = ')';

                    } elseif ($func_type == '!=' && $enum_selected_count > 1) {
                        $func_type    = 'NOT IN';
                        $parens_open  = '(';
                        $parens_close = ')';

                    } else {
                        $parens_open  = '';
                        $parens_close = '';
                    }
                    $enum_where = '\'' . PMA_sqlAddslashes($fields[0]) . '\'';
                    for ($e = 1; $e < $enum_selected_count; $e++) {
                        $enum_where .= ', \'' . PMA_sqlAddslashes($fields[$e]) . '\'';
                    }

                    $w = PMA_backquote($names) . ' ' . $func_type . ' ' . $parens_open . $enum_where . $parens_close;
                }

            } elseif ($fields != '') {
                // For these types we quote the value. Even if it's another type (like INT),
                // for a LIKE we always quote the value. MySQL converts strings to numbers
                // and numbers to strings as necessary during the comparison
                if (preg_match('@char|binary|blob|text|set|date|time|year@i', $types) || strpos(' ' . $func_type, 'LIKE')) {
                    $quot = '\'';
                } else {
                    $quot = '';
                }

                // LIKE %...%
                if ($func_type == 'LIKE %...%') {
                    $func_type = 'LIKE';
                    $fields = '%' . $fields . '%';
                }
                if ($func_type == 'REGEXP ^...$') {
                    $func_type = 'REGEXP';
                    $fields = '^' . $fields . '$';
                }

                if ($func_type == 'IN (...)' || $func_type == 'NOT IN (...)' || $func_type == 'BETWEEN' || $func_type == 'NOT BETWEEN') {
                    $func_type = str_replace(' (...)', '', $func_type);

                    // quote values one by one
                    $values = explode(',', $fields);
                    foreach ($values as &$value)
                        $value = $quot . PMA_sqlAddslashes(trim($value)) . $quot;

                    if ($func_type == 'BETWEEN' || $func_type == 'NOT BETWEEN')
                        $w = PMA_backquote($names) . ' ' . $func_type . ' ' . (isset($values[0]) ? $values[0] : '')  . ' AND ' . (isset($values[1]) ? $values[1] : '');
                    else
                        $w = PMA_backquote($names) . ' ' . $func_type . ' (' . implode(',', $values) . ')';
                }
                else {
                    $w = PMA_backquote($names) . ' ' . $func_type . ' ' . $quot . PMA_sqlAddslashes($fields) . $quot;;
                }

            } // end if

	return $w;
}
 
function PMA_tbl_getSubTabs(){

	$subtabs = array();

	$subtabs['search']['icon'] = 'b_search.png';
	$subtabs['search']['text'] = __('Table Search');
	$subtabs['search']['link'] = 'tbl_select.php';
	$subtabs['search']['id'] = 'tbl_search_id';
	$subtabs['search']['args']['pos'] = 0;

	$subtabs['zoom']['icon'] = 'b_props.png';
	$subtabs['zoom']['link'] = 'tbl_zoom_select.php';
	$subtabs['zoom']['text'] = __('Zoom Search');
	$subtabs['zoom']['id'] = 'zoom_search_id';
	
	return $subtabs;

}

?>
