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
 * helps in developing structure of DIA Schema Export
 *
 * @access public
 * @see http://php.net/manual/en/book.xmlwriter.php
 */
class PMA_DIA extends XMLWriter
{
    public $title;
    public $author;
    public $font;
    public $fontSize;

    /**
     * The "PMA_DIA" constructor
     *
     * Upon instantiation This starts writing the Dia XML document
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
     * Starts Dia Document
     *
     * dia document starts by first initializing dia:diagram tag
     * then dia:diagramdata contains all the attributes that needed
     * to define the document, then finally a Layer starts which
     * holds all the objects.
     *
     * @param string $paper        the size of the paper/document
     * @param float  $topMargin    top margin of the paper/document in cm
     * @param float  $bottomMargin bottom margin of the paper/document in cm
     * @param float  $leftMargin   left margin of the paper/document in cm
     * @param float  $rightMargin  right margin of the paper/document in cm
     * @param string $portrait     document will be portrait or landscape
     *
     * @return void
     *
     * @access public
     * @see XMLWriter::startElement(),XMLWriter::writeAttribute(),
     *      XMLWriter::writeRaw()
     */
    function startDiaDoc($paper, $topMargin, $bottomMargin, $leftMargin,
        $rightMargin, $portrait
    ) {
        if ($portrait == 'P') {
            $isPortrait='true';
        } else {
            $isPortrait='false';
        }
        $this->startElement('dia:diagram');
        $this->writeAttribute('xmlns:dia', 'http://www.lysator.liu.se/~alla/dia/');
        $this->startElement('dia:diagramdata');
        $this->writeRaw(
            '<dia:attribute name="background">
              <dia:color val="#ffffff"/>
            </dia:attribute>
            <dia:attribute name="pagebreak">
              <dia:color val="#000099"/>
            </dia:attribute>
            <dia:attribute name="paper">
              <dia:composite type="paper">
                <dia:attribute name="name">
                  <dia:string>#' . $paper . '#</dia:string>
                </dia:attribute>
                <dia:attribute name="tmargin">
                  <dia:real val="' . $topMargin . '"/>
                </dia:attribute>
                <dia:attribute name="bmargin">
                  <dia:real val="' . $bottomMargin . '"/>
                </dia:attribute>
                <dia:attribute name="lmargin">
                  <dia:real val="' . $leftMargin . '"/>
                </dia:attribute>
                <dia:attribute name="rmargin">
                  <dia:real val="' . $rightMargin . '"/>
                </dia:attribute>
                <dia:attribute name="is_portrait">
                  <dia:boolean val="' . $isPortrait . '"/>
                </dia:attribute>
                <dia:attribute name="scaling">
                  <dia:real val="1"/>
                </dia:attribute>
                <dia:attribute name="fitto">
                  <dia:boolean val="false"/>
                </dia:attribute>
              </dia:composite>
            </dia:attribute>
            <dia:attribute name="grid">
              <dia:composite type="grid">
                <dia:attribute name="width_x">
                  <dia:real val="1"/>
                </dia:attribute>
                <dia:attribute name="width_y">
                  <dia:real val="1"/>
                </dia:attribute>
                <dia:attribute name="visible_x">
                  <dia:int val="1"/>
                </dia:attribute>
                <dia:attribute name="visible_y">
                  <dia:int val="1"/>
                </dia:attribute>
                <dia:composite type="color"/>
              </dia:composite>
            </dia:attribute>
            <dia:attribute name="color">
              <dia:color val="#d8e5e5"/>
            </dia:attribute>
            <dia:attribute name="guides">
              <dia:composite type="guides">
                <dia:attribute name="hguides"/>
                <dia:attribute name="vguides"/>
              </dia:composite>
            </dia:attribute>'
        );
        $this->endElement();
        $this->startElement('dia:layer');
        $this->writeAttribute('name', 'Background');
        $this->writeAttribute('visible', 'true');
        $this->writeAttribute('active', 'true');
    }

    /**
     * Ends Dia Document
     *
     * @return void
     * @access public
     * @see XMLWriter::endElement(),XMLWriter::endDocument()
     */
    function endDiaDoc()
    {
        $this->endElement();
        $this->endDocument();
    }

    /**
     * Output Dia Document for download
     *
     * @param string $fileName name of the dia document
     *
     * @return void
     * @access public
     * @see XMLWriter::flush()
     */
    function showOutput($fileName)
    {
        if (ob_get_clean()) {
            ob_end_clean();
        }
        $output = $this->flush();
        PMA_Response::getInstance()->disable();
        PMA_downloadHeader(
            $fileName . '.dia', 'application/x-dia-diagram', strlen($output)
        );
        print $output;
    }
}

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in dia XML document.
 *
 * @name Table_Stats
 * @see PMA_DIA
 */
class Table_Stats
{
    /**
     * Defines properties
     */
    public $tableName;
    public $fields = array();
    public $x, $y;
    public $primary = array();
    public $tableId;
    public $tableColor;

    /**
     * The "Table_Stats" constructor
     *
     * @param string  $tableName  The table name
     * @param integer $pageNumber The current page number (from the
     *                            $cfg['Servers'][$i]['table_coords'] table)
     * @param boolean $showKeys   Whether to display ONLY keys or not
     *
     * @return void
     *
     * @global object    The current dia document
     * @global array     The relations settings
     * @global string    The current db name
     *
     * @see PMA_DIA
     */
    function __construct($tableName, $pageNumber, $showKeys = false)
    {
        global $dia, $cfgRelation, $db;
        
        $this->tableName = $tableName;
        $sql = 'DESCRIBE ' . PMA_Util::backquote($tableName);
        $result = PMA_DBI_try_query($sql, null, PMA_DBI_QUERY_STORE);
        if (!$result || !PMA_DBI_num_rows($result)) {
            $dia->dieSchema(
                $pageNumber, "DIA",
                sprintf(__('The %s table doesn\'t exist!'), $tableName)
            );
        }
        /*
         * load fields
         * check to see if it will load all fields or only the foreign keys
         */
        if ($showKeys) {
            $indexes = PMA_Index::getFromTable($this->tableName, $db);
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

        $sql = 'SELECT x, y FROM '
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
            . PMA_Util::backquote($cfgRelation['table_coords'])
            . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND table_name = \''
            . PMA_Util::sqlAddSlashes($tableName) . '\''
            . ' AND pdf_page_number = ' . $pageNumber;
        $result = PMA_queryAsControlUser($sql, false, PMA_DBI_QUERY_STORE);
        if (! $result || ! PMA_DBI_num_rows($result)) {
            $dia->dieSchema(
                $pageNumber,
                "DIA",
                sprintf(
                    __('Please configure the coordinates for table %s'),
                    $tableName
                )
            );
        }
        list($this->x, $this->y) = PMA_DBI_fetch_row($result);
        $this->x = (double) $this->x;
        $this->y = (double) $this->y;
        /*
         * displayfield
         */
        $this->displayfield = PMA_getDisplayField($db, $tableName);
        /*
         * index
         */
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
        /**
         * Every object in Dia document needs an ID to identify
         * so, we used a static variable to keep the things unique
         */
        PMA_Dia_Relation_Schema::$objectId += 1;
        $this->tableId = PMA_Dia_Relation_Schema::$objectId;
    }

    /**
     * Do draw the table
     *
     * Tables are generated using object type Database - Table
     * primary fields are underlined in tables. Dia object
     * is used to generate the XML of Dia Document. Database Table
     * Object and their attributes are involved in the combination
     * of displaing Database - Table on Dia Document.
     *
     * @param boolean $changeColor Whether to show color for tables text or not
     * if changeColor is true then an array of $listOfColors will be used to choose
     * the random colors for tables text we can change/add more colors to this array
     *
     * @return void
     *
     * @global object The current Dia document
     *
     * @access public
     * @see PMA_DIA
     */
    public function tableDraw($changeColor)
    {
        global $dia;

        if ($changeColor) {
            $listOfColors = array(
                'FF0000',
                '000099',
                '00FF00'
            );
            shuffle($listOfColors);
            $this->tableColor =  '#' . $listOfColors[0] . '';
        } else {
            $this->tableColor = '#000000';
        }

        $factor = 0.1;

        $dia->startElement('dia:object');
        $dia->writeAttribute('type', 'Database - Table');
        $dia->writeAttribute('version', '0');
        $dia->writeAttribute('id', '' . $this->tableId . '');
        $dia->writeRaw(
            '<dia:attribute name="obj_pos">
                <dia:point val="'
            . ($this->x * $factor) . ',' . ($this->y * $factor) . '"/>
            </dia:attribute>
            <dia:attribute name="obj_bb">
                <dia:rectangle val="'
            .($this->x * $factor) . ',' . ($this->y * $factor) . ';9.97,9.2"/>
            </dia:attribute>
            <dia:attribute name="meta">
                <dia:composite type="dict"/>
            </dia:attribute>
            <dia:attribute name="elem_corner">
                <dia:point val="'
            . ($this->x * $factor) . ',' . ($this->y * $factor) . '"/>
            </dia:attribute>
            <dia:attribute name="elem_width">
                <dia:real val="5.9199999999999999"/>
            </dia:attribute>
            <dia:attribute name="elem_height">
                <dia:real val="3.5"/>
            </dia:attribute>
            <dia:attribute name="text_colour">
                <dia:color val="' . $this->tableColor . '"/>
            </dia:attribute>
            <dia:attribute name="line_colour">
                <dia:color val="#000000"/>
            </dia:attribute>
            <dia:attribute name="fill_colour">
                <dia:color val="#ffffff"/>
            </dia:attribute>
            <dia:attribute name="line_width">
                <dia:real val="0.10000000000000001"/>
            </dia:attribute>
            <dia:attribute name="name">
                <dia:string>#' . $this->tableName . '#</dia:string>
            </dia:attribute>
            <dia:attribute name="comment">
                <dia:string>##</dia:string>
            </dia:attribute>
            <dia:attribute name="visible_comment">
                <dia:boolean val="false"/>
            </dia:attribute>
            <dia:attribute name="tagging_comment">
                <dia:boolean val="false"/>
            </dia:attribute>
            <dia:attribute name="underline_primary_key">
                <dia:boolean val="true"/>
            </dia:attribute>
            <dia:attribute name="bold_primary_keys">
                <dia:boolean val="true"/>
            </dia:attribute>
            <dia:attribute name="normal_font">
                <dia:font family="monospace" style="0" name="Courier"/>
            </dia:attribute>
            <dia:attribute name="name_font">
                <dia:font family="sans" style="80" name="Helvetica-Bold"/>
            </dia:attribute>
            <dia:attribute name="comment_font">
                <dia:font family="sans" style="0" name="Helvetica"/>
            </dia:attribute>
            <dia:attribute name="normal_font_height">
                <dia:real val="0.80000000000000004"/>
            </dia:attribute>
            <dia:attribute name="name_font_height">
                <dia:real val="0.69999999999999996"/>
            </dia:attribute>
            <dia:attribute name="comment_font_height">
                <dia:real val="0.69999999999999996"/>
            </dia:attribute>'
        );

        $dia->startElement('dia:attribute');
        $dia->writeAttribute('name', 'attributes');

        foreach ($this->fields as $field) {
            $dia->writeRaw(
                '<dia:composite type="table_attribute">
                    <dia:attribute name="name">
                <dia:string>#' . $field . '#</dia:string>
                </dia:attribute>
                <dia:attribute name="type">
                    <dia:string>##</dia:string>
                </dia:attribute>
                    <dia:attribute name="comment">
                <dia:string>##</dia:string>
                </dia:attribute>'
            );
            unset($pm);
            $pm = 'false';
            if (in_array($field, $this->primary)) {
                $pm = 'true';
            }
            if ($field == $this->displayfield) {
                $pm = 'false';
            }
            $dia->writeRaw(
                '<dia:attribute name="primary_key">
                    <dia:boolean val="' . $pm . '"/>
                </dia:attribute>
                <dia:attribute name="nullable">
                    <dia:boolean val="false"/>
                </dia:attribute>
                <dia:attribute name="unique">
                    <dia:boolean val="' . $pm . '"/>
                </dia:attribute>
                </dia:composite>'
            );
        }
        $dia->endElement();
        $dia->endElement();
    }
}

/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in dia XML document.
 *
 * @name Relation_Stats
 * @see PMA_DIA
 */
class Relation_Stats
{
    /**
     * Defines properties
     */
    public $srcConnPointsRight;
    public $srcConnPointsLeft;
    public $destConnPointsRight;
    public $destConnPointsLeft;
    public $masterTableId;
    public $foreignTableId;
    public $masterTablePos;
    public $foreignTablePos;
    public $referenceColor;

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
    function __construct($master_table, $master_field, $foreign_table,
        $foreign_field
    ) {
        $src_pos  = $this->_getXy($master_table, $master_field);
        $dest_pos = $this->_getXy($foreign_table, $foreign_field);
        $this->srcConnPointsLeft = $src_pos[0];
        $this->srcConnPointsRight = $src_pos[1];
        $this->destConnPointsLeft = $dest_pos[0];
        $this->destConnPointsRight = $dest_pos[1];
        $this->masterTablePos = $src_pos[2];
        $this->foreignTablePos = $dest_pos[2];
        $this->masterTableId = $master_table->tableId;
        $this->foreignTableId = $foreign_table->tableId;
    }

    /**
     * Each Table object have connection points
     * which is used to connect to other objects in Dia
     * we detect the position of key in fields and
     * then determines its left and right connection
     * points.
     *
     * @param string $table  The current table name
     * @param string $column The relation column name
     *
     * @return array Table right,left connection points and key position
     *
     * @access private
     */
    private function _getXy($table, $column)
    {
        $pos = array_search($column, $table->fields);
        // left, right, position
        $value = 12;
        if ($pos != 0) {
            return array($pos + $value + $pos, $pos + $value + $pos + 1, $pos);
        }
        return array($pos + $value , $pos + $value + 1, $pos);
    }

    /**
     * Draws relation references
     *
     * connects master table's master field to foreign table's
     * forein field using Dia object type Database - Reference
     * Dia object is used to generate the XML of Dia Document.
     * Database reference Object and their attributes are involved
     * in the combination of displaing Database - reference on Dia Document.
     *
     * @param boolean $changeColor Whether to use one color per relation or not
     * if changeColor is true then an array of $listOfColors will be used to choose
     * the random colors for references lines. we can change/add more colors to this
     *
     * @return void
     *
     * @global object The current Dia document
     *
     * @access public
     * @see PMA_PDF
     */
    public function relationDraw($changeColor)
    {
        global $dia;

        PMA_Dia_Relation_Schema::$objectId += 1;
        /*
         * if source connection points and destination connection
         * points are same then return it false and don't draw that
         * relation
         */
        if ( $this->srcConnPointsRight == $this->destConnPointsRight) {
            if ( $this->srcConnPointsLeft == $this->destConnPointsLeft) {
                return false;
            }
        }

        if ($changeColor) {
            $listOfColors = array(
                'FF0000',
                '000099',
                '00FF00'
            );
            shuffle($listOfColors);
            $this->referenceColor =  '#' . $listOfColors[0] . '';
        } else {
            $this->referenceColor = '#000000';
        }

        $dia->writeRaw(
            '<dia:object type="Database - Reference" version="0" id="'
            . PMA_Dia_Relation_Schema::$objectId . '">
            <dia:attribute name="obj_pos">
                <dia:point val="3.27,18.9198"/>
            </dia:attribute>
            <dia:attribute name="obj_bb">
                <dia:rectangle val="2.27,8.7175;17.7679,18.9198"/>
            </dia:attribute>
            <dia:attribute name="meta">
                <dia:composite type="dict"/>
            </dia:attribute>
            <dia:attribute name="orth_points">
                <dia:point val="3.27,18.9198"/>
                <dia:point val="2.27,18.9198"/>
                <dia:point val="2.27,14.1286"/>
                <dia:point val="17.7679,14.1286"/>
                <dia:point val="17.7679,9.3375"/>
                <dia:point val="16.7679,9.3375"/>
            </dia:attribute>
            <dia:attribute name="orth_orient">
                <dia:enum val="0"/>
                <dia:enum val="1"/>
                <dia:enum val="0"/>
                <dia:enum val="1"/>
                <dia:enum val="0"/>
            </dia:attribute>
            <dia:attribute name="orth_autoroute">
                <dia:boolean val="true"/>
            </dia:attribute>
            <dia:attribute name="text_colour">
                <dia:color val="#000000"/>
            </dia:attribute>
            <dia:attribute name="line_colour">
                <dia:color val="' . $this->referenceColor . '"/>
            </dia:attribute>
            <dia:attribute name="line_width">
                <dia:real val="0.10000000000000001"/>
            </dia:attribute>
            <dia:attribute name="line_style">
                <dia:enum val="0"/>
                <dia:real val="1"/>
            </dia:attribute>
            <dia:attribute name="corner_radius">
                <dia:real val="0"/>
            </dia:attribute>
            <dia:attribute name="end_arrow">
                <dia:enum val="22"/>
            </dia:attribute>
            <dia:attribute name="end_arrow_length">
                <dia:real val="0.5"/>
            </dia:attribute>
            <dia:attribute name="end_arrow_width">
                <dia:real val="0.5"/>
            </dia:attribute>
            <dia:attribute name="start_point_desc">
                <dia:string>#1#</dia:string>
            </dia:attribute>
            <dia:attribute name="end_point_desc">
                <dia:string>#n#</dia:string>
            </dia:attribute>
            <dia:attribute name="normal_font">
                <dia:font family="monospace" style="0" name="Courier"/>
            </dia:attribute>
            <dia:attribute name="normal_font_height">
                <dia:real val="0.59999999999999998"/>
            </dia:attribute>
            <dia:connections>
                <dia:connection handle="0" to="'
            . $this->masterTableId . '" connection="'
            . $this->srcConnPointsRight . '"/>
                <dia:connection handle="1" to="'
            . $this->foreignTableId . '" connection="'
            . $this->destConnPointsRight . '"/>
            </dia:connections>
            </dia:object>'
        );
    }
}

/**
 * Dia Relation Schema Class
 *
 * Purpose of this class is to generate the Dia XML Document
 * which is used for representing the database diagrams in Dia IDE
 * This class uses Database Table and Reference Objects of Dia and with
 * the combination of these objects actually helps in preparing Dia XML.
 *
 * Dia XML is generated by using XMLWriter php extension and this class
 * inherits Export_Relation_Schema class has common functionality added
 * to this class
 *
 * @name Dia_Relation_Schema
 */
class PMA_Dia_Relation_Schema extends PMA_Export_Relation_Schema
{
    /**
     * Defines properties
     */
    private $_tables = array();
    private $_relations = array();
    private $_topMargin = 2.8222000598907471;
    private $_bottomMargin = 2.8222000598907471;
    private $_leftMargin = 2.8222000598907471;
    private $_rightMargin = 2.8222000598907471;
    public static $objectId = 0;

    /**
     * The "PMA_Dia_Relation_Schema" constructor
     *
     * Upon instantiation This outputs the Dia XML document
     * that user can download
     *
     * @return void
     * @see PMA_DIA,Table_Stats,Relation_Stats
     */
    function __construct()
    {
        global $dia,$db;

        $this->setPageNumber($_POST['pdf_page_number']);
        $this->setShowGrid(isset($_POST['show_grid']));
        $this->setShowColor($_POST['show_color']);
        $this->setShowKeys(isset($_POST['show_keys']));
        $this->setOrientation(isset($_POST['orientation']));
        $this->setPaper($_POST['paper']);
        $this->setExportType($_POST['export_type']);

        $dia = new PMA_DIA();
        $dia->startDiaDoc(
            $this->paper, $this->_topMargin, $this->_bottomMargin,
            $this->_leftMargin, $this->_rightMargin, $this->orientation
        );
        $alltables = $this->getAllTables($db, $this->pageNumber);
        foreach ($alltables as $table) {
            if (! isset($this->tables[$table])) {
                $this->_tables[$table] = new Table_Stats(
                    $table, $this->pageNumber, $this->showKeys
                );
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
                            $one_table, $master_field, $rel['foreign_table'],
                            $rel['foreign_field'], $this->showKeys
                        );
                    }
                }
            }
        }
        $this->_drawTables($this->showColor);

        if ($seen_a_relation) {
            $this->_drawRelations($this->showColor);
        }
        $dia->endDiaDoc();
        $dia->showOutput($db . '-' . $this->pageNumber);
        exit();
    }

    /**
     * Defines relation objects
     *
     * @param string $masterTable  The master table name
     * @param string $masterField  The relation field in the master table
     * @param string $foreignTable The foreign table name
     * @param string $foreignField The relation field in the foreign table
     * @param bool   $showKeys     Whether to display ONLY keys or not
     *
     * @return void
     *
     * @access private
     * @see Table_Stats::__construct(),Relation_Stats::__construct()
     */
    private function _addRelation($masterTable, $masterField, $foreignTable,
        $foreignField, $showKeys
    ) {
        if (! isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new Table_Stats(
                $masterTable, $this->pageNumber, $showKeys
            );
        }
        if (! isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new Table_Stats(
                $foreignTable, $this->pageNumber, $showKeys
            );
        }
        $this->_relations[] = new Relation_Stats(
            $this->_tables[$masterTable], $masterField,
            $this->_tables[$foreignTable], $foreignField
        );
    }

    /**
     * Draws relation references
     *
     * connects master table's master field to
     * foreign table's forein field using Dia object
     * type Database - Reference
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
     * Tables are generated using Dia object type Database - Table
     * primary fields are underlined and bold in tables
     *
     * @param boolean $changeColor Whether to show color for tables text or not
     *
     * @return void
     *
     * @access private
     * @see Table_Stats::tableDraw()
     */
    private function _drawTables($changeColor)
    {
        foreach ($this->_tables as $table) {
            $table->tableDraw($changeColor);
        }
    }
}
?>
