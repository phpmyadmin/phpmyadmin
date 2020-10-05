<?php
/**
 * TCPDF wrapper class.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use Exception;
use TCPDF;
use TCPDF_FONTS;
use function count;
use function strlen;
use function strtr;

/**
 * PDF export base class providing basic configuration.
 */
class Pdf extends TCPDF
{
    /** @var array */
    public $footerset;

    /** @var array */
    public $alias = [];

    /**
     * PDF font to use.
     */
    public const PMA_PDF_FONT = 'DejaVuSans';

    /**
     * Constructs PDF and configures standard parameters.
     *
     * @param string $orientation page orientation
     * @param string $unit        unit
     * @param string $format      the format used for pages
     * @param bool   $unicode     true means that the input text is unicode
     * @param string $encoding    charset encoding; default is UTF-8.
     * @param bool   $diskcache   if true reduce the RAM memory usage by caching
     *                            temporary data on filesystem (slower).
     * @param bool   $pdfa        If TRUE set the document to PDF/A mode.
     *
     * @throws Exception
     *
     * @access public
     */
    public function __construct(
        $orientation = 'P',
        $unit = 'mm',
        $format = 'A4',
        $unicode = true,
        $encoding = 'UTF-8',
        $diskcache = false,
        $pdfa = false
    ) {
        parent::__construct(
            $orientation,
            $unit,
            $format,
            $unicode,
            $encoding,
            $diskcache,
            $pdfa
        );
        $this->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $this->AddFont('DejaVuSans', '', 'dejavusans.php');
        $this->AddFont('DejaVuSans', 'B', 'dejavusansb.php');
        $this->SetFont(self::PMA_PDF_FONT, '', 14);
        $this->setFooterFont([self::PMA_PDF_FONT, '', 14]);
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     *
     * @return void
     */
    // @codingStandardsIgnoreLine
    public function Footer()
    {
        // Check if footer for this page already exists
        if (isset($this->footerset[$this->page])) {
            return;
        }

        $this->SetY(-15);
        $this->SetFont(self::PMA_PDF_FONT, '', 14);
        $this->Cell(
            0,
            6,
            __('Page number:') . ' '
            . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(),
            'T',
            0,
            'C'
        );
        $this->Cell(0, 6, Util::localisedDate(), 0, 1, 'R');
        $this->SetY(20);

        // set footerset
        $this->footerset[$this->page] = 1;
    }

    /**
     * Function to set alias which will be expanded on page rendering.
     *
     * @param string $name  name of the alias
     * @param string $value value of the alias
     *
     * @return void
     */
    public function setAlias($name, $value)
    {
        $name = TCPDF_FONTS::UTF8ToUTF16BE(
            $name,
            false,
            true,
            $this->CurrentFont
        );
        $this->alias[$name] = TCPDF_FONTS::UTF8ToUTF16BE(
            $value,
            false,
            true,
            $this->CurrentFont
        );
    }

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

    /**
     * Improved with alias expanding.
     *
     * @return void
     */
    public function _putpages()
    {
        if (count($this->alias) > 0) {
            $nbPages = count($this->pages);
            for ($n = 1; $n <= $nbPages; $n++) {
                $this->pages[$n] = strtr($this->pages[$n], $this->alias);
            }
        }
        parent::_putpages();
        // phpcs:enable
    }

    /**
     * Displays an error message
     *
     * @param string $error_message the error message
     *
     * @return void
     */
    // @codingStandardsIgnoreLine
    public function Error($error_message = '')
    {
        echo Message::error(
            __('Error while creating PDF:') . ' ' . $error_message
        )->getDisplay();
        exit;
    }

    /**
     * Sends file as a download to user.
     *
     * @param string $filename file name
     *
     * @return void
     */
    public function download($filename)
    {
        $pdfData = $this->getPDFData();
        Response::getInstance()->disable();
        Core::downloadHeader(
            $filename,
            'application/pdf',
            strlen($pdfData)
        );
        echo $pdfData;
    }
}
