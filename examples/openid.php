<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Single signon for phpMyAdmin using OpenID
 *
 * This is just example how to use single signon with phpMyAdmin, it is
 * not intended to be perfect code and look, only shows how you can
 * integrate this functionality in your application.
 *
 * It uses OpenID pear package, see http://pear.php.net/package/OpenID
 *
 * User first authenticates using OpenID and based on content of $AUTH_MAP
 * the login information is passed to phpMyAdmin in session data.
 *
 * @package    PhpMyAdmin
 * @subpackage Example
 */

if (false === @include_once 'OpenID/RelyingParty.php') {
    exit;
}

/**
 * Map of authenticated users to MySQL user/password pairs.
 */
$AUTH_MAP = array(
    'http://launchpad.net/~username' => array(
        'user' => 'root',
        'password' => '',
        ),
    );

/**
 * Simple function to show HTML page with given content.
 *
 * @return void
 */
function show_page($contents)
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    ?>
<!DOCTYPE HTML>
<html lang="en" dir="ltr">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
    <meta charset="utf-8" />
    <title>phpMyAdmin OpenID signon example</title>
</head>
<body>
<?php
if (isset($_SESSION) && isset($_SESSION['PMA_single_signon_error_message'])) {
    echo '<p class="error">' . $_SESSION['PMA_single_signon_message'] . '</p>';
    unset($_SESSION['PMA_single_signon_message']);
}
echo $contents;
?>
</body>
</html>
<?php
}

/* Need to have cookie visible from parent directory */
session_set_cookie_params(0, '/', '', 0);
/* Create signon session */
$session_name = 'SignonSession';
session_name($session_name);
session_start();

// Determine realm and return_to
$base = 'http';
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $base .= 's';
}
$base .= '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];

$realm = $base . '/';
$returnTo = $base . dirname($_SERVER['PHP_SELF']);
if ($returnTo[strlen($returnTo) - 1] != '/') {
    $returnTo .= '/';
}
$returnTo .= 'openid.php';

/* Display form */
if (!count($_GET) && !count($_POST) || isset($_GET['phpMyAdmin'])) {
    /* Show simple form */
    $content = '<form action="openid.php" method="post">
OpenID: <input type="text" name="identifier" /><br />
<input type="submit" name="start" />
</form>
</body>
</html>';
    show_page($content);
    exit;
}

/* Grab identifier */
if (isset($_POST['identifier'])) {
    $identifier = $_POST['identifier'];
} else if (isset($_SESSION['identifier'])) {
    $identifier = $_SESSION['identifier'];
} else {
    $identifier = null;
}

/* Create OpenID object */
try {
    $o = new OpenID_RelyingParty($returnTo, $realm, $identifier);
} catch (OpenID_Exception $e) {
    $contents = "<div class='relyingparty_results'>\n";
    $contents .= "<pre>" . $e->getMessage() . "</pre>\n";
    $contents .= "</div class='relyingparty_results'>";
    show_page($contents);
    exit;
}

/* Redirect to OpenID provider */
if (isset($_POST['start'])) {
    try {
        $authRequest = $o->prepare();
    } catch (OpenID_Exception $e) {
        $contents = "<div class='relyingparty_results'>\n";
        $contents .= "<pre>" . $e->getMessage() . "</pre>\n";
        $contents .= "</div class='relyingparty_results'>";
        show_page($contents);
        exit;
    }

    $url = $authRequest->getAuthorizeURL();

    header("Location: $url");
    exit;
} else {
    /* Grab query string */
    if (!count($_POST)) {
        list(, $queryString) = explode('?', $_SERVER['REQUEST_URI']);
    } else {
        // I hate php sometimes
        $queryString = file_get_contents('php://input');
    }

    /* Check reply */
    $message = new OpenID_Message($queryString, OpenID_Message::FORMAT_HTTP);

    $id = $message->get('openid.claimed_id');

    if (!empty($id) && isset($AUTH_MAP[$id])) {
        $_SESSION['PMA_single_signon_user'] = $AUTH_MAP[$id]['user'];
        $_SESSION['PMA_single_signon_password'] = $AUTH_MAP[$id]['password'];
        session_write_close();
        /* Redirect to phpMyAdmin (should use absolute URL here!) */
        header('Location: ../index.php');
    } else {
        show_page('<p>User not allowed!</p>');
        exit;
    }
}
