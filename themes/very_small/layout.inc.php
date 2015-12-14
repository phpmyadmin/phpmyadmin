<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */ 	 
/** 	 
 * 	 
 * @version $Id$ 	 
 * @package phpMyAdmin-theme 	 
 * @subpackage Very_small 	 
 */ 	 
 
/** 	 
 * 	 
 */
$cfg['LeftWidth']           = 170;          						// left frame width
/* colors */
$cfg['LeftBgColor']         = '#d9e4f4';    						// background color for the left frame
$cfg['RightBgColor']        = '#ffffff';    						// background color for the right frame
$cfg['RightBgImage']        = '';                 // path to a background image for the right frame
                                            						// (leave blank for no background image)
$cfg['LeftPointerColor']    = '#b4cae9';    						// color of the pointer in left frame
$cfg['Border']              = 0;            						// border width on tables
$cfg['ThBgcolor']           = '#e5e5e5';    						// table header row colour
$cfg['BgcolorOne']          = '#e6f0ff';    						// table data row colour
$cfg['BgcolorTwo']          = '#dbe7f9';    						// table data row colour, alternate
$cfg['BrowsePointerColor']  = '#b4cae9';    						// color of the pointer in browse mode
$cfg['BrowseMarkerColor']   = '#e9c7b4';    						// color of the marker (visually marks row
                                            						// by clicking on it) in browse mode
$cfg['BrowseMarkerBackground'] = '#b4cae9';						// background color of a marked item
$cfg['BrowseHoverBackground']  = '#e9c7b4';						// background color of a hovered item

$cfg['QueryWindowWidth']    = 550;          						// Width of Query window
$cfg['QueryWindowHeight']   = 310;          						// Height of Query window

/**
 * SQL Parser Settings
 */
$cfg['SQP']['fmtColor']     = array(        						// Syntax colouring data
    'comment'            => '#999999',
    'comment_mysql'      => '',
    'comment_ansi'       => '',
    'comment_c'          => '',
    'digit'              => '',
    'digit_hex'          => 'teal',
    'digit_integer'      => 'teal',
    'digit_float'        => 'aqua',
    'punct'              => '#cc0000',
    'alpha'              => '',
    'alpha_columnType'   => '#FF9900',
    'alpha_columnAttrib' => '#0000FF',
    'alpha_reservedWord' => '#cc0000',
    'alpha_functionName' => '#000099',
    'alpha_identifier'   => 'black',
    'alpha_charset'      => '#6495ed',
    'alpha_variable'     => '#800000',
    'quote'              => '#008000',
    'quote_double'       => '#000000',
    'quote_single'       => '#000000',
    'quote_backtick'     => ''
);
?>
