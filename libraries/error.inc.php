<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin fatal error display page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Sanitize;

if (! defined('PHPMYADMIN')) {
    exit;
}

if (! defined('TESTSUITE')) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!DOCTYPE HTML>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <title>phpMyAdmin</title>
    <meta charset="utf-8">
</head>
<body>
<h1>phpMyAdmin - <?php echo $error_header; ?></h1>
<p><?php echo Sanitize::sanitize($error_message); ?></p>
</body>
</html>
