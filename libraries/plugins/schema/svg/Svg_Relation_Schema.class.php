<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Classes to create relation schema in SVG format.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/plugins/schema/Export_Relation_Schema.class.php';
require_once 'libraries/plugins/schema/svg/RelationStatsSvg.class.php';
require_once 'libraries/plugins/schema/svg/TableStatsSvg.class.php';
require_once 'libraries/Font.class.php';

/**
 * This Class inherits the XMLwriter class and
 * helps in developing structure of SVG Schema Export
 *
 * @package PhpMyAdmin
 * @access  public
 * @see     http://php.net/manual/en/book.xmlwriter.php
 */
class PMA_SVG extends XMLWriter
{
    public $title;
    public $author;
    public $font;
    public $fontSize;

    /**
     * The "PMA_SVG" constructor
     *
     * Upon instantiation This starts writing the Svg XML document
     *
     * @see XMLWriter::openMemory(),XMLWriter::setIndent(),XMLWriter::startDocument()
     */
    function __construct()
    {
        $this->openMemory();
        /*
         * Set indenting using three spaces,
         * so output is formatted
         */

        $this->setIndent(true);
        $this->setIndentString('   ');
        /*
         * Create the XML document
         */

        $this->startDocument('1.0', 'UTF-8');
        $this->startDtd(
            'svg', '-//W3C//DTD SVG 1.1//EN',
            'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'
        );
        $this->endDtd();
    }

    /**
     * Set document title
     *
     * @param string $value sets the title text
     *
     * @return void
     * @access public
     */
    function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * Set document author
     *
     * @param string $value sets the author
     *
     * @return void
     * @access public
     */
    function setAuthor($value)
    {
        $this->author = $value;
    }

    /**
     * Set document font
     *
     * @param string $value sets the font e.g Arial, Sans-serif etc
     *
     * @return void
     * @access public
     */
    function setFont($value)
    {
        $this->font = $value;
    }

    /**
     * Get document font
     *
     * @return string returns the font name
     * @access public
     */
    function getFont()
    {
        return $this->font;
    }

    /**
     * Set document font size
     *
     * @param string $value sets the font size in pixels
     *
     * @return void
     * @access public
     */
    function setFontSize($value)
    {
        $this->fontSize = $value;
    }

    /**
     * Get document font size
     *
     * @return string returns the font size
     * @access public
     */
    function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * Starts Svg Document
     *
     * svg document starts by first initializing svg tag
     * which contains all the attributes and namespace that needed
     * to define the svg document
     *
     * @param integer $width  total width of the Svg document
     * @param integer $height total height of the Svg document
     *
     * @return void
     * @access public
     *
     * @see XMLWriter::startElement(),XMLWriter::writeAttribute()
     */
    function startSvgDoc($width,$height)
    {
        $this->startElement('svg');
        $this->writeAttribute('width', $width);
        $this->writeAttribute('height', $height);
        $this->writeAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $this->writeAttribute('version', '1.1');
    }

    /**
     * Ends Svg Document
     *
     * @return void
     * @access public
     * @see XMLWriter::endElement(),XMLWriter::endDocument()
     */
    function endSvgDoc()
    {
        $this->endElement();
        $this->endDocument();
    }

    /**
     * output Svg Document
     *
     * svg document prompted to the user for download
     * Svg document saved in .svg extension and can be
     * easily changeable by using any svg IDE
     *
     * @param string $fileName file name
     *
     * @return void
     * @access public
     * @see XMLWriter::startElement(),XMLWriter::writeAttribute()
     */
    function showOutput($fileName)
    {
        //ob_get_clean();
        $output = $this->flush();
        PMA_Response::getInstance()->disable();
        PMA_downloadHeader(
            $fileName,
            'image/svg+xml',
            /*overload*/mb_strlen($output)
        );
        print $output;
    }

    /**
     * Draws Svg elements
     *
     * SVG has some predefined shape elements like rectangle & text
     * and other elements who have x,y co-ordinates are drawn.
     * specify their width and height and can give styles too.
     *
     * @param string     $name   Svg element name
     * @param int        $x      The x attr defines the left position of the element
     * (e.g. x="0" places the element 0 pixels from the left of the browser window)
     * @param integer    $y      The y attribute defines the top position of the
     * element (e.g. y="0" places the element 0 pixels from the top of the browser
     * window)
     * @param int|string $width  The width attribute defines the width the element
     * @param int|string $height The height attribute defines the height the element
     * @param string     $text   The text attribute defines the text the element
     * @param string     $styles The style attribute defines the style the element
     * styles can be defined like CSS styles
     *
     * @return void
     * @access public
     *
     * @see XMLWriter::startElement(), XMLWriter::writeAttribute(),
     * XMLWriter::text(), XMLWriter::endElement()
     */
    function printElement($name, $x, $y, $width = '', $height = '',
        $text = '', $styles = ''
    ) {
        $this->startElement($name);
        $this->writeAttribute('width', $width);
        $this->writeAttribute('height', $height);
        $this->writeAttribute('x', $x);
        $this->writeAttribute('y', $y);
        $this->writeAttribute('style', $styles);
        if (isset($text)) {
            $this->writeAttribute('font-family', $this->font);
            $this->writeAttribute('font-size', $this->fontSize);
            $this->text($text);
        }
        $this->endElement();
    }

    /**
     * Draws Svg Line element
     *
     * Svg line element is drawn for connecting the tables.
     * arrows are also drawn by specify its start and ending
     * co-ordinates
     *
     * @param string  $name   Svg element name i.e line
     * @param integer $x1     Defines the start of the line on the x-axis
     * @param integer $y1     Defines the start of the line on the y-axis
     * @param integer $x2     Defines the end of the line on the x-axis
     * @param integer $y2     Defines the end of the line on the y-axis
     * @param string  $styles The style attribute defines the style the element
     *                        styles can be defined like CSS styles
     *
     * @return void
     * @access public
     *
     * @see XMLWriter::startElement(), XMLWriter::writeAttribute(),
     * XMLWriter::endElement()
     */
    function printElementLine($name,$x1,$y1,$x2,$y2,$styles)
    {
        $this->startElement($name);
        $this->writeAttribute('x1', $x1);
        $this->writeAttribute('y1', $y1);
        $this->writeAttribute('x2', $x2);
        $this->writeAttribute('y2', $y2);
        $this->writeAttribute('style', $styles);
        $this->endElement();
    }
}

/**
 * Svg Relation Schema Class
 *
 * Purpose of this class is to generate the SVG XML Document because
 * SVG defines the graphics in XML format which is used for representing
 * the database diagrams as vector image. This class actually helps
 *  in preparing SVG XML format.
 *
 * SVG XML is generated by using XMLWriter php extension and this class
 * inherits Export_Relation_Schema class has common functionality added
 * to this class
 *
 * @package PhpMyAdmin
 * @name Svg_Relation_Schema
 */
class PMA_Svg_Relation_Schema extends PMA_Export_Relation_Schema
{

    private $_tables = array();
    private $_relations = array();
    private $_xMax = 0;
    private $_yMax = 0;
    private $_xMin = 100000;
    private $_yMin = 100000;
    private $_tablewidth;

    /**
     * The "PMA_Svg_Relation_Schema" constructor
     *
     * Upon instantiation This starts writing the SVG XML document
     * user will be prompted for download as .svg extension
     *
     * @see PMA_SVG
     */
    function __construct()
    {
        parent::__construct();

        global $svg;

        $this->setShowColor(isset($_REQUEST['svg_show_color']));
        $this->setShowKeys(isset($_REQUEST['svg_show_keys']));
        $this->setTableDimension(isset($_REQUEST['svg_show_table_dimension']));
        $this->setAllTablesSameWidth(isset($_REQUEST['svg_all_tables_same_width']));

        $svg = new PMA_SVG();
        $svg->setTitle(
            sprintf(
                __('Schema of the %s database - Page %s'),
                $GLOBALS['db'],
                $this->pageNumber
            )
        );
        $svg->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $svg->setFont('Arial');
        $svg->setFontSize('16px');
        $svg->startSvgDoc('1000px', '1000px');

        $alltables = $this->getTablesFromRequest();

        foreach ($alltables as $table) {
            if (! isset($this->_tables[$table])) {
                $this->_tables[$table] = new Table_Stats_Svg(
                    $table, $svg->getFont(), $svg->getFontSize(), $this->pageNumber,
                    $this->_tablewidth, $this->showKeys, $this->tableDimension,
                    $this->offline
                );
            }

            if ($this->sameWide) {
                $this->_tables[$table]->width = $this->_tablewidth;
            }
            $this->_setMinMax($this->_tables[$table]);
        }
        $seen_a_relation = false;
        foreach ($alltables as $one_table) {
            $exist_rel = PMA_getForeigners($GLOBALS['db'], $one_table, '', 'both');
            if (!$exist_rel) {
                continue;
            }

            $seen_a_relation = true;
            foreach ($exist_rel as $master_field => $rel) {
                /* put the foreign table on the schema only if selected
                * by the user
                * (do not use array_search() because we would have to
                * to do a === false and this is not PHP3 compatible)
                */
                if ($master_field != 'foreign_keys_data') {
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->_addRelation(
                            $one_table, $svg->getFont(), $svg->getFontSize(),
                            $master_field, $rel['foreign_table'],
                            $rel['foreign_field'], $this->tableDimension
                        );
                    }
                    continue;
                }

                foreach ($rel as $one_key) {
                    if (!in_array($one_key['ref_table_name'], $alltables)) {
                        continue;
                    }

                    foreach ($one_key['index_list']
                        as $index => $one_field
                    ) {
                        $this->_addRelation(
                            $one_table, $svg->getFont(),
                            $svg->getFontSize(),
                            $one_field, $one_key['ref_table_name'],
                            $one_key['ref_index_list'][$index],
                            $this->tableDimension
                        );
                    }
                }
            }
        }
        if ($seen_a_relation) {
            $this->_drawRelations();
        }

        $this->_drawTables();
        $svg->endSvgDoc();
    }

    /**
     * Output Svg Document for download
     *
     * @return void
     * @access public
     */
    function showOutput()
    {
        global $svg;
        $svg->showOutput($this->getFileName('.svg'));
    }


    /**
     * Sets X and Y minimum and maximum for a table cell
     *
     * @param string $table The table name
     *
     * @return void
     * @access private
     */
    private function _setMinMax($table)
    {
        $this->_xMax = max($this->_xMax, $table->x + $table->width);
        $this->_yMax = max($this->_yMax, $table->y + $table->height);
        $this->_xMin = min($this->_xMin, $table->x);
        $this->_yMin = min($this->_yMin, $table->y);
    }

    /**
     * Defines relation objects
     *
     * @param string  $masterTable    The master table name
     * @param string  $font           The font face
     * @param int     $fontSize       Font size
     * @param string  $masterField    The relation field in the master table
     * @param string  $foreignTable   The foreign table name
     * @param string  $foreignField   The relation field in the foreign table
     * @param boolean $tableDimension Whether to display table position or not
     *
     * @access private
     * @return void
     *
     * @see _setMinMax,Table_Stats_Svg::__construct(),
     *       Relation_Stats_Svg::__construct()
     */
    private function _addRelation(
        $masterTable,$font,$fontSize, $masterField,
        $foreignTable, $foreignField, $tableDimension
    ) {
        if (! isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new Table_Stats_Svg(
                $masterTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $tableDimension
            );
            $this->_setMinMax($this->_tables[$masterTable]);
        }
        if (! isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new Table_Stats_Svg(
                $foreignTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $tableDimension
            );
            $this->_setMinMax($this->_tables[$foreignTable]);
        }
        $this->_relations[] = new Relation_Stats_Svg(
            $this->_tables[$masterTable], $masterField,
            $this->_tables[$foreignTable], $foreignField
        );
    }

    /**
     * Draws relation arrows and lines
     * connects master table's master field to
     * foreign table's forein field
     *
     * @return void
     * @access private
     *
     * @see Relation_Stats_Svg::relationDraw()
     */
    private function _drawRelations()
    {
        foreach ($this->_relations as $relation) {
            $relation->relationDraw($this->showColor);
        }
    }

    /**
     * Draws tables
     *
     * @return void
     * @access private
     *
     * @see Table_Stats_Svg::Table_Stats_tableDraw()
     */
    private function _drawTables()
    {
        foreach ($this->_tables as $table) {
            $table->tableDraw($this->showColor);
        }
    }
}
?>
