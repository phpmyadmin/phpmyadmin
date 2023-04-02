<?php
/**
 * PDF schema handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Pdf;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Pdf as PdfLib;
use PhpMyAdmin\Util;

use function __;
use function count;
use function getcwd;
use function is_string;
use function max;
use function mb_ord;
use function str_replace;
use function strlen;
use function ucfirst;

// phpcs:disable PSR1.Files.SideEffects
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
 */
class Pdf extends PdfLib
{
    public int|float $xMin = 0;

    public int|float $yMin = 0;

    public int|float $leftMargin = 10;

    public int|float $topMargin = 10;

    public int|float $scale = 1;

    /** @var mixed[] */
    public array $customLinks = [];

    /** @var mixed[] */
    public array $widths = [];

    public float $cMargin = 0;

    private string $ff = PdfLib::PMA_PDF_FONT;

    private bool $offline = false;

    private Relation $relation;

    /**
     * Constructs PDF for schema export.
     *
     * @param string $orientation page orientation
     * @param string $unit        unit
     * @param string $paper       the format used for pages
     * @param int    $pageNumber  schema page number that is being exported
     * @param bool   $withDoc     with document dictionary
     * @param string $db          the database name
     */
    public function __construct(
        string $orientation,
        string $unit,
        string $paper,
        private int $pageNumber,
        private bool $withDoc,
        private string $db,
    ) {
        parent::__construct($orientation, $unit, $paper);

        $this->relation = new Relation($GLOBALS['dbi']);
    }

    /**
     * Sets the value for margins
     *
     * @param float $cMargin margin
     */
    public function setCMargin(float $cMargin): void
    {
        $this->cMargin = $cMargin;
    }

    /**
     * Sets the scaling factor, defines minimum coordinates and margins
     *
     * @param float|int $scale      The scaling factor
     * @param float|int $xMin       The minimum X coordinate
     * @param float|int $yMin       The minimum Y coordinate
     * @param float|int $leftMargin The left margin
     * @param float|int $topMargin  The top margin
     */
    public function setScale(
        float|int $scale = 1,
        float|int $xMin = 0,
        float|int $yMin = 0,
        float|int $leftMargin = -1,
        float|int $topMargin = -1,
    ): void {
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
     * @param bool      $fill   Whether to fill the cell with a color or not
     * @param string    $link   Link
     */
    public function cellScale(
        float|int $w,
        float|int $h = 0,
        string $txt = '',
        mixed $border = 0,
        int $ln = 0,
        string $align = '',
        bool $fill = false,
        string $link = '',
    ): void {
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
     */
    public function lineScale(float $x1, float $y1, float $x2, float $y2): void
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
     * @see TCPDF::setXY()
     *
     * @param float $x The x position
     * @param float $y The y position
     */
    public function setXyScale(float $x, float $y): void
    {
        $x = ($x - $this->xMin) / $this->scale + $this->leftMargin;
        $y = ($y - $this->yMin) / $this->scale + $this->topMargin;
        $this->setXY($x, $y);
    }

    /**
     * Sets the X scaled positions
     *
     * @see TCPDF::setX()
     *
     * @param float $x The x position
     */
    public function setXScale(float $x): void
    {
        $x = ($x - $this->xMin) / $this->scale + $this->leftMargin;
        $this->setX($x);
    }

    /**
     * Sets the scaled font size
     *
     * @see TCPDF::setFontSize()
     *
     * @param float $size The font size (in points)
     */
    public function setFontSizeScale(float $size): void
    {
        // Set font size in points
        $size /= $this->scale;
        $this->setFontSize($size);
    }

    /**
     * Sets the scaled line width
     *
     * @see TCPDF::setLineWidth()
     *
     * @param float $width The line width
     */
    public function setLineWidthScale(float $width): void
    {
        $width /= $this->scale;
        $this->setLineWidth($width);
    }

    /**
     * This method is used to render the page header.
     *
     * @see TCPDF::Header()
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function Header(): void
    {
        // We only show this if we find something in the new pdf_pages table

        // This function must be named "Header" to work with the TCPDF library
        if (! $this->withDoc) {
            return;
        }

        $pdfFeature = $this->relation->getRelationParameters()->pdfFeature;
        if ($this->offline || $this->pageNumber == -1 || $pdfFeature === null) {
            $pgName = __('PDF export page');
        } else {
            $testQuery = 'SELECT * FROM '
                . Util::backquote($pdfFeature->database) . '.'
                . Util::backquote($pdfFeature->pdfPages)
                . ' WHERE db_name = ' . $GLOBALS['dbi']->quoteString($this->db, Connection::TYPE_CONTROL)
                . ' AND page_nr = ' . $this->pageNumber;
            $testRs = $GLOBALS['dbi']->queryAsControlUser($testQuery);
            $pageDesc = (string) $testRs->fetchValue('page_descr');

            $pgName = ucfirst($pageDesc);
        }

        $this->setFont($this->ff, 'B', 14);
        $this->Cell(0, 6, $pgName, 'B', 1, 'C');
        $this->setFont($this->ff, '');
        $this->Ln();
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     *
     * @see PDF::Footer()
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function Footer(): void
    {
        if (! $this->withDoc) {
            return;
        }

        parent::Footer();
    }

    /**
     * Sets widths
     *
     * @param mixed[] $w array of widths
     */
    public function setWidths(array $w): void
    {
        // column widths
        $this->widths = $w;
    }

    /**
     * Generates table row.
     *
     * @param mixed[] $data  Data for table
     * @param mixed[] $links Links for table cells
     */
    public function row(array $data, array $links): void
    {
        // line height
        $nb = 0;
        $dataCnt = count($data);
        for ($i = 0; $i < $dataCnt; $i++) {
            $nb = max($nb, $this->numLines($this->widths[$i], $data[$i]));
        }

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $il = $this->FontSize;
        $h = ($il + 1) * $nb;
        // page break if necessary
        $this->checkPageBreak($h);
        // draw the cells
        $dataCnt = count($data);
        for ($i = 0; $i < $dataCnt; $i++) {
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
            $this->setXY($x + $w, $y);
        }

        // go to line
        $this->Ln($h);
    }

    /**
     * Compute number of lines used by a multicell of width w
     *
     * @param int    $w   width
     * @param string $txt text
     */
    public function numLines(int $w, string $txt): int
    {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
                    if ($i === $j) {
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
     * @param bool $value whether offline
     */
    public function setOffline(bool $value): void
    {
        $this->offline = $value;
    }

    public function getOutputData(): string
    {
        /** @var mixed $data */
        $data = $this->getPDFData();

        return is_string($data) ? $data : '';
    }
}
