<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * TCPDF wrapper class.
 */

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
	public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false)
    {
        parent::__construct();
        $this->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $this->AliasNbPages();
        $this->AddFont('DejaVuSans', '', 'dejavusans.php');
        $this->AddFont('DejaVuSans', 'B', 'dejavusansb.php');
        $this->AddFont('DejaVuSerif', '', 'dejavuserif.php');
        $this->AddFont('DejaVuSerif', 'B', 'dejavuserifb.php');
        $this->SetFont(PMA_PDF_FONT, '', 14);
        $this->setFooterFont(array(PMA_PDF_FONT, '', 14));
    }
}
