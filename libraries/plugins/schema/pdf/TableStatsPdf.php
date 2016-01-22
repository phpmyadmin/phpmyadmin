<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains PMA\libraries\plugins\schema\pdf\TableStatsPdf class
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\plugins\schema\pdf;

use PMA\libraries\plugins\schema\ExportRelationSchema;
use PMA\libraries\plugins\schema\TableStats;
use PMA\libraries\PDF as PDF_lib;

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
class TableStatsPdf extends TableStats
{
    /**
     * Defines properties
     */
    public $nb_fiels;
    public $height;
    private $_ff = PDF_lib::PMA_PDF_FONT;

    /**
     * The "PMA\libraries\plugins\schema\pdf\TableStatsPdf" constructor
     *
     * @param object  $diagram        The PDF diagram
     * @param string  $db             The database name
     * @param string  $tableName      The table name
     * @param integer $fontSize       The font size
     * @param integer $pageNumber     The current page number (from the
     *                                $cfg['Servers'][$i]['table_coords'] table)
     * @param integer &$sameWideWidth The max. width among tables
     * @param boolean $showKeys       Whether to display keys or not
     * @param boolean $tableDimension Whether to display table position or not
     * @param boolean $offline        Whether the coordinates are sent
     *                                from the browser
     *
     * @see PMA_Schema_PDF, Table_Stats_Pdf::Table_Stats_setWidth,
     *     PMA\libraries\plugins\schema\pdf\TableStatsPdf::Table_Stats_setHeight
     */
    public function __construct(
        $diagram,
        $db,
        $tableName,
        $fontSize,
        $pageNumber,
        &$sameWideWidth,
        $showKeys = false,
        $tableDimension = false,
        $offline = false
    ) {
        parent::__construct(
            $diagram,
            $db,
            $pageNumber,
            $tableName,
            $showKeys,
            $tableDimension,
            $offline
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
        ExportRelationSchema::dieSchema(
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
     * @access private
     *
     * @return void
     *
     * @see    PMA_Schema_PDF
     */
    private function _setWidth($fontSize)
    {
        foreach ($this->fields as $field) {
            $this->width = max($this->width, $this->diagram->GetStringWidth($field));
        }
        $this->width += $this->diagram->GetStringWidth('      ');
        $this->diagram->SetFont($this->_ff, 'B', $fontSize);
        /*
         * it is unknown what value must be added, because
         * table title is affected by the table width value
         */
        while ($this->width < $this->diagram->GetStringWidth($this->getTitle())) {
            $this->width += 5;
        }
        $this->diagram->SetFont($this->_ff, '', $fontSize);
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
     * @access public
     *
     * @return void
     *
     * @see    PMA_Schema_PDF
     */
    public function tableDraw($fontSize, $withDoc, $setColor = 0)
    {
        $this->diagram->setXyScale($this->x, $this->y);
        $this->diagram->SetFont($this->_ff, 'B', $fontSize);
        if ($setColor) {
            $this->diagram->SetTextColor(200);
            $this->diagram->SetFillColor(0, 0, 128);
        }
        if ($withDoc) {
            $this->diagram->SetLink(
                $this->diagram->PMA_links['RT'][$this->tableName]['-'],
                -1
            );
        } else {
            $this->diagram->PMA_links['doc'][$this->tableName]['-'] = '';
        }

        $this->diagram->cellScale(
            $this->width,
            $this->heightCell,
            $this->getTitle(),
            1,
            1,
            'C',
            $setColor,
            $this->diagram->PMA_links['doc'][$this->tableName]['-']
        );
        $this->diagram->setXScale($this->x);
        $this->diagram->SetFont($this->_ff, '', $fontSize);
        $this->diagram->SetTextColor(0);
        $this->diagram->SetFillColor(255);

        foreach ($this->fields as $field) {
            if ($setColor) {
                if (in_array($field, $this->primary)) {
                    $this->diagram->SetFillColor(215, 121, 123);
                }
                if ($field == $this->displayfield) {
                    $this->diagram->SetFillColor(142, 159, 224);
                }
            }
            if ($withDoc) {
                $this->diagram->SetLink(
                    $this->diagram->PMA_links['RT'][$this->tableName][$field],
                    -1
                );
            } else {
                $this->diagram->PMA_links['doc'][$this->tableName][$field] = '';
            }

            $this->diagram->cellScale(
                $this->width,
                $this->heightCell,
                ' ' . $field,
                1,
                1,
                'L',
                $setColor,
                $this->diagram->PMA_links['doc'][$this->tableName][$field]
            );
            $this->diagram->setXScale($this->x);
            $this->diagram->SetFillColor(255);
        }
    }
}

