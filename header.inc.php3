<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets a core script and starts output buffering work
 */
if (!defined('PMA_COMMON_LIB_INCLUDED')) {
    include('./libraries/common.lib.php3');
}
if (!defined('PMA_OB_LIB_INCLUDED')) {
    include('./libraries/ob.lib.php3');
}
if ($GLOBALS['cfg']['OBGzip']) {
    $GLOBALS['ob_mode'] = PMA_outBufferModeGet();
    if ($GLOBALS['ob_mode']) {
        PMA_outBufferPre($GLOBALS['ob_mode']);
    }
}


/**
 * Sends http headers
 */
// Don't use cache (required for Opera)
$GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';
header('Expires: ' . $GLOBALS['now']); // rfc2616 - Section 14.21
header('Last-Modified: ' . $GLOBALS['now']);
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache'); // HTTP/1.0
// Define the charset to be used
header('Content-Type: text/html; charset=' . $GLOBALS['charset']);


/**
 * Sends the beginning of the html page then returns to the calling script
 */
// Gets the font sizes to use
PMA_setFontSizes();
// Defines the cell alignment values depending on text direction
if ($GLOBALS['text_dir'] == 'ltr') {
    $GLOBALS['cell_align_left']  = 'left';
    $GLOBALS['cell_align_right'] = 'right';
} else {
    $GLOBALS['cell_align_left']  = 'right';
    $GLOBALS['cell_align_right'] = 'left';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">

<head>
<title>phpMyAdmin</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
<?php
if (!empty($GLOBALS['cfg']['PmaAbsoluteUri'])) {
    echo '<base href="' . $GLOBALS['cfg']['PmaAbsoluteUri'] . '" />' . "\n";
}
?>
<style type="text/css">
<!--
body {
    font-family: <?php echo $GLOBALS['right_font_family']; ?>;
    font-size: <?php echo $GLOBALS['font_size']; ?>;
    color: #000000;
<?php
if ($GLOBALS['cfg']['RightBgImage'] == '') {
    echo '    background-image: url(\'./images/vertical_line.gif\');' . "\n"
         . '    background-repeat: repeat-y;' . "\n";
} else {
    echo '    background-image: url(\'' . $GLOBALS['cfg']['RightBgImage'] . '\');' . "\n";
} // end if... else...
?>
    background-color: <?php echo $GLOBALS['cfg']['RightBgColor'] . "\n"; ?>
}
pre, tt         {font-size: <?php echo $GLOBALS['font_size']; ?>}
th              {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; font-weight: bold; color: #000000; background-color: <?php echo $GLOBALS['cfg']['ThBgcolor']; ?>}
td              {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>}
form            {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>}
input           {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>}
input.textfield {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; color: #000000; background-color: #FFFFFF}
select          {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; color: #000000; background-color: #FFFFFF}
textarea        {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; color: #000000; background-color: #FFFFFF}
h1              {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_biggest']; ?>; font-weight: bold}
h2              {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_bigger']; ?>; font-weight: bold}
h3              {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; font-weight: bold}
a:link          {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; text-decoration: none; color: #0000FF}
a:visited       {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; text-decoration: none; color: #0000FF}
a:hover         {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; text-decoration: underline; color: #FF0000}
a.nav:link      {font-family: <?php echo $GLOBALS['right_font_family']; ?>; color: #000000}
a.nav:visited   {font-family: <?php echo $GLOBALS['right_font_family']; ?>; color: #000000}
a.nav:hover     {font-family: <?php echo $GLOBALS['right_font_family']; ?>; color: #FF0000}
a.h1:link       {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_biggest']; ?>; font-weight: bold; color: #000000}
a.h1:active     {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_biggest']; ?>; font-weight: bold; color: #000000}
a.h1:visited    {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_biggest']; ?>; font-weight: bold; color: #000000}
a.h1:hover      {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_biggest']; ?>; font-weight: bold; color: #FF0000}
a.drop:link     {font-family: <?php echo $GLOBALS['right_font_family']; ?>; color: #ff0000}
a.drop:visited  {font-family: <?php echo $GLOBALS['right_font_family']; ?>; color: #ff0000}
a.drop:hover    {font-family: <?php echo $GLOBALS['right_font_family']; ?>; color: #ffffff; background-color:#ff0000; text-decoration: none}
dfn             {font-style: normal}
dfn:hover       {font-style: normal; cursor: help}
.nav            {font-family: <?php echo $GLOBALS['right_font_family']; ?>; color: #000000}
.warning        {font-family: <?php echo $GLOBALS['right_font_family']; ?>; font-size: <?php echo $GLOBALS['font_size']; ?>; font-weight: bold; color: #FF0000}
td.topline      {font-size: 1px}
td.tab          {
    border-top: 1px solid #999;
    border-right: 1px solid #666;
    border-left: 1px solid #999;
    border-bottom: none;
    border-radius: 2px;
    -moz-border-radius: 2px;
}
table.tabs      {
    border-top: none;
    border-right: none;
    border-left: none;
    border-bottom: 1px solid #666;
}

.print{font-family:arial;font-size:8pt;}

.syntax {font-family: sans-serif; font-size: <?php echo $font_smaller; ?>;}
.syntax_comment            {}
.syntax_digit              {}
.syntax_digit_hex          {}
.syntax_digit_integer      {}
.syntax_digit_float        {}
.syntax_punct              {}
.syntax_alpha              {text-transform: lowercase;}
.syntax_alpha_columnType   {text-transform: uppercase;}
.syntax_alpha_columnAttrib {text-transform: uppercase;}
.syntax_alpha_reservedWord {text-transform: uppercase; font-weight: bold;}
.syntax_alpha_functionName {text-transform: uppercase;}
.syntax_alpha_identifier   {}
.syntax_alpha_variable     {}
.syntax_quote              {}
.syntax_quote_backtick     {}
<?php
echo PMA_SQP_buildCssData();
?>
//-->
</style>

<?php
$title     = '';
if (isset($GLOBALS['db'])) {
    $title .= str_replace('\'', '\\\'', $GLOBALS['db']);
}
if (isset($GLOBALS['table'])) {
    $title .= (empty($title) ? '' : '.') . str_replace('\'', '\\\'', $GLOBALS['table']);
}
if (!empty($GLOBALS['cfg']['Server']) && isset($GLOBALS['cfg']['Server']['host'])) {
    $title .= (empty($title) ? 'phpMyAdmin ' : ' ')
           . sprintf($GLOBALS['strRunning'], (empty($GLOBALS['cfg']['Server']['verbose']) ? str_replace('\'', '\\\'', $GLOBALS['cfg']['Server']['host']) : str_replace('\'', '\\\'', $GLOBALS['cfg']['Server']['verbose'])));
}
$title     .= (empty($title) ? '' : ' - ') . 'phpMyAdmin ' . PMA_VERSION;
?>
<script type="text/javascript" language="javascript">
<!--
// Updates the title of the frameset if possible (ns4 does not allow this)
if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown'
    && typeof(parent.document.title) == 'string') {
    parent.document.title = '<?php echo $title; ?>';
}
<?php
// Add some javascript instructions if required
if (isset($js_to_run) && $js_to_run == 'functions.js') {
    echo "\n";
    ?>
// js form validation stuff
var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strFormEmpty']); ?>';
var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotNumber']); ?>';
var errorMsg2   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotValidNumber']); ?>';
var noDropDbMsg = '<?php echo((!$GLOBALS['cfg']['AllowUserDropDatabase']) ? str_replace('\'', '\\\'', $GLOBALS['strNoDropDatabases']) : ''); ?>';
var confirmMsg  = '<?php echo(($GLOBALS['cfg']['Confirm']) ? str_replace('\'', '\\\'', $GLOBALS['strDoYouReally']) : ''); ?>';
//-->
</script>
<script src="libraries/functions.js" type="text/javascript" language="javascript"></script>
    <?php
} else if (isset($js_to_run) && $js_to_run == 'user_details.js') {
    echo "\n";
    ?>
// js form validation stuff
var jsHostEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strHostEmpty']); ?>';
var jsUserEmpty       = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strUserEmpty']); ?>';
var jsPasswordEmpty   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordEmpty']); ?>';
var jsPasswordNotSame = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strPasswordNotSame']); ?>';
//-->
</script>
<script src="libraries/user_details.js" type="text/javascript" language="javascript"></script>
    <?php
} else if (isset($js_to_run) && $js_to_run == 'indexes.js') {
    echo "\n";
    ?>
// js index validation stuff
var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strFormEmpty']); ?>';
var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotNumber']); ?>';
var errorMsg2   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotValidNumber']); ?>';
//-->
</script>
<script src="libraries/indexes.js" type="text/javascript" language="javascript"></script>
    <?php
} else if (isset($js_to_run) && $js_to_run == 'tbl_change.js') {
    echo "\n";
    ?>
//-->
</script>
<script src="libraries/tbl_change.js" type="text/javascript" language="javascript"></script>
    <?php
} else {
    echo "\n";
    ?>
//-->
</script>
    <?php
}
echo "\n";
?>
</head>


<?php
if ($GLOBALS['cfg']['RightBgImage'] != '') {
    $bkg_img = ' background="' . $GLOBALS['cfg']['RightBgImage'] . '"';
} else {
    $bkg_img = '';
}
?>
<body bgcolor="<?php echo $GLOBALS['cfg']['RightBgColor'] . '"' . $bkg_img; ?>>
<?php
if (!defined('PMA_DISPLAY_HEADING')) {
    define('PMA_DISPLAY_HEADING', 1);
}
if (PMA_DISPLAY_HEADING) {
    $header_url_qry = '?lang=' . urlencode($GLOBALS['lang'])
                    . '&amp;convcharset=' . $GLOBALS['convcharset']
                    . '&amp;server=' . $GLOBALS['server'];
    echo '<h1>' . "\n";
    $server_info = (!empty($cfg['Server']['verbose'])
                    ? $cfg['Server']['verbose']
                    : $server_info = $cfg['Server']['host'] . (empty($cfg['Server']['port'])
                                                               ? ''
                                                               : ':' . $cfg['Server']['port']
                                                              )
                   );
    if (isset($GLOBALS['db'])) {
        echo '    ' . $GLOBALS['strDatabase'] . ' <i><a class="h1" href="db_details.php3' . $header_url_qry . '&amp;db=' . urlencode($GLOBALS['db']) . '">' . htmlspecialchars($GLOBALS['db']) . '</a></i>' . "\n";
        if (!empty($GLOBALS['table'])) {
            echo '    - ' . $GLOBALS['strTable'] . ' <i><a class="h1" href="tbl_properties.php3' . $header_url_qry . '&amp;db=' . urlencode($GLOBALS['db']) . '&amp;table=' . urlencode($GLOBALS['table']) . '">' . htmlspecialchars($GLOBALS['table']) . '</a></i>' . "\n";
        }
        echo '    ' . sprintf($GLOBALS['strRunning'], '<i>' . htmlspecialchars($server_info) . '</i>');
    } else {
        echo '    ' . sprintf($GLOBALS['strServer'], '<i>' . htmlspecialchars($server_info) . '</i>');
    }
    echo "\n" . '</h1>' . "\n";
}
echo "\n";


/**
 * Sets a variable to remember headers have been sent
 */
$GLOBALS['is_header_sent'] = TRUE;
?>
