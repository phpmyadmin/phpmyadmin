<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains PhpMyAdmin\Plugins\Schema\Eps\TableStatsEps class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins\Schema\Eps;

use PhpMyAdmin\Font;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Plugins\Schema\TableStats;

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in EPS.
 *
 * @package PhpMyAdmin
 * @name    Table_Stats_Eps
 * @see     PMA_EPS
 */
class TableStatsEps extends TableStats
{
    /**
     * Defines properties
     */
    public $height;
    public $currentCell = 0;

    /**
     * The "PhpMyAdmin\Plugins\Schema\Eps\TableStatsEps" constructor
     *
     * @param object  $diagram          The EPS diagram
     * @param string  $db               The database name
     * @param string  $tableName        The table name
     * @param string  $font             The font  name
     * @param integer $fontSize         The font size
     * @param integer $pageNumber       Page number
     * @param integer &$same_wide_width The max width among tables
     * @param boolean $showKeys         Whether to display keys or not
     * @param boolean $tableDimension   Whether to display table position or not
     * @param boolean $offline          Whether the coordinates are sent
     *                                  from the browser
     *
     * @see PMA_EPS, Table_Stats_Eps::Table_Stats_setWidth,
     *      PhpMyAdmin\Plugins\Schema\Eps\TableStatsEps::Table_Stats_setHeight
     */
    public function __construct(
        $diagram,
        $db,
        $tableName,
        $font,
        $fontSize,
        $pageNumber,
        &$same_wide_width,
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
        ExportRelationSchema::dieSchema(
            $this->pageNumber,
            "EPS",
            sprintf(__('The %s table doesn\'t exist!'), $this->tableName)
        );
    }

    /**
     * Sets the width of the table
     *
     * @param string  $font     The font name
     * @param integer $fontSize The font size
     *
     * @return void
     *
     * @see PMA_EPS
     */
    private function _setWidthTable($font, $fontSize)
    {
        foreach ($this->fields as $field) {
            $this->width = max(
                $this->width,
                Font::getStringWidth($field, $font, $fontSize)
            );
        }
        $this->width += Font::getStringWidth(
            '      ',
            $font,
            $fontSize
        );
        /*
         * it is unknown what value must be added, because
        * table title is affected by the table width value
        */
        while ($this->width
            < Font::getStringWidth(
                $this->getTitle(),
                $font,
                $fontSize
            )) {
            $this->width += 7;
        }
    }

    /**
     * Sets the height of the table
     *
     * @param integer $fontSize The font size
     *
     * @return void
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
     * @return void
     *
     * @see PMA_EPS,PMA_EPS::line,PMA_EPS::rect
     */
    public function tableDraw($showColor)
    {
        //echo $this->tableName.'<br />';
        $this->diagram->rect(
            $this->x,
            $this->y + 12,
            $this->width,
            $this->heightCell,
            1
        );
        $this->diagram->showXY($this->getTitle(), $this->x + 5, $this->y + 14);
        foreach ($this->fields as $field) {
            $this->currentCell += $this->heightCell;
            $this->diagram->rect(
                $this->x,
                $this->y + 12 + $this->currentCell,
                $this->width,
                $this->heightCell,
                1
            );
            $this->diagram->showXY(
                $field,
                $this->x + 5,
                $this->y + 14 + $this->currentCell
            );
        }
    }
}
