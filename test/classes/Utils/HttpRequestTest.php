<?php
/**
 * tests for PhpMyAdmin\Utils\HttpRequest class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Utils\HttpRequest;
use const CURLOPT_CAINFO;
use const CURLOPT_CAPATH;
use function curl_version;
use function function_exists;
use function ini_get;
use function stripos;

class HttpRequestTest extends PmaTestCase
{
    /** @var HttpRequest */
    private $httpRequest;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setProxySettings();
        $this->httpRequest = new HttpRequest();
    }

    /**
     * Skip test if CURL extension is not installed
     *
     * @param bool $ssl_flags Whether to check support for SSL flags
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
     * @param string $url                url
     * @param string $method             method
     * @param bool   $return_only_status return only status
     * @param bool   $expected           expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     */
    public function testCurl($url, $method, $return_only_status, $expected): void
    {
        $this->checkCurl();
        $result = $this->callFunction(
            $this->httpRequest,
            HttpRequest::class,
            'curl',
            [$url, $method, $return_only_status]
        );
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using Curl with CURLOPT_CAPATH
     *
     * @param string $url                url
     * @param string $method             method
     * @param bool   $return_only_status return only status
     * @param bool   $expected           expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     */
    public function testCurlCAPath($url, $method, $return_only_status, $expected): void
    {
        $this->checkCurl(true);
        $result = $this->callFunction($this->httpRequest, HttpRequest::class, 'curl', [
            $url,
            $method,
            $return_only_status,
            null,
            '',
            CURLOPT_CAPATH,
        ]);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using Curl with CURLOPT_CAINFO
     *
     * @param string $url                url
     * @param string $method             method
     * @param bool   $return_only_status return only status
     * @param bool   $expected           expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     */
    public function testCurlCAInfo($url, $method, $return_only_status, $expected): void
    {
        $this->checkCurl(true);
        $result = $this->callFunction($this->httpRequest, HttpRequest::class, 'curl', [
            $url,
            $method,
            $return_only_status,
            null,
            '',
            CURLOPT_CAINFO,
        ]);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using fopen
     *
     * @param string $url                url
     * @param string $method             method
     * @param bool   $return_only_status return only status
     * @param bool   $expected           expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     */
    public function testFopen($url, $method, $return_only_status, $expected): void
    {
        if (! ini_get('allow_url_fopen')) {
            $this->markTestSkipped('allow_url_fopen not supported');
        }
        $result = $this->callFunction(
            $this->httpRequest,
            HttpRequest::class,
            'fopen',
            [$url, $method, $return_only_status]
        );
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using generic interface
     *
     * @param string $url                url
     * @param string $method             method
     * @param bool   $return_only_status return only status
     * @param bool   $expected           expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     */
    public function testCreate($url, $method, $return_only_status, $expected): void
    {
        if (! function_exists('curl_init') && ! ini_get('allow_url_fopen')) {
            $this->markTestSkipped('neither curl nor allow_url_fopen are supported');
        }
        $result = $this->httpRequest->create($url, $method, $return_only_status);
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
            $this->assertStringContainsString($expected, $result);
        }
    }

    /**
     * Data provider for HTTP tests
     *
     * @return array
     */
    public function httpRequests()
    {
        return [
            [
                'https://www.phpmyadmin.net/test/data',
                'GET',
                true,
                true,
            ],
            [
                'https://www.phpmyadmin.net/test/data',
                'POST',
                true,
                null,
            ],
            [
                'https://nonexisting.phpmyadmin.net/test/data',
                'GET',
                true,
                null,
            ],
            [
                'https://www.phpmyadmin.net/test/data',
                'GET',
                false,
                'TEST DATA',
            ],
            [
                'https://www.phpmyadmin.net/test/nothing',
                'GET',
                true,
                false,
            ],
        ];
    }
}
