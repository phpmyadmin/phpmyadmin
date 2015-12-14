<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * configures general layout
 * for detailed layout configuration please refer to the css files
 *
 * @package phpMyAdmin-theme
 * @subpackage Original
 */
 
 
/**
 * Basic color 
 * Define color here
 */ 
 
$color['one'] 		= '#D0E7F4'; 
$color['two'] 		= '#4284B5';
$color['three'] 	= '#F8FAFC';
$color['four'] 		= '#0000FF';
$color['five'] 		= '#000000';
$color['six'] 		= '#FFFFFF';
$color['seven'] 	= '#999999';
$color['eight'] 	= '#386bcd';
$color['nine'] 		= '#FEFEFE';
$color['ten'] 		= '#333333';
$color['eleven'] 	= '#F5FAFC';
$color['twelve'] 	= '#7fcfe9';

/**
 * navi frame
 */
// navi frame width
$GLOBALS['cfg']['NaviWidth']                = 225;

// foreground (text) color for the navi frame
$GLOBALS['cfg']['NaviColor']                = $color['five'];

// background for the navi frame
$GLOBALS['cfg']['NaviBackground']           = $color['one'];

// foreground (text) color of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerColor']         = $color['nine'];
// background of the pointer in navi frame
$GLOBALS['cfg']['NaviPointerBackground']    = $color['two'];
// text color of the selected database name (when showing the table list)
$GLOBALS['cfg']['NaviDatabaseNameColor']    = $color['four'];

/**
 * main frame
 */
// foreground (text) color for the main frame
$GLOBALS['cfg']['MainColor']                = $color['five'];

// background for the main frame
$GLOBALS['cfg']['MainBackground']           = $color['three'];

// foreground (text) color of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerColor']       = $color['five'];

// background of the pointer in browse mode
$GLOBALS['cfg']['BrowsePointerBackground']  = '#CCFFCC';

// foreground (text) color of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerColor']        = $color['five'];

// background of the marker (visually marks row by clicking on it) in browse mode
$GLOBALS['cfg']['BrowseMarkerBackground']   = '#FFCC99';

/**
 * fonts
 */
/**
 * the font family as a valid css font family value,
 * if not set the browser default will be used
 * (depending on browser, DTD and system settings)
 */
$GLOBALS['cfg']['FontFamily']           = 'Arial,sans-serif';
/**
 * fixed width font family, used in textarea
 */
$GLOBALS['cfg']['FontFamilyFixed']      = 'monospace';

/**
 * tables
 */
// border
$GLOBALS['cfg']['Border']               = 0;
// table header and footer color
$GLOBALS['cfg']['ThBackground']         = $color['two'];
// table content color
$GLOBALS['cfg']['TblContentBackground'] = $color['six'];
// table header and footer background
$GLOBALS['cfg']['ThColor']              = $color['nine'];
// table data row background
$GLOBALS['cfg']['BgOne']                = $color['one'];
// table data row background, alternate
$GLOBALS['cfg']['BgTwo']                = $color['eleven'];


/**
 * misc
 */
// div header 
$GLOBALS['cfg']['HeaderBackground']     = $color['two']; 
$GLOBALS['cfg']['HeaderColor']     		= $color['nine']; 
  
// link color
$GLOBALS['cfg']['linkColor']            = '#21759B'; 

// label color
$GLOBALS['cfg']['LabelColor']           = $color['ten'];

// tableheader link color
$GLOBALS['cfg']['TopMenuColor'] 		= $color['eleven'];
$GLOBALS['cfg']['TableHeaderlinkColor'] = $color['one'];

$GLOBALS['cfg']['TableLinkColor'] 		= $color['eight'];

$GLOBALS['cfg']['TopMenuBgColor']       = $color['one']; 

// LightTabs
//$GLOBALS['cfg']['LightTabs']            = '#333'; 

/**
 * query window
 */
// Width of Query window
$GLOBALS['cfg']['QueryWindowWidth']     = 600;
// Height of Query window
$GLOBALS['cfg']['QueryWindowHeight']    = 400;

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
