<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Creates Layout.inc.php file
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Theme;

/**
 * Function to create Layout.inc.php in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class Layout
{
    /**
     * Creates layout.inc.php
     *
     * @param array $post POST form data
     *
     * @return string $txt layout.inc.php file data
     */
    public function createLayoutFile(array $post)
    {
        $name = $post['theme_name'];
        $file = fopen("themes/" . $name . "/layout.inc.php", "w");
        $txt = "<?php\n";
        /**
         * configures general layout
         * for detailed layout configuration please refer to the css files
         */
        $txt .= 'declare(strict_types=1);';
        /**
         * navi frame
         */
        // navi frame width
        $txt .= '$GLOBALS[\'cfg\'][\'NaviWidth\']                = 240;';
        // foreground (text) color for the navi frame
        $txt .= '$GLOBALS[\'cfg\'][\'NaviColor\']                = \'#000\';';
        // background for the navi frame
        $txt .= '$GLOBALS[\'cfg\'][\'NaviBackground\']           = \'' . $post['Navigation_Panel'] . '\';';
        // foreground (text) color of the pointer in navi frame
        $txt .= '$GLOBALS[\'cfg\'][\'NaviPointerColor\']         = \'#000\';';
        // background of the pointer in navi frame
        $txt .= '$GLOBALS[\'cfg\'][\'NaviPointerBackground\']    = \'' . $post['Navigation_Hover'] . '\';';
        /**
         * main frame
         */
        // foreground (text) color for the main frame
        $txt .= '$GLOBALS[\'cfg\'][\'MainColor\']                = \'' . $post['Text_Color'] . '\';';
        // background for the main frame
        $txt .= '$GLOBALS[\'cfg\'][\'MainBackground\']           = \'' . $post['Background_Color'] . '\';';
        // foreground (text) color of the pointer in browse mode
        $txt .= '$GLOBALS[\'cfg\'][\'BrowsePointerColor\']       = \'#000\';';
        // background of the pointer in browse mode
        $txt .= '$GLOBALS[\'cfg\'][\'BrowsePointerBackground\']  = \'#cfc\';';
        // foreground (text) color of the marker (visually marks row by clicking on it)
        // in browse mode
        $txt .= '$GLOBALS[\'cfg\'][\'BrowseMarkerColor\']        = \'#000\';';
        // background of the marker (visually marks row by clicking on it) in browse mode
        $txt .= '$GLOBALS[\'cfg\'][\'BrowseMarkerBackground\']   = \'#fc9\';';
        //server info header
        $txt .= '$GLOBALS[\'cfg\'][\'Header\']           = \'' . $post['Header'] . '\';';
        /**
         * fonts
         */
        /**
         * the font family as a valid css font family value,
         * if not set the browser default will be used
         * (depending on browser, DTD and system settings)
         */
        $txt .= '$GLOBALS[\'cfg\'][\'FontFamily\']           = \'sans-serif\';';
        /**
         * fixed width font family, used in textarea
         */
        $txt .= '$GLOBALS[\'cfg\'][\'FontFamilyFixed\']      = \'monospace\';';
        /**
         * tables
         */
        // border
        $txt .= '$GLOBALS[\'cfg\'][\'Border\']               = 0;';
        // table header and footer color
        // Dialogue Footer color
        $txt .= '$GLOBALS[\'cfg\'][\'ThBackground\']         = \'' . $post['Table_Header_and_Footer'] . '\';';
        // table header and footer background
        //text color footer and dialogue
        $txt .= '$GLOBALS[\'cfg\'][\'ThColor\']              = \'' . $post['Table_Header_and_Footer_Background'] . '\';';
        // table data row background
        // Dialougue result background
        $txt .= '$GLOBALS[\'cfg\'][\'BgOne\']                = \'' . $post['Table_Row_Background'] . '\';';
        // table data row background, alternate
        $txt .= '$GLOBALS[\'cfg\'][\'BgTwo\']                = \'' . $post['Table_Row_Alternate_Background'] . '\';';
        //table hover and selected
        $txt .= '$GLOBALS[\'cfg\'][\'BgThree\']                = \'' . $post['Table_Row_Hover_and_Selected'] . '\';';
        // Hyperlink Text
        $txt .= '$GLOBALS[\'cfg\'][\'Hyperlink\']            = \'' . $post['Hyperlink_Text'] . '\';';
        // Group Background
        $txt .= '$GLOBALS[\'cfg\'][\'GroupBg\']            = \'' . $post['Group_Background'] . '\';';

        fwrite($file, $txt);
        fclose($file);
        return $txt;
    }
}
