<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_securePath() from libraries/core.lib.php
 * PMA_securePath changes .. to .
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/core.lib.php';

class PMA_securePath extends PHPUnit_Framework_TestCase
{
    public function testReplaceDots()
    {
        $this->assertEquals(PMA_securePath('../../../etc/passwd'), './././etc/passwd');
        $this->assertEquals(PMA_securePath('/var/www/../phpmyadmin'), '/var/www/./phpmyadmin');
        $this->assertEquals(PMA_securePath('./path/with..dots/../../file..php'), './path/with.dots/././file.php');
    }

}
