<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Swekey
 *
 * @package Swekey
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Checks Swekey authentication.
 *
 * @return boolean whether authentication succeeded or not
 */
function Swekey_Auth_check()
{
    global $cfg;
    $confFile = $cfg['Server']['auth_swekey_config'];

    if (! isset($_SESSION['SWEKEY'])) {
        $_SESSION['SWEKEY'] = array();
    }

    $_SESSION['SWEKEY']['ENABLED'] = (! empty($confFile) && file_exists($confFile));

    // Load the swekey.conf file the first time
    if ($_SESSION['SWEKEY']['ENABLED']
        && empty($_SESSION['SWEKEY']['CONF_LOADED'])
    ) {
        $_SESSION['SWEKEY']['CONF_LOADED'] = true;
        $_SESSION['SWEKEY']['VALID_SWEKEYS'] = array();
        $valid_swekeys = explode("\n", @file_get_contents($confFile));
        foreach ($valid_swekeys as $line) {
            if (preg_match("/^[0-9A-F]{32}:.+$/", $line) != false) {
                $items = explode(":", $line);
                if (count($items) == 2) {
                    $_SESSION['SWEKEY']['VALID_SWEKEYS'][$items[0]]
                        = trim($items[1]);
                }
            } elseif (preg_match("/^[A-Z_]+=.*$/", $line) != false) {
                $items = explode("=", $line);
                $_SESSION['SWEKEY']['CONF_' . trim($items[0])] = trim($items[1]);
            }
        }

        // Set default values for settings
        if (! isset($_SESSION['SWEKEY']['CONF_SERVER_CHECK'])) {
            $_SESSION['SWEKEY']['CONF_SERVER_CHECK'] = "";
        }
        if (! isset($_SESSION['SWEKEY']['CONF_SERVER_RNDTOKEN'])) {
            $_SESSION['SWEKEY']['CONF_SERVER_RNDTOKEN'] = "";
        }
        if (! isset($_SESSION['SWEKEY']['CONF_SERVER_STATUS'])) {
             $_SESSION['SWEKEY']['CONF_SERVER_STATUS'] = "";
        }
        if (! isset($_SESSION['SWEKEY']['CONF_CA_FILE'])) {
            $_SESSION['SWEKEY']['CONF_CA_FILE'] = "";
        }
        if (! isset($_SESSION['SWEKEY']['CONF_ENABLE_TOKEN_CACHE'])) {
            $_SESSION['SWEKEY']['CONF_ENABLE_TOKEN_CACHE'] = true;
        }
        if (! isset($_SESSION['SWEKEY']['CONF_DEBUG'])) {
               $_SESSION['SWEKEY']['CONF_DEBUG'] = false;
        }
    }

    // check if a web key has been authenticated
    if ($_SESSION['SWEKEY']['ENABLED']) {
        if (empty($_SESSION['SWEKEY']['AUTHENTICATED_SWEKEY'])) {
            return false;
        }
    }

    return true;
}


/**
 * Handle Swekey authentication error.
 *
 * @return string HTML
 */
function Swekey_Auth_error()
{
    if (! isset($_SESSION['SWEKEY'])) {
        return null;
    }

    if (! $_SESSION['SWEKEY']['ENABLED']) {
        return null;
    }

    include_once './libraries/plugins/auth/swekey/authentication.inc.php';

    ?>
    <script>
    function Swekey_GetValidKey()
    {
        var valids = "<?php
        foreach ($_SESSION['SWEKEY']['VALID_SWEKEYS'] as $key => $value) {
                echo $key . ',';
        }
        ?>";
        var connected_keys = Swekey_ListKeyIds().split(",");
        for (i in connected_keys) {
            if (connected_keys[i] != null && connected_keys[i].length == 32) {
                if (valids.indexOf(connected_keys[i]) >= 0) {
                   return connected_keys[i];
                }
            }
        }


        if (connected_keys.length > 0) {
            if (connected_keys[0].length == 32) {
                return "unknown_key_" + connected_keys[0];
            }
        }

        return "none";
    }

    var key = Swekey_GetValidKey();

    function timedCheck()
    {
        if (key != Swekey_GetValidKey()) {
            window.location.search = "?swekey_reset";
        } else {
            setTimeout("timedCheck()",1000);
        }
    }

    setTimeout("timedCheck()",1000);
    </script>
        <?php

        if (! empty($_SESSION['SWEKEY']['AUTHENTICATED_SWEKEY'])) {
            return null;
        }

        if (count($_SESSION['SWEKEY']['VALID_SWEKEYS']) == 0) {
            return sprintf(
                __('File %s does not contain any key id'),
                $GLOBALS['cfg']['Server']['auth_swekey_config']
            );
        }

    include_once "libraries/plugins/auth/swekey/swekey.php";

    Swekey_SetCheckServer($_SESSION['SWEKEY']['CONF_SERVER_CHECK']);
    Swekey_SetRndTokenServer($_SESSION['SWEKEY']['CONF_SERVER_RNDTOKEN']);
    Swekey_SetStatusServer($_SESSION['SWEKEY']['CONF_SERVER_STATUS']);
    Swekey_EnableTokenCache($_SESSION['SWEKEY']['CONF_ENABLE_TOKEN_CACHE']);

    $caFile = $_SESSION['SWEKEY']['CONF_CA_FILE'];
    if (empty($caFile)) {
        $caFile = __FILE__;
        $pos = strrpos($caFile, '/');
        if ($pos === false) {
            $pos = strrpos($caFile, '\\'); // windows
        }
        $caFile = substr($caFile, 0, $pos + 1) . 'musbe-ca.crt';
        //        echo "\n<!-- $caFile -->\n";
        //        if (file_exists($caFile))
        //            echo "<!-- exists -->\n";
    }

    if (file_exists($caFile)) {
        Swekey_SetCAFile($caFile);
    } elseif (! empty($caFile)
        && (substr($_SESSION['SWEKEY']['CONF_SERVER_CHECK'], 0, 8) == "https://")
    ) {
        return "Internal Error: CA File $caFile not found";
    }

    $result = null;
    $swekey_id = $_GET['swekey_id'];
    $swekey_otp = $_GET['swekey_otp'];

    if (isset($swekey_id)) {
        unset($_SESSION['SWEKEY']['AUTHENTICATED_SWEKEY']);
        if (! isset($_SESSION['SWEKEY']['RND_TOKEN'])) {
            unset($swekey_id);
        } else {
            if (strlen($swekey_id) == 32) {
                $res = Swekey_CheckOtp(
                    $swekey_id, $_SESSION['SWEKEY']['RND_TOKEN'], $swekey_otp
                );
                unset($_SESSION['SWEKEY']['RND_TOKEN']);
                if (! $res) {
                    $result = __('Hardware authentication failed!') . ' (' . Swekey_GetLastError() . ')';
                } else {
                    $_SESSION['SWEKEY']['AUTHENTICATED_SWEKEY'] = $swekey_id;
                    $_SESSION['SWEKEY']['FORCE_USER']
                        = $_SESSION['SWEKEY']['VALID_SWEKEYS'][$swekey_id];
                    return null;
                }
            } else {
                $result = __('No valid authentication key plugged');
                if ($_SESSION['SWEKEY']['CONF_DEBUG']) {
                    $result .= "<br/>" . htmlspecialchars($swekey_id);
                }
                unset($_SESSION['SWEKEY']['CONF_LOADED']); // reload the conf file
            }
        }
    } else {
        unset($_SESSION['SWEKEY']);
    }

    $_SESSION['SWEKEY']['RND_TOKEN'] = Swekey_GetFastRndToken();
    if (strlen($_SESSION['SWEKEY']['RND_TOKEN']) != 64) {
        $result = __('Hardware authentication failed!') . ' (' . Swekey_GetLastError() . ')';
        unset($_SESSION['SWEKEY']['CONF_LOADED']); // reload the conf file
    }

    if (! isset($swekey_id)) {
        ?>
        <script>
        if (key.length != 32) {
            window.location.search="?swekey_id=" + key + "&token=<?php echo $_SESSION[' PMA_token ']; ?>";
        } else {
            var url = "" + window.location;
            if (url.indexOf("?") > 0) {
                url = url.substr(0, url.indexOf("?"));
            }
            Swekey_SetUnplugUrl(key, "pma_login", url + "?session_to_unset=<?php echo session_id();?>&token=<?php echo $_SESSION[' PMA_token ']; ?>");
            var otp = Swekey_GetOtp(key, <?php echo '"' . $_SESSION['SWEKEY']['RND_TOKEN'] . '"';?>);
            window.location.search="?swekey_id=" + key + "&swekey_otp=" + otp + "&token=<?php echo $_SESSION[' PMA_token ']; ?>";
        }
        </script>
        <?php
        return __('Authenticating…');
    }

    return $result;
}


/**
 * Perform login using Swekey.
 *
 * @param string $input_name Input "Name"
 * @param string $input_go   Input "Go"
 *
 * @return void
 */
function Swekey_login($input_name, $input_go)
{
    $swekeyErr = Swekey_Auth_error();
    if ($swekeyErr != null) {
        PMA_Message::error($swekeyErr)->display();
        if ($GLOBALS['error_handler']->hasDisplayErrors()) {
            echo '<div>';
            $GLOBALS['error_handler']->dispErrors();
            echo '</div>';
        }
    }

    if (isset($_SESSION['SWEKEY']) && $_SESSION['SWEKEY']['ENABLED']) {
        echo '<script type="text/javascript">';
        if (empty($_SESSION['SWEKEY']['FORCE_USER'])) {
            echo 'var user = null;';
        } else {
            echo 'var user = "' . $_SESSION['SWEKEY']['FORCE_USER'] . '";';
        }

        ?>
            function open_swekey_site()
            {
                window.open("<?php echo PMA_linkURL('https://www.phpmyadmin.net/auth_key/'); ?>");
            }

            var input_username = document.getElementById("<?php echo $input_name; ?>");
            var input_go = document.getElementById("<?php echo $input_go; ?>");
            var swekey_status = document.createElement('img');
            swekey_status.setAttribute('onclick', 'open_swekey_site()');
            swekey_status.setAttribute('style', 'width:8px; height:16px; border:0px; vspace:0px; hspace:0px; frameborder:no');
            if (user == null) {
                swekey_status.setAttribute('src', 'http://artwork.swekey.com/unplugged-8x16.png');
                //swekey_status.setAttribute('title', 'No swekey plugged');
                input_go.disabled = true;
            } else {
                swekey_status.setAttribute('src', 'http://artwork.swekey.com/plugged-8x16.png');
                //swekey_status.setAttribute('title', 'swekey plugged');
                input_username.value = user;
            }
            input_username.readOnly = true;

            if (input_username.nextSibling == null) {
                input_username.parentNode.appendChild(swekey_status);
            } else {
                input_username.parentNode.insertBefore(swekey_status, input_username.nextSibling);
            }

        <?php
        echo '</script>';
    }
}

if (!empty($_GET['session_to_unset'])) {
    session_write_close();
    session_id($_GET['session_to_unset']);
    session_start();
    $_SESSION = array();
    session_write_close();
    session_destroy();
    exit;
}

if (isset($_GET['swekey_reset'])) {
    unset($_SESSION['SWEKEY']);
}

