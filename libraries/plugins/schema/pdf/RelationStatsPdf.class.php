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
class Relation_Stats_Pdf extends RelationStats
{
    /**
     * The "Relation_Stats_Pdf" constructor
     *
     * @param string $master_table  The master table name
     * @param string $master_field  The relation field in the master table
     * @param string $foreign_table The foreign table name
     * @param string $foreign_field The relation field in the foreign table
     */
    function __construct($master_table, $master_field, $foreign_table,
        $foreign_field
    ) {
        $this->wTick = 5;
        parent::__construct(
            $master_table, $master_field, $foreign_table, $foreign_field
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