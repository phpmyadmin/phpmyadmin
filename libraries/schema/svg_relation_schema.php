<?php
include_once("relation_schema.php");
class PMA_SVG extends XMLWriter
{
	public $title;
	public $author;
	public $font;
	public $fontSize;
	
	function __construct()
	{
		//$writer = new XMLWriter();
		$this->openMemory();
		/* Set indenting using three spaces, so output is formatted */
		$this->setIndent(TRUE);
		$this->setIndentString('   ');
		/* Create the XML document */
		$this->startDocument('1.0','UTF-8');
		$this->startDtd('svg','-//W3C//DTD SVG 1.1//EN','http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd');
		$this->endDtd();		
	}
	
	function setTitle($title)
	{
		$this->title=$title;
	}
	
	function setAuthor($author)
	{
		$this->author=$author;
	}
	
	function setFont($font)
	{
		$this->font=$font;
	}
	function setFontSize($fontSize)
	{
		$this->fontSize=$fontSize;
	}
	
	function startSvgDoc($width,$height)
	{

	 	$this->startElement('svg');
		$this->writeAttribute('width', $width);
		$this->writeAttribute('height', $height);
		$this->writeAttribute('xmlns', 'http://www.w3.org/2000/svg');
		$this->writeAttribute('version', '1.1');
	}
	
	function endSvgDoc()
	{

		$this->endElement();
		$this->endDocument();
	}
	
	function showOutput()
	{

		header('Content-type: image/svg+xml');
		$output = $this->flush();
		print $output;
	}
	
	function printElement($name,$x,$y,$width='',$height='',$text='',$styles='')
	{

		$this->startElement($name);
		$this->writeAttribute('width',$width);
		$this->writeAttribute('height',$height);
		$this->writeAttribute('x', $x);
		$this->writeAttribute('y', $y);
		$this->writeAttribute('style', $styles);
		if(isset($text)){
			$this->writeAttribute('font-family', $this->font);
			$this->writeAttribute('font-size', $this->fontSize);
			$this->text($text);
		}	
		$this->endElement();
	}
}

class PMA_SVG_RELAION_SCHEMA extends PMA_Relation_Schema
{
	
	function __construct($page_number, $show_info = 0, $change_color = 0, $all_table_same_wide = 0, $show_keys = 0)
	{
       //global $pdf, $db, $cfgRelation, $with_doc;
	   global $db,$writer;

		$svg = new PMA_SVG();
        $this->setSameWidthTables($all_table_same_wide);
		
		$svg->setTitle(sprintf(__('Schema of the %s database - Page %s'), $db, $page_number));
		$svg->SetAuthor('phpMyAdmin ' . PMA_VERSION);
		$svg->setFont('Arial');
		$svg->setFontSize('16px');
		$svg->startSvgDoc('500px','500px');
		$svg->printElement('rect',0,0,'100','100',NULL,'fill:none;stroke:black;');
		$svg->printElement('text',100,100,'100','100','this is just a test');
		$svg->endSvgDoc();
		$svg->showOutput();
		//echo $svg->getTitle();
	/*	$alltables=$this->getAllTables($db,$page_number);
		foreach ($alltables AS $table) {
            if (!isset($this->tables[$table])) {
                $this->tables[$table] = new PMA_RT_Table($table, $this->ff, $this->_tablewidth, $show_keys, $show_info);
			   // $this->tables[$table]=$table;
            }

            if ($this->same_wide) {
                $this->tables[$table]->width = $this->_tablewidth;
            }
            $this->PMA_RT_setMinMax($this->tables[$table]);
        }*/
		
		
/*		print '<pre>';
		print_r(get_object_vars($svg));
		print_r($this);
		print '</pre>';
		exit();*/
	}	
}
?>