<?php
/**
 * Library that provides common functions that are used to help integrating Swekey Authentication in a PHP web site
 * Version 1.0
 *
 * History:
 * 1.2 Use curl (widely installed) to query the server
 *     Fixed a possible tempfile race attack
 *     Random token cache can now be disabled
 * 1.1 Added Swekey_HttpGet function that support faulty servers
 *     Support for custom servers
 * 1.0 First release
 *
 * @package Swekey
 */


/**
 * Errors codes
 */
define ("SWEKEY_ERR_INVALID_DEV_STATUS", 901);   // The satus of the device is not SWEKEY_STATUS_OK
define ("SWEKEY_ERR_INTERNAL", 902);             // Should never occurd
define ("SWEKEY_ERR_OUTDATED_RND_TOKEN", 910);   // You random token is too old
define ("SWEKEY_ERR_INVALID_OTP", 911);          // The otp was not correct

/**
 * Those errors are considered as an attack and your site will be blacklisted during one minute
 * if you receive one of those errors
 */
define ("SWEKEY_ERR_BADLY_ENCODED_REQUEST", 920);
define ("SWEKEY_ERR_INVALID_RND_TOKEN", 921);
define ("SWEKEY_ERR_DEV_NOT_FOUND", 922);

/**
 * Default values for configuration.
 */
define ('SWEKEY_DEFAULT_CHECK_SERVER', 'https://auth-check.musbe.net');
define ('SWEKEY_DEFAULT_RND_SERVER', 'https://auth-rnd-gen.musbe.net');
define ('SWEKEY_DEFAULT_STATUS_SERVER', 'https://auth-status.musbe.net');

/**
 * The last error of an operation is alway put in this global var
 */

global $gSwekeyLastError;
$gSwekeyLastError = 0;

global $gSwekeyLastResult;
$gSwekeyLastResult = "<not set>";

/**
 * Servers addresses
 * Use the  Swekey_SetXxxServer($server) functions to set them
 */

global $gSwekeyCheckServer;
if (! isset($gSwekeyCheckServer))
    $gSwekeyCheckServer = SWEKEY_DEFAULT_CHECK_SERVER;

global $gSwekeyRndTokenServer;
if (! isset($gSwekeyRndTokenServer))
    $gSwekeyRndTokenServer = SWEKEY_DEFAULT_RND_SERVER;

global $gSwekeyStatusServer;
if (! isset($gSwekeyStatusServer))
    $gSwekeyStatusServer = SWEKEY_DEFAULT_STATUS_SERVER;

global $gSwekeyCA;

global $gSwekeyTokenCacheEnabled;
if (! isset($gSwekeyTokenCacheEnabled))
    $gSwekeyTokenCacheEnabled = true;

/**
 *  Change the address of the Check server.
 *  If $server is empty the default value 'http://auth-check.musbe.net' will be used
 *
 *  @param  server              The protocol and hostname to use
 *  @access public
 */
function Swekey_SetCheckServer($server)
{
    global $gSwekeyCheckServer;
    if (empty($server))
        $gSwekeyCheckServer = SWEKEY_DEFAULT_CHECK_SERVER;
    else
        $gSwekeyCheckServer = $server;
}

/**
 *  Change the address of the Random Token Generator server.
 *  If $server is empty the default value 'http://auth-rnd-gen.musbe.net' will be used
 *
 *  @param  server              The protocol and hostname to use
 *  @access public
 */
function Swekey_SetRndTokenServer($server)
{
    global $gSwekeyRndTokenServer;
    if (empty($server))
        $gSwekeyRndTokenServer = SWEKEY_DEFAULT_RND_SERVER;
    else
        $gSwekeyRndTokenServer = $server;
}

/**
 *  Change the address of the Satus server.
 *  If $server is empty the default value 'http://auth-status.musbe.net' will be used
 *
 *  @param  server              The protocol and hostname to use
 *  @access public
 */
function Swekey_SetStatusServer($server)
{
    global $gSwekeyStatusServer;
    if (empty($server))
        $gSwekeyStatusServer = SWEKEY_DEFAULT_STATUS_SERVER;
    else
        $gSwekeyStatusServer = $server;
}

/**
 *  Change the certificat file in case of the the severs use https instead of http
 *
 *  @param  cafile              The path of the crt file to use
 *  @access public
 */
function Swekey_SetCAFile($cafile)
{
    global $gSwekeyCA;
       $gSwekeyCA = $cafile;
}

/**
 *  Enable or disable the random token caching
 *  Because everybody has full access to the cache file, it can be a DOS vulnerability
 *  So disable it if you are running in a non secure enviromnement
 *
 *  @param  $enable
 *  @access public
 */
function Swekey_EnableTokenCache($enable)
{
    global $gSwekeyTokenCacheEnabled;
    $gSwekeyTokenCacheEnabled = ! empty($enable);
}


/**
 *  Return the last error.
 *
 *  @return                     The Last Error
 *  @access public
 */
function Swekey_GetLastError()
{
    global $gSwekeyLastError;
    return $gSwekeyLastError;
}

/**
 *  Return the last result.
 *
 *  @return                     The Last Error
 *  @access public
 */
function Swekey_GetLastResult()
{
    global $gSwekeyLastResult;
    return $gSwekeyLastResult;
}

/**
 *  Send a synchronous request to the  server.
 *  This function manages timeout then will not block if one of the server is down
 *
 *  @param  url                 The url to get
 *  @param  response_code       The response code
 *  @return                     The body of the response or "" in case of error
 *  @access private
 */
function Swekey_HttpGet($url, &$response_code)
{
    global $gSwekeyLastError;
    $gSwekeyLastError = 0;
    global $gSwekeyLastResult;
    $gSwekeyLastResult = "<not set>";

     // use curl if available
    if (function_exists('curl_init')) {
        $sess = curl_init($url);
        if (substr($url, 0, 8) == "https://") {
            global $gSwekeyCA;

            if (! empty($gSwekeyCA)) {
                if (file_exists($gSwekeyCA)) {
                    if (! curl_setopt($sess, CURLOPT_CAINFO, $gSwekeyCA)) {
                        error_log("SWEKEY_ERROR:Could not set CA file : ".curl_error($sess));
                    } else {
                        $caFileOk = true;
                    }
                } else {
                    error_log("SWEKEY_ERROR:Could not find CA file $gSwekeyCA getting $url");
                }
            }

            curl_setopt($sess, CURLOPT_SSL_VERIFYHOST, '2');
            curl_setopt($sess, CURLOPT_SSL_VERIFYPEER, '2');
            curl_setopt($sess, CURLOPT_CONNECTTIMEOUT, '20');
            curl_setopt($sess, CURLOPT_TIMEOUT, '20');
        } else {
            curl_setopt($sess, CURLOPT_CONNECTTIMEOUT, '3');
            curl_setopt($sess, CURLOPT_TIMEOUT, '5');
        }

        curl_setopt($sess, CURLOPT_RETURNTRANSFER, '1');
        $res=curl_exec($sess);
        $response_code = curl_getinfo($sess, CURLINFO_HTTP_CODE);
        $curlerr = curl_error($sess);
        curl_close($sess);

        if ($response_code == 200) {
            $gSwekeyLastResult = $res;
            return $res;
        }

        if (! empty($response_code)) {
            $gSwekeyLastError = $response_code;
            error_log("SWEKEY_ERROR:Error $gSwekeyLastError ($curlerr) getting $url");
            return "";
        }

        $response_code = 408; // Request Timeout
        $gSwekeyLastError = $response_code;
        error_log("SWEKEY_ERROR:Error $curlerr getting $url");
        return "";
    }

    // use pecl_http if available
    if (class_exists('HttpRequest')) {
        // retry if one of the server is down
        for ($num=1; $num <= 3; $num++ ) {
            $r = new HttpRequest($url);
            $options = array('timeout' => '3');

            if (substr($url, 0, 6) == "https:") {
                $sslOptions = array();
                $sslOptions['verifypeer'] = true;
                $sslOptions['verifyhost'] = true;

                $capath = __FILE__;
                $name = strrchr($capath, '/');
                // windows
                if (empty($name)) {
                    $name = strrchr($capath, '\\');
                }
                $capath = substr($capath, 0, strlen($capath) - strlen($name) + 1).'musbe-ca.crt';

                if (! empty($gSwekeyCA)) {
                    $sslOptions['cainfo'] = $gSwekeyCA;
                }

                $options['ssl'] = $sslOptions;
            }

            $r->setOptions($options);

 //           try
            {
               $reply = $r->send();
               $res = $reply->getBody();
               $info = $r->getResponseInfo();
               $response_code = $info['response_code'];
               if ($response_code != 200)
               {
                    $gSwekeyLastError = $response_code;
                    error_log("SWEKEY_ERROR:Error ".$gSwekeyLastError." getting ".$url);
                    return "";
               }


               $gSwekeyLastResult = $res;
               return $res;
            }
 //           catch (HttpException $e)
 //           {
 //               error_log("SWEKEY_WARNING:HttpException ".$e." getting ".$url);
 //           }
        }

        $response_code = 408; // Request Timeout
        $gSwekeyLastError = $response_code;
        error_log("SWEKEY_ERROR:Error ".$gSwekeyLastError." getting ".$url);
        return "";
    }

       global $http_response_header;
    $res = @file_get_contents($url);
    $response_code = substr($http_response_header[0], 9, 3); //HTTP/1.0
    if ($response_code == 200) {
       $gSwekeyLastResult = $res;
       return $res;
    }

    $gSwekeyLastError = $response_code;
    error_log("SWEKEY_ERROR:Error ".$response_code." getting ".$url);
    return "";
}

/**
 *  Get a Random Token from a Token Server
 *  The RT is a 64 vhars hexadecimal value
 *  You should better use Swekey_GetFastRndToken() for performance
 *  @access public
 */
function Swekey_GetRndToken()
{
    global $gSwekeyRndTokenServer;
    return Swekey_HttpGet($gSwekeyRndTokenServer.'/FULL-RND-TOKEN', $response_code);
}

/**
 *  Get a Half Random Token from a Token Server
 *  The RT is a 64 vhars hexadecimal value
 *  Use this value if you want to make your own Swekey_GetFastRndToken()
 *  @access public
 */
function Swekey_GetHalfRndToken()
{
    global $gSwekeyRndTokenServer;
    return Swekey_HttpGet($gSwekeyRndTokenServer.'/HALF-RND-TOKEN', $response_code);
}

/**
 *  Get a Half Random Token
 *  The RT is a 64 vhars hexadecimal value
 *  This function get a new random token and reuse it.
 *  Token are refetched from the server only once every 30 seconds.
 *  You should always use this function to get half random token.
 *  @access public
 */
function Swekey_GetFastHalfRndToken()
{
    global $gSwekeyTokenCacheEnabled;

    $res = "";
    $cachefile = "";

    // We check if we have a valid RT is the session
    if (isset($_SESSION['rnd-token-date'])) {
        if (time() - $_SESSION['rnd-token-date'] < 30) {
             $res = $_SESSION['rnd-token'];
        }
    }

    // If not we try to get it from a temp file (PHP >= 5.2.1 only)
    if (strlen($res) != 32 && $gSwekeyTokenCacheEnabled) {
        if (function_exists('sys_get_temp_dir')) {
            $tempdir = sys_get_temp_dir();
            $cachefile = $tempdir."/swekey-rnd-token-".get_current_user();
            $modif = filemtime($cachefile);
            if ($modif != false) {
                if (time() - $modif < 30) {
                    $res = @file_get_contents($cachefile);
                    if (strlen($res) != 32) {
                         $res = "";
                    } else {
                         $_SESSION['rnd-token'] = $res;
                         $_SESSION['rnd-token-date'] = $modif;
                    }
                }
            }
        }
    }

    // If we don't have a valid RT here we have to get it from the server
    if (strlen($res) != 32) {
        $res = substr(Swekey_GetHalfRndToken(), 0, 32);
        $_SESSION['rnd-token'] = $res;
        $_SESSION['rnd-token-date'] = time();
        if (! empty($cachefile)) {
            // we unlink the file so no possible tempfile race attack
            unlink($cachefile);
            $file = fopen($cachefile, "x");
            if ($file != false) {
                @fwrite($file, $res);
                @fclose($file);
            }
        }
    }

   return $res."00000000000000000000000000000000";
}

/**
 *  Get a Random Token
 *  The RT is a 64 vhars hexadecimal value
 *  This function generates a unique random token for each call but call the
 *  server only once every 30 seconds.
 *  You should always use this function to get random token.
 *  @access public
 */
function Swekey_GetFastRndToken()
{
    $res = Swekey_GetFastHalfRndToken();
    if (strlen($res) == 64)
        return substr($res, 0, 32).strtoupper(md5("Musbe Authentication Key" + mt_rand() + date(DATE_ATOM)));

    return "";
}


/**
 *  Checks that an OTP generated by a Swekey is valid
 *
 *  @param  id                  The id of the swekey
 *  @param rt                   The random token used to generate the otp
 *  @param otp                  The otp generated by the swekey
 *  @return                     true or false
 *  @access public
 */
function Swekey_CheckOtp($id, $rt, $otp)
{
    global $gSwekeyCheckServer;
    $res = Swekey_HttpGet($gSwekeyCheckServer.'/CHECK-OTP/'.$id.'/'.$rt.'/'.$otp, $response_code);
    return $response_code == 200 && $res == "OK";
}

/**
 * Values that are associated with a key.
 * The following values can be returned by the Swekey_GetStatus() function
 */
define ("SWEKEY_STATUS_OK", 0);
define ("SWEKEY_STATUS_NOT_FOUND", 1);  // The key does not exist in the db
define ("SWEKEY_STATUS_INACTIVE", 2);   // The key has never been activated
define ("SWEKEY_STATUS_LOST", 3);       // The user has lost his key
define ("SWEKEY_STATUS_STOLEN", 4);       // The key was stolen
define ("SWEKEY_STATUS_FEE_DUE", 5);       // The annual fee was not paid
define ("SWEKEY_STATUS_OBSOLETE", 6);   // The hardware is no longer supported
define ("SWEKEY_STATUS_UNKOWN", 201);   // We could not connect to the authentication server

/**
 * Values that are associated with a key.
 * The Javascript Api can also return the following values
 */
define ("SWEKEY_STATUS_REPLACED", 100);     // This key has been replaced by a backup key
define ("SWEKEY_STATUS_BACKUP_KEY", 101); // This key is a backup key that is not activated yet
define ("SWEKEY_STATUS_NOTPLUGGED", 200); // This key is not plugged in the computer


/**
 *  Return the text corresponding to the integer status of a key
 *
 *  @param  status              The status
 *  @return                     The text corresponding to the status
 *  @access public
 */
function Swekey_GetStatusStr($status)
{
    switch($status)
    {
       case SWEKEY_STATUS_OK            : return 'OK';
       case SWEKEY_STATUS_NOT_FOUND        : return 'Key does not exist in the db';
       case SWEKEY_STATUS_INACTIVE        : return 'Key not activated';
       case SWEKEY_STATUS_LOST            : return 'Key was lost';
       case SWEKEY_STATUS_STOLEN        : return 'Key was stolen';
       case SWEKEY_STATUS_FEE_DUE        : return 'The annual fee was not paid';
       case SWEKEY_STATUS_OBSOLETE        : return 'Key no longer supported';
       case SWEKEY_STATUS_REPLACED        : return 'This key has been replaced by a backup key';
       case SWEKEY_STATUS_BACKUP_KEY    : return 'This key is a backup key that is not activated yet';
       case SWEKEY_STATUS_NOTPLUGGED    : return 'This key is not plugged in the computer';
       case SWEKEY_STATUS_UNKOWN        : return 'Unknow Status, could not connect to the authentication server';
    }
    return 'unknown status '.$status;
}

/**
 *  If your web site requires a key to login you should check that the key
 *  is still valid (has not been lost or stolen) before requiring it.
 *  A key can be authenticated only if its status is SWEKEY_STATUS_OK
 *  @param  id                  The id of the swekey
 *  @return                     The status of the swekey
 *  @access public
 */
function Swekey_GetStatus($id)
{
    global $gSwekeyStatusServer;
    $res = Swekey_HttpGet($gSwekeyStatusServer.'/GET-STATUS/'.$id, $response_code);
    if ($response_code == 200)
        return intval($res);

    return SWEKEY_STATUS_UNKOWN;
}

?>
