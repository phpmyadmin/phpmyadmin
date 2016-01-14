<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PMA_securePath changes .. to .
 *
 * @package PhpMyAdmin-test
 */


/**
 * Test for securing path.
 *
 * @package PhpMyAdmin-test
 */
class PMA_SecurePath extends PHPUnit_Framework_TestCase
{
    /**
     * Test for replacing dots.
     *
     * @return void
     */
    public function testReplaceDots()
    {
        $this->assertEquals(
            PMA_securePath('../../../etc/passwd'),
            './././etc/passwd'
        );
        $this->assertEquals(
            PMA_securePath('/var/www/../phpmyadmin'),
            '/var/www/./phpmyadmin'
        );
        $this->assertEquals(
            PMA_securePath('./path/with..dots/../../file..php'),
            './path/with.dots/././file.php'
        );
    }

}
