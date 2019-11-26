<?php
/**
 * Tests for methods in URL class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;

/**
 * Tests for methods in URL class
 *
 * @package PhpMyAdmin-test
 */
class UrlTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        unset($_COOKIE['pma_lang']);
    }

    /**
     * Test for Url::getCommon for DB only
     *
     * @return void
     */
    public function testDbOnly()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . htmlentities($separator) . 'lang=en' ;

        $expected = '?db=db'
            . htmlentities($separator) . $expected;

        $this->assertEquals($expected, Url::getCommon(['db' => 'db']));
    }

    /**
     * Test for Url::getCommon with new style
     *
     * @return void
     */
    public function testNewStyle()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . htmlentities($separator) . 'lang=en' ;

        $expected = '?db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;
        $params = [
            'db' => 'db',
            'table' => 'table',
        ];
        $this->assertEquals($expected, Url::getCommon($params));
    }

    /**
     * Test for Url::getCommon with alternate divider
     *
     * @return void
     */
    public function testWithAlternateDivider()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . $separator . 'lang=en' ;

        $expected = '#ABC#db=db' . $separator . 'table=table' . $separator
            . $expected;
        $this->assertEquals(
            $expected,
            Url::getCommonRaw(
                [
                    'db' => 'db',
                    'table' => 'table',
                ],
                '#ABC#'
            )
        );
    }

    /**
     * Test for Url::getCommon
     *
     * @return void
     */
    public function testDefault()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = '?server=x' . htmlentities($separator) . 'lang=en' ;
        $this->assertEquals($expected, Url::getCommon());
    }

    /**
     * @param string      $expected   Expected result.
     * @param string|null $https      HTTPS value of $_SERVER array.
     * @param string|null $host       HTTP_HOST value of $_SERVER array.
     * @param string|null $port       SERVER_PORT value of $_SERVER array.
     * @param string|null $serverName SERVER_NAME value of $_SERVER array.
     * @param string|null $scriptName SCRIPT_NAME value of $_SERVER array.
     *
     * @return void
     *
     * @dataProvider getBaseUrlProvider
     */
    public function testGetBaseUrl(
        string $expected,
        ?string $https,
        ?string $host,
        ?string $port,
        ?string $serverName,
        ?string $scriptName
    ): void {
        $_SERVER['HTTPS'] = $https;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SERVER_PORT'] = $port;
        $_SERVER['SERVER_NAME'] = $serverName;
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $url = Url::getBaseUrl();
        $this->assertEquals($expected, $url);
    }

    /**
     * @return array
     */
    public function getBaseUrlProvider(): array
    {
        return [
            ['http://localhost', null, 'localhost', null, null, null],
            ['http://localhost', 'off', 'localhost', '80', null, null],
            ['http://localhost:123', '', 'localhost', '123', null, null],
            ['https://localhost', 'on', 'localhost', '443', null, null],
            ['https://localhost:123', 'on', 'localhost', '123', null, null],
            ['http://localhost:321', null, 'localhost:123', '321', '0.0.0.0', null],
            ['http://0.0.0.0', null, null, '80', '0.0.0.0', null],
            ['http://0.0.0.0:123', null, null, '123', '0.0.0.0', null],
            ['http://localhost', null, 'localhost', '80', null, '/index.php'],
            ['http://localhost/phpmyadmin', null, 'localhost', '80', null, '/phpmyadmin/index.php'],
            ['http://localhost:123', null, 'localhost', '123', null, '/index.php'],
            ['http://localhost:123/phpmyadmin', null, 'localhost', '123', null, '/phpmyadmin/index.php'],
        ];
    }
}
