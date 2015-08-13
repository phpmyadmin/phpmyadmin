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

namespace ReCaptcha;

/**
 * Stores and formats the parameters for the request to the reCAPTCHA service.
 */
class RequestParameters
{
    /**
     * Site secret.
     * @var string
     */
    private $secret;

    /**
     * Form response.
     * @var string
     */
    private $response;

    /**
     * Remote user's IP address.
     * @var string
     */
    private $remoteIp;

    /**
     * Client version.
     * @var string
     */
    private $version;

    /**
     * Initialise parameters.
     *
     * @param string $secret Site secret.
     * @param string $response Value from g-captcha-response form field.
     * @param string $remoteIp User's IP address.
     * @param string $version Version of this client library.
     */
    public function __construct($secret, $response, $remoteIp = null, $version = null)
    {
        $this->secret = $secret;
        $this->response = $response;
        $this->remoteIp = $remoteIp;
        $this->version = $version;
    }

    /**
     * Array representation.
     *
     * @return array Array formatted parameters.
     */
    public function toArray()
    {
        $params = array('secret' => $this->secret, 'response' => $this->response);

        if (!is_null($this->remoteIp)) {
            $params['remoteip'] = $this->remoteIp;
        }

        if (!is_null($this->version)) {
            $params['version'] = $this->version;
        }

        return $params;
    }

    /**
     * Query string representation for HTTP request.
     *
     * @return string Query string formatted parameters.
     */
    public function toQueryString()
    {
        return http_build_query($this->toArray());
    }
}
