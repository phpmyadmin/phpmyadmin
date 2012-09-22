<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'Export_Relation_Schema.class.php';

/**
 * This Class inherits the XMLwriter class and
 * helps in developing structure of SVG Schema Export
 *
 * @access public
 * @see http://php.net/manual/en/book.xmlwriter.php
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
     * @return void
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
    function printElement($name, $x, $y, $width = '', $height = '', $text = '', $styles = '')
    {
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

    /**
     * get width of string/text
     *
     * Svg text element width is calcualted depending on font name
     * and font size. It is very important to know the width of text
     * because rectangle is drawn around it.
     *
     * This is a bit hardcore method. I didn't found any other than this.
     *
     * @param string  $text     string that width will be calculated
     * @param integer $font     name of the font like Arial,sans-serif etc
     * @param integer $fontSize size of font
     *
     * @return integer width of the text
     * @access public
     */
    function getStringWidth($text,$font,$fontSize)
    {
        /*
         * Start by counting the width, giving each character a modifying value
         */
        $count = 0;
        $count = $count + ((strlen($text) - strlen(str_replace(array("i", "j", "l"), "", $text))) * 0.23);//ijl
        $count = $count + ((strlen($text) - strlen(str_replace(array("f"), "", $text))) * 0.27);//f
        $count = $count + ((strlen($text) - strlen(str_replace(array("t", "I"), "", $text))) * 0.28);//tI
        $count = $count + ((strlen($text) - strlen(str_replace(array("r"), "", $text))) * 0.34);//r
        $count = $count + ((strlen($text) - strlen(str_replace(array("1"), "", $text))) * 0.49);//1
        $count = $count + ((strlen($text) - strlen(str_replace(array("c", "k", "s", "v", "x", "y", "z", "J"), "", $text))) * 0.5);//cksvxyzJ
        $count = $count + ((strlen($text) - strlen(str_replace(array("a", "b", "d", "e", "g", "h", "n", "o", "p", "q", "u", "L", "0", "2", "3", "4", "5", "6", "7", "8", "9"), "", $text))) * 0.56);//abdeghnopquL023456789
        $count = $count + ((strlen($text) - strlen(str_replace(array("F", "T", "Z"), "", $text))) * 0.61);//FTZ
        $count = $count + ((strlen($text) - strlen(str_replace(array("A", "B", "E", "K", "P", "S", "V", "X", "Y"), "", $text))) * 0.67);//ABEKPSVXY
        $count = $count + ((strlen($text) - strlen(str_replace(array("w", "C", "D", "H", "N", "R", "U"), "", $text))) * 0.73);//wCDHNRU
        $count = $count + ((strlen($text) - strlen(str_replace(array("G", "O", "Q"), "", $text))) * 0.78);//GOQ
        $count = $count + ((strlen($text) - strlen(str_replace(array("m", "M"), "", $text))) * 0.84);//mM
        $count = $count + ((strlen($text) - strlen(str_replace("W", "", $text))) * .95);//W
        $count = $count + ((strlen($text) - strlen(str_replace(" ", "", $text))) * .28);//" "
        $text  = str_replace(" ", "", $text);//remove the " "'s
        $count = $count + (strlen(preg_replace("/[a-z0-9]/i", "", $text)) * 0.3); //all other chrs

        $modifier = 1;
        $font = strtolower($font);
        switch ($font) {
        /*
         * no modifier for arial and sans-serif
         */
        case 'arial':
        case 'sans-serif':
            break;
        /*
         * .92 modifer for time, serif, brushscriptstd, and californian fb
         */
        case 'times':
        case 'serif':
        case 'brushscriptstd':
        case 'californian fb':
            $modifier = .92;
            break;
        /*
         * 1.23 modifier for broadway
         */
        case 'broadway':
            $modifier = 1.23;
            break;
        }
        $textWidth = $count*$fontSize;
        return ceil($textWidth*$modifier);
    }
}

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in SVG XML document.
 *
 * @name Table_Stats
 * @see PMA_SVG
 */
class Table_Stats
{
    /**
     * Defines properties
     */

    private $_tableName;
    private $_showInfo = false;

    public $width = 0;
    public $height;
    public $fields = array();
    public $heightCell = 0;
    public $currentCell = 0;
    public $x, $y;
    public $primary = array();

    /**
     * The "Table_Stats" constructor
     *
     * @param string  $tableName        The table name
     * @param string  $font             Font face
     * @param integer $fontSize         The font size
     * @param integer $pageNumber       Page number
     * @param integer &$same_wide_width The max. with among tables
     * @param boolean $showKeys         Whether to display keys or not
     * @param boolean $showInfo         Whether to display table position or not
     *
     * @global object    The current SVG image document
     * @global integer   The current page number (from the
     *                   $cfg['Servers'][$i]['table_coords'] table)
     * @global array     The relations settings
     * @global string    The current db name
     *
     * @access private
     *
     * @see PMA_SVG, Table_Stats::Table_Stats_setWidth,
     *       Table_Stats::Table_Stats_setHeight
     */
    function __construct(
        $tableName, $font, $fontSize, $pageNumber,
        &$same_wide_width, $showKeys = false, $showInfo = false
    ) {
        global $svg, $cfgRelation, $db;

        $this->_tableName = $tableName;
        $sql = 'DESCRIBE ' . PMA_Util::backquote($tableName);
        $result = PMA_DBI_try_query($sql, null, PMA_DBI_QUERY_STORE);
        if (! $result || ! PMA_DBI_num_rows($result)) {
            $svg->dieSchema(
                $pageNumber,
                "SVG",
                sprintf(__('The %s table doesn\'t exist!'), $tableName)
            );
        }

        /*
        * load fields
        * check to see if it will load all fields or only the foreign keys
        */

        if ($showKeys) {
            $indexes = PMA_Index::getFromTable($this->_tableName, $db);
            $all_columns = array();
            foreach ($indexes as $index) {
                $all_columns = array_merge(
                    $all_columns,
                    array_flip(array_keys($index->getColumns()))
                );
            }
            $this->fields = array_keys($all_columns);
        } else {
            while ($row = PMA_DBI_fetch_row($result)) {
                $this->fields[] = $row[0];
            }
        }

        $this->_showInfo = $showInfo;

        // height and width
        $this->_setHeightTable($fontSize);

        // setWidth must me after setHeight, because title
        // can include table height which changes table width
        $this->_setWidthTable($font, $fontSize);
        if ($same_wide_width < $this->width) {
            $same_wide_width = $this->width;
        }

        // x and y
        $sql = 'SELECT x, y FROM '
         . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
         . PMA_Util::backquote($cfgRelation['table_coords'])
         . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\''
         . ' AND   table_name = \'' . PMA_Util::sqlAddSlashes($tableName) . '\''
         . ' AND   pdf_page_number = ' . $pageNumber;
        $result = PMA_queryAsControlUser($sql, false, PMA_DBI_QUERY_STORE);

        if (!$result || !PMA_DBI_num_rows($result)) {
            $svg->dieSchema(
                $pageNumber,
                "SVG",
                sprintf(
                    __('Please configure the coordinates for table %s'),
                    $tableName
                )
            );
        }
        list($this->x, $this->y) = PMA_DBI_fetch_row($result);
        $this->x = (double) $this->x;
        $this->y = (double) $this->y;
        // displayfield
        $this->displayfield = PMA_getDisplayField($db, $tableName);
        // index
        $result = PMA_DBI_query(
            'SHOW INDEX FROM ' . PMA_Util::backquote($tableName) . ';',
            null,
            PMA_DBI_QUERY_STORE
        );
        if (PMA_DBI_num_rows($result) > 0) {
            while ($row = PMA_DBI_fetch_assoc($result)) {
                if ($row['Key_name'] == 'PRIMARY') {
                    $this->primary[] = $row['Column_name'];
                }
            }
        }
    }

    /**
     * Returns title of the current table,
     * title can have the dimensions/co-ordinates of the table
     *
     * @access private
     */
    private function _getTitle()
    {
        return ($this->_showInfo
            ? sprintf('%.0f', $this->width) . 'x' . sprintf('%.0f', $this->heightCell)
            : ''
        ) . ' ' . $this->_tableName;
    }

    /**
     * Sets the width of the table
     *
     * @param string  $font     The font size
     * @param integer $fontSize The font size
     *
     * @global object    The current SVG image document
     *
     * @return void
     * @access private
     *
     * @see PMA_SVG
     */
    private function _setWidthTable($font,$fontSize)
    {
        global $svg;

        foreach ($this->fields as $field) {
            $this->width = max(
                $this->width,
                $svg->getStringWidth($field, $font, $fontSize)
            );
        }
        $this->width += $svg->getStringWidth('  ', $font, $fontSize);

        /*
         * it is unknown what value must be added, because
         * table title is affected by the tabe width value
         */
        while ($this->width < $svg->getStringWidth($this->_getTitle(), $font, $fontSize)) {
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
     * @global object The current SVG image document
     *
     * @access public
     * @return void
     *
     * @see PMA_SVG,PMA_SVG::printElement
     */
    public function tableDraw($showColor)
    {
        global $svg;
        //echo $this->_tableName.'<br />';
        $svg->printElement(
            'rect', $this->x, $this->y, $this->width,
            $this->heightCell, null, 'fill:red;stroke:black;'
        );
        $svg->printElement(
            'text', $this->x + 5, $this->y+ 14, $this->width, $this->heightCell,
            $this->_getTitle(), 'fill:none;stroke:black;'
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
                $this->heightCell, null, 'fill:'.$showColor.';stroke:black;'
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
 * @name Relation_Stats
 * @see PMA_SVG::printElementLine
 */
class Relation_Stats
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
     * The "Relation_Stats" constructor
     *
     * @param string $master_table  The master table name
     * @param string $master_field  The relation field in the master table
     * @param string $foreign_table The foreign table name
     * @param string $foreign_field The relation field in the foreign table
     *
     * @return void
     *
     * @see Relation_Stats::_getXy
     */
    function __construct($master_table, $master_field, $foreign_table, $foreign_field)
    {
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
     * @global object The current SVG image document
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
* end of the "Relation_Stats" class
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
     * @return void
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

        foreach ($alltables AS $table) {
            if (! isset($this->_tables[$table])) {
                $this->_tables[$table] = new Table_Stats(
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
        $svg->showOutput($db.'-'.$this->pageNumber);
        exit();
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
     * @see _setMinMax,Table_Stats::__construct(),Relation_Stats::__construct()
     */
    private function _addRelation(
        $masterTable,$font,$fontSize, $masterField,
        $foreignTable, $foreignField, $showInfo
    ) {
        if (! isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new Table_Stats(
                $masterTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $showInfo
            );
            $this->_setMinMax($this->_tables[$masterTable]);
        }
        if (! isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new Table_Stats(
                $foreignTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $showInfo
            );
            $this->_setMinMax($this->_tables[$foreignTable]);
        }
        $this->_relations[] = new Relation_Stats(
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
     * @see Relation_Stats::relationDraw()
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
     * @see Table_Stats::Table_Stats_tableDraw()
     */
    private function _drawTables($changeColor)
    {
        foreach ($this->_tables as $table) {
            $table->tableDraw($changeColor);
        }
    }
}
?>
