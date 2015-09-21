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
 * reCAPTCHA client.
 */
class ReCaptcha
{
    /**
     * Version of this client library.
     * @const string
     */
    const VERSION = 'php_1.1.0';

    /**
     * Shared secret for the site.
     * @var type string
     */
    private $secret;

    /**
     * Method used to communicate  with service. Defaults to POST request.
     * @var RequestMethod
     */
    private $requestMethod;

    /**
     * Create a configured instance to use the reCAPTCHA service.
     *
     * @param string $secret shared secret between site and reCAPTCHA server.
     * @param RequestMethod $requestMethod method used to send the request. Defaults to POST.
     */
    public function __construct($secret, RequestMethod $requestMethod = null)
    {
        if (empty($secret)) {
            throw new \RuntimeException('No secret provided');
        }

        if (!is_string($secret)) {
            throw new \RuntimeException('The provided secret must be a string');
        }

        $this->secret = $secret;

        if (!is_null($requestMethod)) {
            $this->requestMethod = $requestMethod;
        } else {
            $this->requestMethod = new RequestMethod\Post();
        }
    }

    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test.
     *
     * @param string $response The value of 'g-recaptcha-response' in the submitted form.
     * @param string $remoteIp The end user's IP address.
     * @return Response Response from the service.
     */
    public function verify($response, $remoteIp = null)
    {
        // Discard empty solution submissions
        if (empty($response)) {
            $recaptchaResponse = new Response(false, array('missing-input-response'));
            return $recaptchaResponse;
        }

        $params = new RequestParameters($this->secret, $response, $remoteIp, self::VERSION);
        $rawResponse = $this->requestMethod->submit($params);
        return Response::fromJson($rawResponse);
    }
}
