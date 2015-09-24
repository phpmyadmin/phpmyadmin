<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This file generates the CSS code for the sprites of a theme
 *
 * @package PhpMyAdmin-theme
 */

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON')) {
    exit();
}

$bg = $_SESSION['PMA_Theme']->getImgPath() . 'sprites.png?v=' . urlencode(PMA_VERSION);
/* Check if there is a valid data file for sprites */
if (is_readable($_SESSION['PMA_Theme']->getPath() . '/sprites.lib.php')) {

    ?>
    /* Icon sprites */
    .icon {
    margin: 0;
    margin-<?php echo $left; ?>: .3em;
    padding: 0 !important;
    width: 16px;
    height: 16px;
    background-image: url('<?php echo $bg; ?>') !important;
    background-repeat: no-repeat !important;
    background-position: top left !important;
    }
    <?php

    include_once $_SESSION['PMA_Theme']->getPath() . '/sprites.lib.php';
    $sprites = array();
    if (function_exists('PMA_sprites')) {
        $sprites = PMA_sprites();
    }
    $template = ".ic_%s { background-position: 0 -%upx !important;%s%s }\n";
    foreach ($sprites as $name => $data) {
        // generate the CSS code for each icon
        $width = '';
        $height = '';
        // if either the height or width of an icon is 16px,
        // then it's pointless to set this as a parameter,
        //since it will be inherited from the "icon" class
        if ($data['width'] != 16) {
            $width = " width: " . $data['width'] . "px;";
        }
        if ($data['height'] != 16) {
            $height = " height: " . $data['height'] . "px;";
        }
        printf(
            $template,
            $name,
            ($data['position'] * 16),
            $width,
            $height
        );
    }
}
?>
