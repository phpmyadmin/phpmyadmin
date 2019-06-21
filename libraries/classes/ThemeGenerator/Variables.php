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
 * Function to create Layout.inc.php in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class Variables
{
    /**
     * Creates layout.inc.php
     *
     * @param array $post POST form data
     *
     * @return string $txt layout.inc.php file data
     */
    public function createVariablesFile(array $post)
    {
        $name = $post['theme_name'];
        $file = fopen("themes/" . $name . "/scss/_variables.scss", "w");
        /**
         * configures general layout
         * for detailed layout configuration please refer to the css files
         */
        /**
         * navi frame
         */
        // navi frame width
        $txt = '$navi-width                : 240px;';
        // foreground (text) color for the navi frame
        $txt .= '$navi-color : #000 ;';
        // background for the navi frame
        $txt .= '$navi-background           :  ' . $post['Navigation_Panel'] . ';';
        // foreground (text) color of the pointer in navi frame
        $txt .= '$navi-pointer-color         : #000;';
        // background of the pointer in navi frame
        $txt .= '$navi-pointer-background    : ' . $post['Navigation_Hover'] . ';';
        /**
         * main frame
         */
        // foreground (text) color for the main frame
        $txt .= '$main-color               : ' . $post['Text_Colour'] . ';';
        // background for the main frame
        $txt .= '$main-background           : ' . $post['Background_Colour'] . ';';
        // foreground (text) color of the pointer in browse mode
        $txt .= '$browse-pointer-color       : #000;';
        // background of the pointer in browse mode
        $txt .= '$browse-pointer-background  : #cfc;';
        // foreground (text) color of the marker (visually marks row by clicking on it)
        // in browse mode
        $txt .= '$browse-marker-color        : #000;';
        // background of the marker (visually marks row by clicking on it) in browse mode
        $txt .= '$browse-marker-background   : #fc9;';
        //server info header
        $txt .= '$header           : ' . $post['Header'] . ';';
        /**
         * fonts
         */
        /**
         * the font family as a valid css font family value,
         * if not set the browser default will be used
         * (depending on browser, DTD and system settings)
         */
        $txt .= '$font-family           :' . $post['font'] . ';';
        /**
         * fixed width font family, used in textarea
         */
        $txt .= '$font-family-fixed      : monospace ;';
        /**
         * tables
         */
        // border
        $txt .= '$border               : 0;';
        // table header and footer color
        // Dialogue Footer color
        $txt .= '$th-background         : ' . $post['Table_Header_and_Footer_Background'] . ';';
        // table header and footer background
        //text color footer and dialogue
        $txt .= '$th-color              : ' . $post['Table_Header_and_Footer_Text_Colour'] . ';';
        // table data row background
        // Dialougue result background
        $txt .= '$bg-one               :' . $post['Table_Row_Background'] . ';';
        // table data row background, alternate
        $txt .= '$bg-two:' . $post['Table_Row_Alternate_Background'] . ';';
        //table hover and selected
        $txt .= '$bg-three               :' . $post['Table_Row_Hover_and_Selected'] . ';';
        // Hyperlink Text
        $txt .= '$hyperlink           :' . $post['Hyperlink_Text'] . ';';
        // Group Background
        $txt .= '$group-bg            :' . $post['Group_Background'] . ';';

        // Check if the file is writable as this condition would only occur if files are overwritten.
        if ($file) {
            fwrite($file, $txt);
            fclose($file);
            return $txt;
        } else {
            trigger_error("The _variables.scss file is not writable by the webserver process. You must change permissions for the theme generator to be able to write the generated theme.", E_USER_ERROR);
            return;
        }
    }
}
