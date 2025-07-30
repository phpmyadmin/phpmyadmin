<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function is_numeric;
use function is_string;

#[Route('/server/import', ['GET', 'POST'])]
final readonly class ImportController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private DatabaseInterface $dbi,
        private PageSettings $pageSettings,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->pageSettings->init('Import');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->response->addScriptFiles(['import.js']);

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        [$uploadId] = Ajax::uploadProgressSetup();

        ImportSettings::$importType = 'server';
        $importList = Plugins::getImport();

        if ($importList === []) {
            $this->response->addHTML(Message::error(__(
                'Could not load import plugins, please check your installation!',
            ))->getDisplay());

            return $this->response->response();
        }

        $offset = null;
        if (isset($_REQUEST['offset']) && is_numeric($_REQUEST['offset'])) {
            $offset = (int) $_REQUEST['offset'];
        }

        $timeoutPassed = $_REQUEST['timeout_passed'] ?? null;
        $localImportFile = $_REQUEST['local_import_file'] ?? null;
        $compressions = Import::getCompressions();

        $charsets = Charsets::getCharsets($this->dbi, $this->config->selectedServer['DisableIS']);

        $idKey = $_SESSION[Ajax::SESSION_KEY]['handler']::getIdKey();
        $hiddenInputs = [$idKey => $uploadId, 'import_type' => 'server'];

        $choice = Plugins::getChoice($importList, $this->getFormat($request->getParam('format')));
        $options = Plugins::getOptions('Import', $importList);
        $skipQueriesDefault = $this->getSkipQueries($request->getParam('skip_queries'));
        $isAllowInterruptChecked = Plugins::checkboxCheck('Import', 'allow_interrupt');
        $maxUploadSize = Util::getUploadSizeInBytes();

        $this->response->render('server/import/index', [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'upload_id' => $uploadId,
            'handler' => $_SESSION[Ajax::SESSION_KEY]['handler'],
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
            'is_upload' => $this->config->isUploadEnabled(),
            'upload_dir' => $this->config->settings['UploadDir'] ?? null,
            'timeout_passed_global' => ImportSettings::$timeoutPassed,
            'compressions' => $compressions,
            'is_encoding_supported' => Encoding::isSupported(),
            'encodings' => Encoding::listEncodings(),
            'import_charset' => $this->config->settings['Import']['charset'] ?? null,
            'timeout_passed' => $timeoutPassed,
            'offset' => $offset,
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'charsets' => $charsets,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
            'user_upload_dir' => Util::userDir($this->config->settings['UploadDir'] ?? ''),
            'local_files' => Import::getLocalFiles($importList),
        ]);

        return $this->response->response();
    }

    private function getFormat(mixed $formatParam): string
    {
        if (is_string($formatParam) && $formatParam !== '') {
            return $formatParam;
        }

        return $this->config->settings['Import']['format'];
    }

    private function getSkipQueries(mixed $skipQueriesParam): int
    {
        if (is_numeric($skipQueriesParam) && $skipQueriesParam >= 0) {
            return (int) $skipQueriesParam;
        }

        return $this->config->settings['Import']['skip_queries'];
    }
}
