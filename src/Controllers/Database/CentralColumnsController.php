<?php
/**
 * Central Columns view/edit
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use Webmozart\Assert\Assert;

use function __;
use function is_bool;
use function is_numeric;
use function parse_str;
use function sprintf;

#[Route('/database/central-columns', ['GET', 'POST'])]
final readonly class CentralColumnsController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private CentralColumns $centralColumns,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $db = DatabaseName::from($request->getParam('db'));

        if ($request->hasBodyParam('edit_save')) {
            $this->response->addHTML((string) $this->editSave(
                $request->getParsedBodyParamAsString('col_name'),
                $request->getParsedBodyParamAsString('orig_col_name'),
                $request->getParsedBodyParamAsString('col_default'),
                $request->getParsedBodyParamAsString('col_default_sel'),
                $request->getParsedBodyParamAsString('col_extra', ''),
                $request->getParsedBodyParamAsStringOrNull('col_isNull'),
                $request->getParsedBodyParamAsString('col_length'),
                $request->getParsedBodyParamAsString('col_attribute'),
                $request->getParsedBodyParamAsString('col_type'),
                $request->getParsedBodyParamAsString('collation'),
                $db,
            ));

            return $this->response->response();
        }

        $tmpMsg = null;
        if ($request->hasBodyParam('add_new_column')) {
            $tmpMsg = $this->addNewColumn(
                $request->getParsedBodyParamAsString('col_name'),
                $request->getParsedBodyParamAsString('col_default'),
                $request->getParsedBodyParamAsString('col_default_sel'),
                $request->getParsedBodyParamAsString('col_extra', ''),
                $request->getParsedBodyParamAsStringOrNull('col_isNull'),
                $request->getParsedBodyParamAsString('col_length'),
                $request->getParsedBodyParamAsString('col_attribute'),
                $request->getParsedBodyParamAsString('col_type'),
                $request->getParsedBodyParamAsString('collation'),
                $db,
            );
        }

        if ($request->hasBodyParam('getColumnList')) {
            $this->response->addJSON('message', $this->centralColumns->getListRaw(
                $db->getName(),
                $request->getParsedBodyParamAsString('cur_table', ''),
            ));

            return $this->response->response();
        }

        if ($request->hasBodyParam('add_column')) {
            $tmpMsg = $this->centralColumns->syncUniqueColumns(
                $db,
                [$request->getParsedBodyParamAsString('column-select')],
                false,
                $request->getParsedBodyParamAsString('table-select'),
            );
        }

        $this->response->addScriptFiles([
            'vendor/jquery/jquery.uitablefilter.js',
            'vendor/jquery/jquery.tablesorter.js',
            'database/central_columns.js',
        ]);

        if ($request->hasBodyParam('edit_central_columns_page')) {
            $this->editPage($request, $db);

            return $this->response->response();
        }

        if ($request->hasBodyParam('multi_edit_central_column_save')) {
            $message = $this->centralColumns->updateMultipleColumn([
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
            if (! is_bool($message)) {
                Current::$message = $message;
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Current::$message);
            }
        }

        if ($request->hasBodyParam('delete_save')) {
            $tmpMsg = $this->deleteSave($request, $db);
        }

        $this->main(
            $request->getParsedBodyParamAsString('pos', ''),
            $request->getParsedBodyParamAsString('total_rows', ''),
            $db,
        );

        $pos = 0;
        if (is_numeric($request->getParsedBodyParam('pos'))) {
            $pos = (int) $request->getParsedBodyParam('pos');
        }

        $numberOfColumns = $this->centralColumns->getColumnsCount(
            $db->getName(),
            $pos,
            $this->config->settings['MaxRows'],
        );
        Current::$message = Message::success(
            sprintf(__('Showing rows %1$s - %2$s.'), $pos + 1, $pos + $numberOfColumns),
        );
        if (! ($tmpMsg instanceof Message)) {
            return $this->response->response();
        }

        Current::$message = $tmpMsg;

        return $this->response->response();
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

        $variables = $this->centralColumns->getTemplateVariablesForMain($db->getName(), $totalRows, $pos);

        $this->response->render('database/central_columns/main', $variables);
    }

    public function editSave(
        string $colName,
        string $origColName,
        string $colDefault,
        string $colDefaultSel,
        string $colExtra,
        string|null $colIsNull,
        string $colLength,
        string $colAttribute,
        string $colType,
        string $collation,
        DatabaseName $db,
    ): true|Message {
        $columnDefault = $colDefault;
        if ($columnDefault === 'NONE' && $colDefaultSel !== 'USER_DEFINED') {
            $columnDefault = '';
        }

        return $this->centralColumns->updateOneColumn(
            $db->getName(),
            $origColName,
            $colName,
            $colType,
            $colAttribute,
            $colLength,
            $colIsNull !== null,
            $collation,
            $colExtra,
            $columnDefault,
        );
    }

    public function addNewColumn(
        string $colName,
        string $colDefault,
        string $colDefaultSel,
        string $colExtra,
        string|null $colIsNull,
        string $colLength,
        string $colAttribute,
        string $colType,
        string $collation,
        DatabaseName $db,
    ): true|Message {
        return $this->centralColumns->updateOneColumn(
            $db->getName(),
            '',
            $colName,
            $colType,
            $colAttribute,
            $colLength,
            $colIsNull !== null,
            $collation,
            $colExtra,
            $colDefault === 'NONE' && $colDefaultSel !== 'USER_DEFINED' ? '' : $colDefault,
        );
    }

    public function editPage(ServerRequest $request, DatabaseName $db): void
    {
        $selectedFields = $request->getParsedBodyParam('selected_fld');
        Assert::isArray($selectedFields);
        Assert::allString($selectedFields);
        $this->response->render('database/central_columns/edit', [
            'rows' => $this->centralColumns->getHtmlForEditingPage($selectedFields, $db->getName()),
        ]);
    }

    public function deleteSave(ServerRequest $request, DatabaseName $db): true|Message
    {
        parse_str($request->getParsedBodyParamAsString('col_name'), $name);

        Assert::isArray($name['selected_fld']);
        Assert::allString($name['selected_fld']);

        return $this->centralColumns->deleteColumnsFromList($db->getName(), $name['selected_fld'], false);
    }
}
