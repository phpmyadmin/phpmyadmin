<?php 
	$data = fread($import_handle,36782*8);
	
	$cr = preg_split('/CREATE TABLE[ IF NOT EXISTS]* `/',$data);
	unset($cr[0]);
	array_values($cr);
	
	$in = explode('INSERT INTO ',$data);
	unset($in[0]);
	array_values($in);
	
	$tnms = array();
	$tcrs = array();
	$tins = array();
	
	$i = 0;
		
	foreach ($cr as $crt) {
		$pos = strpos($crt,'`');
		$tnms[$i] = substr($crt,0,$pos);
		
		$pos = strpos($crt,';');
		$tcrs[$i] = 'CREATE TABLE IF NOT EXISTS `' .substr($crt,0,$pos+1);
		
		$i++;
	}
	$i=0;
	foreach ($in as $int) {
		$pos = strpos($int,';');
		$tins[$i] = 'INSERT INTO ' .substr($int,0,$pos+1);
		
		$i++;
	}
	
	
	$_SESSION['t_names'] = $tnms;
	$_SESSION['t_creates'] = $tcrs;
	$_SESSION['t_inserts'] = $tins;
	
	unset($tnms);
	unset($tcrs);
	unset($tins);
	
	
	$_SESSION['t_select'] = TRUE;
?>