<?php
/**
 * PDF schema handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Pdf;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Pdf as PdfLib;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function __;
use function ceil;
use function getcwd;
use function in_array;
use function intval;
use function max;
use function min;
use function rsort;
use function sort;
use function sprintf;
use function str_replace;
use function strtotime;

// phpcs:disable PSR1.Files.SideEffects
/**
 * block attempts to directly run this script
 */
if (getcwd() == __DIR__) {
    die('Attack stopped');
}

// phpcs:enable

/**
 * Pdf Relation Schema Class
 *
 * Purpose of this class is to generate the PDF Document. PDF is widely
 * used format for documenting text,fonts,images and 3d vector graphics.
 *
 * This class inherits ExportRelationSchema class has common functionality added
 * to this class
 *
 * @extends ExportRelationSchema<Pdf>
 */
class PdfRelationSchema extends ExportRelationSchema
{
    private bool $showGrid = false;

    private bool $withDoc = false;

    private string $tableOrder = '';

    /** @var TableStatsPdf[] */
    private array $tables = [];

    private string $ff = PdfLib::PMA_PDF_FONT;

    private int|float $xMax = 0;

    private int|float $yMax = 0;

    private float|int $scale;

    private int|float $xMin = 100000;

    private int|float $yMin = 100000;

    private int $topMargin = 10;

    private int $bottomMargin = 10;

    private int $leftMargin = 10;

    private int $rightMargin = 10;

    private int|float $tablewidth = 0;

    /** @var RelationStatsPdf[] */
    protected array $relations = [];

    private Transformations $transformations;

    /** @see Pdf */
    public function __construct(DatabaseName $db)
    {
        $this->transformations = new Transformations();

        $this->setShowGrid(isset($_REQUEST['pdf_show_grid']));
        $this->setShowColor(isset($_REQUEST['pdf_show_color']));
        $this->setShowKeys(isset($_REQUEST['pdf_show_keys']));
        $this->setTableDimension(isset($_REQUEST['pdf_show_table_dimension']));
        $this->setAllTablesSameWidth(isset($_REQUEST['pdf_all_tables_same_width']));
        $this->setWithDataDictionary(isset($_REQUEST['pdf_with_doc']));
        $this->setTableOrder($_REQUEST['pdf_table_order']);
        $this->setOrientation((string) $_REQUEST['pdf_orientation']);
        $this->setPaper((string) $_REQUEST['pdf_paper']);

        // Initializes a new document
        parent::__construct(
            $db,
            new Pdf(
                $this->orientation,
                'mm',
                $this->paper,
                $this->pageNumber,
                $this->withDoc,
                $db->getName(),
            ),
        );

        $this->diagram->setTitle(
            sprintf(
                __('Schema of the %s database'),
                $this->db->getName(),
            ),
        );
        $this->diagram->setCMargin(0);
        $this->diagram->Open();
        $this->diagram->setAutoPageBreak(true);
        $this->diagram->setOffline($this->offline);

        $alltables = $this->getTablesFromRequest();
        if ($this->getTableOrder() === 'name_asc') {
            sort($alltables);
        } elseif ($this->getTableOrder() === 'name_desc') {
            rsort($alltables);
        }

        if ($this->withDoc) {
            $this->diagram->setAutoPageBreak(true, 15);
            $this->diagram->setCMargin(1);
            $this->dataDictionaryDoc($alltables);
            $this->diagram->setAutoPageBreak(true);
            $this->diagram->setCMargin(0);
        }

        $this->diagram->AddPage();

        if ($this->withDoc) {
            $this->diagram->setLink($this->diagram->customLinks['RT']['-'], -1);
            $this->diagram->Bookmark(__('Relational schema'));
            $this->diagram->setAlias('{00}', $this->diagram->PageNo());
            $this->topMargin = 28;
            $this->bottomMargin = 28;
        }

        /* snip */
        foreach ($alltables as $table) {
            if (! isset($this->tables[$table])) {
                $this->tables[$table] = new TableStatsPdf(
                    $this->diagram,
                    $this->db->getName(),
                    $table,
                    null,
                    $this->pageNumber,
                    $this->showKeys,
                    $this->tableDimension,
                    $this->offline,
                );
                $this->tablewidth = max($this->tablewidth, $this->tables[$table]->width);
            }

            if ($this->sameWide) {
                $this->tables[$table]->width = $this->tablewidth;
            }

            $this->setMinMax($this->tables[$table]);
        }

        // Defines the scale factor
        $innerWidth = $this->diagram->getPageWidth() - $this->rightMargin
            - $this->leftMargin;
        $innerHeight = $this->diagram->getPageHeight() - $this->topMargin
            - $this->bottomMargin;
        $this->scale = ceil(
            max(
                ($this->xMax - $this->xMin) / $innerWidth,
                ($this->yMax - $this->yMin) / $innerHeight,
            ) * 100,
        ) / 100;

        $this->diagram->setScale($this->scale, $this->xMin, $this->yMin, $this->leftMargin, $this->topMargin);
        // Builds and save the PDF document
        $this->diagram->setLineWidthScale(0.1);

        if ($this->showGrid) {
            $this->diagram->setFontSize(10);
            $this->strokeGrid();
        }

        $this->diagram->setFontSizeScale(14);
        // previous logic was checking master tables and foreign tables
        // but I think that looping on every table of the pdf page as a master
        // and finding its foreigns is OK (then we can support innodb)
        $seenARelation = false;
        foreach ($alltables as $oneTable) {
            $existRel = $this->relation->getForeigners($this->db->getName(), $oneTable, '', 'both');
            if (! $existRel) {
                continue;
            }

            $seenARelation = true;
            foreach ($existRel as $masterField => $rel) {
                // put the foreign table on the schema only if selected
                // by the user
                // (do not use array_search() because we would have to
                // to do a === false and this is not PHP3 compatible)
                if ($masterField !== 'foreign_keys_data') {
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->addRelation($oneTable, $masterField, $rel['foreign_table'], $rel['foreign_field']);
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
                            $oneField,
                            $oneKey['ref_table_name'],
                            $oneKey['ref_index_list'][$index],
                        );
                    }
                }
            }
        }

        if ($seenARelation) {
            $this->drawRelations();
        }

        $this->drawTables();
    }

    /**
     * Set Show Grid
     *
     * @param bool $value show grid of the document or not
     */
    public function setShowGrid(bool $value): void
    {
        $this->showGrid = $value;
    }

    /**
     * Returns whether to show grid
     */
    public function isShowGrid(): bool
    {
        return $this->showGrid;
    }

    /**
     * Set Data Dictionary
     *
     * @param bool $value show selected database data dictionary or not
     */
    public function setWithDataDictionary(bool $value): void
    {
        $this->withDoc = $value;
    }

    /**
     * Return whether to show selected database data dictionary or not
     */
    public function isWithDataDictionary(): bool
    {
        return $this->withDoc;
    }

    /**
     * Sets the order of the table in data dictionary
     *
     * @param string $value table order
     */
    public function setTableOrder(string $value): void
    {
        $this->tableOrder = $value;
    }

    /**
     * Returns the order of the table in data dictionary
     *
     * @return string table order
     */
    public function getTableOrder(): string
    {
        return $this->tableOrder;
    }

    /** @return array{fileName: non-empty-string, fileData: string} */
    public function getExportInfo(): array
    {
        return ['fileName' => $this->getFileName('.pdf'), 'fileData' => $this->diagram->getOutputData()];
    }

    /**
     * Sets X and Y minimum and maximum for a table cell
     *
     * @param TableStatsPdf $table The table name of which sets XY co-ordinates
     */
    private function setMinMax(TableStatsPdf $table): void
    {
        $this->xMax = max($this->xMax, $table->x + $table->width);
        $this->yMax = max($this->yMax, $table->y + $table->height);
        $this->xMin = min($this->xMin, $table->x);
        $this->yMin = min($this->yMin, $table->y);
    }

    /**
     * Defines relation objects
     *
     * @see setMinMax
     *
     * @param string $masterTable  The master table name
     * @param string $masterField  The relation field in the master table
     * @param string $foreignTable The foreign table name
     * @param string $foreignField The relation field in the foreign table
     */
    private function addRelation(
        string $masterTable,
        string $masterField,
        string $foreignTable,
        string $foreignField,
    ): void {
        if (! isset($this->tables[$masterTable])) {
            $this->tables[$masterTable] = new TableStatsPdf(
                $this->diagram,
                $this->db->getName(),
                $masterTable,
                null,
                $this->pageNumber,
                $this->showKeys,
                $this->tableDimension,
            );
            $this->tablewidth = max($this->tablewidth, $this->tables[$masterTable]->width);
            $this->setMinMax($this->tables[$masterTable]);
        }

        if (! isset($this->tables[$foreignTable])) {
            $this->tables[$foreignTable] = new TableStatsPdf(
                $this->diagram,
                $this->db->getName(),
                $foreignTable,
                null,
                $this->pageNumber,
                $this->showKeys,
                $this->tableDimension,
            );
            $this->tablewidth = max($this->tablewidth, $this->tables[$foreignTable]->width);
            $this->setMinMax($this->tables[$foreignTable]);
        }

        $this->relations[] = new RelationStatsPdf(
            $this->diagram,
            $this->tables[$masterTable],
            $masterField,
            $this->tables[$foreignTable],
            $foreignField,
        );
    }

    /**
     * Draws the grid
     *
     * @see PMA_Schema_PDF
     */
    private function strokeGrid(): void
    {
        $gridSize = 10;
        $labelHeight = 4;
        $labelWidth = 5;
        if ($this->withDoc) {
            $topSpace = 6;
            $bottomSpace = 15;
        } else {
            $topSpace = 0;
            $bottomSpace = 0;
        }

        $this->diagram->setMargins(0, 0);
        $this->diagram->setDrawColor(200, 200, 200);
        // Draws horizontal lines
        $innerHeight = $this->diagram->getPageHeight() - $topSpace - $bottomSpace;
        for ($l = 0, $size = intval($innerHeight / $gridSize); $l <= $size; $l++) {
            $this->diagram->line(
                0,
                $l * $gridSize + $topSpace,
                $this->diagram->getPageWidth(),
                $l * $gridSize + $topSpace,
            );
            // Avoid duplicates
            if ($l <= 0 || $l > intval(($innerHeight - $labelHeight) / $gridSize)) {
                continue;
            }

            $this->diagram->setXY(0, $l * $gridSize + $topSpace);
            $label = sprintf('%.0f', ($l * $gridSize + $topSpace - $this->topMargin) * $this->scale + $this->yMin);
            $this->diagram->Cell($labelWidth, $labelHeight, ' ' . $label);
        }

        // Draws vertical lines
        for ($j = 0, $size = intval($this->diagram->getPageWidth() / $gridSize); $j <= $size; $j++) {
            $this->diagram->line(
                $j * $gridSize,
                $topSpace,
                $j * $gridSize,
                $this->diagram->getPageHeight() - $bottomSpace,
            );
            $this->diagram->setXY($j * $gridSize, $topSpace);
            $label = sprintf('%.0f', ($j * $gridSize - $this->leftMargin) * $this->scale + $this->xMin);
            $this->diagram->Cell($labelWidth, $labelHeight, $label);
        }
    }

    /**
     * Draws relation arrows
     *
     * @see Relation_Stats_Pdf::relationdraw()
     */
    private function drawRelations(): void
    {
        $i = 0;
        foreach ($this->relations as $relation) {
            $relation->relationDraw($this->showColor, $i);
            $i++;
        }
    }

    /**
     * Draws tables
     *
     * @see TableStatsPdf::tableDraw()
     */
    private function drawTables(): void
    {
        foreach ($this->tables as $table) {
            $table->tableDraw(null, $this->withDoc, $this->showColor);
        }
    }

    /**
     * Generates data dictionary pages.
     *
     * @param mixed[] $alltables Tables to document.
     */
    public function dataDictionaryDoc(array $alltables): void
    {
        // TOC
        $this->diagram->AddPage($this->orientation);
        $this->diagram->Cell(0, 9, __('Table of contents'), 1, 0, 'C');
        $this->diagram->Ln(15);
        $i = 1;
        foreach ($alltables as $table) {
            $this->diagram->customLinks['doc'][$table]['-'] = $this->diagram->AddLink();
            $this->diagram->setX(10);
            // $this->diagram->Ln(1);
            $this->diagram->Cell(
                0,
                6,
                __('Page number:') . ' {' . sprintf('%02d', $i) . '}',
                0,
                0,
                'R',
                false,
                $this->diagram->customLinks['doc'][$table]['-'],
            );
            $this->diagram->setX(10);
            $this->diagram->Cell(
                0,
                6,
                $i . ' ' . $table,
                0,
                1,
                'L',
                false,
                $this->diagram->customLinks['doc'][$table]['-'],
            );
            // $this->diagram->Ln(1);
            $fields = $GLOBALS['dbi']->getColumns($this->db->getName(), $table);
            foreach ($fields as $row) {
                $this->diagram->setX(20);
                $fieldName = $row['Field'];
                $this->diagram->customLinks['doc'][$table][$fieldName] = $this->diagram->AddLink();
            }

            $i++;
        }

        $this->diagram->customLinks['RT']['-'] = $this->diagram->AddLink();
        $this->diagram->setX(10);
        $this->diagram->Cell(
            0,
            6,
            __('Page number:') . ' {00}',
            0,
            0,
            'R',
            false,
            $this->diagram->customLinks['RT']['-'],
        );
        $this->diagram->setX(10);
        $this->diagram->Cell(
            0,
            6,
            $i . ' ' . __('Relational schema'),
            0,
            1,
            'L',
            false,
            $this->diagram->customLinks['RT']['-'],
        );
        $z = 0;
        foreach ($alltables as $table) {
            $z++;
            $this->diagram->setAutoPageBreak(true, 15);
            $this->diagram->AddPage($this->orientation);
            $this->diagram->Bookmark($table);
            $this->diagram->setAlias(
                '{' . sprintf('%02d', $z) . '}',
                $this->diagram->PageNo(),
            );
            $this->diagram->customLinks['RT'][$table]['-'] = $this->diagram->AddLink();
            $this->diagram->setLink($this->diagram->customLinks['doc'][$table]['-'], -1);
            $this->diagram->setFont($this->ff, 'B', 18);
            $this->diagram->Cell(
                0,
                8,
                $z . ' ' . $table,
                1,
                1,
                'C',
                false,
                $this->diagram->customLinks['RT'][$table]['-'],
            );
            $this->diagram->setFont($this->ff, '', 8);
            $this->diagram->Ln();

            $relationParameters = $this->relation->getRelationParameters();
            $comments = $this->relation->getComments($this->db->getName(), $table);
            if ($relationParameters->browserTransformationFeature !== null) {
                $mimeMap = $this->transformations->getMime($this->db->getName(), $table, true);
            }

            /**
             * Gets table information
             */
            $showTable = $GLOBALS['dbi']->getTable($this->db->getName(), $table)
                ->getStatusInfo();
            $showComment = $showTable['Comment'] ?? '';
            $createTime = isset($showTable['Create_time'])
                ? Util::localisedDate(
                    strtotime($showTable['Create_time']),
                )
                : '';
            $updateTime = isset($showTable['Update_time'])
                ? Util::localisedDate(
                    strtotime($showTable['Update_time']),
                )
                : '';
            $checkTime = isset($showTable['Check_time'])
                ? Util::localisedDate(
                    strtotime($showTable['Check_time']),
                )
                : '';

            /**
             * Gets fields properties
             */
            $columns = $GLOBALS['dbi']->getColumns($this->db->getName(), $table);

            // Find which tables are related with the current one and write it in
            // an array
            $resRel = $this->relation->getForeigners($this->db->getName(), $table);

            /**
             * Displays the comments of the table if MySQL >= 3.23
             */

            $break = false;
            if (! empty($showComment)) {
                $this->diagram->Cell(
                    0,
                    3,
                    __('Table comments:') . ' ' . $showComment,
                    0,
                    1,
                );
                $break = true;
            }

            if ($createTime !== '') {
                $this->diagram->Cell(
                    0,
                    3,
                    __('Creation:') . ' ' . $createTime,
                    0,
                    1,
                );
                $break = true;
            }

            if ($updateTime !== '') {
                $this->diagram->Cell(
                    0,
                    3,
                    __('Last update:') . ' ' . $updateTime,
                    0,
                    1,
                );
                $break = true;
            }

            if ($checkTime !== '') {
                $this->diagram->Cell(
                    0,
                    3,
                    __('Last check:') . ' ' . $checkTime,
                    0,
                    1,
                );
                $break = true;
            }

            if ($break == true) {
                $this->diagram->Cell(0, 3, '', 0, 1);
                $this->diagram->Ln();
            }

            $this->diagram->setFont($this->ff, 'B');
            if ($this->orientation === 'L') {
                $this->diagram->Cell(25, 8, __('Column'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Type'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Attributes'), 1, 0, 'C');
                $this->diagram->Cell(10, 8, __('Null'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Default'), 1, 0, 'C');
                $this->diagram->Cell(25, 8, __('Extra'), 1, 0, 'C');
                $this->diagram->Cell(45, 8, __('Links to'), 1, 0, 'C');

                if ($this->paper === 'A4') {
                    $commentsWidth = 67;
                } else {
                    // this is really intended for 'letter'
                    /** @todo find optimal width for all formats */
                    $commentsWidth = 50;
                }

                $this->diagram->Cell($commentsWidth, 8, __('Comments'), 1, 0, 'C');
                $this->diagram->Cell(45, 8, 'MIME', 1, 1, 'C');
                $this->diagram->setWidths(
                    [25, 20, 20, 10, 20, 25, 45, $commentsWidth, 45],
                );
            } else {
                $this->diagram->Cell(20, 8, __('Column'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Type'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Attributes'), 1, 0, 'C');
                $this->diagram->Cell(10, 8, __('Null'), 1, 0, 'C');
                $this->diagram->Cell(15, 8, __('Default'), 1, 0, 'C');
                $this->diagram->Cell(15, 8, __('Extra'), 1, 0, 'C');
                $this->diagram->Cell(30, 8, __('Links to'), 1, 0, 'C');
                $this->diagram->Cell(30, 8, __('Comments'), 1, 0, 'C');
                $this->diagram->Cell(30, 8, 'MIME', 1, 1, 'C');
                $this->diagram->setWidths([20, 20, 20, 10, 15, 15, 30, 30, 30]);
            }

            $this->diagram->setFont($this->ff, '');

            foreach ($columns as $row) {
                $extractedColumnSpec = Util::extractColumnSpec($row['Type']);
                $type = $extractedColumnSpec['print_type'];
                $attribute = $extractedColumnSpec['attribute'];
                if (! isset($row['Default'])) {
                    if ($row['Null'] != '' && $row['Null'] !== 'NO') {
                        $row['Default'] = 'NULL';
                    }
                }

                $fieldName = $row['Field'];
                // $this->diagram->Ln();
                $this->diagram->customLinks['RT'][$table][$fieldName] = $this->diagram->AddLink();
                $this->diagram->Bookmark($fieldName, 1, -1);
                $this->diagram->setLink($this->diagram->customLinks['doc'][$table][$fieldName], -1);
                $foreigner = $this->relation->searchColumnInForeigners($resRel, $fieldName);

                $linksTo = '';
                if ($foreigner) {
                    $linksTo = '-> ';
                    if ($foreigner['foreign_db'] != $this->db->getName()) {
                        $linksTo .= $foreigner['foreign_db'] . '.';
                    }

                    $linksTo .= $foreigner['foreign_table']
                        . '.' . $foreigner['foreign_field'];

                    if (isset($foreigner['on_update'])) { // not set for internal
                        $linksTo .= "\n" . 'ON UPDATE ' . $foreigner['on_update'];
                        $linksTo .= "\n" . 'ON DELETE ' . $foreigner['on_delete'];
                    }
                }

                $diagramRow = [
                    $fieldName,
                    $type,
                    $attribute,
                    $row['Null'] == '' || $row['Null'] === 'NO'
                        ? __('No')
                        : __('Yes'),
                    $row['Default'] ?? '',
                    $row['Extra'],
                    $linksTo,
                    $comments[$fieldName] ?? '',
                    isset($mimeMap, $mimeMap[$fieldName])
                        ? str_replace('_', '/', $mimeMap[$fieldName]['mimetype'])
                        : '',
                ];
                $links = [];
                $links[0] = $this->diagram->customLinks['RT'][$table][$fieldName];
                if (
                    $foreigner
                    && isset(
                        $this->diagram->customLinks['doc'][$foreigner['foreign_table']][$foreigner['foreign_field']],
                    )
                ) {
                    $foreignTable = $this->diagram->customLinks['doc'][$foreigner['foreign_table']];
                    $links[6] = $foreignTable[$foreigner['foreign_field']];
                }

                $this->diagram->row($diagramRow, $links);
            }

            $this->diagram->setFont($this->ff, '', 14);
        }
    }
}
