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

require('./libraries/relation.lib.php3');
$cfgRelation = PMA_getRelationsParam();

if ($cfg['QueryHistoryDB'] && $cfgRelation['historywork']) {
    PMA_purgeHistory($cfg['Server']['user']);
}

$phpmain_hash = md5($cfg['PmaAbsoluteUri']);
$phpmain_hash_js = time();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">
<head>
<title>phpMyAdmin <?php echo PMA_VERSION; ?> - <?php echo $HTTP_HOST; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php3?lang=<?php echo $lang; ?>&amp;js_frame=right" />
</head>

<frameset cols="<?php echo $cfg['LeftWidth']; ?>,*" rows="*">
<?php if ($cfg['QueryFrame']) { ?>
    <frameset rows="*, 50" framespacing="0" frameborder="0" border="0">
<?php
    if ($cfg['QueryFrameJS']) {?>
        <script type="text/javascript">
        <!--
        document.writeln('<frame src="left.php3?<?php echo $url_query; ?>&amp;hash=<?php echo $phpmain_hash . $phpmain_hash_js; ?>" name="nav" frameborder="0" />');
        document.writeln('<frame src="queryframe.php3?<?php echo $url_query; ?>&amp;hash=<?php echo $phpmain_hash . $phpmain_hash_js; ?>" name="queryframe" frameborder="0" />');
        //-->
        </script>

        <noscript>
<?php } ?>
        <frame src="left.php3?<?php echo $url_query; ?>&amp;hash=<?php echo $phpmain_hash; ?>" name="nav" frameborder="0" />
        <frame src="queryframe.php3?<?php echo $url_query; ?>&amp;hash=<?php echo $phpmain_hash; ?>" name="queryframe" frameborder="0" />
<?php if ($cfg['QueryFrameJS']) { ?>
        </noscript>
<?php } ?>
    </frameset>
<?php
} else {
?>
    <frame src="left.php3?<?php echo $url_query; ?>&amp;hash=<?php echo $phpmain_hash; ?>" name="nav" frameborder="0" />
<?php
}
if ($cfg['QueryFrameJS']) {
?>
    <script type="text/javascript">
    <!--
    document.writeln('<frame src="<?php echo (empty($db)) ? 'main.php3' : $cfg['DefaultTabDatabase']; ?>?<?php echo $url_query; ?>" name="phpmain<?php echo $phpmain_hash . $phpmain_hash_js; ?>" frameborder="0" />');
    //-->
    </script>
    <noscript>
<?php } ?>
        <frame src="<?php echo (empty($db)) ? 'main.php3' : $cfg['DefaultTabDatabase']; ?>?<?php echo $url_query; ?>" name="phpmain<?php echo $phpmain_hash; ?>" frameborder="1" />
<?php if ($cfg['QueryFrameJS']) { ?>
    </noscript>
<?php } ?>

    <noframes>
        <body bgcolor="#FFFFFF">
            <p><?php echo $strNoFrames; ?></p>
        </body>
    </noframes>
</frameset>

</html>
