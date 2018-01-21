<?php

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Utils\HttpRequest;
use PhpMyAdmin\Tests\PmaTestCase;

class HttpRequestTest extends PmaTestCase
{
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
        $result = HttpRequest::httpRequestCurl($url, $method, $return_only_status);
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
        $result = HttpRequest::httpRequestCurl($url, $method, $return_only_status, null, '', CURLOPT_CAPATH);
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
        $result = HttpRequest::httpRequestCurl($url, $method, $return_only_status, null, '', CURLOPT_CAINFO);
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
        $result = HttpRequest::httpRequestFopen($url, $method, $return_only_status);
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
        $result = HttpRequest::httpRequest($url, $method, $return_only_status);
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
}
