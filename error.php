<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin fatal error display page
 *
 * @version $Id$
 */

/* Input sanitizing */
require_once './libraries/sanitizing.lib.php';

/* Get variables */
if (! empty($_REQUEST['lang']) && is_string($_REQUEST['lang'])) {
    $lang = htmlspecialchars($_REQUEST['lang']);
} else {
    $lang = 'en';
}

if (! empty($_REQUEST['dir']) && is_string($_REQUEST['dir'])) {
    $dir = htmlspecialchars($_REQUEST['dir']);
} else {
    $dir = 'ltr';
}

if (! empty($_REQUEST['type']) && is_string($_REQUEST['type'])) {
    $type = htmlspecialchars($_REQUEST['type']);
} else {
    $type = 'error';
}

// force utf-8 to avoid XSS with crafted URL and utf-7 in charset parameter
$charset = 'utf-8';

header('Content-Type: text/html; charset=' . $charset);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <title>phpMyAdmin</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
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
<h1>phpMyAdmin - <?php echo $type; ?></h1>
<p><?php
if (!empty($_REQUEST['error'])) {
    if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
        echo PMA_sanitize(stripslashes($_REQUEST['error']));
    } else {
        echo PMA_sanitize($_REQUEST['error']);
    }
} else {
    echo 'No error message!';
}
?></p>
</body>
</html>
