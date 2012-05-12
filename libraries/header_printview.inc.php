<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Starts output buffering work
 */
require_once './libraries/ob.lib.php';
PMA_outBufferPre();

// Check parameters

PMA_checkParameters(array('db', 'full_sql_query'));


// For re-usability, moved http-headers
// to a separate file. It can now be included by libraries/header.inc.php,
// querywindow.php.

require_once './libraries/header_http.inc.php';

/**
 * Sends the beginning of the html page then returns to the calling script
 */
// Defines the cell alignment values depending on text direction
if ($text_dir == 'ltr') {
    $cell_align_left  = 'left';
    $cell_align_right = 'right';
} else {
    $cell_align_left  = 'right';
    $cell_align_right = 'left';
}
?>
<!DOCTYPE HTML>
<html lang="<?php echo $available_languages[$lang][1]; ?>" dir="<?php echo $text_dir; ?>">

<head>
<meta charset="utf-8" />
<link rel="icon" href="favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<title><?php echo __('SQL result'); ?> - phpMyAdmin <?php echo PMA_VERSION ?></title>
<link rel="stylesheet" type="text/css" href="phpmyadmin.css.php?<?php echo PMA_generate_common_url('', ''); ?>&amp;js_frame=print&amp;nocache=<?php echo $GLOBALS['PMA_Config']->getThemeUniqueValue(); ?>" />
<?php
require_once './libraries/header_scripts.inc.php';
?>
</head>

<body>
<?php

/**
 * Sets a variable to remember headers have been sent
 */
$is_header_sent = true;
?>
