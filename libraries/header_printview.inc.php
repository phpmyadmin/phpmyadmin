<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Gets a core script and starts output buffering work
 */
require_once './libraries/common.inc.php';
require_once './libraries/ob.lib.php';
PMA_outBufferPre();

// Check parameters

PMA_checkParameters(array('db', 'full_sql_query'));


// garvin: For re-usability, moved http-headers
// to a seperate file. It can now be included by libraries/header.inc.php,
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<link rel="icon" href="./favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
<title><?php echo $strSQLResult; ?> - phpMyAdmin <?php echo PMA_VERSION ?></title>
<link rel="stylesheet" type="text/css" href="phpmyadmin.css.php?<?php echo PMA_generate_common_url('', ''); ?>&amp;js_frame=print&amp;nocache=<?php echo $_SESSION['PMA_Config']->getThemeUniqueValue(); ?>" />
</head>

<body bgcolor="#ffffff">
<h1><?php echo $strSQLResult; ?></h1>
<p>
    <strong><?php echo $strHost; ?>:</strong> <?php echo $cfg['Server']['verbose'] ? $cfg['Server']['verbose'] : $cfg['Server']['host'] . ((!empty($cfg['Server']['port'])) ? ':' . $cfg['Server']['port'] : ''); ?><br />
    <strong><?php echo $strDatabase; ?>:</strong> <?php echo htmlspecialchars($db); ?><br />
    <strong><?php echo $strGenTime; ?>:</strong> <?php echo PMA_localisedDate(); ?><br />
    <strong><?php echo $strGenBy; ?>:</strong> phpMyAdmin&nbsp;<?php echo PMA_VERSION; ?>&nbsp;/ MySQL&nbsp;<?php echo PMA_MYSQL_STR_VERSION; ?><br />
    <strong><?php echo $strSQLQuery; ?>:</strong> <?php echo htmlspecialchars($full_sql_query); ?>;
    <?php if (isset($num_rows)) { ?><br />
    <strong><?php echo $strRows; ?>:</strong> <?php echo $num_rows; ?>
    <?php } ?>
</p>


<?php

/**
 * Sets a variable to remember headers have been sent
 */
$is_header_sent = TRUE;
?>
