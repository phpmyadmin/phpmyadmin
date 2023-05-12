<?php
/**
 * TCPDF wrapper class.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use Exception;
use TCPDF;
use TCPDF_FONTS;

use function __;
use function count;
use function strtr;

/**
 * PDF export base class providing basic configuration.
 */
class Pdf extends TCPDF
{
    /** @var mixed[] */
    public array $footerset = [];

    /** @var mixed[] */
    public array $alias = [];

    /**
     * PDF font to use.
     */
    public const PMA_PDF_FONT = 'DejaVuSans';

    /**
     * Constructs PDF and configures standard parameters.
     *
     * @param string    $orientation page orientation
     * @param string    $unit        unit
     * @param string    $format      the format used for pages
     * @param bool      $unicode     true means that the input text is unicode
     * @param string    $encoding    charset encoding; default is UTF-8.
     * @param bool      $diskcache   DEPRECATED TCPDF FEATURE
     * @param false|int $pdfa        If not false, set the document to PDF/A mode and the good version (1 or 3)
     *
     * @throws Exception
     */
    public function __construct(
        string $orientation = 'P',
        string $unit = 'mm',
        string $format = 'A4',
        bool $unicode = true,
        string $encoding = 'UTF-8',
        bool $diskcache = false,
        false|int $pdfa = false,
    ) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);

        $this->setAuthor('phpMyAdmin ' . Version::VERSION);
        $this->AddFont('DejaVuSans', '', 'dejavusans.php');
        $this->AddFont('DejaVuSans', 'B', 'dejavusansb.php');
        $this->setFont(self::PMA_PDF_FONT, '', 14);
        $this->setFooterFont([self::PMA_PDF_FONT, '', 14]);
    }

    /**
     * This function must be named "Footer" to work with the TCPDF library
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function Footer(): void
    {
        // Check if footer for this page already exists
        if (isset($this->footerset[$this->page])) {
            return;
        }

        $this->setY(-15);
        $this->setFont(self::PMA_PDF_FONT, '', 14);
        $this->Cell(
            0,
            6,
            __('Page number:') . ' '
            . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(),
            'T',
            0,
            'C',
        );
        $this->Cell(0, 6, Util::localisedDate(), 0, 1, 'R');
        $this->setY(20);

        // set footerset
        $this->footerset[$this->page] = 1;
    }

    /**
     * Function to set alias which will be expanded on page rendering.
     *
     * @param string $name  name of the alias
     * @param string $value value of the alias
     */
    public function setAlias(string $name, string $value): void
    {
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $name = TCPDF_FONTS::UTF8ToUTF16BE($name, false, true, $this->CurrentFont);
        $this->alias[$name] = TCPDF_FONTS::UTF8ToUTF16BE($value, false, true, $this->CurrentFont);
        // phpcs:enable
    }

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

    /**
     * Improved with alias expanding.
     */
    public function _putpages(): void
    {
        if ($this->alias !== []) {
            $nbPages = count($this->pages);
            for ($n = 1; $n <= $nbPages; $n++) {
                $this->pages[$n] = strtr($this->pages[$n], $this->alias);
            }
        }

        parent::_putpages();
    }

    // phpcs:enable

    /**
     * Displays an error message
     *
     * @param string $errorMessage the error message
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function Error(mixed $errorMessage = ''): never
    {
        echo Message::error(
            __('Error while creating PDF:') . ' ' . $errorMessage,
        )->getDisplay();
        exit;
    }
}
