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

/**
 * if there were any errors then take appropriate actions based on user preferences.
 */
if($GLOBALS['error_handler']->hasErrors()) {
	// Delete all the prev_errors in session & store new prev_errors in session
	$GLOBALS['error_handler']->savePreviousErrors();

	if($GLOBALS['cfg']['SendErrorReports'] == 'always'){
		//send the error reports directly

	    $_REQUEST['exception_type'] = 'php';
	    $_REQUEST['send_error_report'] = '1';
	    require_once('error_report.php');

	    // The errors are already sent. Just focus on errors division upon load event.
		$jsCode = '$("html, body").animate({scrollTop:$(document).height()}, "slow");';
		$response = PMA_Response::getInstance();
		$response->getFooter()->getScripts()->addCode($jsCode);
	}
	elseif($GLOBALS['cfg']['SendErrorReports'] == 'ask') {
		//ask user whether to submit errors or not.
		if($response->isAjax()) {
			// Send the errors in '_error' param. That is Already done in PMA_Response::_ajaxResponse().
		}
		else {
			// The errors are already sent. Just focus on errors division upon load event.
			$jsCode = 'PMA_ajaxShowMessage(PMA_messages["phpErrorsFound"], 2000);'
					. '$("html, body").animate({scrollTop:$(document).height()}, "slow");';
					
			$response = PMA_Response::getInstance();
			$response->getFooter()->getScripts()->addCode($jsCode);
		}

	}
	else {
		//$GLOBALS['cfg']['SendErrorReports'] set to 'never'. Do not submit error reports.

	}
}
?>
