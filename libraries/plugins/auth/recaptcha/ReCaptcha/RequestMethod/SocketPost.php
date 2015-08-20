<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * @copyright Copyright (c) 2015, Google Inc.
 * @link      http://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ReCaptcha\RequestMethod;

use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends a POST request to the reCAPTCHA service, but makes use of fsockopen() 
 * instead of get_file_contents(). This is to account for people who may be on 
 * servers where allow_furl_open is disabled.
 */
class SocketPost implements RequestMethod
{
    /**
     * reCAPTCHA service host.
     * @const string 
     */
    const RECAPTCHA_HOST = 'www.google.com';

    /**
     * @const string reCAPTCHA service path
     */
    const SITE_VERIFY_PATH = '/recaptcha/api/siteverify';

    /**
     * @const string Bad request error
     */
    const BAD_REQUEST = '{"success": false, "error-codes": ["invalid-request"]}';

    /**
     * @const string Bad response error
     */
    const BAD_RESPONSE = '{"success": false, "error-codes": ["invalid-response"]}';

    /**
     * Socket to the reCAPTCHA service
     * @var Socket
     */
    private $socket;

    /**
     * Constructor
     * 
     * @param \ReCaptcha\RequestMethod\Socket $socket optional socket, injectable for testing
     */
    public function __construct(Socket $socket = null)
    {
        if (!is_null($socket)) {
            $this->socket = $socket;
        } else {
            $this->socket = new Socket();
        }
    }

    /**
     * Submit the POST request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params)
    {
        $errno = 0;
        $errstr = '';

        if ($this->socket->fsockopen('ssl://' . self::RECAPTCHA_HOST, 443, $errno, $errstr, 30) !== false) {
            $content = $params->toQueryString();

            $request = "POST " . self::SITE_VERIFY_PATH . " HTTP/1.1\r\n";
            $request .= "Host: " . self::RECAPTCHA_HOST . "\r\n";
            $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $request .= "Content-length: " . strlen($content) . "\r\n";
            $request .= "Connection: close\r\n\r\n";
            $request .= $content . "\r\n\r\n";

            $this->socket->fwrite($request);
            $response = '';

            while (!$this->socket->feof()) {
                $response .= $this->socket->fgets(4096);
            }

            $this->socket->fclose();

            if (0 === strpos($response, 'HTTP/1.1 200 OK')) {
                $parts = preg_split("#\n\s*\n#Uis", $response);
                return $parts[1];
            }

            return self::BAD_RESPONSE;
        }

        return self::BAD_REQUEST;
    }
}
