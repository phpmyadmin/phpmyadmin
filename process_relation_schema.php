<?php
error_reporting(E_ALL | E_WARNING);
/**
 * Gets some core scripts
 */
require_once './libraries/common.inc.php';

/**
 * Settings for relation stuff
 */
require_once './libraries/relation.lib.php';
require_once './libraries/transformations.lib.php';
require_once './libraries/Index.class.php';

$cfgRelation = PMA_getRelationsParam();

/**
 * Now in ./libraries/relation.lib.php we check for all tables
 * that we need, but if we don't find them we are quiet about it
 * so people can work without.
 * This page is absolutely useless if you didn't set up your tables
 * correctly, so it is a good place to see which tables we can and
 * complain ;-)
 */
if (!$cfgRelation['pdfwork']) {
    echo '<font color="red">' . __('Error') . '</font><br />' . "\n";
    $url_to_goto = '<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $url_query . '">';
    echo sprintf(__('The additional features for working with linked tables have been deactivated. To find out why click %shere%s.'), $url_to_goto, '</a>') . "\n";
}

/**
 * Main logic
 */
try
	{
	$pdf_page_number        = isset($pdf_page_number) ? $pdf_page_number : 1;
	$show_grid              = (isset($show_grid) && $show_grid == 'on') ? 1 : 0;
	$show_color             = (isset($show_color) && $show_color == 'on') ? 1 : 0;
	$show_table_dimension   = (isset($show_table_dimension) && $show_table_dimension == 'on') ? 1 : 0;
	$all_table_same_wide    = (isset($all_table_same_wide) && $all_table_same_wide == 'on') ? 1 : 0;
	$with_doc               = (isset($with_doc) && $with_doc == 'on') ? 1 : 0;
	$orientation            = (isset($orientation) && $orientation == 'P') ? 'P' : 'L';
	$paper                  = isset($paper) ? $paper : 'A4';
	$show_keys              = (isset($show_keys) && $show_keys == 'on') ? 1 : 0;
	$export_type            = isset($export_type) ? $export_type : 'pdf';  // default is PDF
	PMA_DBI_select_db($db);
	
	switch($export_type)
		{
			case 'pdf';
				include_once("./libraries/schema/pdf_relation_schema.php");
				$obj_schema=new PMA_PDF_RELAION_SCHEMA($pdf_page_number, $show_table_dimension, $show_color,
													   $show_grid, $all_table_same_wide, $orientation, $paper,
														$show_keys
														);
				break;
			case 'svg';
				include_once("./libraries/schema/svg_relation_schema.php");
				$obj_schema=new PMA_SVG_RELAION_SCHEMA($pdf_page_number, $show_table_dimension, $show_color,
													   $all_table_same_wide,$show_keys);
				break;
			case 'dia';
				include_once("./libraries/schema/diaSchema.class.php");
				$obj_schema=new diaSchema();
				break;
			case 'visio';
				include_once("./libraries/schema/visioSchema.class.php");
				$obj_schema=new visioSchema();
				break;
			case 'eps';
				include_once("./libraries/schema/epsSchema.class.php");
				$obj_schema=new epsSchema();
				break;
		} 
}
catch (Exception $e)
{
	print('<pre>');
	print_r($e);
	print('</pre>');	
}
?>