<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * php Error reporting script.
 * REQUIRED by ALL the scripts.
 * MUST be included by every script at the end.
 *
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/** if there were any errors currently write them to a log file. 
 *	Later it will be changed to submit them to the error reporting server.
 */
if($GLOBALS['error_handler']->hasErrors()) {
	// then log them in a local file.
	$path = "./pma_error_log.txt";
	$fp = fopen($path, "w");

	// for each errors in the list
	foreach($GLOBALS['error_handler']->getCurrentErrors() as $errObj ) {
		/**
		 * Following check is to avoid error reported by PMA_warnMissingExtension();
		 * 
		 */ 
		if ($errObj->getLine() && $errObj->getType()) {
			$str = "\n\n".$errObj->getFile() . "(#"  . $errObj->getLine() . ")\n\t". $errObj->getTitle();
			// for stack trace
			// ------------------------------------------------------------------------------------------
			$backtrace = $errObj->getBacktrace();
			$error_str= "";	
			foreach($backtrace as $i=>$stack_frame)
			{
			 	$error_str .= "\n \t\t\t Frame[".$i."]: \tfile:".$stack_frame["file"]."\tline:".$stack_frame["line"]."\tfunction:".$stack_frame["function"]."(";
		 		foreach($stack_frame["args"] as $j=>$arg)
		 		{
		 			if($j != 0)
		 			{
		 				$error_str .= ", ";
		 			}

		 			$error_str .= "arg[".$j."] = ".$arg;
		 		}
		 		$error_str .= ")";
			 	
			 	if($i >= 5)	// MAX 5 of stack frames.
			 	{
			 		break;
			 	}
			 }
			// ------------------------------------------------------------------------------------------
			$retVal = fwrite ($fp , $str);
			$retVal = fwrite ($fp , $error_str);
		}
	}
	fclose($fp);
}
?>
