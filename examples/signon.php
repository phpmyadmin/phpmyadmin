<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Single signon for phpMyAdmin
 *
 * This is just example how to use session based single signon with
 * phpMyAdmin, it is not intended to be perfect code and look, only
 * shows how you can integrate this functionality in your application.
 *
 * @package    PhpMyAdmin
 * @subpackage Example
 */

/* Need to have cookie visible from parent directory */
session_set_cookie_params(0, '/', '', true, true);
/* Create signon session */
$session_name = 'SignonSession';
session_name($session_name);
@session_start();

/* Was data posted? */
if (isset($_POST['user'])) {
    /* Store there credentials */
    $_SESSION['PMA_single_signon_user'] = $_POST['user'];
    $_SESSION['PMA_single_signon_password'] = $_POST['password'];
    $_SESSION['PMA_single_signon_host'] = $_POST['host'];
    $_SESSION['PMA_single_signon_port'] = $_POST['port'];
    /* Update another field of server configuration */
    $_SESSION['PMA_single_signon_cfgupdate'] = array('verbose' => 'Signon test');
    $id = session_id();
    /* Close that session */
    @session_write_close();
    /* Redirect to phpMyAdmin (should use absolute URL here!) */
    header('Location: ../index.php');
} else {
    /* Show simple form */
    header('Content-Type: text/html; charset=utf-8');
    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    ?>
<!DOCTYPE HTML>
<html lang="en" dir="ltr">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
    <meta charset="utf-8" />
    <title>phpMyAdmin single signon example</title>
</head>
<body>
<?php
if (isset($_SESSION['PMA_single_signon_error_message'])) {
    echo '<p class="error">' . $_SESSION['PMA_single_signon_error_message'] . '</p>';
}
?>
<form action="signon.php" method="post">
Username: <input type="text" name="user" /><br />
Password: <input type="password" name="password" /><br />
Host: (will use the one from config.inc.php by default) <input type="text" name="host" /><br />
Port: (will use the one from config.inc.php by default) <input type="text" name="port" /><br />
<input type="submit" />
</form>
</body>
</html>
<?php
}
?>
