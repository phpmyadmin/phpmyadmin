<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

require_once 'Export_Relation_Schema.class.php';

/**
 * This Class inherits the XMLwriter class and
 * helps in developing structure of MS Visio Schema Export
 *
 * @access public
 * @see http://php.net/manual/en/book.xmlwriter.php
 */
class PMA_VISIO extends XMLWriter
{
    public $title;
    public $author;
    public $font;
    public $fontSize;

    /**
     * The "PMA_VISIO" constructor
     *
     * Upon instantiation This starts writing the Visio XML .VDX document
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
    }

    /**
     * Starts Visio XML .VDX Document
     *
     * Visio XML document starts by first initializing VisioDocument tag
     * then DocumentProperties & DocumentSettings contains all the
     * attributes that needed to define the document. Order of elements
     * should be maintained while generating XML of Visio.
     *
     * @return void
     * @access public
     * @see XMLWriter::startElement(), XMLWriter::writeAttribute(),
     * _documentProperties, _documentSettings
     */
    function startVisioDoc()
    {
        $this->startElement('VisioDocument');
        $this->writeAttribute('xmlns', 'http://schemas.microsoft.com/visio/2003/core');
        $this->writeAttribute('xmlns:vx', 'http://schemas.microsoft.com/visio/2006/extension');
        $this->writeAttribute('xml:space', 'preserve');
        $this->_documentProperties();
        $this->_documentSettings();
    }

    /**
     * Set document title
     *
     * @param string $value title text
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
     * @param string $value the author
     *
     * @return void
     * @access public
     */
    function setAuthor($value)
    {
        $this->author = $value;
    }

    /**
     * Sets Visio XML .VDX Document Properties
     *
     * DocumentProperties tag contains document property elements such as
       the document's Title,Subject,Creator and templates tags
     *
     * @return void
     * @access private
     * @see XMLWriter::startElement(),XMLWriter::endElement(),XMLWriter::writeRaw()
     */
    private function _documentProperties()
    {
        $this->startElement('DocumentProperties');
        $this->writeRaw('<Title>'.$this->title.'</Title>');
        $this->writeRaw('<Subject>'.$this->title.'</Subject>');
        $this->writeRaw('<Creator>'.$this->author.'</Creator>');
        $this->writeRaw('<Company>phpMyAdmin</Company>');
        $this->writeRaw('<Template>c:\program files\microsoft office\office12\1033\DBMODL_U.VST</Template>');
        $this->endElement();
    }

    /**
     * Sets Visio XML .VDX Document Settings
     *
     * DocumentSettings  tag contains elements that specify document settings.
     *
     * @return void
     * @access private
     * @see XMLWriter::startElement(),XMLWriter::endElement()
     */
    private function _documentSettings()
    {
        $this->startElement('DocumentSettings');
        $this->endElement();
    }

    /**
     * Ends Visio XML Document
     *
     * @return void
     * @access public
     * @see XMLWriter::endElement(),XMLWriter::endDocument()
     */
    function endVisioDoc()
    {
        $this->endElement();
        $this->endDocument();
    }

    /**
     * Output Visio XML .VDX Document for download
     *
     * @param string $fileName name of the Visio XML document
     *
     * @return void
     * @access public
     * @see XMLWriter::flush()
     */
    function showOutput($fileName)
    {
         //if(ob_get_clean()){
            //ob_end_clean();
        //}
        $output = $this->flush();
        PMA_download_header($fileName . '.vdx', 'application/visio', strlen($output));
        print $output;
    }
}


/**
 * Draws tables schema
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
     * @param integer $pageNumber       Page number
     * @param integer &$same_wide_width The max. with among tables
     * @param boolean $showKeys         Whether to display keys or not
     * @param boolean $showInfo         Whether to display table position or not
     *
     * @global object    The current Visio XML document
     * @global integer   The current page number (from the
     *                     $cfg['Servers'][$i]['table_coords'] table)
     * @global array     The relations settings
     * @global string    The current db name
     *
     * @return void
     * @access private
     * @see PMA_VISIO, Table_Stats::Table_Stats_setWidth,
     *      Table_Stats::Table_Stats_setHeight
     */
    function __construct($tableName, $pageNumber, &$same_wide_width, $showKeys = false, $showInfo = false)
    {
        global $visio, $cfgRelation, $db;

        $this->_tableName = $tableName;
        $sql = 'DESCRIBE ' . PMA_backquote($tableName);
        $result = PMA_DBI_try_query($sql, null, PMA_DBI_QUERY_STORE);
        if (!$result || !PMA_DBI_num_rows($result)) {
            $visio->dieSchema(
                $pageNumber,
                "VISIO",
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
         . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
         . PMA_backquote($cfgRelation['table_coords'])
         . ' WHERE db_name = \'' . PMA_sqlAddSlashes($db) . '\''
         . ' AND   table_name = \'' . PMA_sqlAddSlashes($tableName) . '\''
         . ' AND   pdf_page_number = ' . $pageNumber;
        $result = PMA_query_as_controluser($sql, false, PMA_DBI_QUERY_STORE);

        if (!$result || !PMA_DBI_num_rows($result)) {
            $visio->dieSchema(
                $pageNumber,
                "VISIO",
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
        $result = PMA_DBI_query('SHOW INDEX FROM ' . PMA_backquote($tableName) . ';', null, PMA_DBI_QUERY_STORE);
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
     * @return the title
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
     * @param string  $font     font name
     * @param integer $fontSize font size
     *
     * @global object The current Visio XML document
     *
     * @return void
     * @see PMA_VISIO
     */
    private function _setWidthTable($font,$fontSize)
    {
        global $visio;
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
     * @global object The current Visio XML document
     *
     * @return void
     * @access public
     * @see PMA_VISIO
     */
    public function tableDraw($showColor)
    {
        global $visio;
        //echo $this->_tableName.'<br />';

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
            // code here for drawing table diagrams
        }
    }
}

/**
 * Draws relation links
 *
 * @access public
 * @see PMA_VISIO
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
     * @global object The current Visio XML document
     *
     * @return void
     * @access public
     * @see PMA_VISIO
     */
    public function relationDraw($changeColor)
    {
        global $visio;

        if ($changeColor) {
            $listOfColors = array(
                'red',
                'grey',
                'black',
                'yellow',
                'green',
                'cyan',
                'orange'
            );
            shuffle($listOfColors);
            $color =  $listOfColors[0];
        } else {
            $color = 'black';
        }

        // code here for making connections b/w relation objects
    }
}
/*
* end of the "Relation_Stats" class
*/

/**
 * Visio Relation Schema Class
 *
 * Purpose of this class is to generate the Visio XML .VDX Document which is used
 * for representing the database diagrams in any version of MS Visio IDE.
 * This class uses Software and Database Template and Database model diagram of
 * Visio and with the combination of these objects actually helps in preparing
 * Visio XML .VDX document.
 *
 * Visio XML is generated by using XMLWriter php extension and this class
 * inherits Export_Relation_Schema class has common functionality added
 * to this class
 *
 * @name Visio_Relation_Schema
 */
class PMA_Visio_Relation_Schema extends PMA_Export_Relation_Schema
{
    /**
     * The "PMA_Visio_Relation_Schema" constructor
     *
     * Upon instantiation This outputs the Visio XML document
     * that user can download
     *
     * @return void
     * @see PMA_VISIO,Table_Stats,Relation_Stats
     */
    function __construct()
    {
        global $visio,$db;

        $this->setPageNumber($_POST['pdf_page_number']);
        $this->setShowGrid(isset($_POST['show_grid']));
        $this->setShowColor($_POST['show_color']);
        $this->setShowKeys(isset($_POST['show_keys']));
        $this->setOrientation(isset($_POST['orientation']));
        $this->setPaper($_POST['paper']);
        $this->setExportType($_POST['export_type']);

        $visio = new PMA_VISIO();
        $visio->setTitle(sprintf(__('Schema of the %s database - Page %s'), $db, $this->pageNumber));
        $visio->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $visio->startVisioDoc();
        $alltables = $this->getAllTables($db, $this->pageNumber);

        foreach ($alltables as $table) {
            if (! isset($this->tables[$table])) {
                $this->tables[$table] = new Table_Stats($table, $this->pageNumber, $this->showKeys);
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
                            $one_table,
                            $master_field,
                            $rel['foreign_table'],
                            $rel['foreign_field'],
                            $this->showKeys
                        );
                    }
                }
            }
        }
        $this->_drawTables($this->showColor);

        if ($seen_a_relation) {
            $this->_drawRelations($this->showColor);
        }
        $visio->endVisioDoc();
        $visio->showOutput($db.'-'.$this->pageNumber);
        exit();
    }

    /**
     * Defines relation objects
     *
     * @param string  $masterTable  The master table name
     * @param string  $masterField  The relation field in the master table
     * @param string  $foreignTable The foreign table name
     * @param string  $foreignField The relation field in the foreign table
     * @param boolean $showKeys     Whether to display keys or not
     *
     * @return void
     * @access private
     * @see Table_Stats::__construct(), Relation_Stats::__construct()
     */
    private function _addRelation($masterTable, $masterField, $foreignTable, $foreignField, $showKeys)
    {
        if (! isset($this->tables[$masterTable])) {
            $this->tables[$masterTable] = new Table_Stats(
                $masterTable, $this->pageNumber, $showKeys
            );
        }
        if (! isset($this->tables[$foreignTable])) {
            $this->tables[$foreignTable] = new Table_Stats(
                $foreignTable, $this->pageNumber, $showKeys
            );
        }
        $this->_relations[] = new Relation_Stats(
            $this->tables[$masterTable], $masterField,
            $this->tables[$foreignTable], $foreignField
        );
    }

    /**
     * Draws relation references
     * connects master table's master field to foreign table's forein field.
     *
     * @param boolean $changeColor Whether to use one color per relation or not
     *
     * @return void
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
     * @param boolean $changeColor Whether to show color for tables text or not
     *
     * @return void
     * @access private
     * @see Table_Stats::tableDraw()
     */
    private function _drawTables($changeColor)
    {
        foreach ($this->tables as $table) {
            $table->tableDraw($changeColor);
        }
    }
}
?>
