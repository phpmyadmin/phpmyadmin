<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use Composer\CaBundle\CaBundle;
use PhpMyAdmin\Config;
use PhpMyAdmin\Http\RequestMethod;

use function base64_encode;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function file_get_contents;
use function function_exists;
use function getenv;
use function ini_get;
use function is_array;
use function is_dir;
use function parse_url;
use function preg_match;
use function stream_context_create;

use const CURL_IPRESOLVE_V4;
use const CURLINFO_HTTP_CODE;
use const CURLOPT_CAINFO;
use const CURLOPT_CAPATH;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_IPRESOLVE;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_PROXY;
use const CURLOPT_PROXYUSERPWD;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_USERAGENT;
use const PHP_SAPI;

/**
 * Handles HTTP requests
 */
class HttpRequest
{
    private string $proxyUrl;

    private string $proxyUser;

    private string $proxyPass;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->proxyUrl = $config->settings['ProxyUrl'];
        $this->proxyUser = $config->settings['ProxyUser'];
        $this->proxyPass = $config->settings['ProxyPass'];
    }

    public static function setProxySettingsFromEnv(): void
    {
        $httpProxy = getenv('http_proxy');
        $urlInfo = parse_url((string) $httpProxy);
        if (PHP_SAPI !== 'cli' || ! is_array($urlInfo)) {
            return;
        }

        $config = Config::getInstance();
        $config->settings['ProxyUrl'] = ($urlInfo['host'] ?? '')
            . (isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '');
        $config->settings['ProxyUser'] = $urlInfo['user'] ?? '';
        $config->settings['ProxyPass'] = $urlInfo['pass'] ?? '';
    }

    /**
     * Returns information with regards to handling the http request
     *
     * @param mixed[] $context Data about the context for which
     *                       to http request is sent
     *
     * @return mixed[] of updated context information
     */
    private function handleContext(array $context): array
    {
        if ($this->proxyUrl !== '') {
            $context['http'] = ['proxy' => $this->proxyUrl, 'request_fulluri' => true];
            if ($this->proxyUser !== '') {
                $auth = base64_encode($this->proxyUser . ':' . $this->proxyPass);
                $context['http']['header'] = 'Proxy-Authorization: Basic '
                    . $auth . "\r\n";
            }
        }

        return $context;
    }

    /**
     * Creates HTTP request using curl
     *
     * @param mixed $response         HTTP response
     * @param int   $httpStatus       HTTP response status code
     * @param bool  $returnOnlyStatus If set to true, the method would only return response status
     */
    private function response(
        mixed $response,
        int $httpStatus,
        bool $returnOnlyStatus,
    ): string|bool|null {
        if ($httpStatus === 404) {
            return false;
        }

        if ($httpStatus !== 200) {
            return null;
        }

        if ($returnOnlyStatus) {
            return true;
        }

        return $response;
    }

    /**
     * Creates HTTP request using curl
     *
     * @param string        $url              Url to send the request
     * @param RequestMethod $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool          $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed         $content          Content to be sent with HTTP request
     * @param string        $header           Header to be set for the HTTP request
     */
    private function curl(
        string $url,
        RequestMethod $method,
        bool $returnOnlyStatus = false,
        mixed $content = null,
        string $header = '',
    ): string|bool|null {
        $curlHandle = curl_init($url);
        if ($curlHandle === false) {
            return null;
        }

        $curlStatus = 1;
        if ($this->proxyUrl !== '') {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_PROXY, $this->proxyUrl);
            if ($this->proxyUser !== '') {
                $curlStatus &= (int) curl_setopt(
                    $curlHandle,
                    CURLOPT_PROXYUSERPWD,
                    $this->proxyUser . ':' . $this->proxyPass,
                );
            }
        }

        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_USERAGENT, 'phpMyAdmin');

        if ($method !== RequestMethod::Get) {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $method->value);
        }

        if ($header !== '') {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [$header]);
        }

        if ($method === RequestMethod::Post) {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $content);
        }

        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true);

        $caPathOrFile = CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_CAINFO, $caPathOrFile);
        }

        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 0);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
        $curlStatus &= (int) curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 10);

        if ($curlStatus === 0) {
            return null;
        }

        $response = @curl_exec($curlHandle);
        if ($response === false) {
            return null;
        }

        $httpStatus = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

        return $this->response($response, $httpStatus, $returnOnlyStatus);
    }

    /**
     * Creates HTTP request using file_get_contents
     *
     * @param string        $url              Url to send the request
     * @param RequestMethod $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool          $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed         $content          Content to be sent with HTTP request
     * @param string        $header           Header to be set for the HTTP request
     */
    private function fopen(
        string $url,
        RequestMethod $method,
        bool $returnOnlyStatus = false,
        mixed $content = null,
        string $header = '',
    ): string|bool|null {
        $context = [
            'http' => [
                'method' => $method->value,
                'request_fulluri' => true,
                'timeout' => 10,
                'user_agent' => 'phpMyAdmin',
                'header' => 'Accept: */*',
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ];
        if ($header !== '') {
            $context['http']['header'] .= "\n" . $header;
        }

        if ($method === RequestMethod::Post) {
            $context['http']['content'] = $content;
        }

        $caPathOrFile = CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            $context['ssl']['capath'] = $caPathOrFile;
        } else {
            $context['ssl']['cafile'] = $caPathOrFile;
        }

        $context = $this->handleContext($context);
        $response = @file_get_contents(
            $url,
            false,
            stream_context_create($context),
        );

        if (! isset($http_response_header)) {
            return null;
        }

        preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $http_response_header[0], $out);
        $httpStatus = (int) $out[1];

        return $this->response($response, $httpStatus, $returnOnlyStatus);
    }

    /**
     * Creates HTTP request
     *
     * @param string        $url              Url to send the request
     * @param RequestMethod $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool          $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed         $content          Content to be sent with HTTP request
     * @param string        $header           Header to be set for the HTTP request
     */
    public function create(
        string $url,
        RequestMethod $method,
        bool $returnOnlyStatus = false,
        mixed $content = null,
        string $header = '',
    ): string|bool|null {
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            return $this->curl($url, $method, $returnOnlyStatus, $content, $header);
        }

        if (ini_get('allow_url_fopen')) {
            return $this->fopen($url, $method, $returnOnlyStatus, $content, $header);
        }

        return null;
    }
}
