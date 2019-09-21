<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold the PhpMyAdmin\Utils\HttpRequest class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Utils;

/**
 * Handles HTTP requests
 *
 * @package PhpMyAdmin
 */
class HttpRequest
{
    private $proxyUrl;
    private $proxyUser;
    private $proxyPass;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;

        $this->proxyUrl = $cfg['ProxyUrl'];
        $this->proxyUser = $cfg['ProxyUser'];
        $this->proxyPass = $cfg['ProxyPass'];
    }

    /**
     * Returns information with regards to handling the http request
     *
     * @param array $context Data about the context for which
     *                       to http request is sent
     *
     * @return array of updated context information
     */
    private function handleContext(array $context)
    {
        if (strlen($this->proxyUrl) > 0) {
            $context['http'] = array(
                'proxy' => $this->proxyUrl,
                'request_fulluri' => true
            );
            if (strlen($this->proxyUser) > 0) {
                $auth = base64_encode(
                    $this->proxyUser . ':' . $this->proxyPass
                );
                $context['http']['header'] .= 'Proxy-Authorization: Basic '
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
     *
     * @return string|null|bool
     */
    private function response(
        $response,
        $httpStatus,
        $returnOnlyStatus
    ) {
        if ($httpStatus == 404) {
            return false;
        }
        if ($httpStatus != 200) {
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
     * @param string $url              Url to send the request
     * @param string $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed  $content          Content to be sent with HTTP request
     * @param string $header           Header to be set for the HTTP request
     * @param int    $ssl              SSL mode to use
     *
     * @return string|null|bool
     */
    private function curl(
        $url,
        $method,
        $returnOnlyStatus = false,
        $content = null,
        $header = '',
        $ssl = 0
    ) {
        $curlHandle = curl_init($url);
        if ($curlHandle === false) {
            return null;
        }
        $curlStatus = true;
        if (strlen($this->proxyUrl) > 0) {
            $curlStatus &= curl_setopt($curlHandle, CURLOPT_PROXY, $this->proxyUrl);
            if (strlen($this->proxyUser) > 0) {
                $curlStatus &= curl_setopt(
                    $curlHandle,
                    CURLOPT_PROXYUSERPWD,
                    $this->proxyUser . ':' . $this->proxyPass
                );
            }
        }
        $curlStatus &= curl_setopt($curlHandle, CURLOPT_USERAGENT, 'phpMyAdmin');

        if ($method != "GET") {
            $curlStatus &= curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($header) {
            $curlStatus &= curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array($header));
        }

        if ($method == "POST") {
            $curlStatus &= curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $content);
        }

        $curlStatus &= curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, '2');
        $curlStatus &= curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, '1');

        /**
         * Configure ISRG Root X1 to be able to verify Let's Encrypt SSL
         * certificates even without properly configured curl in PHP.
         *
         * See https://letsencrypt.org/certificates/
         */
        $certsDir = dirname(__file__) . '/../../certs/';
        /* See code below for logic */
        if ($ssl == CURLOPT_CAPATH) {
            $curlStatus &= curl_setopt($curlHandle, CURLOPT_CAPATH, $certsDir);
        } elseif ($ssl == CURLOPT_CAINFO) {
            $curlStatus &= curl_setopt($curlHandle, CURLOPT_CAINFO, $certsDir . 'cacert.pem');
        }

        $curlStatus &= curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $curlStatus &= curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, 0);
        $curlStatus &= curl_setopt($curlHandle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $curlStatus &= curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
        $curlStatus &= curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 10);

        if (! $curlStatus) {
            return null;
        }
        $response = @curl_exec($curlHandle);
        if ($response === false) {
            /*
             * In case of SSL verification failure let's try configuring curl
             * certificate verification. Unfortunately it is tricky as setting
             * options incompatible with PHP build settings can lead to failure.
             *
             * So let's rather try the options one by one.
             *
             * 1. Try using system SSL storage.
             * 2. Try setting CURLOPT_CAINFO.
             * 3. Try setting CURLOPT_CAPATH.
             * 4. Fail.
             */
            if (curl_getinfo($curlHandle, CURLINFO_SSL_VERIFYRESULT) != 0) {
                if ($ssl == 0) {
                    $this->curl($url, $method, $returnOnlyStatus, $content, $header, CURLOPT_CAINFO);
                } elseif ($ssl == CURLOPT_CAINFO) {
                    $this->curl($url, $method, $returnOnlyStatus, $content, $header, CURLOPT_CAPATH);
                }
            }
            return null;
        }
        $httpStatus = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        return $this->response($response, $httpStatus, $returnOnlyStatus);
    }

    /**
     * Creates HTTP request using file_get_contents
     *
     * @param string $url              Url to send the request
     * @param string $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed  $content          Content to be sent with HTTP request
     * @param string $header           Header to be set for the HTTP request
     *
     * @return string|null|bool
     */
    private function fopen(
        $url,
        $method,
        $returnOnlyStatus = false,
        $content = null,
        $header = ''
    ) {
        $context = array(
            'http' => array(
                'method'  => $method,
                'request_fulluri' => true,
                'timeout' => 10,
                'user_agent' => 'phpMyAdmin',
                'header' => "Accept: */*",
            )
        );
        if ($header) {
            $context['http']['header'] .= "\n" . $header;
        }
        if ($method == "POST") {
            $context['http']['content'] = $content;
        }
        $context = $this->handleContext($context);
        $response = @file_get_contents(
            $url,
            false,
            stream_context_create($context)
        );
        if (isset($http_response_header)) {
            preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $http_response_header[0], $out);
            $httpStatus = intval($out[1]);
            return $this->response($response, $httpStatus, $returnOnlyStatus);
        }
        return null;
    }

    /**
     * Creates HTTP request
     *
     * @param string $url              Url to send the request
     * @param string $method           HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $returnOnlyStatus If set to true, the method would only return response status
     * @param mixed  $content          Content to be sent with HTTP request
     * @param string $header           Header to be set for the HTTP request
     *
     * @return string|null|bool
     */
    public function create(
        $url,
        $method,
        $returnOnlyStatus = false,
        $content = null,
        $header = ''
    ) {
        if (function_exists('curl_init')) {
            return $this->curl($url, $method, $returnOnlyStatus, $content, $header);
        } elseif (ini_get('allow_url_fopen')) {
            return $this->fopen($url, $method, $returnOnlyStatus, $content, $header);
        }
        return null;
    }
}
