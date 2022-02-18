<?php
/**
 * Single signon for phpMyAdmin
 *
 * This is just example how to use session based single signon with
 * phpMyAdmin, it is not intended to be perfect code and look, only
 * shows how you can integrate this functionality in your application.
 */

declare(strict_types=1);

/* Use cookies for session */
ini_set('session.use_cookies', 'true');
/* Change this to true if using phpMyAdmin over https */
$secure_cookie = false;
/* Need to have cookie visible from parent directory */
session_set_cookie_params(0, '/', '', $secure_cookie, true);
/* Create signon session */
$session_name = 'SignonSession';
session_name($session_name);
// Uncomment and change the following line to match your $cfg['SessionSavePath']
//session_save_path('/foobar');
@session_start();

/* Was data posted? */
if (isset($_POST['user']) && !empty($_POST['user'])) {
    /* Store there credentials */
    $_SESSION['PMA_single_signon_user'] = $_POST['user'];
    $_SESSION['PMA_single_signon_password'] = $_POST['password'];
    $_SESSION['PMA_single_signon_host'] = $_POST['host'];
    $_SESSION['PMA_single_signon_port'] = $_POST['port'];
    /* Update another field of server configuration */
    $_SESSION['PMA_single_signon_cfgupdate'] = ['verbose' => 'Signon test'];
    $_SESSION['PMA_single_signon_HMAC_secret'] = hash('sha1', uniqid(strval(random_int(0, mt_getrandmax())), true));
    $id = session_id();
    /* Close that session */
    @session_write_close();
    /* Redirect to phpMyAdmin (should use absolute URL here!) */
    header('Location: ../index.php');
} else {
    /* Show simple form */
    header('Content-Type: text/html; charset=utf-8');

    echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL .
         '<!DOCTYPE HTML>' . PHP_EOL .
         '<html lang="en" dir="ltr">' . PHP_EOL .
         '<head>' . PHP_EOL .
         '<link rel="icon" href="../favicon.ico" type="image/x-icon">' . PHP_EOL .
         '<link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">' . PHP_EOL .
         '<meta charset="utf-8">' . PHP_EOL .
         '<title>phpMyAdmin single signon example</title>' . PHP_EOL .
         '</head>' . PHP_EOL .
         '<body>';

    if (isset($_SESSION['PMA_single_signon_error_message']) && !empty($_SESSION['PMA_single_signon_error_message'])) {
        echo '<p class="error">' . $_SESSION['PMA_single_signon_error_message'] . '</p>';
    }

    echo '<form action="signon.php" method="post">' . PHP_EOL .
         'Username: <input type="text" name="user" autocomplete="username"><br>' . PHP_EOL .
         'Password: <input type="password" name="password" autocomplete="current-password"><br>' . PHP_EOL .
         'Host: (will use the one from config.inc.php by default)' . PHP_EOL .
         '<input type="text" name="host"><br>' . PHP_EOL .
         'Port: (will use the one from config.inc.php by default)' . PHP_EOL .
         '<input type="text" name="port"><br>' . PHP_EOL .
         '<input type="submit">' . PHP_EOL .
         '</form>' . PHP_EOL .
         '</body>' . PHP_EOL .
         '</html>';
}
