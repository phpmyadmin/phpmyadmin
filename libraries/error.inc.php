<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin fatal error display page
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

if (! defined('TESTSUITE')) {
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!DOCTYPE HTML>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <link rel="icon" href="favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <title>phpMyAdmin</title>
    <meta charset="utf-8" />
    <style type="text/css">
    <!--
    html {
        padding: 0;
        margin: 0;
    }
    body  {
        font-family: sans-serif;
        font-size: small;
        color: #000000;
        background-color: #F5F5F5;
        margin: 1em;
    }
    h1 {
        margin: 0;
        padding: 0.3em;
        font-size: 1.4em;
        font-weight: bold;
        color: #ffffff;
        background-color: #ff0000;
    }
    p {
        margin: 0;
        padding: 0.5em;
        border: 0.1em solid red;
        background-color: #ffeeee;
    }
    //-->
    </style>
</head>
<body>
<h1>phpMyAdmin - <?php echo $error_header; ?></h1>
<p><?php echo PMA_sanitize($error_message); ?></p>
</body>
</html>
