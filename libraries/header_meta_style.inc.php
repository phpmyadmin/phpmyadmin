<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

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
// removes the bug with the horizontal scrollbar in IE (it's allways shown, if need it or not)
echo "<?xml version=\"1.0\" encoding=\"" . $GLOBALS['charset'] . "\"?".">";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">
    <link rel="shortcut icon" href="favicon.ico" type="image/ico" />
<head>
<title><?php 
    if(!empty($page_title)) 
        echo htmlspecialchars($page_title); 
    else 
        echo 'phpMyAdmin';
?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
<?php
if (!empty($GLOBALS['cfg']['PmaAbsoluteUri'])) {
    echo '<base href="' . $GLOBALS['cfg']['PmaAbsoluteUri'] . '" />' . "\n";
}
?>
    <link rel="stylesheet" type="text/css" href="<?php echo defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : './'; ?>css/phpmyadmin.css.php?<?php echo PMA_generate_common_url(); ?>&amp;js_frame=<?php echo isset($print_view) ? 'print' : 'right'; ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : './'; ?>css/print.css?<?php echo PMA_generate_common_url(); ?>" media="print" />
