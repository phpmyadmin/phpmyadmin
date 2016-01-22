<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * TCPDF wrapper class.
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

use TCPDF;
use TCPDF_FONTS;

require_once TCPDF_INC;

/**
 * PDF export base class providing basic configuration.
 *
 * @package PhpMyAdmin
 */
class PDF extends TCPDF
{
    var $footerset;
    var $Alias = array();

    /**
     * PDF font to use.
     */
    const PMA_PDF_FONT = 'DejaVuSans';

    /**
     * Constructs PDF and configures standard parameters.
     *
     * @param string  $orientation page orientation
     * @param string  $unit        unit
     * @param string  $format      the format used for pages
     * @param boolean $unicode     true means that the input text is unicode
     * @param string  $encoding    charset encoding; default is UTF-8.
     * @param boolean $diskcache   if true reduce the RAM memory usage by caching
     *                             temporary data on filesystem (slower).
     * @param boolean $pdfa        If TRUE set the document to PDF/A mode.
     *
     * @access public
     */
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4',
        $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa=false
    ) {
        parent::__construct(
            $orientation, $unit, $format, $unicode,
            $encoding, $diskcache, $pdfa
        );
        $this->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $this->AddFont('DejaVuSans', '', 'dejavusans.php');
        $this->AddFont('DejaVuSans', 'B', 'dejavusansb.php');
        $this->SetFont(PDF::PMA_PDF_FONT, '', 14);
        $this->setFooterFont(array(PDF::PMA_PDF_FONT, '', 14));
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     *
     * @return void
     */
    public function Footer()
    {
        // Check if footer for this page already exists
        if (!isset($this->footerset[$this->page])) {
            $this->SetY(-15);
            $this->SetFont(PDF::PMA_PDF_FONT, '', 14);
            $this->Cell(
                0, 6,
                __('Page number:') . ' '
                . $this->getAliasNumPage() . '/' .  $this->getAliasNbPages(),
                'T', 0, 'C'
            );
            $this->Cell(0, 6, Util::localisedDate(), 0, 1, 'R');
            $this->SetY(20);

            // set footerset
            $this->footerset[$this->page] = 1;
        }
    }

    /**
     * Function to set alias which will be expanded on page rendering.
     *
     * @param string $name  name of the alias
     * @param string $value value of the alias
     *
     * @return void
     */
    public function SetAlias($name, $value)
    {
        $name = TCPDF_FONTS::UTF8ToUTF16BE(
            $name, false, true, $this->CurrentFont
        );
        $this->Alias[$name] = TCPDF_FONTS::UTF8ToUTF16BE(
            $value, false, true, $this->CurrentFont
        );
    }

    /**
     * Improved with alias expanding.
     *
     * @return void
     */
    public function _putpages()
    {
        if (count($this->Alias) > 0) {
            $nbPages = count($this->pages);
            for ($n = 1; $n <= $nbPages; $n++) {
                $this->pages[$n] = strtr($this->pages[$n], $this->Alias);
            }
        }
        parent::_putpages();
    }

    /**
     * Displays an error message
     *
     * @param string $error_message the error message
     *
     * @return void
     */
    public function Error($error_message = '')
    {
        Message::error(
            __('Error while creating PDF:') . ' ' . $error_message
        )->display();
        exit;
    }

    /**
     * Sends file as a download to user.
     *
     * @param string $filename file name
     *
     * @return void
     */
    public function Download($filename)
    {
        $pdfData = $this->getPDFData();
        Response::getInstance()->disable();
        PMA_downloadHeader(
            $filename,
            'application/pdf',
            mb_strlen($pdfData)
        );
        echo $pdfData;
    }
}
