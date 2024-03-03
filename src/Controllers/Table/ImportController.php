<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function is_numeric;

final class ImportController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['SESSION_KEY'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->pageSettings->init('Import');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->addScriptFiles(['import.js']);

        if (! $this->checkParameters(['db', 'table'])) {
            return;
        }

        $GLOBALS['urlParams'] = ['db' => Current::$database, 'table' => Current::$table];
        $config = Config::getInstance();
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return;
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/table/import');
        $GLOBALS['urlParams']['back'] = Url::getFromRoute('/table/import');

        [$GLOBALS['SESSION_KEY'], $uploadId] = Ajax::uploadProgressSetup();

        $importList = Plugins::getImport('table');

        if ($importList === []) {
            $this->response->addHTML(Message::error(__(
                'Could not load import plugins, please check your installation!',
            ))->getDisplay());

            return;
        }

        $offset = null;
        if (isset($_REQUEST['offset']) && is_numeric($_REQUEST['offset'])) {
            $offset = (int) $_REQUEST['offset'];
        }

        $timeoutPassed = $_REQUEST['timeout_passed'] ?? null;
        $localImportFile = $_REQUEST['local_import_file'] ?? null;
        $compressions = Import::getCompressions();

        $charsets = Charsets::getCharsets($this->dbi, $config->selectedServer['DisableIS']);

        $idKey = $_SESSION[$GLOBALS['SESSION_KEY']]['handler']::getIdKey();
        $hiddenInputs = [
            $idKey => $uploadId,
            'import_type' => 'table',
            'db' => Current::$database,
            'table' => Current::$table,
        ];

        $default = $request->hasQueryParam('format')
            ? (string) $request->getQueryParam('format')
            : Plugins::getDefault('Import', 'format');
        $choice = Plugins::getChoice($importList, $default);
        $options = Plugins::getOptions('Import', $importList);
        $skipQueriesDefault = Plugins::getDefault('Import', 'skip_queries');
        $isAllowInterruptChecked = Plugins::checkboxCheck('Import', 'allow_interrupt');
        $maxUploadSize = (int) $config->get('max_upload_size');

        $this->render('table/import/index', [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'upload_id' => $uploadId,
            'handler' => $_SESSION[$GLOBALS['SESSION_KEY']]['handler'],
            'hidden_inputs' => $hiddenInputs,
            'db' => Current::$database,
            'table' => Current::$table,
            'max_upload_size' => $maxUploadSize,
            'formatted_maximum_upload_size' => Util::getFormattedMaximumUploadSize($maxUploadSize),
            'plugins_choice' => $choice,
            'options' => $options,
            'skip_queries_default' => $skipQueriesDefault,
            'is_allow_interrupt_checked' => $isAllowInterruptChecked,
            'local_import_file' => $localImportFile,
            'is_upload' => $config->get('enable_upload'),
            'upload_dir' => $config->settings['UploadDir'] ?? null,
            'timeout_passed_global' => ImportSettings::$timeoutPassed,
            'compressions' => $compressions,
            'is_encoding_supported' => Encoding::isSupported(),
            'encodings' => Encoding::listEncodings(),
            'import_charset' => $config->settings['Import']['charset'] ?? null,
            'timeout_passed' => $timeoutPassed,
            'offset' => $offset,
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'charsets' => $charsets,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
            'user_upload_dir' => Util::userDir($config->settings['UploadDir'] ?? ''),
            'local_files' => Import::getLocalFiles($importList),
        ]);
    }
}
