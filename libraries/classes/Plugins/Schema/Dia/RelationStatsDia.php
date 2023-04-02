<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\Dia\RelationStatsDia class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Dia;

use function array_search;
use function shuffle;

/**
 * Relation preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key
 * in dia XML document.
 */
class RelationStatsDia
{
    public mixed $srcConnPointsRight;

    public mixed $srcConnPointsLeft;

    public mixed $destConnPointsRight;

    public mixed $destConnPointsLeft;

    public int $masterTableId;

    public int $foreignTableId;

    public string $referenceColor = '#000000';

    /**
     * @see Relation_Stats_Dia::getXy
     *
     * @param Dia           $diagram      The DIA diagram
     * @param TableStatsDia $masterTable  The master table name
     * @param string        $masterField  The relation field in the master table
     * @param TableStatsDia $foreignTable The foreign table name
     * @param string        $foreignField The relation field in the foreign table
     */
    public function __construct(
        protected Dia $diagram,
        TableStatsDia $masterTable,
        string $masterField,
        TableStatsDia $foreignTable,
        string $foreignField,
    ) {
        $srcPos = $this->getXy($masterTable, $masterField);
        $destPos = $this->getXy($foreignTable, $foreignField);
        $this->srcConnPointsLeft = $srcPos[0];
        $this->srcConnPointsRight = $srcPos[1];
        $this->destConnPointsLeft = $destPos[0];
        $this->destConnPointsRight = $destPos[1];
        $this->masterTableId = $masterTable->tableId;
        $this->foreignTableId = $foreignTable->tableId;
    }

    /**
     * Each Table object have connection points
     * which is used to connect to other objects in Dia
     * we detect the position of key in fields and
     * then determines its left and right connection
     * points.
     *
     * @param TableStatsDia $table  The current table name
     * @param string        $column The relation column name
     *
     * @return mixed[] Table right,left connection points and key position
     */
    private function getXy(TableStatsDia $table, string $column): array
    {
        $pos = array_search($column, $table->fields);
        // left, right, position
        $value = 12;
        if ($pos != 0) {
            return [$pos + $value + $pos, $pos + $value + $pos + 1, $pos];
        }

        return [$pos + $value, $pos + $value + 1, $pos];
    }

    /**
     * Draws relation references
     *
     * connects master table's master field to foreign table's
     * foreign field using Dia object type Database - Reference
     * Dia object is used to generate the XML of Dia Document.
     * Database reference Object and their attributes are involved
     * in the combination of displaying Database - reference on Dia Document.
     *
     * @see    PDF
     *
     * @param bool $showColor Whether to use one color per relation or not
     *                        if showColor is true then an array of $listOfColors
     *                        will be used to choose the random colors for
     *                        references lines. we can change/add more colors to
     *                        this
     */
    public function relationDraw(bool $showColor): void
    {
        ++DiaRelationSchema::$objectId;
        // if source connection points and destination connection points are same then
        // don't draw that relation
        if ($this->srcConnPointsRight == $this->destConnPointsRight) {
            if ($this->srcConnPointsLeft == $this->destConnPointsLeft) {
                return;
            }
        }

        if ($showColor) {
            $listOfColors = ['FF0000', '000099', '00FF00'];
            shuffle($listOfColors);
            $this->referenceColor = '#' . $listOfColors[0];
        } else {
            $this->referenceColor = '#000000';
        }

        $this->diagram->writeRaw(
            '<dia:object type="Database - Reference" version="0" id="'
            . DiaRelationSchema::$objectId . '">
            <dia:attribute name="obj_pos">
                <dia:point val="3.27,18.9198"/>
            </dia:attribute>
            <dia:attribute name="obj_bb">
                <dia:rectangle val="2.27,8.7175;17.7679,18.9198"/>
            </dia:attribute>
            <dia:attribute name="meta">
                <dia:composite type="dict"/>
            </dia:attribute>
            <dia:attribute name="orth_points">
                <dia:point val="3.27,18.9198"/>
                <dia:point val="2.27,18.9198"/>
                <dia:point val="2.27,14.1286"/>
                <dia:point val="17.7679,14.1286"/>
                <dia:point val="17.7679,9.3375"/>
                <dia:point val="16.7679,9.3375"/>
            </dia:attribute>
            <dia:attribute name="orth_orient">
                <dia:enum val="0"/>
                <dia:enum val="1"/>
                <dia:enum val="0"/>
                <dia:enum val="1"/>
                <dia:enum val="0"/>
            </dia:attribute>
            <dia:attribute name="orth_autoroute">
                <dia:boolean val="true"/>
            </dia:attribute>
            <dia:attribute name="text_colour">
                <dia:color val="#000000"/>
            </dia:attribute>
            <dia:attribute name="line_colour">
                <dia:color val="' . $this->referenceColor . '"/>
            </dia:attribute>
            <dia:attribute name="line_width">
                <dia:real val="0.10000000000000001"/>
            </dia:attribute>
            <dia:attribute name="line_style">
                <dia:enum val="0"/>
                <dia:real val="1"/>
            </dia:attribute>
            <dia:attribute name="corner_radius">
                <dia:real val="0"/>
            </dia:attribute>
            <dia:attribute name="end_arrow">
                <dia:enum val="22"/>
            </dia:attribute>
            <dia:attribute name="end_arrow_length">
                <dia:real val="0.5"/>
            </dia:attribute>
            <dia:attribute name="end_arrow_width">
                <dia:real val="0.5"/>
            </dia:attribute>
            <dia:attribute name="start_point_desc">
                <dia:string>#1#</dia:string>
            </dia:attribute>
            <dia:attribute name="end_point_desc">
                <dia:string>#n#</dia:string>
            </dia:attribute>
            <dia:attribute name="normal_font">
                <dia:font family="monospace" style="0" name="Courier"/>
            </dia:attribute>
            <dia:attribute name="normal_font_height">
                <dia:real val="0.59999999999999998"/>
            </dia:attribute>
            <dia:connections>
                <dia:connection handle="0" to="'
            . $this->masterTableId . '" connection="'
            . $this->srcConnPointsRight . '"/>
                <dia:connection handle="1" to="'
            . $this->foreignTableId . '" connection="'
            . $this->destConnPointsRight . '"/>
            </dia:connections>
            </dia:object>',
        );
    }
}
