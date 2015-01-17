<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains Table_Stats_Svg class
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
 * and helps in drawing/generating the Tables in SVG XML document.
 *
 * @package PhpMyAdmin
 * @name    Table_Stats_Svg
 * @see     PMA_SVG
 */
class Table_Stats_Svg extends TableStats
{
    /**
     * Defines properties
     */
    public $height;
    public $currentCell = 0;

    /**
     * The "Table_Stats_Svg" constructor
     *
     * @param string  $tableName        The table name
     * @param string  $font             Font face
     * @param integer $fontSize         The font size
     * @param integer $pageNumber       Page number
     * @param integer &$same_wide_width The max. with among tables
     * @param boolean $showKeys         Whether to display keys or not
     * @param boolean $tableDimension   Whether to display table position or not
     * @param boolean $offline          Whether the coordinates are sent
     *                                  from the browser
     *
     * @global object  $svg         The current SVG image document
     *
     * @access private
     *
     * @see PMA_SVG, Table_Stats_Svg::Table_Stats_setWidth,
     *       Table_Stats_Svg::Table_Stats_setHeight
     */
    function __construct(
        $tableName, $font, $fontSize, $pageNumber, &$same_wide_width,
        $showKeys = false, $tableDimension = false, $offline = false
    ) {
        global $svg;
        parent::__construct(
            $svg, $GLOBALS['db'], $pageNumber, $tableName,
            $showKeys, $tableDimension, $offline
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
        PMA_Export_Relation_Schema::dieSchema(
            $this->pageNumber,
            "SVG",
            sprintf(__('The %s table doesn\'t exist!'), $this->tableName)
        );
    }

    /**
     * Displays an error on missing coordinates
     *
     * @return void
     */
    protected function showMissingCoordinatesError()
    {
        PMA_Export_Relation_Schema::dieSchema(
            $this->pageNumber,
            "SVG",
            sprintf(
                __('Please configure the coordinates for table %s'),
                $this->tableName
            )
        );
    }

    /**
     * Sets the width of the table
     *
     * @param string  $font     The font size
     * @param integer $fontSize The font size
     *
     * @global object $svg The current SVG image document
     *
     * @return void
     * @access private
     *
     * @see PMA_SVG
     */
    private function _setWidthTable($font,$fontSize)
    {
        foreach ($this->fields as $field) {
            $this->width = max(
                $this->width,
                PMA_Font::getStringWidth($field, $font, $fontSize)
            );
        }
        $this->width += PMA_Font::getStringWidth('  ', $font, $fontSize);

        /*
         * it is unknown what value must be added, because
        * table title is affected by the tabe width value
        */
        while ($this->width
            < PMA_Font::getStringWidth($this->getTitle(), $font, $fontSize)
        ) {
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
     * @global object $svg The current SVG image document
     *
     * @access public
     * @return void
     *
     * @see PMA_SVG,PMA_SVG::printElement
     */
    public function tableDraw($showColor)
    {
        global $svg;

        $svg->printElement(
            'rect', $this->x, $this->y, $this->width,
            $this->heightCell, null, 'fill:red;stroke:black;'
        );
        $svg->printElement(
            'text', $this->x + 5, $this->y+ 14, $this->width, $this->heightCell,
            $this->getTitle(), 'fill:none;stroke:black;'
        );
        foreach ($this->fields as $field) {
            $this->currentCell += $this->heightCell;
            $fillColor    = 'none';
            if ($showColor) {
                if (in_array($field, $this->primary)) {
                    $fillColor = '#0c0';
                }
                if ($field == $this->displayfield) {
                    $fillColor = 'none';
                }
            }
            $svg->printElement(
                'rect', $this->x, $this->y + $this->currentCell, $this->width,
                $this->heightCell, null, 'fill:' . $fillColor . ';stroke:black;'
            );
            $svg->printElement(
                'text', $this->x + 5, $this->y + 14 + $this->currentCell,
                $this->width, $this->heightCell, $field, 'fill:none;stroke:black;'
            );
        }
    }
}
?>