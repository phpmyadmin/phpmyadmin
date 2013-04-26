<?php

require_once './libraries/common.inc.php';
require_once './libraries/database_interface.lib.php';
include_once './libraries/user_info.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('user_info.js');
 
//Test whether Database phpmyadmin exist or not
$is_db_exist = PMA_DBI_select_db("phpmyadmin");

//If database doen't exist then create it
if(!($is_db_exist)){
	$query = "CREATE DATABASE IF NOT EXISTS `phpmyadmin`"
  	."DEFAULT CHARACTER SET utf8 COLLATE utf8_bin";
  	$result = PMA_DBI_try_query($query);
  }

//Test if Table Exist or not  
$is_table_exist = PMA_DBI_try_query("select 1 from phpmyadmin.pma_user_info");

//If table doesn't  exist then create it
if(!($is_table_exist))
{
	$query= "CREATE TABLE IF NOT EXISTS `pma_user_info` ("
  	. "`User` char(16) COLLATE utf8_bin NOT NULL,"
  	. "`Host` char(60) COLLATE utf8_bin NOT NULL,"
  	. "`Full Name` varchar(50) COLLATE utf8_bin NOT NULL COMMENT 'Write Full name',"
  	. "`Description` tinytext COLLATE utf8_bin NOT NULL COMMENT 'Description Of User',"
  	. "`E-Mail` varchar(50) COLLATE utf8_bin NOT NULL COMMENT 'Email-Id Of User',"
  	. "`Contact Information` varchar(20) COLLATE utf8_bin NOT NULL COMMENT 'Contact Information Of User',"
  	. "`Icon` blob NOT NULL COMMENT 'Icon of User'"
	. ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Information Regarding Users of Database'";
	$result = PMA_DBI_try_query($query);
}

//if form for updation was submitted
if(isset($_POST['editform'])){

	//take the host and user information from $cfg. No need to pass them as $_POST
	$host = $cfg['Server']['host'];
	$user = $cfg['Server']['user'];
	$ext = $cfg['Server']['extension'];
	
	//process the data and update the table  
	$newname = PMA_Util::sqlAddSlashes($_POST['new_name']);
	$newcontact = PMA_Util::sqlAddSlashes($_POST['new_contact']);
	
	//sanitizing Telephone numbers. Currently of the type "+<isd code><numbers separated with ->"
	if(!(preg_match("/^\+?([0-9]-?)+$/",$newcontact))){
		PMA_Message::error('Check your Telephone Number again. It will not be saved.')->display();
		unset($newcontact);
	}
	
	$newmail = PMA_Util::sqlAddSlashes($_POST['new_email']);
	
	//sanitizing email address
	$regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
	if(!(preg_match($regex, $newmail))){
		PMA_Message::error('Check your Email Address Again. It will not be saved')->display();
		unset($newmail);
	}
	
	//sanitizing Description
	$newdesc = htmlspecialchars(PMA_Util::sqlAddSlashes($_POST['new_description']));
	$newdesc=nl2br($newdesc);
	
	if(($_FILES['new_img']['size']/1024)<64){ //if uploaded image is of less than 64KB
		if((substr($_FILES['new_img']['type'],0,5) == "image")) //if uploaded image is of image type
			$newimg = $_FILES['new_img']['tmp_name'];
		else
			PMA_Message::error('Selected File is not of Image Type')->display();
	}
	else{
		PMA_Message::error('File Size was greater than 64KB')->display();
	}
	
	//Send The Parameters to the function for Insertion/Updation
	$result = doInsert_Update($host, $user, $newname, $newcontact,
				$newmail, $newdesc, $newimg, $ext);			
}

$html='';

//usual display of details
$user=$_GET['user'];
$host=$_GET['host'];

//If No information comes via GET then Display User Information. Helps in redirecting after Updation
if(!isset($user) && (!isset($host))){
	$user = $cfg['Server']['user'];
	$host = $cfg['Server']['host'];
}
	
//if user whose information is desired and the logged in user are same
//then display the edit option
if($user == $cfg['Server']['user'] && $host == $cfg['Server']['host'])
	$edit=true;
	
$userdetails=showFetchedInfo($host,$user);

//return the Fetched Html via array. Let's Keep all the output part of code in same place
$html.= $userdetails[4];

//only the user can change his information and no other
if($edit){
	$html.= '<input type = \'button\' id = \'buttonGo\' value = \'Edit\' name=\'submit_reset\'>';
}

$html.=   '<div id = \'user_info\' class = \'user_info\'>'
	. '<h2> Update User Information</h2>'
	. '<form method = post class = disableAjax id = edituser action = user_info.php enctype = \'multipart/form-data\'>'
	. '<input type = hidden name = token value ='. $_SESSION[' PMA_token '] . '>'
	. '<input type = hidden name = editform value = 1>'
	. '<table>'
	. '<tr><td>Name :</td><td><input type = text name = new_name value="' . htmlspecialchars($userdetails[0]) . '"></td></tr>'
	. '<tr><td>Contact :</td><td><input type = text name = new_contact value="' . htmlspecialchars($userdetails[1]) . '"></td></tr>'
	. '<tr><td>E-mail : </td><td><input type = text name = new_email value="' . htmlspecialchars($userdetails[3]) . '"></td></tr>'
	. '<tr><td>Description : </td><td><textarea name = new_description rows=20 cols=30>' 
	. htmlspecialchars(str_replace('<br />',"",($userdetails[2]))) .'</textarea></td></tr>'
	. '<tr><td>Icon : </td><td><input type = file name = new_img id = new_img>(max 64KB)</td></tr></table>'
	. '<input type = submit value = update id = btn_submitform>'
	. '</form></div>';
	
$response->addHTML($html);
?>
