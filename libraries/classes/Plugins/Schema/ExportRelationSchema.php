<?php
/**
 * Contains PhpMyAdmin\Plugins\Schema\ExportRelationSchema class which is
 * inherited by all schema classes.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function rawurldecode;

/**
 * This class is inherited by all schema classes
 * It contains those methods which are common in them
 * it works like factory pattern
 *
 * @template T
 */
class ExportRelationSchema
{
    protected bool $showColor = false;

    protected bool $tableDimension = false;

    protected bool $sameWide = false;

    protected bool $showKeys = false;

    protected string $orientation = 'L';

    protected string $paper = 'A4';

    protected int $pageNumber = 0;

    protected bool $offline = false;

    protected Relation $relation;

    /** @param T $diagram */
    public function __construct(protected DatabaseName $db, protected $diagram)
    {
        $this->setPageNumber((int) $_REQUEST['page_number']);
        $this->setOffline(isset($_REQUEST['offline_export']));
        $this->relation = new Relation($GLOBALS['dbi']);
    }

    /**
     * Set Page Number
     *
     * @param int $value Page Number of the document to be created
     */
    public function setPageNumber(int $value): void
    {
        $this->pageNumber = $value;
    }

    /**
     * Returns the schema page number
     *
     * @return int schema page number
     */
    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    /**
     * Sets showColor
     *
     * @param bool $value whether to show colors
     */
    public function setShowColor(bool $value): void
    {
        $this->showColor = $value;
    }

    /**
     * Returns whether to show colors
     */
    public function isShowColor(): bool
    {
        return $this->showColor;
    }

    /**
     * Set Table Dimension
     *
     * @param bool $value show table co-ordinates or not
     */
    public function setTableDimension(bool $value): void
    {
        $this->tableDimension = $value;
    }

    /**
     * Returns whether to show table dimensions
     */
    public function isTableDimension(): bool
    {
        return $this->tableDimension;
    }

    /**
     * Set same width of All Tables
     *
     * @param bool $value set same width of all tables or not
     */
    public function setAllTablesSameWidth(bool $value): void
    {
        $this->sameWide = $value;
    }

    /**
     * Returns whether to use same width for all tables or not
     */
    public function isAllTableSameWidth(): bool
    {
        return $this->sameWide;
    }

    /**
     * Set Show only keys
     *
     * @param bool $value show only keys or not
     */
    public function setShowKeys(bool $value): void
    {
        $this->showKeys = $value;
    }

    /**
     * Returns whether to show keys
     */
    public function isShowKeys(): bool
    {
        return $this->showKeys;
    }

    /**
     * Set Orientation
     *
     * @param string $value Orientation will be portrait or landscape
     */
    public function setOrientation(string $value): void
    {
        $this->orientation = $value === 'P' ? 'P' : 'L';
    }

    /**
     * Returns orientation
     *
     * @return string orientation
     */
    public function getOrientation(): string
    {
        return $this->orientation;
    }

    /**
     * Set type of paper
     *
     * @param string $value paper type can be A4 etc
     */
    public function setPaper(string $value): void
    {
        $this->paper = $value;
    }

    /**
     * Returns the paper size
     *
     * @return string paper size
     */
    public function getPaper(): string
    {
        return $this->paper;
    }

    /**
     * Set whether the document is generated from client side DB
     *
     * @param bool $value offline or not
     */
    public function setOffline(bool $value): void
    {
        $this->offline = $value;
    }

    /**
     * Returns whether the client side database is used
     */
    public function isOffline(): bool
    {
        return $this->offline;
    }

    /**
     * Get the table names from the request
     *
     * @return string[] an array of table names
     */
    protected function getTablesFromRequest(): array
    {
        $tables = [];
        if (isset($_POST['t_tbl'])) {
            foreach ($_POST['t_tbl'] as $table) {
                $tables[] = rawurldecode($table);
            }
        }

        return $tables;
    }

    /**
     * @param non-empty-string $extension
     *
     * @return non-empty-string
     */
    protected function getFileName(string $extension): string
    {
        $pdfFeature = $this->relation->getRelationParameters()->pdfFeature;

        $filename = $this->db . $extension;
        // Get the name of this page to use as filename
        if ($this->pageNumber != -1 && ! $this->offline && $pdfFeature !== null) {
            $nameSql = 'SELECT page_descr FROM '
                . Util::backquote($pdfFeature->database) . '.'
                . Util::backquote($pdfFeature->pdfPages)
                . ' WHERE page_nr = ' . $this->pageNumber;
            $nameRs = $GLOBALS['dbi']->queryAsControlUser($nameSql);
            $nameRow = $nameRs->fetchRow();
            $filename = $nameRow[0] . $extension;
        }

        return $filename;
    }

    /**
     * Displays an error message
     *
     * @param int    $pageNumber   ID of the chosen page
     * @param string $type         Schema Type
     * @param string $errorMessage The error message
     */
    public static function dieSchema(int $pageNumber, string $type = '', string $errorMessage = ''): never
    {
        echo '<p><strong>' , __('SCHEMA ERROR: ') , $type , '</strong></p>' , "\n";
        if (! empty($errorMessage)) {
            $errorMessage = htmlspecialchars($errorMessage);
        }

        echo '<p>' , "\n";
        echo '    ' , $errorMessage , "\n";
        echo '</p>' , "\n";
        echo '<a href="';
        echo Url::getFromRoute('/database/designer', [
            'db' => $GLOBALS['db'],
            'server' => $GLOBALS['server'],
            'page' => $pageNumber,
        ]);
        echo '">' . __('Back') . '</a>';
        echo "\n";
        exit;
    }
}
