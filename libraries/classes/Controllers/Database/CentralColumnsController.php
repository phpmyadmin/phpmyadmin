<?php
/**
 * Central Columns view/edit
 *
 * @package PhpMyAdmin\Controllers\Database
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
 * @package PhpMyAdmin\Controllers\Database
 */
class CentralColumnsController extends AbstractController
{
    /**
     * @var CentralColumns
     */
    private $centralColumns;

    /**
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

    public function index(): void
    {
        global $cfg, $db, $message, $pos, $num_cols;

        if (isset($_POST['edit_save'])) {
            echo $this->editSave([
                'col_name' => $_POST['col_name'] ?? null,
                'orig_col_name' => $_POST['orig_col_name'] ?? null,
                'col_default' => $_POST['col_default'] ?? null,
                'col_default_sel' => $_POST['col_default_sel'] ?? null,
                'col_extra' => $_POST['col_extra'] ?? null,
                'col_isNull' => $_POST['col_isNull'] ?? null,
                'col_length' => $_POST['col_length'] ?? null,
                'col_attribute' => $_POST['col_attribute'] ?? null,
                'col_type' => $_POST['col_type'] ?? null,
                'collation' => $_POST['collation'] ?? null,
            ]);
            return;
        } elseif (isset($_POST['add_new_column'])) {
            $tmp_msg = $this->addNewColumn([
                'col_name' => $_POST['col_name'] ?? null,
                'col_default' => $_POST['col_default'] ?? null,
                'col_default_sel' => $_POST['col_default_sel'] ?? null,
                'col_extra' => $_POST['col_extra'] ?? null,
                'col_isNull' => $_POST['col_isNull'] ?? null,
                'col_length' => $_POST['col_length'] ?? null,
                'col_attribute' => $_POST['col_attribute'] ?? null,
                'col_type' => $_POST['col_type'] ?? null,
                'collation' => $_POST['collation'] ?? null,
            ]);
        }
        if (isset($_POST['populateColumns'])) {
            $this->response->addHTML($this->populateColumns([
                'selectedTable' => $_POST['selectedTable'],
            ]));
            return;
        }
        if (isset($_POST['getColumnList'])) {
            $this->response->addJSON('message', $this->getColumnList([
                'cur_table' => $_POST['cur_table'] ?? null,
            ]));
            return;
        }
        if (isset($_POST['add_column'])) {
            $tmp_msg = $this->addColumn([
                'table-select' => $_POST['table-select'] ?? null,
                'column-select' => $_POST['column-select'] ?? null,
            ]);
        }

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
        $scripts->addFile('vendor/jquery/jquery.tablesorter.js');
        $scripts->addFile('database/central_columns.js');

        if (isset($_POST['edit_central_columns_page'])) {
            $this->response->addHTML($this->editPage([
                'selected_fld' => $_POST['selected_fld'] ?? null,
                'db' => $_POST['db'] ?? null,
            ]));
            return;
        }
        if (isset($_POST['multi_edit_central_column_save'])) {
            $message = $this->updateMultipleColumn([
                'db' => $_POST['db'] ?? null,
                'orig_col_name' => $_POST['orig_col_name'] ?? null,
                'field_name' => $_POST['field_name'] ?? null,
                'field_default_type' => $_POST['field_default_type'] ?? null,
                'field_default_value' => $_POST['field_default_value'] ?? null,
                'field_length' => $_POST['field_length'] ?? null,
                'field_attribute' => $_POST['field_attribute'] ?? null,
                'field_type' => $_POST['field_type'] ?? null,
                'field_collation' => $_POST['field_collation'] ?? null,
                'field_null' => $_POST['field_null'] ?? null,
                'col_extra' => $_POST['col_extra'] ?? null,
            ]);
            if (! is_bool($message)) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $message);
            }
        }
        if (isset($_POST['delete_save'])) {
            $tmp_msg = $this->deleteSave([
                'db' => $_POST['db'] ?? null,
                'col_name' => $_POST['col_name'] ?? null,
            ]);
        }

        $this->response->addHTML($this->main([
            'pos' => $_POST['pos'] ?? null,
            'total_rows' => $_POST['total_rows'] ?? null,
        ]));

        $pos = 0;
        if (Core::isValid($_POST['pos'], 'integer')) {
            $pos = (int) $_POST['pos'];
        }
        $num_cols = $this->centralColumns->getColumnsCount(
            $db,
            $pos,
            (int) $cfg['MaxRows']
        );
        $message = Message::success(
            sprintf(__('Showing rows %1$s - %2$s.'), $pos + 1, $pos + $num_cols)
        );
        if (isset($tmp_msg) && $tmp_msg !== true) {
            $message = $tmp_msg;
        }
    }

    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function main(array $params): string
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
     * @return true|Message
     */
    public function updateMultipleColumn(array $params)
    {
        return $this->centralColumns->updateMultipleColumn($params);
    }

    /**
     * @param array $params Request parameters
     *
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
