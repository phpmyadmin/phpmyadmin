<?php
/**
 * Central Columns view/edit
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function is_bool;
use function is_numeric;
use function parse_str;
use function sprintf;

class CentralColumnsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private CentralColumns $centralColumns,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['message'] ??= null;
        $GLOBALS['num_cols'] ??= null;

        if ($request->hasBodyParam('edit_save')) {
            echo $this->editSave([
                'col_name' => $request->getParsedBodyParam('col_name'),
                'orig_col_name' => $request->getParsedBodyParam('orig_col_name'),
                'col_default' => $request->getParsedBodyParam('col_default'),
                'col_default_sel' => $request->getParsedBodyParam('col_default_sel'),
                'col_extra' => $request->getParsedBodyParam('col_extra'),
                'col_isNull' => $request->getParsedBodyParam('col_isNull'),
                'col_length' => $request->getParsedBodyParam('col_length'),
                'col_attribute' => $request->getParsedBodyParam('col_attribute'),
                'col_type' => $request->getParsedBodyParam('col_type'),
                'collation' => $request->getParsedBodyParam('collation'),
            ]);

            return;
        }

        if ($request->hasBodyParam('add_new_column')) {
            $tmpMsg = $this->addNewColumn([
                'col_name' => $request->getParsedBodyParam('col_name'),
                'col_default' => $request->getParsedBodyParam('col_default'),
                'col_default_sel' => $request->getParsedBodyParam('col_default_sel'),
                'col_extra' => $request->getParsedBodyParam('col_extra'),
                'col_isNull' => $request->getParsedBodyParam('col_isNull'),
                'col_length' => $request->getParsedBodyParam('col_length'),
                'col_attribute' => $request->getParsedBodyParam('col_attribute'),
                'col_type' => $request->getParsedBodyParam('col_type'),
                'collation' => $request->getParsedBodyParam('collation'),
            ]);
        }

        if ($request->hasBodyParam('getColumnList')) {
            $this->response->addJSON('message', $this->getColumnList([
                'cur_table' => $request->getParsedBodyParam('cur_table'),
            ]));

            return;
        }

        if ($request->hasBodyParam('add_column')) {
            $tmpMsg = $this->addColumn([
                'table-select' => $request->getParsedBodyParam('table-select'),
                'column-select' => $request->getParsedBodyParam('column-select'),
            ]);
        }

        $this->addScriptFiles([
            'vendor/jquery/jquery.uitablefilter.js',
            'vendor/jquery/jquery.tablesorter.js',
            'database/central_columns.js',
        ]);

        if ($request->hasBodyParam('edit_central_columns_page')) {
            $this->editPage([
                'selected_fld' => $request->getParsedBodyParam('selected_fld'),
                'db' => $request->getParsedBodyParam('db'),
            ]);

            return;
        }

        if ($request->hasBodyParam('multi_edit_central_column_save')) {
            $GLOBALS['message'] = $this->updateMultipleColumn([
                'db' => $request->getParsedBodyParam('db'),
                'orig_col_name' => $request->getParsedBodyParam('orig_col_name'),
                'field_name' => $request->getParsedBodyParam('field_name'),
                'field_default_type' => $request->getParsedBodyParam('field_default_type'),
                'field_default_value' => $request->getParsedBodyParam('field_default_value'),
                'field_length' => $request->getParsedBodyParam('field_length'),
                'field_attribute' => $request->getParsedBodyParam('field_attribute'),
                'field_type' => $request->getParsedBodyParam('field_type'),
                'field_collation' => $request->getParsedBodyParam('field_collation'),
                'field_null' => $request->getParsedBodyParam('field_null'),
                'col_extra' => $request->getParsedBodyParam('col_extra'),
            ]);
            if (! is_bool($GLOBALS['message'])) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $GLOBALS['message']);
            }
        }

        if ($request->hasBodyParam('delete_save')) {
            $tmpMsg = $this->deleteSave([
                'db' => $request->getParsedBodyParam('db'),
                'col_name' => $request->getParsedBodyParam('col_name'),
            ]);
        }

        $this->main([
            'pos' => $request->getParsedBodyParam('pos'),
            'total_rows' => $request->getParsedBodyParam('total_rows'),
        ]);

        $pos = 0;
        if (is_numeric($request->getParsedBodyParam('pos'))) {
            $pos = (int) $request->getParsedBodyParam('pos');
        }

        $GLOBALS['num_cols'] = $this->centralColumns->getColumnsCount(
            $GLOBALS['db'],
            $pos,
            (int) $GLOBALS['cfg']['MaxRows'],
        );
        $GLOBALS['message'] = Message::success(
            sprintf(__('Showing rows %1$s - %2$s.'), $pos + 1, $pos + $GLOBALS['num_cols']),
        );
        if (! isset($tmpMsg) || $tmpMsg === true) {
            return;
        }

        $GLOBALS['message'] = $tmpMsg;
    }

    /** @param mixed[] $params Request parameters */
    public function main(array $params): void
    {
        $GLOBALS['text_dir'] ??= null;

        if (! empty($params['total_rows']) && is_numeric($params['total_rows'])) {
            $totalRows = (int) $params['total_rows'];
        } else {
            $totalRows = $this->centralColumns->getCount($GLOBALS['db']);
        }

        $pos = 0;
        if (isset($params['pos']) && is_numeric($params['pos'])) {
            $pos = (int) $params['pos'];
        }

        $variables = $this->centralColumns->getTemplateVariablesForMain(
            $GLOBALS['db'],
            $totalRows,
            $pos,
            $GLOBALS['text_dir'],
        );

        $this->render('database/central_columns/main', $variables);
    }

    /**
     * @param mixed[] $params Request parameters
     *
     * @return mixed[] JSON
     */
    public function getColumnList(array $params): array
    {
        return $this->centralColumns->getListRaw($GLOBALS['db'], $params['cur_table'] ?? '');
    }

    /**
     * @param mixed[] $params Request parameters
     *
     * @return true|Message
     */
    public function editSave(array $params): bool|Message
    {
        $columnDefault = $params['col_default'];
        if ($columnDefault === 'NONE' && $params['col_default_sel'] !== 'USER_DEFINED') {
            $columnDefault = '';
        }

        return $this->centralColumns->updateOneColumn(
            $GLOBALS['db'],
            $params['orig_col_name'],
            $params['col_name'],
            $params['col_type'],
            $params['col_attribute'],
            $params['col_length'],
            isset($params['col_isNull']) ? 1 : 0,
            $params['collation'],
            $params['col_extra'] ?? '',
            $columnDefault,
        );
    }

    /**
     * @param mixed[] $params Request parameters
     *
     * @return true|Message
     */
    public function addNewColumn(array $params): bool|Message
    {
        $columnDefault = $params['col_default'];
        if ($columnDefault === 'NONE' && $params['col_default_sel'] !== 'USER_DEFINED') {
            $columnDefault = '';
        }

        return $this->centralColumns->updateOneColumn(
            $GLOBALS['db'],
            '',
            $params['col_name'],
            $params['col_type'],
            $params['col_attribute'],
            $params['col_length'],
            isset($params['col_isNull']) ? 1 : 0,
            $params['collation'],
            $params['col_extra'] ?? '',
            $columnDefault,
        );
    }

    /**
     * @param mixed[] $params Request parameters
     *
     * @return true|Message
     */
    public function addColumn(array $params): bool|Message
    {
        return $this->centralColumns->syncUniqueColumns(
            [$params['column-select']],
            false,
            $params['table-select'],
        );
    }

    /** @param mixed[] $params Request parameters */
    public function editPage(array $params): void
    {
        $rows = $this->centralColumns->getHtmlForEditingPage($params['selected_fld'], $params['db']);

        $this->render('database/central_columns/edit', ['rows' => $rows]);
    }

    /**
     * @param mixed[] $params Request parameters
     *
     * @return true|Message
     */
    public function updateMultipleColumn(array $params): bool|Message
    {
        return $this->centralColumns->updateMultipleColumn($params);
    }

    /**
     * @param mixed[] $params Request parameters
     *
     * @return true|Message
     */
    public function deleteSave(array $params): bool|Message
    {
        $name = [];
        parse_str($params['col_name'], $name);

        return $this->centralColumns->deleteColumnsFromList($params['db'], $name['selected_fld'], false);
    }
}
