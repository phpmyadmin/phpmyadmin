<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\Pdf\TableStatsPdf class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Pdf;

use PhpMyAdmin\Pdf as PdfLib;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Plugins\Schema\TableStats;

use function __;
use function count;
use function in_array;
use function max;
use function sprintf;

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the Tables in PDF document.
 *
 * @see Pdf
 *
 * @property Pdf $diagram
 */
class TableStatsPdf extends TableStats
{
    public int $height = 0;

    private string $ff = PdfLib::PMA_PDF_FONT;

    /**
     * @see Pdf
     * @see TableStatsPdf::setWidthTable
     * @see TableStatsPdf::setHeightTable
     *
     * @param Pdf      $diagram        The PDF diagram
     * @param string   $db             The database name
     * @param string   $tableName      The table name
     * @param int|null $fontSize       The font size
     * @param int      $pageNumber     The current page number (from the
     *                                 $cfg['Servers'][$i]['table_coords'] table)
     * @param bool     $showKeys       Whether to display keys or not
     * @param bool     $tableDimension Whether to display table position or not
     * @param bool     $offline        Whether the coordinates are sent
     *                                 from the browser
     */
    public function __construct(
        Pdf $diagram,
        string $db,
        string $tableName,
        int|null $fontSize,
        int $pageNumber,
        bool $showKeys = false,
        bool $tableDimension = false,
        bool $offline = false,
    ) {
        parent::__construct($diagram, $db, $pageNumber, $tableName, $showKeys, $tableDimension, $offline);

        $this->heightCell = 6;
        $this->setHeight();
        // setWidth must be after setHeight, because title can include table height which changes table width
        $this->setWidth($fontSize);
    }

    /**
     * Displays an error when the table cannot be found.
     */
    protected function showMissingTableError(): void
    {
        ExportRelationSchema::dieSchema(
            $this->pageNumber,
            'PDF',
            sprintf(__('The %s table doesn\'t exist!'), $this->tableName),
        );
    }

    /**
     * Returns title of the current table,
     * title can have the dimensions of the table
     */
    protected function getTitle(): string
    {
        $ret = '';
        if ($this->tableDimension) {
            $ret = sprintf('%.0fx%0.f', $this->width, $this->height);
        }

        return $ret . ' ' . $this->tableName;
    }

    /**
     * Sets the width of the table
     *
     * @see Pdf
     *
     * @param int|null $fontSize The font size
     */
    private function setWidth(int|null $fontSize): void
    {
        foreach ($this->fields as $field) {
            $this->width = max($this->width, $this->diagram->GetStringWidth($field));
        }

        $this->width += $this->diagram->GetStringWidth('      ');
        $this->diagram->setFont($this->ff, 'B', $fontSize);
        // it is unknown what value must be added, because table title is affected by the table width value
        while ($this->width < $this->diagram->GetStringWidth($this->getTitle())) {
            $this->width += 5;
        }

        $this->diagram->setFont($this->ff, '', $fontSize);
    }

    /**
     * Sets the height of the table
     */
    private function setHeight(): void
    {
        $this->height = (count($this->fields) + 1) * $this->heightCell;
    }

    /**
     * Do draw the table
     *
     * @see Pdf
     *
     * @param int|null $fontSize The font size or null to use the default value
     * @param bool     $withDoc  Whether to include links to documentation
     * @param bool     $setColor Whether to display color
     */
    public function tableDraw(int|null $fontSize, bool $withDoc, bool $setColor = false): void
    {
        $this->diagram->setXyScale($this->x, $this->y);
        $this->diagram->setFont($this->ff, 'B', $fontSize);
        if ($setColor) {
            $this->diagram->setTextColor(200);
            $this->diagram->setFillColor(0, 0, 128);
        }

        if ($withDoc) {
            $this->diagram->setLink($this->diagram->customLinks['RT'][$this->tableName]['-'], -1);
        } else {
            $this->diagram->customLinks['doc'][$this->tableName]['-'] = '';
        }

        $this->diagram->cellScale(
            $this->width,
            $this->heightCell,
            $this->getTitle(),
            1,
            1,
            'C',
            $setColor,
            (string) $this->diagram->customLinks['doc'][$this->tableName]['-'],
        );
        $this->diagram->setXScale($this->x);
        $this->diagram->setFont($this->ff, '', $fontSize);
        $this->diagram->setTextColor();
        $this->diagram->setFillColor(255);

        foreach ($this->fields as $field) {
            if ($setColor) {
                if (in_array($field, $this->primary, true)) {
                    $this->diagram->setFillColor(215, 121, 123);
                }

                if ($field === $this->displayfield) {
                    $this->diagram->setFillColor(142, 159, 224);
                }
            }

            if ($withDoc) {
                $this->diagram->setLink($this->diagram->customLinks['RT'][$this->tableName][$field], -1);
            } else {
                $this->diagram->customLinks['doc'][$this->tableName][$field] = '';
            }

            $this->diagram->cellScale(
                $this->width,
                $this->heightCell,
                ' ' . $field,
                1,
                1,
                'L',
                $setColor,
                (string) $this->diagram->customLinks['doc'][$this->tableName][$field],
            );
            $this->diagram->setXScale($this->x);
            $this->diagram->setFillColor(255);
        }
    }
}
