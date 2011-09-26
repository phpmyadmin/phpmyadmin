<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This file generates the CSS code for the sprites of a theme
 *
 * @package phpMyAdmin-theme
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}

$bg = $_SESSION['PMA_Theme']->getImgPath() . 'sprites.png';
?>
/* Icon sprites */
.icon, .footnotemarker {
    vertical-align: -3px;
    margin-right:   0.3em;
    margin-left:    0.3em;
    width:          16px;
    height:         16px;
    background:     url('<?php echo $bg; ?>') no-repeat top left;
}

<?php
    /* Check if there is a valid data file for sprites */
    if (is_readable($_SESSION['PMA_Theme']->getPath() . '/sprites.lib.php')) {
        include_once $_SESSION['PMA_Theme']->getPath() . '/sprites.lib.php';
        $sprites = array();
        if (function_exists('PMA_sprites')) {
            $sprites = PMA_sprites();
        }
        foreach ($sprites as $name => $data) {
            // generate the CSS code for each icon
            echo ".ic_$name { ";
            echo "background-position: 0 -" . ($data['position'] * 16) . "px;";
            // if either the height or width of an icon is 16px,
            // then it's pointless to set this as a parameter,
            //since it will be inherited from the "icon" class
            if ($data['width'] != 16) {
                echo " width: " . $data['width'] . "px;";
            }
            if ($data['height'] != 16) {
                echo " height: " . $data['height'] . "px;";
            }
            echo " }\n";
        }
        // Here we map some of the classes that we
        // defined above to other CSS selectors.
        // The indexes of the array correspond to
        // already defined classes and the values
        // are the selectors that we want to map to.
        $elements = array(
            's_sortable' => 'img.sortableIcon',
            's_asc'      => 'th.headerSortUp img.sortableIcon',
            's_desc'     => 'th.headerSortDown img.sortableIcon'
        );
        $template = "%s { background-position: 0 -%upx; "
                  . "height: %upx; width: %upx; }\n";
        foreach ($elements as $key => $value) {
            if (isset($sprites[$key])) { // If the CSS class has been defined
                printf(
                    $template,
                    $value,
                    ($sprites[$key]['position'] * 16),
                    $sprites[$key]['height'],
                    $sprites[$key]['width']
                );
            }
        }
    }
?>
