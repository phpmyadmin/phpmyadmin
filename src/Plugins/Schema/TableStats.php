<?php
/**
 * Contains abstract class to hold table preferences/statistics
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Font;
use PhpMyAdmin\Index;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Util;

use function array_keys;
use function array_unique;
use function array_values;
use function is_array;
use function rawurldecode;
use function sprintf;

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the tables.
 *
 * @abstract
 */
abstract class TableStats
{
    public string $displayfield;

    /** @var list<string> */
    public array $fields = [];

    /** @var list<string|null> */
    public array $primary = [];

    public int|float $x = 0;

    public int|float $y = 0;

    public int|float $width = 0;

    public int $heightCell = 0;

    protected Relation $relation;

    protected Font $font;

    /**
     * @param Pdf\Pdf|Svg\Svg|Eps\Eps|Dia\Dia $diagram        schema diagram
     * @param string                          $db             current db name
     * @param int                             $pageNumber     current page number (from the
     *                                                        $cfg['Servers'][$i]['table_coords'] table)
     * @param string                          $tableName      table name
     * @param bool                            $showKeys       whether to display keys or not
     * @param bool                            $tableDimension whether to display table position or not
     * @param bool                            $offline        whether the coordinates are sent from the browser
     */
    public function __construct(
        protected Pdf\Pdf|Svg\Svg|Eps\Eps|Dia\Dia $diagram,
        protected string $db,
        protected int $pageNumber,
        protected string $tableName,
        protected bool $showKeys,
        protected bool $tableDimension,
        protected bool $offline,
    ) {
        $this->relation = new Relation(DatabaseInterface::getInstance());
        $this->font = new Font();

        // checks whether the table exists
        // and loads fields
        $this->validateTableAndLoadFields();
        // load table coordinates
        $this->loadCoordinates();
        // loads display field
        $this->loadDisplayField();
        // loads primary keys
        $this->loadPrimaryKey();
    }

    /**
     * Validate whether the table exists.
     */
    protected function validateTableAndLoadFields(): void
    {
        $dbi = DatabaseInterface::getInstance();
        $columnNames = $dbi->getColumnNames($this->db, $this->tableName);
        if ($columnNames === []) {
            $this->showMissingTableError();
            ResponseRenderer::getInstance()->callExit();
        }

        if ($this->showKeys) {
            $indexes = Index::getFromTable($dbi, $this->tableName, $this->db);
            $allColumns = [];
            foreach ($indexes as $index) {
                foreach ($index->getColumns() as $indexColumn) {
                    $allColumns[] = $indexColumn->getName();
                }
            }

            $this->fields = array_values(array_unique($allColumns));
        } else {
            $this->fields = $columnNames;
        }
    }

    /**
     * Displays an error when the table cannot be found.
     *
     * @abstract
     */
    abstract protected function showMissingTableError(): void;

    /**
     * Loads coordinates of a table
     */
    protected function loadCoordinates(): void
    {
        if (! isset($_POST['t_h']) || ! is_array($_POST['t_h'])) {
            return;
        }

        foreach (array_keys($_POST['t_h']) as $key) {
            $db = rawurldecode($_POST['t_db'][$key]);
            $tbl = rawurldecode($_POST['t_tbl'][$key]);
            if ($this->db . '.' . $this->tableName === $db . '.' . $tbl) {
                $this->x = (float) $_POST['t_x'][$key];
                $this->y = (float) $_POST['t_y'][$key];
                break;
            }
        }
    }

    /**
     * Loads the table's display field
     */
    protected function loadDisplayField(): void
    {
        $this->displayfield = $this->relation->getDisplayField($this->db, $this->tableName);
    }

    /**
     * Loads the PRIMARY key.
     */
    protected function loadPrimaryKey(): void
    {
        $result = DatabaseInterface::getInstance()->query('SHOW INDEX FROM ' . Util::backquote($this->tableName) . ';');
        if ($result->numRows() <= 0) {
            return;
        }

        while ($row = $result->fetchAssoc()) {
            if ($row['Key_name'] !== 'PRIMARY') {
                continue;
            }

            $this->primary[] = $row['Column_name'];
        }
    }

    /**
     * Returns title of the current table,
     * title can have the dimensions/co-ordinates of the table
     *
     * @return string title of the current table
     */
    protected function getTitle(): string
    {
        return ($this->tableDimension
            ? sprintf('%.0fx%0.f', $this->width, $this->heightCell)
            : ''
        )
        . ' ' . $this->tableName;
    }
}
