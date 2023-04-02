<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Utils\HttpRequest;

use function curl_version;
use function ini_get;
use function stripos;

use const CURLOPT_CAINFO;
use const CURLOPT_CAPATH;

/** @covers \PhpMyAdmin\Utils\HttpRequest */
class HttpRequestTest extends AbstractTestCase
{
    private HttpRequest $httpRequest;

    protected function setUp(): void
    {
        parent::setUp();

        parent::setProxySettings();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $this->httpRequest = new HttpRequest();
    }

    /**
     * Skip test if CURL extension does not support SSL flags
     */
    private function checkCurlSslFlagsSupport(): void
    {
        $curl = curl_version();
        /**
         * Some SSL engines in CURL do not support CURLOPT_CAPATH
         * and CURLOPT_CAINFO flags, see
         * https://curl.haxx.se/docs/ssl-compared.html
         */
        if (
            $curl !== false && stripos($curl['ssl_version'], 'WinSSL') === false
            && stripos($curl['ssl_version'], 'SecureTransport') === false
        ) {
            return;
        }

        $this->markTestSkipped('Not supported in CURL SSL backend: ' . ($curl !== false ? $curl['ssl_version'] : '?'));
    }

    /**
     * Test for http request using Curl
     *
     * @param string           $url              url
     * @param string           $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     * @requires extension curl
     */
    public function testCurl(string $url, string $method, bool $returnOnlyStatus, bool|string|null $expected): void
    {
        $result = $this->callFunction(
            $this->httpRequest,
            HttpRequest::class,
            'curl',
            [$url, $method, $returnOnlyStatus],
        );
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using Curl with CURLOPT_CAPATH
     *
     * @param string           $url              url
     * @param string           $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     * @requires extension curl
     */
    public function testCurlCAPath(
        string $url,
        string $method,
        bool $returnOnlyStatus,
        bool|string|null $expected,
    ): void {
        $this->checkCurlSslFlagsSupport();
        $result = $this->callFunction($this->httpRequest, HttpRequest::class, 'curl', [
            $url,
            $method,
            $returnOnlyStatus,
            null,
            '',
            CURLOPT_CAPATH,
        ]);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using Curl with CURLOPT_CAINFO
     *
     * @param string           $url              url
     * @param string           $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     * @requires extension curl
     */
    public function testCurlCAInfo(
        string $url,
        string $method,
        bool $returnOnlyStatus,
        bool|string|null $expected,
    ): void {
        $this->checkCurlSslFlagsSupport();
        $result = $this->callFunction($this->httpRequest, HttpRequest::class, 'curl', [
            $url,
            $method,
            $returnOnlyStatus,
            null,
            '',
            CURLOPT_CAINFO,
        ]);
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using fopen
     *
     * @param string           $url              url
     * @param string           $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     */
    public function testFopen(string $url, string $method, bool $returnOnlyStatus, bool|string|null $expected): void
    {
        if (! ini_get('allow_url_fopen')) {
            $this->markTestSkipped('Configuration directive allow_url_fopen is not enabled.');
        }

        $result = $this->callFunction(
            $this->httpRequest,
            HttpRequest::class,
            'fopen',
            [$url, $method, $returnOnlyStatus],
        );
        $this->validateHttp($result, $expected);
    }

    /**
     * Test for http request using generic interface
     *
     * @param string           $url              url
     * @param string           $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     *
     * @group medium
     * @dataProvider httpRequests
     * @group network
     * @requires extension curl
     */
    public function testCreate(string $url, string $method, bool $returnOnlyStatus, bool|string|null $expected): void
    {
        if (! ini_get('allow_url_fopen')) {
            $this->markTestSkipped('Configuration directive allow_url_fopen is not enabled.');
        }

        $result = $this->httpRequest->create($url, $method, $returnOnlyStatus);
        $this->validateHttp($result, $expected);
    }

    /**
     * Method to check http test results
     *
     * @param mixed $result   Result of HTTP request
     * @param mixed $expected Expected match
     */
    private function validateHttp(mixed $result, mixed $expected): void
    {
        if ($expected === true) {
            $this->assertTrue($result);
        } elseif ($expected === false) {
            $this->assertFalse($result);
        } elseif ($expected === null) {
            $this->assertNull($result);
        } else {
            $this->assertNotNull($result, 'The request maybe has failed');
            $this->assertStringContainsString($expected, $result);
        }
    }

    /**
     * Data provider for HTTP tests
     *
     * @return mixed[][]
     */
    public static function httpRequests(): array
    {
        return [
            ['https://www.phpmyadmin.net/test/data', 'GET', true, true],
            ['https://www.phpmyadmin.net/test/data', 'POST', true, null],
            ['https://nonexisting.phpmyadmin.net/test/data', 'GET', true, null],
            ['https://www.phpmyadmin.net/test/data', 'GET', false, 'TEST DATA'],
            ['https://www.phpmyadmin.net/test/nothing', 'GET', true, false],
        ];
    }
}
