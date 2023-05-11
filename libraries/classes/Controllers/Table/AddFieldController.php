<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function intval;
use function is_array;
use function is_numeric;
use function min;
use function strlen;

/**
 * Displays add field form and handles it.
 */
class AddFieldController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Transformations $transformations,
        private Config $config,
        private DatabaseInterface $dbi,
        private ColumnsDefinition $columnsDefinition,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['message'] ??= null;
        $GLOBALS['active_page'] ??= null;
        $GLOBALS['num_fields'] ??= null;
        $GLOBALS['regenerate'] ??= null;
        $GLOBALS['result'] ??= null;

        /** @var string|null $numberOfFields */
        $numberOfFields = $request->getParsedBodyParam('num_fields');

        $this->addScriptFiles(['table/structure.js']);

        $this->checkParameters(['db', 'table']);

        $cfg = $this->config->settings;

        /**
         * Defines the url to return to in case of error in a sql statement
         */
        $GLOBALS['errorUrl'] = Url::getFromRoute('/table/sql', ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']]);

        // check number of fields to be created
        if (isset($_POST['submit_num_fields'])) {
            if (isset($_POST['orig_after_field'])) {
                $_POST['after_field'] = $_POST['orig_after_field'];
            }

            if (isset($_POST['orig_field_where'])) {
                $_POST['field_where'] = $_POST['orig_field_where'];
            }

            $GLOBALS['num_fields'] = min(
                intval($_POST['orig_num_fields']) + intval($_POST['added_fields']),
                4096,
            );
            $GLOBALS['regenerate'] = true;
        } elseif (is_numeric($numberOfFields) && $numberOfFields > 0) {
            $GLOBALS['num_fields'] = min(4096, (int) $numberOfFields);
        } else {
            $GLOBALS['num_fields'] = 1;
        }

        if (isset($_POST['do_save_data'])) {
            // avoid an incorrect calling of PMA_updateColumns() via
            // /table/structure below
            unset($_POST['do_save_data']);

            $createAddField = new CreateAddField($this->dbi);

            $GLOBALS['sql_query'] = $createAddField->getColumnCreationQuery($GLOBALS['table']);

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL($GLOBALS['sql_query']);

                return;
            }

            $GLOBALS['result'] = $createAddField->tryColumnCreationQuery(
                DatabaseName::fromValue($GLOBALS['db']),
                $GLOBALS['sql_query'],
                $GLOBALS['errorUrl'],
            );

            if (! $GLOBALS['result']) {
                $errorMessageHtml = Generator::mysqlDie('', '', false, $GLOBALS['errorUrl'], false);
                $this->response->addHTML($errorMessageHtml ?? '');
                $this->response->setRequestStatus(false);

                return;
            }

            // Update comment table for mime types [MIME]
            if (isset($_POST['field_mimetype']) && is_array($_POST['field_mimetype']) && $cfg['BrowseMIME']) {
                foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                    if (! isset($_POST['field_name'][$fieldindex]) || strlen($_POST['field_name'][$fieldindex]) <= 0) {
                        continue;
                    }

                    $this->transformations->setMime(
                        $GLOBALS['db'],
                        $GLOBALS['table'],
                        $_POST['field_name'][$fieldindex],
                        $mimetype,
                        $_POST['field_transformation'][$fieldindex],
                        $_POST['field_transformation_options'][$fieldindex],
                        $_POST['field_input_transformation'][$fieldindex],
                        $_POST['field_input_transformation_options'][$fieldindex],
                    );
                }
            }

            // Go back to the structure sub-page
            $GLOBALS['message'] = Message::success(
                __('Table %1$s has been altered successfully.'),
            );
            $GLOBALS['message']->addParam($GLOBALS['table']);
            $this->response->addJSON(
                'message',
                Generator::getMessage($GLOBALS['message'], $GLOBALS['sql_query'], 'success'),
            );

            // Give an URL to call and use to appends the structure after the success message
            $this->response->addJSON(
                'structure_refresh_route',
                Url::getFromRoute('/table/structure', [
                    'db' => $GLOBALS['db'],
                    'table' => $GLOBALS['table'],
                    'ajax_request' => '1',
                ]),
            );

            return;
        }

        $urlParams = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($urlParams, '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        $GLOBALS['active_page'] = Url::getFromRoute('/table/structure');

        $this->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js']);

        $this->checkParameters(['server', 'db', 'table', 'num_fields']);

        $templateData = $this->columnsDefinition->displayForm(
            '/table/add-field',
            $GLOBALS['num_fields'],
            $GLOBALS['regenerate'],
        );

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }
}
