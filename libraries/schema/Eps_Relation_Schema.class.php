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
 * This Class is EPS Library and
 * helps in developing structure of EPS Schema Export
 *
 * @access public
 * @see http://php.net/manual/en/book.xmlwriter.php
 */

class PMA_EPS
{
    public $font;
    public $fontSize;
    public $stringCommands;

    /**
     * The "PMA_EPS" constructor
     *
     * Upon instantiation This starts writing the EPS Document.
     * %!PS-Adobe-3.0 EPSF-3.0 This is the MUST first comment to include
     * it shows/tells that the Post Script document is purely under
     * Document Structuring Convention [DSC] and is Compliant
     * Encapsulated Post Script Document
     *
     * @return void
     * @access public
     */
    function __construct()
    {
        $this->stringCommands = "";
        $this->stringCommands .= "%!PS-Adobe-3.0 EPSF-3.0 \n";
    }

    /**
     * Set document title
     *
     * @param string $value sets the title text
     *
     * @return void
     *
     * @access public
     */
    function setTitle($value)
    {
        $this->stringCommands .= '%%Title: ' . $value . "\n";
    }

    /**
     * Set document author
     *
     * @param string $value sets the author
     *
     * @return void
     *
     * @access public
     */
    function setAuthor($value)
    {
        $this->stringCommands .= '%%Creator: ' . $value . "\n";
    }

    /**
     * Set document creation date
     *
     * @param string $value sets the date
     *
     * @return void
     *
     * @access public
     */
    function setDate($value)
    {
        $this->stringCommands .= '%%CreationDate: ' . $value . "\n";
    }

    /**
     * Set document orientation
     *
     * @param string $value sets the author
     *
     * @return void
     *
     * @access public
     */
    function setOrientation($value)
    {
        $this->stringCommands .= "%%PageOrder: Ascend \n";
        if ($value == "L") {
            $value = "Landscape";
            $this->stringCommands .= '%%Orientation: ' . $value . "\n";
        } else {
            $value = "Portrait";
            $this->stringCommands .= '%%Orientation: ' . $value . "\n";
        }
        $this->stringCommands .= "%%EndComments \n";
        $this->stringCommands .= "%%Pages 1 \n";
        $this->stringCommands .= "%%BoundingBox: 72 150 144 170 \n";
    }

    /**
     * Set the font and size
     *
     * font can be set whenever needed in EPS
     *
     * @param string  $value sets the font name e.g Arial
     * @param integer $size  sets the size of the font e.g 10
     *
     * @return void
     *
     * @access public
     */
    function setFont($value, $size)
    {
        $this->font = $value;
        $this->fontSize = $size;
        $this->stringCommands .= "/" . $value . " findfont   % Get the basic font\n";
        $this->stringCommands .= "" . $size . " scalefont            % Scale the font to $size points\n";
        $this->stringCommands .= "setfont                 % Make it the current font\n";
    }

    /**
     * Get the font
     *
     * @return string return the font name e.g Arial
     * @access public
     */
    function getFont()
    {
        return $this->font;
    }

    /**
     * Get the font Size
     *
     * @return string return the size of the font e.g 10
     * @access public
     */
    function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * Draw the line
     *
     * drawing the lines from x,y source to x,y destination and set the
     * width of the line. lines helps in showing relationships of tables
     *
     * @param integer $x_from    The x_from attribute defines the start
     *                           left position of the element
     * @param integer $y_from    The y_from attribute defines the start
     *                           right position of the element
     * @param integer $x_to      The x_to attribute defines the end
     *                           left position of the element
     * @param integer $y_to      The y_to attribute defines the end
     *                           right position of the element
     * @param integer $lineWidth Sets the width of the line e.g 2
     *
     * @return void
     *
     * @access public
     */
    function line($x_from = 0, $y_from = 0, $x_to = 0, $y_to = 0, $lineWidth = 0)
    {
        $this->stringCommands .= $lineWidth . " setlinewidth  \n";
        $this->stringCommands .= $x_from . ' ' . $y_from  . " moveto \n";
        $this->stringCommands .= $x_to . ' ' . $y_to  . " lineto \n";
        $this->stringCommands .= "stroke \n";
    }

    /**
     * Draw the rectangle
     *
     * drawing the rectangle from x,y source to x,y destination and set the
     * width of the line. rectangles drawn around the text shown of fields
     *
     * @param integer $x_from    The x_from attribute defines the start
                                 left position of the element
     * @param integer $y_from    The y_from attribute defines the start
                                 right position of the element
     * @param integer $x_to      The x_to attribute defines the end
                                 left position of the element
     * @param integer $y_to      The y_to attribute defines the end
                                 right position of the element
     * @param integer $lineWidth Sets the width of the line e.g 2
     *
     * @return void
     *
     * @access public
     */
    function rect($x_from, $y_from, $x_to, $y_to, $lineWidth)
    {
        $this->stringCommands .= $lineWidth . " setlinewidth  \n";
        $this->stringCommands .= "newpath \n";
        $this->stringCommands .= $x_from . " " . $y_from  . " moveto \n";
        $this->stringCommands .= "0 " . $y_to  . " rlineto \n";
        $this->stringCommands .= $x_to . " 0 rlineto \n";
        $this->stringCommands .= "0 -" . $y_to  . " rlineto \n";
        $this->stringCommands .= "closepath \n";
        $this->stringCommands .= "stroke \n";
    }

    /**
     * Set the current point
     *
     * The moveto operator takes two numbers off the stack and treats
     * them as x and y coordinates to which to move. The coordinates
     * specified become the current point.
     *
     * @param integer $x The x attribute defines the left position of the element
     * @param integer $y The y attribute defines the right position of the element
     *
     * @return void
     *
     * @access public
     */
    function moveTo($x, $y)
    {
        $this->stringCommands .= $x . ' ' . $y . " moveto \n";
    }

    /**
     * Output/Display the text
     *
     * @param string $text The string to be displayed
     *
     * @return void
     *
     * @access public
     */
    function show($text)
    {
        $this->stringCommands .=  '(' . $text  . ") show \n";
    }

    /**
     * Output the text at specified co-ordinates
     *
     * @param string  $text String to be displayed
     * @param integer $x    X attribute defines the left position of the element
     * @param integer $y    Y attribute defines the right position of the element
     *
     * @return void
     *
     * @access public
     */
    function showXY($text, $x, $y)
    {
        $this->moveTo($x, $y);
        $this->show($text);
    }

    /**
     * get width of string/text
     *
     * EPS text width is calcualted depending on font name
     * and font size. It is very important to know the width of text
     * because rectangle is drawn around it.
     *
     * This is a bit hardcore method. I didn't found any other better than this.
     * if someone found better than this. would love to hear that method
     *
     * @param string  $text     string that width will be calculated
     * @param integer $font     name of the font like Arial,sans-serif etc
     * @param integer $fontSize size of font
     *
     * @return integer width of the text
     *
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

    /**
     * Ends EPS Document
     *
     * @return void
     * @access public
     */
    function endEpsDoc()
    {
        $this->stringCommands .= "showpage \n";
    }

    /**
     * Output EPS Document for download
     *
     * @param string $fileName name of the eps document
     *
     * @return void
     *
     * @access public
     */
    function showOutput($fileName)
    {
        // if(ob_get_clean()){
            //ob_end_clean();
        //}
        $output = $this->stringCommands;
        PMA_Response::getInstance()->disable();
        PMA_downloadHeader($fileName . '.eps', 'image/x-eps', strlen($output));
        print $output;
    }
}

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in EPS.
 *
 * @name Table_Stats
 * @see PMA_EPS
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
     * @param string  $font             The font  name
     * @param integer $fontSize         The font size
     * @param integer $pageNumber       Page number
     * @param integer &$same_wide_width The max width among tables
     * @param boolean $showKeys         Whether to display keys or not
     * @param boolean $showInfo         Whether to display table position or not
     *
     * @global object    The current eps document
     * @global integer   The current page number (from the
     *                     $cfg['Servers'][$i]['table_coords'] table)
     * @global array     The relations settings
     * @global string    The current db name
     *
     * @access private
     * @see PMA_EPS, Table_Stats::Table_Stats_setWidth,
     *      Table_Stats::Table_Stats_setHeight
     */
    function __construct(
        $tableName, $font, $fontSize, $pageNumber, &$same_wide_width,
        $showKeys = false, $showInfo = false
    ) {
        global $eps, $cfgRelation, $db;

        $this->_tableName = $tableName;
        $sql = 'DESCRIBE ' . PMA_Util::backquote($tableName);
        $result = PMA_DBI_try_query($sql, null, PMA_DBI_QUERY_STORE);
        if (! $result || ! PMA_DBI_num_rows($result)) {
            $eps->dieSchema(
                $pageNumber, "EPS",
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

        if (! $result || ! PMA_DBI_num_rows($result)) {
            $eps->dieSchema(
                $pageNumber, "EPS",
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
            null, PMA_DBI_QUERY_STORE
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
     * @return string The relation/table name
     * @access private
     */
    private function _getTitle()
    {
        return ($this->_showInfo
            ? sprintf('%.0f', $this->width) . 'x' . sprintf('%.0f', $this->heightCell)
            : '') . ' ' . $this->_tableName;
    }

    /**
     * Sets the width of the table
     *
     * @param string  $font     The font name
     * @param integer $fontSize The font size
     *
     * @global object    The current eps document
     *
     * @return void
     *
     * @access private
     * @see PMA_EPS
     */
    private function _setWidthTable($font,$fontSize)
    {
        global $eps;

        foreach ($this->fields as $field) {
            $this->width = max(
                $this->width,
                $eps->getStringWidth($field, $font, $fontSize)
            );
        }
        $this->width += $eps->getStringWidth('      ', $font, $fontSize);
        /*
         * it is unknown what value must be added, because
         * table title is affected by the tabe width value
         */
        while ($this->width < $eps->getStringWidth($this->_getTitle(), $font, $fontSize)) {
            $this->width += 7;
        }
    }

    /**
     * Sets the height of the table
     *
     * @param integer $fontSize The font size
     *
     * @return void
     * @access private
     */
    private function _setHeightTable($fontSize)
    {
        $this->heightCell = $fontSize + 4;
        $this->height = (count($this->fields) + 1) * $this->heightCell;
    }

    /**
     * Draw the table
     *
     * @param boolean $showColor Whether to display color
     *
     * @global object The current eps document
     *
     * @return void
     *
     * @access public
     * @see PMA_EPS,PMA_EPS::line,PMA_EPS::rect
     */
    public function tableDraw($showColor)
    {
        global $eps;
        //echo $this->_tableName.'<br />';
        $eps->rect($this->x, $this->y + 12, $this->width, $this->heightCell, 1);
        $eps->showXY($this->_getTitle(), $this->x + 5, $this->y + 14);
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
            $eps->rect(
                $this->x, $this->y + 12  + $this->currentCell,
                $this->width, $this->heightCell, 1
            );
            $eps->showXY($field, $this->x + 5, $this->y + 14 + $this->currentCell);
        }
    }
}

/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in EPS document.
 *
 * @name Relation_Stats
 * @see PMA_EPS
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
        $this->ySrc   = $src_pos[2] + 10;
        $this->yDest = $dest_pos[2] + 10;
    }

    /**
     * Gets arrows coordinates
     *
     * @param string $table  The current table name
     * @param string $column The relation column name
     *
     * @return array Arrows coordinates
     *
     * @access private
     */
    private function _getXy($table, $column)
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
     * draws relation links and arrows
     * shows foreign key relations
     *
     * @param boolean $changeColor Whether to use one color per relation or not
     *
     * @global object The current EPS document
     *
     * @access public
     * @see PMA_EPS
     *
     * @return void
     */
    public function relationDraw($changeColor)
    {
        global $eps;

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
        // draw a line like -- to foreign field
        $eps->line(
            $this->xSrc,
            $this->ySrc,
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            1
        );
        // draw a line like -- to master field
        $eps->line(
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            $this->xDest,
            $this->yDest,
            1
        );
        // draw a line that connects to master field line and foreign field line
        $eps->line(
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            1
        );
        $root2 = 2 * sqrt(2);
        $eps->line(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc + $this->wTick / $root2,
            1
        );
        $eps->line(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc - $this->wTick / $root2,
            1
        );
        $eps->line(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest + $this->wTick / $root2,
            1
        );
        $eps->line(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest - $this->wTick / $root2,
            1
        );
    }
}
/*
* end of the "Relation_Stats" class
*/

/**
 * EPS Relation Schema Class
 *
 * Purpose of this class is to generate the EPS Document
 * which is used for representing the database diagrams.
 * This class uses post script commands and with
 * the combination of these commands actually helps in preparing EPS Document.
 *
 * This class inherits Export_Relation_Schema class has common functionality added
 * to this class
 *
 * @name Eps_Relation_Schema
 */
class PMA_Eps_Relation_Schema extends PMA_Export_Relation_Schema
{
    private $_tables = array();
    private $_relations = array();

    /**
     * The "PMA_EPS_Relation_Schema" constructor
     *
     * Upon instantiation This starts writing the EPS document
     * user will be prompted for download as .eps extension
     *
     * @return void
     * @see PMA_EPS
     */
    function __construct()
    {
        global $eps,$db;

        $this->setPageNumber($_POST['pdf_page_number']);
        $this->setShowColor(isset($_POST['show_color']));
        $this->setShowKeys(isset($_POST['show_keys']));
        $this->setTableDimension(isset($_POST['show_table_dimension']));
        $this->setAllTablesSameWidth(isset($_POST['all_tables_same_width']));
        $this->setOrientation($_POST['orientation']);
        $this->setExportType($_POST['export_type']);

        $eps = new PMA_EPS();
        $eps->setTitle(
            sprintf(
                __('Schema of the %s database - Page %s'),
                $db,
                $this->pageNumber
            )
        );
        $eps->setAuthor('phpMyAdmin ' . PMA_VERSION);
        $eps->setDate(date("j F Y, g:i a"));
        $eps->setOrientation($this->orientation);
        $eps->setFont('Verdana', '10');

        $alltables = $this->getAllTables($db, $this->pageNumber);

        foreach ($alltables AS $table) {
            if (! isset($this->_tables[$table])) {
                $this->_tables[$table] = new Table_Stats(
                    $table, $eps->getFont(), $eps->getFontSize(), $this->pageNumber,
                    $this->_tablewidth, $this->showKeys, $this->tableDimension
                );
            }

            if ($this->sameWide) {
                $this->_tables[$table]->width = $this->_tablewidth;
            }
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
                            $one_table, $eps->getFont(), $eps->getFontSize(),
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
        $eps->endEpsDoc();
        $eps->showOutput($db.'-'.$this->pageNumber);
        exit();
    }

    /**
     * Defines relation objects
     *
     * @param string  $masterTable  The master table name
     * @param string  $font         The font
     * @param int     $fontSize     The font size
     * @param string  $masterField  The relation field in the master table
     * @param string  $foreignTable The foreign table name
     * @param string  $foreignField The relation field in the foreign table
     * @param boolean $showInfo     Whether to display table position or not
     *
     * @return void
     *
     * @access private
     * @see _setMinMax,Table_Stats::__construct(),Relation_Stats::__construct()
     */
    private function _addRelation(
        $masterTable, $font, $fontSize, $masterField,
        $foreignTable, $foreignField, $showInfo
    ) {
        if (! isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new Table_Stats(
                $masterTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $showInfo
            );
        }
        if (! isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new Table_Stats(
                $foreignTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $showInfo
            );
        }
        $this->_relations[] = new Relation_Stats(
            $this->_tables[$masterTable], $masterField,
            $this->_tables[$foreignTable], $foreignField
        );
    }

    /**
     * Draws relation arrows and lines connects master table's master field to
     * foreign table's forein field
     *
     * @param boolean $changeColor Whether to use one color per relation or not
     *
     * @return void
     *
     * @access private
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
     *
     * @access private
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
