<?php
//============================================================+
// File name   : tcpdf.php
// Begin       : 2002-08-03
// Last Update : 2006-10-27
// Author      : Nicola Asuni
// Version     : 1.53.0.TC026_PHP4
// License     : GNU LGPL (http://www.gnu.org/copyleft/lesser.html)
//
// Description : This is a PHP4 class for generating PDF files 
//               on-the-fly without requiring external 
//               extensions.
//
// IMPORTANT:
// This class is an extension and improvement of the public Domain 
// FPDF class by Olivier Plathey (http://www.fpdf.org).
//
// Main changes by Nicola Asuni:
//    PHP4 porting;
//    UTF-8 Unicode support;
//    code refactoring;
//    source code clean up;
//    code style and formatting;
//    source code documentation using phpDocumentor (www.phpdoc.org);
//    All ISO page formats were included;
//    image scale factor;
//    includes methods to parse and printsome XHTML code, supporting the following elements: h1, h2, h3, h4, h5, h6, b, u, i, a, img, p, br, strong, em, font, blockquote, li, ul, ol, hr, td, th, tr, table, sup, sub, small;
//    includes a method to print various barcode formats using an improved version of "Generic Barcode Render Class" by Karim Mribti (http://www.mribti.com/barcode/) (require GD library: http://www.boutell.com/gd/);
//    defines standard Header() and Footer() methods.
//============================================================+

/**
 * include configuration file
 */
// Disabled in phpMyAdmin
//require_once(dirname(__FILE__).'/config/tcpdf_config.php');


/**
 * TCPDF Class.
 * @package com.tecnick.tcpdf
 */
 
/**
 * This is a PHP4 class for generating PDF files on-the-fly without requiring external extensions.<br>
 * TCPDF project (http://tcpdf.sourceforge.net) is based on the public Domain FPDF class by Olivier Plathey (http://www.fpdf.org).<br>
 * <h3>TCPDF main changes from FPDF are:</h3><ul>
 * <li>PHP4 porting</li>
 * <li>UTF-8 Unicode support</li>
 * <li>source code clean up</li>
 * <li>code style and formatting</li>
 * <li>source code documentation using phpDocumentor (www.phpdoc.org)</li>
 * <li>All ISO page formats were included</li>
 * <li>image scale factor</li>
 * <li>includes methods to parse and printsome XHTML code, supporting the following elements: h1, h2, h3, h4, h5, h6, b, u, i, a, img, p, br, strong, em, font, blockquote, li, ul, ol, hr, td, th, tr, table, sup, sub, small;</li>
 * <li>includes a method to print various barcode formats using an improved version of "Generic Barcode Render Class" by Karim Mribti (http://www.mribti.com/barcode/) (require GD library: http://www.boutell.com/gd/)</li>
 * <li>defines standard Header() and Footer() methods.</li>
 * </ul>
 * Tools to encode your unicode fonts are on fonts/ttf2ufm directory.</p>
 * @name TCPDF
 * @package com.tecnick.tcpdf
 * @abstract Class for generating PDF files on-the-fly without requiring external extensions.
 * @author Nicola Asuni
 * @copyright 2004-2006 Tecnick.com S.r.l (www.tecnick.com) Via Ugo Foscolo n.19 - 09045 Quartu Sant'Elena (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.sourceforge.net
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 @version 1.53.0.TC026_PHP4
 */

if(!class_exists('TCPDF')) {
	/**
	 * define default PDF document producer
	 */ 
	define('PDF_PRODUCER','TCPDF 1.53.0.TC026_PHP4 (http://tcpdf.sourceforge.net)');
	
	/**
	* This is a PHP4 class for generating PDF files on-the-fly without requiring external extensions.<br>
	* This class is an extension and improvement of the FPDF class by Olivier Plathey (http://www.fpdf.org).<br>
	* This version contains some changes: [porting to PHP4, support for UTF-8 Unicode, code style and formatting, php documentation (www.phpdoc.org), ISO page formats, minor improvements, image scale factor]<br>
	* TCPDF project (http://tcpdf.sourceforge.net) is based on the public Domain FPDF class by Olivier Plathey (http://www.fpdf.org).<br>
	* To add your own TTF fonts please read /fonts/README.TXT
	* @name TCPDF
	* @package com.tecnick.tcpdf
	* @version 1.53.0.TC026_PHP4
	* @author Nicola Asuni
	* @link http://tcpdf.sourceforge.net
	* @license http://www.gnu.org/copyleft/lesser.html LGPL
	*/
	class TCPDF {
		//var properties

		/**
		* @var current page number
		* @access protected
		*/
		var $page;

		/**
		* @var current object number
		* @access protected
		*/
		var $n;

		/**
		* @var array of object offsets
		* @access protected
		*/
		var $offsets;

		/**
		* @var buffer holding in-memory PDF
		* @access protected
		*/
		var $buffer;

		/**
		* @var array containing pages
		* @access protected
		*/
		var $pages;

		/**
		* @var current document state
		* @access protected
		*/
		var $state;

		/**
		* @var compression flag
		* @access protected
		*/
		var $compress;

		/**
		* @var default orientation
		* @access protected
		*/
		var $DefOrientation;

		/**
		* @var current orientation
		* @access protected
		*/
		var $CurOrientation;

		/**
		* @var array indicating orientation changes
		* @access protected
		*/
		var $OrientationChanges;

		/**
		* @var scale factor (number of points in user unit)
		* @access protected
		*/
		var $k;

		/**
		* @var width of page format in points
		* @access protected
		*/
		var $fwPt;

		/**
		* @var height of page format in points
		* @access protected
		*/
		var $fhPt;

		/**
		* @var width of page format in user unit
		* @access protected
		*/
		var $fw;

		/**
		* @var height of page format in user unit
		* @access protected
		*/
		var $fh;

		/**
		* @var current width of page in points
		* @access protected
		*/
		var $wPt;

		/**
		* @var current height of page in points
		* @access protected
		*/
		var $hPt;

		/**
		* @var current width of page in user unit
		* @access protected
		*/
		var $w;

		/**
		* @var current height of page in user unit
		* @access protected
		*/
		var $h;

		/**
		* @var left margin
		* @access protected
		*/
		var $lMargin;

		/**
		* @var top margin
		* @access protected
		*/
		var $tMargin;

		/**
		* @var right margin
		* @access protected
		*/
		var $rMargin;

		/**
		* @var page break margin
		* @access protected
		*/
		var $bMargin;

		/**
		* @var cell margin
		* @access protected
		*/
		var $cMargin;

		/**
		* @var current horizontal position in user unit for cell positioning
		* @access protected
		*/
		var $x;

		/**
		* @var current vertical position in user unit for cell positioning
		* @access protected
		*/
		var $y;

		/**
		* @var height of last cell printed
		* @access protected
		*/
		var $lasth;

		/**
		* @var line width in user unit
		* @access protected
		*/
		var $LineWidth;

		/**
		* @var array of standard font names
		* @access protected
		*/
		var $CoreFonts;

		/**
		* @var array of used fonts
		* @access protected
		*/
		var $fonts;

		/**
		* @var array of font files
		* @access protected
		*/
		var $FontFiles;

		/**
		* @var array of encoding differences
		* @access protected
		*/
		var $diffs;

		/**
		* @var array of used images
		* @access protected
		*/
		var $images;

		/**
		* @var array of links in pages
		* @access protected
		*/
		var $PageLinks;

		/**
		* @var array of internal links
		* @access protected
		*/
		var $links;

		/**
		* @var current font family
		* @access protected
		*/
		var $FontFamily;

		/**
		* @var current font style
		* @access protected
		*/
		var $FontStyle;

		/**
		* @var underlining flag
		* @access protected
		*/
		var $underline;

		/**
		* @var current font info
		* @access protected
		*/
		var $CurrentFont;

		/**
		* @var current font size in points
		* @access protected
		*/
		var $FontSizePt;

		/**
		* @var current font size in user unit
		* @access protected
		*/
		var $FontSize;

		/**
		* @var commands for drawing color
		* @access protected
		*/
		var $DrawColor;

		/**
		* @var commands for filling color
		* @access protected
		*/
		var $FillColor;

		/**
		* @var commands for text color
		* @access protected
		*/
		var $TextColor;

		/**
		* @var indicates whether fill and text colors are different
		* @access protected
		*/
		var $ColorFlag;

		/**
		* @var word spacing
		* @access protected
		*/
		var $ws;

		/**
		* @var automatic page breaking
		* @access protected
		*/
		var $AutoPageBreak;

		/**
		* @var threshold used to trigger page breaks
		* @access protected
		*/
		var $PageBreakTrigger;

		/**
		* @var flag set when processing footer
		* @access protected
		*/
		var $InFooter;

		/**
		* @var zoom display mode
		* @access protected
		*/
		var $ZoomMode;

		/**
		* @var layout display mode
		* @access protected
		*/
		var $LayoutMode;

		/**
		* @var title
		* @access protected
		*/
		var $title;

		/**
		* @var subject
		* @access protected
		*/
		var $subject;

		/**
		* @var author
		* @access protected
		*/
		var $author;

		/**
		* @var keywords
		* @access protected
		*/
		var $keywords;

		/**
		* @var creator
		* @access protected
		*/
		var $creator;

		/**
		* @var alias for total number of pages
		* @access protected
		*/
		var $AliasNbPages;

		/**
		* @var right-bottom corner X coordinate of inserted image
		* @since 2002-07-31
		* @author Nicola Asuni
		* @access protected
		*/
		var $img_rb_x;

		/**
		* @var right-bottom corner Y coordinate of inserted image
		* @since 2002-07-31
		* @author Nicola Asuni
		* @access protected
		*/
		var $img_rb_y;

		/**
		* @var image scale factor
		* @since 2004-06-14
		* @author Nicola Asuni
		* @access protected
		*/
		var $imgscale = 1;

		/**
		* @var boolean set to true when the input text is unicode (require unicode fonts)
		* @since 2005-01-02
		* @author Nicola Asuni
		* @access protected
		*/
		var $isunicode = false;

		/**
		* @var PDF version
		* @since 1.5.3
		* @access protected
		*/
		var $PDFVersion = "1.3";
		
		
		// ----------------------
		
		/**
		 * @var Minimum distance between header and top page margin.
		 * @access private
		 */
		var $header_margin;
		
		/**
		 * @var Minimum distance between footer and bottom page margin.
		 * @access private
		 */
		var $footer_margin;
		
		/**
		 * @var original left margin value
		 * @access private
		 * @since 1.53.0.TC013
		 */
		var $original_lMargin;
		
		/**
		 * @var original right margin value
		 * @access private
		 * @since 1.53.0.TC013
		 */
		var $original_rMargin;
			
		/**
		 * @var Header font.
		 * @access private
		 */
		var $header_font;
		
		/**
		 * @var Footer font.
		 * @access private
		 */
		var $footer_font;
		
		/**
		 * @var Language templates.
		 * @access private
		 */
		var $l;
		
		/**
		 * @var Barcode to print on page footer (only if set).
		 * @access private
		 */
		var $barcode = false;
		
		/**
		 * @var If true prints header
		 * @access private
		 */
		var $print_header = true;
		
		/**
		 * @var If true prints footer.
		 * @access private
		 */
		var $print_footer = true;
		
		/**
		 * @var Header width (0 = full page width).
		 * @access private
		 */
		var $header_width = 0;
		
		/**
		 * @var Header image logo.
		 * @access private
		 */
		var $header_logo = "";
		
		/**
		 * @var Header image logo width in mm.
		 * @access private
		 */
		var $header_logo_width = 30;
		
		/**
		 * @var String to print as title on document header.
		 * @access private
		 */
		var $header_title = "";
		
		/**
		 * @var String to print on document header.
		 * @access private
		 */
		var $header_string = "";
		
		/**
		 * @var Default number of columns for html table.
		 * @access private
		 */
		var $default_table_columns = 4;
		
		
		// variables for html parser
		
		/**
		 * @var HTML PARSER: store current link.
		 * @access private
		 */
		var $HREF;
		
		/**
		 * @var HTML PARSER: store font list.
		 * @access private
		 */
		var $fontList;
		
		/**
		 * @var HTML PARSER: true when font attribute is set.
		 * @access private
		 */
		var $issetfont;
		
		/**
		 * @var HTML PARSER: true when color attribute is set.
		 * @access private
		 */
		var $issetcolor;
		
		/**
		 * @var HTML PARSER: true in case of ordered list (OL), false otherwise.
		 * @access private
		 */
		var $listordered = false;
		
		/**
		 * @var HTML PARSER: count list items.
		 * @access private
		 */
		var $listcount = 0;
		
		/**
		 * @var HTML PARSER: size of table border.
		 * @access private
		 */
		var $tableborder = 0;
		
		/**
		 * @var HTML PARSER: true at the beginning of table.
		 * @access private
		 */
		var $tdbegin = false;
		
		/**
		 * @var HTML PARSER: table width.
		 * @access private
		 */
		var $tdwidth = 0;
		
		/**
		 * @var HTML PARSER: table height.
		 * @access private
		 */
		var $tdheight = 0;
		
		/**
		 * @var HTML PARSER: table align.
		 * @access private
		 */
		var $tdalign = "L";
		
		/**
		 * @var HTML PARSER: table background color.
		 * @access private
		 */
		var $tdbgcolor = false;
		
		/**
		 * @var Store temporary font size in points.
		 * @access private
		 */
		var $tempfontsize = 10;
		
		/**
		 * @var Bold font style status.
		 * @access private
		 */
		var $b;
		
		/**
		 * @var Underlined font style status.
		 * @access private
		 */
		var $u;
		
		/**
		 * @var Italic font style status.
		 * @access private
		 */
		var $i;
		
		/**
		 * @var spacer for LI tags.
		 * @access private
		 */
		var $lispacer = "";
		
		/**
		 * @var default encoding
		 * @access private
		 * @since 1.53.0.TC010
		 */
		var $encoding = "UTF-8";
		
		/**
		 * @var PHP internal encoding
		 * @access private
		 * @since 1.53.0.TC016
		 */
		var $internal_encoding;
		
		/**
		 * @var store previous fill color as RGB array
		 * @access private
		 * @since 1.53.0.TC017
		 */
		var $prevFillColor = array(255,255,255);
		
		/**
		 * @var store previous text color as RGB array
		 * @access private
		 * @since 1.53.0.TC017
		 */
		var $prevTextColor = array(0,0,0);
		
		/**
		 * @var store previous font family
		 * @access private
		 * @since 1.53.0.TC017
		 */
		var $prevFontFamily;
		
		/**
		 * @var store previous font style
		 * @access private
		 * @since 1.53.0.TC017
		 */
		var $prevFontStyle;

		//------------------------------------------------------------
		// var methods
		//------------------------------------------------------------

		/**
		 * This is the class constructor. 
		 * It allows to set up the page format, the orientation and 
		 * the measure unit used in all the methods (except for the font sizes).
		 * @since 1.0
		 * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or Portrait (default)</li><li>L or Landscape</li></ul>
		 * @param string $unit User measure unit. Possible values are:<ul><li>pt: point</li><li>mm: millimeter (default)</li><li>cm: centimeter</li><li>in: inch</li></ul><br />A point equals 1/72 of inch, that is to say about 0.35 mm (an inch being 2.54 cm). This is a very common unit in typography; font sizes are expressed in that unit.
		 * @param mixed $format The format used for pages. It can be either one of the following values (case insensitive) or a custom format in the form of a two-element array containing the width and the height (expressed in the unit given by unit).<ul><li>4A0</li><li>2A0</li><li>A0</li><li>A1</li><li>A2</li><li>A3</li><li>A4 (default)</li><li>A5</li><li>A6</li><li>A7</li><li>A8</li><li>A9</li><li>A10</li><li>B0</li><li>B1</li><li>B2</li><li>B3</li><li>B4</li><li>B5</li><li>B6</li><li>B7</li><li>B8</li><li>B9</li><li>B10</li><li>C0</li><li>C1</li><li>C2</li><li>C3</li><li>C4</li><li>C5</li><li>C6</li><li>C7</li><li>C8</li><li>C9</li><li>C10</li><li>RA0</li><li>RA1</li><li>RA2</li><li>RA3</li><li>RA4</li><li>SRA0</li><li>SRA1</li><li>SRA2</li><li>SRA3</li><li>SRA4</li><li>LETTER</li><li>LEGAL</li><li>EXECUTIVE</li><li>FOLIO</li></ul>
		 * @param boolean $unicode TRUE means that the input text is unicode (default = true)
		 * @param String $encoding charset encoding; default is UTF-8
		 */
		function TCPDF($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding="UTF-8") {
			
			/* Set internal character encoding to ASCII */
			if (function_exists("mb_internal_encoding") AND mb_internal_encoding()) {
				$this->internal_encoding = mb_internal_encoding();
				mb_internal_encoding("ASCII");
			}
				
			//Some checks
			$this->_dochecks();
			//Initialization of properties
			$this->isunicode=$unicode;
			$this->page=0;
			$this->n=2;
			$this->buffer='';
			$this->pages=array();
			$this->OrientationChanges=array();
			$this->state=0;
			$this->fonts=array();
			$this->FontFiles=array();
			$this->diffs=array();
			$this->images=array();
			$this->links=array();
			$this->InFooter=false;
			$this->lasth=0;
			$this->FontFamily='';
			$this->FontStyle='';
			$this->FontSizePt=12;
			$this->underline=false;
			$this->DrawColor='0 G';
			$this->FillColor='0 g';
			$this->TextColor='0 g';
			$this->ColorFlag=false;
			$this->ws=0;
			//Standard Unicode fonts
			$this->CoreFonts=array(
			'courier'=>'Courier',
			'courierB'=>'Courier-Bold',
			'courierI'=>'Courier-Oblique',
			'courierBI'=>'Courier-BoldOblique',
			'helvetica'=>'Helvetica',
			'helveticaB'=>'Helvetica-Bold',
			'helveticaI'=>'Helvetica-Oblique',
			'helveticaBI'=>'Helvetica-BoldOblique',
			'times'=>'Times-Roman',
			'timesB'=>'Times-Bold',
			'timesI'=>'Times-Italic',
			'timesBI'=>'Times-BoldItalic',
			'symbol'=>'Symbol',
			'zapfdingbats'=>'ZapfDingbats'
			);

			//Scale factor
			// 2003-06-11 - Nicola Asuni : changed if/else with switch statement
			switch (strtolower($unit)){
				case 'pt': {$this->k=1; break;}
				case 'mm': {$this->k=72/25.4; break;}
				case 'cm': {$this->k=72/2.54; break;}
				case 'in': {$this->k=72; break;}
				default : {$this->Error('Incorrect unit: '.$unit); break;}
			}

			//Page format
			if(is_string($format)) {
				// 2002-07-24 - Nicola Asuni (info@tecnick.com)
				// Added new page formats (45 standard ISO paper formats and 4 american common formats).
				// Paper cordinates are calculated in this way: (inches * 72) where (1 inch = 2.54 cm)
				switch (strtoupper($format)){
					case '4A0': {$format = array(4767.87,6740.79); break;}
					case '2A0': {$format = array(3370.39,4767.87); break;}
					case 'A0': {$format = array(2383.94,3370.39); break;}
					case 'A1': {$format = array(1683.78,2383.94); break;}
					case 'A2': {$format = array(1190.55,1683.78); break;}
					case 'A3': {$format = array(841.89,1190.55); break;}
					case 'A4': default: {$format = array(595.28,841.89); break;}
					case 'A5': {$format = array(419.53,595.28); break;}
					case 'A6': {$format = array(297.64,419.53); break;}
					case 'A7': {$format = array(209.76,297.64); break;}
					case 'A8': {$format = array(147.40,209.76); break;}
					case 'A9': {$format = array(104.88,147.40); break;}
					case 'A10': {$format = array(73.70,104.88); break;}
					case 'B0': {$format = array(2834.65,4008.19); break;}
					case 'B1': {$format = array(2004.09,2834.65); break;}
					case 'B2': {$format = array(1417.32,2004.09); break;}
					case 'B3': {$format = array(1000.63,1417.32); break;}
					case 'B4': {$format = array(708.66,1000.63); break;}
					case 'B5': {$format = array(498.90,708.66); break;}
					case 'B6': {$format = array(354.33,498.90); break;}
					case 'B7': {$format = array(249.45,354.33); break;}
					case 'B8': {$format = array(175.75,249.45); break;}
					case 'B9': {$format = array(124.72,175.75); break;}
					case 'B10': {$format = array(87.87,124.72); break;}
					case 'C0': {$format = array(2599.37,3676.54); break;}
					case 'C1': {$format = array(1836.85,2599.37); break;}
					case 'C2': {$format = array(1298.27,1836.85); break;}
					case 'C3': {$format = array(918.43,1298.27); break;}
					case 'C4': {$format = array(649.13,918.43); break;}
					case 'C5': {$format = array(459.21,649.13); break;}
					case 'C6': {$format = array(323.15,459.21); break;}
					case 'C7': {$format = array(229.61,323.15); break;}
					case 'C8': {$format = array(161.57,229.61); break;}
					case 'C9': {$format = array(113.39,161.57); break;}
					case 'C10': {$format = array(79.37,113.39); break;}
					case 'RA0': {$format = array(2437.80,3458.27); break;}
					case 'RA1': {$format = array(1729.13,2437.80); break;}
					case 'RA2': {$format = array(1218.90,1729.13); break;}
					case 'RA3': {$format = array(864.57,1218.90); break;}
					case 'RA4': {$format = array(609.45,864.57); break;}
					case 'SRA0': {$format = array(2551.18,3628.35); break;}
					case 'SRA1': {$format = array(1814.17,2551.18); break;}
					case 'SRA2': {$format = array(1275.59,1814.17); break;}
					case 'SRA3': {$format = array(907.09,1275.59); break;}
					case 'SRA4': {$format = array(637.80,907.09); break;}
					case 'LETTER': {$format = array(612.00,792.00); break;}
					case 'LEGAL': {$format = array(612.00,1008.00); break;}
					case 'EXECUTIVE': {$format = array(521.86,756.00); break;}
					case 'FOLIO': {$format = array(612.00,936.00); break;}
					// default: {$this->Error('Unknown page format: '.$format); break;}
					// END CHANGES Nicola Asuni
				}
				$this->fwPt=$format[0];
				$this->fhPt=$format[1];
			}
			else {
				$this->fwPt=$format[0]*$this->k;
				$this->fhPt=$format[1]*$this->k;
			}

			$this->fw=$this->fwPt/$this->k;
			$this->fh=$this->fhPt/$this->k;

			//Page orientation
			$orientation=strtolower($orientation);
			if($orientation=='p' or $orientation=='portrait') {
				$this->DefOrientation='P';
				$this->wPt=$this->fwPt;
				$this->hPt=$this->fhPt;
			}
			elseif($orientation=='l' or $orientation=='landscape') {
				$this->DefOrientation='L';
				$this->wPt=$this->fhPt;
				$this->hPt=$this->fwPt;
			}
			else {
				$this->Error('Incorrect orientation: '.$orientation);
			}

			$this->CurOrientation=$this->DefOrientation;
			$this->w=$this->wPt/$this->k;
			$this->h=$this->hPt/$this->k;
			//Page margins (1 cm)
			$margin=28.35/$this->k;
			$this->SetMargins($margin,$margin);
			//Interior cell margin (1 mm)
			$this->cMargin=$margin/10;
			//Line width (0.2 mm)
			$this->LineWidth=.567/$this->k;
			//Automatic page break
			$this->SetAutoPageBreak(true,2*$margin);
			//Full width display mode
			$this->SetDisplayMode('fullwidth');
			//Compression
			$this->SetCompression(true);
			//Set default PDF version number
			$this->PDFVersion = "1.3";
			
			$this->encoding = $encoding;
			$this->b = 0;
			$this->i = 0;
			$this->u = 0;
			$this->HREF = '';
			$this->fontlist = array("arial", "times", "courier", "helvetica", "symbol");
			$this->issetfont = false;
			$this->issetcolor = false;
			$this->tableborder = 0;
			$this->tdbegin = false;
			$this->tdwidth=  0;
			$this->tdheight = 0;
			$this->tdalign = "L";
			$this->tdbgcolor = false;
			
			$this->SetFillColor(200, 200, 200, true);
			$this->SetTextColor(0, 0, 0, true);
		}

		/**
		* Set the image scale.
		* @param float $scale image scale.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function setImageScale($scale) {
			$this->imgscale=$scale;
		}

		/**
		* Returns the image scale.
		* @return float image scale.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getImageScale() {
			return $this->imgscale;
		}

		/**
		* Returns the page width in units.
		* @return int page width.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getPageWidth() {
			return $this->w;
		}

		/**
		* Returns the page height in units.
		* @return int page height.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getPageHeight() {
			return $this->h;
		}

		/**
		* Returns the page break margin.
		* @return int page break margin.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getBreakMargin() {
			return $this->bMargin;
		}

		/**
		* Returns the scale factor (number of points in user unit).
		* @return int scale factor.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getScaleFactor() {
			return $this->k;
		}

		/**
		* Defines the left, top and right margins. By default, they equal 1 cm. Call this method to change them.
		* @param float $left Left margin.
		* @param float $top Top margin.
		* @param float $right Right margin. Default value is the left one.
		* @since 1.0
		* @see SetLeftMargin(), SetTopMargin(), SetRightMargin(), SetAutoPageBreak()
		*/
		function SetMargins($left, $top, $right=-1) {
			//Set left, top and right margins
			$this->lMargin=$left;
			$this->tMargin=$top;
			if($right==-1) {
				$right=$left;
			}
			$this->rMargin=$right;
		}

		/**
		* Defines the left margin. The method can be called before creating the first page. If the current abscissa gets out of page, it is brought back to the margin.
		* @param float $margin The margin.
		* @since 1.4
		* @see SetTopMargin(), SetRightMargin(), SetAutoPageBreak(), SetMargins()
		*/
		function SetLeftMargin($margin) {
			//Set left margin
			$this->lMargin=$margin;
			if(($this->page>0) and ($this->x<$margin)) {
				$this->x=$margin;
			}
		}

		/**
		* Defines the top margin. The method can be called before creating the first page.
		* @param float $margin The margin.
		* @since 1.5
		* @see SetLeftMargin(), SetRightMargin(), SetAutoPageBreak(), SetMargins()
		*/
		function SetTopMargin($margin) {
			//Set top margin
			$this->tMargin=$margin;
		}

		/**
		* Defines the right margin. The method can be called before creating the first page.
		* @param float $margin The margin.
		* @since 1.5
		* @see SetLeftMargin(), SetTopMargin(), SetAutoPageBreak(), SetMargins()
		*/
		function SetRightMargin($margin) {
			//Set right margin
			$this->rMargin=$margin;
		}

		/**
		* Enables or disables the automatic page breaking mode. When enabling, the second parameter is the distance from the bottom of the page that defines the triggering limit. By default, the mode is on and the margin is 2 cm.
		* @param boolean $auto Boolean indicating if mode should be on or off.
		* @param float $margin Distance from the bottom of the page.
		* @since 1.0
		* @see Cell(), MultiCell(), AcceptPageBreak()
		*/
		function SetAutoPageBreak($auto, $margin=0) {
			//Set auto page break mode and triggering margin
			$this->AutoPageBreak=$auto;
			$this->bMargin=$margin;
			$this->PageBreakTrigger=$this->h-$margin;
		}

		/**
		* Defines the way the document is to be displayed by the viewer. The zoom level can be set: pages can be displayed entirely on screen, occupy the full width of the window, use real size, be scaled by a specific zooming factor or use viewer default (configured in the Preferences menu of Acrobat). The page layout can be specified too: single at once, continuous display, two columns or viewer default. By default, documents use the full width mode with continuous display.
		* @param mixed $zoom The zoom to use. It can be one of the following string values or a number indicating the zooming factor to use. <ul><li>fullpage: displays the entire page on screen </li><li>fullwidth: uses maximum width of window</li><li>real: uses real size (equivalent to 100% zoom)</li><li>default: uses viewer default mode</li></ul>
		* @param string $layout The page layout. Possible values are:<ul><li>single: displays one page at once</li><li>continuous: displays pages continuously (default)</li><li>two: displays two pages on two columns</li><li>default: uses viewer default mode</li></ul>
		* @since 1.2
		*/
		function SetDisplayMode($zoom, $layout='continuous') {
			//Set display mode in viewer
			if($zoom=='fullpage' or $zoom=='fullwidth' or $zoom=='real' or $zoom=='default' or !is_string($zoom)) {
				$this->ZoomMode=$zoom;
			}
			else {
				$this->Error('Incorrect zoom display mode: '.$zoom);
			}
			if($layout=='single' or $layout=='continuous' or $layout=='two' or $layout=='default') {
				$this->LayoutMode=$layout;
			}
			else {
				$this->Error('Incorrect layout display mode: '.$layout);
			}
		}

		/**
		* Activates or deactivates page compression. When activated, the internal representation of each page is compressed, which leads to a compression ratio of about 2 for the resulting document. Compression is on by default.
		* Note: the Zlib extension is required for this feature. If not present, compression will be turned off.
		* @param boolean $compress Boolean indicating if compression must be enabled.
		* @since 1.4
		*/
		function SetCompression($compress) {
			//Set page compression
			if(function_exists('gzcompress')) {
				$this->compress=$compress;
			}
			else {
				$this->compress=false;
			}
		}

		/**
		* Defines the title of the document.
		* @param string $title The title.
		* @since 1.2
		* @see SetAuthor(), SetCreator(), SetKeywords(), SetSubject()
		*/
		function SetTitle($title) {
			//Title of document
			$this->title=$title;
		}

		/**
		* Defines the subject of the document.
		* @param string $subject The subject.
		* @since 1.2
		* @see SetAuthor(), SetCreator(), SetKeywords(), SetTitle()
		*/
		function SetSubject($subject) {
			//Subject of document
			$this->subject=$subject;
		}

		/**
		* Defines the author of the document.
		* @param string $author The name of the author.
		* @since 1.2
		* @see SetCreator(), SetKeywords(), SetSubject(), SetTitle()
		*/
		function SetAuthor($author) {
			//Author of document
			$this->author=$author;
		}

		/**
		* Associates keywords with the document, generally in the form 'keyword1 keyword2 ...'.
		* @param string $keywords The list of keywords.
		* @since 1.2
		* @see SetAuthor(), SetCreator(), SetSubject(), SetTitle()
		*/
		function SetKeywords($keywords) {
			//Keywords of document
			$this->keywords=$keywords;
		}

		/**
		* Defines the creator of the document. This is typically the name of the application that generates the PDF.
		* @param string $creator The name of the creator.
		* @since 1.2
		* @see SetAuthor(), SetKeywords(), SetSubject(), SetTitle()
		*/
		function SetCreator($creator) {
			//Creator of document
			$this->creator=$creator;
		}

		/**
		* Defines an alias for the total number of pages. It will be substituted as the document is closed.<br />
		* <b>Example:</b><br />
		* <pre>
		* class PDF extends TCPDF {
		* 	function Footer() {
		* 		//Go to 1.5 cm from bottom
		* 		$this->SetY(-15);
		* 		//Select Arial italic 8
		* 		$this->SetFont('Arial','I',8);
		* 		//Print current and total page numbers
		* 		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
		* 	}
		* }
		* $pdf=new PDF();
		* $pdf->AliasNbPages();
		* </pre>
		* @param string $alias The alias. Default value: {nb}.
		* @since 1.4
		* @see PageNo(), Footer()
		*/
		function AliasNbPages($alias='{nb}') {
			//Define an alias for total number of pages
			$this->AliasNbPages = $this->_escapetext($alias);
		}

		/**
		* This method is automatically called in case of fatal error; it simply outputs the message and halts the execution. An inherited class may override it to customize the error handling but should always halt the script, or the resulting document would probably be invalid.
		* 2004-06-11 :: Nicola Asuni : changed bold tag with strong
		* @param string $msg The error message
		* @since 1.0
		*/
		function Error($msg) {
			//Fatal error
			die('<strong>TCPDF error: </strong>'.$msg);
		}

		/**
		* This method begins the generation of the PDF document. It is not necessary to call it explicitly because AddPage() does it automatically.
		* Note: no page is created by this method
		* @since 1.0
		* @see AddPage(), Close()
		*/
		function Open() {
			//Begin document
			$this->state=1;
		}

		/**
		* Terminates the PDF document. It is not necessary to call this method explicitly because Output() does it automatically. If the document contains no page, AddPage() is called to prevent from getting an invalid document.
		* @since 1.0
		* @see Open(), Output()
		*/
		function Close() {
			//Terminate document
			if($this->state==3) {
				return;
			}
			if($this->page==0) {
				$this->AddPage();
			}
			//Page footer
			$this->InFooter=true;
			$this->Footer();
			$this->InFooter=false;
			//Close page
			$this->_endpage();
			//Close document
			$this->_enddoc();
		}

		/**
		* Adds a new page to the document. If a page is already present, the Footer() method is called first to output the footer. Then the page is added, the current position set to the top-left corner according to the left and top margins, and Header() is called to display the header.
		* The font which was set before calling is automatically restored. There is no need to call SetFont() again if you want to continue with the same font. The same is true for colors and line width.
		* The origin of the coordinate system is at the top-left corner and increasing ordinates go downwards.
		* @param string $orientation Page orientation. Possible values are (case insensitive):<ul><li>P or Portrait</li><li>L or Landscape</li></ul> The default value is the one passed to the constructor.
		* @since 1.0
		* @see TCPDF(), Header(), Footer(), SetMargins()
		*/
		function AddPage($orientation='') {
			//Start a new page
			if($this->state==0) {
				$this->Open();
			}
			$family=$this->FontFamily;
			$style=$this->FontStyle.($this->underline ? 'U' : '');
			$size=$this->FontSizePt;
			$lw=$this->LineWidth;
			$dc=$this->DrawColor;
			$fc=$this->FillColor;
			$tc=$this->TextColor;
			$cf=$this->ColorFlag;
			if($this->page>0) {
				//Page footer
				$this->InFooter=true;
				$this->Footer();
				$this->InFooter=false;
				//Close page
				$this->_endpage();
			}
			//Start new page
			$this->_beginpage($orientation);
			//Set line cap style to square
			$this->_out('2 J');
			//Set line width
			$this->LineWidth=$lw;
			$this->_out(sprintf('%.2f w',$lw*$this->k));
			//Set font
			if($family) {
				$this->SetFont($family,$style,$size);
			}
			//Set colors
			$this->DrawColor=$dc;
			if($dc!='0 G') {
				$this->_out($dc);
			}
			$this->FillColor=$fc;
			if($fc!='0 g') {
				$this->_out($fc);
			}
			$this->TextColor=$tc;
			$this->ColorFlag=$cf;
			//Page header
			$this->Header();
			//Restore line width
			if($this->LineWidth!=$lw) {
				$this->LineWidth=$lw;
				$this->_out(sprintf('%.2f w',$lw*$this->k));
			}
			//Restore font
			if($family) {
				$this->SetFont($family,$style,$size);
			}
			//Restore colors
			if($this->DrawColor!=$dc) {
				$this->DrawColor=$dc;
				$this->_out($dc);
			}
			if($this->FillColor!=$fc) {
				$this->FillColor=$fc;
				$this->_out($fc);
			}
			$this->TextColor=$tc;
			$this->ColorFlag=$cf;
		}
		
		
		
		/**
	 	 * Set header data.
		 * @param string $ln header image logo
		 * @param string $lw header image logo width in mm
		 * @param string $ht string to print as title on document header
		 * @param string $hs string to print on document header
		*/
		function setHeaderData($ln="", $lw=0, $ht="", $hs="") {
			$this->header_logo = $ln;
			$this->header_logo_width = $lw;
			$this->header_title = $ht;
			$this->header_string = $hs;
		}
		
		/**
	 	 * Set header margin.
		 * (minimum distance between header and top page margin)
		 * @param int $hm distance in millimeters
		*/
		function setHeaderMargin($hm=10) {
			$this->header_margin = $hm;
		}
		
		/**
	 	 * Set footer margin.
		 * (minimum distance between footer and bottom page margin)
		 * @param int $fm distance in millimeters
		*/
		function setFooterMargin($fm=10) {
			$this->footer_margin = $fm;
		}
		
		/**
	 	 * This method is used to render the page header.
	 	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
		 */
		function Header() {
			if ($this->print_header) {
				
				if (!isset($this->original_lMargin)) {
					$this->original_lMargin = $this->lMargin;
				}
				if (!isset($this->original_rMargin)) {
					$this->original_rMargin = $this->rMargin;
				}
				
				//set current position
				$this->SetXY($this->original_lMargin, $this->header_margin);
				
				if (($this->header_logo) AND ($this->header_logo != K_BLANK_IMAGE)) {
					$this->Image(K_PATH_IMAGES.$this->header_logo, $this->original_lMargin, $this->header_margin, $this->header_logo_width);
				}
				else {
					$this->img_rb_y = $this->GetY();
				}
				
				$cell_height = round((K_CELL_HEIGHT_RATIO * $this->header_font[2]) / $this->k, 2);
				
				$header_x = $this->original_lMargin + ($this->header_logo_width * 1.05); //set left margin for text data cell
				
				// header title
				$this->SetFont($this->header_font[0], 'B', $this->header_font[2] + 1);
				$this->SetX($header_x);
				$this->Cell($this->header_width, $cell_height, $this->header_title, 0, 1, 'L'); 
				
				// header string
				$this->SetFont($this->header_font[0], $this->header_font[1], $this->header_font[2]);
				$this->SetX($header_x);
				$this->MultiCell($this->header_width, $cell_height, $this->header_string, 0, 'L', 0);
				
				// print an ending header line
				if (empty($this->header_width)) {
					//set style for cell border
					$this->SetLineWidth(0.3);
					$this->SetDrawColor(0, 0, 0);
					$this->SetY(1 + max($this->img_rb_y, $this->GetY()));
					$this->SetX($this->original_lMargin);
					$this->Cell(0, 0, '', 'T', 0, 'C'); 
				}
				
				//restore position
				$this->SetXY($this->original_lMargin, $this->tMargin);
			}
		}
		
		/**
	 	 * This method is used to render the page footer. 
	 	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
		 */
		function Footer() {
			if ($this->print_footer) {
				
				if (!isset($this->original_lMargin)) {
					$this->original_lMargin = $this->lMargin;
				}
				if (!isset($this->original_rMargin)) {
					$this->original_rMargin = $this->rMargin;
				}
				
				//set font
				$this->SetFont($this->footer_font[0], $this->footer_font[1] , $this->footer_font[2]);
				//set style for cell border
				$line_width = 0.3;
				$this->SetLineWidth($line_width);
				$this->SetDrawColor(0, 0, 0);
				
				$footer_height = round((K_CELL_HEIGHT_RATIO * $this->footer_font[2]) / $this->k, 2); //footer height
				//get footer y position
				$footer_y = $this->h - $this->footer_margin - $footer_height;
				//set current position
				$this->SetXY($this->original_lMargin, $footer_y); 
				
				//print document barcode
				if ($this->barcode) {
					$this->Ln();
					$barcode_width = round(($this->w - $this->original_lMargin - $this->original_rMargin)); //max width
					$this->writeBarcode($this->original_lMargin, $footer_y + $line_width, $barcode_width, $footer_height - $line_width, "C128B", false, false, 2, $this->barcode);
				}
				
				$this->SetXY($this->original_lMargin, $footer_y); 
				
				//Print page number
				$this->Cell(0, $footer_height, $this->l['w_page']." ".$this->PageNo().' / {nb}', 'T', 0, 'R'); 
			}
		}
		
		/**
		* Returns the current page number.
		* @return int page number
		* @since 1.0
		* @see AliasNbPages()
		*/
		function PageNo() {
			//Get current page number
			return $this->page;
		}

		/**
		* Defines the color used for all drawing operations (lines, rectangles and cell borders). It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
		* @param int $r If g et b are given, red component; if not, indicates the gray level. Value between 0 and 255
		* @param int $g Green component (between 0 and 255)
		* @param int $b Blue component (between 0 and 255)
		* @since 1.3
		* @see SetFillColor(), SetTextColor(), Line(), Rect(), Cell(), MultiCell()
		*/
		function SetDrawColor($r, $g=-1, $b=-1) {
			//Set color for all stroking operations
			if(($r==0 and $g==0 and $b==0) or $g==-1) {
				$this->DrawColor=sprintf('%.3f G',$r/255);
			}
			else {
				$this->DrawColor=sprintf('%.3f %.3f %.3f RG',$r/255,$g/255,$b/255);
			}
			if($this->page>0) {
				$this->_out($this->DrawColor);
			}
		}

		/**
		* Defines the color used for all filling operations (filled rectangles and cell backgrounds). It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
		* @param int $r If g et b are given, red component; if not, indicates the gray level. Value between 0 and 255
		* @param int $g Green component (between 0 and 255)
		* @param int $b Blue component (between 0 and 255)
		* @param boolean $storeprev if true stores the RGB array on $prevFillColor variable.
		* @since 1.3
		* @see SetDrawColor(), SetTextColor(), Rect(), Cell(), MultiCell()
		*/
		function SetFillColor($r, $g=-1, $b=-1, $storeprev=false) {
			//Set color for all filling operations
			if(($r==0 and $g==0 and $b==0) or $g==-1) {
				$this->FillColor=sprintf('%.3f g',$r/255);
			}
			else {
				$this->FillColor=sprintf('%.3f %.3f %.3f rg',$r/255,$g/255,$b/255);
			}
			$this->ColorFlag=($this->FillColor!=$this->TextColor);
			if($this->page>0) {
				$this->_out($this->FillColor);
			}
			if ($storeprev) {
				// store color as previous value
				$this->prevFillColor = array($r, $g, $b);
			}
		}

		/**
		* Defines the color used for text. It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
		* @param int $r If g et b are given, red component; if not, indicates the gray level. Value between 0 and 255
		* @param int $g Green component (between 0 and 255)
		* @param int $b Blue component (between 0 and 255)
		* @param boolean $storeprev if true stores the RGB array on $prevTextColor variable.
		* @since 1.3
		* @see SetDrawColor(), SetFillColor(), Text(), Cell(), MultiCell()
		*/
		function SetTextColor($r, $g=-1, $b=-1, $storeprev=false) {
			//Set color for text
			if(($r==0 and $g==0 and $b==0) or $g==-1) {
				$this->TextColor=sprintf('%.3f g',$r/255);
			}
			else {
				$this->TextColor=sprintf('%.3f %.3f %.3f rg',$r/255,$g/255,$b/255);
			}
			$this->ColorFlag=($this->FillColor!=$this->TextColor);
			if ($storeprev) {
				// store color as previous value
				$this->prevTextColor = array($r, $g, $b);
			}
		}

		/**
		* Returns the length of a string in user unit. A font must be selected.<br>
		* Support UTF-8 Unicode [Nicola Asuni, 2005-01-02]
		* @param string $s The string whose length is to be computed
		* @return int
		* @since 1.2
		*/
		function GetStringWidth($s) {
			//Get width of a string in the current font
			$s = (string)$s;
			$cw = &$this->CurrentFont['cw'];
			$w = 0;
			if($this->isunicode) {
				$unicode = $this->UTF8StringToArray($s);
				foreach($unicode as $char) {
					if (isset($cw[$char])) {
						$w+=$cw[$char];
					} elseif(isset($cw[ord($char)])) {
						$w+=$cw[ord($char)];
					} elseif(isset($cw[chr($char)])) {
						$w+=$cw[chr($char)];
					} elseif(isset($this->CurrentFont['desc']['MissingWidth'])) {
						$w += $this->CurrentFont['desc']['MissingWidth']; // set default size
					} else {
						$w += 500;
					}
				}
			} else {
				$l = strlen($s);
				for($i=0; $i<$l; $i++) {
					if (isset($cw[$s{$i}])) {
						$w += $cw[$s{$i}];
					} else if (isset($cw[ord($s{$i})])) {
						$w += $cw[ord($s{$i})];
					}
				}
			}
			return ($w * $this->FontSize / 1000);
		}

		/**
		* Defines the line width. By default, the value equals 0.2 mm. The method can be called before the first page is created and the value is retained from page to page.
		* @param float $width The width.
		* @since 1.0
		* @see Line(), Rect(), Cell(), MultiCell()
		*/
		function SetLineWidth($width) {
			//Set line width
			$this->LineWidth=$width;
			if($this->page>0) {
				$this->_out(sprintf('%.2f w',$width*$this->k));
			}
		}

		/**
		* Draws a line between two points.
		* @param float $x1 Abscissa of first point
		* @param float $y1 Ordinate of first point
		* @param float $x2 Abscissa of second point
		* @param float $y2 Ordinate of second point
		* @since 1.0
		* @see SetLineWidth(), SetDrawColor()
		*/
		function Line($x1, $y1, $x2, $y2) {
			//Draw a line
			$this->_out(sprintf('%.2f %.2f m %.2f %.2f l S', $x1*$this->k, ($this->h-$y1)*$this->k, $x2*$this->k, ($this->h-$y2)*$this->k));
		}

		/**
		* Outputs a rectangle. It can be drawn (border only), filled (with no border) or both.
		* @param float $x Abscissa of upper-left corner
		* @param float $y Ordinate of upper-left corner
		* @param float $w Width
		* @param float $h Height
		* @param string $style Style of rendering. Possible values are:<ul><li>D or empty string: draw (default)</li><li>F: fill</li><li>DF or FD: draw and fill</li></ul>
		* @since 1.0
		* @see SetLineWidth(), SetDrawColor(), SetFillColor()
		*/
		function Rect($x, $y, $w, $h, $style='') {
			//Draw a rectangle
			if($style=='F') {
				$op='f';
			}
			elseif($style=='FD' or $style=='DF') {
				$op='B';
			}
			else {
				$op='S';
			}
			$this->_out(sprintf('%.2f %.2f %.2f %.2f re %s',$x*$this->k,($this->h-$y)*$this->k,$w*$this->k,-$h*$this->k,$op));
		}

		/**
		* Imports a TrueType or Type1 font and makes it available. It is necessary to generate a font definition file first with the makefont.php utility. The definition file (and the font file itself when embedding) must be present either in the current directory or in the one indicated by FPDF_FONTPATH if the constant is defined. If it could not be found, the error "Could not include font definition file" is generated.
		* Support UTF-8 Unicode [Nicola Asuni, 2005-01-02].
		* <b>Example</b>:<br />
		* <pre>
		* $pdf->AddFont('Comic','I');
		* // is equivalent to:
		* $pdf->AddFont('Comic','I','comici.php');
		* </pre>
		* @param string $family Font family. The name can be chosen arbitrarily. If it is a standard family name, it will override the corresponding font.
		* @param string $style Font style. Possible values are (case insensitive):<ul><li>empty string: regular (default)</li><li>B: bold</li><li>I: italic</li><li>BI or IB: bold italic</li></ul>
		* @param string $file The font definition file. By default, the name is built from the family and style, in lower case with no space.
		* @since 1.5
		* @see SetFont()
		*/
		function AddFont($family, $style='', $file='') {
			if(empty($family)) {
				return;
			}

			//Add a TrueType or Type1 font
			$family = strtolower($family);
			if((!$this->isunicode) AND ($family == 'arial')) {
				$family = 'helvetica';
			}

			$style=strtoupper($style);
			$style=str_replace('U','',$style);
			if($style == 'IB') {
				$style = 'BI';
			}

			$fontkey = $family.$style;
			// check if the font has been already added
			if(isset($this->fonts[$fontkey])) {
				return;
			}

			if($file=='') {
				$file = str_replace(' ', '', $family).strtolower($style).'.php';
			}
			if(!file_exists($this->_getfontpath().$file)) {
				// try to load the basic file without styles
				$file = str_replace(' ', '', $family).'.php';
			}

			include($this->_getfontpath().$file);

			if(!isset($name) AND !isset($fpdf_charwidths)) {
				$this->Error('Could not include font definition file');
			}

			$i = count($this->fonts)+1;

			if($this->isunicode) {
				$this->fonts[$fontkey] = array('i'=>$i, 'type'=>$type, 'name'=>$name, 'desc'=>$desc, 'up'=>$up, 'ut'=>$ut, 'cw'=>$cw, 'enc'=>$enc, 'file'=>$file, 'ctg'=>$ctg);
				$fpdf_charwidths[$fontkey] = $cw;
			} else {
				$this->fonts[$fontkey]=array('i'=>$i, 'type'=>'core', 'name'=>$this->CoreFonts[$fontkey], 'up'=>-100, 'ut'=>50, 'cw'=>$fpdf_charwidths[$fontkey]);
			}

			if(isset($diff) AND (!empty($diff))) {
				//Search existing encodings
				$d=0;
				$nb=count($this->diffs);
				for($i=1;$i<=$nb;$i++) {
					if($this->diffs[$i]==$diff) {
						$d=$i;
						break;
					}
				}
				if($d==0) {
					$d=$nb+1;
					$this->diffs[$d]=$diff;
				}
				$this->fonts[$fontkey]['diff']=$d;
			}
			if(!empty($file)) {
				if((strcasecmp($type,"TrueType") == 0) OR (strcasecmp($type,"TrueTypeUnicode") == 0)) {
					$this->FontFiles[$file]=array('length1'=>$originalsize);
				}
				else {
					$this->FontFiles[$file]=array('length1'=>$size1,'length2'=>$size2);
				}
			}
		}

		/**
		* Sets the font used to print character strings. It is mandatory to call this method at least once before printing text or the resulting document would not be valid.
		* The font can be either a standard one or a font added via the AddFont() method. Standard fonts use Windows encoding cp1252 (Western Europe).
		* The method can be called before the first page is created and the font is retained from page to page.
		If you just wish to change the current font size, it is simpler to call SetFontSize().
		* Note: for the standard fonts, the font metric files must be accessible. There are three possibilities for this:<ul><li>They are in the current directory (the one where the running script lies)</li><li>They are in one of the directories defined by the include_path parameter</li><li>They are in the directory defined by the FPDF_FONTPATH constant</li></ul><br />
		* Example for the last case (note the trailing slash):<br />
		* <pre>
		* define('FPDF_FONTPATH','/home/www/font/');
		* require('tcpdf.php');
		*
		* //Times regular 12
		* $pdf->SetFont('Times');
		* //Arial bold 14
		* $pdf->SetFont('Arial','B',14);
		* //Removes bold
		* $pdf->SetFont('');
		* //Times bold, italic and underlined 14
		* $pdf->SetFont('Times','BIU');
		* </pre><br />
		* If the file corresponding to the requested font is not found, the error "Could not include font metric file" is generated.
		* @param string $family Family font. It can be either a name defined by AddFont() or one of the standard families (case insensitive):<ul><li>Courier (fixed-width)</li><li>Helvetica or Arial (synonymous; sans serif)</li><li>Times (serif)</li><li>Symbol (symbolic)</li><li>ZapfDingbats (symbolic)</li></ul>It is also possible to pass an empty string. In that case, the current family is retained.
		* @param string $style Font style. Possible values are (case insensitive):<ul><li>empty string: regular</li><li>B: bold</li><li>I: italic</li><li>U: underline</li></ul>or any combination. The default value is regular. Bold and italic styles do not apply to Symbol and ZapfDingbats
		* @param float $size Font size in points. The default value is the current size. If no size has been specified since the beginning of the document, the value taken is 12
		* @since 1.0
		* @see AddFont(), SetFontSize(), Cell(), MultiCell(), Write()
		*/
		function SetFont($family, $style='', $size=0) {
			// save previous values
			$this->prevFontFamily = $this->FontFamily;
			$this->prevFontStyle = $this->FontStyle;

			//Select a font; size given in points
			global $fpdf_charwidths;

			$family=strtolower($family);
			if($family=='') {
				$family=$this->FontFamily;
			}
			if((!$this->isunicode) AND ($family == 'arial')) {
				$family = 'helvetica';
			}
			elseif(($family=="symbol") OR ($family=="zapfdingbats")) {
				$style='';
			}
			$style=strtoupper($style);

			if(strpos($style,'U')!==false) {
				$this->underline=true;
				$style=str_replace('U','',$style);
			}
			else {
				$this->underline=false;
			}
			if($style=='IB') {
				$style='BI';
			}
			if($size==0) {
				$size=$this->FontSizePt;
			}

			// try to add font (if not already added)
			if($this->isunicode) {
				$this->AddFont($family, $style);
			}
			
			//Test if font is already selected
			if(($this->FontFamily == $family) AND ($this->FontStyle == $style) AND ($this->FontSizePt == $size)) {
				return;
			}
			
			$fontkey = $family.$style;
			//if(!isset($this->fonts[$fontkey]) AND isset($this->fonts[$family])) {
			//	$style='';
			//}

			//Test if used for the first time
			if(!isset($this->fonts[$fontkey])) {
				//Check if one of the standard fonts
				if(isset($this->CoreFonts[$fontkey])) {
					if(!isset($fpdf_charwidths[$fontkey])) {
						//Load metric file
						$file = $family;
						if(($family!='symbol') AND ($family!='zapfdingbats')) {
							$file .= strtolower($style);
						}
						if(!file_exists($this->_getfontpath().$file.'.php')) {
							// try to load the basic file without styles
							$file = $family;
							$fontkey = $family;
						}
						include($this->_getfontpath().$file.'.php');
						if (($this->isunicode AND !isset($ctg)) OR ((!$this->isunicode) AND (!isset($fpdf_charwidths[$fontkey]))) ) {
							$this->Error("Could not include font metric file [".$fontkey."]: ".$this->_getfontpath().$file.".php");
						}
					}
					$i = count($this->fonts) + 1;

					if($this->isunicode) {
						$this->fonts[$fontkey] = array('i'=>$i, 'type'=>$type, 'name'=>$name, 'desc'=>$desc, 'up'=>$up, 'ut'=>$ut, 'cw'=>$cw, 'enc'=>$enc, 'file'=>$file, 'ctg'=>$ctg);
						$fpdf_charwidths[$fontkey] = $cw;
					} else {
						$this->fonts[$fontkey]=array('i'=>$i, 'type'=>'core', 'name'=>$this->CoreFonts[$fontkey], 'up'=>-100, 'ut'=>50, 'cw'=>$fpdf_charwidths[$fontkey]);
					}
				}
				else {
					$this->Error('Undefined font: '.$family.' '.$style);
				}
			}
			//Select it
			$this->FontFamily = $family;
			$this->FontStyle = $style;
			$this->FontSizePt = $size;
			$this->FontSize = $size / $this->k;
			$this->CurrentFont = &$this->fonts[$fontkey];
			if($this->page>0) {
				$this->_out(sprintf('BT /F%d %.2f Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
			}
		}

		/**
		* Defines the size of the current font.
		* @param float $size The size (in points)
		* @since 1.0
		* @see SetFont()
		*/
		function SetFontSize($size) {
			//Set font size in points
			if($this->FontSizePt==$size) {
				return;
			}
			$this->FontSizePt = $size;
			$this->FontSize = $size / $this->k;
			if($this->page > 0) {
				$this->_out(sprintf('BT /F%d %.2f Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
			}
		}

		/**
		* Creates a new internal link and returns its identifier. An internal link is a clickable area which directs to another place within the document.<br />
		* The identifier can then be passed to Cell(), Write(), Image() or Link(). The destination is defined with SetLink().
		* @since 1.5
		* @see Cell(), Write(), Image(), Link(), SetLink()
		*/
		function AddLink() {
			//Create a new internal link
			$n=count($this->links)+1;
			$this->links[$n]=array(0,0);
			return $n;
		}

		/**
		* Defines the page and position a link points to
		* @param int $link The link identifier returned by AddLink()
		* @param float $y Ordinate of target position; -1 indicates the current position. The default value is 0 (top of page)
		* @param int $page Number of target page; -1 indicates the current page. This is the default value
		* @since 1.5
		* @see AddLink()
		*/
		function SetLink($link, $y=0, $page=-1) {
			//Set destination of internal link
			if($y==-1) {
				$y=$this->y;
			}
			if($page==-1) {
				$page=$this->page;
			}
			$this->links[$link]=array($page,$y);
		}

		/**
		* Puts a link on a rectangular area of the page. Text or image links are generally put via Cell(), Write() or Image(), but this method can be useful for instance to define a clickable area inside an image.
		* @param float $x Abscissa of the upper-left corner of the rectangle
		* @param float $y Ordinate of the upper-left corner of the rectangle
		* @param float $w Width of the rectangle
		* @param float $h Height of the rectangle
		* @param mixed $link URL or identifier returned by AddLink()
		* @since 1.5
		* @see AddLink(), Cell(), Write(), Image()
		*/
		function Link($x, $y, $w, $h, $link) {
			//Put a link on the page
			$this->PageLinks[$this->page][] = array($x * $this->k, $this->hPt - $y * $this->k, $w * $this->k, $h*$this->k, $link);
		}

		/**
		* Prints a character string. The origin is on the left of the first charcter, on the baseline. This method allows to place a string precisely on the page, but it is usually easier to use Cell(), MultiCell() or Write() which are the standard methods to print text.
		* @param float $x Abscissa of the origin
		* @param float $y Ordinate of the origin
		* @param string $txt String to print
		* @since 1.0
		* @see SetFont(), SetTextColor(), Cell(), MultiCell(), Write()
		*/
		function Text($x, $y, $txt) {
			//Output a string
			$s=sprintf('BT %.2f %.2f Td (%s) Tj ET', $x * $this->k, ($this->h-$y) * $this->k, $this->_escapetext($txt));
			if($this->underline AND ($txt!='')) {
				$s .= ' '.$this->_dounderline($x,$y,$txt);
			}
			if($this->ColorFlag) {
				$s='q '.$this->TextColor.' '.$s.' Q';
			}
			$this->_out($s);
		}

		/**
		* Whenever a page break condition is met, the method is called, and the break is issued or not depending on the returned value. The default implementation returns a value according to the mode selected by SetAutoPageBreak().<br />
		* This method is called automatically and should not be called directly by the application.<br />
		* <b>Example:</b><br />
		* The method is overriden in an inherited class in order to obtain a 3 column layout:<br />
		* <pre>
		* class PDF extends TCPDF {
		* 	var $col=0;
		*
		* 	function SetCol($col) {
		* 		//Move position to a column
		* 		$this->col=$col;
		* 		$x=10+$col*65;
		* 		$this->SetLeftMargin($x);
		* 		$this->SetX($x);
		* 	}
		*
		* 	function AcceptPageBreak() {
		* 		if($this->col<2) {
		* 			//Go to next column
		* 			$this->SetCol($this->col+1);
		* 			$this->SetY(10);
		* 			return false;
		* 		}
		* 		else {
		* 			//Go back to first column and issue page break
		* 			$this->SetCol(0);
		* 			return true;
		* 		}
		* 	}
		* }
		*
		* $pdf=new PDF();
		* $pdf->Open();
		* $pdf->AddPage();
		* $pdf->SetFont('Arial','',12);
		* for($i=1;$i<=300;$i++) {
		*     $pdf->Cell(0,5,"Line $i",0,1);
		* }
		* $pdf->Output();
		* </pre>
		* @return boolean
		* @since 1.4
		* @see SetAutoPageBreak()
		*/
		function AcceptPageBreak() {
			//Accept automatic page break or not
			return $this->AutoPageBreak;
		}

		/**
		* Prints a cell (rectangular area) with optional borders, background color and character string. The upper-left corner of the cell corresponds to the current position. The text can be aligned or centered. After the call, the current position moves to the right or to the next line. It is possible to put a link on the text.<br />
		* If automatic page breaking is enabled and the cell goes beyond the limit, a page break is done before outputting.
		* @param float $w Cell width. If 0, the cell extends up to the right margin.
		* @param float $h Cell height. Default value: 0.
		* @param string $txt String to print. Default value: empty string.
		* @param mixed $border Indicates if borders must be drawn around the cell. The value can be either a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul>
		* @param int $ln Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>
		Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
		* @param string $align Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li></ul>
		* @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
		* @param mixed $link URL or identifier returned by AddLink().
		* @since 1.0
		* @see SetFont(), SetDrawColor(), SetFillColor(), SetTextColor(), SetLineWidth(), AddLink(), Ln(), MultiCell(), Write(), SetAutoPageBreak()
		*/
		function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='') {
			//Output a cell
			$k=$this->k;
			if(($this->y + $h) > $this->PageBreakTrigger AND empty($this->InFooter) AND $this->AcceptPageBreak()) {
				//Automatic page break
				$x = $this->x;
				$ws = $this->ws;
				if($ws > 0) {
					$this->ws = 0;
					$this->_out('0 Tw');
				}
				$this->AddPage($this->CurOrientation);
				$this->x = $x;
				if($ws > 0) {
					$this->ws = $ws;
					$this->_out(sprintf('%.3f Tw',$ws * $k));
				}
			}
			if($w == 0) {
				$w = $this->w - $this->rMargin - $this->x;
			}
			$s = '';
			if(($fill == 1) OR ($border == 1)) {
				if($fill == 1) {
					$op = ($border == 1) ? 'B' : 'f';
				}
				else {
					$op = 'S';
				}
				$s = sprintf('%.2f %.2f %.2f %.2f re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
			}
			if(is_string($border)) {
				$x=$this->x;
				$y=$this->y;
				if(strpos($border,'L')!==false) {
					$s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
				}
				if(strpos($border,'T')!==false) {
					$s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
				}
				if(strpos($border,'R')!==false) {
					$s.=sprintf('%.2f %.2f m %.2f %.2f l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
				}
				if(strpos($border,'B')!==false) {
					$s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
				}
			}
			if($txt != '') {
				$width = $this->GetStringWidth($txt);
				if($align == 'R') {
					$dx = $w - $this->cMargin - $width;
				}
				elseif($align=='C') {
					$dx = ($w - $width)/2;
				}
				else {
					$dx = $this->cMargin;
				}
				if($this->ColorFlag) {
					$s .= 'q '.$this->TextColor.' ';
				}
				$txt2 = $this->_escapetext($txt);
				$s.=sprintf('BT %.2f %.2f Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + 0.5 * $h + 0.3 * $this->FontSize)) * $k, $txt2);
				if($this->underline) {
					$s.=' '.$this->_dounderline($this->x + $dx, $this->y + 0.5 * $h + 0.3 * $this->FontSize, $txt);
				}
				if($this->ColorFlag) {
					$s.=' Q';
				}
				if($link) {
					$this->Link($this->x + $dx, $this->y + 0.5 * $h - 0.5 * $this->FontSize, $width, $this->FontSize, $link);
				}
			}
			if($s) {
				$this->_out($s);
			}
			$this->lasth = $h;
			if($ln>0) {
				//Go to next line
				$this->y += $h;
				if($ln == 1) {
					$this->x = $this->lMargin;
				}
			}
			else {
				$this->x += $w;
			}
		}

		/**
		* This method allows printing text with line breaks. They can be automatic (as soon as the text reaches the right border of the cell) or explicit (via the \n character). As many cells as necessary are output, one below the other.<br />
		* Text can be aligned, centered or justified. The cell block can be framed and the background painted.
		* @param float $w Width of cells. If 0, they extend up to the right margin of the page.
		* @param float $h Height of cells.
		* @param string $txt String to print
		* @param mixed $border Indicates if borders must be drawn around the cell block. The value can be either a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul>
		* @param string $align Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align</li><li>C: center</li><li>R: right align</li><li>J: justification (default value)</li></ul>
		* @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
		* @since 1.3
		* @see SetFont(), SetDrawColor(), SetFillColor(), SetTextColor(), SetLineWidth(), Cell(), Write(), SetAutoPageBreak()
		*/
		function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0) {
			//Output text with automatic or explicit line breaks
			$cw = &$this->CurrentFont['cw'];

			if($w == 0) {
				$w = $this->w - $this->rMargin - $this->x;
			}

			$wmax = ($w - 2 * $this->cMargin);

			$s = str_replace("\r", '', $txt); // remove carriage returns
			$nb = strlen($s);

			$b=0;
			if($border) {
				if($border==1) {
					$border='LTRB';
					$b='LRT';
					$b2='LR';
				}
				else {
					$b2='';
					if(strpos($border,'L')!==false) {
						$b2.='L';
					}
					if(strpos($border,'R')!==false) {
						$b2.='R';
					}
					$b=(strpos($border,'T')!==false) ? $b2.'T' : $b2;
				}
			}
			$sep=-1;
			$i=0;
			$j=0;
			$l=0;
			$ns=0;
			$nl=1;
			while($i<$nb) {
				//Get next character
				$c = $s{$i};
				if(preg_match("/[\n]/u", $c)) {
					//Explicit line break
					if($this->ws > 0) {
						$this->ws = 0;
						$this->_out('0 Tw');
					}
					$this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
					$i++;
					$sep=-1;
					$j=$i;
					$l=0;
					$ns=0;
					$nl++;
					if($border and $nl==2) {
						$b = $b2;
					}
					continue;
				}
				if(preg_match("/[ ]/u", $c)) {
					$sep = $i;
					$ls = $l;
					$ns++;
				}

				$l = $this->GetStringWidth(substr($s, $j, $i-$j));

				if($l > $wmax) {
					//Automatic line break
					if($sep == -1) {
						if($i == $j) {
							$i++;
						}
						if($this->ws > 0) {
							$this->ws = 0;
							$this->_out('0 Tw');
						}
						$this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
					}
					else {
						if($align=='J') {
							$this->ws = ($ns>1) ? ($wmax-$ls)/($ns-1) : 0;
							$this->_out(sprintf('%.3f Tw', $this->ws * $this->k));
						}
						$this->Cell($w, $h, substr($s, $j, $sep-$j), $b, 2, $align, $fill);
						$i = $sep + 1;
					}
					$sep=-1;
					$j=$i;
					$l=0;
					$ns=0;
					$nl++;
					if($border AND ($nl==2)) {
						$b=$b2;
					}
				}
				else {
					$i++;
				}
			}
			//Last chunk
			if($this->ws>0) {
				$this->ws=0;
				$this->_out('0 Tw');
			}
			if($border and is_int(strpos($border,'B'))) {
				$b.='B';
			}
			$this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
			$this->x=$this->lMargin;
		}

		/**
		* This method prints text from the current position. When the right margin is reached (or the \n character is met) a line break occurs and text continues from the left margin. Upon method exit, the current position is left just at the end of the text. It is possible to put a link on the text.<br />
		* <b>Example:</b><br />
		* <pre>
		* //Begin with regular font
		* $pdf->SetFont('Arial','',14);
		* $pdf->Write(5,'Visit ');
		* //Then put a blue underlined link
		* $pdf->SetTextColor(0,0,255);
		* $pdf->SetFont('','U');
		* $pdf->Write(5,'www.tecnick.com','http://www.tecnick.com');
		* </pre>
		* @param float $h Line height
		* @param string $txt String to print
		* @param mixed $link URL or identifier returned by AddLink()
		* @param int $fill Indicates if the background must be painted (1) or transparent (0). Default value: 0.
		* @since 1.5
		* @see SetFont(), SetTextColor(), AddLink(), MultiCell(), SetAutoPageBreak()
		*/
		function Write($h, $txt, $link='', $fill=0) {

			//Output text in flowing mode
			$cw = &$this->CurrentFont['cw'];
			$w = $this->w - $this->rMargin - $this->x;
			$wmax = ($w - 2 * $this->cMargin);

			$s = str_replace("\r", '', $txt);
			$nb = strlen($s);

			// handle single space character
			if(($nb==1) AND preg_match("/[ ]/u", $s)) {
				$this->x += $this->GetStringWidth($s);
				return;
			}

			$sep=-1;
			$i=0;
			$j=0;
			$l=0;
			$nl=1;
			while($i<$nb) {
				//Get next character
				$c=$s{$i};
				if(preg_match("/[\n]/u", $c)) {
					//Explicit line break
					$this->Cell($w, $h, substr($s, $j, $i-$j), 0, 2, '', $fill, $link);
					$i++;
					$sep = -1;
					$j = $i;
					$l = 0;
					if($nl == 1) {
						$this->x = $this->lMargin;
						$w = $this->w - $this->rMargin - $this->x;
						$wmax = ($w - 2 * $this->cMargin);
					}
					$nl++;
					continue;
				}
				if(preg_match("/[ ]/u", $c)) {
					$sep= $i;
				}

				$l = $this->GetStringWidth(substr($s, $j, $i-$j));

				if($l > $wmax) {
					//Automatic line break (word wrapping)
					if($sep == -1) {
						if($this->x > $this->lMargin) {
							//Move to next line
							$this->x = $this->lMargin;
							$this->y += $h;
							$w=$this->w - $this->rMargin - $this->x;
							$wmax=($w - 2 * $this->cMargin);
							$i++;
							$nl++;
							continue;
						}
						if($i==$j) {
							$i++;
						}
						$this->Cell($w, $h, substr($s, $j, $i-$j), 0, 2, '', $fill, $link);
					}
					else {
						$this->Cell($w, $h, substr($s, $j, $sep-$j), 0, 2, '', $fill, $link);
						$i=$sep+1;
					}
					$sep = -1;
					$j = $i;
					$l = 0;
					if($nl==1) {
						$this->x = $this->lMargin;
						$w = $this->w - $this->rMargin - $this->x;
						$wmax = ($w - 2 * $this->cMargin);
					}
					$nl++;
				}
				else {
					$i++;
				}
			}
			
			//Last chunk
			if($i!=$j) {
				$this->Cell($this->GetStringWidth(substr($s, $j)), $h, substr($s, $j), 0, 0, '', $fill, $link);
			}
		}

		/**
		* Puts an image in the page. The upper-left corner must be given. The dimensions can be specified in different ways:<ul><li>explicit width and height (expressed in user unit)</li><li>one explicit dimension, the other being calculated automatically in order to keep the original proportions</li><li>no explicit dimension, in which case the image is put at 72 dpi</li></ul>
		* Supported formats are JPEG and PNG.
		* For JPEG, all flavors are allowed:<ul><li>gray scales</li><li>true colors (24 bits)</li><li>CMYK (32 bits)</li></ul>
		* For PNG, are allowed:<ul><li>gray scales on at most 8 bits (256 levels)</li><li>indexed colors</li><li>true colors (24 bits)</li></ul>
		* but are not supported:<ul><li>Interlacing</li><li>Alpha channel</li></ul>
		* If a transparent color is defined, it will be taken into account (but will be only interpreted by Acrobat 4 and above).<br />
		* The format can be specified explicitly or inferred from the file extension.<br />
		* It is possible to put a link on the image.<br />
		* Remark: if an image is used several times, only one copy will be embedded in the file.<br />
		* @param string $file Name of the file containing the image.
		* @param float $x Abscissa of the upper-left corner.
		* @param float $y Ordinate of the upper-left corner.
		* @param float $w Width of the image in the page. If not specified or equal to zero, it is automatically calculated.
		* @param float $h Height of the image in the page. If not specified or equal to zero, it is automatically calculated.
		* @param string $type Image format. Possible values are (case insensitive): JPG, JPEG, PNG. If not specified, the type is inferred from the file extension.
		* @param mixed $link URL or identifier returned by AddLink().
		* @since 1.1
		* @see AddLink()
		*/
		function Image($file, $x, $y, $w=0, $h=0, $type='', $link='') {
			//Put an image on the page
			if(!isset($this->images[$file])) {
				//First use of image, get info
				if($type == '') {
					$pos = strrpos($file,'.');
					if(empty($pos)) {
						$this->Error('Image file has no extension and no type was specified: '.$file);
					}
					$type = substr($file, $pos+1);
				}
				$type = strtolower($type);
				$mqr = get_magic_quotes_runtime();
				set_magic_quotes_runtime(0);
				if($type == 'jpg' or $type == 'jpeg') {
					$info=$this->_parsejpg($file);
				}
				elseif($type == 'png') {
					$info=$this->_parsepng($file);
				}
				else {
					//Allow for additional formats
					$mtd='_parse'.$type;
					if(!method_exists($this,$mtd)) {
						$this->Error('Unsupported image type: '.$type);
					}
					$info=$this->$mtd($file);
				}
				set_magic_quotes_runtime($mqr);
				$info['i']=count($this->images)+1;
				$this->images[$file]=$info;
			}
			else {
				$info=$this->images[$file];
			}
			//Automatic width and height calculation if needed
			if(($w == 0) and ($h == 0)) {
				//Put image at 72 dpi
				// 2004-06-14 :: Nicola Asuni, scale factor where added
				$w = $info['w'] / ($this->imgscale * $this->k);
				$h = $info['h'] / ($this->imgscale * $this->k);
			}
			if($w == 0) {
				$w = $h * $info['w'] / $info['h'];
			}
			if($h == 0) {
				$h = $w * $info['h'] / $info['w'];
			}
			$this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q', $w*$this->k, $h*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k, $info['i']));
			if($link) {
				$this->Link($x, $y, $w, $h, $link);
			}

			//2002-07-31 - Nicola Asuni
			// set right-bottom corner coordinates
			$this->img_rb_x = $x + $w;
			$this->img_rb_y = $y + $h;
		}

		/**
		* Performs a line break. The current abscissa goes back to the left margin and the ordinate increases by the amount passed in parameter.
		* @param float $h The height of the break. By default, the value equals the height of the last printed cell.
		* @since 1.0
		* @see Cell()
		*/
		function Ln($h='') {
			//Line feed; default value is last cell height
			$this->x=$this->lMargin;
			if(is_string($h)) {
				$this->y+=$this->lasth;
			}
			else {
				$this->y+=$h;
			}
		}

		/**
		* Returns the abscissa of the current position.
		* @return float
		* @since 1.2
		* @see SetX(), GetY(), SetY()
		*/
		function GetX() {
			//Get x position
			return $this->x;
		}

		/**
		* Defines the abscissa of the current position. If the passed value is negative, it is relative to the right of the page.
		* @param float $x The value of the abscissa.
		* @since 1.2
		* @see GetX(), GetY(), SetY(), SetXY()
		*/
		function SetX($x) {
			//Set x position
			if($x>=0) {
				$this->x=$x;
			}
			else {
				$this->x=$this->w+$x;
			}
		}

		/**
		* Returns the ordinate of the current position.
		* @return float
		* @since 1.0
		* @see SetY(), GetX(), SetX()
		*/
		function GetY() {
			//Get y position
			return $this->y;
		}

		/**
		* Moves the current abscissa back to the left margin and sets the ordinate. If the passed value is negative, it is relative to the bottom of the page.
		* @param float $y The value of the ordinate.
		* @since 1.0
		* @see GetX(), GetY(), SetY(), SetXY()
		*/
		function SetY($y) {
			//Set y position and reset x
			$this->x=$this->lMargin;
			if($y>=0) {
				$this->y=$y;
			}
			else {
				$this->y=$this->h+$y;
			}
		}

		/**
		* Defines the abscissa and ordinate of the current position. If the passed values are negative, they are relative respectively to the right and bottom of the page.
		* @param float $x The value of the abscissa
		* @param float $y The value of the ordinate
		* @since 1.2
		* @see SetX(), SetY()
		*/
		function SetXY($x, $y) {
			//Set x and y positions
			$this->SetY($y);
			$this->SetX($x);
		}

		/**
		* Send the document to a given destination: string, local file or browser. In the last case, the plug-in may be used (if present) or a download ("Save as" dialog box) may be forced.<br />
		* The method first calls Close() if necessary to terminate the document.
		* @param string $name The name of the file. If not given, the document will be sent to the browser (destination I) with the name doc.pdf.
		* @param string $dest Destination where to send the document. It can take one of the following values:<ul><li>I: send the file inline to the browser. The plug-in is used if available. The name given by name is used when one selects the "Save as" option on the link generating the PDF.</li><li>D: send to the browser and force a file download with the name given by name.</li><li>F: save to a local file with the name given by name.</li><li>S: return the document as a string. name is ignored.</li></ul>If the parameter is not specified but a name is given, destination is F. If no parameter is specified at all, destination is I.<br />Note: for compatibility with previous versions, a boolean value is also accepted (false for F and true for D).
		* @since 1.0
		* @see Close()
		*/
		function Output($name='',$dest='') {
			//Output PDF to some destination
			//Finish document if necessary
			if($this->state < 3) {
				$this->Close();
			}
			//Normalize parameters
			if(is_bool($dest)) {
				$dest=$dest ? 'D' : 'F';
			}
			$dest=strtoupper($dest);
			if($dest=='') {
				if($name=='') {
					$name='doc.pdf';
					$dest='I';
				} else {
					$dest='F';
				}
			}
			switch($dest) {
				case 'I': {
					//Send to standard output
					if(ob_get_contents()) {
						$this->Error('Some data has already been output, can\'t send PDF file');
					}
					if(php_sapi_name()!='cli') {
						//We send to a browser
						header('Content-Type: application/pdf');
						if(headers_sent()) {
							$this->Error('Some data has already been output to browser, can\'t send PDF file');
						}
						header('Content-Length: '.strlen($this->buffer));
						header('Content-disposition: inline; filename="'.$name.'"');
					}
					echo $this->buffer;
					break;
				}
				case 'D': {
					//Download file
					if(ob_get_contents()) {
						$this->Error('Some data has already been output, can\'t send PDF file');
					}
					if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MSIE')) {
						header('Content-Type: application/force-download');
					} else {
						header('Content-Type: application/octet-stream');
					}
					if(headers_sent()) {
						$this->Error('Some data has already been output to browser, can\'t send PDF file');
					}
					header('Content-Length: '.strlen($this->buffer));
					header('Content-disposition: attachment; filename="'.$name.'"');
					echo $this->buffer;
					break;
				}
				case 'F': {
					//Save to local file
					$f=fopen($name,'wb');
					if(!$f) {
						$this->Error('Unable to create output file: '.$name);
					}
					fwrite($f,$this->buffer,strlen($this->buffer));
					fclose($f);
					break;
				}
				case 'S': {
					//Return as a string
					return $this->buffer;
				}
				default: {
					$this->Error('Incorrect output destination: '.$dest);
				}
			}
			return '';
		}

		// var methods

		/**
		* Check for locale-related bug
		* @access protected
		*/
		function _dochecks() {
			//Check for locale-related bug
			if(1.1==1) {
				$this->Error('Don\'t alter the locale before including class file');
			}
			//Check for decimal separator
			if(sprintf('%.1f',1.0)!='1.0') {
				setlocale(LC_NUMERIC,'C');
			}
		}

		/**
		* Return fonts path
		* @access protected
		*/
		function _getfontpath() {
			if(!defined('FPDF_FONTPATH') AND is_dir(dirname(__FILE__).'/font')) {
				define('FPDF_FONTPATH', dirname(__FILE__).'/font/');
			}
			return defined('FPDF_FONTPATH') ? FPDF_FONTPATH : '';
		}

		/**
		* Start document
		* @access protected
		*/
		function _begindoc() {
			//Start document
			$this->state=1;
			$this->_out('%PDF-1.3');
		}

		/**
		* _putpages
		* @access protected
		*/
		function _putpages() {
			$nb = $this->page;
			if(!empty($this->AliasNbPages)) {
				$nbstr = $this->UTF8ToUTF16BE($nb, false);
				//Replace number of pages
				for($n=1;$n<=$nb;$n++) {
					$this->pages[$n]=str_replace($this->AliasNbPages, $nbstr, $this->pages[$n]);
				}
			}
			if($this->DefOrientation=='P') {
				$wPt=$this->fwPt;
				$hPt=$this->fhPt;
			}
			else {
				$wPt=$this->fhPt;
				$hPt=$this->fwPt;
			}
			$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
			for($n=1;$n<=$nb;$n++) {
				//Page
				$this->_newobj();
				$this->_out('<</Type /Page');
				$this->_out('/Parent 1 0 R');
				if(isset($this->OrientationChanges[$n])) {
					$this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]',$hPt,$wPt));
				}
				$this->_out('/Resources 2 0 R');
				if(isset($this->PageLinks[$n])) {
					//Links
					$annots='/Annots [';
					foreach($this->PageLinks[$n] as $pl) {
						$rect=sprintf('%.2f %.2f %.2f %.2f',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
						$annots.='<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
						if(is_string($pl[4])) {
							$annots.='/A <</S /URI /URI ('.$this->_escape($pl[4]).')>>>>';
						}
						else {
							$l=$this->links[$pl[4]];
							$h=isset($this->OrientationChanges[$l[0]]) ? $wPt : $hPt;
							$annots.=sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]>>',1+2*$l[0],$h-$l[1]*$this->k);
						}
					}
					$this->_out($annots.']');
				}
				$this->_out('/Contents '.($this->n+1).' 0 R>>');
				$this->_out('endobj');
				//Page content
				$p=($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
				$this->_newobj();
				$this->_out('<<'.$filter.'/Length '.strlen($p).'>>');
				$this->_putstream($p);
				$this->_out('endobj');
			}
			//Pages root
			$this->offsets[1]=strlen($this->buffer);
			$this->_out('1 0 obj');
			$this->_out('<</Type /Pages');
			$kids='/Kids [';
			for($i=0;$i<$nb;$i++) {
				$kids.=(3+2*$i).' 0 R ';
			}
			$this->_out($kids.']');
			$this->_out('/Count '.$nb);
			$this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]',$wPt,$hPt));
			$this->_out('>>');
			$this->_out('endobj');
		}

		/**
		* Adds fonts
		* _putfonts
		* @access protected
		*/
		function _putfonts() {
			$nf=$this->n;
			foreach($this->diffs as $diff) {
				//Encodings
				$this->_newobj();
				$this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences ['.$diff.']>>');
				$this->_out('endobj');
			}
			$mqr=get_magic_quotes_runtime();
			set_magic_quotes_runtime(0);
			foreach($this->FontFiles as $file=>$info) {
				//Font file embedding
				$this->_newobj();
				$this->FontFiles[$file]['n']=$this->n;
				$font='';
				$f=fopen($this->_getfontpath().$file,'rb',1);
				if(!$f) {
					$this->Error('Font file not found');
				}
				while(!feof($f)) {
					$font .= fread($f, 8192);
				}
				fclose($f);
				$compressed=(substr($file,-2)=='.z');
				if(!$compressed && isset($info['length2'])) {
					$header=(ord($font{0})==128);
					if($header) {
						//Strip first binary header
						$font=substr($font,6);
					}
					if($header && ord($font{$info['length1']})==128) {
						//Strip second binary header
						$font=substr($font,0,$info['length1']).substr($font,$info['length1']+6);
					}
				}
				$this->_out('<</Length '.strlen($font));
				if($compressed) {
					$this->_out('/Filter /FlateDecode');
				}
				$this->_out('/Length1 '.$info['length1']);
				if(isset($info['length2'])) {
					$this->_out('/Length2 '.$info['length2'].' /Length3 0');
				}
				$this->_out('>>');
				$this->_putstream($font);
				$this->_out('endobj');
			}
			set_magic_quotes_runtime($mqr);
			foreach($this->fonts as $k=>$font) {
				//Font objects
				$this->fonts[$k]['n']=$this->n+1;
				$type=$font['type'];
				$name=$font['name'];
				if($type=='core') {
					//Standard font
					$this->_newobj();
					$this->_out('<</Type /Font');
					$this->_out('/BaseFont /'.$name);
					$this->_out('/Subtype /Type1');
					if($name!='Symbol' && $name!='ZapfDingbats') {
						$this->_out('/Encoding /WinAnsiEncoding');
					}
					$this->_out('>>');
					$this->_out('endobj');
				} elseif($type=='Type1' || $type=='TrueType') {
					//Additional Type1 or TrueType font
					$this->_newobj();
					$this->_out('<</Type /Font');
					$this->_out('/BaseFont /'.$name);
					$this->_out('/Subtype /'.$type);
					$this->_out('/FirstChar 32 /LastChar 255');
					$this->_out('/Widths '.($this->n+1).' 0 R');
					$this->_out('/FontDescriptor '.($this->n+2).' 0 R');
					if($font['enc']) {
						if(isset($font['diff'])) {
							$this->_out('/Encoding '.($nf+$font['diff']).' 0 R');
						} else {
							$this->_out('/Encoding /WinAnsiEncoding');
						}
					}
					$this->_out('>>');
					$this->_out('endobj');
					//Widths
					$this->_newobj();
					$cw=&$font['cw'];
					$s='[';
					for($i=32;$i<=255;$i++) {
						$s.=$cw[chr($i)].' ';
					}
					$this->_out($s.']');
					$this->_out('endobj');
					//Descriptor
					$this->_newobj();
					$s='<</Type /FontDescriptor /FontName /'.$name;
					foreach($font['desc'] as $k=>$v) {
						$s.=' /'.$k.' '.$v;
					}
					$file = $font['file'];
					if($file) {
						$s.=' /FontFile'.($type=='Type1' ? '' : '2').' '.$this->FontFiles[$file]['n'].' 0 R';
					}
					$this->_out($s.'>>');
					$this->_out('endobj');
				} else {
					//Allow for additional types
					$mtd='_put'.strtolower($type);
					if(!method_exists($this, $mtd)) {
						$this->Error('Unsupported font type: '.$type);
					}
					$this->$mtd($font);
				}
			}
		}

		/**
		* _putimages
		* @access protected
		*/
		function _putimages() {
			$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
			reset($this->images);
			while(list($file,$info)=each($this->images)) {
				$this->_newobj();
				$this->images[$file]['n']=$this->n;
				$this->_out('<</Type /XObject');
				$this->_out('/Subtype /Image');
				$this->_out('/Width '.$info['w']);
				$this->_out('/Height '.$info['h']);
				if($info['cs']=='Indexed') {
					$this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
				}
				else {
					$this->_out('/ColorSpace /'.$info['cs']);
					if($info['cs']=='DeviceCMYK') {
						$this->_out('/Decode [1 0 1 0 1 0 1 0]');
					}
				}
				$this->_out('/BitsPerComponent '.$info['bpc']);
				if(isset($info['f'])) {
					$this->_out('/Filter /'.$info['f']);
				}
				if(isset($info['parms'])) {
					$this->_out($info['parms']);
				}
				if(isset($info['trns']) and is_array($info['trns'])) {
					$trns='';
					for($i=0;$i<count($info['trns']);$i++) {
						$trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
					}
					$this->_out('/Mask ['.$trns.']');
				}
				$this->_out('/Length '.strlen($info['data']).'>>');
				$this->_putstream($info['data']);
				unset($this->images[$file]['data']);
				$this->_out('endobj');
				//Palette
				if($info['cs']=='Indexed') {
					$this->_newobj();
					$pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
					$this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
					$this->_putstream($pal);
					$this->_out('endobj');
				}
			}
		}

		/**
		* _putxobjectdict
		* @access protected
		*/
		function _putxobjectdict() {
			foreach($this->images as $image) {
				$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
			}
		}

		/**
		* _putresourcedict
		* @access protected
		*/
		function _putresourcedict(){
			$this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
			$this->_out('/Font <<');
			foreach($this->fonts as $font) {
				$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
			}
			$this->_out('>>');
			$this->_out('/XObject <<');
			$this->_putxobjectdict();
			$this->_out('>>');
		}

		/**
		* _putresources
		* @access protected
		*/
		function _putresources() {
			$this->_putfonts();
			$this->_putimages();
			//Resource dictionary
			$this->offsets[2]=strlen($this->buffer);
			$this->_out('2 0 obj');
			$this->_out('<<');
			$this->_putresourcedict();
			$this->_out('>>');
			$this->_out('endobj');
		}
		
		/**
		* _putinfo
		* @access protected
		*/
		function _putinfo() {
			$this->_out('/Producer '.$this->_textstring(PDF_PRODUCER));
			if(!empty($this->title)) {
				$this->_out('/Title '.$this->_textstring($this->title));
			}
			if(!empty($this->subject)) {
				$this->_out('/Subject '.$this->_textstring($this->subject));
			}
			if(!empty($this->author)) {
				$this->_out('/Author '.$this->_textstring($this->author));
			}
			if(!empty($this->keywords)) {
				$this->_out('/Keywords '.$this->_textstring($this->keywords));
			}
			if(!empty($this->creator)) {
				$this->_out('/Creator '.$this->_textstring($this->creator));
			}
			$this->_out('/CreationDate '.$this->_textstring('D:'.date('YmdHis')));
		}

		/**
		* _putcatalog
		* @access protected
		*/
		function _putcatalog() {
			$this->_out('/Type /Catalog');
			$this->_out('/Pages 1 0 R');
			if($this->ZoomMode=='fullpage') {
				$this->_out('/OpenAction [3 0 R /Fit]');
			}
			elseif($this->ZoomMode=='fullwidth') {
				$this->_out('/OpenAction [3 0 R /FitH null]');
			}
			elseif($this->ZoomMode=='real') {
				$this->_out('/OpenAction [3 0 R /XYZ null null 1]');
			}
			elseif(!is_string($this->ZoomMode)) {
				$this->_out('/OpenAction [3 0 R /XYZ null null '.($this->ZoomMode/100).']');
			}
			if($this->LayoutMode=='single') {
				$this->_out('/PageLayout /SinglePage');
			}
			elseif($this->LayoutMode=='continuous') {
				$this->_out('/PageLayout /OneColumn');
			}
			elseif($this->LayoutMode=='two') {
				$this->_out('/PageLayout /TwoColumnLeft');
			}
		}

		/**
		* _puttrailer
		* @access protected
		*/
		function _puttrailer() {
			$this->_out('/Size '.($this->n+1));
			$this->_out('/Root '.$this->n.' 0 R');
			$this->_out('/Info '.($this->n-1).' 0 R');
		}

		/**
		* _putheader
		* @access protected
		*/
		function _putheader() {
			$this->_out('%PDF-'.$this->PDFVersion);
		}

		/**
		* _enddoc
		* @access protected
		*/
		function _enddoc() {
			$this->_putheader();
			$this->_putpages();
			$this->_putresources();
			//Info
			$this->_newobj();
			$this->_out('<<');
			$this->_putinfo();
			$this->_out('>>');
			$this->_out('endobj');
			//Catalog
			$this->_newobj();
			$this->_out('<<');
			$this->_putcatalog();
			$this->_out('>>');
			$this->_out('endobj');
			//Cross-ref
			$o=strlen($this->buffer);
			$this->_out('xref');
			$this->_out('0 '.($this->n+1));
			$this->_out('0000000000 65535 f ');
			for($i=1;$i<=$this->n;$i++) {
				$this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
			}
			//Trailer
			$this->_out('trailer');
			$this->_out('<<');
			$this->_puttrailer();
			$this->_out('>>');
			$this->_out('startxref');
			$this->_out($o);
			$this->_out('%%EOF');
			$this->state=3;
		}

		/**
		* _beginpage
		* @access protected
		*/
		function _beginpage($orientation) {
			$this->page++;
			$this->pages[$this->page]='';
			$this->state=2;
			$this->x=$this->lMargin;
			$this->y=$this->tMargin;
			$this->FontFamily='';
			//Page orientation
			if(empty($orientation)) {
				$orientation=$this->DefOrientation;
			}
			else {
				$orientation=strtoupper($orientation{0});
				if($orientation!=$this->DefOrientation) {
					$this->OrientationChanges[$this->page]=true;
				}
			}
			if($orientation!=$this->CurOrientation) {
				//Change orientation
				if($orientation=='P') {
					$this->wPt=$this->fwPt;
					$this->hPt=$this->fhPt;
					$this->w=$this->fw;
					$this->h=$this->fh;
				}
				else {
					$this->wPt=$this->fhPt;
					$this->hPt=$this->fwPt;
					$this->w=$this->fh;
					$this->h=$this->fw;
				}
				$this->PageBreakTrigger=$this->h-$this->bMargin;
				$this->CurOrientation=$orientation;
			}
		}

		/**
		* End of page contents
		* @access protected
		*/
		function _endpage() {
			$this->state=1;
		}

		/**
		* Begin a new object
		* @access protected
		*/
		function _newobj() {
			$this->n++;
			$this->offsets[$this->n]=strlen($this->buffer);
			$this->_out($this->n.' 0 obj');
		}

		/**
		* Underline text
		* @access protected
		*/
		function _dounderline($x,$y,$txt) {
			$up = $this->CurrentFont['up'];
			$ut = $this->CurrentFont['ut'];
			$w = $this->GetStringWidth($txt) + $this->ws * substr_count($txt,' ');
			return sprintf('%.2f %.2f %.2f %.2f re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
		}

		/**
		* Extract info from a JPEG file
		* @access protected
		*/
		function _parsejpg($file) {
			$a=GetImageSize($file);
			if(empty($a)) {
				$this->Error('Missing or incorrect image file: '.$file);
			}
			if($a[2]!=2) {
				$this->Error('Not a JPEG file: '.$file);
			}
			if(!isset($a['channels']) or $a['channels']==3) {
				$colspace='DeviceRGB';
			}
			elseif($a['channels']==4) {
				$colspace='DeviceCMYK';
			}
			else {
				$colspace='DeviceGray';
			}
			$bpc=isset($a['bits']) ? $a['bits'] : 8;
			//Read whole file
			$f=fopen($file,'rb');
			$data='';
			while(!feof($f)) {
				$data.=fread($f,4096);
			}
			fclose($f);
			return array('w'=>$a[0],'h'=>$a[1],'cs'=>$colspace,'bpc'=>$bpc,'f'=>'DCTDecode','data'=>$data);
		}

		/**
		* Extract info from a PNG file
		* @access protected
		*/
		function _parsepng($file) {
			$f=fopen($file,'rb');
			if(empty($f)) {
				$this->Error('Can\'t open image file: '.$file);
			}
			//Check signature
			if(fread($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10)) {
				$this->Error('Not a PNG file: '.$file);
			}
			//Read header chunk
			fread($f,4);
			if(fread($f,4)!='IHDR') {
				$this->Error('Incorrect PNG file: '.$file);
			}
			$w=$this->_freadint($f);
			$h=$this->_freadint($f);
			$bpc=ord(fread($f,1));
			if($bpc>8) {
				$this->Error('16-bit depth not supported: '.$file);
			}
			$ct=ord(fread($f,1));
			if($ct==0) {
				$colspace='DeviceGray';
			}
			elseif($ct==2) {
				$colspace='DeviceRGB';
			}
			elseif($ct==3) {
				$colspace='Indexed';
			}
			else {
				$this->Error('Alpha channel not supported: '.$file);
			}
			if(ord(fread($f,1))!=0) {
				$this->Error('Unknown compression method: '.$file);
			}
			if(ord(fread($f,1))!=0) {
				$this->Error('Unknown filter method: '.$file);
			}
			if(ord(fread($f,1))!=0) {
				$this->Error('Interlacing not supported: '.$file);
			}
			fread($f,4);
			$parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
			//Scan chunks looking for palette, transparency and image data
			$pal='';
			$trns='';
			$data='';
			do {
				$n=$this->_freadint($f);
				$type=fread($f,4);
				if($type=='PLTE') {
					//Read palette
					$pal=fread($f,$n);
					fread($f,4);
				}
				elseif($type=='tRNS') {
					//Read transparency info
					$t=fread($f,$n);
					if($ct==0) {
						$trns=array(ord(substr($t,1,1)));
					}
					elseif($ct==2) {
						$trns=array(ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1)));
					}
					else {
						$pos=strpos($t,chr(0));
						if($pos!==false) {
							$trns=array($pos);
						}
					}
					fread($f,4);
				}
				elseif($type=='IDAT') {
					//Read image data block
					$data.=fread($f,$n);
					fread($f,4);
				}
				elseif($type=='IEND') {
					break;
				}
				else {
					fread($f,$n+4);
				}
			}
			while($n);
			if($colspace=='Indexed' and empty($pal)) {
				$this->Error('Missing palette in '.$file);
			}
			fclose($f);
			return array('w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'FlateDecode', 'parms'=>$parms, 'pal'=>$pal, 'trns'=>$trns, 'data'=>$data);
		}

		/**
		* Read a 4-byte integer from file
		* @access protected
		*/
		function _freadint($f) {
			//Read a 4-byte integer from file
			$a=unpack('Ni',fread($f,4));
			return $a['i'];
		}

		/**
		* Format a text string
		* @access protected
		*/
		function _textstring($s) {
			if($this->isunicode) {
				//Convert string to UTF-16BE
				$s = $this->UTF8ToUTF16BE($s, true);
			}
			return '('. $this->_escape($s).')';
		}

		/**
		* Format a text string
		* @access protected
		*/
		function _escapetext($s) {
			if($this->isunicode) {
				//Convert string to UTF-16BE
				$s = $this->UTF8ToUTF16BE($s, false);
			}
			return $this->_escape($s);
		}

		/**
		* Add \ before \, ( and )
		* @access protected
		*/
		function _escape($s) {
			// the chr(13) substitution fixes the Bugs item #1421290.
			return strtr($s, array(')' => '\\)', '(' => '\\(', '\\' => '\\\\', chr(13) => '\r'));
		}

		/**
		*
		* @access protected
		*/
		function _putstream($s) {
			$this->_out('stream');
			$this->_out($s);
			$this->_out('endstream');
		}

		/**
		* Add a line to the document
		* @access protected
		*/
		function _out($s) {
			if($this->state==2) {
				$this->pages[$this->page] .= $s."\n";
			}
			else {
				$this->buffer .= $s."\n";
			}
		}

		/**
		* Adds unicode fonts.<br>
		* Based on PDF Reference 1.3 (section 5)
		* @access protected
		* @author Nicola Asuni
		* @since 1.52.0.TC005 (2005-01-05)
		*/
		function _puttruetypeunicode($font) {
			// Type0 Font
			// A composite font—a font composed of other fonts, organized hierarchically
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/Subtype /Type0');
			$this->_out('/BaseFont /'.$font['name'].'');
			$this->_out('/Encoding /Identity-H'); //The horizontal identity mapping for 2-byte CIDs; may be used with CIDFonts using any Registry, Ordering, and Supplement values.
			$this->_out('/DescendantFonts ['.($this->n + 1).' 0 R]');
			$this->_out('>>');
			$this->_out('endobj');
			
			// CIDFontType2
			// A CIDFont whose glyph descriptions are based on TrueType font technology
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/Subtype /CIDFontType2');
			$this->_out('/BaseFont /'.$font['name'].'');
			$this->_out('/CIDSystemInfo '.($this->n + 1).' 0 R'); 
			$this->_out('/FontDescriptor '.($this->n + 2).' 0 R');
			if (isset($font['desc']['MissingWidth'])){
				$this->_out('/DW '.$font['desc']['MissingWidth'].''); // The default width for glyphs in the CIDFont MissingWidth
			}
			$w = "";
			foreach ($font['cw'] as $cid => $width) {
				$w .= ''.$cid.' ['.$width.'] '; // define a specific width for each individual CID
			}
			$this->_out('/W ['.$w.']'); // A description of the widths for the glyphs in the CIDFont
			$this->_out('/CIDToGIDMap '.($this->n + 3).' 0 R');
			$this->_out('>>');
			$this->_out('endobj');
			
			// CIDSystemInfo dictionary
			// A dictionary containing entries that define the character collection of the CIDFont.
			$this->_newobj();
			$this->_out('<</Registry (Adobe)'); // A string identifying an issuer of character collections
			$this->_out('/Ordering (UCS)'); // A string that uniquely names a character collection issued by a specific registry
			$this->_out('/Supplement 0'); // The supplement number of the character collection.
			$this->_out('>>');
			$this->_out('endobj');
			
			// Font descriptor
			// A font descriptor describing the CIDFont’s default metrics other than its glyph widths
			$this->_newobj();
			$this->_out('<</Type /FontDescriptor');
			$this->_out('/FontName /'.$font['name']);
			foreach ($font['desc'] as $key => $value) {
				$this->_out('/'.$key.' '.$value);
			}
			if ($font['file']) {
				// A stream containing a TrueType font program
				$this->_out('/FontFile2 '.$this->FontFiles[$font['file']]['n'].' 0 R');
			}
			$this->_out('>>');
			$this->_out('endobj');

			// Embed CIDToGIDMap
			// A specification of the mapping from CIDs to glyph indices
			$this->_newobj();
			$ctgfile = $this->_getfontpath().$font['ctg'];
			if(!file_exists($ctgfile)) {
				$this->Error('Font file not found: '.$ctgfile);
			}
			$size = filesize($ctgfile);
			$this->_out('<</Length '.$size.'');
			if(substr($ctgfile, -2) == '.z') { // check file extension
				/* Decompresses data encoded using the public-domain 
				zlib/deflate compression method, reproducing the 
				original text or binary data */
				$this->_out('/Filter /FlateDecode');
			}
			$this->_out('>>');
			$this->_putstream(file_get_contents($ctgfile));
			$this->_out('endobj');
		}

		 /**
		 * Converts UTF-8 strings to codepoints array.<br>
		 * Invalid byte sequences will be replaced with 0xFFFD (replacement character)<br>
		 * Based on: http://www.faqs.org/rfcs/rfc3629.html
		 * <pre>
		 * 	  Char. number range  |        UTF-8 octet sequence
		 *       (hexadecimal)    |              (binary)
		 *    --------------------+-----------------------------------------------
		 *    0000 0000-0000 007F | 0xxxxxxx
		 *    0000 0080-0000 07FF | 110xxxxx 10xxxxxx
		 *    0000 0800-0000 FFFF | 1110xxxx 10xxxxxx 10xxxxxx
		 *    0001 0000-0010 FFFF | 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
		 *    ---------------------------------------------------------------------
		 *
		 *   ABFN notation:
		 *   ---------------------------------------------------------------------
		 *   UTF8-octets = *( UTF8-char )
		 *   UTF8-char   = UTF8-1 / UTF8-2 / UTF8-3 / UTF8-4
		 *   UTF8-1      = %x00-7F
		 *   UTF8-2      = %xC2-DF UTF8-tail
		 *
		 *   UTF8-3      = %xE0 %xA0-BF UTF8-tail / %xE1-EC 2( UTF8-tail ) /
		 *                 %xED %x80-9F UTF8-tail / %xEE-EF 2( UTF8-tail )
		 *   UTF8-4      = %xF0 %x90-BF 2( UTF8-tail ) / %xF1-F3 3( UTF8-tail ) /
		 *                 %xF4 %x80-8F 2( UTF8-tail )
		 *   UTF8-tail   = %x80-BF
		 *   ---------------------------------------------------------------------
		 * </pre>
		 * @param string $str string to process.
		 * @return array containing codepoints (UTF-8 characters values)
		 * @access protected
		 * @author Nicola Asuni
		 * @since 1.53.0.TC005 (2005-01-05)
		 */
		function UTF8StringToArray($str) {
			if(!$this->isunicode) {
				return $str; // string is not in unicode
			}
			$unicode = array(); // array containing unicode values
			$bytes  = array(); // array containing single character byte sequences
			$numbytes  = 1; // number of octetc needed to represent the UTF-8 character
			
			$str .= ""; // force $str to be a string
			$length = strlen($str);
			
			for($i = 0; $i < $length; $i++) {
				$char = ord($str{$i}); // get one string character at time
				if(count($bytes) == 0) { // get starting octect
					if ($char <= 0x7F) {
						$unicode[] = $char; // use the character "as is" because is ASCII
						$numbytes = 1;
					} elseif (($char >> 0x05) == 0x06) { // 2 bytes character (0x06 = 110 BIN)
						$bytes[] = ($char - 0xC0) << 0x06; 
						$numbytes = 2;
					} elseif (($char >> 0x04) == 0x0E) { // 3 bytes character (0x0E = 1110 BIN)
						$bytes[] = ($char - 0xE0) << 0x0C; 
						$numbytes = 3;
					} elseif (($char >> 0x03) == 0x1E) { // 4 bytes character (0x1E = 11110 BIN)
						$bytes[] = ($char - 0xF0) << 0x12; 
						$numbytes = 4;
					} else {
						// use replacement character for other invalid sequences
						$unicode[] = 0xFFFD;
						$bytes = array();
						$numbytes = 1;
					}
				} elseif (($char >> 0x06) == 0x02) { // bytes 2, 3 and 4 must start with 0x02 = 10 BIN
					$bytes[] = $char - 0x80;
					if (count($bytes) == $numbytes) {
						// compose UTF-8 bytes to a single unicode value
						$char = $bytes[0];
						for($j = 1; $j < $numbytes; $j++) {
							$char += ($bytes[$j] << (($numbytes - $j - 1) * 0x06));
						}
						if ((($char >= 0xD800) AND ($char <= 0xDFFF)) OR ($char >= 0x10FFFF)) {
							/* The definition of UTF-8 prohibits encoding character numbers between
							U+D800 and U+DFFF, which are reserved for use with the UTF-16
							encoding form (as surrogate pairs) and do not directly represent
							characters. */
							$unicode[] = 0xFFFD; // use replacement character
						}
						else {
							$unicode[] = $char; // add char to array
						}
						// reset data for next char
						$bytes = array(); 
						$numbytes = 1;
					}
				} else {
					// use replacement character for other invalid sequences
					$unicode[] = 0xFFFD;
					$bytes = array();
					$numbytes = 1;
				}
			}
			return $unicode;
		}
		
		/**
		 * Converts UTF-8 strings to UTF16-BE.<br>
		 * Based on: http://www.faqs.org/rfcs/rfc2781.html
	 	 * <pre>
		 *   Encoding UTF-16:
		 * 
 		 *   Encoding of a single character from an ISO 10646 character value to
		 *    UTF-16 proceeds as follows. Let U be the character number, no greater
		 *    than 0x10FFFF.
		 * 
		 *    1) If U < 0x10000, encode U as a 16-bit unsigned integer and
		 *       terminate.
		 * 
		 *    2) Let U' = U - 0x10000. Because U is less than or equal to 0x10FFFF,
		 *       U' must be less than or equal to 0xFFFFF. That is, U' can be
		 *       represented in 20 bits.
		 * 
		 *    3) Initialize two 16-bit unsigned integers, W1 and W2, to 0xD800 and
		 *       0xDC00, respectively. These integers each have 10 bits free to
		 *       encode the character value, for a total of 20 bits.
		 * 
		 *    4) Assign the 10 high-order bits of the 20-bit U' to the 10 low-order
		 *       bits of W1 and the 10 low-order bits of U' to the 10 low-order
		 *       bits of W2. Terminate.
		 * 
		 *    Graphically, steps 2 through 4 look like:
		 *    U' = yyyyyyyyyyxxxxxxxxxx
		 *    W1 = 110110yyyyyyyyyy
		 *    W2 = 110111xxxxxxxxxx
		 * </pre>
		 * @param string $str string to process.
		 * @param boolean $setbom if true set the Byte Order Mark (BOM = 0xFEFF)
		 * @return string
		 * @access protected
		 * @author Nicola Asuni
		 * @since 1.53.0.TC005 (2005-01-05)
		 * @uses UTF8StringToArray
		 */
		function UTF8ToUTF16BE($str, $setbom=true) {
			if(!$this->isunicode) {
				return $str; // string is not in unicode
			}
			$outstr = ""; // string to be returned
			$unicode = $this->UTF8StringToArray($str); // array containing UTF-8 unicode values
			$numitems = count($unicode);
			
			if ($setbom) {
				$outstr .= "\xFE\xFF"; // Byte Order Mark (BOM)
			}
			foreach($unicode as $char) {
				if($char == 0xFFFD) {
					$outstr .= "\xFF\xFD"; // replacement character
				} elseif ($char < 0x10000) {
					$outstr .= chr($char >> 0x08);
					$outstr .= chr($char & 0xFF);
				} else {
					$char -= 0x10000;
					$w1 = 0xD800 | ($char >> 0x10);
					$w2 = 0xDC00 | ($char & 0x3FF);	
					$outstr .= chr($w1 >> 0x08);
					$outstr .= chr($w1 & 0xFF);
					$outstr .= chr($w2 >> 0x08);
					$outstr .= chr($w2 & 0xFF);
				}
			}
			return $outstr;
		}
		
		// ====================================================
		
		/**
	 	 * Set header font.
		 * @param array $font font
		 * @since 1.1
		 */
		function setHeaderFont($font) {
			$this->header_font = $font;
		}
		
		/**
	 	 * Set footer font.
		 * @param array $font font
		 * @since 1.1
		 */
		function setFooterFont($font) {
			$this->footer_font = $font;
		}
		
		/**
	 	 * Set language array.
		 * @param array $language
		 * @since 1.1
		 */
		function setLanguageArray($language) {
			$this->l = $language;
		}
		
		/**
	 	 * Set document barcode.
		 * @param string $bc barcode
		 */
		function setBarcode($bc="") {
			$this->barcode = $bc;
		}
		
		/**
	 	 * Print Barcode.
		 * @param int $x x position in user units
		 * @param int $y y position in user units
		 * @param int $w width in user units
		 * @param int $h height position in user units
		 * @param string $type type of barcode (I25, C128A, C128B, C128C, C39)
		 * @param string $style barcode style
		 * @param string $font font for text
		 * @param int $xres x resolution
		 * @param string $code code to print
		 */
		function writeBarcode($x, $y, $w, $h, $type, $style, $font, $xres, $code) {
			require_once(dirname(__FILE__)."/barcode/barcode.php");
			require_once(dirname(__FILE__)."/barcode/i25object.php");
			require_once(dirname(__FILE__)."/barcode/c39object.php");
			require_once(dirname(__FILE__)."/barcode/c128aobject.php");
			require_once(dirname(__FILE__)."/barcode/c128bobject.php");
			require_once(dirname(__FILE__)."/barcode/c128cobject.php");
			
			if (empty($code)) {
				return;
			}
			
			if (empty($style)) {
				$style  = BCS_ALIGN_LEFT;
				$style |= BCS_IMAGE_PNG;
				$style |= BCS_TRANSPARENT;
				//$style |= BCS_BORDER;
				//$style |= BCS_DRAW_TEXT;
				//$style |= BCS_STRETCH_TEXT;
				//$style |= BCS_REVERSE_COLOR;
			}
			if (empty($font)) {$font = BCD_DEFAULT_FONT;}
			if (empty($xres)) {$xres = BCD_DEFAULT_XRES;}
			
			$scale_factor = 1.5 * $xres * $this->k;
			$bc_w = round($w * $scale_factor); //width in points
			$bc_h = round($h * $scale_factor); //height in points
			
			switch (strtoupper($type)) {
				case "I25": {
					$obj = new I25Object($bc_w, $bc_h, $style, $code);
					break;
				}
				case "C128A": {
					$obj = new C128AObject($bc_w, $bc_h, $style, $code);
					break;
				}
				default:
				case "C128B": {
					$obj = new C128BObject($bc_w, $bc_h, $style, $code);
					break;
				}
				case "C128C": {
					$obj = new C128CObject($bc_w, $bc_h, $style, $code);
					break;
				}
				case "C39": {
					$obj = new C39Object($bc_w, $bc_h, $style, $code);
					break;
				}
			}
			
			$obj->SetFont($font);   
			$obj->DrawObject($xres);
			
			//use a temporary file....
			$tmpName = tempnam(K_PATH_CACHE,'img');
			imagepng($obj->getImage(), $tmpName);
			$this->Image($tmpName, $x, $y, $w, $h, 'png');
			$obj->DestroyObject();
			unset($obj);
			unlink($tmpName);
		}
		
		/**
	 	 * Returns the PDF data.
		 */
		function getPDFData() {
			if($this->state < 3) {
				$this->Close();
			}
			return $this->buffer;
		}
		
		// --- HTML PARSER FUNCTIONS ---
		
		/**
		 * Allows to preserve some HTML formatting.<br />
		 * Supports: h1, h2, h3, h4, h5, h6, b, u, i, a, img, p, br, strong, em, font, blockquote, li, ul, ol, hr, td, th, tr, table, sup, sub, small
		 * @param string $html text to display
		 * @param boolean $ln if true add a new line after text (default = true)
		 * @param int $fill Indicates if the background must be painted (1) or transparent (0). Default value: 0.
		 */
		function writeHTML($html, $ln=true, $fill=0) {
						
			// store some variables
			$html=strip_tags($html,"<h1><h2><h3><h4><h5><h6><b><u><i><a><img><p><br><br/><strong><em><font><blockquote><li><ul><ol><hr><td><th><tr><table><sup><sub><small>"); //remove all unsupported tags
			//replace carriage returns, newlines and tabs
			$repTable = array("\t" => " ", "\n" => " ", "\r" => " ", "\0" => " ", "\x0B" => " "); 
			$html = strtr($html, $repTable);
			$pattern = '/(<[^>]+>)/Uu';
			$a = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); //explodes the string
			
			if (empty($this->lasth)) {
				//set row height
				$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO; 
			}
			
			foreach($a as $key=>$element) {
				if (!preg_match($pattern, $element)) {
					//Text
					if($this->HREF) {
						$this->addHtmlLink($this->HREF, $element, $fill);
					}
					elseif($this->tdbegin) {
						if((strlen(trim($element)) > 0) AND ($element != "&nbsp;")) {
							$this->Cell($this->tdwidth, $this->tdheight, $this->unhtmlentities($element), $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
						}
						elseif($element == "&nbsp;") {
							$this->Cell($this->tdwidth, $this->tdheight, '', $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
						}
					}
					else {
						$this->Write($this->lasth, stripslashes($this->unhtmlentities($element)), '', $fill);
					}
				}
				else {
					$element = substr($element, 1, -1);
					//Tag
					if($element{0}=='/') {
						$this->closedHTMLTagHandler(strtolower(substr($element, 1)));
					}
					else {
						//Extract attributes
						// get tag name
						preg_match('/([a-zA-Z0-9]*)/', $element, $tag);
						$tag = strtolower($tag[0]);
						// get attributes
						preg_match_all('/([^=\s]*)=["\']?([^"\']*)["\']?/', $element, $attr_array, PREG_PATTERN_ORDER);
						$attr = array(); // reset attribute array
						while(list($id,$name)=each($attr_array[1])) {
							$attr[strtolower($name)] = $attr_array[2][$id];
						}
						$this->openHTMLTagHandler($tag, $attr, $fill);
					}
				}
			}
			if ($ln) {
				$this->Ln($this->lasth);
			}
		}
		
		/**
		 * Prints a cell (rectangular area) with optional borders, background color and html text string. The upper-left corner of the cell corresponds to the current position. After the call, the current position moves to the right or to the next line.<br />
		 * If automatic page breaking is enabled and the cell goes beyond the limit, a page break is done before outputting.
		 * @param float $w Cell width. If 0, the cell extends up to the right margin.
		 * @param float $h Cell minimum height. The cell extends automatically if needed.
		 * @param float $x upper-left corner X coordinate
		 * @param float $y upper-left corner Y coordinate
		 * @param string $html html text to print. Default value: empty string.
		 * @param mixed $border Indicates if borders must be drawn around the cell. The value can be either a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul>
		 * @param int $ln Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>
	Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
		 * @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
		 * @see Cell()
		 */
		function writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=0) {
			
			if (empty($this->lasth)) {
				//set row height
				$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO; 
			}
			
			if (empty($x)) {
				$x = $this->GetX();
			}
			if (empty($y)) {
				$y = $this->GetY();
			}
			
			// get current page number
			$pagenum = $this->page;
			
			$this->SetX($x);
			$this->SetY($y);
					
			if(empty($w)) {
				$w = $this->fw - $x - $this->rMargin;
			}
			
			// store original margin values
			$lMargin = $this->lMargin;
			$rMargin = $this->rMargin;
			
			// set new margin values
			$this->SetLeftMargin($x);
			$this->SetRightMargin($this->fw - $x - $w);
					
			// calculate remaining vertical space on page
			$restspace = $this->getPageHeight() - $this->GetY() - $this->getBreakMargin();
			
			$this->writeHTML($html, true, $fill); // write html text
			
			$currentY =  $this->GetY();
			
			// check if a new page has been created
			if ($this->page > $pagenum) {
				// design a cell around the text on first page
				$currentpage = $this->page;
				$this->page = $pagenum;
				$this->SetY($this->getPageHeight() - $restspace - $this->getBreakMargin());
				$h = $restspace - 1;
				$this->Cell($w, $h, "", $border, $ln, 'L', 0);
				// design a cell around the text on last page
				$this->page = $currentpage;
				$h = $currentY - $this->tMargin;
				$this->SetY($this->tMargin); // put cursor at the beginning of text
				$this->Cell($w, $h, "", $border, $ln, 'L', 0);
			} else {
				$h = max($h, ($currentY - $y));
				$this->SetY($y); // put cursor at the beginning of text
				// design a cell around the text
				$this->Cell($w, $h, "", $border, $ln, 'L', 0);
			}
			
			// restore original margin values
			$this->SetLeftMargin($lMargin);
			$this->SetRightMargin($rMargin);
			
			if ($ln) {
				$this->Ln(0);
			}
		}
		
		/**
		 * Process opening tags.
		 * @param string $tag tag name (in uppercase)
		 * @param string $attr tag attribute (in uppercase)
		 * @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
		 * @access private
		 */
		function openHTMLTagHandler($tag, $attr, $fill=0) {
			//Opening tag
			switch($tag) {
				case 'table': {
					if ((isset($attr['border'])) AND ($attr['border'] != '')) {
						$this->tableborder = $attr['border'];
					}
					else {
						$this->tableborder = 0;
					}
					break;
				}
				case 'tr': {
					break;
				}
				case 'td':
				case 'th': {
					if ((isset($attr['width'])) AND ($attr['width'] != '')) {
						$this->tdwidth = ($attr['width']/4);
					}
					else {
						$this->tdwidth = (($this->w - $this->lMargin - $this->rMargin) / $this->default_table_columns);
					}
					if ((isset($attr['height'])) AND ($attr['height'] != '')) {
						$this->tdheight=($attr['height'] / $this->k);
					}
					else {
						$this->tdheight = $this->lasth;
					}
					if ((isset($attr['align'])) AND ($attr['align'] != '')) {
						switch ($attr['align']) {
							case 'center': {
								$this->tdalign = "C";
								break;
							}
							case 'right': {
								$this->tdalign = "R";
								break;
							}
							default:
							case 'left': {
								$this->tdalign = "L";
								break;
							}
						}
					}
					if ((isset($attr['bgcolor'])) AND ($attr['bgcolor'] != '')) {
						$coul = $this->convertColorHexToDec($attr['bgcolor']);
						$this->SetFillColor($coul['R'], $coul['G'], $coul['B']);
						$this->tdbgcolor=true;
					}
					$this->tdbegin=true;
					break;
				}
				case 'hr': {
					$this->Ln();
					if ((isset($attr['width'])) AND ($attr['width'] != '')) {
						$hrWidth = $attr['width'];
					}
					else {
						$hrWidth = $this->w - $this->lMargin - $this->rMargin;
					}
					$x = $this->GetX();
					$y = $this->GetY();
					$this->SetLineWidth(0.2);
					$this->Line($x, $y, $x + $hrWidth, $y);
					$this->SetLineWidth(0.2);
					$this->Ln();
					break;
				}
				case 'strong': {
					$this->setStyle('b', true);
					break;
				}
				case 'em': {
					$this->setStyle('i', true);
					break;
				}
				case 'b':
				case 'i':
				case 'u': {
					$this->setStyle($tag, true);
					break;
				}
				case 'a': {
					$this->HREF = $attr['href'];
					break;
				}
				case 'img': {
					if(isset($attr['src'])) {
						// replace relative path with real server path
						$attr['src'] = str_replace(K_PATH_URL_CACHE, K_PATH_CACHE, $attr['src']);
						if(!isset($attr['width'])) {
							$attr['width'] = 0;
						}
						if(!isset($attr['height'])) {
							$attr['height'] = 0;
						}
						
						$this->Image($attr['src'], $this->GetX(),$this->GetY(), $this->pixelsToMillimeters($attr['width']), $this->pixelsToMillimeters($attr['height']));
						//$this->SetX($this->img_rb_x);
						$this->SetY($this->img_rb_y);
						
					}
					break;
				}
				case 'ul': {
					$this->listordered = false;
					$this->listcount = 0;
					break;
				}
				case 'ol': {
					$this->listordered = true;
					$this->listcount = 0;
					break;
				}
				case 'li': {
					$this->Ln();
					if ($this->listordered) {
						$this->lispacer = "    ".(++$this->listcount).". ";
					}
					else {
						//unordered list simbol
						$this->lispacer = "    -  ";
					}
					$this->Write($this->lasth, $this->lispacer, '', $fill);
					break;
				}
				case 'blockquote':
				case 'br': {
					$this->Ln();
					if(strlen($this->lispacer) > 0) {
						$this->x += $this->GetStringWidth($this->lispacer);
					}
					break;
				}
				case 'p': {
					$this->Ln();
					$this->Ln();
					break;
				}
				case 'sup': {
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
					$this->SetXY($this->GetX(), $this->GetY() - (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
					break;
				}
				case 'sub': {
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
					$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
					break;
				}
				case 'small': {
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
					$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)/3));
					break;
				}
				case 'font': {
					if (isset($attr['color']) AND $attr['color']!='') {
						$coul = $this->convertColorHexToDec($attr['color']);
						$this->SetTextColor($coul['R'],$coul['G'],$coul['B']);
						$this->issetcolor=true;
					}
					if (isset($attr['face']) and in_array(strtolower($attr['face']), $this->fontlist)) {
						$this->SetFont(strtolower($attr['FACE']));
						$this->issetfont=true;
					}
					if (isset($attr['size'])) {
						$headsize = intval($attr['size']);
					} else {
						$headsize = 0;
					}
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt + $headsize);
					$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
					break;
				}
				case 'h1': 
				case 'h2': 
				case 'h3': 
				case 'h4': 
				case 'h5': 
				case 'h6': {
					$headsize = (4 - substr($tag, 1)) * 2;
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt + $headsize);
					$this->setStyle('b', true);
					$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
					break;
				}
			}
		}
		
		/**
		 * Process closing tags.
		 * @param string $tag tag name (in uppercase)
		 * @access private
		 */
		function closedHTMLTagHandler($tag) {
			//Closing tag
			switch($tag) {
				case 'td':
				case 'th': {
					$this->tdbegin = false;
					$this->tdwidth = 0;
					$this->tdheight = 0;
					$this->tdalign = "L";
					$this->tdbgcolor = false;
					$this->SetFillColor($this->prevFillColor[0], $this->prevFillColor[1], $this->prevFillColor[2]);
					break;
				}
				case 'tr': {
					$this->Ln();
					break;
				}
				case 'table': {
					$this->tableborder=0;
					break;
				}
				case 'strong': {
					$this->setStyle('b', false);
					break;
				}
				case 'em': {
					$this->setStyle('i', false);
					break;
				}
				case 'b':
				case 'i':
				case 'u': {
					$this->setStyle($tag, false);
					break;
				}
				case 'a': {
					$this->HREF = '';
					break;
				}
				case 'sup': {
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					$this->SetXY($this->GetX(), $this->GetY() - (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
					break;
				}
				case 'sub': {
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
					break;
				}
				case 'small': {
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					$this->SetXY($this->GetX(), $this->GetY() - (($this->FontSize - $currentFontSize)/3));
					break;
				}
				case 'font': {
					if ($this->issetcolor == true) {
						$this->SetTextColor($this->prevTextColor[0], $this->prevTextColor[1], $this->prevTextColor[2]);
					}
					if ($this->issetfont) {
						$this->FontFamily = $this->prevFontFamily;
						$this->FontStyle = $this->prevFontStyle;
						$this->SetFont($this->FontFamily);
						$this->issetfont = false;
					}
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					//$this->TextColor = $this->prevTextColor;
					$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
					break;
				}
				case 'ul': {
					$this->Ln();
					break;
				}
				case 'ol': {
					$this->Ln();
					break;
				}
				case 'li': {
					$this->lispacer = "";
					break;
				}
				case 'h1': 
				case 'h2': 
				case 'h3': 
				case 'h4': 
				case 'h5': 
				case 'h6': {
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					$this->setStyle('b', false);
					$this->Ln();
					$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
					break;
				}
				default : {
					break;
				}
			}
		}
		
		/**
		 * Sets font style.
		 * @param string $tag tag name (in lowercase)
		 * @param boolean $enable
		 * @access private
		 */
		function setStyle($tag, $enable) {
			//Modify style and select corresponding font
			$this->$tag += ($enable ? 1 : -1);
			$style='';
			foreach(array('b', 'i', 'u') as $s) {
				if($this->$s > 0) {
					$style .= $s;
				}
			}
			$this->SetFont('', $style);
		}
		
		/**
		 * Output anchor link.
		 * @param string $url link URL
		 * @param string $name link name
		 * @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
		 * @access public
		 */
		function addHtmlLink($url, $name, $fill=0) {
			//Put a hyperlink
			$this->SetTextColor(0, 0, 255);
			$this->setStyle('u', true);
			$this->Write($this->lasth, $name, $url, $fill);
			$this->setStyle('u', false);
			$this->SetTextColor(0);
		}
		
		/**
		 * Returns an associative array (keys: R,G,B) from 
		 * a hex html code (e.g. #3FE5AA).
		 * @param string $color hexadecimal html color [#rrggbb]
		 * @return array
		 * @access private
		 */
		function convertColorHexToDec($color = "#000000"){
			$tbl_color = array();
			$tbl_color['R'] = hexdec(substr($color, 1, 2));
			$tbl_color['G'] = hexdec(substr($color, 3, 2));
			$tbl_color['B'] = hexdec(substr($color, 5, 2));
			return $tbl_color;
		}
		
		/**
		 * Converts pixels to millimeters in 72 dpi.
		 * @param int $px pixels
		 * @return float millimeters
		 * @access private
		 */
		function pixelsToMillimeters($px){
			return $px * 25.4 / 72;
		}
			
		/**
		 * Reverse function for htmlentities.
		 * Convert entities in UTF-8.
		 *
		 * @param $text_to_convert Text to convert.
		 * @return string converted
		 */
		function unhtmlentities($text_to_convert) {
			require_once(dirname(__FILE__).'/html_entity_decode_php4.php');
			return html_entity_decode_php4($text_to_convert);
		}
	} // END OF CLASS

	//Handle special IE contype request
	if(isset($_SERVER['HTTP_USER_AGENT']) AND ($_SERVER['HTTP_USER_AGENT']=='contype')) {
		header('Content-Type: application/pdf');
		exit;
	}
	
}
//============================================================+
// END OF FILE
//============================================================+
?>
