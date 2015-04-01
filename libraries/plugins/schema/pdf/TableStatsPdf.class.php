<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains Table_Stats_Pdf class
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/plugins/schema/TableStats.class.php';

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in PDF document.
 *
 * @name    Table_Stats_Pdf
 * @package PhpMyAdmin
 * @see     PMA_Schema_PDF
 */
class Table_Stats_Pdf extends TableStats
{
    /**
     * Defines properties
     */
    public $nb_fiels;
    public $height;
    private $_ff = PMA_PDF_FONT;

    /**
     * The "Table_Stats_Pdf" constructor
     *
     * @param string  $tableName      The table name
     * @param integer $fontSize       The font size
     * @param integer $pageNumber     The current page number (from the
     *                                $cfg['Servers'][$i]['table_coords'] table)
     * @param integer &$sameWideWidth The max. with among tables
     * @param boolean $showKeys       Whether to display keys or not
     * @param boolean $tableDimension Whether to display table position or not
     * @param boolean $offline        Whether the coordinates are sent
     *                                from the browser
     *
     * @global object $pdf         The current PDF document
     *
     * @see PMA_Schema_PDF, Table_Stats_Pdf::Table_Stats_setWidth,
     *     Table_Stats_Pdf::Table_Stats_setHeight
     */
    function __construct($tableName, $fontSize, $pageNumber, &$sameWideWidth,
        $showKeys = false, $tableDimension = false, $offline = false
    ) {
        global $pdf;
        parent::__construct(
            $pdf, $GLOBALS['db'], $pageNumber, $tableName,
            $showKeys, $tableDimension, $offline
        );

        $this->heightCell = 6;
        $this->_setHeight();
        /*
         * setWidth must me after setHeight, because title
        * can include table height which changes table width
        */
        $this->_setWidth($fontSize);
        if ($sameWideWidth < $this->width) {
            $sameWideWidth = $this->width;
        }
    }

    /**
     * Displays an error when the table cannot be found.
     *
     * @return void
     */
    protected function showMissingTableError()
    {
        PMA_Export_Relation_Schema::dieSchema(
            $this->pageNumber,
            "PDF",
            sprintf(__('The %s table doesn\'t exist!'), $this->tableName)
        );
    }

    /**
     * Returns title of the current table,
     * title can have the dimensions of the table
     *
     * @return string
     */
    protected function getTitle()
    {
        $ret = '';
        if ($this->tableDimension) {
            $ret = sprintf('%.0fx%0.f', $this->width, $this->height);
        }
        return $ret . ' ' . $this->tableName;
    }

    /**
     * Sets the width of the table
     *
     * @param integer $fontSize The font size
     *
     * @global object $pdf The current PDF document
     *
     * @access private
     *
     * @return void
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
         * table title is affected by the table width value
         */
        while ($this->width < $pdf->GetStringWidth($this->getTitle())) {
            $this->width += 5;
        }
        $pdf->SetFont($this->_ff, '', $fontSize);
    }

    /**
     * Sets the height of the table
     *
     * @return void
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
     * @param integer         $fontSize The font size
     * @param boolean         $withDoc  Whether to include links to documentation
     * @param boolean|integer $setColor Whether to display color
     *
     * @global object $pdf The current PDF document
     *
     * @access public
     *
     * @return void
     *
     * @see PMA_Schema_PDF
     */
    public function tableDraw($fontSize, $withDoc, $setColor = 0)
    {
        global $pdf;

        $pdf->setXyScale($this->x, $this->y);
        $pdf->SetFont($this->_ff, 'B', $fontSize);
        if ($setColor) {
            $pdf->SetTextColor(200);
            $pdf->SetFillColor(0, 0, 128);
        }
        if ($withDoc) {
            $pdf->SetLink($pdf->PMA_links['RT'][$this->tableName]['-'], -1);
        } else {
            $pdf->PMA_links['doc'][$this->tableName]['-'] = '';
        }

        $pdf->cellScale(
            $this->width,
            $this->heightCell,
            $this->getTitle(),
            1,
            1,
            'C',
            $setColor,
            $pdf->PMA_links['doc'][$this->tableName]['-']
        );
        $pdf->setXScale($this->x);
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
                $pdf->SetLink($pdf->PMA_links['RT'][$this->tableName][$field], -1);
            } else {
                $pdf->PMA_links['doc'][$this->tableName][$field] = '';
            }

            $pdf->cellScale(
                $this->width,
                $this->heightCell,
                ' ' . $field,
                1,
                1,
                'L',
                $setColor,
                $pdf->PMA_links['doc'][$this->tableName][$field]
            );
            $pdf->setXScale($this->x);
            $pdf->SetFillColor(255);
        }
    }
}

?>