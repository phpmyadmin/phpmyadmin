<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Classes to create relation schema in EPS format.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/plugins/schema/Export_Relation_Schema.class.php';
require_once 'libraries/plugins/schema/eps/RelationStatsEps.class.php';
require_once 'libraries/plugins/schema/eps/TableStatsEps.class.php';
require_once 'libraries/Font.class.php';

/**
 * This Class is EPS Library and
 * helps in developing structure of EPS Schema Export
 *
 * @package PhpMyAdmin
 * @access  public
 * @see     http://php.net/manual/en/book.xmlwriter.php
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
     * @param string $orientation sets the orientation
     *
     * @return void
     *
     * @access public
     */
    function setOrientation($orientation)
    {
        $this->stringCommands .= "%%PageOrder: Ascend \n";
        if ($orientation == "L") {
            $orientation = "Landscape";
            $this->stringCommands .= '%%Orientation: ' . $orientation . "\n";
        } else {
            $orientation = "Portrait";
            $this->stringCommands .= '%%Orientation: ' . $orientation . "\n";
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
        $this->stringCommands .= ""
            . $size . " scalefont            % Scale the font to $size points\n";
        $this->stringCommands
            .= "setfont                 % Make it the current font\n";
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
        PMA_downloadHeader(
            $fileName,
            'image/x-eps',
            /*overload*/mb_strlen($output)
        );
        print $output;
    }
}

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
 * @package PhpMyAdmin
 * @name    Eps_Relation_Schema
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
     * @see PMA_EPS
     */
    function __construct()
    {
        parent::__construct();

        global $eps;

        $this->setShowColor(isset($_REQUEST['eps_show_color']));
        $this->setShowKeys(isset($_REQUEST['eps_show_keys']));
        $this->setTableDimension(isset($_REQUEST['eps_show_table_dimension']));
        $this->setAllTablesSameWidth(isset($_REQUEST['eps_all_tables_same_width']));
        $this->setOrientation($_REQUEST['eps_orientation']);

        $eps = new PMA_EPS();
        $eps->setTitle(
            sprintf(
                __('Schema of the %s database - Page %s'),
                $GLOBALS['db'],
                $this->pageNumber
            )
        );
        $eps->setAuthor('phpMyAdmin ' . PMA_VERSION);
        $eps->setDate(date("j F Y, g:i a"));
        $eps->setOrientation($this->orientation);
        $eps->setFont('Verdana', '10');

        $alltables = $this->getTablesFromRequest();

        foreach ($alltables as $table) {
            if (! isset($this->_tables[$table])) {
                $this->_tables[$table] = new Table_Stats_Eps(
                    $table, $eps->getFont(), $eps->getFontSize(), $this->pageNumber,
                    $this->_tablewidth, $this->showKeys,
                    $this->tableDimension, $this->offline
                );
            }

            if ($this->sameWide) {
                $this->_tables[$table]->width = $this->_tablewidth;
            }
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
                            $one_table, $eps->getFont(), $eps->getFontSize(),
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
                            $one_table, $eps->getFont(),
                            $eps->getFontSize(),
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
        $eps->endEpsDoc();
    }

    /**
     * Output Eps Document for download
     *
     * @return void
     * @access public
     */
    function showOutput()
    {
        global $eps;
        $eps->showOutput($this->getFileName('.eps'));
    }

    /**
     * Defines relation objects
     *
     * @param string  $masterTable    The master table name
     * @param string  $font           The font
     * @param int     $fontSize       The font size
     * @param string  $masterField    The relation field in the master table
     * @param string  $foreignTable   The foreign table name
     * @param string  $foreignField   The relation field in the foreign table
     * @param boolean $tableDimension Whether to display table position or not
     *
     * @return void
     *
     * @access private
     * @see _setMinMax,Table_Stats_Eps::__construct(),
     * Relation_Stats_Eps::__construct()
     */
    private function _addRelation(
        $masterTable, $font, $fontSize, $masterField,
        $foreignTable, $foreignField, $tableDimension
    ) {
        if (! isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new Table_Stats_Eps(
                $masterTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $tableDimension
            );
        }
        if (! isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new Table_Stats_Eps(
                $foreignTable, $font, $fontSize, $this->pageNumber,
                $this->_tablewidth, false, $tableDimension
            );
        }
        $this->_relations[] = new Relation_Stats_Eps(
            $this->_tables[$masterTable], $masterField,
            $this->_tables[$foreignTable], $foreignField
        );
    }

    /**
     * Draws relation arrows and lines connects master table's master field to
     * foreign table's foreign field
     *
     * @return void
     *
     * @access private
     * @see Relation_Stats_Eps::relationDraw()
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
     *
     * @access private
     * @see Table_Stats_Eps::Table_Stats_tableDraw()
     */
    private function _drawTables()
    {
        foreach ($this->_tables as $table) {
            $table->tableDraw($this->showColor);
        }
    }
}
?>
