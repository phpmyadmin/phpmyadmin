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
 *
 */
if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
    die("GLOBALS overwrite attempt");
}

/**
 * Sends the beginning of the html page then returns to the calling script
 */
// Defines the cell alignment values depending on text direction
if ($GLOBALS['text_dir'] == 'ltr') {
    $GLOBALS['cell_align_left']  = 'left';
    $GLOBALS['cell_align_right'] = 'right';
} else {
    $GLOBALS['cell_align_left']  = 'right';
    $GLOBALS['cell_align_right'] = 'left';
}
// removes the bug with the horizontal scrollbar in IE (it's allways shown, if need it or not)
// xml declaration moves IE into quirks mode, making much trouble with CSS
/* echo '<?xml version="1.0" encoding="' . $GLOBALS['charset'] . '"?>'; */

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" lang="<?php echo $GLOBALS['available_languages'][$GLOBALS['lang']][2]; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <title><?php
    if (!empty($page_title)) {
        echo htmlspecialchars($page_title);
    } else {
        echo 'phpMyAdmin';
    }
?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : ''; ?>phpmyadmin.css.php?<?php echo PMA_generate_common_url(); ?>&amp;js_frame=<?php echo isset($print_view) ? 'print' : 'right'; ?>&amp;nocache=<?php echo $_SESSION['PMA_Config']->getThemeUniqueValue(); ?>" />
<?php if (defined('PMA_MOORAINBOW')) { ?>
    <link rel="stylesheet" type="text/css" href="js/mooRainbow/mooRainbow.css" />
<?php
      }
?>
    <link rel="stylesheet" type="text/css" href="<?php echo defined('PMA_PATH_TO_BASEDIR') ? PMA_PATH_TO_BASEDIR : ''; ?>print.css" media="print" />
    <meta name="robots" content="noindex,nofollow" />
