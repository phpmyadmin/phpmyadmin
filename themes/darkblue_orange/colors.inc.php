<?php

$cfg['LeftWidth']           = 180;          // left frame width
/* colors */
$cfg['LeftBgColor']         = '#666699';    // background color for the left frame
$cfg['RightBgColor']        = '#FFFFFF';    // background color for the right frame
$cfg['RightBgImage']        = '';           // path to a background image for the right frame
                                            // (leave blank for no background image)
$cfg['LeftPointerColor']    = '#9999CC';    // color of the pointer in left frame
                                            // (blank for no pointer)
$cfg['Border']              = 0;            // border width on tables
$cfg['ThBgcolor']           = '#666699';    // table header row colour
$cfg['BgcolorOne']          = '#EEEEEE';    // table data row colour
$cfg['BgcolorTwo']          = '#E5E5E5';    // table data row colour, alternate
$cfg['BrowsePointerColor']  = '#CCCCFF';    // color of the pointer in browse mode
                                            // (blank for no pointer)
$cfg['BrowseMarkerColor']   = '#FFCC99';    // color of the marker (visually marks row
                                            // by clicking on it) in browse mode
                                            // (blank for no marker)
/* rows and columns for inputs */
$cfg['TextareaCols']        = 40;           // textarea size (columns) in edit mode
                                            // (this value will be emphasized (*2) for sql
                                            // query textareas and (*1.25) for query window)
$cfg['TextareaRows']        = 7;            // textarea size (rows) in edit mode
$cfg['CharTextareaCols']    = 40;           // textarea size (columns) for CHAR/VARCHAR
$cfg['CharTextareaRows']    = 2;            // textarea size (rows) for CHAR/VARCHAR

/**
 * SQL Parser Settings
 */
$cfg['SQP']['fmtType']      = 'html';       // Pretty-printing style to use on queries (html, text, none)
$cfg['SQP']['fmtInd']       = '1';          // Amount to indent each level (floats ok)
$cfg['SQP']['fmtIndUnit']   = 'px';         // Units for indenting each level (CSS Types - {em,px,pt})
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