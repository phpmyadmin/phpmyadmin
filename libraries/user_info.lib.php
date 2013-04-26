<?php
if (! defined('PHPMYADMIN')) {
    exit;
}

/*showFetchedInfo will display the stored information of
 *the user.
 */
function showFetchedInfo($host, $user){
	$query = "SELECT * FROM phpmyadmin.pma_user_info 
	WHERE User = '". PMA_Util::sqlAddSlashes($user) . "' 
	AND Host = '". PMA_Util::sqlAddSlashes($host) . "'";
	
	$userInfo_arr = PMA_DBI_fetch_result($query);
	
	//userinfo is available in the $userInfo_arr[0]
	//sanitising input for proper messages
	$icon = isset($userInfo_arr[0]['Icon'])?$userInfo_arr[0]['Icon']:0;
	$name = isset($userInfo_arr[0]['Full Name'])?$userInfo_arr[0]['Full Name']: "No Name Set";
	$contact = isset($userInfo_arr[0]['Contact Information'])?$userInfo_arr[0]['Contact Information']:"No Contact Information";
	$description = isset($userInfo_arr[0]['Description'])?$userInfo_arr[0]['Description']:"No Description found";
	$email = isset($userInfo_arr[0]['E-Mail'])?$userInfo_arr[0]['E-Mail']: "No Email Found";
	
	$html='';
	$html.= '<h2> User Details</h2>'
		. '<table id=display_table><tr>'
		. '<td><div id= \'user_img\' ><img src = "data:image/png|image/jpeg|image/gif;base64,' 
		. htmlspecialchars(base64_encode($icon)) . '" width=150 height=150 /></div></td>'
		. '<td><div id= \'details_text\'>'
		. '<h1>' . htmlspecialchars($name) . '</h1>'
		. '<div id = \'user_contact\'>'. htmlspecialchars($contact) . ","
		. htmlspecialchars($email) . '</div>'
		. '<div id = \'user_description\'>'. ($description) .'</div>'
		. '</div></td></tr></table>';
	
	//return all User details along with the html to display	
	return (array($name, $contact, $description, $email, $html));
}


/*Insert or Update the User Details.First the information is checked whether it is available or not
 *If available then UPDATE query is run. ELse an INSERT query is Run*/
function doInsert_Update($host, $user, $newname, $newcontact, $newmail, $newdesc, $newimg){
	
	$dmltype = "SELECT COUNT(*) from phpmyadmin.pma_user_info where User = '". $user . "'"
		. " AND Host = '" . $host . "'";
	//Test if the User's Information already exist
	$result = PMA_DBI_fetch_result($dmltype);
	
	$dml_query='';
	if($result[0] > 0){ //information already exist, we need to update it
		$dml_query = "UPDATE "
			. "phpmyadmin.pma_user_info SET ";
		if(isset($newname))
			$dml_query.= PMA_Util::backquote("Full Name")." ='". $newname . "'";
		if(isset($newdesc))
			$dml_query.= ", " .PMA_Util::backquote("Description"). "='". $newdesc ."'";
		if(isset($newmail))
			$dml_query.= "," .PMA_Util::backquote("E-Mail"). "='". $newmail ."'";
		if(isset($newcontact))
			$dml_query.= "," .PMA_Util::backquote("Contact Information"). "='". $newcontact ."'";
		if(isset($newimg) && $ext == "mysqli")
			$dml_query.= "," .PMA_Util::backquote("Icon"). "='". mysqli_escape_string(file_get_contents($newimg)) ."'";
		elseif(isset($newimg))
			$dml_query.= "," .PMA_Util::backquote("Icon"). "='". mysql_escape_string(file_get_contents($newimg)) ."'";
			
			$dml_query.= " WHERE User='" . $user . "' AND Host='" .$host. "'";
	}
	else{ //No information found, So we have to insert it
		$dml_query = 'INSERT '
			. 'INTO phpmyadmin.pma_user_info '
			. "VALUES ('". $user ."','". $host ."'";
			$dml_query.= ",'". $newname ."'";
			$dml_query.= ",'". $newdesc ."'";
			$dml_query.= ",'". $newmail ."'";
			$dml_query.= ",'". $newcontact ."'";
			if($ext == "mysqli")
				$dml_query.= ",'". PMA_Util::sqlAddSlashes(file_get_contents($newimg)) ."'";
			else
				$dml_query.= ",'". PMA_Util::sqlAddSlashes(file_get_contents($newimg)) ."'";
			$dml_query.= ")"; 
	}
	
	//Query is build, time to execute it
	$result=PMA_DBI_query($dml_query);
	return $result;
}

?>
