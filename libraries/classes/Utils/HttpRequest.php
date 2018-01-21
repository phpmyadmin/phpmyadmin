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
    /**
     * Returns information with regards to handling the http request
     *
     * @param array $context Data about the context for which
     *                       to http request is sent
     *
     * @return array of updated context information
     */
    public static function handleContext(array $context)
    {
        if (strlen($GLOBALS['cfg']['ProxyUrl']) > 0) {
            $context['http'] = array(
                'proxy' => $GLOBALS['cfg']['ProxyUrl'],
                'request_fulluri' => true
            );
            if (strlen($GLOBALS['cfg']['ProxyUser']) > 0) {
                $auth = base64_encode(
                    $GLOBALS['cfg']['ProxyUser'] . ':' . $GLOBALS['cfg']['ProxyPass']
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
     * @param mixed    $response           HTTP response
     * @param interger $http_status        HTTP response status code
     * @param bool     $return_only_status If set to true, the method would only return response status
     *
     * @return mixed
     */
    public static function httpRequestReturn($response, $http_status, $return_only_status)
    {
        if ($http_status == 404) {
            return false;
        }
        if ($http_status != 200) {
            return null;
        }
        if ($return_only_status) {
            return true;
        }
        return $response;
    }

    /**
     * Creates HTTP request using curl
     *
     * @param string $url                Url to send the request
     * @param string $method             HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $return_only_status If set to true, the method would only return response status
     * @param mixed  $content            Content to be sent with HTTP request
     * @param string $header             Header to be set for the HTTP request
     * @param int    $ssl                SSL mode to use
     *
     * @return mixed
     */
    public static function httpRequestCurl($url, $method, $return_only_status = false, $content = null, $header = "", $ssl = 0)
    {
        $curl_handle = curl_init($url);
        if ($curl_handle === false) {
            return null;
        }
        $curl_status = true;
        if (strlen($GLOBALS['cfg']['ProxyUrl']) > 0) {
            $curl_status &= curl_setopt($curl_handle, CURLOPT_PROXY, $GLOBALS['cfg']['ProxyUrl']);
            if (strlen($GLOBALS['cfg']['ProxyUser']) > 0) {
                $curl_status &= curl_setopt(
                    $curl_handle,
                    CURLOPT_PROXYUSERPWD,
                    $GLOBALS['cfg']['ProxyUser'] . ':' . $GLOBALS['cfg']['ProxyPass']
                );
            }
        }
        $curl_status &= curl_setopt($curl_handle, CURLOPT_USERAGENT, 'phpMyAdmin');

        if ($method != "GET") {
            $curl_status &= curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($header) {
            $curl_status &= curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array($header));
        }

        if ($method == "POST") {
            $curl_status &= curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $content);
        }

        $curl_status &= curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, '2');
        $curl_status &= curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, '1');

        /**
         * Configure ISRG Root X1 to be able to verify Let's Encrypt SSL
         * certificates even without properly configured curl in PHP.
         *
         * See https://letsencrypt.org/certificates/
         */
        $certs_dir = dirname(__file__) . '/../../certs/';
        /* See code below for logic */
        if ($ssl == CURLOPT_CAPATH) {
            $curl_status &= curl_setopt($curl_handle, CURLOPT_CAPATH, $certs_dir);
        } elseif ($ssl == CURLOPT_CAINFO) {
            $curl_status &= curl_setopt($curl_handle, CURLOPT_CAINFO, $certs_dir . 'cacert.pem');
        }

        $curl_status &= curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,true);
        $curl_status &= curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 0);
        $curl_status &= curl_setopt($curl_handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $curl_status &= curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);
        $curl_status &= curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 10);

        if (! $curl_status) {
            return null;
        }
        $response = @curl_exec($curl_handle);
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
            if (curl_getinfo($curl_handle, CURLINFO_SSL_VERIFYRESULT) != 0) {
                if ($ssl == 0) {
                    self::httpRequestCurl($url, $method, $return_only_status, $content, $header, CURLOPT_CAINFO);
                } elseif ($ssl == CURLOPT_CAINFO) {
                    self::httpRequestCurl($url, $method, $return_only_status, $content, $header, CURLOPT_CAPATH);
                }
            }
            return null;
        }
        $http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        return self::httpRequestReturn($response, $http_status, $return_only_status);
    }

    /**
     * Creates HTTP request using file_get_contents
     *
     * @param string $url                Url to send the request
     * @param string $method             HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $return_only_status If set to true, the method would only return response status
     * @param mixed  $content            Content to be sent with HTTP request
     * @param string $header             Header to be set for the HTTP request
     *
     * @return mixed
     */
    public static function httpRequestFopen($url, $method, $return_only_status = false, $content = null, $header = "")
    {
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

        $context = self::handleContext($context);
        $response = @file_get_contents(
            $url,
            false,
            stream_context_create($context)
        );
        if (! isset($http_response_header)) {
            return null;
        }
        preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $http_response_header[0], $out );
        $http_status = intval($out[1]);
        return self::httpRequestReturn($response, $http_status, $return_only_status);
    }

    /**
     * Creates HTTP request
     *
     * @param string $url                Url to send the request
     * @param string $method             HTTP request method (GET, POST, PUT, DELETE, etc)
     * @param bool   $return_only_status If set to true, the method would only return response status
     * @param mixed  $content            Content to be sent with HTTP request
     * @param string $header             Header to be set for the HTTP request
     *
     * @return mixed
     */
    public static function httpRequest($url, $method, $return_only_status = false, $content = null, $header = "")
    {
        if (function_exists('curl_init')) {
            return self::httpRequestCurl($url, $method, $return_only_status, $content, $header);
        } elseif (ini_get('allow_url_fopen')) {
            return self::httpRequestFopen($url, $method, $return_only_status, $content, $header);
        }
        return null;
    }
}
