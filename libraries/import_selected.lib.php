<?php 
	$data = fread($import_handle,36782*8);
	
	//Explode file to find CREATE TABLE queries and table names
	$cr = preg_split('/CREATE TABLE[ IF NOT EXISTS]* `/',$data);
	unset($cr[0]);
	array_values($cr);
	
	//Explode file to find INSERT queries
	$in = explode('INSERT INTO ',$data);
	unset($in[0]);
	array_values($in);
	
	//Initializing
	$tnms = array();
	$tcrs = array();
	$tins = array();
	$tinsnms = array();
	$i = 0;
		
	
	foreach ($cr as $crt) {
		//Get table name
		$pos = strpos($crt,'`');
		$tnms[$i] = substr($crt,0,$pos);
		
		//Get CREATE query for table
		$pos = strpos($crt,';');
		$tcrs[$i] = 'CREATE TABLE IF NOT EXISTS `' .substr($crt,0,$pos+1);
		
		$i++;
	}
	
	$i=0;
	foreach ($in as $int) {
		//Get INSERT queries
		$pos = strpos($int,';');
		$tins[$i] = 'INSERT INTO ' .substr($int,0,$pos+1);
		
		//Get table name of INSERT query
		$tmp = explode('`',$tins[$i]);
		$tinsnms[$i] = $tmp[1];
		$i++;
	}
	
	//Compare INSERT and CREATE tablen names
	for ($i=0;$i<count($tnms);$i++) {
		if ($tinsnms[$i] != $tnms[$i]) {
			//Synchronize Insert and Create tables
			$tins = Correct_Table_Order($tins,$tinsnms,$tnms);
			break;
		}
	}	
	
	//Pass variables into SESSION
	$_SESSION['t_names'] = $tnms;
	$_SESSION['t_creates'] = $tcrs;
	$_SESSION['t_inserts'] = $tins;
	$_SESSION['t_select'] = TRUE;
		
	unset($tnms);
	unset($tcrs);
	unset($tins);
	
	function Correct_Table_Order($array,$old,$new,$pos) {
		$final = array();
		for ($i=0;$i<count($array);$i++) {
			//if ins name != cr name
			if ($old[$i] != $new[$i]) {
				for ($k=$i;$k<count($new);$k++) {
					//find ins name [i] = cr name [k]
					if ($old[$i] == $new[$k]) {
						//save at [k] the current query [i]
						$final[$k] = $array[$i];
						break;
					}
				}
			} else {
				$final[$i] = $array[$i];
			}
		}
		return $final;
	}
?>