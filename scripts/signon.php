<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Single signon for phpMyAdmin
 *
 * This is just example how to use single signon with phpMyAdmin, it is
 * not intended to be perfect code and look, only shows how you can
 * integrate this functionality in your application.
 *
 * @version $Id$
 * @package phpMyAdmin
 * @subpacke Example
 */

/* Was data posted? */
if (isset($_POST['user'])) {
    /* Need to have cookie visible from parent directory */
    session_set_cookie_params(0, '/', '', 0);
    /* Create signon session */
    $session_name = 'SignonSession';
    session_name($session_name);
    session_start();
    /* Store there credentials */
    $_SESSION['PMA_single_signon_user'] = $_POST['user'];
    $_SESSION['PMA_single_signon_password'] = $_POST['password'];
    $_SESSION['PMA_single_signon_host'] = $_POST['host'];
    $id = session_id();
    /* Close that session */
    session_write_close();
    /* Redirect to phpMyAdmin (should use absolute URL here!) */
    header('Location: ../index.php');
} else {
    /* Show simple form */
    header('Content-Type: text/html; charset=utf-8');
    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
    <title>phpMyAdmin single signon example</title>
<html>
<body>
<form action="signon.php" method="post">
Username: <input type="text" name="user" /><br />
Password: <input type="password" name="password" /><br />
Host: (will use the one from config.inc.php by default) <input type="text" name="host" /><br />
<input type="submit" />
</form>
</body>
</html>
<?php
}
?>
