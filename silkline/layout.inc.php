<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * configures general layout
 * for detailed layout configuration please refer to the css files
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Silkline
 */

/**
 * navi frame
 */

$cfg['LeftWidth']           = 250;          // left frame width
/* colors */
$cfg['LeftBgColor']         = '#ededed';    // background color for the left frame
$cfg['RightBgColor']        = '#ededed';    // background color for the right frame
$cfg['RightBgImage']        = '';           // path to a background image for the right frame
                                            // (leave blank for no background image)
$cfg['LeftPointerColor']    = '#525252';    // color of the pointer in left frame
$cfg['Border']              = 0;            // border width on tables
$cfg['ThBgcolor']           = '#DEDEDE';    // table header row colour
$cfg['BgcolorOne']          = '#F3F3F3';    // table data row colour
$cfg['BgcolorTwo']          = '#FFFFFF';    // table data row colour, alternate
$cfg['BrowsePointerColor']  = '#ffffd7';    // color of the pointer in browse mode
$cfg['BrowseMarkerColor']   = '#ffffd7';    // color of the marker (visually marks row
                                            // by clicking on it) in browse mode
$right_font_family = 'Tahoma, Arial, Helvetica, Verdana, sans-serif';
$font_size = '11px';
/**
 * SQL Parser Settings
 */
$cfg['SQP']['fmtColor']     = array(        // Syntax colouring data
    'comment'            => '#808000',
    'comment_mysql'      => '',
    'comment_ansi'       => '',
    'comment_c'          => '',
    'digit'              => '',
    'digit_hex'          => 'teal',
    'digit_integer'      => 'teal',
    'digit_float'        => 'aqua',
    'punct'              => 'fuchsia',
    'alpha'              => '',
    'alpha_columnType'   => '#FF9900',
    'alpha_columnAttrib' => '#0000FF',
    'alpha_reservedWord' => '#990099',
    'alpha_functionName' => '#FF0000',
    'alpha_identifier'   => 'black',
    'alpha_charset'      => '#6495ed',
    'alpha_variable'     => '#800000',
    'quote'              => '#008000',
    'quote_double'       => '',
    'quote_single'       => '',
    'quote_backtick'     => ''
);
?>
