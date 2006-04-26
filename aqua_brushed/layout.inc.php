<?php

$cfg['LeftWidth']           = 185;          // left frame width
/* colors */
$cfg['LeftBgColor']         = '#DDDDDD';    // background color for the left frame
$cfg['RightBgColor']        = '#FFFFFF';    // background color for the right frame
$cfg['RightBgImage']        = '';       // path to a background image for the right frame (problems with IE so I use the path directly in theme_right.css.php)
                                            // (leave blank for no background image)
$cfg['LeftPointerColor']    = '#9999CC';    // color of the pointer in left frame
$cfg['Border']              = 0;            // border width on tables
$cfg['ThBgcolor']           = '#666699';    // table header row colour
$cfg['BgcolorOne']          = '#EFEFEF';    // table data row colour
$cfg['BgcolorTwo']          = '#E5E5E5';    // table data row colour, alternate
$cfg['BrowsePointerColor']  = '#BDC5CC';    // color of the pointer in browse mode
$cfg['BrowseMarkerColor']   = '#CCCCCC';    // color of the marker (visually marks row
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
