<?php
/**
 * Classes to create relation schema in EPS format.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Eps;

use PhpMyAdmin\Plugins\Schema\Dia\TableStatsDia;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Plugins\Schema\Pdf\TableStatsPdf;
use PhpMyAdmin\Plugins\Schema\Svg\TableStatsSvg;
use function date;
use function in_array;
use function sprintf;

/**
 * EPS Relation Schema Class
 *
 * Purpose of this class is to generate the EPS Document
 * which is used for representing the database diagrams.
 * This class uses post script commands and with
 * the combination of these commands actually helps in preparing EPS Document.
 *
 * This class inherits ExportRelationSchema class has common functionality added
 * to this class
 *
 * @name    EpsRelationSchema
 */
class EpsRelationSchema extends ExportRelationSchema
{
    /** @var TableStatsDia[]|TableStatsEps[]|TableStatsPdf[]|TableStatsSvg[] */
    private $tables = [];

    /** @var RelationStatsEps[] Relations */
    private $relations = [];

    /** @var int */
    private $tablewidth;

    /**
     * Upon instantiation This starts writing the EPS document
     * user will be prompted for download as .eps extension
     *
     * @see PMA_EPS
     *
     * @param string $db database name
     */
    public function __construct($db)
    {
        parent::__construct($db, new Eps());

        $this->setShowColor(isset($_REQUEST['eps_show_color']));
        $this->setShowKeys(isset($_REQUEST['eps_show_keys']));
        $this->setTableDimension(isset($_REQUEST['eps_show_table_dimension']));
        $this->setAllTablesSameWidth(isset($_REQUEST['eps_all_tables_same_width']));
        $this->setOrientation((string) $_REQUEST['eps_orientation']);

        $this->diagram->setTitle(
            sprintf(
                __('Schema of the %s database - Page %s'),
                $this->db,
                $this->pageNumber
            )
        );
        $this->diagram->setAuthor('phpMyAdmin ' . PMA_VERSION);
        $this->diagram->setDate(date('j F Y, g:i a'));
        $this->diagram->setOrientation($this->orientation);
        $this->diagram->setFont('Verdana', '10');

        $alltables = $this->getTablesFromRequest();

        foreach ($alltables as $table) {
            if (! isset($this->tables[$table])) {
                $this->tables[$table] = new TableStatsEps(
                    $this->diagram,
                    $this->db,
                    $table,
                    $this->diagram->getFont(),
                    $this->diagram->getFontSize(),
                    $this->pageNumber,
                    $this->tablewidth,
                    $this->showKeys,
                    $this->tableDimension,
                    $this->offline
                );
            }

            if (! $this->sameWide) {
                continue;
            }

            $this->tables[$table]->width = $this->tablewidth;
        }

        $seen_a_relation = false;
        foreach ($alltables as $one_table) {
            $exist_rel = $this->relation->getForeigners($this->db, $one_table, '', 'both');
            if (! $exist_rel) {
                continue;
            }

            $seen_a_relation = true;
            foreach ($exist_rel as $master_field => $rel) {
                /* put the foreign table on the schema only if selected
                * by the user
                * (do not use array_search() because we would have to
                * to do a === false and this is not PHP3 compatible)
                */
                if ($master_field !== 'foreign_keys_data') {
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->addRelation(
                            $one_table,
                            $this->diagram->getFont(),
                            $this->diagram->getFontSize(),
                            $master_field,
                            $rel['foreign_table'],
                            $rel['foreign_field'],
                            $this->tableDimension
                        );
                    }
                    continue;
                }

                foreach ($rel as $one_key) {
                    if (! in_array($one_key['ref_table_name'], $alltables)) {
                        continue;
                    }

                    foreach ($one_key['index_list'] as $index => $one_field) {
                        $this->addRelation(
                            $one_table,
                            $this->diagram->getFont(),
                            $this->diagram->getFontSize(),
                            $one_field,
                            $one_key['ref_table_name'],
                            $one_key['ref_index_list'][$index],
                            $this->tableDimension
                        );
                    }
                }
            }
        }
        if ($seen_a_relation) {
            $this->drawRelations();
        }

        $this->drawTables();
        $this->diagram->endEpsDoc();
    }

    /**
     * Output Eps Document for download
     *
     * @return void
     */
    public function showOutput()
    {
        $this->diagram->showOutput($this->getFileName('.eps'));
    }

    /**
     * Defines relation objects
     *
     * @see _setMinMax
     * @see TableStatsEps::__construct()
     * @see PhpMyAdmin\Plugins\Schema\Eps\RelationStatsEps::__construct()
     *
     * @param string $masterTable    The master table name
     * @param string $font           The font
     * @param int    $fontSize       The font size
     * @param string $masterField    The relation field in the master table
     * @param string $foreignTable   The foreign table name
     * @param string $foreignField   The relation field in the foreign table
     * @param bool   $tableDimension Whether to display table position or not
     *
     * @return void
     */
    private function addRelation(
        $masterTable,
        $font,
        $fontSize,
        $masterField,
        $foreignTable,
        $foreignField,
        $tableDimension
    ) {
        if (! isset($this->tables[$masterTable])) {
            $this->tables[$masterTable] = new TableStatsEps(
                $this->diagram,
                $this->db,
                $masterTable,
                $font,
                $fontSize,
                $this->pageNumber,
                $this->tablewidth,
                false,
                $tableDimension
            );
        }
        if (! isset($this->tables[$foreignTable])) {
            $this->tables[$foreignTable] = new TableStatsEps(
                $this->diagram,
                $this->db,
                $foreignTable,
                $font,
                $fontSize,
                $this->pageNumber,
                $this->tablewidth,
                false,
                $tableDimension
            );
        }
        $this->relations[] = new RelationStatsEps(
            $this->diagram,
            $this->tables[$masterTable],
            $masterField,
            $this->tables[$foreignTable],
            $foreignField
        );
    }

    /**
     * Draws relation arrows and lines connects master table's master field to
     * foreign table's foreign field
     *
     * @see RelationStatsEps::relationDraw()
     *
     * @return void
     */
    private function drawRelations()
    {
        foreach ($this->relations as $relation) {
            $relation->relationDraw();
        }
    }

    /**
     * Draws tables
     *
     * @see TableStatsEps::Table_Stats_tableDraw()
     *
     * @return void
     */
    private function drawTables()
    {
        foreach ($this->tables as $table) {
            $table->tableDraw($this->showColor);
        }
    }
}
