<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Central Columns view/edit
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CentralColumns;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Class CentralColumnsController
 * @package PhpMyAdmin\Controllers\Database
 */
class CentralColumnsController extends AbstractController
{
    /**
     * @var CentralColumns
     */
    private $centralColumns;

    /**
     * CentralColumnsController constructor.
     *
     * @param Response          $response       Response instance
     * @param DatabaseInterface $dbi            DatabaseInterface instance
     * @param Template          $template       Template object
     * @param string            $db             Database name
     * @param CentralColumns    $centralColumns CentralColumns instance
     */
    public function __construct($response, $dbi, Template $template, $db, $centralColumns)
    {
        parent::__construct($response, $dbi, $template, $db);
        $this->centralColumns = $centralColumns;
    }

    /**
     * @param array $params Request parameters
     * @return string HTML
     */
    public function index(array $params): string
    {
        global $pmaThemeImage, $text_dir;

        if (! empty($params['total_rows'])
            && Core::isValid($params['total_rows'], 'integer')
        ) {
            $totalRows = (int) $params['total_rows'];
        } else {
            $totalRows = $this->centralColumns->getCount($this->db);
        }

        $pos = 0;
        if (Core::isValid($params['pos'], 'integer')) {
            $pos = (int) $params['pos'];
        }

        return $this->centralColumns->getHtmlForMain(
            $this->db,
            $totalRows,
            $pos,
            $pmaThemeImage,
            $text_dir
        );
    }

    /**
     * @param array $params Request parameters
     * @return array JSON
     */
    public function getColumnList(array $params): array
    {
        return $this->centralColumns->getListRaw(
            $this->db,
            $params['cur_table'] ?? ''
        );
    }

    /**
     * @param array $params Request parameters
     * @return string HTML
     */
    public function populateColumns(array $params): string
    {
        return $this->centralColumns->getHtmlForColumnDropdown(
            $this->db,
            $params['selectedTable']
        );
    }

    /**
     * @param array $params Request parameters
     * @return true|Message
     */
    public function editSave(array $params)
    {
        $columnDefault = $params['col_default'];
        if ($columnDefault === 'NONE' && $params['col_default_sel'] !== 'USER_DEFINED') {
            $columnDefault = '';
        }
        return $this->centralColumns->updateOneColumn(
            $this->db,
            $params['orig_col_name'],
            $params['col_name'],
            $params['col_type'],
            $params['col_attribute'],
            $params['col_length'],
            isset($params['col_isNull']) ? 1 : 0,
            $params['collation'],
            $params['col_extra'] ?? '',
            $columnDefault
        );
    }

    /**
     * @param array $params Request parameters
     * @return true|Message
     */
    public function addNewColumn(array $params)
    {
        $columnDefault = $params['col_default'];
        if ($columnDefault === 'NONE' && $params['col_default_sel'] !== 'USER_DEFINED') {
            $columnDefault = '';
        }
        return $this->centralColumns->updateOneColumn(
            $this->db,
            '',
            $params['col_name'],
            $params['col_type'],
            $params['col_attribute'],
            $params['col_length'],
            isset($params['col_isNull']) ? 1 : 0,
            $params['collation'],
            $params['col_extra'] ?? '',
            $columnDefault
        );
    }

    /**
     * @param array $params Request parameters
     * @return true|Message
     */
    public function addColumn(array $params)
    {
        return $this->centralColumns->syncUniqueColumns(
            [$params['column-select']],
            false,
            $params['table-select']
        );
    }

    /**
     * @param array $params Request parameters
     * @return string HTML
     */
    public function editPage(array $params): string
    {
        return $this->centralColumns->getHtmlForEditingPage(
            $params['selected_fld'],
            $params['db']
        );
    }

    /**
     * @param array $params Request parameters
     * @return true|Message
     */
    public function updateMultipleColumn(array $params)
    {
        return $this->centralColumns->updateMultipleColumn($params);
    }

    /**
     * @param array $params Request parameters
     * @return true|Message
     */
    public function deleteSave(array $params)
    {
        $name = [];
        parse_str($params['col_name'], $name);
        return $this->centralColumns->deleteColumnsFromList(
            $params['db'],
            $name['selected_fld'],
            false
        );
    }
}
