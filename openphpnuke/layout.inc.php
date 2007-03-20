<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage OpenPHPNuke
 */

/**
 *
 */
$cfg['LeftWidth']           = 250;          // left frame width
/* colors */
$cfg['LeftBgColor']         = '#1CC2F8';    // background color for the left frame
$cfg['RightBgColor']        = '#FFFFFF';    // background color for the right frame
$cfg['RightBgImage']        = '';           // path to a background image for the right frame
                                            // (leave blank for no background image)
$cfg['LeftPointerColor']    = '#CCFFCC';    // color of the pointer in left frame
$cfg['Border']              = 0;            // border width on tables
$cfg['ThBgcolor']           = '#FCFCFC';    // table header row colour
$cfg['BgcolorOne']          = '#E9ECEF';    // table data row colour
$cfg['BgcolorTwo']          = '#FFFFFF';    // table data row colour, alternate
$cfg['BrowsePointerColor']  = '#1CC2F8';    // color of the pointer in browse mode
$cfg['BrowseMarkerColor']   = '#FFCC99';    // color of the marker (visually marks row
                                            // by clicking on it) in browse mode

$cfg['QueryWindowWidth']    = 550;          // Width of Query window
$cfg['QueryWindowHeight']   = 310;          // Height of Query window

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
