<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * GIS styles for the pmahomme theme
 *
 * @package    PhpMyAdmin-theme
 * @subpackage PMAHomme
 */

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON') && ! defined('TESTSUITE')) {
    exit();
}
?>

.gis_table td {
    vertical-align: middle;
}

.gis_table select {
    min-width: 151px;
    margin: 6px;
}

.gis_table .button {
   text-align: <?php echo $right; ?>;
}

/**
 * GIS data editor styles
 */
a.close_gis_editor {
    float: <?php echo $right; ?>;
}

#gis_editor {
    display: none;
    position: fixed;
    _position: absolute; /* hack for IE */
    z-index: 1001;
    overflow-y: auto;
    overflow-x: hidden;
}

#gis_data {
    min-height: 230px;
}

#gis_data_textarea {
    height: 6em;
}

#gis_data_editor {
    background: #D0DCE0;
    padding: 15px;
    min-height: 500px;
}

#gis_data_editor .choice {
    display: none;
}

#gis_data_editor input[type="text"] {
    width: 75px;
}
