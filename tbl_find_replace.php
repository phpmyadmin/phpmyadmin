<?php 
/**
 * prepaires sql query for "find and replace" feature and executes it
 * to be executed as an action from find and replace form (see at the bottom of "tbl_change.php")
 * 
 *@todo: add confirmation for the query from user before being executed
 * 
 */


 
require_once 'libraries/common.inc.php';
require_once 'libraries/db_table_exists.lib.php';
require_once 'libraries/insert_edit.lib.php';


/**
*prepairing query
*
*/


if (isset($_REQUEST['find']) and isset($_REQUEST['replace']) ){

$table_fields = array_values(PMA_DBI_get_columns($db, $table));

	$findWhat=$_REQUEST['find'];
	$replaceWith=$_REQUEST['replace'];
	//if one column is checked at the radio buttons for columns in the form
	if (isset($_REQUEST['column'])){
		$column=$_REQUEST['column'];
		$s_query="UPDATE ".$table." SET ".$table.".".$column."='".$replaceWith."' WHERE ".$table.".".$column."='".$findWhat."'";
		
		if (isset($_REQUEST['option']) and isset($_REQUEST['where_clause']) and $_REQUEST['option']=='select'){
			$where=$_REQUEST['where_clause'];
			for ($i=0; $i<count($where); $i++){
				if ($i==0)
					$s_query=$s_query." AND (";
				else
					$s_query=$s_query." OR ";
				$s_query=$s_query.$where[$i];
			}
			
			if ($i!=0){
				$s_query=$s_query." )";
			}
		}
	}
	
	//no need to execute the query if no column is checked
	else{
		require 'sql.php';
		exit;
	}



/**
*executing query
*
*/

$query[]=$s_query;
list ($url_params, $total_affected_rows, $last_messages, $warning_messages,
    $error_messages, $return_to_sql_query)
		
        = PMA_executeSqlQuery($url_params, $query);
		
//no of rows affected by query
$message = PMA_Message::getMessageForAffectedRows($total_affected_rows);
$message->addMessages($last_messages, '<br />');

if (! empty($warning_messages)) {
    $message->addMessages($warning_messages, '<br />');
    $message->isError(true);
}
if (! empty($error_messages)) {
    $message->addMessages($error_messages);
    $message->isError(true);
}

if (! empty($return_to_sql_query)) {
    $disp_query = $GLOBALS['sql_query'];
    $disp_message = $message;
    unset($message);
    $GLOBALS['sql_query'] = $return_to_sql_query;
}
	
require 'sql.php';
exit;
}


//if the page is reloaded
else{
header ("location: sql.php?db=".$db."&table=".$table);
}
?>
