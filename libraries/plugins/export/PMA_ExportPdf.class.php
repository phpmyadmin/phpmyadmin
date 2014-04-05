<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * TableProperty class
 *
 * @package    PhpMyAdmin-Export
 * @subpackage PDF
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the PDF class */
require_once 'libraries/PDF.class.php';

/**
 * Adapted from a LGPL script by Philip Clarke
 *
 * @package    PhpMyAdmin-Export
 * @subpackage PDF
 */
class PMA_ExportPdf extends PMA_PDF
{
    var $tablewidths;
    var $headerset;

    /**
     * Add page if needed.
     *
     * @param float   $h       cell height. Default value: 0
     * @param mixed   $y       starting y position, leave empty for current position
     * @param boolean $addpage if true add a page, otherwise only return
     *                         the true/false state
     *
     * @return boolean true in case of page break, false otherwise.
     */
    function checkPageBreak($h = 0, $y = '', $addpage = true)
    {
        if (TCPDF_STATIC::empty_string($y)) {
            $y = $this->y;
        }
        $current_page = $this->page;
        if ((($y + $h) > $this->PageBreakTrigger)
            AND (! $this->InFooter)
            AND ($this->AcceptPageBreak())
        ) {
            if ($addpage) {
                //Automatic page break
                $x = $this->x;
                $this->AddPage($this->CurOrientation);
                $this->y = $this->dataY;
                $oldpage = $this->page - 1;

                $this_page_orm = $this->pagedim[$this->page]['orm'];
                $old_page_orm = $this->pagedim[$oldpage]['orm'];
                $this_page_olm = $this->pagedim[$this->page]['olm'];
                $old_page_olm = $this->pagedim[$oldpage]['olm'];
                if ($this->rtl) {
                    if ($this_page_orm!= $old_page_orm) {
                        $this->x = $x - ($this_page_orm - $old_page_orm);
                    } else {
                        $this->x = $x;
                    }
                } else {
                    if ($this_page_olm != $old_page_olm) {
                        $this->x = $x + ($this_page_olm - $old_page_olm);
                    } else {
                        $this->x = $x;
                    }
                }
            }
            return true;
        }
        if ($current_page != $this->page) {
            // account for columns mode
            return true;
        }
        return false;
    }

    /**
     * This method is used to render the page header.
     *
     * @return void
     */
    function Header()
    {
        global $maxY;
        // We don't want automatic page breaks while generating header
        // as this can lead to infinite recursion as auto generated page
        // will want header as well causing another page break
        // FIXME: Better approach might be to try to compact the content
        $this->SetAutoPageBreak(false);
        // Check if header for this page already exists
        if (! isset($this->headerset[$this->page])) {
            $fullwidth = 0;
            foreach ($this->tablewidths as $width) {
                $fullwidth += $width;
            }
            $this->SetY(($this->tMargin) - ($this->FontSizePt / $this->k) * 5);
            $this->cellFontSize = $this->FontSizePt ;
            $this->SetFont(
                PMA_PDF_FONT,
                '',
                ($this->titleFontSize
                ? $this->titleFontSize
                : $this->FontSizePt)
            );
            $this->Cell(0, $this->FontSizePt, $this->titleText, 0, 1, 'C');
            $this->SetFont(PMA_PDF_FONT, '', $this->cellFontSize);
            $this->SetY(($this->tMargin) - ($this->FontSizePt / $this->k) * 2.5);
            $this->Cell(
                0,
                $this->FontSizePt,
                __('Database:') . ' ' . $this->currentDb . ',  '
                . __('Table:') . ' ' . $this->currentTable,
                0, 1, 'L'
            );
            $l = ($this->lMargin);
            foreach ($this->colTitles as $col => $txt) {
                $this->SetXY($l, ($this->tMargin));
                $this->MultiCell(
                    $this->tablewidths[$col],
                    $this->FontSizePt,
                    $txt
                );
                $l += $this->tablewidths[$col] ;
                $maxY = ($maxY < $this->getY()) ? $this->getY() : $maxY ;
            }
            $this->SetXY($this->lMargin, $this->tMargin);
            $this->setFillColor(200, 200, 200);
            $l = ($this->lMargin);
            foreach ($this->colTitles as $col => $txt) {
                $this->SetXY($l, $this->tMargin);
                $this->cell(
                    $this->tablewidths[$col],
                    $maxY-($this->tMargin),
                    '',
                    1,
                    0,
                    'L',
                    1
                );
                $this->SetXY($l, $this->tMargin);
                $this->MultiCell(
                    $this->tablewidths[$col],
                    $this->FontSizePt,
                    $txt,
                    0,
                    'C'
                );
                $l += $this->tablewidths[$col];
            }
            $this->setFillColor(255, 255, 255);
            // set headerset
            $this->headerset[$this->page] = 1;
        }

        $this->dataY = $maxY;
        $this->SetAutoPageBreak(true);
    }

    /**
     * Generate table
     *
     * @param int $lineheight Height of line
     *
     * @return void
     */
    function morepagestable($lineheight = 8)
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
        $tmpheight = array();
        $maxpage = $this->page;

        while ($data = $GLOBALS['dbi']->fetchRow($this->results)) {
            $this->page = $currpage;
            // write the horizontal borders
            $this->Line($l, $h, $fullwidth+$l, $h);
            // write the content and remember the height of the highest col
            foreach ($data as $col => $txt) {
                $this->page = $currpage;
                $this->SetXY($l, $h);
                if ($this->tablewidths[$col] > 0) {
                    $this->MultiCell(
                        $this->tablewidths[$col],
                        $lineheight,
                        $txt,
                        0,
                        $this->colAlign[$col]
                    );
                    $l += $this->tablewidths[$col];
                }

                if (! isset($tmpheight[$row.'-'.$this->page])) {
                    $tmpheight[$row.'-'.$this->page] = 0;
                }
                if ($tmpheight[$row.'-'.$this->page] < $this->GetY()) {
                    $tmpheight[$row.'-'.$this->page] = $this->GetY();
                }
                if ($this->page > $maxpage) {
                    $maxpage = $this->page;
                }
                unset($data[$col]);
            }

            // get the height we were in the last used page
            $h = $tmpheight[$row.'-'.$maxpage];
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
        $this->Line($l, $h, $fullwidth+$l, $h);
        // now we start at the top of the document and walk down
        for ($i = $startpage; $i <= $maxpage; $i++) {
            $this->page = $i;
            $l = $this->lMargin;
            $t = ($i == $startpage) ? $startheight : $this->tMargin;
            $lh = ($i == $maxpage) ? $h : $this->h-$this->bMargin;
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
     * Sets a set of attributes.
     *
     * @param array $attr array containing the attributes
     *
     * @return void
     */
    function setAttributes($attr = array())
    {
        foreach ($attr as $key => $val) {
            $this->$key = $val ;
        }
    }

    /**
     * Defines the top margin.
     * The method can be called before creating the first page.
     *
     * @param float $topMargin the margin
     *
     * @return void
     */
    function setTopMargin($topMargin)
    {
        $this->tMargin = $topMargin;
    }

    /**
     * MySQL report
     *
     * @param string $query Query to execute
     *
     * @return void
     */
    function mysqlReport($query)
    {
        unset($this->tablewidths);
        unset($this->colTitles);
        unset($this->titleWidth);
        unset($this->colFits);
        unset($this->display_column);
        unset($this->colAlign);

        /**
         * Pass 1 for column widths
         */
        $this->results = $GLOBALS['dbi']->query(
            $query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
        );
        $this->numFields  = $GLOBALS['dbi']->numFields($this->results);
        $this->fields = $GLOBALS['dbi']->getFieldsMeta($this->results);

        // sColWidth = starting col width (an average size width)
        $availableWidth = $this->w - $this->lMargin - $this->rMargin;
        $this->sColWidth = $availableWidth / $this->numFields;
        $totalTitleWidth = 0;

        // loop through results header and set initial
        // col widths/ titles/ alignment
        // if a col title is less than the starting col width,
        // reduce that column size
        $colFits = array();
        for ($i = 0; $i < $this->numFields; $i++) {
            $stringWidth = $this->getstringwidth($this->fields[$i]->name) + 6 ;
            // save the real title's width
            $titleWidth[$i] = $stringWidth;
            $totalTitleWidth += $stringWidth;

            // set any column titles less than the start width to
            // the column title width
            if ($stringWidth < $this->sColWidth) {
                $colFits[$i] = $stringWidth ;
            }
            $this->colTitles[$i] = $this->fields[$i]->name;
            $this->display_column[$i] = true;

            switch ($this->fields[$i]->type) {
            case 'int':
                $this->colAlign[$i] = 'R';
                break;
            case 'blob':
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
                /**
                 * @todo do not deactivate completely the display
                 * but show the field's name and [BLOB]
                 */
                if (stristr($this->fields[$i]->flags, 'BINARY')) {
                    $this->display_column[$i] = false;
                    unset($this->colTitles[$i]);
                }
                $this->colAlign[$i] = 'L';
                break;
            default:
                $this->colAlign[$i] = 'L';
            }
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
        /**
          * @todo force here a LIMIT to avoid reading all rows
          */
        while ($row = $GLOBALS['dbi']->fetchRow($this->results)) {
            foreach ($colFits as $key => $val) {
                $stringWidth = $this->getstringwidth($row[$key]) + 6 ;
                if ($adjustingMode && ($stringWidth > $this->sColWidth)) {
                    // any column whose data's width is bigger than
                    // the start width is now discarded
                    unset($colFits[$key]);
                } else {
                    // if data's width is bigger than the current column width,
                    // enlarge the column (but avoid enlarging it if the
                    // data's width is very big)
                    if ($stringWidth > $val
                        && $stringWidth < ($this->sColWidth * 3)
                    ) {
                        $colFits[$key] = $stringWidth ;
                    }
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
            $surplus = (sizeof($colFits) * $this->sColWidth) - $totAlreadyFitted;
            $surplusToAdd = $surplus / ($this->numFields - sizeof($colFits));
        } else {
            $surplusToAdd = 0;
        }

        for ($i = 0; $i < $this->numFields; $i++) {
            if (! in_array($i, array_keys($colFits))) {
                $this->tablewidths[$i] = $this->sColWidth + $surplusToAdd;
            }
            if ($this->display_column[$i] == false) {
                $this->tablewidths[$i] = 0;
            }
        }

        ksort($this->tablewidths);

        $GLOBALS['dbi']->freeResult($this->results);

        // Pass 2

        $this->results = $GLOBALS['dbi']->query(
            $query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
        );
        $this->setY($this->tMargin);
        $this->AddPage();
        $this->SetFont(PMA_PDF_FONT, '', 9);
        $this->morepagestable($this->FontSizePt);
        $GLOBALS['dbi']->freeResult($this->results);

    } // end of mysqlReport function

} // end of PMA_Export_PDF class
?>
