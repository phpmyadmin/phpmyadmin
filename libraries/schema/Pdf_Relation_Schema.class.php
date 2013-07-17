<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * block attempts to directly run this script 
 */
if (getcwd() == dirname(__FILE__)) {
    die('Attack stopped');
}

require_once 'Export_Relation_Schema.class.php';
require_once './libraries/PDF.class.php';

/**
 * Extends the "TCPDF" class and helps
 * in developing the structure of PDF Schema Export
 *
 * @access public
 * @see TCPDF
 */
class PMA_Schema_PDF extends PMA_PDF
{
    /**
     * Defines properties
     */
    var $_xMin;
    var $_yMin;
    var $leftMargin = 10;
    var $topMargin = 10;
    var $scale;
    var $PMA_links;
    var $Outlines = array();
    var $def_outlines;
    var $widths;
    private $_ff = PMA_PDF_FONT;

    /**
     * Sets the value for margins
     *
     * @param float $c_margin margin
     *
     * @return nothing
     */
    public function setCMargin($c_margin)
    {
        $this->cMargin = $c_margin;
    }

    /**
     * Sets the scaling factor, defines minimum coordinates and margins
     *
     * @param float $scale      The scaling factor
     * @param float $xMin       The minimum X coordinate
     * @param float $yMin       The minimum Y coordinate
     * @param float $leftMargin The left margin
     * @param float $topMargin  The top margin
     *
     * @access public
     *
     * @return nothing
     */
    function PMA_PDF_setScale($scale = 1, $xMin = 0, $yMin = 0, $leftMargin = -1, $topMargin = -1)
    {
        $this->scale = $scale;
        $this->_xMin = $xMin;
        $this->_yMin = $yMin;
        if ($this->leftMargin != -1) {
            $this->leftMargin = $leftMargin;
        }
        if ($this->topMargin != -1) {
            $this->topMargin = $topMargin;
        }
    }

    /**
     * Outputs a scaled cell
     *
     * @param float   $w      The cell width
     * @param float   $h      The cell height
     * @param string  $txt    The text to output
     * @param mixed   $border Whether to add borders or not
     * @param integer $ln     Where to put the cursor once the output is done
     * @param string  $align  Align mode
     * @param integer $fill   Whether to fill the cell with a color or not
     * @param string  $link   Link
     *
     * @access public
     *
     * @return nothing
     *
     * @see TCPDF::Cell()
     */
    function PMA_PDF_cellScale($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = 0, $link = '')
    {
        $h = $h / $this->scale;
        $w = $w / $this->scale;
        $this->Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    /**
     * Draws a scaled line
     *
     * @param float $x1 The horizontal position of the starting point
     * @param float $y1 The vertical position of the starting point
     * @param float $x2 The horizontal position of the ending point
     * @param float $y2 The vertical position of the ending point
     *
     * @access public
     *
     * @return nothing
     *
     * @see TCPDF::Line()
     */
    function PMA_PDF_lineScale($x1, $y1, $x2, $y2)
    {
        $x1 = ($x1 - $this->_xMin) / $this->scale + $this->leftMargin;
        $y1 = ($y1 - $this->_yMin) / $this->scale + $this->topMargin;
        $x2 = ($x2 - $this->_xMin) / $this->scale + $this->leftMargin;
        $y2 = ($y2 - $this->_yMin) / $this->scale + $this->topMargin;
        $this->Line($x1, $y1, $x2, $y2);
    }

    /**
     * Sets x and y scaled positions
     *
     * @param float $x The x position
     * @param float $y The y position
     *
     * @access public
     *
     * @return nothing
     *
     * @see TCPDF::SetXY()
     */
    function PMA_PDF_setXyScale($x, $y)
    {
        $x = ($x - $this->_xMin) / $this->scale + $this->leftMargin;
        $y = ($y - $this->_yMin) / $this->scale + $this->topMargin;
        $this->SetXY($x, $y);
    }

    /**
     * Sets the X scaled positions
     *
     * @param float $x The x position
     *
     * @access public
     *
     * @return nothing
     *
     * @see TCPDF::SetX()
     */
    function PMA_PDF_setXScale($x)
    {
        $x = ($x - $this->_xMin) / $this->scale + $this->leftMargin;
        $this->SetX($x);
    }

    /**
     * Sets the scaled font size
     *
     * @param float $size The font size (in points)
     *
     * @access public
     *
     * @return nothing
     *
     * @see TCPDF::SetFontSize()
     */
    function PMA_PDF_setFontSizeScale($size)
    {
        // Set font size in points
        $size = $size / $this->scale;
        $this->SetFontSize($size);
    }

    /**
     * Sets the scaled line width
     *
     * @param float $width The line width
     *
     * @access public
     *
     * @return nothing
     *
     * @see TCPDF::SetLineWidth()
     */
    function PMA_PDF_setLineWidthScale($width)
    {
        $width = $width / $this->scale;
        $this->SetLineWidth($width);
    }

    /**
     * This method is used to render the page header.
     *
     * @return nothing
     *
     * @see TCPDF::Header()
     */
    function Header()
    {
        // We only show this if we find something in the new pdf_pages table

        // This function must be named "Header" to work with the TCPDF library
        global $cfgRelation, $db, $pdf_page_number, $with_doc;
        if ($with_doc) {
            $test_query = 'SELECT * FROM '
                . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
                . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . PMA_sqlAddSlashes($db) . '\''
                . ' AND page_nr = \'' . $pdf_page_number . '\'';
            $test_rs = PMA_query_as_controluser($test_query);
            $pages = @PMA_DBI_fetch_assoc($test_rs);
            $this->SetFont($this->_ff, 'B', 14);
            $this->Cell(0, 6, ucfirst($pages['page_descr']), 'B', 1, 'C');
            $this->SetFont($this->_ff, '');
            $this->Ln();
        }
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     *
     * @return nothing
     *
     * @see PMA_PDF::Footer()
     */
    function Footer()
    {
        global $with_doc;
        if ($with_doc) {
            parent::Footer();
        }
    }

    /**
     * Sets widths
     *
     * @param array $w array of widths
     *
     * @return nothing
     */
    function SetWidths($w)
    {
        // column widths
        $this->widths = $w;
    }

    function Row($data, $links)
    {
        // line height
        $nb = 0;
        $data_cnt = count($data);
        for ($i = 0;$i < $data_cnt;$i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $il = $this->FontSize;
        $h = ($il + 1) * $nb;
        // page break if necessary
        $this->CheckPageBreak($h);
        // draw the cells
        $data_cnt = count($data);
        for ($i = 0;$i < $data_cnt;$i++) {
            $w = $this->widths[$i];
            // save current position
            $x = $this->GetX();
            $y = $this->GetY();
            // draw the border
            $this->Rect($x, $y, $w, $h);
            if (isset($links[$i])) {
                $this->Link($x, $y, $w, $h, $links[$i]);
            }
            // print text
            $this->MultiCell($w, $il + 1, $data[$i], 0, 'L');
            // go to right side
            $this->SetXY($x + $w, $y);
        }
        // go to line
        $this->Ln($h);
    }

    /**
     * Compute number of lines used by a multicell of width w
     *
     * @param int    $w   width
     * @param string $txt text
     *
     * @return int
     */
    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w-2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb-1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += isset($cw[ord($c)])?$cw[ord($c)]:0 ;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in PDF document.
 *
 * @name Table_Stats
 * @see PMA_Schema_PDF
 */
class Table_Stats
{
    /**
     * Defines properties
     */
    private $_tableName;
    private $_showInfo = false;

    public $nb_fiels;
    public $width = 0;
    public $height;
    public $fields = array();
    public $heightCell = 6;
    public $x, $y;
    public $primary = array();
    private $_ff = PMA_PDF_FONT;

    /**
     * The "Table_Stats" constructor
     *
     * @param string  $tableName      The table name
     * @param integer $fontSize       The font size
     * @param integer $pageNumber     The current page number (from the
     *                                $cfg['Servers'][$i]['table_coords'] table)
     * @param integer &$sameWideWidth The max. with among tables
     * @param boolean $showKeys       Whether to display keys or not
     * @param boolean $showInfo       Whether to display table position or not
     *
     * @global object    The current PDF document
     * @global array     The relations settings
     * @global string    The current db name
     *
     * @return nothing
     *
     * @see PMA_Schema_PDF, Table_Stats::Table_Stats_setWidth,
     *     Table_Stats::Table_Stats_setHeight
     */
    function __construct($tableName, $fontSize, $pageNumber, &$sameWideWidth, $showKeys = false, $showInfo = false)
    {
        global $pdf, $cfgRelation, $db;

        $this->_tableName = $tableName;
        $sql = 'DESCRIBE ' . PMA_backquote($tableName);
        $result = PMA_DBI_try_query($sql, null, PMA_DBI_QUERY_STORE);
        if (! $result || ! PMA_DBI_num_rows($result)) {
            $pdf->Error(sprintf(__('The %s table doesn\'t exist!'), $tableName));
        }
        // load fields
        //check to see if it will load all fields or only the foreign keys
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
        $this->_setHeight();
        /*
         * setWidth must me after setHeight, because title
         * can include table height which changes table width
         */
        $this->_setWidth($fontSize);
        if ($sameWideWidth < $this->width) {
            $sameWideWidth = $this->width;
        }
        $sql = 'SELECT x, y FROM '
             . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
             . PMA_backquote($cfgRelation['table_coords'])
             . ' WHERE db_name = \'' . PMA_sqlAddSlashes($db) . '\''
             . ' AND   table_name = \'' . PMA_sqlAddSlashes($tableName) . '\''
             . ' AND   pdf_page_number = ' . $pageNumber;
        $result = PMA_query_as_controluser($sql, false, PMA_DBI_QUERY_STORE);
        if (! $result || ! PMA_DBI_num_rows($result)) {
            $pdf->Error(
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
            'SHOW INDEX FROM ' . PMA_backquote($tableName) . ';',
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
     * title can have the dimensions of the table
     *
     * @return string
     */
    private function _getTitle()
    {
        return ($this->_showInfo ? sprintf('%.0f', $this->width) . 'x' . sprintf('%.0f', $this->height) : '') . ' ' . $this->_tableName;
    }

    /**
     * Sets the width of the table
     *
     * @param integer $fontSize The font size
     *
     * @global object    The current PDF document
     *
     * @access private
     *
     * @return nothing
     *
     * @see PMA_Schema_PDF
     */
    private function _setWidth($fontSize)
    {
        global $pdf;

        foreach ($this->fields as $field) {
            $this->width = max($this->width, $pdf->GetStringWidth($field));
        }
        $this->width += $pdf->GetStringWidth('      ');
        $pdf->SetFont($this->_ff, 'B', $fontSize);
        /*
         * it is unknown what value must be added, because
         * table title is affected by the tabe width value
         */
        while ($this->width < $pdf->GetStringWidth($this->_getTitle())) {
            $this->width += 5;
        }
        $pdf->SetFont($this->_ff, '', $fontSize);
    }

    /**
     * Sets the height of the table
     *
     * @return nothing
     *
     * @access private
     */
    private function _setHeight()
    {
        $this->height = (count($this->fields) + 1) * $this->heightCell;
    }

    /**
     * Do draw the table
     *
     * @param integer $fontSize The font size
     * @param boolean $withDoc
     * @param boolean $setColor Whether to display color
     *
     * @global object The current PDF document
     *
     * @access public
     *
     * @return nothing
     *
     * @see PMA_Schema_PDF
     */
    public function tableDraw($fontSize, $withDoc, $setColor = 0)
    {
        global $pdf, $withDoc;

        $pdf->PMA_PDF_setXyScale($this->x, $this->y);
        $pdf->SetFont($this->_ff, 'B', $fontSize);
        if ($setColor) {
            $pdf->SetTextColor(200);
            $pdf->SetFillColor(0, 0, 128);
        }
        if ($withDoc) {
            $pdf->SetLink($pdf->PMA_links['RT'][$this->_tableName]['-'], -1);
        } else {
            $pdf->PMA_links['doc'][$this->_tableName]['-'] = '';
        }

        $pdf->PMA_PDF_cellScale(
            $this->width,
            $this->heightCell,
            $this->_getTitle(),
            1,
            1,
            'C',
            $setColor,
            $pdf->PMA_links['doc'][$this->_tableName]['-']
        );
        $pdf->PMA_PDF_setXScale($this->x);
        $pdf->SetFont($this->_ff, '', $fontSize);
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);

        foreach ($this->fields as $field) {
            if ($setColor) {
                if (in_array($field, $this->primary)) {
                    $pdf->SetFillColor(215, 121, 123);
                }
                if ($field == $this->displayfield) {
                    $pdf->SetFillColor(142, 159, 224);
                }
            }
            if ($withDoc) {
                $pdf->SetLink($pdf->PMA_links['RT'][$this->_tableName][$field], -1);
            } else {
                $pdf->PMA_links['doc'][$this->_tableName][$field] = '';
            }

            $pdf->PMA_PDF_cellScale(
                $this->width,
                $this->heightCell,
                ' ' . $field,
                1,
                1,
                'L',
                $setColor,
                $pdf->PMA_links['doc'][$this->_tableName][$field]
            );
            $pdf->PMA_PDF_setXScale($this->x);
            $pdf->SetFillColor(255);
        }
    }
}

/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in PDF document.
 *
 * @name Relation_Stats
 * @see PMA_Schema_PDF::SetDrawColor, PMA_Schema_PDF::PMA_PDF_setLineWidthScale,
 * PMA_Schema_PDF::PMA_PDF_lineScale
 */
class Relation_Stats
{
    /**
     * Defines properties
     */
    public $xSrc, $ySrc;
    public $srcDir;
    public $destDir;
    public $xDest, $yDest;
    public $wTick = 5;

    /**
     * The "Relation_Stats" constructor
     *
     * @param string $master_table  The master table name
     * @param string $master_field  The relation field in the master table
     * @param string $foreign_table The foreign table name
     * @param string $foreign_field The relation field in the foreign table
     *
     * @return nothing
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
     *
     * @access private
     */
    private function _getXy($table, $column)
    {
        $pos = array_search($column, $table->fields);
        // x_left, x_right, y
        return array($table->x, $table->x + + $table->width, $table->y + ($pos + 1.5) * $table->heightCell);
    }

    /**
     * draws relation links and arrows shows foreign key relations
     *
     * @param boolean $changeColor Whether to use one color per relation or not
     * @param integer $i           The id of the link to draw
     *
     * @global object    The current PDF document
     *
     * @access public
     *
     * @return nothing
     *
     * @see PMA_Schema_PDF
     */
    public function relationDraw($changeColor, $i)
    {
        global $pdf;

        if ($changeColor) {
            $d = $i % 6;
            $j = ($i - $d) / 6;
            $j = $j % 4;
            $j++;
            $case = array(
                array(1, 0, 0),
                array(0, 1, 0),
                array(0, 0, 1),
                array(1, 1, 0),
                array(1, 0, 1),
                array(0, 1, 1)
            );
            list ($a, $b, $c) = $case[$d];
            $e = (1 - ($j - 1) / 6);
            $pdf->SetDrawColor($a * 255 * $e, $b * 255 * $e, $c * 255 * $e);
        } else {
            $pdf->SetDrawColor(0);
        }
        $pdf->PMA_PDF_setLineWidthScale(0.2);
        $pdf->PMA_PDF_lineScale(
            $this->xSrc,
            $this->ySrc,
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc
        );
        $pdf->PMA_PDF_lineScale(
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            $this->xDest,
            $this->yDest
        );
        $pdf->PMA_PDF_setLineWidthScale(0.1);
        $pdf->PMA_PDF_lineScale(
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest
        );
        /*
         * Draws arrows ->
         */
        $root2 = 2 * sqrt(2);
        $pdf->PMA_PDF_lineScale(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc + $this->wTick / $root2
        );
        $pdf->PMA_PDF_lineScale(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc - $this->wTick / $root2
        );

        $pdf->PMA_PDF_lineScale(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest + $this->wTick / $root2
        );
        $pdf->PMA_PDF_lineScale(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest - $this->wTick / $root2
        );
        $pdf->SetDrawColor(0);
    }
}

/**
 * Pdf Relation Schema Class
 *
 * Purpose of this class is to generate the PDF Document. PDF is widely
 * used format for documenting text,fonts,images and 3d vector graphics.
 *
 * This class inherits Export_Relation_Schema class has common functionality added
 * to this class
 *
 * @name Pdf_Relation_Schema
 */
class PMA_Pdf_Relation_Schema extends PMA_Export_Relation_Schema
{
    /**
     * Defines properties
     */
    private $_ff = PMA_PDF_FONT;
    private $_xMax = 0;
    private $_yMax = 0;
    private $scale;
    private $_xMin = 100000;
    private $_yMin = 100000;
    private $topMargin = 10;
    private $bottomMargin = 10;
    private $leftMargin = 10;
    private $rightMargin = 10;
    private $_tablewidth;

    /**
     * The "PMA_Pdf_Relation_Schema" constructor
     *
     * @global object   The current PDF Schema document
     * @global string   The current db name
     * @global array    The relations settings
     * @access private
     * @see PMA_Schema_PDF
     */
    function __construct()
    {
        global $pdf, $db;

        $this->setPageNumber($_POST['pdf_page_number']);
        $this->setShowGrid(isset($_POST['show_grid']));
        $this->setShowColor(isset($_POST['show_color']));
        $this->setShowKeys(isset($_POST['show_keys']));
        $this->setTableDimension(isset($_POST['show_table_dimension']));
        $this->setAllTableSameWidth(isset($_POST['all_table_same_wide']));
        $this->setWithDataDictionary($_POST['with_doc']);
        $this->setOrientation($_POST['orientation']);
        $this->setPaper($_POST['paper']);
        $this->setExportType($_POST['export_type']);

         // Initializes a new document
        $pdf = new PMA_Schema_PDF($this->orientation, 'mm', $this->paper);
        $pdf->SetTitle(
            sprintf(
                __('Schema of the %s database - Page %s'),
                $GLOBALS['db'],
                $this->pageNumber
            )
        );
        $pdf->setCMargin(0);
        $pdf->Open();
        $pdf->SetAutoPageBreak('auto');
        $alltables = $this->getAllTables($db, $this->pageNumber);

        if ($this->withDoc) {
            $pdf->SetAutoPageBreak('auto', 15);
            $pdf->setCMargin(1);
            $this->dataDictionaryDoc($alltables);
            $pdf->SetAutoPageBreak('auto');
            $pdf->setCMargin(0);
        }

        $pdf->Addpage();

        if ($this->withDoc) {
            $pdf->SetLink($pdf->PMA_links['RT']['-'], -1);
            $pdf->Bookmark(__('Relational schema'));
            $pdf->SetAlias('{00}', $pdf->PageNo());
            $this->topMargin = 28;
            $this->bottomMargin = 28;
        }

        /* snip */
        foreach ($alltables as $table) {
            if (! isset($this->tables[$table])) {
                $this->tables[$table] = new Table_Stats(
                    $table, $this->_ff,
                    $this->pageNumber,
                    $this->_tablewidth,
                    $this->showKeys,
                    $this->tableDimension
                );
            }
            if ($this->sameWide) {
                $this->tables[$table]->width = $this->_tablewidth;
            }
            $this->_setMinMax($this->tables[$table]);
        }

        // Defines the scale factor
        $this->scale = ceil(
            max(
                ($this->_xMax - $this->_xMin) / ($pdf->getPageWidth() - $this->rightMargin - $this->leftMargin),
                ($this->_yMax - $this->_yMin) / ($pdf->getPageHeight() - $this->topMargin - $this->bottomMargin)
            ) * 100
        ) / 100;

        $pdf->PMA_PDF_setScale(
            $this->scale,
            $this->_xMin,
            $this->_yMin,
            $this->leftMargin,
            $this->topMargin
        );
        // Builds and save the PDF document
        $pdf->PMA_PDF_setLineWidthScale(0.1);

        if ($this->showGrid) {
            $pdf->SetFontSize(10);
            $this->_strokeGrid();
        }
        $pdf->PMA_PDF_setFontSizeScale(14);
        // previous logic was checking master tables and foreign tables
        // but I think that looping on every table of the pdf page as a master
        // and finding its foreigns is OK (then we can support innodb)
        $seen_a_relation = false;
        foreach ($alltables as $one_table) {
            $exist_rel = PMA_getForeigners($db, $one_table, '', 'both');
            if ($exist_rel) {
                $seen_a_relation = true;
                foreach ($exist_rel as $master_field => $rel) {
                    // put the foreign table on the schema only if selected
                    // by the user
                    // (do not use array_search() because we would have to
                    // to do a === false and this is not PHP3 compatible)
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->_addRelation(
                            $one_table,
                            $master_field,
                            $rel['foreign_table'],
                            $rel['foreign_field'],
                            $this->tableDimension
                        );
                    }
                } // end while
            } // end if
        } // end while

        if ($seen_a_relation) {
            $this->_drawRelations($this->showColor);
        }
        $this->_drawTables($this->showColor);
        $this->_showOutput($this->pageNumber);
        exit();
    }

    /**
     * Sets X and Y minimum and maximum for a table cell
     *
     * @param string $table The table name of which sets XY co-ordinates
     *
     * @return nothing
     *
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
     * @param string  $masterField  The relation field in the master table
     * @param string  $foreignTable The foreign table name
     * @param string  $foreignField The relation field in the foreign table
     * @param boolean $showInfo     Whether to display table position or not
     *
     * @access private
     *
     * @return nothing
     *
     * @see _setMinMax
     */
    private function _addRelation($masterTable, $masterField, $foreignTable, $foreignField, $showInfo)
    {
        if (! isset($this->tables[$masterTable])) {
            $this->tables[$masterTable] = new Table_Stats(
                $masterTable, $this->_ff, $this->pageNumber,
                $this->_tablewidth, false, $showInfo
            );
            $this->_setMinMax($this->tables[$masterTable]);
        }
        if (! isset($this->tables[$foreignTable])) {
            $this->tables[$foreignTable] = new Table_Stats(
                $foreignTable, $this->_ff, $this->pageNumber,
                $this->_tablewidth, false, $showInfo
            );
            $this->_setMinMax($this->tables[$foreignTable]);
        }
        $this->relations[] = new Relation_Stats(
            $this->tables[$masterTable], $masterField,
            $this->tables[$foreignTable], $foreignField
        );
    }

    /**
     * Draws the grid
     *
     * @global object  the current PMA_Schema_PDF instance
     *
     * @access private
     *
     * @return nothing
     *
     * @see PMA_Schema_PDF
     */
    private function _strokeGrid()
    {
        global $pdf;

        $gridSize = 10;
        $labelHeight = 4;
        $labelWidth = 5;
        if ($this->withDoc) {
            $topSpace = 6;
            $bottomSpace = 15;
        } else {
            $topSpace = 0;
            $bottomSpace = 0;
        }

        $pdf->SetMargins(0, 0);
        $pdf->SetDrawColor(200, 200, 200);
        // Draws horizontal lines
        for ($l = 0; $l <= intval(($pdf->getPageHeight() - $topSpace - $bottomSpace) / $gridSize); $l++) {
            $pdf->line(
                0, $l * $gridSize + $topSpace,
                $pdf->getPageWidth(), $l * $gridSize + $topSpace
            );
            // Avoid duplicates
            if ($l > 0
                && $l <= intval(($pdf->getPageHeight() - $topSpace - $bottomSpace - $labelHeight) / $gridSize)
            ) {
                $pdf->SetXY(0, $l * $gridSize + $topSpace);
                $label = (string) sprintf(
                    '%.0f',
                    ($l * $gridSize + $topSpace - $this->topMargin) * $this->scale + $this->_yMin
                );
                $pdf->Cell($labelWidth, $labelHeight, ' ' . $label);
            } // end if
        } // end for
        // Draws vertical lines
        for ($j = 0; $j <= intval($pdf->getPageWidth() / $gridSize); $j++) {
            $pdf->line(
                $j * $gridSize,
                $topSpace,
                $j * $gridSize,
                $pdf->getPageHeight() - $bottomSpace
            );
            $pdf->SetXY($j * $gridSize, $topSpace);
            $label = (string) sprintf(
                '%.0f',
                ($j * $gridSize - $this->leftMargin) * $this->scale + $this->_xMin
            );
            $pdf->Cell($labelWidth, $labelHeight, $label);
        }
    }

    /**
     * Draws relation arrows
     *
     * @param boolean $changeColor Whether to use one color per relation or not
     *
     * @access private
     *
     * @return nothing
     *
     * @see Relation_Stats::relationdraw()
     */
    private function _drawRelations($changeColor)
    {
        $i = 0;
        foreach ($this->relations as $relation) {
            $relation->relationDraw($changeColor, $i);
            $i++;
        }
    }

    /**
     * Draws tables
     *
     * @param boolean $changeColor Whether to display table position or not
     *
     * @access private
     *
     * @return nothing
     *
     * @see Table_Stats::tableDraw()
     */
    private function _drawTables($changeColor = 0)
    {
        foreach ($this->tables as $table) {
            $table->tableDraw($this->_ff, $this->withDoc, $changeColor);
        }
    }

    /**
     * Ouputs the PDF document to a file
     * or sends the output to browser
     *
     * @param integer $pageNumber page number
     *
     * @global object   The current PDF document
     * @global string   The current database name
     * @global integer  The current page number (from the
     *                  $cfg['Servers'][$i]['table_coords'] table)
     * @access private
     *
     * @return nothing
     *
     * @see PMA_Schema_PDF
     */
    private function _showOutput($pageNumber)
    {
        global $pdf, $cfgRelation;

        // Get the name of this pdfpage to use as filename
        $_name_sql = 'SELECT page_descr FROM '
            . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.'
            . PMA_backquote($cfgRelation['pdf_pages'])
            . ' WHERE page_nr = ' . $pageNumber;
        $_name_rs = PMA_query_as_controluser($_name_sql);
        if ($_name_rs) {
            $_name_row = PMA_DBI_fetch_row($_name_rs);
            $filename = $_name_row[0] . '.pdf';
        }
        if (empty($filename)) {
            $filename = $pageNumber . '.pdf';
        }
        $pdf->Download($filename);
    }

    public function dataDictionaryDoc($alltables)
    {
        global $db, $pdf, $orientation, $paper;
        // TOC
        $pdf->addpage($GLOBALS['orientation']);
        $pdf->Cell(0, 9, __('Table of contents'), 1, 0, 'C');
        $pdf->Ln(15);
        $i = 1;
        foreach ($alltables as $table) {
            $pdf->PMA_links['doc'][$table]['-'] = $pdf->AddLink();
            $pdf->SetX(10);
            // $pdf->Ln(1);
            $pdf->Cell(
                0, 6, __('Page number:') . ' {' . sprintf("%02d", $i) . '}', 0, 0,
                'R', 0, $pdf->PMA_links['doc'][$table]['-']
            );
            $pdf->SetX(10);
            $pdf->Cell(
                0, 6, $i . ' ' . $table, 0, 1,
                'L', 0, $pdf->PMA_links['doc'][$table]['-']
            );
            // $pdf->Ln(1);
            $fields = PMA_DBI_get_columns($GLOBALS['db'], $table);
            foreach ($fields as $row) {
                $pdf->SetX(20);
                $field_name = $row['Field'];
                $pdf->PMA_links['doc'][$table][$field_name] = $pdf->AddLink();
                //$pdf->Cell(
                //    0, 6, $field_name, 0, 1,
                //    'L', 0, $pdf->PMA_links['doc'][$table][$field_name]
                //);
            }
            $i++;
        }
        $pdf->PMA_links['RT']['-'] = $pdf->AddLink();
        $pdf->SetX(10);
        $pdf->Cell(
            0, 6, __('Page number:') . ' {00}', 0, 0,
            'R', 0, $pdf->PMA_links['RT']['-']
        );
        $pdf->SetX(10);
        $pdf->Cell(
            0, 6, $i . ' ' . __('Relational schema'), 0, 1,
            'L', 0, $pdf->PMA_links['RT']['-']
        );
        $z = 0;
        foreach ($alltables as $table) {
            $z++;
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->addpage($GLOBALS['orientation']);
            $pdf->Bookmark($table);
            $pdf->SetAlias('{' . sprintf("%02d", $z) . '}', $pdf->PageNo());
            $pdf->PMA_links['RT'][$table]['-'] = $pdf->AddLink();
            $pdf->SetLink($pdf->PMA_links['doc'][$table]['-'], -1);
            $pdf->SetFont($this->_ff, 'B', 18);
            $pdf->Cell(
                0, 8, $z . ' ' . $table, 1, 1,
                'C', 0, $pdf->PMA_links['RT'][$table]['-']
            );
            $pdf->SetFont($this->_ff, '', 8);
            $pdf->ln();

            $cfgRelation = PMA_getRelationsParam();
            $comments = PMA_getComments($db, $table);
            if ($cfgRelation['mimework']) {
                $mime_map = PMA_getMIME($db, $table, true);
            }

            /**
             * Gets table informations
             */
            $showtable    = PMA_Table::sGetStatusInfo($db, $table);
            $show_comment = isset($showtable['Comment'])
                ? $showtable['Comment']
                : '';
            $create_time  = isset($showtable['Create_time'])
                ? PMA_localisedDate(strtotime($showtable['Create_time']))
                : '';
            $update_time  = isset($showtable['Update_time'])
                ? PMA_localisedDate(strtotime($showtable['Update_time']))
                : '';
            $check_time   = isset($showtable['Check_time'])
                ? PMA_localisedDate(strtotime($showtable['Check_time']))
                : '';

            /**
             * Gets table keys and retains them
             */
            $result = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($table) . ';');
            $primary = '';
            $indexes = array();
            $lastIndex = '';
            $indexes_info = array();
            $indexes_data = array();
            $pk_array = array(); // will be use to emphasis prim. keys in the table
            // view
            while ($row = PMA_DBI_fetch_assoc($result)) {
                // Backups the list of primary keys
                if ($row['Key_name'] == 'PRIMARY') {
                    $primary .= $row['Column_name'] . ', ';
                    $pk_array[$row['Column_name']] = 1;
                }
                // Retains keys informations
                if ($row['Key_name'] != $lastIndex) {
                    $indexes[] = $row['Key_name'];
                    $lastIndex = $row['Key_name'];
                }
                $indexes_info[$row['Key_name']]['Sequences'][] = $row['Seq_in_index'];
                $indexes_info[$row['Key_name']]['Non_unique'] = $row['Non_unique'];
                if (isset($row['Cardinality'])) {
                    $indexes_info[$row['Key_name']]['Cardinality'] = $row['Cardinality'];
                }
                // I don't know what does following column mean....
                // $indexes_info[$row['Key_name']]['Packed'] = $row['Packed'];
                $indexes_info[$row['Key_name']]['Comment'] = $row['Comment'];

                $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Column_name'] = $row['Column_name'];
                if (isset($row['Sub_part'])) {
                    $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Sub_part'] = $row['Sub_part'];
                }
            } // end while
            if ($result) {
                PMA_DBI_free_result($result);
            }

            /**
             * Gets fields properties
             */
            $columns = PMA_DBI_get_columns($db, $table);
            // Check if we can use Relations
            if (!empty($cfgRelation['relation'])) {
                // Find which tables are related with the current one and write it in
                // an array
                $res_rel = PMA_getForeigners($db, $table);

                if (count($res_rel) > 0) {
                    $have_rel = true;
                } else {
                    $have_rel = false;
                }
            } else {
                $have_rel = false;
            } // end if

            /**
             * Displays the comments of the table if MySQL >= 3.23
             */

            $break = false;
            if (! empty($show_comment)) {
                $pdf->Cell(0, 3, __('Table comments') . ' : ' . $show_comment, 0, 1);
                $break = true;
            }

            if (! empty($create_time)) {
                $pdf->Cell(0, 3, __('Creation') . ': ' . $create_time, 0, 1);
                $break = true;
            }

            if (! empty($update_time)) {
                $pdf->Cell(0, 3, __('Last update') . ': ' . $update_time, 0, 1);
                $break = true;
            }

            if (! empty($check_time)) {
                $pdf->Cell(0, 3, __('Last check') . ': ' . $check_time, 0, 1);
                $break = true;
            }

            if ($break == true) {
                $pdf->Cell(0, 3, '', 0, 1);
                $pdf->Ln();
            }

            $pdf->SetFont($this->_ff, 'B');
            if (isset($orientation) && $orientation == 'L') {
                $pdf->Cell(25, 8, __('Column'), 1, 0, 'C');
                $pdf->Cell(20, 8, __('Type'), 1, 0, 'C');
                $pdf->Cell(20, 8, __('Attributes'), 1, 0, 'C');
                $pdf->Cell(10, 8, __('Null'), 1, 0, 'C');
                $pdf->Cell(20, 8, __('Default'), 1, 0, 'C');
                $pdf->Cell(25, 8, __('Extra'), 1, 0, 'C');
                $pdf->Cell(45, 8, __('Links to'), 1, 0, 'C');

                if ($paper == 'A4') {
                    $comments_width = 67;
                } else {
                    // this is really intended for 'letter'
                    /**
                     * @todo find optimal width for all formats
                     */
                    $comments_width = 50;
                }
                $pdf->Cell($comments_width, 8, __('Comments'), 1, 0, 'C');
                $pdf->Cell(45, 8, 'MIME', 1, 1, 'C');
                $pdf->SetWidths(array(25, 20, 20, 10, 20, 25, 45, $comments_width, 45));
            } else {
                $pdf->Cell(20, 8, __('Column'), 1, 0, 'C');
                $pdf->Cell(20, 8, __('Type'), 1, 0, 'C');
                $pdf->Cell(20, 8, __('Attributes'), 1, 0, 'C');
                $pdf->Cell(10, 8, __('Null'), 1, 0, 'C');
                $pdf->Cell(15, 8, __('Default'), 1, 0, 'C');
                $pdf->Cell(15, 8, __('Extra'), 1, 0, 'C');
                $pdf->Cell(30, 8, __('Links to'), 1, 0, 'C');
                $pdf->Cell(30, 8, __('Comments'), 1, 0, 'C');
                $pdf->Cell(30, 8, 'MIME', 1, 1, 'C');
                $pdf->SetWidths(array(20, 20, 20, 10, 15, 15, 30, 30, 30));
            }
            $pdf->SetFont($this->_ff, '');

            foreach ($columns as $row) {
                $extracted_fieldspec = PMA_extractFieldSpec($row['Type']);
                $type                = $extracted_fieldspec['print_type'];
                $attribute           = $extracted_fieldspec['attribute'];
                if (! isset($row['Default'])) {
                    if ($row['Null'] != '' && $row['Null'] != 'NO') {
                        $row['Default'] = 'NULL';
                    }
                }
                $field_name = $row['Field'];
                // $pdf->Ln();
                $pdf->PMA_links['RT'][$table][$field_name] = $pdf->AddLink();
                $pdf->Bookmark($field_name, 1, -1);
                $pdf->SetLink($pdf->PMA_links['doc'][$table][$field_name], -1);
                $pdf_row = array(
                    $field_name,
                    $type,
                    $attribute,
                    ($row['Null'] == '' || $row['Null'] == 'NO') ? __('No') : __('Yes'),
                    (isset($row['Default']) ? $row['Default'] : ''),
                    $row['Extra'],
                    (isset($res_rel[$field_name])
                        ? $res_rel[$field_name]['foreign_table'] . ' -> ' . $res_rel[$field_name]['foreign_field']
                        : ''),
                    (isset($comments[$field_name])
                        ? $comments[$field_name]
                        : ''),
                    (isset($mime_map) && isset($mime_map[$field_name])
                        ? str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                        : '')
                );
                $links[0] = $pdf->PMA_links['RT'][$table][$field_name];
                if (isset($res_rel[$field_name]['foreign_table'])
                    AND isset($res_rel[$field_name]['foreign_field'])
                    AND isset($pdf->PMA_links['doc'][$res_rel[$field_name]['foreign_table']][$res_rel[$field_name]['foreign_field']])
                ) {
                    $links[6] = $pdf->PMA_links['doc'][$res_rel[$field_name]['foreign_table']][$res_rel[$field_name]['foreign_field']];
                } else {
                    unset($links[6]);
                }
                $pdf->Row($pdf_row, $links);
            } // end foreach
            $pdf->SetFont($this->_ff, '', 14);
        } //end each
    }
}
?>
