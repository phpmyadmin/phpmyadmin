<?php
/**
 * Classes to create relation schema in EPS format.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Eps;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Version;

use function __;
use function date;
use function in_array;
use function max;
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
 * @extends ExportRelationSchema<Eps>
 */
class EpsRelationSchema extends ExportRelationSchema
{
    /** @var TableStatsEps[] */
    private array $tables = [];

    /** @var RelationStatsEps[] Relations */
    private array $relations = [];

    private int|float $tablewidth = 0;

    /**
     * Upon instantiation This starts writing the EPS document
     * user will be prompted for download as .eps extension
     *
     * @see Eps
     */
    public function __construct(DatabaseName $db)
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
                $this->db->getName(),
                $this->pageNumber,
            ),
        );
        $this->diagram->setAuthor('phpMyAdmin ' . Version::VERSION);
        $this->diagram->setDate(date('j F Y, g:i a'));
        $this->diagram->setOrientation($this->orientation);
        $this->diagram->setFont('Verdana', 10);

        $alltables = $this->getTablesFromRequest();

        foreach ($alltables as $table) {
            if (! isset($this->tables[$table])) {
                $this->tables[$table] = new TableStatsEps(
                    $this->diagram,
                    $this->db->getName(),
                    $table,
                    $this->diagram->getFont(),
                    $this->diagram->getFontSize(),
                    $this->pageNumber,
                    $this->showKeys,
                    $this->tableDimension,
                    $this->offline,
                );
                $this->tablewidth = max($this->tablewidth, $this->tables[$table]->width);
            }

            if (! $this->sameWide) {
                continue;
            }

            $this->tables[$table]->width = $this->tablewidth;
        }

        $seenARelation = false;
        foreach ($alltables as $oneTable) {
            $existRel = $this->relation->getForeigners($this->db->getName(), $oneTable, '', 'both');
            if (! $existRel) {
                continue;
            }

            $seenARelation = true;
            foreach ($existRel as $masterField => $rel) {
                /* put the foreign table on the schema only if selected
                * by the user
                * (do not use array_search() because we would have to
                * to do a === false and this is not PHP3 compatible)
                */
                if ($masterField !== 'foreign_keys_data') {
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->addRelation(
                            $oneTable,
                            $this->diagram->getFont(),
                            $this->diagram->getFontSize(),
                            $masterField,
                            $rel['foreign_table'],
                            $rel['foreign_field'],
                            $this->tableDimension,
                        );
                    }

                    continue;
                }

                foreach ($rel as $oneKey) {
                    if (! in_array($oneKey['ref_table_name'], $alltables)) {
                        continue;
                    }

                    foreach ($oneKey['index_list'] as $index => $oneField) {
                        $this->addRelation(
                            $oneTable,
                            $this->diagram->getFont(),
                            $this->diagram->getFontSize(),
                            $oneField,
                            $oneKey['ref_table_name'],
                            $oneKey['ref_index_list'][$index],
                            $this->tableDimension,
                        );
                    }
                }
            }
        }

        if ($seenARelation) {
            $this->drawRelations();
        }

        $this->drawTables();
        $this->diagram->endEpsDoc();
    }

    /** @return array{fileName: non-empty-string, fileData: string} */
    public function getExportInfo(): array
    {
        return ['fileName' => $this->getFileName('.eps'), 'fileData' => $this->diagram->getOutputData()];
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
     */
    private function addRelation(
        string $masterTable,
        string $font,
        int $fontSize,
        string $masterField,
        string $foreignTable,
        string $foreignField,
        bool $tableDimension,
    ): void {
        if (! isset($this->tables[$masterTable])) {
            $this->tables[$masterTable] = new TableStatsEps(
                $this->diagram,
                $this->db->getName(),
                $masterTable,
                $font,
                $fontSize,
                $this->pageNumber,
                false,
                $tableDimension,
            );
            $this->tablewidth = max($this->tablewidth, $this->tables[$masterTable]->width);
        }

        if (! isset($this->tables[$foreignTable])) {
            $this->tables[$foreignTable] = new TableStatsEps(
                $this->diagram,
                $this->db->getName(),
                $foreignTable,
                $font,
                $fontSize,
                $this->pageNumber,
                false,
                $tableDimension,
            );
            $this->tablewidth = max($this->tablewidth, $this->tables[$foreignTable]->width);
        }

        $this->relations[] = new RelationStatsEps(
            $this->diagram,
            $this->tables[$masterTable],
            $masterField,
            $this->tables[$foreignTable],
            $foreignField,
        );
    }

    /**
     * Draws relation arrows and lines connects master table's master field to
     * foreign table's foreign field
     *
     * @see RelationStatsEps::relationDraw()
     */
    private function drawRelations(): void
    {
        foreach ($this->relations as $relation) {
            $relation->relationDraw();
        }
    }

    /**
     * Draws tables
     */
    private function drawTables(): void
    {
        foreach ($this->tables as $table) {
            $table->tableDraw();
        }
    }
}
