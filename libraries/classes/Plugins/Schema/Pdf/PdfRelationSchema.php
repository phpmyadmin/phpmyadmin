<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PDF schema handling
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins\Schema\Pdf;

use PhpMyAdmin\Pdf as PdfLib;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

/**
 * Skip the plugin if TCPDF is not available.
 */
if (! class_exists('TCPDF')) {
    $GLOBALS['skip_import'] = true;
    return;
}

/**
 * block attempts to directly run this script
 */
if (getcwd() == dirname(__FILE__)) {
    die('Attack stopped');
}

/**
 * Pdf Relation Schema Class
 *
 * Purpose of this class is to generate the PDF Document. PDF is widely
 * used format for documenting text,fonts,images and 3d vector graphics.
 *
 * This class inherits ExportRelationSchema class has common functionality added
 * to this class
 *
 * @name Pdf_Relation_Schema
 * @package PhpMyAdmin
 */
class PdfRelationSchema extends ExportRelationSchema
{
    /**
     * Defines properties
     */
    private $_showGrid;
    private $_withDoc;
    private $_tableOrder;

    /**
     * @var TableStatsPdf[]
     */
    private $_tables = array();
    private $_ff = PdfLib::PMA_PDF_FONT;
    private $_xMax = 0;
    private $_yMax = 0;
    private $_scale;
    private $_xMin = 100000;
    private $_yMin = 100000;
    private $_topMargin = 10;
    private $_bottomMargin = 10;
    private $_leftMargin = 10;
    private $_rightMargin = 10;
    private $_tablewidth;

    /**
     * @var RelationStatsPdf[]
     */
    protected $relations = array();

    /**
     * The "PdfRelationSchema" constructor
     *
     * @param string $db database name
     *
     * @see PMA_Schema_PDF
     */
    public function __construct($db)
    {
        $this->setShowGrid(isset($_REQUEST['pdf_show_grid']));
        $this->setShowColor(isset($_REQUEST['pdf_show_color']));
        $this->setShowKeys(isset($_REQUEST['pdf_show_keys']));
        $this->setTableDimension(isset($_REQUEST['pdf_show_table_dimension']));
        $this->setAllTablesSameWidth(isset($_REQUEST['pdf_all_tables_same_width']));
        $this->setWithDataDictionary(isset($_REQUEST['pdf_with_doc']));
        $this->setTableOrder($_REQUEST['pdf_table_order']);
        $this->setOrientation($_REQUEST['pdf_orientation']);
        $this->setPaper($_REQUEST['pdf_paper']);

        // Initializes a new document
        parent::__construct(
            $db,
            new Pdf(
                $this->orientation, 'mm', $this->paper,
                $this->pageNumber, $this->_withDoc, $db
            )
        );
        $this->diagram->SetTitle(
            sprintf(
                __('Schema of the %s database'),
                $this->db
            )
        );
        $this->diagram->setCMargin(0);
        $this->diagram->Open();
        $this->diagram->SetAutoPageBreak('auto');
        $this->diagram->setOffline($this->offline);

        $alltables = $this->getTablesFromRequest();
        if ($this->getTableOrder() == 'name_asc') {
            sort($alltables);
        } elseif ($this->getTableOrder() == 'name_desc') {
            rsort($alltables);
        }

        if ($this->_withDoc) {
            $this->diagram->SetAutoPageBreak('auto', 15);
            $this->diagram->setCMargin(1);
            $this->dataDictionaryDoc($alltables);
            $this->diagram->SetAutoPageBreak('auto');
            $this->diagram->setCMargin(0);
        }

        $this->diagram->Addpage();

        if ($this->_withDoc) {
            $this->diagram->SetLink($this->diagram->PMA_links['RT']['-'], -1);
            $this->diagram->Bookmark(__('Relational schema'));
            $this->diagram->setAlias('{00}', $this->diagram->PageNo());
            $this->_topMargin = 28;
            $this->_bottomMargin = 28;
        }

        /* snip */
        foreach ($alltables as $table) {
            if (! isset($this->_tables[$table])) {
                $this->_tables[$table] = new TableStatsPdf(
                    $this->diagram,
                    $this->db,
                    $table,
                    null,
                    $this->pageNumber,
                    $this->_tablewidth,
                    $this->showKeys,
                    $this->tableDimension,
                    $this->offline
                );
            }
            if ($this->sameWide) {
                $this->_tables[$table]->width = $this->_tablewidth;
            }
            $this->_setMinMax($this->_tables[$table]);
        }

        // Defines the scale factor
        $innerWidth = $this->diagram->getPageWidth() - $this->_rightMargin
            - $this->_leftMargin;
        $innerHeight = $this->diagram->getPageHeight() - $this->_topMargin
            - $this->_bottomMargin;
        $this->_scale = ceil(
            max(
                ($this->_xMax - $this->_xMin) / $innerWidth,
                ($this->_yMax - $this->_yMin) / $innerHeight
            ) * 100
        ) / 100;

        $this->diagram->setScale(
            $this->_scale,
            $this->_xMin,
            $this->_yMin,
            $this->_leftMargin,
            $this->_topMargin
        );
        // Builds and save the PDF document
        $this->diagram->setLineWidthScale(0.1);

        if ($this->_showGrid) {
            $this->diagram->SetFontSize(10);
            $this->_strokeGrid();
        }
        $this->diagram->setFontSizeScale(14);
        // previous logic was checking master tables and foreign tables
        // but I think that looping on every table of the pdf page as a master
        // and finding its foreigns is OK (then we can support innodb)
        $seen_a_relation = false;
        foreach ($alltables as $one_table) {
            $exist_rel = $this->relation->getForeigners($this->db, $one_table, '', 'both');
            if (!$exist_rel) {
                continue;
            }

            $seen_a_relation = true;
            foreach ($exist_rel as $master_field => $rel) {
                // put the foreign table on the schema only if selected
                // by the user
                // (do not use array_search() because we would have to
                // to do a === false and this is not PHP3 compatible)
                if ($master_field != 'foreign_keys_data') {
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->_addRelation(
                            $one_table,
                            $master_field,
                            $rel['foreign_table'],
                            $rel['foreign_field']
                        );
                    }
                    continue;
                }

                foreach ($rel as $one_key) {
                    if (!in_array($one_key['ref_table_name'], $alltables)) {
                        continue;
                    }

                    foreach ($one_key['index_list']
                        as $index => $one_field
                    ) {
                        $this->_addRelation(
                            $one_table,
                            $one_field,
                            $one_key['ref_table_name'],
                            $one_key['ref_index_list'][$index]
                        );
                    }
                }
            } // end while
        } // end while

        if ($seen_a_relation) {
            $this->_drawRelations();
        }
        $this->_drawTables();
    }

    /**
     * Set Show Grid
     *
     * @param boolean $value show grid of the document or not
     *
     * @return void
     */
    public function setShowGrid($value)
    {
        $this->_showGrid = $value;
    }

    /**
     * Returns whether to show grid
     *
     * @return boolean whether to show grid
     */
    public function isShowGrid()
    {
        return $this->_showGrid;
    }

    /**
     * Set Data Dictionary
     *
     * @param boolean $value show selected database data dictionary or not
     *
     * @return void
     */
    public function setWithDataDictionary($value)
    {
        $this->_withDoc = $value;
    }

    /**
     * Return whether to show selected database data dictionary or not
     *
     * @return boolean whether to show selected database data dictionary or not
     */
    public function isWithDataDictionary()
    {
        return $this->_withDoc;
    }

    /**
     * Sets the order of the table in data dictionary
     *
     * @param string $value table order
     *
     * @return void
     */
    public function setTableOrder($value)
    {
        $this->_tableOrder = $value;
    }

    /**
     * Returns the order of the table in data dictionary
     *
     * @return string table order
     */
    public function getTableOrder()
    {
        return $this->_tableOrder;
    }

    /**
     * Output Pdf Document for download
     *
     * @return void
     */
    public function showOutput()
    {
        $this->diagram->download($this->getFileName('.pdf'));
    }

    /**
     * Sets X and Y minimum and maximum for a table cell
     *
     * @param TableStatsPdf $table The table name of which sets XY co-ordinates
     *
     * @return void
     */
    private function _setMinMax($table)
    {
        $this->_xMax = max($this->_xMax, $table->x + $table->width);
        $this->_yMax = max($this->_yMax, $table->y + $table->height);
        $this->_xMin = min($this->_xMin, $table->x);
        $this->_yMin = min($this->_yMin, $table->y);
    }

    /**
     * Defines relation objects
     *
     * @param string $masterTable  The master table name
     * @param string $masterField  The relation field in the master table
     * @param string $foreignTable The foreign table name
     * @param string $foreignField The relation field in the foreign table
     *
     * @return void
     *
     * @see _setMinMax
     */
    private function _addRelation($masterTable, $masterField, $foreignTable,
        $foreignField
    ) {
        if (! isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new TableStatsPdf(
                $this->diagram,
                $this->db,
                $masterTable,
                null,
                $this->pageNumber,
                $this->_tablewidth,
                $this->showKeys,
                $this->tableDimension
            );
            $this->_setMinMax($this->_tables[$masterTable]);
        }
        if (! isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new TableStatsPdf(
                $this->diagram,
                $this->db,
                $foreignTable,
                null,
                $this->pageNumber,
                $this->_tablewidth,
                $this->showKeys,
                $this->tableDimension
            );
            $this->_setMinMax($this->_tables[$foreignTable]);
        }
        $this->relations[] = new RelationStatsPdf(
            $this->diagram,
            $this->_tables[$masterTable],
            $masterField,
            $this->_tables[$foreignTable],
            $foreignField
        );
    }

    /**
     * Draws the grid
     *
     * @return void
     *
     * @see PMA_Schema_PDF
     */
    private function _strokeGrid()
    {
        $gridSize = 10;
        $labelHeight = 4;
        $labelWidth = 5;
        if ($this->_withDoc) {
            $topSpace = 6;
            $bottomSpace = 15;
        } else {
            $topSpace = 0;
            $bottomSpace = 0;
        }

        $this->diagram->SetMargins(0, 0);
        $this->diagram->SetDrawColor(200, 200, 200);
        // Draws horizontal lines
        $innerHeight = $this->diagram->getPageHeight() - $topSpace - $bottomSpace;
        for ($l = 0,
            $size = intval($innerHeight / $gridSize);
            $l <= $size;
            $l++
        ) {
            $this->diagram->line(
                0, $l * $gridSize + $topSpace,
                $this->diagram->getPageWidth(), $l * $gridSize + $topSpace
            );
            // Avoid duplicates
            if ($l > 0
                && $l <= intval(($innerHeight - $labelHeight) / $gridSize)
            ) {
                $this->diagram->SetXY(0, $l * $gridSize + $topSpace);
                $label = (string) sprintf(
                    '%.0f',
                    ($l * $gridSize + $topSpace - $this->_topMargin)
                    * $this->_scale + $this->_yMin
                );
                $this->diagram->Cell($labelWidth, $labelHeight, ' ' . $label);
            } // end if
        } // end for
        // Draws vertical lines
        for (
            $j = 0, $size = intval($this->diagram->getPageWidth() / $gridSize);
            $j <= $size;
            $j++
        ) {
            $this->diagram->line(
                $j * $gridSize,
                $topSpace,
                $j * $gridSize,
                $this->diagram->getPageHeight() - $bottomSpace
            );
            $this->diagram->SetXY($j * $gridSize, $topSpace);
            $label = (string) sprintf(
                '%.0f',
                ($j * $gridSize - $this->_leftMargin) * $this->_scale + $this->_xMin
            );
            $this->diagram->Cell($labelWidth, $labelHeight, $label);
        }
    }

    /**
     * Draws relation arrows
     *
     * @return void
     *
     * @see Relation_Stats_Pdf::relationdraw()
     */
    private function _drawRelations()
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
     * @return void
     *
     * @see Table_Stats_Pdf::tableDraw()
     */
    private function _drawTables()
    {
        foreach ($this->_tables as $table) {
            $table->tableDraw(null, $this->_withDoc, $this->showColor);
        }
    }

    /**
     * Generates data dictionary pages.
     *
     * @param array $alltables Tables to document.
     *
     * @return void
     */
    public function dataDictionaryDoc(array $alltables)
    {
         // TOC
        $this->diagram->addpage($this->orientation);
        $this->diagram->Cell(0, 9, __('Table of contents'), 1, 0, 'C');
        $this->diagram->Ln(15);
        $i = 1;
        foreach ($alltables as $table) {
            $this->diagram->PMA_links['doc'][$table]['-']
                = $this->diagram->AddLink();
            $this->diagram->SetX(10);
            // $this->diagram->Ln(1);
            $this->diagram->Cell(
                0, 6, __('Page number:') . ' {' . sprintf("%02d", $i) . '}', 0, 0,
                'R', 0, $this->diagram->PMA_links['doc'][$table]['-']
            );
            $this->diagram->SetX(10);
            $this->diagram->Cell(
                0, 6, $i . ' ' . $table, 0, 1,
                'L', 0, $this->diagram->PMA_links['doc'][$table]['-']
            );
            // $this->diagram->Ln(1);
            $fields = $GLOBALS['dbi']->getColumns($this->db, $table);
            foreach ($fields as $row) {
                $this->diagram->SetX(20);
                $field_name = $row['Field'];
                $this->diagram->PMA_links['doc'][$table][$field_name]
                    = $this->diagram->AddLink();
                //$this->diagram->Cell(
                //    0, 6, $field_name, 0, 1,
                //    'L', 0, $this->diagram->PMA_links['doc'][$table][$field_name]
                //);
            }
            $i++;
        }
        $this->diagram->PMA_links['RT']['-'] = $this->diagram->AddLink();
        $this->diagram->SetX(10);
        $this->diagram->Cell(
            0, 6, __('Page number:') . ' {00}', 0, 0,
            'R', 0, $this->diagram->PMA_links['RT']['-']
        );
        $this->diagram->SetX(10);
        $this->diagram->Cell(
            0, 6, $i . ' ' . __('Relational schema'), 0, 1,
            'L', 0, $this->diagram->PMA_links['RT']['-']
        );
        $z = 0;
        foreach ($alltables as $table) {
            $z++;
            $this->diagram->SetAutoPageBreak(true, 15);
            $this->diagram->addpage($this->orientation);
            $this->diagram->Bookmark($table);
            $this->diagram->setAlias(
                '{' . sprintf("%02d", $z) . '}', $this->diagram->PageNo()
            );
            $this->diagram->PMA_links['RT'][$table]['-']
                = $this->diagram->AddLink();
            $this->diagram->SetLink(
                $this->diagram->PMA_links['doc'][$table]['-'], -1
            );
            $this->diagram->SetFont($this->_ff, 'B', 18);
            $this->diagram->Cell(
                0, 8, $z . ' ' . $table, 1, 1,
                'C', 0, $this->diagram->PMA_links['RT'][$table]['-']
            );
            $this->diagram->SetFont($this->_ff, '', 8);
            $this->diagram->ln();

            $cfgRelation = $this->relation->getRelationsParam();
            $comments = $this->relation->getComments($this->db, $table);
            if ($cfgRelation['mimework']) {
                $mime_map = Transformations::getMIME($this->db, $table, true);
            }

            /**
             * Gets table information
             */
            $showtable = $GLOBALS['dbi']->getTable($this->db, $table)
                ->getStatusInfo();
            $show_comment = isset($showtable['Comment'])
                ? $showtable['Comment']
                : '';
            $create_time  = isset($showtable['Create_time'])
                ? Util::localisedDate(
                    strtotime($showtable['Create_time'])
                )
                : '';
            $update_time  = isset($showtable['Update_time'])
                ? Util::localisedDate(
                    strtotime($showtable['Update_time'])
                )
                : '';
            $check_time   = isset($showtable['Check_time'])
                ? Util::localisedDate(
                    strtotime($showtable['Check_time'])
                )
                : '';

            /**
             * Gets fields properties
             */
            $columns = $GLOBALS['dbi']->getColumns($this->db, $table);

            // Find which tables are related with the current one and write it in
            // an array
            $res_rel = $this->relation->getForeigners($this->db, $table);

            /**
             * Displays the comments of the table if MySQL >= 3.23
             */

            $break = false;
            if (! empty($show_comment)) {
                $this->diagram->Cell(
                    0, 3, __('Table comments:') . ' ' . $show_comment, 0, 1
                );
                $break = true;
            }

            if (! empty($create_time)) {
                $this->diagram->Cell(
                    0, 3, __('Creation:') . ' ' . $create_time, 0, 1
                );
                $break = true;
            }

            if (! empty($update_time)) {
                $this->diagram->Cell(
                    0, 3, __('Last update:') . ' ' . $update_time, 0, 1
                );
                $break = true;
            }

            if (! empty($check_time)) {
                $this->diagram->Cell(
                    0, 3, __('Last check:') . ' ' . $check_time, 0, 1
                );
                $break = true;
            }

            if ($break == true) {
                $this->diagram->Cell(0, 3, '', 0, 1);
                $this->diagram->Ln();
            }

            $this->diagram->SetFont($this->_ff, 'B');
            if (isset($this->orientation) && $this->orientation == 'L') {
                $this->diagram->Cell(25, 8, __('Column'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Type'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Attributes'), 1, 0, 'C');
                $this->diagram->Cell(10, 8, __('Null'), 1, 0, 'C');
                $this->diagram->Cell(20, 8, __('Default'), 1, 0, 'C');
                $this->diagram->Cell(25, 8, __('Extra'), 1, 0, 'C');
                $this->diagram->Cell(45, 8, __('Links to'), 1, 0, 'C');

                if ($this->paper == 'A4') {
                    $comments_width = 67;
                } else {
                    // this is really intended for 'letter'
                    /**
                     * @todo find optimal width for all formats
                     */
                    $comments_width = 50;
                }
                $this->diagram->Cell($comments_width, 8, __('Comments'), 1, 0, 'C');
                $this->diagram->Cell(45, 8, 'MIME', 1, 1, 'C');
                $this->diagram->setWidths(
                    array(25, 20, 20, 10, 20, 25, 45, $comments_width, 45)
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
                $this->diagram->setWidths(array(20, 20, 20, 10, 15, 15, 30, 30, 30));
            }
            $this->diagram->SetFont($this->_ff, '');

            foreach ($columns as $row) {
                $extracted_columnspec
                    = Util::extractColumnSpec($row['Type']);
                $type                = $extracted_columnspec['print_type'];
                $attribute           = $extracted_columnspec['attribute'];
                if (! isset($row['Default'])) {
                    if ($row['Null'] != '' && $row['Null'] != 'NO') {
                        $row['Default'] = 'NULL';
                    }
                }
                $field_name = $row['Field'];
                // $this->diagram->Ln();
                $this->diagram->PMA_links['RT'][$table][$field_name]
                    = $this->diagram->AddLink();
                $this->diagram->Bookmark($field_name, 1, -1);
                $this->diagram->SetLink(
                    $this->diagram->PMA_links['doc'][$table][$field_name], -1
                );
                $foreigner = $this->relation->searchColumnInForeigners($res_rel, $field_name);

                $linksTo = '';
                if ($foreigner) {
                    $linksTo = '-> ';
                    if ($foreigner['foreign_db'] != $this->db) {
                        $linksTo .= $foreigner['foreign_db'] . '.';
                    }
                    $linksTo .= $foreigner['foreign_table']
                        . '.' . $foreigner['foreign_field'];

                    if (isset($foreigner['on_update'])) { // not set for internal
                        $linksTo .= "\n" . 'ON UPDATE ' . $foreigner['on_update'];
                        $linksTo .= "\n" . 'ON DELETE ' . $foreigner['on_delete'];
                    }
                }

                $this->diagram_row = array(
                    $field_name,
                    $type,
                    $attribute,
                    (($row['Null'] == '' || $row['Null'] == 'NO')
                        ? __('No')
                        : __('Yes')),
                    (isset($row['Default']) ? $row['Default'] : ''),
                    $row['Extra'],
                    $linksTo,
                    (isset($comments[$field_name])
                        ? $comments[$field_name]
                        : ''),
                    (isset($mime_map) && isset($mime_map[$field_name])
                        ? str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                        : '')
                );
                $links = array();
                $links[0] = $this->diagram->PMA_links['RT'][$table][$field_name];
                if ($foreigner
                    && isset($this->diagram->PMA_links['doc'][$foreigner['foreign_table']][$foreigner['foreign_field']])
                ) {
                    $links[6] = $this->diagram->PMA_links['doc']
                        [$foreigner['foreign_table']][$foreigner['foreign_field']];
                } else {
                    unset($links[6]);
                }
                $this->diagram->row($this->diagram_row, $links);
            } // end foreach
            $this->diagram->SetFont($this->_ff, '', 14);
        } //end each
    }
}
