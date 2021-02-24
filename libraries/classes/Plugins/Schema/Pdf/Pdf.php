<?php
/**
 * PDF schema handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Pdf;

use PhpMyAdmin\Pdf as PdfLib;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Util;
use function class_exists;
use function count;
use function getcwd;
use function max;
use function mb_ord;
use function str_replace;
use function strlen;
use function ucfirst;
use function is_array;

// phpcs:disable PSR1.Files.SideEffects
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
// phpcs:enable

/**
 * Extends the "TCPDF" class and helps
 * in developing the structure of PDF Schema Export
 *
 * @see     TCPDF
 *
 * @access  public
 */
class Pdf extends PdfLib
{
    /** @var int|float */
    public $xMin;

    /** @var int|float */
    public $yMin;

    /** @var int|float */
    public $leftMargin = 10;

    /** @var int|float */
    public $topMargin = 10;

    /** @var int|float */
    public $scale;

    /** @var array */
    public $customLinks;

    /** @var array */
    public $widths;

    /** @var float */
    public $cMargin;

    /** @var string */
    private $ff = PdfLib::PMA_PDF_FONT;

    /** @var string */
    private $offline;

    /** @var int */
    private $pageNumber;

    /** @var bool */
    private $withDoc;

    /** @var string */
    private $db;

    /** @var Relation */
    private $relation;

    /**
     * Constructs PDF for schema export.
     *
     * @param string $orientation page orientation
     * @param string $unit        unit
     * @param string $paper       the format used for pages
     * @param int    $pageNumber  schema page number that is being exported
     * @param bool   $withDoc     with document dictionary
     * @param string $db          the database name
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
        global $dbi;

        parent::__construct($orientation, $unit, $paper);
        $this->pageNumber = $pageNumber;
        $this->withDoc = $withDoc;
        $this->db = $db;
        $this->relation = new Relation($dbi);
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
        $this->xMin = $xMin;
        $this->yMin = $yMin;
        if ($this->leftMargin != -1) {
            $this->leftMargin = $leftMargin;
        }
        if ($this->topMargin == -1) {
            return;
        }

        $this->topMargin = $topMargin;
    }

    /**
     * Outputs a scaled cell
     *
     * @see TCPDF::Cell()
     *
     * @param float|int $w      The cell width
     * @param float|int $h      The cell height
     * @param string    $txt    The text to output
     * @param mixed     $border Whether to add borders or not
     * @param int       $ln     Where to put the cursor once the output is done
     * @param string    $align  Align mode
     * @param int       $fill   Whether to fill the cell with a color or not
     * @param string    $link   Link
     *
     * @return void
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
     * @see TCPDF::Line()
     *
     * @param float $x1 The horizontal position of the starting point
     * @param float $y1 The vertical position of the starting point
     * @param float $x2 The horizontal position of the ending point
     * @param float $y2 The vertical position of the ending point
     *
     * @return void
     */
    public function lineScale($x1, $y1, $x2, $y2)
    {
        $x1 = ($x1 - $this->xMin) / $this->scale + $this->leftMargin;
        $y1 = ($y1 - $this->yMin) / $this->scale + $this->topMargin;
        $x2 = ($x2 - $this->xMin) / $this->scale + $this->leftMargin;
        $y2 = ($y2 - $this->yMin) / $this->scale + $this->topMargin;
        $this->Line($x1, $y1, $x2, $y2);
    }

    /**
     * Sets x and y scaled positions
     *
     * @see TCPDF::SetXY()
     *
     * @param float $x The x position
     * @param float $y The y position
     *
     * @return void
     */
    public function setXyScale($x, $y)
    {
        $x = ($x - $this->xMin) / $this->scale + $this->leftMargin;
        $y = ($y - $this->yMin) / $this->scale + $this->topMargin;
        $this->SetXY($x, $y);
    }

    /**
     * Sets the X scaled positions
     *
     * @see TCPDF::SetX()
     *
     * @param float $x The x position
     *
     * @return void
     */
    public function setXScale($x)
    {
        $x = ($x - $this->xMin) / $this->scale + $this->leftMargin;
        $this->SetX($x);
    }

    /**
     * Sets the scaled font size
     *
     * @see TCPDF::SetFontSize()
     *
     * @param float $size The font size (in points)
     *
     * @return void
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
     * @see TCPDF::SetLineWidth()
     *
     * @param float $width The line width
     *
     * @return void
     */
    public function setLineWidthScale($width)
    {
        $width /= $this->scale;
        $this->SetLineWidth($width);
    }

    /**
     * This method is used to render the page header.
     *
     * @see TCPDF::Header()
     *
     * @return void
     */
    // @codingStandardsIgnoreLine
    public function Header()
    {
        global $dbi;

        // We only show this if we find something in the new pdf_pages table

        // This function must be named "Header" to work with the TCPDF library
        if (! $this->withDoc) {
            return;
        }

        if ($this->offline || $this->pageNumber == -1) {
            $pg_name = __('PDF export page');
        } else {
            $test_query = 'SELECT * FROM '
                . Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
                . Util::backquote($GLOBALS['cfgRelation']['pdf_pages'])
                . ' WHERE db_name = \'' . $dbi->escapeString($this->db)
                . '\' AND page_nr = \'' . $this->pageNumber . '\'';
            $test_rs = $this->relation->queryAsControlUser($test_query);
            $pageDesc = '';
            $pages = $dbi->fetchAssoc($test_rs);
            if (is_array($pages)) {
                $pageDesc = (string) $pages['page_descr'];
            }
            $pg_name = ucfirst($pageDesc);
        }

        $this->SetFont($this->ff, 'B', 14);
        $this->Cell(0, 6, $pg_name, 'B', 1, 'C');
        $this->SetFont($this->ff, '');
        $this->Ln();
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     *
     * @see PDF::Footer()
     *
     * @return void
     */
    // @codingStandardsIgnoreLine
    public function Footer()
    {
        if (! $this->withDoc) {
            return;
        }

        parent::Footer();
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
            if ($c === ' ') {
                $sep = $i;
            }
            $l += $cw[mb_ord($c)] ?? 0;
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
        $this->offline = $value;
    }
}
