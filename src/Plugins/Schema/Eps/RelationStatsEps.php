<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\Eps\RelationStatsEps class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Eps;

use PhpMyAdmin\Plugins\Schema\RelationStats;
use PhpMyAdmin\Plugins\Schema\TableStats;

use function sqrt;

/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in EPS document.
 *
 * @see     Eps
 */
class RelationStatsEps extends RelationStats
{
    /**
     * @param Eps        $diagram      The EPS diagram
     * @param TableStats $masterTable  The master table name
     * @param string     $masterField  The relation field in the master table
     * @param TableStats $foreignTable The foreign table name
     * @param string     $foreignField The relation field in the foreign table
     */
    public function __construct(
        Eps $diagram,
        TableStats $masterTable,
        string $masterField,
        TableStats $foreignTable,
        string $foreignField,
    ) {
        $this->wTick = 10;

        parent::__construct($diagram, $masterTable, $masterField, $foreignTable, $foreignField);

        $this->ySrc += 10;
        $this->yDest += 10;
    }

    /**
     * draws relation links and arrows
     * shows foreign key relations
     *
     * @see Eps
     */
    public function relationDraw(): void
    {
        // draw a line like -- to foreign field
        $this->diagram->line($this->xSrc, $this->ySrc, $this->xSrc + $this->srcDir * $this->wTick, $this->ySrc, 1);
        // draw a line like -- to master field
        $this->diagram->line($this->xDest + $this->destDir * $this->wTick, $this->yDest, $this->xDest, $this->yDest, 1);
        // draw a line that connects to master field line and foreign field line
        $this->diagram->line(
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            1,
        );
        $root2 = 2 * sqrt(2);
        $this->diagram->line(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc + $this->wTick / $root2,
            1,
        );
        $this->diagram->line(
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc - $this->wTick / $root2,
            1,
        );
        $this->diagram->line(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest + $this->wTick / $root2,
            1,
        );
        $this->diagram->line(
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest - $this->wTick / $root2,
            1,
        );
    }
}
