<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\PluginFactory;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function is_numeric;
use function is_string;

/**
 * Builds the template parameters of the server, database and table import pages.
 */
final readonly class ImportForm
{
    public function __construct(
        private DatabaseInterface $dbi,
        private Config $config,
        private Ajax $ajax,
        private PluginFactory $pluginFactory,
    ) {
    }

    /**
     * @param 'server'|'database'|'table' $importType
     * @param array<string, string>       $hiddenInputs Hidden inputs added after the upload ID and the import type
     *
     * @return array<string, mixed>|null The template parameters, null when no import plugin can be loaded
     */
    public function getTemplateParams(ServerRequest $request, string $importType, array $hiddenInputs): array|null
    {
        $uploadId = $this->ajax->uploadProgressSetup();

        ImportSettings::$importType = $importType;
        $importList = $this->pluginFactory->getAllImportPlugins();

        if ($importList === []) {
            return null;
        }

        $offset = null;
        if (is_numeric($request->getParam('offset'))) {
            $offset = (int) $request->getParam('offset');
        }

        $handler = Ajax::getHandlerFromSession();

        $pluginName = $this->getFormat($request->getParam('format'));
        $pluginName = Plugins::validatePluginNameOrUseDefault($importList, $pluginName);

        $maxUploadSize = Util::getUploadSizeInBytes();

        return [
            'upload_id' => $uploadId,
            'handler' => $handler,
            'hidden_inputs' => [$handler::getIdKey() => $uploadId, 'import_type' => $importType] + $hiddenInputs,
            'db' => Current::$database,
            'table' => Current::$table,
            'max_upload_size' => $maxUploadSize,
            'formatted_maximum_upload_size' => Util::getFormattedMaximumUploadSize($maxUploadSize),
            'plugins_choice' => Plugins::getChoice($importList, $pluginName),
            'options' => Plugins::getOptions(PluginType::Import, $importList),
            'skip_queries_default' => $this->getSkipQueries($request->getParam('skip_queries')),
            'is_allow_interrupt_checked' => Plugins::checkboxCheck(PluginType::Import, 'allow_interrupt'),
            'local_import_file' => $request->getParam('local_import_file'),
            'is_upload' => $this->config->isUploadEnabled(),
            'upload_dir' => $this->config->config->UploadDir,
            'timeout_passed_global' => ImportSettings::$timeoutPassed,
            'compressions' => Import::getCompressions($this->config),
            'is_encoding_supported' => Encoding::isSupported(),
            'encodings' => Encoding::listEncodings(),
            'import_charset' => $this->config->config->Import->charset,
            'timeout_passed' => $request->getParam('timeout_passed'),
            'offset' => $offset,
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'charsets' => Charsets::getCharsets($this->dbi, $this->config->selectedServer['DisableIS']),
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
            'user_upload_dir' => Util::userDir($this->config->selectedServer['user'], $this->config->config->UploadDir),
            'local_files' => Import::getLocalFiles($this->config, $importList),
        ];
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
