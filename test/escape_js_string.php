<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Test for javascript escaping.
 *
 * @author Michal Čihař <michal@cihar.com>
 * @package phpMyAdmin-test
 */

/**
 * Tests core.
 */
include('./core.lib.php');
/**
 * Include to test.
 */
include('./libraries/js_escape.lib.php');

/**
 * Test java script escaping.
 *
 * @uses    PMA_escapeJsString()
 * @uses    PMA_test_string()
 * @param string string to escape
 * @param string expected result
 */
function PMA_test_escape($test, $expected) {
    PMA_test_string('PMA_escapeJsString', $test, PMA_escapeJsString($test), $expected);
}

PMA_test_escape('\';', '\\\';');
PMA_test_escape("\r\n'<scrIpt></sCRIPT>", '\r\n\\\'<scrIpt></\' + \'script>');
PMA_test_escape('\';[XSS]', '\\\';[XSS]');
PMA_test_escape('</SCRIPT></head><body>[HTML]', '</\' + \'script></head><body>[HTML]');
PMA_test_escape('"\'\\\'"', '"\\\'\\\\\\\'"');
PMA_test_escape("\\''''''''''''\\", "\\\\\'\'\'\'\'\'\'\'\'\'\'\'\\\\");
?>
