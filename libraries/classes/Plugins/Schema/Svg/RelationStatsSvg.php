<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\Svg\RelationStatsSvg class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Svg;

use PhpMyAdmin\Plugins\Schema\RelationStats;
use PhpMyAdmin\Plugins\Schema\TableStats;

use function shuffle;
use function sqrt;

/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in SVG XML document.
 *
 * @see     Svg::printElementLine
 */
class RelationStatsSvg extends RelationStats
{
    /**
     * @param Svg        $diagram      The SVG diagram
     * @param TableStats $masterTable  The master table name
     * @param string     $masterField  The relation field in the master table
     * @param TableStats $foreignTable The foreign table name
     * @param string     $foreignField The relation field in the foreign table
     */
    public function __construct(
        Svg $diagram,
        TableStats $masterTable,
        string $masterField,
        TableStats $foreignTable,
        string $foreignField,
    ) {
        $this->wTick = 10;

        parent::__construct($diagram, $masterTable, $masterField, $foreignTable, $foreignField);
    }

    /**
     * draws relation links and arrows shows foreign key relations
     *
     * @see    PMA_SVG
     *
     * @param bool $showColor Whether to use one color per relation or not
     */
    public function relationDraw(bool $showColor): void
    {
        if ($showColor) {
            $listOfColors = ['#c00', '#bbb', '#333', '#cb0', '#0b0', '#0bf', '#b0b'];
            shuffle($listOfColors);
            $color = $listOfColors[0];
        } else {
            $color = '#333';
        }

        $this->diagram->printElementLine(
            'line',
            $this->xSrc,
            $this->ySrc,
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            'stroke:' . $color . ';stroke-width:1;',
        );
        $this->diagram->printElementLine(
            'line',
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            $this->xDest,
            $this->yDest,
            'stroke:' . $color . ';stroke-width:1;',
        );
        $this->diagram->printElementLine(
            'line',
            $this->xSrc + $this->srcDir * $this->wTick,
            $this->ySrc,
            $this->xDest + $this->destDir * $this->wTick,
            $this->yDest,
            'stroke:' . $color . ';stroke-width:1;',
        );
        $root2 = 2 * sqrt(2);
        $this->diagram->printElementLine(
            'line',
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc + $this->wTick / $root2,
            'stroke:' . $color . ';stroke-width:2;',
        );
        $this->diagram->printElementLine(
            'line',
            $this->xSrc + $this->srcDir * $this->wTick * 0.75,
            $this->ySrc,
            $this->xSrc + $this->srcDir * (0.75 - 1 / $root2) * $this->wTick,
            $this->ySrc - $this->wTick / $root2,
            'stroke:' . $color . ';stroke-width:2;',
        );
        $this->diagram->printElementLine(
            'line',
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest + $this->wTick / $root2,
            'stroke:' . $color . ';stroke-width:2;',
        );
        $this->diagram->printElementLine(
            'line',
            $this->xDest + $this->destDir * $this->wTick / 2,
            $this->yDest,
            $this->xDest + $this->destDir * (0.5 + 1 / $root2) * $this->wTick,
            $this->yDest - $this->wTick / $root2,
            'stroke:' . $color . ';stroke-width:2;',
        );
    }
}
