<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * runs all defined tests
 *
 * @version $Id: AllTests.php 12036 2008-11-30 11:49:44Z lem9 $
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'AllTests.php';

echo '<pre>';
AllTests::main();
echo '</pre>';

?>