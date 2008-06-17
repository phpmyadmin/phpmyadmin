<?php
 
function Swekey_auth_check()
{
    // Load the swekey.conf file the first time
	if (empty($_SESSION['PHP_AUTH_REQUIRED_SWEKEY'])) {
        global $cfg;
        $confFile = $cfg['Server']['auth_swekey_config'];
        $_SESSION['PHP_AUTH_REQUIRES_SWEKEY'] = (! empty($confFile) && file_exists($confFile));
        if ($_SESSION['PHP_AUTH_REQUIRES_SWEKEY']) {
            $_SESSION['PHP_AUTH_VALID_SWEKEYS'] = "";
            $_SESSION['PHP_AUTH_SERVER_CHECK'] = "";
            $_SESSION['PHP_AUTH_SERVER_RNDTOKEN'] = "";
            $_SESSION['PHP_AUTH_SERVER_STATUS'] = "";
            $valid_swekeys = split("\n",@file_get_contents($confFile));
            foreach ($valid_swekeys as $line) {
                if (ereg("^[0-9A-F]{32}:.+$", $line) != false)      
                    $_SESSION['PHP_AUTH_VALID_SWEKEYS'] .= trim($line) . ",";   
                else if (ereg("^SERVER_[A-Z]+=.*$", $line) != false) {
                    $items = explode("=", $line);
                    $_SESSION['PHP_AUTH_'.trim($items[0])] = trim($items[1]);
                }      
            }
         }
        else
            unset($_SESSION['PHP_AUTH_VALID_SWEKEYS']); 
    }

    // check if a web key has been authenticated
    if ($_SESSION['PHP_AUTH_REQUIRES_SWEKEY']) {	
        if (empty($_SESSION['PHP_AUTH_AUTHENTICATED_SWEKEY']))
           return false; 	
	}
	
	return true;
}


function Swekey_auth_error()
{
    if (! $_SESSION['PHP_AUTH_REQUIRES_SWEKEY']) 
        return null;
 
     if (! empty($_SESSION['PHP_AUTH_AUTHENTICATED_SWEKEY'])) 
        return null;
 
     if (empty($_SESSION['PHP_AUTH_VALID_SWEKEYS'])) 
        return sprintf($GLOBALS['strSwekeyNoKeyId'], $GLOBALS['cfg']['Server']['auth_swekey_config']); 

    require_once "./libraries/auth/swekey/swekey.php";

    Swekey_SetCheckServer($_SESSION['PHP_AUTH_SERVER_CHECK']);
    Swekey_SetRndTokenServer($_SESSION['PHP_AUTH_SERVER_RNDTOKEN']);
    Swekey_SetStatusServer($_SESSION['PHP_AUTH_SERVER_STATUS']);

    $result = null;
    parse_str($_SERVER['QUERY_STRING']); 
    if (isset($swekey_id)) {
        unset($_SESSION['PHP_AUTH_AUTHENTICATED_SWEKEY']);
        if (! isset($_SESSION['PHP_AUTH_SWEKEY_RND_TOKEN'])) {
            unset($swekey_id);
        }
        else {
            if (strlen($swekey_id) == 32) {
                $res = Swekey_CheckOtp($swekey_id, $_SESSION['PHP_AUTH_SWEKEY_RND_TOKEN'], $swekey_otp);
                unset($_SESSION['PHP_AUTH_SWEKEY_RND_TOKEN']);
                if (! $res) {
                    $result = $GLOBALS['strSwekeyAuthFailed'] . ' (' . Swekey_GetLastError() . ')';
                }
                else {            
                    $_SESSION['PHP_AUTH_AUTHENTICATED_SWEKEY'] = $swekey_id;
                    unset($_SESSION['PHP_AUTH_FORCE_USER']);
                    $valid_swekeys = split(",",$_SESSION['PHP_AUTH_VALID_SWEKEYS']);
                    foreach ($valid_swekeys as $line) {
                        if (substr($line,0,32) == $swekey_id) {
                            $_SESSION['PHP_AUTH_FORCE_USER'] = substr($line,33);
                            break;
                        }
                    }
                    return null;
                }           
            }
            else {
                $result = $GLOBALS['strSwekeyNoKey']; 
             }
        }                
    }

    $_SESSION['PHP_AUTH_SWEKEY_RND_TOKEN'] = Swekey_GetFastRndToken();
    if (strlen($_SESSION['PHP_AUTH_SWEKEY_RND_TOKEN']) != 64) {
        $result = $GLOBALS['strSwekeyAuthFailed'] . ' (' . Swekey_GetLastError() . ')';
    }

    require_once './libraries/auth/swekey/authentication.inc.php';

    if (! isset($swekey_id)) {
        ?>
        <script>
        window.location.search="?swekey_id=" + Swekey_GetValidKey() + "&swekey_otp=" + Swekey_GetOtpFromValidKey();
        </script>
        <?php
        return $GLOBALS['strSwekeyAuthenticating']; 
    }

    ?>
    <script>
    var key = Swekey_GetValidKey();
    function timedCheck()
    {
        if (key != Swekey_GetValidKey())
            window.location.search="";  

        setTimeout("timedCheck()",1000);
    }
    timedCheck();
    </script>
    <?php

    return $result;
}

?>
