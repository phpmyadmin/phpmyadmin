<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets core libraries and defines some variables
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');

// Gets the default font sizes
PMA_setFontSizes();

// Gets the host name
// loic1 - 2001/25/11: use the new globals arrays defined with php 4.1+
if (empty($HTTP_HOST)) {
    if (!empty($_ENV) && isset($_ENV['HTTP_HOST'])) {
        $HTTP_HOST = $_ENV['HTTP_HOST'];
    }
    else if (!empty($HTTP_ENV_VARS) && isset($HTTP_ENV_VARS['HTTP_HOST'])) {
        $HTTP_HOST = $HTTP_ENV_VARS['HTTP_HOST'];
    }
    else if (@getenv('HTTP_HOST')) {
        $HTTP_HOST = getenv('HTTP_HOST');
    }
    else {
        $HTTP_HOST = '';
    }
}


/**
 * Defines the frameset
 */
// loic1: If left light mode -> urldecode the db name
if (isset($lightm_db)) {
    $db    = urldecode($lightm_db);
    unset($lightm_db);
}
$url_query = PMA_generate_common_url(isset($db) ? $db : '');

header('Content-Type: text/html; charset=' . $GLOBALS['charset']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">
<head>
<title>phpMyAdmin <?php echo PMA_VERSION; ?> - <?php echo $HTTP_HOST; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
<style type="text/css">
<!--
body  {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>}
//-->
</style>
</head>

<frameset cols="<?php echo $cfg['LeftWidth']; ?>,*" rows="*">
    <frame src="left.php3?<?php echo $url_query; ?>" name="nav" frameborder="1" />
    <frame src="<?php echo (empty($db)) ? 'main.php3' : $cfg['DefaultTabDatabase']; ?>?<?php echo $url_query; ?>" name="phpmain" />

    <noframes>
        <body bgcolor="#FFFFFF">
            <p><?php echo $strNoFrames; ?></p>
        </body>
    </noframes>
</frameset>

</html>
