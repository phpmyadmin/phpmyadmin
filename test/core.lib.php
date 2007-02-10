<?php
/* $Id: common.lib.php 9832 2007-01-09 09:50:49Z nijel $ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Core testing library to wrap phpMyAdmin and add some useful functions.
 *
 * @author Michal Čihař <michal@cihar.com>
 * @package phpMyAdmin-test
 */

/**
 * Go to root directory.
 */
chdir('..');


/**
 * Report failed test.
 *
 * @param string function to test
 * @param string test description
 * @param string failure description
 */
function PMA_test_fail($function, $test, $message) {
	$function = htmlspecialchars($function);
	$test = htmlspecialchars($test);
	$message = htmlspecialchars($message);
	echo <<<EOT
<dt>$function ($test)</dt>
<dd><strong>Failed:</strong> $message</dd>
EOT;
}

/**
 * Report ok test.
 *
 * @param string function to test
 * @param string test description
 */
function PMA_test_okay($function, $test) {
	$function = htmlspecialchars($function);
	$test = htmlspecialchars($test);
	echo <<<EOT
<dt>$function ($test)</dt>
<dd><strong>OK</strong></dd>
EOT;
}

/**
 * Function for testing strings.
 *
 * @uses    PMA_test_okay()
 * @uses    PMA_test_fail()
 * @param string function to test
 * @param string test description
 * @param string actual result
 * @param string expected result
 */
function PMA_test_string($function, $test, $received, $expected) {
	if ($received != $expected) {
		PMA_test_fail($function, $test, "Strings >$received< and >$expected< do not match");
	} else {
		PMA_test_okay($function, $test);
	}
}
?>
