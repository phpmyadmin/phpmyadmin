<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains Relation_Stats_Eps class
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
 * in EPS document.
 *
 * @package PhpMyAdmin
 * @name    Relation_Stats_Eps
 * @see     PMA_EPS
 */
class Relation_Stats_Eps extends RelationStats
{
    /**
     * The "Relation_Stats_Eps" constructor
     *
     * @param string $master_table  The master table name
     * @param string $master_field  The relation field in the master table
     * @param string $foreign_table The foreign table name
     * @param string $foreign_field The relation field in the foreign table
     */
    function __construct(
        $master_table, $master_field, $foreign_table, $foreign_field
    ) {
        $this->wTick = 10;
        parent::__construct(
            $master_table, $master_field, $foreign_table, $foreign_field
        );
        $this->ySrc  += 10;
        $this->yDest += 10;
    }

    /**
     * draws relation links and arrows
     * shows foreign key relations
     *
     * @param boolean $showColor Whether to use one color per relation or not
     *
     * @global object $eps The current EPS document
     *
     * @access public
     * @see PMA_EPS
     *
     * @return void
     */
    public function relationDraw($showColor)
    {
        global $eps;

        /* Commented because $color unused.
        if ($showColor) {
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
        }*/
        // draw a line like -- to foreign field
        $eps->line(
            $this->xSrc,
            $this->ySrc,
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            1
        );
        // draw a line like -- to master field
        $eps->line(
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            $this->xDest,
            $this->yDest,
            1
        );
        // draw a line that connects to master field line and foreign field line
        $eps->line(
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            1
        );
        $root2 = 2 * sqrt(2);
        $eps->line(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc + $this->wTick / $root2,
            1
        );
        $eps->line(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc - $this->wTick / $root2,
            1
        );
        $eps->line(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest + $this->wTick / $root2,
            1
        );
        $eps->line(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest - $this->wTick / $root2,
            1
        );
    }
}
?>