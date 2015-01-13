<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Classes to create relation schema in Dia format.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/plugins/schema/Export_Relation_Schema.class.php';
require_once 'libraries/plugins/schema/dia/RelationStatsDia.class.php';
require_once 'libraries/plugins/schema/dia/TableStatsDia.class.php';

/**
 * This Class inherits the XMLwriter class and
 * helps in developing structure of DIA Schema Export
 *
 * @package PhpMyAdmin
 * @access  public
 * @see     http://php.net/manual/en/book.xmlwriter.php
 */
class PMA_DIA extends XMLWriter
{
    /**
     * The "PMA_DIA" constructor
     *
     * Upon instantiation This starts writing the Dia XML document
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
     * @param string $orientation  orientation of the document, portrait or landscape
     *
     * @return void
     *
     * @access public
     * @see XMLWriter::startElement(),XMLWriter::writeAttribute(),
     *      XMLWriter::writeRaw()
     */
    function startDiaDoc($paper, $topMargin, $bottomMargin, $leftMargin,
        $rightMargin, $orientation
    ) {
        if ($orientation == 'P') {
            $isPortrait = 'true';
        } else {
            $isPortrait = 'false';
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
            $fileName,
            'application/x-dia-diagram',
            /*overload*/mb_strlen($output)
        );
        print $output;
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
 * @package PhpMyAdmin
 * @name    Dia_Relation_Schema
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
     * @see PMA_DIA,Table_Stats_Dia,Relation_Stats_Dia
     */
    function __construct()
    {
        parent::__construct();

        global $dia;

        $this->setShowColor(isset($_REQUEST['dia_show_color']));
        $this->setShowKeys(isset($_REQUEST['dia_show_keys']));
        $this->setOrientation($_REQUEST['dia_orientation']);
        $this->setPaper($_REQUEST['dia_paper']);

        $dia = new PMA_DIA();
        $dia->startDiaDoc(
            $this->paper, $this->_topMargin, $this->_bottomMargin,
            $this->_leftMargin, $this->_rightMargin, $this->orientation
        );

        $alltables = $this->getTablesFromRequest();

        foreach ($alltables as $table) {
            if (! isset($this->tables[$table])) {
                $this->_tables[$table] = new Table_Stats_Dia(
                    $table, $this->pageNumber, $this->showKeys, $this->offline
                );
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
                            $one_table, $master_field, $rel['foreign_table'],
                            $rel['foreign_field'], $this->showKeys
                        );
                    }
                    continue;
                }

                foreach ($rel as $one_key) {
                    if (!in_array($one_key['ref_table_name'], $alltables)) {
                        continue;
                    }

                    foreach ($one_key['index_list'] as $index => $one_field) {
                        $this->_addRelation(
                            $one_table, $one_field, $one_key['ref_table_name'],
                            $one_key['ref_index_list'][$index], $this->showKeys
                        );
                    }
                }
            }
        }
        $this->_drawTables();

        if ($seen_a_relation) {
            $this->_drawRelations();
        }
        $dia->endDiaDoc();
    }

    /**
     * Output Dia Document for download
     *
     * @return void
     * @access public
     */
    function showOutput()
    {
        global $dia;
        $dia->showOutput($this->getFileName('.dia'));
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
     * @see Table_Stats_Dia::__construct(),Relation_Stats_Dia::__construct()
     */
    private function _addRelation($masterTable, $masterField, $foreignTable,
        $foreignField, $showKeys
    ) {
        if (! isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new Table_Stats_Dia(
                $masterTable, $this->pageNumber, $showKeys
            );
        }
        if (! isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new Table_Stats_Dia(
                $foreignTable, $this->pageNumber, $showKeys
            );
        }
        $this->_relations[] = new Relation_Stats_Dia(
            $this->_tables[$masterTable], $masterField,
            $this->_tables[$foreignTable], $foreignField
        );
    }

    /**
     * Draws relation references
     *
     * connects master table's master field to
     * foreign table's foreign field using Dia object
     * type Database - Reference
     *
     * @return void
     *
     * @access private
     * @see Relation_Stats_Dia::relationDraw()
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
     * Tables are generated using Dia object type Database - Table
     * primary fields are underlined and bold in tables
     *
     * @return void
     *
     * @access private
     * @see Table_Stats_Dia::tableDraw()
     */
    private function _drawTables()
    {
        foreach ($this->_tables as $table) {
            $table->tableDraw($this->showColor);
        }
    }
}
?>
