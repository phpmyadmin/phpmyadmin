<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Class with Font related methods.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Class with Font related methods.
 *
 * @package PhpMyAdmin
 */
class PMA_Font
{
    /**
     * Get list with characters and the corresponding width modifiers.
     *
     * @param string $font name of the font like Arial,sans-serif etc
     *
     * @return array with characters and corresponding width modifier
     * @access public
     */
    public static function getCharLists($font)
    {
        // list of characters and their width modifiers
        $charLists = array();

        //ijl
        $charLists[] = array("chars" => array("i", "j", "l"), "modifier" => 0.23);
        //f
        $charLists[] = array("chars" => array("f"), "modifier" => 0.27);
        //tI
        $charLists[] = array("chars" => array("t", "I"), "modifier" => 0.28);
        //r
        $charLists[] = array("chars" => array("r"), "modifier" => 0.34);
        //1
        $charLists[] = array("chars" => array("1"), "modifier" => 0.49);
        //cksvxyzJ
        $charLists[] = array(
            "chars" => array("c", "k", "s", "v", "x", "y", "z", "J"),
            "modifier" => 0.5
        );
        //abdeghnopquL023456789
        $charLists[] = array(
            "chars" => array(
                "a", "b", "d", "e", "g", "h", "n", "o", "p", "q", "u", "L",
                "0", "2", "3", "4", "5", "6", "7", "8", "9"
            ),
            "modifier" => 0.56
        );
        //FTZ
        $charLists[] = array("chars" => array("F", "T", "Z"), "modifier" => 0.61);
        //ABEKPSVXY
        $charLists[] = array(
            "chars" => array("A", "B", "E", "K", "P", "S", "V", "X", "Y"),
            "modifier" => 0.67
        );
        //wCDHNRU
        $charLists[] = array(
            "chars" => array("w", "C", "D", "H", "N", "R", "U"),
            "modifier" => 0.73
        );
        //GOQ
        $charLists[] = array("chars" => array("G", "O", "Q"), "modifier" => 0.78);
        //mM
        $charLists[] = array("chars" => array("m", "M"), "modifier" => 0.84);
        //W
        $charLists[] = array("chars" => array("W"), "modifier" => 0.95);
        //" "
        $charLists[] = array("chars" => array(" "), "modifier" => 0.28);

        return $charLists;
    }

    /**
     * Get width of string/text
     *
     * The text element width is calculated depending on font name
     * and font size.
     *
     * @param string  $text      string of which the width will be calculated
     * @param string  $font      name of the font like Arial,sans-serif etc
     * @param integer $fontSize  size of font
     * @param array   $charLists list of characters and their width modifiers
     *
     * @return integer width of the text
     * @access public
     */
    public static function getStringWidth($text, $font, $fontSize, $charLists = null)
    {
        if (empty($charLists) || !is_array($charLists)
            || !isset($charLists[0]["chars"]) || !is_array($charLists[0]["chars"])
            || !isset($charLists[0]["modifier"])
        ) {
            $charLists = self::getCharLists($font);
        }

        /*
         * Start by counting the width, giving each character a modifying value
         */
        $count = 0;

        foreach ($charLists as $charList) {
            $count += ((strlen($text)
                - strlen(str_replace($charList["chars"], "", $text))
                ) * $charList["modifier"]);
        }

        $text  = str_replace(" ", "", $text);//remove the " "'s
        //all other chars
        $count = $count + (strlen(preg_replace("/[a-z0-9]/i", "", $text)) * 0.3);

        $modifier = 1;
        $font = strtolower($font);
        switch ($font) {
        /*
         * no modifier for arial and sans-serif
         */
        case 'arial':
        case 'sans-serif':
            break;
        /*
         * .92 modifer for time, serif, brushscriptstd, and californian fb
         */
        case 'times':
        case 'serif':
        case 'brushscriptstd':
        case 'californian fb':
            $modifier = .92;
            break;
        /*
         * 1.23 modifier for broadway
         */
        case 'broadway':
            $modifier = 1.23;
            break;
        }
        $textWidth = $count*$fontSize;
        return ceil($textWidth*$modifier);
    }
}
?>
