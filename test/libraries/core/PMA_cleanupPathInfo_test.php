<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * PMA_fatalError() displays the given error message on phpMyAdmin error page in
 * foreign language
 * and ends script execution and closes session
 *
 * @package PhpMyAdmin-test
 */




/**
 *
 * PMA_fatalError() displays the given error message on phpMyAdmin error page in
 * foreign language
 * and ends script execution and closes session
 *
 * @package PhpMyAdmin-test
 */
class PMA_CleanupPathInfo_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_cleanupPathInfo
     *
     * @param string $php_self  The PHP_SELF value
     * @param string $request   The REQUEST_URI value
     * @param string $path_info The PATH_INFO value
     * @param string $expected  Expected result
     *
     * @return void
     *
     * @dataProvider pathsProvider
     */
    public function testPahtInfo($php_self, $request, $path_info, $expected)
    {
        $_SERVER['PHP_SELF'] = $php_self;
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['PATH_INFO'] = $path_info;
        PMA_cleanupPathInfo();
        $this->assertEquals(
            $expected,
            $GLOBALS['PMA_PHP_SELF']
        );
    }

    /**
     * Data provider for PMA_cleanupPathInfo tests
     *
     * @return array
     */
    public function pathsProvider()
    {
        return array(
            array(
                '/phpmyadmin/index.php/; cookieinj=value/',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '//example.com/../phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '//example.com/../../.././phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '/page.php/malicouspathinfo?malicouspathinfo',
                'malicouspathinfo',
                '/page.php'
            ),
            array(
                '/phpmyadmin/./index.php',
                '/phpmyadmin/./index.php',
                '',
                '/phpmyadmin/index.php'
            ),
            array(
                '/phpmyadmin/index.php',
                '/phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php'
            ),
            array(
                '',
                '/phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php'
            ),
        );
    }
}

