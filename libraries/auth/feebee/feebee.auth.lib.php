<?php
 
function Feebee_auth_check()
{
    // Load the feebee.conf file the first time
	if (empty($_SESSION['PHP_AUTH_REQUIRED_FEEBEE'])) {
        global $cfg;
        $confFile = $cfg['Server']['auth_feebee_config'];
        $_SESSION['PHP_AUTH_REQUIRES_FEEBEE'] = (! empty($confFile) && file_exists($confFile));
//        $_SESSION['PHP_AUTH_REQUIRES_FEEBEE'] = file_exists($confFile);
        if ($_SESSION['PHP_AUTH_REQUIRES_FEEBEE']) {
            $_SESSION['PHP_AUTH_VALID_FEEBEES'] = "";
            $valid_feebees = split("\n",@file_get_contents($confFile));
            foreach ($valid_feebees as $line) {
                if (ereg("^[0-9A-F]{32}:.+$", $line) != false)      
                    $_SESSION['PHP_AUTH_VALID_FEEBEES'] .= $line . ",";   
            }
         }
        else
            unset($_SESSION['PHP_AUTH_VALID_FEEBEES']); 
    }

    // check if a web key has been authenticated
    if ($_SESSION['PHP_AUTH_REQUIRES_FEEBEE']) {	
        if (empty($_SESSION['PHP_AUTH_AUTHENTICATED_FEEBEE']))
           return false; 	
	}
	
	return true;
}


function Feebee_auth_error()
{
    if (! $_SESSION['PHP_AUTH_REQUIRES_FEEBEE']) 
        return null;
 
     if (! empty($_SESSION['PHP_AUTH_AUTHENTICATED_FEEBEE'])) 
        return null;
 
     if (empty($_SESSION['PHP_AUTH_VALID_FEEBEES'])) 
        return sprintf($GLOBALS['strFeebeeNoKeyId'], $GLOBALS['cfg']['Server']['auth_feebee_config']); 

    $result = null;
    parse_str($_SERVER['QUERY_STRING']); 
    if (isset($feebee_id)) {
        unset($_SESSION['PHP_AUTH_AUTHENTICATED_FEEBEE']);
        if (! isset($_SESSION['PHP_AUTH_FEEBEE_RND_TOKEN'])) {
            unset($feebee_id);
        }
        else {
            if (strlen($feebee_id) == 32) {
                $res = file_get_contents('http://auth-check.musbe.net/CHECK-OTP/'.$feebee_id.'/'.$_SESSION['PHP_AUTH_FEEBEE_RND_TOKEN'].'/'.$feebee_otp);
                unset($_SESSION['PHP_AUTH_FEEBEE_RND_TOKEN']);
                if ($res != "OK") {
                    $result = $GLOBALS['strFeebeeAuthFailed'] . ' (' . $res . ')';
                }
                else {            
                    $_SESSION['PHP_AUTH_AUTHENTICATED_FEEBEE'] = $feebee_id;
                    unset($_SESSION['PHP_AUTH_FORCE_USER']);
                    $valid_feebees = split(",",$_SESSION['PHP_AUTH_VALID_FEEBEES']);
                    foreach ($valid_feebees as $line) {
                        if (substr($line,0,32) == $feebee_id) {
                            $_SESSION['PHP_AUTH_FORCE_USER'] = substr($line,33);
                            break;
                        }
                    }
                    return null;
                }           
            }
            $result = $GLOBALS['strFeebeeNoKey']; 
        }                
    }

    require_once './libraries/auth/feebee/authentication.inc.php';

    if (! isset($feebee_id)) {
        ?>
        <script>
        window.location.search="?feebee_id=" + Feebee_GetValidKey() + "&feebee_otp=" + Feebee_GetOtp();
        </script>
        <?php
        return $GLOBALS['strFeebeeAuthenticating']; 
    }

    ?>
    <script>
    var key = Feebee_GetValidKey();
    function timedCheck()
    {
        if (key != Feebee_GetValidKey())
            window.location.search="";  

        setTimeout("timedCheck()",1000);
    }
    timedCheck();
    </script>
    <?php

    return $result;
}

?>
