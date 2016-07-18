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

/**
 * Convenience wrapper around the cURL functions to allow mocking.
 */
class Curl
{

    /**
     * @see http://php.net/curl_init
     * @param string $url
     * @return resource cURL handle
     */
    public function init($url = null)
    {
        return curl_init($url);
    }

    /**
     * @see http://php.net/curl_setopt_array
     * @param resource $ch
     * @param array $options
     * @return bool
     */
    public function setoptArray($ch, array $options)
    {
        return curl_setopt_array($ch, $options);
    }

    /**
     * @see http://php.net/curl_exec
     * @param resource $ch
     * @return mixed
     */
    public function exec($ch)
    {
        return curl_exec($ch);
    }

    /**
     * @see http://php.net/curl_close
     * @param resource $ch
     */
    public function close($ch)
    {
        curl_close($ch);
    }
}
