<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Produce a PDF report (export) from a query
 *
 * @package phpMyAdmin-Export-PDF
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['pdf'] = array(
        'text' => 'strPDF',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'force_file' => true,
        'options' => array(
            array('type' => 'message_only', 'name' => 'explanation', 'text' => 'strPDFReportExplanation'),
            array('type' => 'text', 'name' => 'report_title', 'text' => 'strPDFReportTitle'),
            array('type' => 'hidden', 'name' => 'data'),
            ),
        'options_text' => 'strOptions',
        );
} else {

/**
 * Font used in PDF.
 *
 * @todo Make this configuratble (at least Sans/Serif).
 */
define('PMA_PDF_FONT', 'DejaVuSans');
require_once './libraries/tcpdf/tcpdf.php';

/**
 * Adapted from a LGPL script by Philip Clarke
 * @package phpMyAdmin-Export-PDF
 */
class PMA_PDF extends TCPDF
{
    var $tablewidths;
    var $headerset;
    var $footerset;

    // added because tcpdf for PHP 5 has a protected $buffer
    public function getBuffer()
    {
        return $this->buffer;
    }

    public function getState()
    {
        return $this->state;
    }

    // overloading of a tcpdf function:
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
            $this->SetFont(PMA_PDF_FONT, '', ($this->titleFontSize ? $this->titleFontSize : $this->FontSizePt));
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
                if ($this->tablewidths[$col] > 0) {
                    $this->MultiCell($this->tablewidths[$col], $lineheight, $txt, 0, $this->colAlign[$col]);
                    $l += $this->tablewidths[$col];
                }

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

        /**
         * Pass 1 for column widths
         */
        $this->results = PMA_DBI_query($query, null, PMA_DBI_QUERY_UNBUFFERED);
        $this->numFields  = PMA_DBI_num_fields($this->results);
        $this->fields = PMA_DBI_get_fields_meta($this->results);

        // if column widths not set
        if (!isset($this->tablewidths)){

            // sColWidth = starting col width (an average size width)
            $availableWidth = $this->w - $this->lMargin - $this->rMargin;
            $this->sColWidth = $availableWidth / $this->numFields;
            $totalTitleWidth = 0;

            // loop through results header and set initial col widths/ titles/ alignment
            // if a col title is less than the starting col width, reduce that column size
            for ($i = 0; $i < $this->numFields; $i++){
                $stringWidth = $this->getstringwidth($this->fields[$i]->name) + 6 ;
                // save the real title's width
                $titleWidth[$i] = $stringWidth;
                $totalTitleWidth += $stringWidth;

                // set any column titles less than the start width to the column title width
                if ($stringWidth < $this->sColWidth){
                    $colFits[$i] = $stringWidth ;
                }
                $this->colTitles[$i] = $this->fields[$i]->name;
                $this->display_column[$i] = true;

                switch ($this->fields[$i]->type){
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
            while ($row = PMA_DBI_fetch_row($this->results)) {
                foreach ($colFits as $key => $val) {
                    $stringWidth = $this->getstringwidth($row[$key]) + 6 ;
                    if ($adjustingMode && ($stringWidth > $this->sColWidth)) {
                    // any column whose data's width is bigger than the start width is now discarded
                        unset($colFits[$key]);
                    } else {
                    // if data's width is bigger than the current column width,
                    // enlarge the column (but avoid enlarging it if the
                    // data's width is very big)
                            if ($stringWidth > $val && $stringWidth < ($this->sColWidth * 3)) {
                            $colFits[$key] = $stringWidth ;
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

            if ($adjustingMode) {
                $surplus = (sizeof($colFits) * $this->sColWidth) - $totAlreadyFitted;
                $surplusToAdd = $surplus / ($this->numFields - sizeof($colFits));
            } else {
                $surplusToAdd = 0;
            }

            for ($i=0; $i < $this->numFields; $i++) {
                if (!in_array($i, array_keys($colFits))) {
                    $this->tablewidths[$i] = $this->sColWidth + $surplusToAdd;
                }
                if ($this->display_column[$i] == false) {
                    $this->tablewidths[$i] = 0;
                }
            }

            ksort($this->tablewidths);
        }
        PMA_DBI_free_result($this->results);

        // Pass 2

        $this->results = PMA_DBI_query($query, null, PMA_DBI_QUERY_UNBUFFERED);
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
 * @todo    user-defined page orientation, paper size
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

    $pdf = new PMA_PDF('L', 'pt', 'A3');

    $pdf->AddFont('DejaVuSans', '', 'dejavusans.php');
    $pdf->AddFont('DejaVuSans', 'B', 'dejavusans-bold.php');
    $pdf->AddFont('DejaVuSerif', '', 'dejavuserif.php');
    $pdf->AddFont('DejaVuSerif', 'B', 'dejavuserif-bold.php');
    $pdf->SetFont(PMA_PDF_FONT, '', 11.5);
    $pdf->AliasNbPages();
    $attr=array('titleFontSize' => 18, 'titleText' => $pdf_report_title);
    $pdf->mysql_report($sql_query, $attr);

    // instead of $pdf->Output():
    if ($pdf->getState() < 3) {
        $pdf->Close();
    }
    if (!PMA_exportOutputHandler($pdf->getBuffer())) {
        return FALSE;
    }

    return TRUE;
} // end of the 'PMA_exportData()' function
}
?>
