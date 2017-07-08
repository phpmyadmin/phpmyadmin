<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Util class
 *
 * @package PhpMyAdmin-test
 */

use PhpMyAdmin\Util;

require_once 'test/PMATestCase.php';

/**
 * Test for PhpMyAdmin\Util class
 *
 * @package PhpMyAdmin-test
 */
class UtilTest extends PMATestCase
{

    /**
     * Test for createGISData
     *
     * @return void
     */
    public function testCreateGISData()
    {
        $this->assertEquals(
            "abc",
            Util::createGISData("abc")
        );
        $this->assertEquals(
            "GeomFromText('POINT()',10)",
            Util::createGISData("'POINT()',10")
        );
    }

    /**
     * Test for getGISFunctions
     *
     * @return void
     */
    public function testGetGISFunctions()
    {
        $funcs = Util::getGISFunctions();
        $this->assertArrayHasKey(
            'Dimension',
            $funcs
        );
        $this->assertArrayHasKey(
            'GeometryType',
            $funcs
        );
        $this->assertArrayHasKey(
            'MBRDisjoint',
            $funcs
        );
    }

    /**
     * Test for Page Selector
     *
     * @return void
     */
    public function testPageSelector()
    {
        $this->assertContains(
            '<select class="pageselector ajax" name="pma" >',
            Util::pageselector("pma", 3)
        );
    }

    /**
     * Test for isForeignKeyCheck
     *
     * @return void
     */
    public function testIsForeignKeyCheck()
    {
        $GLOBALS['server'] = 1;

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'enable';
        $this->assertEquals(
            true,
            Util::isForeignKeyCheck()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'disable';
        $this->assertEquals(
            false,
            Util::isForeignKeyCheck()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'default';
        $this->assertEquals(
            true,
            Util::isForeignKeyCheck()
        );
    }

    /**
     * Test for getCharsetQueryPart
     *
     * @param string $collation Collation
     * @param string $expected  Expected Charset Query
     *
     * @return void
     * @test
     * @dataProvider charsetQueryData
     */
    public function testGenerateCharsetQueryPart($collation, $expected)
    {
        $this->assertEquals(
            $expected,
            Util::getCharsetQueryPart($collation)
        );
    }

    /**
     * Data Provider for testgetCharsetQueryPart
     *
     * @return array test data
     */
    public function charsetQueryData()
    {
        return array(
            array("a_b_c_d", " CHARSET=a COLLATE a_b_c_d"),
            array("a_", " CHARSET=a COLLATE a_"),
            array("a", " CHARSET=a"),
        );
    }

    /**
     * Test for isForeignKeySupported
     *
     * @return void
     */
    public function testIsForeignKeySupported()
    {
        $GLOBALS['server'] = 1;

        $this->assertTrue(
            Util::isForeignKeySupported('innodb')
        );
        $this->assertFalse(
            Util::isForeignKeySupported('myisam')
        );
        $this->assertTrue(
            Util::isForeignKeySupported('ndb')
        );
    }

    /**
     * Skip test if CURL extension is not installed
     *
     * @param boolean $ssl_flags Whether to check support for SSL flags
     *
     * @return void
     */
    public function checkCurl($ssl_flags = false)
    {
        if (! function_exists('curl_init')) {
            $this->markTestSkipped('curl not supported');
        }
        if ($ssl_flags) {
            $curl = curl_version();
            /*
             * Some SSL engines in CURL do not support CURLOPT_CAPATH
             * and CURLOPT_CAINFO flags, see
             * https://curl.haxx.se/docs/ssl-compared.html
             */
            if (stripos($curl['ssl_version'], 'WinSSL') !== false
                || stripos($curl['ssl_version'], 'SecureTransport') !== false
            ) {
                $this->markTestSkipped('Not supported in CURL SSL backend: ' . $curl['ssl_version']);
            }
        }
    }

    /**
     * Test for http request using Curl
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequestCurl($url, $method, $return_only_status, $expected)
    {
        $this->checkCurl();
        $result = Util::httpRequestCurl($url, $method, $return_only_status);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using Curl with CURLOPT_CAPATH
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequestCurlCAPath($url, $method, $return_only_status, $expected)
    {
        $this->checkCurl(true);
        $result = Util::httpRequestCurl($url, $method, $return_only_status, null, '', CURLOPT_CAPATH);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using Curl with CURLOPT_CAINFO
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequestCurlCAInfo($url, $method, $return_only_status, $expected)
    {
        $this->checkCurl(true);
        $result = Util::httpRequestCurl($url, $method, $return_only_status, null, '', CURLOPT_CAINFO);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using fopen
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequestFopen($url, $method, $return_only_status, $expected)
    {
        if (! ini_get('allow_url_fopen')) {
            $this->markTestSkipped('allow_url_fopen not supported');
        }
        $result = Util::httpRequestFopen($url, $method, $return_only_status);
        $this->validateHttp($result, $expected);
    }


    /**
     * Test for http request using generic interface
     *
     * @group medium
     *
     * @return void
     *
     * @dataProvider httpRequests
     *
     * @group network
     */
    public function testHttpRequest($url, $method, $return_only_status, $expected)
    {
        if (! function_exists('curl_init') && ! ini_get('allow_url_fopen')) {
            $this->markTestSkipped('neither curl nor allow_url_fopen are supported');
        }
        $result = Util::httpRequest($url, $method, $return_only_status);
        $this->validateHttp($result, $expected);
    }

    /**
     * Method to check http test results
     *
     * @param mixed $result   Result of HTTP request
     * @param mixed $expected Expected match
     *
     * @return void
     */
    private function validateHttp($result, $expected)
    {
        if ($expected === true) {
            $this->assertTrue($result);
        } elseif ($expected === false) {
            $this->assertFalse($result);
        } elseif ($expected === null) {
            $this->assertNull($result);
        } else {
            $this->assertContains($expected, $result);
        }
    }

    /**
     * Data provider for HTTP tests
     *
     * @return array
     */
    public function httpRequests()
    {
        return array(
            array("https://www.phpmyadmin.net/test/data", "GET", true, true),
            array("https://www.phpmyadmin.net/test/data", "POST", true, null),
            array("https://nonexisting.phpmyadmin.net/test/data", "GET", true, null),
            array("https://www.phpmyadmin.net/test/data","GET", false, "TEST DATA"),
            array("https://www.phpmyadmin.net/test/nothing","GET", true, false),
        );
    }

    /**
     * Test for random generation
     */
    public function testGenerateRandom()
    {
        $this->assertEquals(32, strlen(Util::generateRandom(32)));
        $this->assertEquals(16, strlen(Util::generateRandom(16)));
    }
}
