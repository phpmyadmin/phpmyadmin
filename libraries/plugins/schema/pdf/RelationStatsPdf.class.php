<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains Relation_Stats_Pdf class
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/plugins/schema/RelationStats.class.php';

/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in PDF document.
 *
 * @name    Relation_Stats_Pdf
 * @package PhpMyAdmin
 * @see     PMA_Schema_PDF::SetDrawColor, PMA_Schema_PDF::setLineWidthScale,
 *          PMA_Schema_PDF::lineScale
 */
class Relation_Stats_Pdf extends RealtionStats
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
     * The "Relation_Stats_Pdf" constructor
     *
     * @param string $master_table  The master table name
     * @param string $master_field  The relation field in the master table
     * @param string $foreign_table The foreign table name
     * @param string $foreign_field The relation field in the foreign table
     *
     * @see Relation_Stats_Pdf::_getXy
     */
    function __construct($master_table, $master_field, $foreign_table,
        $foreign_field
    ) {
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
        return array(
            $table->x,
            $table->x + $table->width,
            $table->y + ($pos + 1.5) * $table->heightCell
        );
    }

    /**
     * draws relation links and arrows shows foreign key relations
     *
     * @param boolean $showColor Whether to use one color per relation or not
     * @param integer $i         The id of the link to draw
     *
     * @global object $pdf The current PDF document
     *
     * @access public
     *
     * @return void
     *
     * @see PMA_Schema_PDF
     */
    public function relationDraw($showColor, $i)
    {
        global $pdf;

        if ($showColor) {
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
        $pdf->setLineWidthScale(0.2);
        $pdf->lineScale(
            $this->xSrc,
            $this->ySrc,
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc
        );
        $pdf->lineScale(
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            $this->xDest,
            $this->yDest
        );
        $pdf->setLineWidthScale(0.1);
        $pdf->lineScale(
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest
        );
        /*
         * Draws arrows ->
        */
        $root2 = 2 * sqrt(2);
        $pdf->lineScale(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc + $this->wTick / $root2
        );
        $pdf->lineScale(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc - $this->wTick / $root2
        );

        $pdf->lineScale(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest + $this->wTick / $root2
        );
        $pdf->lineScale(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest - $this->wTick / $root2
        );
        $pdf->SetDrawColor(0);
    }
}
?>