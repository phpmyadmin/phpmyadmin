<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * configures general layout
 * for detailed layout configuration please refer to the css files
 *
 * @package PhpMyAdmin-theme
 * @subpackage Original
 */

/**
 * navi frame
 */
// navi frame width
$GLOBALS['cfg']['NaviWidth']                = 180;

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor']                = '#000000';

// background for the navi frame
$GLOBALS['cfg']['NaviBackground']           = '#F9F9F9';

// foreground (text) color of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerColor']         = '#000000';
// background of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerBackground']    = '#EEEEEE';

/**
 * main frame
 */
// foreground (text) color for the main frame
$GLOBALS['cfg']['MainColor']                = '#000000';

// background for the main frame
$GLOBALS['cfg']['MainBackground']           = '#F9F9F9';

// foreground (text) color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerColor']       = '#000000';

// background of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerBackground']  = '#FFFFFF';

// foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']        = '#000000';

// background of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerBackground']   = '#F2FAFE';

/**
 * fonts
 */
/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontFamily']           = 'Tahoma';
/**
 * fixed width font family, used in textarea
 */
$GLOBALS['cfg']['FontFamilyFixed']      = 'Tahoma';

/**
 * tables
 */
// border
$GLOBALS['cfg']['Border']               = 0;
// table header and footer color
$GLOBALS['cfg']['ThBackground']         = '#DDE8ED';
// table header and footer background
$GLOBALS['cfg']['ThColor']              = '#000000';
// table data row background
$GLOBALS['cfg']['BgOne']                = '#F9F9F9';
// table data row background, alternate
$GLOBALS['cfg']['BgTwo']                = '#EEEEEE';

/**
 * query window
 */
// Width of Query window
$GLOBALS['cfg']['QueryWindowWidth']     = 800;
// Height of Query window
$GLOBALS['cfg']['QueryWindowHeight']    = 600;

/**
 * SQL Parser Settings
 * Syntax colouring data
 */
$GLOBALS['cfg']['SQP']['fmtColor']      = array(
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

/**
 * Chart colors
 */

 $GLOBALS['cfg']['chartColor'] = array(
    'gradientIntensity'       => 0,
    // The style of the chart title.
    'titleColor'              => '#000000',
    'titleBgColor'            => $GLOBALS['cfg']['ThBackground'],
    // Chart border (0 for no border)
    'border'                  => '#CCCCCC',
    // Chart background color.
    'bgColor'                 => $GLOBALS['cfg']['BgTwo'],
    // when graph area gradient is used, this is the color of the graph
    // area border
    'graphAreaColor'          =>  '#D5D9DD',
    // the background color of the inner graph area
    'graphAreaGradientColor'  => $GLOBALS['cfg']['BgOne'],
    // the color of the grid lines in the graph area
    'gridColor'               => '#E6E6E6',
    // the color of the scale and the labels
    'scaleColor'              => '#D5D9DD',
 );

?>
