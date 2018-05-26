<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\ThemeGenerator class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Set of functions for Automated Theme Generator in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class ThemeGenerator
{

    /**
     * Colour Picker HTML file
     *
     * @return string HTML for the color picker tool
     */
    public function colorPicker()
    {
        $output = '<div id="container">';
        $output .= '<div id="palette" class="block">';
        $output .= '<div id="color-palette"></div>';
        $output .= '</div>';
        $output .= '<div id="picker" class="block">';
        $output .= '<div class="ui-color-picker" data-topic="picker" data-mode="HSB"></div>';
        $output .= '<div id="picker-samples" sample-id="master"></div>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * File creation form
     *
     * @return string HTML for the form submission
     */
    public function form()
    {
        $output = '<form action="#" method="post" id="save">';
        $output .= '<select name="type" id="theme">';
        $output .= '<option value="0">Triadic</option>';
        $output .= '<option value="1">Complementary</option>';
        $output .= '<option value="2">Adjacent</option>';
        $output .= '<option value="3">Monochrome</option>';
        $output .= '</select>';
        $output .= '<br>';
        $output .= '<input type="text" name="theme_name"></input>';
        $output .= '<br><br>';
        $output .= '<input type="submit">';
        $output .= '</form>';
        return $output;
    }

    /**
     * File creation function
     *
     * @param string $name name of new theme
     *
     * @return null
     */
    public function createLayoutFile($name)
    {
        if (!is_dir("themes/" . $name)) {
            mkdir("themes/" . $name);
        }
        $myfile = fopen("themes/" . $name . "/layout.inc.php", "w");
        $txt = '<?php
        declare(strict_types=1);
        
        $GLOBALS[\'cfg\'][\'NaviWidth\']                = \'250\';
        $GLOBALS[\'cfg\'][\'FontFamily\']               = \'"Open Sans", "Segoe UI"\';
        $GLOBALS[\'cfg\'][\'FontFamilyLight\']          = \'"Open Sans Light", "Segoe UI Light", "Segoe UI"; font-weight: 300\';
        $GLOBALS[\'cfg\'][\'FontFamilyFixed\']          = \'Consolas, Monospace, "Lucida Grande"\';

        $GLOBALS[\'cfg\'][\'Scheme\']                   = "' . $name . '";
        
        $GLOBALS[\'cfg\'][\'NaviColor\']                = \'#EEEEEE\';
        $GLOBALS[\'cfg\'][\'NaviBackground\']           = \'#377796\';
        $GLOBALS[\'cfg\'][\'NaviBackgroundLight\']      = \'#428EB4\';
        $GLOBALS[\'cfg\'][\'NaviPointerColor\']         = \'#333333\';
        $GLOBALS[\'cfg\'][\'NaviPointerBackground\']    = \'#377796\';
        $GLOBALS[\'cfg\'][\'NaviDatabaseNameColor\']    = \'#333333\';
        $GLOBALS[\'cfg\'][\'NaviHoverBackground\']      = \'#428EB4\';
        $GLOBALS[\'cfg\'][\'MainColor\']                = \'#444444\';
        $GLOBALS[\'cfg\'][\'MainBackground\']           = \'#FFFFFF\';
        $GLOBALS[\'cfg\'][\'BrowsePointerColor\']       = \'#377796\';
        $GLOBALS[\'cfg\'][\'BrowseMarkerColor\']        = \'#000000\';
        $GLOBALS[\'cfg\'][\'BrowseWarningColor\']       = \'#D44A26\';
        $GLOBALS[\'cfg\'][\'BrowseSuccessColor\']       = \'#01A31C\';
        $GLOBALS[\'cfg\'][\'BrowseGrayColor\']          = \'#CCCCCC\';
        $GLOBALS[\'cfg\'][\'BrowseMarkerBackground\']   = \'#EEEEEE\';
        $GLOBALS[\'cfg\'][\'BorderColor\']              = \'#DDDDDD\';
        $GLOBALS[\'cfg\'][\'ButtonColor\']              = \'#FFFFFF\';
        $GLOBALS[\'cfg\'][\'ButtonBackground\']         = \'#377796\';
        $GLOBALS[\'cfg\'][\'ButtonHover\']              = \'#428EB4\';
        $GLOBALS[\'cfg\'][\'ThBackground\']             = \'#F7F7F7\';
        $GLOBALS[\'cfg\'][\'ThDisabledBackground\']     = \'#F3F3F3\';
        $GLOBALS[\'cfg\'][\'ThColor\']                  = \'#666666\';
        $GLOBALS[\'cfg\'][\'ThPointerColor\']           = \'#000000\';
        $GLOBALS[\'cfg\'][\'BgOne\']                    = \'#F7F7F7\';
        $GLOBALS[\'cfg\'][\'BgTwo\']                    = \'#FFFFFF\';
        $GLOBALS[\'cfg\'][\'BlueHeader\']               = \'#3A7EAD\';';
        fwrite($myfile, $txt);
        fclose($myfile);
        return null;
    }
}

