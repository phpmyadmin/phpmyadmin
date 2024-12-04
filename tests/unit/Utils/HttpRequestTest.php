<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\RequestMethod;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Utils\HttpRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function curl_version;
use function ini_get;
use function stripos;

use const CURLOPT_CAINFO;
use const CURLOPT_CAPATH;

#[CoversClass(HttpRequest::class)]
#[Medium]
class HttpRequestTest extends AbstractTestCase
{
    private HttpRequest $httpRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setProxySettings();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
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

        self::markTestSkipped('Not supported in CURL SSL backend: ' . ($curl !== false ? $curl['ssl_version'] : '?'));
    }

    /**
     * Test for http request using Curl
     *
     * @param string           $url              url
     * @param RequestMethod    $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     */
    #[DataProvider('httpRequests')]
    #[Group('network')]
    #[RequiresPhpExtension('curl')]
    public function testCurl(
        string $url,
        RequestMethod $method,
        bool $returnOnlyStatus,
        bool|string|null $expected,
    ): void {
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
     * @param RequestMethod    $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     */
    #[DataProvider('httpRequests')]
    #[Group('network')]
    #[RequiresPhpExtension('curl')]
    public function testCurlCAPath(
        string $url,
        RequestMethod $method,
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
     * @param RequestMethod    $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     */
    #[DataProvider('httpRequests')]
    #[Group('network')]
    #[RequiresPhpExtension('curl')]
    public function testCurlCAInfo(
        string $url,
        RequestMethod $method,
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
     * @param RequestMethod    $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     */
    #[DataProvider('httpRequests')]
    #[Group('network')]
    public function testFopen(
        string $url,
        RequestMethod $method,
        bool $returnOnlyStatus,
        bool|string|null $expected,
    ): void {
        if (! ini_get('allow_url_fopen')) {
            self::markTestSkipped('Configuration directive allow_url_fopen is not enabled.');
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
     * @param RequestMethod    $method           method
     * @param bool             $returnOnlyStatus return only status
     * @param bool|string|null $expected         expected result
     */
    #[DataProvider('httpRequests')]
    #[Group('network')]
    #[RequiresPhpExtension('curl')]
    public function testCreate(
        string $url,
        RequestMethod $method,
        bool $returnOnlyStatus,
        bool|string|null $expected,
    ): void {
        if (! ini_get('allow_url_fopen')) {
            self::markTestSkipped('Configuration directive allow_url_fopen is not enabled.');
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
            self::assertTrue($result);
        } elseif ($expected === false) {
            self::assertFalse($result);
        } elseif ($expected === null) {
            self::assertNull($result);
        } else {
            self::assertNotNull($result, 'The request maybe has failed');
            self::assertStringContainsString($expected, $result);
        }
    }

    /**
     * Data provider for HTTP tests
     *
     * @return list<array{string, RequestMethod, bool, bool|string|null}>
     */
    public static function httpRequests(): array
    {
        return [
            ['https://www.phpmyadmin.net/test/data', RequestMethod::Get, true, true],
            ['https://www.phpmyadmin.net/test/data', RequestMethod::Post, true, null],
            ['https://nonexisting.phpmyadmin.net/test/data', RequestMethod::Get, true, null],
            ['https://www.phpmyadmin.net/test/data', RequestMethod::Get, false, 'TEST DATA'],
            ['https://www.phpmyadmin.net/test/nothing', RequestMethod::Get, true, false],
        ];
    }
}
