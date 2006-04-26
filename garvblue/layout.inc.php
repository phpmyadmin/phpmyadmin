<?php
// Done By Garvin Hicking, http://www.supergarv.de/

$cfg['LeftWidth']           = 200;          // left frame width
/* colors */
$cfg['LeftBgColor']         = '#E8EAF1';    // background color for the left frame
$cfg['RightBgColor']        = '#FAFBFE';    // background color for the right frame
$cfg['RightBgImage']        = '';           // path to a background image for the right frame
                                            // (leave blank for no background image)
$cfg['LeftPointerColor']    = '#E8EAF1';    // color of the pointer in left frame
$cfg['Border']              = 0;            // border width on tables
$cfg['ThBgcolor']           = '#A4ABCA';    // table header row colour
$cfg['BgcolorOne']          = '#E2E4ED';    // table data row colour
$cfg['BgcolorTwo']          = '#C0C6DF';    // table data row colour, alternate
$cfg['BrowsePointerColor']  = '#F4A227';    // color of the pointer in browse mode
$cfg['BrowseMarkerColor']   = '#FAFED2';    // color of the marker (visually marks row
                                            // by clicking on it) in browse mode
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
