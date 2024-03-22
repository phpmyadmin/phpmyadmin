<?php
/**
 * Contains abstract class to hold relation preferences/statistics
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use function abs;
use function array_search;
use function min;

/**
 * Relations preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key.
 *
 * @abstract
 */
abstract class RelationStats
{
    public mixed $xSrc;

    public mixed $ySrc;

    public int $srcDir;

    public int $destDir;

    public mixed $xDest;

    public mixed $yDest;

    public int $wTick = 0;

    /**
     * @param object     $diagram      The diagram
     * @param TableStats $masterTable  The master table name
     * @param string     $masterField  The relation field in the master table
     * @param TableStats $foreignTable The foreign table name
     * @param string     $foreignField The relation field in the foreign table
     */
    public function __construct(
        protected object $diagram,
        TableStats $masterTable,
        string $masterField,
        TableStats $foreignTable,
        string $foreignField,
    ) {
        $srcPos = $this->getXy($masterTable, $masterField);
        $destPos = $this->getXy($foreignTable, $foreignField);
        // [0] is x-left
        // [1] is x-right
        // [2] is y
        $srcLeft = $srcPos[0] - $this->wTick;
        $srcRight = $srcPos[1] + $this->wTick;
        $destLeft = $destPos[0] - $this->wTick;
        $destRight = $destPos[1] + $this->wTick;

        $d1 = abs($srcLeft - $destLeft);
        $d2 = abs($srcRight - $destLeft);
        $d3 = abs($srcLeft - $destRight);
        $d4 = abs($srcRight - $destRight);
        $d = min($d1, $d2, $d3, $d4);

        if ($d == $d1) {
            $this->xSrc = $srcPos[0];
            $this->srcDir = -1;
            $this->xDest = $destPos[0];
            $this->destDir = -1;
        } elseif ($d == $d2) {
            $this->xSrc = $srcPos[1];
            $this->srcDir = 1;
            $this->xDest = $destPos[0];
            $this->destDir = -1;
        } elseif ($d == $d3) {
            $this->xSrc = $srcPos[0];
            $this->srcDir = -1;
            $this->xDest = $destPos[1];
            $this->destDir = 1;
        } else {
            $this->xSrc = $srcPos[1];
            $this->srcDir = 1;
            $this->xDest = $destPos[1];
            $this->destDir = 1;
        }

        $this->ySrc = $srcPos[2];
        $this->yDest = $destPos[2];
    }

    /**
     * Gets arrows coordinates
     *
     * @param TableStats $table  The table
     * @param string     $column The relation column name
     *
     * @return mixed[] Arrows coordinates
     */
    private function getXy(TableStats $table, string $column): array
    {
        $pos = array_search($column, $table->fields);

        // x_left, x_right, y
        return [$table->x, $table->x + $table->width, $table->y + ($pos + 1.5) * $table->heightCell];
    }
}
