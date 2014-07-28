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

require_once 'Export_Relation_Schema.class.php';
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
        PMA_downloadHeader($fileName . '.svg', 'image/svg+xml', strlen($output));
        print $output;
    }

    /**
     * Draws Svg elements
     *
     * SVG has some predefined shape elements like rectangle & text
     * and other elements who have x,y co-ordinates are drawn.
     * specify their width and height and can give styles too.
     *
     * @param string  $name   Svg element name
     * @param integer $x      The x attr defines the left position of the element
     * (e.g. x="0" places the element 0 pixels from the left of the browser window)
     * @param integer $y      The y attribute defines the top position of the element
     * (e.g. y="0" places the element 0 pixels from the top of the browser window)
     * @param integer $width  The width attribute defines the width the element
     * @param integer $height The height attribute defines the height the element
     * @param string  $text   The text attribute defines the text the element
     * @param string  $styles The style attribute defines the style the element
     *  styles can be defined like CSS styles
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

require_once './libraries/schema/TableStats.class.php';

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in SVG XML document.
 *
 * @package PhpMyAdmin
 * @name    Table_Stats_Svg
 * @see     PMA_SVG
 */
class Table_Stats_Svg extends TableStats
{
    /**
     * Defines properties
     */
    public $height;
    public $currentCell = 0;

    /**
     * The "Table_Stats_Svg" constructor
     *
     * @param string  $tableName        The table name
     * @param string  $font             Font face
     * @param integer $fontSize         The font size
     * @param integer $pageNumber       Page number
     * @param integer &$same_wide_width The max. with among tables
     * @param boolean $showKeys         Whether to display keys or not
     * @param boolean $showInfo         Whether to display table position or not
     *
     * @global object  $svg         The current SVG image document
     * @global integer              The current page number (from the
     *                              $cfg['Servers'][$i]['table_coords'] table)
     * @global array   $cfgRelation The relations settings
     * @global string  $db          The current db name
     *
     * @access private
     *
     * @see PMA_SVG, Table_Stats_Svg::Table_Stats_setWidth,
     *       Table_Stats_Svg::Table_Stats_setHeight
     */
    function __construct(
        $tableName, $font, $fontSize, $pageNumber,
        &$same_wide_width, $showKeys = false, $showInfo = false
    ) {
        global $svg, $cfgRelation, $db;
        parent::__construct(
            $svg, $db, $pageNumber, $tableName, $showKeys, $showInfo
        );

        // height and width
        $this->_setHeightTable($fontSize);
        // setWidth must me after setHeight, because title
        // can include table height which changes table width
        $this->_setWidthTable($font, $fontSize);
        if ($same_wide_width < $this->width) {
            $same_wide_width = $this->width;
        }
    }

    /**
     * Displays an error when the table cannot be found.
     *
     * @return void
     */
    protected function showMissingTableError()
    {
        $this->diagram->dieSchema(
            $this->pageNumber,
            "SVG",
            sprintf(__('The %s table doesn\'t exist!'), $this->tableName)
        );
    }

    /**
     * Displays an error on missing coordinates
     *
     * @return void
     */
    protected function showMissingCoordinatesError()
    {
        $this->diagram->dieSchema(
            $this->pageNumber,
            "SVG",
            sprintf(
                __('Please configure the coordinates for table %s'),
                $this->tableName
            )
        );
    }

    /**
     * Sets the width of the table
     *
     * @param string  $font     The font size
     * @param integer $fontSize The font size
     *
     * @global object $svg The current SVG image document
     *
     * @return void
     * @access private
     *
     * @see PMA_SVG
     */
    private function _setWidthTable($font,$fontSize)
    {
        foreach ($this->fields as $field) {
            $this->width = max(
                $this->width,
                PMA_Font::getStringWidth($field, $font, $fontSize)
            );
        }
        $this->width += PMA_Font::getStringWidth('  ', $font, $fontSize);

        /*
         * it is unknown what value must be added, because
         * table title is affected by the tabe width value
         */
        while ($this->width
            < PMA_Font::getStringWidth($this->getTitle(), $font, $fontSize)
        ) {
            $this->width += 7;
        }
    }

    /**
     * Sets the height of the table
     *
     * @param integer $fontSize font size
     *
     * @return void
     * @access private
     */
    function _setHeightTable($fontSize)
    {
        $this->heightCell = $fontSize + 4;
        $this->height = (count($this->fields) + 1) * $this->heightCell;
    }

    /**
     * draw the table
     *
     * @param boolean $showColor Whether to display color
     *
     * @global object $svg The current SVG image document
     *
     * @access public
     * @return void
     *
     * @see PMA_SVG,PMA_SVG::printElement
     */
    public function tableDraw($showColor)
    {
        global $svg;
        //echo $this->tableName.'<br />';
        $svg->printElement(
            'rect', $this->x, $this->y, $this->width,
            $this->heightCell, null, 'fill:red;stroke:black;'
        );
        $svg->printElement(
            'text', $this->x + 5, $this->y+ 14, $this->width, $this->heightCell,
            $this->getTitle(), 'fill:none;stroke:black;'
        );
        foreach ($this->fields as $field) {
            $this->currentCell += $this->heightCell;
            $showColor    = 'none';
            if ($showColor) {
                if (in_array($field, $this->primary)) {
                    $showColor = '#0c0';
                }
                if ($field == $this->displayfield) {
                    $showColor = 'none';
                }
            }
            $svg->printElement(
                'rect', $this->x, $this->y + $this->currentCell, $this->width,
                $this->heightCell, null, 'fill:' . $showColor . ';stroke:black;'
            );
            $svg->printElement(
                'text', $this->x + 5, $this->y + 14 + $this->currentCell,
                $this->width, $this->heightCell, $field, 'fill:none;stroke:black;'
            );
        }
    }
}


/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in SVG XML document.
 *
 * @package PhpMyAdmin
 * @name    Relation_Stats_Svg
 * @see     PMA_SVG::printElementLine
 */
class Relation_Stats_Svg
{
    /**
     * Defines properties
     */
    public $xSrc, $ySrc;
    public $srcDir ;
    public $destDir;
    public $xDest, $yDest;
    public $wTick = 10;

    /**
     * The "Relation_Stats_Svg" constructor
     *
     * @param string $master_table  The master table name
     * @param string $master_field  The relation field in the master table
     * @param string $foreign_table The foreign table name
     * @param string $foreign_field The relation field in the foreign table
     *
     * @see Relation_Stats_Svg::_getXy
     */
    function __construct($master_table, $master_field, $foreign_table,
        $foreign_field
    ) {
        $src_pos  = $this->_getXy($master_table, $master_field);
        $dest_pos = $this->_getXy($foreign_table, $foreign_field);
        /*
        * [0] is x-left
        * [1] is x-right
        * [2] is y
        */
        $src_left   = $src_pos[0] - $this->wTick;
        $src_right  = $src_pos[1] + $this->wTick;
        $dest_left  = $dest_pos[0] - $this->wTick;
        $dest_right = $dest_pos[1] + $this->wTick;

        $d1 = abs($src_left - $dest_left);
        $d2 = abs($src_right - $dest_left);
        $d3 = abs($src_left - $dest_right);
        $d4 = abs($src_right - $dest_right);
        $d  = min($d1, $d2, $d3, $d4);

        if ($d == $d1) {
            $this->xSrc    = $src_pos[0];
            $this->srcDir  = -1;
            $this->xDest   = $dest_pos[0];
            $this->destDir = -1;
        } elseif ($d == $d2) {
            $this->xSrc    = $src_pos[1];
            $this->srcDir  = 1;
            $this->xDest   = $dest_pos[0];
            $this->destDir = -1;
        } elseif ($d == $d3) {
            $this->xSrc    = $src_pos[0];
            $this->srcDir  = -1;
            $this->xDest   = $dest_pos[1];
            $this->destDir = 1;
        } else {
            $this->xSrc    = $src_pos[1];
            $this->srcDir  = 1;
            $this->xDest   = $dest_pos[1];
            $this->destDir = 1;
        }
        $this->ySrc   = $src_pos[2];
        $this->yDest = $dest_pos[2];
    }

    /**
     * Gets arrows coordinates
     *
     * @param string $table  The current table name
     * @param string $column The relation column name
     *
     * @return array Arrows coordinates
     * @access private
     */
    function _getXy($table, $column)
    {
        $pos = array_search($column, $table->fields);
        // x_left, x_right, y
        return array(
            $table->x,
            $table->x + $table->width,
            $table->y + ($pos + 1.5) * $table->heightCell
        );
    }

    /**
     * draws relation links and arrows shows foreign key relations
     *
     * @param boolean $changeColor Whether to use one color per relation or not
     *
     * @global object $svg The current SVG image document
     *
     * @return void
     * @access public
     *
     * @see PMA_SVG
     */
    public function relationDraw($changeColor)
    {
        global $svg;

        if ($changeColor) {
            $listOfColors = array(
                'red',
                'grey',
                'black',
                'yellow',
                'green',
                'cyan',
            '    orange'
            );
            shuffle($listOfColors);
            $color =  $listOfColors[0];
        } else {
            $color = 'black';
        }

        $svg->printElementLine(
            'line', $this->xSrc, $this->ySrc,
            $this->xSrc + $this->srcDir * $this->wTick, $this->ySrc,
            'fill:' . $color . ';stroke:black;stroke-width:2;'
        );
        $svg->printElementLine(
            'line', $this->xDest + $this->destDir * $this->wTick,
            $this->yDest, $this->xDest, $this->yDest,
            'fill:' . $color . ';stroke:black;stroke-width:2;'
        );
        $svg->printElementLine(
            'line', $this->xSrc + $this->srcDir * $this->wTick, $this->ySrc,
            $this->xDest + $this->destDir * $this->wTick, $this->yDest,
            'fill:' . $color . ';stroke:' . $color . ';stroke-width:1;'
        );
        $root2 = 2 * sqrt(2);
        $svg->printElementLine(
            'line', $this->xSrc + $this->srcDir * $this->wTick * 0.75, $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc + $this->wTick / $root2,
            'fill:' . $color . ';stroke:black;stroke-width:2;'
        );
        $svg->printElementLine(
            'line', $this->xSrc + $this->srcDir * $this->wTick * 0.75, $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc - $this->wTick / $root2,
            'fill:' . $color . ';stroke:black;stroke-width:2;'
        );
        $svg->printElementLine(
            'line', $this->xDest + $this->destDir * $this->wTick / 2, $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest + $this->wTick / $root2,
            'fill:' . $color . ';stroke:black;stroke-width:2;'
        );
        $svg->printElementLine(
            'line', $this->xDest + $this->destDir * $this->wTick / 2, $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest - $this->wTick / $root2,
            'fill:' . $color . ';stroke:black;stroke-width:2;'
        );
    }
}
/*
* end of the "Relation_Stats_Svg" class
*/

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
        global $svg,$db;

        $this->setPageNumber($_POST['pdf_page_number']);
        $this->setShowColor(isset($_POST['show_color']));
        $this->setShowKeys(isset($_POST['show_keys']));
        $this->setTableDimension(isset($_POST['show_table_dimension']));
        $this->setAllTablesSameWidth(isset($_POST['all_tables_same_width']));
        $this->setExportType($_POST['export_type']);

        $svg = new PMA_SVG();
        $svg->setTitle(
            sprintf(
                __('Schema of the %s database - Page %s'),
                $db,
                $this->pageNumber
            )
        );
        $svg->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $svg->setFont('Arial');
        $svg->setFontSize('16px');
        $svg->startSvgDoc('1000px', '1000px');
        $alltables = $this->getAllTables($db, $this->pageNumber);

        foreach ($alltables as $table) {
            if (! isset($this->_tables[$table])) {
                $this->_tables[$table] = new Table_Stats_Svg(
                    $table, $svg->getFont(), $svg->getFontSize(), $this->pageNumber,
                    $this->_tablewidth, $this->showKeys, $this->tableDimension
                );
            }

            if ($this->sameWide) {
                $this->_tables[$table]->width = $this->_tablewidth;
            }
            $this->_setMinMax($this->_tables[$table]);
        }
        $seen_a_relation = false;
        foreach ($alltables as $one_table) {
            $exist_rel = PMA_getForeigners($db, $one_table, '', 'both');
            if ($exist_rel) {
                $seen_a_relation = true;
                foreach ($exist_rel as $master_field => $rel) {
                    /* put the foreign table on the schema only if selected
                    * by the user
                    * (do not use array_search() because we would have to
                    * to do a === false and this is not PHP3 compatible)
                    */
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->_addRelation(
                            $one_table, $svg->getFont(), $svg->getFontSize(),
                            $master_field, $rel['foreign_table'],
                            $rel['foreign_field'], $this->tableDimension
                        );
                    }
                }
            }
        }
        if ($seen_a_relation) {
            $this->_drawRelations($this->showColor);
        }

        $this->_drawTables($this->showColor);
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
        global $svg,$db;
        $svg->showOutput($db . '-' . $this->pageNumber);
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
     * @param string  $masterTable  The master table name
     * @param string  $font         The font face
     * @param int     $fontSize     Font size
     * @param string  $masterField  The relation field in the master table
     * @param string  $foreignTable The foreign table name
     * @param string  $foreignField The relation field in the foreign table
     * @param boolean $showInfo     Whether to display table position or not
     *
     * @access private
     * @return void
     *
     * @see _setMinMax,Table_Stats_Svg::__construct(),
     *       Relation_Stats_Svg::__construct()
     */
    private function _addRelation(
        $masterTable,$font,$fontSize, $masterField,
        $foreignTable, $foreignField, $showInfo
    ) {
        if (! isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new Table_Stats_Svg(
                $masterTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $showInfo
            );
            $this->_setMinMax($this->_tables[$masterTable]);
        }
        if (! isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new Table_Stats_Svg(
                $foreignTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $showInfo
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
     * @param boolean $changeColor Whether to use one color per relation or not
     *
     * @return void
     * @access private
     *
     * @see Relation_Stats_Svg::relationDraw()
     */
    private function _drawRelations($changeColor)
    {
        foreach ($this->_relations as $relation) {
            $relation->relationDraw($changeColor);
        }
    }

    /**
     * Draws tables
     *
     * @param boolean $changeColor Whether to show color for primary fields or not
     *
     * @return void
     * @access private
     *
     * @see Table_Stats_Svg::Table_Stats_tableDraw()
     */
    private function _drawTables($changeColor)
    {
        foreach ($this->_tables as $table) {
            $table->tableDraw($changeColor);
        }
    }
}
?>
