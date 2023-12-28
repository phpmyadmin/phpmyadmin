<?php
/**
 * Central Columns view/edit
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use Webmozart\Assert\Assert;

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
        $db = DatabaseName::from($request->getParam('db'));

        if ($request->hasBodyParam('edit_save')) {
            $this->response->addHTML((string) $this->editSave([
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
            ], $db));

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
            ], $db);
        }

        if ($request->hasBodyParam('getColumnList')) {
            $this->response->addJSON('message', $this->centralColumns->getListRaw(
                $db->getName(),
                $request->getParsedBodyParam('cur_table', ''),
            ));

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

        $this->main(
            $request->getParsedBodyParam('pos', ''),
            $request->getParsedBodyParam('total_rows', ''),
            $db,
        );

        $pos = 0;
        if (is_numeric($request->getParsedBodyParam('pos'))) {
            $pos = (int) $request->getParsedBodyParam('pos');
        }

        $numberOfColumns = $this->centralColumns->getColumnsCount(
            $db->getName(),
            $pos,
            Config::getInstance()->settings['MaxRows'],
        );
        $GLOBALS['message'] = Message::success(
            sprintf(__('Showing rows %1$s - %2$s.'), $pos + 1, $pos + $numberOfColumns),
        );
        if (! isset($tmpMsg) || $tmpMsg === true) {
            return;
        }

        $GLOBALS['message'] = $tmpMsg;
    }

    public function main(string $totalRows, string $position, DatabaseName $db): void
    {
        if ($totalRows !== '' && $totalRows !== '0' && is_numeric($totalRows)) {
            $totalRows = (int) $totalRows;
        } else {
            $totalRows = $this->centralColumns->getCount($db->getName());
        }

        $pos = 0;
        if (is_numeric($position)) {
            $pos = (int) $position;
        }

        $variables = $this->centralColumns->getTemplateVariablesForMain(
            $db->getName(),
            $totalRows,
            $pos,
            LanguageManager::$textDir,
        );

        $this->render('database/central_columns/main', $variables);
    }

    /**
     * @param mixed[] $params Request parameters
     *
     * @return true|Message
     */
    public function editSave(array $params, DatabaseName $db): bool|Message
    {
        $columnDefault = $params['col_default'];
        if ($columnDefault === 'NONE' && $params['col_default_sel'] !== 'USER_DEFINED') {
            $columnDefault = '';
        }

        return $this->centralColumns->updateOneColumn(
            $db->getName(),
            $params['orig_col_name'],
            $params['col_name'],
            $params['col_type'],
            $params['col_attribute'],
            $params['col_length'],
            isset($params['col_isNull']),
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
    public function addNewColumn(array $params, DatabaseName $db): bool|Message
    {
        $columnDefault = $params['col_default'];
        if ($columnDefault === 'NONE' && $params['col_default_sel'] !== 'USER_DEFINED') {
            $columnDefault = '';
        }

        return $this->centralColumns->updateOneColumn(
            $db->getName(),
            '',
            $params['col_name'],
            $params['col_type'],
            $params['col_attribute'],
            $params['col_length'],
            isset($params['col_isNull']),
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
        Assert::isArray($params['selected_fld']);
        Assert::allString($params['selected_fld']);
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

        Assert::isArray($name['selected_fld']);
        Assert::allString($name['selected_fld']);

        return $this->centralColumns->deleteColumnsFromList($params['db'], $name['selected_fld'], false);
    }
}
