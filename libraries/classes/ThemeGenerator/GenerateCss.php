<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Creates Layout.inc.php file
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\ThemeGenerator;

/**
 * Function to convert scss files to css in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class GenerateCss
{
    /**
     * Generates theme.css and theme-rtl.css
     */
    public function GenerateCSSFiles($name){

        $file = fopen("themes/" . $name . "/scss/theme.scss", "w");

        $txt = '@import "direction";
        @import "variables";
        @import "common";
        @import "enum-editor";
        @import "gis";
        @import "navigation";
        @import "designer";
        @import "rte";
        @import "codemirror";
        @import "jqplot";
        @import "resizable-menu";
        @import "icons";';

        if ($file) {
            fwrite($file, $txt);
            fclose($file);
        } else {
            trigger_error("The theme.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
            
        }


        $file = fopen("themes/" . $name . "/scss/theme-rtl.scss", "w");

        $txt = '$direction: rtl;
                @import "theme";';

        if ($file) {
            fwrite($file, $txt);
            fclose($file);
        } else {
            trigger_error("The theme\-rtl.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
        }
        return null; 
    }
    
}    