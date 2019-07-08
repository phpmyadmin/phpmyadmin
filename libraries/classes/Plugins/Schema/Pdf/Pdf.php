<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PDF schema handling
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Pdf;

use PhpMyAdmin\Pdf as PdfLib;
use PhpMyAdmin\Relation;
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
if (getcwd() == __DIR__) {
    die('Attack stopped');
}

/**
 * Extends the "TCPDF" class and helps
 * in developing the structure of PDF Schema Export
 *
 * @access  public
 * @package PhpMyAdmin
 * @see     TCPDF
 */
class Pdf extends PdfLib
{
    /**
     * Defines properties
     */
    public $_xMin;
    public $_yMin;
    public $leftMargin = 10;
    public $topMargin = 10;
    public $scale;
    public $PMA_links;
    public $Outlines = [];
    public $def_outlines;
    public $widths;
    public $cMargin;
    private $_ff = PdfLib::PMA_PDF_FONT;
    private $_offline;
    private $_pageNumber;
    private $_withDoc;
    private $_db;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * Constructs PDF for schema export.
     *
     * @param string  $orientation page orientation
     * @param string  $unit        unit
     * @param string  $paper       the format used for pages
     * @param int     $pageNumber  schema page number that is being exported
     * @param boolean $withDoc     with document dictionary
     * @param string  $db          the database name
     *
     * @access public
     */
    public function __construct(
        $orientation,
        $unit,
        $paper,
        $pageNumber,
        $withDoc,
        $db
    ) {
        parent::__construct($orientation, $unit, $paper);
        $this->_pageNumber = $pageNumber;
        $this->_withDoc = $withDoc;
        $this->_db = $db;
        $this->relation = new Relation($GLOBALS['dbi']);
    }

    /**
     * Sets the value for margins
     *
     * @param float $c_margin margin
     *
     * @return void
     */
    public function setCMargin($c_margin)
    {
        $this->cMargin = $c_margin;
    }

    /**
     * Sets the scaling factor, defines minimum coordinates and margins
     *
     * @param float|int $scale      The scaling factor
     * @param float|int $xMin       The minimum X coordinate
     * @param float|int $yMin       The minimum Y coordinate
     * @param float|int $leftMargin The left margin
     * @param float|int $topMargin  The top margin
     *
     * @return void
     */
    public function setScale(
        $scale = 1,
        $xMin = 0,
        $yMin = 0,
        $leftMargin = -1,
        $topMargin = -1
    ) {
        $this->scale = $scale;
        $this->_xMin = $xMin;
        $this->_yMin = $yMin;
        if ($this->leftMargin != -1) {
            $this->leftMargin = $leftMargin;
        }
        if ($this->topMargin != -1) {
            $this->topMargin = $topMargin;
        }
    }

    /**
     * Outputs a scaled cell
     *
     * @param float|int $w      The cell width
     * @param float|int $h      The cell height
     * @param string    $txt    The text to output
     * @param mixed     $border Whether to add borders or not
     * @param integer   $ln     Where to put the cursor once the output is done
     * @param string    $align  Align mode
     * @param integer   $fill   Whether to fill the cell with a color or not
     * @param string    $link   Link
     *
     * @return void
     *
     * @see TCPDF::Cell()
     */
    public function cellScale(
        $w,
        $h = 0,
        $txt = '',
        $border = 0,
        $ln = 0,
        $align = '',
        $fill = 0,
        $link = ''
    ) {
        $h /= $this->scale;
        $w /= $this->scale;
        $this->Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    /**
     * Draws a scaled line
     *
     * @param float $x1 The horizontal position of the starting point
     * @param float $y1 The vertical position of the starting point
     * @param float $x2 The horizontal position of the ending point
     * @param float $y2 The vertical position of the ending point
     *
     * @return void
     *
     * @see TCPDF::Line()
     */
    public function lineScale($x1, $y1, $x2, $y2)
    {
        $x1 = ($x1 - $this->_xMin) / $this->scale + $this->leftMargin;
        $y1 = ($y1 - $this->_yMin) / $this->scale + $this->topMargin;
        $x2 = ($x2 - $this->_xMin) / $this->scale + $this->leftMargin;
        $y2 = ($y2 - $this->_yMin) / $this->scale + $this->topMargin;
        $this->Line($x1, $y1, $x2, $y2);
    }

    /**
     * Sets x and y scaled positions
     *
     * @param float $x The x position
     * @param float $y The y position
     *
     * @return void
     *
     * @see TCPDF::SetXY()
     */
    public function setXyScale($x, $y)
    {
        $x = ($x - $this->_xMin) / $this->scale + $this->leftMargin;
        $y = ($y - $this->_yMin) / $this->scale + $this->topMargin;
        $this->SetXY($x, $y);
    }

    /**
     * Sets the X scaled positions
     *
     * @param float $x The x position
     *
     * @return void
     *
     * @see TCPDF::SetX()
     */
    public function setXScale($x)
    {
        $x = ($x - $this->_xMin) / $this->scale + $this->leftMargin;
        $this->SetX($x);
    }

    /**
     * Sets the scaled font size
     *
     * @param float $size The font size (in points)
     *
     * @return void
     *
     * @see TCPDF::SetFontSize()
     */
    public function setFontSizeScale($size)
    {
        // Set font size in points
        $size /= $this->scale;
        $this->SetFontSize($size);
    }

    /**
     * Sets the scaled line width
     *
     * @param float $width The line width
     *
     * @return void
     *
     * @see TCPDF::SetLineWidth()
     */
    public function setLineWidthScale($width)
    {
        $width /= $this->scale;
        $this->SetLineWidth($width);
    }

    /**
     * This method is used to render the page header.
     *
     * @return void
     *
     * @see TCPDF::Header()
     */
    // @codingStandardsIgnoreLine
    public function Header()
    {
        // We only show this if we find something in the new pdf_pages table

        // This function must be named "Header" to work with the TCPDF library
        if ($this->_withDoc) {
            if ($this->_offline || $this->_pageNumber == -1) {
                $pg_name = __("PDF export page");
            } else {
                $test_query = 'SELECT * FROM '
                    . Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . Util::backquote($GLOBALS['cfgRelation']['pdf_pages'])
                    . ' WHERE db_name = \'' . $GLOBALS['dbi']->escapeString($this->_db)
                    . '\' AND page_nr = \'' . $this->_pageNumber . '\'';
                $test_rs = $this->relation->queryAsControlUser($test_query);
                $pages = @$GLOBALS['dbi']->fetchAssoc($test_rs);
                $pg_name = ucfirst($pages['page_descr']);
            }

            $this->SetFont($this->_ff, 'B', 14);
            $this->Cell(0, 6, $pg_name, 'B', 1, 'C');
            $this->SetFont($this->_ff, '');
            $this->Ln();
        }
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     *
     * @return void
     *
     * @see PDF::Footer()
     */
    // @codingStandardsIgnoreLine
    public function Footer()
    {
        if ($this->_withDoc) {
            parent::Footer();
        }
    }

    /**
     * Sets widths
     *
     * @param array $w array of widths
     *
     * @return void
     */
    public function setWidths(array $w)
    {
        // column widths
        $this->widths = $w;
    }

    /**
     * Generates table row.
     *
     * @param array $data  Data for table
     * @param array $links Links for table cells
     *
     * @return void
     */
    public function row(array $data, array $links)
    {
        // line height
        $nb = 0;
        $data_cnt = count($data);
        for ($i = 0; $i < $data_cnt; $i++) {
            $nb = max($nb, $this->numLines($this->widths[$i], $data[$i]));
        }
        $il = $this->FontSize;
        $h = ($il + 1) * $nb;
        // page break if necessary
        $this->checkPageBreak($h);
        // draw the cells
        $data_cnt = count($data);
        for ($i = 0; $i < $data_cnt; $i++) {
            $w = $this->widths[$i];
            // save current position
            $x = $this->GetX();
            $y = $this->GetY();
            // draw the border
            $this->Rect($x, $y, $w, $h);
            if (isset($links[$i])) {
                $this->Link($x, $y, $w, $h, $links[$i]);
            }
            // print text
            $this->MultiCell($w, $il + 1, $data[$i], 0, 'L');
            // go to right side
            $this->SetXY($x + $w, $y);
        }
        // go to line
        $this->Ln($h);
    }

    /**
     * Compute number of lines used by a multicell of width w
     *
     * @param int    $w   width
     * @param string $txt text
     *
     * @return int
     */
    public function numLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += isset($cw[mb_ord($c)]) ? $cw[mb_ord($c)] : 0 ;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    /**
     * Set whether the document is generated from client side DB
     *
     * @param string $value whether offline
     *
     * @return void
     *
     * @access private
     */
    public function setOffline($value)
    {
        $this->_offline = $value;
    }
}
