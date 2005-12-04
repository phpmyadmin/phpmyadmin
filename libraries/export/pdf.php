<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Produce a PDF report (export) from a query 
 */


define('FPDF_FONTPATH', './libraries/fpdf/font/');
//if ($charset == 'utf-8') {
    define('PMA_PDF_FONT', 'FreeSans');
    require_once('./libraries/fpdf/ufpdf.php');
    class PMA_FPDF extends UFPDF
    {
    };
//} else {
//    define('PMA_PDF_FONT', 'Arial');
//    require_once('./libraries/fpdf/fpdf.php');
//    class PMA_FPDF extends FPDF {
//    };
//}


// Adapted from a LGPL script by Philip Clarke

class PMA_PDF extends PMA_FPDF 
{
    var $tablewidths;
    var $headerset;
    var $footerset;

    // overloading of a fpdf function:
    function _beginpage($orientation) 
    {
        $this->page++;
        // solved the problem of overwriting a page, if it already exists
        if (!isset($this->pages[$this->page])) {
            $this->pages[$this->page] = '';
        }
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->lasth = 0;
        $this->FontFamily = '';
    
        //Page orientation
        if (!$orientation) {
            $orientation = $this->DefOrientation;
        } else {
            $orientation = strtoupper($orientation{0});
            if ($orientation != $this->DefOrientation) {
                $this->OrientationChanges[$this->page] = true;
            }
        }
        if ($orientation != $this->CurOrientation) {
            //Change orientation
            if ($orientation == 'P') {
                $this->wPt = $this->fwPt;
                $this->hPt = $this->fhPt;
                $this->w = $this->fw;
                $this->h = $this->fh;
            } else {
                $this->wPt = $this->fhPt;
                $this->hPt = $this->fwPt;
                $this->w = $this->fh;
                $this->h = $this->fw;
            }
            $this->PageBreakTrigger = $this->h - $this->bMargin;
            $this->CurOrientation = $orientation;
        }
    }

    function Header() 
    {
        global $maxY;

        // Check if header for this page already exists
        if (!isset($this->headerset[$this->page])) {
            $fullwidth = 0;
            foreach ($this->tablewidths as $width) {
                $fullwidth += $width;
            }
            $this->SetY(($this->tMargin) - ($this->FontSizePt/$this->k)*2);
            $this->cellFontSize = $this->FontSizePt ;
            $this->SetFont(PMA_PDF_FONT, '' ,($this->titleFontSize ? $this->titleFontSize : $this->FontSizePt ));
            $this->Cell(0, $this->FontSizePt, $this->titleText, 0, 1, 'C');
            $l = ($this->lMargin);
            $this->SetFont(PMA_PDF_FONT, '', $this->cellFontSize);
            foreach ($this->colTitles as $col => $txt) {
                $this->SetXY($l, ($this->tMargin));
                $this->MultiCell($this->tablewidths[$col], $this->FontSizePt, $txt);
                $l += $this->tablewidths[$col] ;
                $maxY = ($maxY < $this->getY()) ? $this->getY() : $maxY ;
            }
            $this->SetXY($this->lMargin, $this->tMargin);
            $this->setFillColor(200, 200, 200);
            $l = ($this->lMargin);
            foreach ($this->colTitles as $col => $txt) {
                $this->SetXY($l, $this->tMargin);
                $this->cell($this->tablewidths[$col], $maxY-($this->tMargin), '', 1, 0, 'L', 1);
                $this->SetXY($l, $this->tMargin);
                $this->MultiCell($this->tablewidths[$col], $this->FontSizePt, $txt, 0, 'C');
                $l += $this->tablewidths[$col];
            }
            $this->setFillColor(255, 255, 255);
            // set headerset
            $this->headerset[$this->page] = 1;
        }

        $this->SetY($maxY);
    }

    function Footer() 
    {
    // Check if footer for this page already exists
        if (!isset($this->footerset[$this->page])) {
            $this->SetY(-15);
            //Page number
            $this->Cell(0, 10, $GLOBALS['strPageNumber'] .' '.$this->PageNo() .'/{nb}', 'T', 0, 'C');

        // set footerset
            $this->footerset[$this->page] = 1;
        }
    }

    function morepagestable($lineheight=8) 
    {
        // some things to set and 'remember'
        $l = $this->lMargin;
        $startheight = $h = $this->GetY();
        $startpage = $currpage = $this->page;

        // calculate the whole width
        $fullwidth = 0;
        foreach ($this->tablewidths as $width) {
            $fullwidth += $width;
        }

        // Now let's start to write the table
        $row = 0;
        $tmpheight = array();
        $maxpage = 0;

        while ($data = PMA_DBI_fetch_row($this->results)) {
            $this->page = $currpage;
            // write the horizontal borders
            $this->Line($l, $h, $fullwidth+$l, $h);
            // write the content and remember the height of the highest col
            foreach ($data as $col => $txt) {
                $this->page = $currpage;
                $this->SetXY($l, $h);
                $this->MultiCell($this->tablewidths[$col], $lineheight, $txt, 0, $this->colAlign[$col]);

                $l += $this->tablewidths[$col];

                if (!isset($tmpheight[$row.'-'.$this->page])) {
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


    function mysql_report($query, $attr = array())
    {
        foreach ($attr as $key => $val){
            $this->$key = $val ;
        }

        // Pass 1 for column widths
        // TODO: force here a LIMIT to speed up pass 1 ?
        $this->results = PMA_DBI_query($query, NULL, PMA_DBI_QUERY_UNBUFFERED);
        $this->numFields  = PMA_DBI_num_fields($this->results);
        $this->fields = PMA_DBI_get_fields_meta($this->results);

        // if column widths not set
        if (!isset($this->tablewidths)){

            // starting col width
            $this->sColWidth = ($this->w - $this->lMargin - $this->rMargin) / $this->numFields;

            // loop through results header and set initial col widths/ titles/ alignment
            // if a col title is less than the starting col width / reduce that column size
            for ($i=0; $i < $this->numFields; $i++){
                $stringWidth = $this->getstringwidth($this->fields[$i]->name) + 6 ;
                // set any column titles less than the start width to the column title width
                if (($stringWidth) < $this->sColWidth){
                    $colFits[$i] = $stringWidth ;
                }
                $this->colTitles[$i] = $this->fields[$i]->name;
                switch ($this->fields[$i]->type){
                case 'int':
                    $this->colAlign[$i] = 'R';
                    break;
                default:
                    $this->colAlign[$i] = 'L';
                }
            }

            // loop through the data, any column whose contents is bigger i
            // that the col size is resized
            while ($row = PMA_DBI_fetch_row($this->results)) {
                foreach ($colFits as $key => $val) {
                    $stringWidth = $this->getstringwidth($row[$key]) + 6 ;
                    if ($stringWidth > $this->sColWidth) {
                    // any col where row is bigger than the start width is now discarded
                    unset($colFits[$key]);
                    } else {
                    // if text is not bigger than the current column width setting enlarge the column
                        if ($stringWidth > $val) {
                            $colFits[$key] = ($stringWidth) ;
                        }
                    }
                }
            }

            $totAlreadyFitted = 0;
            foreach ($colFits as $key => $val){
                // set fitted columns to smallest size
                $this->tablewidths[$key] = $val;
                // to work out how much (if any) space has been freed up
                $totAlreadyFitted += $val;
            }

            $surplus = (sizeof($colFits) * $this->sColWidth) - $totAlreadyFitted;
            for ($i=0; $i < $this->numFields; $i++) {
                if (!in_array($i, array_keys($colFits))) {
                    $this->tablewidths[$i] = $this->sColWidth + ($surplus / ($this->numFields - sizeof($colFits)));
                }
            }

            ksort($this->tablewidths);

        }

        PMA_DBI_free_result($this->results);

        // Pass 2

        $this->results = PMA_DBI_query($query, NULL, PMA_DBI_QUERY_UNBUFFERED);
        $this->Open();
        $this->setY($this->tMargin);
        $this->AddPage();
        $this->morepagestable($this->FontSizePt);
        PMA_DBI_free_result($this->results);

    } // end of mysql_report function 

} // end of PMA_PDF class

/**
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  bool        Whether it suceeded
 */
function PMA_exportComment($text) 
{
    return TRUE;
}

/**
 * Outputs export footer
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportFooter() 
{
    return TRUE;
}

/**
 * Outputs export header
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportHeader() 
{
    return TRUE;
}

/**
 * Outputs database header
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBHeader($db) 
{
    return TRUE;
}

/**
 * Outputs database footer
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBFooter($db) 
{
    return TRUE;
}

/**
 * Outputs create database database
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBCreate($db) 
{
    return TRUE;
}

/**
 * Outputs the content of a table in PDF format
 *
 * @param   string      the database name
 * @param   string      the table name
 * @param   string      the end of line sequence
 * @param   string      the url to go back in case of error
 * @param   string      SQL query for obtaining data
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportData($db, $table, $crlf, $error_url, $sql_query) 
{
    global $what;
    global $pdf_report_title;

    // TODO: user-defined page orientation, paper size
    $pdf = new PMA_PDF('L', 'pt', 'A3');

    $pdf->AddFont('FreeSans', '', 'FreeSans.php');
    $pdf->AddFont('FreeSans', 'B', 'FreeSansBold.php');
    $pdf->SetFont(PMA_PDF_FONT, '', 11.5);
    $pdf->AliasNbPages();
    $attr=array('titleFontSize' => 18, 'titleText' => $pdf_report_title);
    $pdf->mysql_report($sql_query, $attr);

    // instead of $pdf->Output():
    if ($pdf->state < 3) {
        $pdf->Close();
    }
    if (!PMA_exportOutputHandler($pdf->buffer)) {
        return FALSE;
    }

    return TRUE;
} // end of the 'PMA_exportData()' function
?>
