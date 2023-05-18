<?php
/**
 * PhpMyAdmin\Plugins\Export\Helpers\Pdf class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Export\Helpers;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Database\Triggers;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Pdf as PdfLib;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;
use TCPDF_STATIC;

use function __;
use function array_key_exists;
use function count;
use function ksort;

/**
 * Adapted from a LGPL script by Philip Clarke
 */
class Pdf extends PdfLib
{
    /** @var mixed[] */
    public array $tablewidths = [];

    /** @var mixed[] */
    public array $headerset = [];

    private int|float $dataY = 0;

    private int $titleFontSize = 0;

    private string $titleText = '';

    private string $dbAlias = '';

    private string $tableAlias = '';

    private string $purpose = '';

    /** @var mixed[] */
    private array $colTitles = [];

    private ResultInterface $results;

    /** @var mixed[] */
    private array $colAlign = [];

    /** @var mixed[] */
    private array $displayColumn = [];

    private string $currentDb = '';

    private string $currentTable = '';

    /** @var mixed[] */
    private array $aliases = [];

    private Relation $relation;

    private Transformations $transformations;

    /**
     * Constructs PDF and configures standard parameters.
     *
     * @param string    $orientation page orientation
     * @param string    $unit        unit
     * @param string    $format      the format used for pages
     * @param bool      $unicode     true means that the input text is unicode
     * @param string    $encoding    charset encoding; default is UTF-8.
     * @param bool      $diskcache   DEPRECATED TCPDF FEATURE
     * @param false|int $pdfa        If not false, set the document to PDF/A mode and the good version (1 or 3)
     */
    public function __construct(
        string $orientation = 'P',
        string $unit = 'mm',
        string $format = 'A4',
        bool $unicode = true,
        string $encoding = 'UTF-8',
        bool $diskcache = false,
        false|int $pdfa = false,
    ) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);

        $this->relation = new Relation($GLOBALS['dbi']);
        $this->transformations = new Transformations();
    }

    /**
     * Add page if needed.
     *
     * @param float|int $h       cell height. Default value: 0
     * @param mixed     $y       starting y position, leave empty for current
     *                           position
     * @param bool      $addpage if true add a page, otherwise only return
     *                           the true/false state
     */
    public function checkPageBreak(mixed $h = 0, mixed $y = '', mixed $addpage = true): bool
    {
        if (TCPDF_STATIC::empty_string($y)) {
            $y = $this->y;
        }

        $currentPage = $this->page;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        if ($y + $h > $this->PageBreakTrigger && ! $this->InFooter && $this->AcceptPageBreak()) {
            if ($addpage) {
                //Automatic page break
                $x = $this->x;
                // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                $this->AddPage($this->CurOrientation);
                $this->y = $this->dataY;
                $oldpage = $this->page - 1;

                $thisPageOrm = $this->pagedim[$this->page]['orm'];
                $oldPageOrm = $this->pagedim[$oldpage]['orm'];
                $thisPageOlm = $this->pagedim[$this->page]['olm'];
                $oldPageOlm = $this->pagedim[$oldpage]['olm'];
                if ($this->rtl) {
                    if ($thisPageOrm != $oldPageOrm) {
                        $this->x = $x - ($thisPageOrm - $oldPageOrm);
                    } else {
                        $this->x = $x;
                    }
                } elseif ($thisPageOlm != $oldPageOlm) {
                    $this->x = $x + $thisPageOlm - $oldPageOlm;
                } else {
                    $this->x = $x;
                }
            }

            return true;
        }

        // account for columns mode
        return $currentPage != $this->page;
    }

    /**
     * This method is used to render the page header.
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function Header(): void
    {
        $GLOBALS['maxY'] ??= null;

        // We don't want automatic page breaks while generating header
        // as this can lead to infinite recursion as auto generated page
        // will want header as well causing another page break
        // FIXME: Better approach might be to try to compact the content
        $this->setAutoPageBreak(false);
        // Check if header for this page already exists
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        if (! isset($this->headerset[$this->page])) {
            $this->setY($this->tMargin - ($this->FontSizePt / $this->k) * 5);
            $cellFontSize = $this->FontSizePt;
            $this->setFont(PdfLib::PMA_PDF_FONT, '', ($this->titleFontSize ?: $this->FontSizePt));
            $this->Cell(0, $this->FontSizePt, $this->titleText, 0, 1, 'C');
            $this->setFont(PdfLib::PMA_PDF_FONT, '', $cellFontSize);
            $this->setY($this->tMargin - ($this->FontSizePt / $this->k) * 2.5);
            $this->Cell(
                0,
                $this->FontSizePt,
                __('Database:') . ' ' . $this->dbAlias . ',  '
                . __('Table:') . ' ' . $this->tableAlias . ',  '
                . __('Purpose:') . ' ' . $this->purpose,
                0,
                1,
                'L',
            );
            $l = $this->lMargin;
            foreach ($this->colTitles as $col => $txt) {
                $this->setXY($l, $this->tMargin);
                $this->MultiCell($this->tablewidths[$col], $this->FontSizePt, $txt);
                $l += $this->tablewidths[$col];
                $GLOBALS['maxY'] = $GLOBALS['maxY'] < $this->GetY() ? $this->GetY() : $GLOBALS['maxY'];
            }

            $this->setXY($this->lMargin, $this->tMargin);
            $this->setFillColor(200, 200, 200);
            $l = $this->lMargin;
            foreach ($this->colTitles as $col => $txt) {
                $this->setXY($l, $this->tMargin);
                $this->Cell($this->tablewidths[$col], $GLOBALS['maxY'] - $this->tMargin, '', 1, 0, 'L', true);
                $this->setXY($l, $this->tMargin);
                $this->MultiCell($this->tablewidths[$col], $this->FontSizePt, $txt, 0, 'C');
                $l += $this->tablewidths[$col];
            }

            $this->setFillColor(255, 255, 255);
            // set headerset
            $this->headerset[$this->page] = 1;
        }

        // phpcs:enable

        $this->dataY = $GLOBALS['maxY'];
        $this->setAutoPageBreak(true);
    }

    /**
     * Generate table
     *
     * @param int $lineheight Height of line
     */
    public function morepagestable(int $lineheight = 8): void
    {
        // some things to set and 'remember'
        $l = $this->lMargin;
        $startheight = $h = $this->dataY;
        $startpage = $currpage = $this->page;

        // calculate the whole width
        $fullwidth = 0;
        foreach ($this->tablewidths as $width) {
            $fullwidth += $width;
        }

        // Now let's start to write the table
        $row = 0;
        $tmpheight = [];
        $maxpage = $this->page;

        while ($data = $this->results->fetchRow()) {
            $this->page = $currpage;
            // write the horizontal borders
            $this->Line($l, $h, $fullwidth + $l, $h);
            // write the content and remember the height of the highest col
            foreach ($data as $col => $txt) {
                $this->page = $currpage;
                $this->setXY($l, $h);
                if ($this->tablewidths[$col] > 0) {
                    $this->MultiCell($this->tablewidths[$col], $lineheight, $txt, 0, $this->colAlign[$col]);
                    $l += $this->tablewidths[$col];
                }

                if (! isset($tmpheight[$row . '-' . $this->page])) {
                    $tmpheight[$row . '-' . $this->page] = 0;
                }

                if ($tmpheight[$row . '-' . $this->page] < $this->GetY()) {
                    $tmpheight[$row . '-' . $this->page] = $this->GetY();
                }

                if ($this->page > $maxpage) {
                    $maxpage = $this->page;
                }

                unset($data[$col]);
            }

            // get the height we were in the last used page
            $h = $tmpheight[$row . '-' . $maxpage];
            // set the "pointer" to the left margin
            $l = $this->lMargin;
            // set the $currpage to the last page
            $currpage = $maxpage;
            unset($data[$row]);
            $row++;
        }

        // draw the borders
        // we start adding a horizontal line on the last page
        $this->page = $maxpage;
        $this->Line($l, $h, $fullwidth + $l, $h);
        // now we start at the top of the document and walk down
        for ($i = $startpage; $i <= $maxpage; $i++) {
            $this->page = $i;
            $l = $this->lMargin;
            $t = $i == $startpage ? $startheight : $this->tMargin;
            $lh = $i == $maxpage ? $h : $this->h - $this->bMargin;
            $this->Line($l, $t, $l, $lh);
            foreach ($this->tablewidths as $width) {
                $l += $width;
                $this->Line($l, $t, $l, $lh);
            }
        }

        // set it to the last page, if not it'll cause some problems
        $this->page = $maxpage;
    }

    /**
     * Defines the top margin.
     * The method can be called before creating the first page.
     *
     * @param float $topMargin the margin
     */
    public function setTopMargin(mixed $topMargin): void
    {
        $this->tMargin = $topMargin;
    }

    /**
     * Prints triggers
     *
     * @param string $db    database name
     * @param string $table table name
     */
    public function getTriggers(string $db, string $table): void
    {
        $triggers = Triggers::getDetails($GLOBALS['dbi'], $db, $table);
        if ($triggers === []) {
            return; //prevents printing blank trigger list for any table
        }

        unset(
            $this->tablewidths,
            $this->colTitles,
            $this->displayColumn,
            $this->colAlign,
        );

        /**
         * Making table heading
         * Keeping column width constant
         */
        $this->colTitles[0] = __('Name');
        $this->tablewidths[0] = 90;
        $this->colTitles[1] = __('Time');
        $this->tablewidths[1] = 80;
        $this->colTitles[2] = __('Event');
        $this->tablewidths[2] = 40;
        $this->colTitles[3] = __('Definition');
        $this->tablewidths[3] = 240;

        for ($columnsCnt = 0; $columnsCnt < 4; $columnsCnt++) {
            $this->colAlign[$columnsCnt] = 'L';
            $this->displayColumn[$columnsCnt] = true;
        }

        // Starting to fill table with required info

        $this->setY($this->tMargin);
        $this->AddPage();
        $this->setFont(PdfLib::PMA_PDF_FONT, '', 9);

        $l = $this->lMargin;
        $startheight = $h = $this->dataY;
        $startpage = $currpage = $this->page;

        // calculate the whole width
        $fullwidth = 0;
        foreach ($this->tablewidths as $width) {
            $fullwidth += $width;
        }

        $row = 0;
        $tmpheight = [];
        $maxpage = $this->page;
        $data = [];

        foreach ($triggers as $trigger) {
            $data[] = $trigger['name'];
            $data[] = $trigger['action_timing'];
            $data[] = $trigger['event_manipulation'];
            $data[] = $trigger['definition'];
            $this->page = $currpage;
            // write the horizontal borders
            $this->Line($l, $h, $fullwidth + $l, $h);
            // write the content and remember the height of the highest col
            foreach ($data as $col => $txt) {
                $this->page = $currpage;
                $this->setXY($l, $h);
                if ($this->tablewidths[$col] > 0) {
                    $this->MultiCell(
                        $this->tablewidths[$col],
                        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                        $this->FontSizePt,
                        $txt,
                        0,
                        $this->colAlign[$col],
                    );
                    $l += $this->tablewidths[$col];
                }

                if (! isset($tmpheight[$row . '-' . $this->page])) {
                    $tmpheight[$row . '-' . $this->page] = 0;
                }

                if ($tmpheight[$row . '-' . $this->page] < $this->GetY()) {
                    $tmpheight[$row . '-' . $this->page] = $this->GetY();
                }

                if ($this->page <= $maxpage) {
                    continue;
                }

                $maxpage = $this->page;
            }

            // get the height we were in the last used page
            $h = $tmpheight[$row . '-' . $maxpage];
            // set the "pointer" to the left margin
            $l = $this->lMargin;
            // set the $currpage to the last page
            $currpage = $maxpage;
            unset($data);
            $row++;
        }

        // draw the borders
        // we start adding a horizontal line on the last page
        $this->page = $maxpage;
        $this->Line($l, $h, $fullwidth + $l, $h);
        // now we start at the top of the document and walk down
        for ($i = $startpage; $i <= $maxpage; $i++) {
            $this->page = $i;
            $l = $this->lMargin;
            $t = $i == $startpage ? $startheight : $this->tMargin;
            $lh = $i == $maxpage ? $h : $this->h - $this->bMargin;
            $this->Line($l, $t, $l, $lh);
            foreach ($this->tablewidths as $width) {
                $l += $width;
                $this->Line($l, $t, $l, $lh);
            }
        }

        // set it to the last page, if not it'll cause some problems
        $this->page = $maxpage;
    }

    /**
     * Print $table's CREATE definition
     *
     * @param string $db         the database name
     * @param string $table      the table name
     * @param bool   $doRelation whether to include relation comments
     * @param bool   $doComments whether to include the pmadb-style column
     *                            comments as comments in the structure;
     *                            this is deprecated but the parameter is
     *                            left here because /export calls
     *                            PMA_exportStructure() also for other
     *                            export types which use this parameter
     * @param bool   $doMime     whether to include mime comments
     */
    public function getTableDef(
        string $db,
        string $table,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
    ): void {
        $relationParameters = $this->relation->getRelationParameters();

        unset(
            $this->tablewidths,
            $this->colTitles,
            $this->displayColumn,
            $this->colAlign,
        );

        /**
         * Gets fields properties
         */
        $GLOBALS['dbi']->selectDb($db);

        /**
         * All these three checks do_relation, do_comment and do_mime is
         * not required. As presently all are set true by default.
         * But when, methods to take user input will be developed,
         * it will be of use
         */
        // Check if we can use Relations
        if ($doRelation) {
            // Find which tables are related with the current one and write it in
            // an array
            $resRel = $this->relation->getForeigners($db, $table);
            $haveRel = $resRel !== [];
        } else {
            $haveRel = false;
        }

        //column count and table heading

        $this->colTitles[0] = __('Column');
        $this->tablewidths[0] = 90;
        $this->colTitles[1] = __('Type');
        $this->tablewidths[1] = 80;
        $this->colTitles[2] = __('Null');
        $this->tablewidths[2] = 40;
        $this->colTitles[3] = __('Default');
        $this->tablewidths[3] = 120;

        for ($columnsCnt = 0; $columnsCnt < 4; $columnsCnt++) {
            $this->colAlign[$columnsCnt] = 'L';
            $this->displayColumn[$columnsCnt] = true;
        }

        if ($doRelation && $haveRel) {
            $this->colTitles[$columnsCnt] = __('Links to');
            $this->displayColumn[$columnsCnt] = true;
            $this->colAlign[$columnsCnt] = 'L';
            $this->tablewidths[$columnsCnt] = 120;
        }

        if ($doComments) {
            $columnsCnt++;
            $this->colTitles[$columnsCnt] = __('Comments');
            $this->displayColumn[$columnsCnt] = true;
            $this->colAlign[$columnsCnt] = 'L';
            $this->tablewidths[$columnsCnt] = 120;
        }

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $columnsCnt++;
            $this->colTitles[$columnsCnt] = __('Media type');
            $this->displayColumn[$columnsCnt] = true;
            $this->colAlign[$columnsCnt] = 'L';
            $this->tablewidths[$columnsCnt] = 120;
        }

        // Starting to fill table with required info

        $this->setY($this->tMargin);
        $this->AddPage();
        $this->setFont(PdfLib::PMA_PDF_FONT, '', 9);

        // Now let's start to write the table structure

        if ($doComments) {
            $comments = $this->relation->getComments($db, $table);
        }

        if ($doMime && $relationParameters->browserTransformationFeature !== null) {
            $mimeMap = $this->transformations->getMime($db, $table, true);
        }

        $columns = $GLOBALS['dbi']->getColumns($db, $table);

        // some things to set and 'remember'
        $l = $this->lMargin;
        $startheight = $h = $this->dataY;
        $startpage = $currpage = $this->page;
        // calculate the whole width
        $fullwidth = 0;
        foreach ($this->tablewidths as $width) {
            $fullwidth += $width;
        }

        $row = 0;
        $tmpheight = [];
        $maxpage = $this->page;
        $data = [];

        // fun begin
        foreach ($columns as $column) {
            $extractedColumnSpec = Util::extractColumnSpec($column['Type']);

            $type = $extractedColumnSpec['print_type'];
            if (empty($type)) {
                $type = ' ';
            }

            if (! isset($column['Default'])) {
                if ($column['Null'] !== 'NO') {
                    $column['Default'] = 'NULL';
                }
            }

            $data[] = $column['Field'];
            $data[] = $type;
            $data[] = $column['Null'] == '' || $column['Null'] === 'NO'
                ? 'No'
                : 'Yes';
            $data[] = $column['Default'] ?? '';

            $fieldName = $column['Field'];

            if ($doRelation && $haveRel) {
                $data[] = isset($resRel[$fieldName])
                    ? $resRel[$fieldName]['foreign_table']
                    . ' (' . $resRel[$fieldName]['foreign_field']
                    . ')'
                    : '';
            }

            if ($doComments) {
                $data[] = $comments[$fieldName] ?? '';
            }

            if ($doMime) {
                $data[] = isset($mimeMap[$fieldName])
                    ? $mimeMap[$fieldName]['mimetype']
                    : '';
            }

            $this->page = $currpage;
            // write the horizontal borders
            $this->Line($l, $h, $fullwidth + $l, $h);
            // write the content and remember the height of the highest col
            foreach ($data as $col => $txt) {
                $this->page = $currpage;
                $this->setXY($l, $h);
                if ($this->tablewidths[$col] > 0) {
                    $this->MultiCell(
                        $this->tablewidths[$col],
                        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                        $this->FontSizePt,
                        $txt,
                        0,
                        $this->colAlign[$col],
                    );
                    $l += $this->tablewidths[$col];
                }

                if (! isset($tmpheight[$row . '-' . $this->page])) {
                    $tmpheight[$row . '-' . $this->page] = 0;
                }

                if ($tmpheight[$row . '-' . $this->page] < $this->GetY()) {
                    $tmpheight[$row . '-' . $this->page] = $this->GetY();
                }

                if ($this->page <= $maxpage) {
                    continue;
                }

                $maxpage = $this->page;
            }

            // get the height we were in the last used page
            $h = $tmpheight[$row . '-' . $maxpage];
            // set the "pointer" to the left margin
            $l = $this->lMargin;
            // set the $currpage to the last page
            $currpage = $maxpage;
            unset($data);
            $row++;
        }

        // draw the borders
        // we start adding a horizontal line on the last page
        $this->page = $maxpage;
        $this->Line($l, $h, $fullwidth + $l, $h);
        // now we start at the top of the document and walk down
        for ($i = $startpage; $i <= $maxpage; $i++) {
            $this->page = $i;
            $l = $this->lMargin;
            $t = $i == $startpage ? $startheight : $this->tMargin;
            $lh = $i == $maxpage ? $h : $this->h - $this->bMargin;
            $this->Line($l, $t, $l, $lh);
            foreach ($this->tablewidths as $width) {
                $l += $width;
                $this->Line($l, $t, $l, $lh);
            }
        }

        // set it to the last page, if not it'll cause some problems
        $this->page = $maxpage;
    }

    /**
     * MySQL report
     *
     * @param string $query Query to execute
     */
    public function mysqlReport(string $query): void
    {
        unset(
            $this->tablewidths,
            $this->colTitles,
            $this->displayColumn,
            $this->colAlign,
        );

        /**
         * Pass 1 for column widths
         */
        $this->results = $GLOBALS['dbi']->query($query, Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $numFields = $this->results->numFields();
        $fields = $GLOBALS['dbi']->getFieldsMeta($this->results);

        // sColWidth = starting col width (an average size width)
        $availableWidth = $this->w - $this->lMargin - $this->rMargin;
        $sColWidth = $availableWidth / $numFields;
        $totalTitleWidth = 0;

        // loop through results header and set initial
        // col widths/ titles/ alignment
        // if a col title is less than the starting col width,
        // reduce that column size
        $colFits = [];
        $titleWidth = [];
        for ($i = 0; $i < $numFields; $i++) {
            $colAs = $fields[$i]->name;
            $db = $this->currentDb;
            $table = $this->currentTable;
            if (! empty($this->aliases[$db]['tables'][$table]['columns'][$colAs])) {
                $colAs = $this->aliases[$db]['tables'][$table]['columns'][$colAs];
            }

            /** @var float $stringWidth */
            $stringWidth = $this->GetStringWidth($colAs);
            $stringWidth += 6;
            // save the real title's width
            $titleWidth[$i] = $stringWidth;
            $totalTitleWidth += $stringWidth;

            // set any column titles less than the start width to
            // the column title width
            if ($stringWidth < $sColWidth) {
                $colFits[$i] = $stringWidth;
            }

            $this->colTitles[$i] = $colAs;
            $this->displayColumn[$i] = true;

            $this->colAlign[$i] = 'L';

            if ($fields[$i]->isType(FieldMetadata::TYPE_INT)) {
                $this->colAlign[$i] = 'R';
            }

            if (! $fields[$i]->isType(FieldMetadata::TYPE_BLOB)) {
                continue;
            }

            /**
             * @todo do not deactivate completely the display
             * but show the field's name and [BLOB]
             */
            if ($fields[$i]->isBinary()) {
                $this->displayColumn[$i] = false;
                unset($this->colTitles[$i]);
            }

            $this->colAlign[$i] = 'L';
        }

        // title width verification
        if ($totalTitleWidth > $availableWidth) {
            $adjustingMode = true;
        } else {
            $adjustingMode = false;
            // we have enough space for all the titles at their
            // original width so use the true title's width
            foreach ($titleWidth as $key => $val) {
                $colFits[$key] = $val;
            }
        }

        // loop through the data; any column whose contents
        // is greater than the column size is resized
        /** @todo force here a LIMIT to avoid reading all rows */
        while ($row = $this->results->fetchRow()) {
            foreach ($colFits as $key => $val) {
                /** @var float $stringWidth */
                $stringWidth = $this->GetStringWidth($row[$key]);
                $stringWidth += 6;
                if ($adjustingMode && ($stringWidth > $sColWidth)) {
                    // any column whose data's width is bigger than
                    // the start width is now discarded
                    unset($colFits[$key]);
                } elseif ($stringWidth > $val && $stringWidth < $sColWidth * 3) {
                    // if data's width is bigger than the current column width,
                    // enlarge the column (but avoid enlarging it if the
                    // data's width is very big)
                    $colFits[$key] = $stringWidth;
                }
            }
        }

        $totAlreadyFitted = 0;
        foreach ($colFits as $key => $val) {
            // set fitted columns to smallest size
            $this->tablewidths[$key] = $val;
            // to work out how much (if any) space has been freed up
            $totAlreadyFitted += $val;
        }

        if ($adjustingMode) {
            $surplus = (count($colFits) * $sColWidth) - $totAlreadyFitted;
            $surplusToAdd = $surplus / ($numFields - count($colFits));
        } else {
            $surplusToAdd = 0;
        }

        for ($i = 0; $i < $numFields; $i++) {
            if (! array_key_exists($i, $colFits)) {
                $this->tablewidths[$i] = $sColWidth + $surplusToAdd;
            }

            if ($this->displayColumn[$i] != false) {
                continue;
            }

            $this->tablewidths[$i] = 0;
        }

        ksort($this->tablewidths);

        // Pass 2

        $this->results = $GLOBALS['dbi']->query($query, Connection::TYPE_USER, DatabaseInterface::QUERY_UNBUFFERED);
        $this->setY($this->tMargin);
        $this->AddPage();
        $this->setFont(PdfLib::PMA_PDF_FONT, '', 9);
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $this->morepagestable($this->FontSizePt);
    }

    public function setTitleFontSize(int $titleFontSize): void
    {
        $this->titleFontSize = $titleFontSize;
    }

    public function setTitleText(string $titleText): void
    {
        $this->titleText = $titleText;
    }

    public function setCurrentDb(string|null $currentDb): void
    {
        $this->currentDb = $currentDb ?? '';
    }

    public function setCurrentTable(string|null $currentTable): void
    {
        $this->currentTable = $currentTable ?? '';
    }

    public function setDbAlias(string|null $dbAlias): void
    {
        $this->dbAlias = $dbAlias ?? '';
    }

    public function setTableAlias(string|null $tableAlias): void
    {
        $this->tableAlias = $tableAlias ?? '';
    }

    /** @param mixed[] $aliases */
    public function setAliases(array $aliases): void
    {
        $this->aliases = $aliases;
    }

    public function setPurpose(string $purpose): void
    {
        $this->purpose = $purpose;
    }
}
