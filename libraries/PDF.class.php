<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * TCPDF wrapper class.
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/tcpdf/tcpdf.php';

/**
 * PDF font to use.
 */
define('PMA_PDF_FONT', 'DejaVuSans');

/**
 * PDF export base class providing basic configuration.
 */
class PMA_PDF extends TCPDF
{
    var $footerset;
    var $Alias = array();

    public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false)
    {
        parent::__construct();
        $this->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $this->AliasNbPages();
        $this->AddFont('DejaVuSans', '', 'dejavusans.php');
        $this->AddFont('DejaVuSans', 'B', 'dejavusansb.php');
        $this->SetFont(PMA_PDF_FONT, '', 14);
        $this->setFooterFont(array(PMA_PDF_FONT, '', 14));
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     */
    function Footer()
    {
        // Check if footer for this page already exists
        if (!isset($this->footerset[$this->page])) {
            $this->SetY(-15);
            $this->SetFont(PMA_PDF_FONT, '', 14);
            $this->Cell(0, 6, __('Page number:') . ' ' . $this->getAliasNumPage() . '/' .  $this->getAliasNbPages(), 'T', 0, 'C');
            $this->Cell(0, 6, PMA_localisedDate(), 0, 1, 'R');
            $this->SetY(20);

            // set footerset
            $this->footerset[$this->page] = 1;
        }
    }

    /**
     * Function to set alias which will be expanded on page rendering.
     */
    function SetAlias($name, $value)
    {
        $this->Alias[$this->UTF8ToUTF16BE($name)] = $this->UTF8ToUTF16BE($value);
    }

    /**
     * Improved with alias expading.
     */
    function _putpages()
    {
        if (count($this->Alias) > 0) {
            $nb = count($this->pages);
            for ($n = 1;$n <= $nb;$n++) {
                $this->pages[$n] = strtr($this->pages[$n], $this->Alias);
            }
        }
        parent::_putpages();
    }

    /**
     * Displays an error message
     *
     * @param string $error_message the error mesage
     */
    function Error($error_message = '')
    {
        include './libraries/header.inc.php';
        PMA_Message::error(__('Error while creating PDF:') . ' ' . $error_message)->display();
        include './libraries/footer.inc.php';
    }

    /**
     * Sends file as a download to user.
     */
    function Download($filename)
    {
        $pdfData = $this->getPDFData();
        PMA_download_header($filename, 'application/pdf', strlen($pdfData));
        echo $pdfData;
    }
}
