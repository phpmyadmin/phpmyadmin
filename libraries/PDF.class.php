<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * TCPDF wrapper class.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once TCPDF_INC;

/**
 * PDF font to use.
 */
define('PMA_PDF_FONT', 'DejaVuSans');

/**
 * PDF export base class providing basic configuration.
 *
 * @package PhpMyAdmin
 */
class PMA_PDF extends TCPDF
{
    var $footerset;
    var $Alias = array();

    /**
     * Constructs PDF and configures standard parameters.
     *
     * @param string  $orientation page orientation
     * @param string  $unit        unit
     * @param mixed   $format      the format used for pages
     * @param boolean $unicode     true means that the input text is unicode
     * @param string  $encoding    charset encoding; default is UTF-8.
     * @param boolean $diskcache   if true reduce the RAM memory usage by caching
     *                             temporary data on filesystem (slower).
     *
     * @return void
     * @access public
     */
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4',
        $unicode = true, $encoding = 'UTF-8', $diskcache = false
    ) {
        parent::__construct();
        $this->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $this->AddFont('DejaVuSans', '', 'dejavusans.php');
        $this->AddFont('DejaVuSans', 'B', 'dejavusansb.php');
        $this->SetFont(PMA_PDF_FONT, '', 14);
        $this->setFooterFont(array(PMA_PDF_FONT, '', 14));
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     *
     * @return void
     */
    function Footer()
    {
        // Check if footer for this page already exists
        if (!isset($this->footerset[$this->page])) {
            $this->SetY(-15);
            $this->SetFont(PMA_PDF_FONT, '', 14);
            $this->Cell(0, 6, __('Page number:') . ' ' . $this->getAliasNumPage() . '/' .  $this->getAliasNbPages(), 'T', 0, 'C');
            $this->Cell(0, 6, PMA_Util::localisedDate(), 0, 1, 'R');
            $this->SetY(20);

            // set footerset
            $this->footerset[$this->page] = 1;
        }
    }

    /**
     * Function to test an empty string (was in tcpdf < 6.0)
     *
     * @param string $str to test
     *
     * @return boolean
     */
    public function empty_string($str) {
            return (is_null($str) OR (is_string($str) AND (strlen($str) == 0)));
    }

    /**
     * Function to set alias which will be expanded on page rendering.
     *
     * @param string $name  name of the alias
     * @param string $value value of the alias
     *
     * @return void
     */
    function SetAlias($name, $value)
    {
        $this->Alias[$this->UTF8ToUTF16BE($name)] = $this->UTF8ToUTF16BE($value);
    }

    /**
     * Improved with alias expading.
     *
     * @return void
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
     *
     * @return void
     */
    function Error($error_message = '')
    {
        PMA_Message::error(
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
    function Download($filename)
    {
        $pdfData = $this->getPDFData();
        PMA_Response::getInstance()->disable();
        PMA_downloadHeader($filename, 'application/pdf', strlen($pdfData));
        echo $pdfData;
    }
}
