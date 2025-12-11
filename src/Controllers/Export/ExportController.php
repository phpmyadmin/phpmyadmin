<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\Database\ExportController as DatabaseExportController;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Export\ExportXml;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function __;
use function count;
use function function_exists;
use function in_array;
use function ini_set;
use function is_array;
use function register_shutdown_function;

#[Route('/export', ['GET', 'POST'])]
final readonly class ExportController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Export $export,
        private ResponseFactory $responseFactory,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $quickOrCustom = $request->getParsedBodyParamAsStringOrNull('quick_or_custom');
        $outputFormat = $request->getParsedBodyParamAsStringOrNull('output_format');
        $compressionParam = $request->getParsedBodyParamAsString('compression', '');
        $asSeparateFiles = $request->getParsedBodyParamAsStringOrNull('as_separate_files');
        $quickExportOnServer = $request->getParsedBodyParamAsStringOrNull('quick_export_onserver');
        $onServerParam = $request->getParsedBodyParamAsStringOrNull('onserver');
        /** @var array|null $aliasesParam */
        $aliasesParam = $request->getParsedBodyParam('aliases');
        $structureOrDataForced = (bool) $request->getParsedBodyParamAsStringOrNull('structure_or_data_forced');
        $rememberTemplate = $request->getParsedBodyParamAsString('remember_template', '');
        $dbSelect = $request->getParsedBodyParam('db_select');
        $tableStructure = $request->getParsedBodyParam('table_structure');
        $lockTables = $request->hasBodyParam('lock_tables');

        $this->response->addScriptFiles(['export_output.js']);

        if ($request->hasBodyParam('single_table')) {
            Export::$singleTable = (bool) $request->getParsedBodyParam('single_table');
        }

        if ($request->hasBodyParam('charset')) {
            Current::$charset = $request->getParsedBodyParamAsString('charset');
        }

        if ($request->hasBodyParam('knjenc')) {
            $this->export->outputHandler->kanjiEncoding = $request->getParsedBodyParamAsString('knjenc');
        }

        if ($request->hasBodyParam('maxsize')) {
            Export::$maxSize = $request->getParsedBodyParamAsString('maxsize');
        }

        $tableSelectParam = [];
        if ($request->hasBodyParam('table_select')) {
            $tableSelectParam = $request->getParsedBodyParam('table_select');
        }

        $tableData = $request->getParsedBodyParam('table_data');
        if (is_array($tableData)) {
            Export::$tableData = $tableData;
        }

        if ($request->hasBodyParam('xkana')) {
            $this->export->outputHandler->xkana = $request->getParsedBodyParamAsString('xkana');
        }

        // sanitize this parameter which will be used below in a file inclusion
        $what = Core::securePath($request->getParsedBodyParamAsString('what', ''));
        if ($what === '') {
            return $this->response->missingParameterError('what');
        }

        $exportType = ExportType::from($request->getParsedBodyParamAsString('export_type'));

        // export class instance, not array of properties, as before
        $exportPlugin = Plugins::getPlugin('export', $what, $exportType, Export::$singleTable);

        // Check export type
        if (! $exportPlugin instanceof ExportPlugin) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error(__('Bad type!'))->getDisplay());

            return $this->response->response();
        }

        $exportPlugin->setExportOptions($request, $this->config->settings['Export']);

        /**
         * valid compression methods
         */
        $compressionMethods = [];
        if ($this->config->settings['ZipDump'] && function_exists('gzcompress')) {
            $compressionMethods[] = 'zip';
        }

        if ($this->config->settings['GZipDump'] && function_exists('gzencode')) {
            $compressionMethods[] = 'gzip';
        }

        /**
         * init and variable checking
         */
        $filename = '';
        $separateFiles = '';

        // Is it a quick or custom export?
        $isQuickExport = $quickOrCustom === 'quick';

        $saveOnServer = false;

        if ($outputFormat === 'astext') {
            OutputHandler::$asFile = false;
        } else {
            OutputHandler::$asFile = true;
            if ($asSeparateFiles && $compressionParam === 'zip') {
                $separateFiles = $asSeparateFiles;
            }

            if (in_array($compressionParam, $compressionMethods, true)) {
                $this->export->outputHandler->setCompression(
                    $compressionParam,
                    $this->config->settings['CompressOnFly'],
                );
            }

            if (($isQuickExport && $quickExportOnServer) || (! $isQuickExport && $onServerParam)) {
                // Will we save dump on server?
                $saveOnServer = $this->config->settings['SaveDir'] !== '';
            }
        }

        $tableNames = [];
        // Generate error url and check for needed variables
        if ($exportType === ExportType::Database) {
            if (Current::$database === '') {
                return $this->response->missingParameterError('db');
            }

            // Check if we have something to export
            $tableNames = $tableSelectParam;
            Assert::isArray($tableNames);
            Assert::allString($tableNames);
        } elseif ($exportType === ExportType::Table) {
            if (Current::$database === '') {
                return $this->response->missingParameterError('db');
            }

            if (Current::$table === '') {
                return $this->response->missingParameterError('table');
            }
        }

        // Merge SQL Query aliases with Export aliases from
        // export page, Export page aliases are given more
        // preference over SQL Query aliases.
        $parser = new Parser(Current::$sqlQuery);
        $aliases = [];
        if (! empty($parser->statements[0]) && $parser->statements[0] instanceof SelectStatement) {
            $aliases = $parser->statements[0]->getAliases(Current::$database);
        }

        if ($aliasesParam !== null && $aliasesParam !== []) {
            $aliases = $this->export->mergeAliases($aliases, $aliasesParam);
            $_SESSION['tmpval']['aliases'] = $aliasesParam;
        }

        /**
         * Increase time limit for script execution and initializes some variables
         */
        Util::setTimeLimit();
        if (! empty($this->config->settings['MemoryLimit'])) {
            ini_set('memory_limit', $this->config->settings['MemoryLimit']);
        }

        register_shutdown_function([$this->export, 'shutdown']);

        $this->export->outputHandler->outputKanjiConversion = Encoding::canConvertKanji();

        // Do we need to convert charset?
        $this->export->outputHandler->outputCharsetConversion = OutputHandler::$asFile
            && Encoding::isSupported()
            && Current::$charset !== null && Current::$charset !== 'utf-8'
            && in_array(Current::$charset, Encoding::listEncodings(), true);

        // Use on the fly compression?
        if ($this->export->outputHandler->onFlyCompression) {
            $this->export->outputHandler->memoryLimit = $this->export->getMemoryLimit();
        }

        // Generate filename and mime type if needed
        $mimeType = '';
        if (OutputHandler::$asFile) {
            $filenameTemplate = $request->getParsedBodyParamAsString('filename_template', '');

            if ((bool) $rememberTemplate) {
                $this->export->rememberFilename($this->config, $exportType, $filenameTemplate);
            }

            $filename = $this->export->getFinalFilename(
                $exportPlugin,
                Sanitize::sanitizeFilename(Util::expandUserString($filenameTemplate), true),
            );

            $mimeType = $this->export->getMimeType($exportPlugin);
        }

        // For raw query export, filename will be export.extension
        if ($exportType === ExportType::Raw) {
            $filename = $this->export->getFinalFilename($exportPlugin, 'export');
        }

        // Open file on server if needed
        if ($saveOnServer) {
            $message = $this->export->outputHandler->openFile(
                $this->config->settings['SaveDir'] ?? '',
                $filename,
                $isQuickExport,
                $request->getParsedBodyParam('quick_export_onserver_overwrite') === 'saveitover',
                $request->getParsedBodyParam('onserver_overwrite') === 'saveitover',
            );

            // problem opening export file on server?
            if ($message !== null) {
                $location = $this->export->getPageLocationAndSaveMessage($exportType, $message);
                $this->response->redirect($location);

                return $this->response->response();
            }
        } elseif (OutputHandler::$asFile) {
            /**
             * Send headers depending on whether the user chose to download a dump file
             * or not
             */
            // Download
            // (avoid rewriting data containing HTML with anchors and forms;
            // this was reported to happen under Plesk)
            ini_set('url_rewriter.tags', '');
            $filename = Sanitize::sanitizeFilename($filename);
            Core::downloadHeader($filename, $mimeType);
        } else {
            // HTML
            if ($exportType === ExportType::Database) {
                Current::$numTables = count($tableNames);
                if (Current::$numTables === 0) {
                    Current::$message = Message::error(
                        __('No tables found in database.'),
                    );
                    $controller = ContainerBuilder::getContainer()->get(DatabaseExportController::class);

                    return $controller($request);
                }
            }

            echo $this->export->getHtmlForDisplayedExportHeader($exportType, Current::$database, Current::$table);
        }

        try {
            // Re - initialize
            $this->export->outputHandler->clearBuffer();

            // TODO: This is a temporary hack to avoid GLOBALS. Replace this with something better.
            if ($exportPlugin instanceof ExportXml) {
                $exportPlugin->setTables($tableNames);
            }

            // Add possibly some comments to export
            if (! $exportPlugin->exportHeader()) {
                throw new ExportException('Failure during header export.');
            }

            /**
             * Builds the dump
             */
            if ($exportType === ExportType::Server) {
                if ($dbSelect === null) {
                    $dbSelect = '';
                }

                $this->export->exportServer($dbSelect, $exportPlugin, $aliases, $separateFiles);
            } elseif ($exportType === ExportType::Database) {
                if (! is_array($tableStructure)) {
                    $tableStructure = [];
                }

                if ($structureOrDataForced) {
                    $tableStructure = $tableNames;
                    Export::$tableData = $tableNames;
                }

                if ($lockTables) {
                    $this->export->lockTables(DatabaseName::from(Current::$database), $tableNames, 'READ');
                    try {
                        $this->export->exportDatabase(
                            DatabaseName::from(Current::$database),
                            $tableNames,
                            $tableStructure,
                            Export::$tableData,
                            $exportPlugin,
                            $aliases,
                            $separateFiles,
                        );
                    } finally {
                        $this->export->unlockTables();
                    }
                } else {
                    $this->export->exportDatabase(
                        DatabaseName::from(Current::$database),
                        $tableNames,
                        $tableStructure,
                        Export::$tableData,
                        $exportPlugin,
                        $aliases,
                        $separateFiles,
                    );
                }
            } elseif ($exportType === ExportType::Raw) {
                Export::exportRaw($exportPlugin, Current::$database, Current::$sqlQuery);
            } else {
                // We export just one table

                $allrows = $request->getParsedBodyParamAsString('allrows', '');
                $limitTo = $request->getParsedBodyParamAsString('limit_to', '0');
                $limitFrom = $request->getParsedBodyParamAsString('limit_from', '0');

                if ($lockTables) {
                    try {
                        $this->export->lockTables(DatabaseName::from(Current::$database), [Current::$table], 'READ');
                        $this->export->exportTable(
                            Current::$database,
                            Current::$table,
                            $exportPlugin,
                            $allrows,
                            $limitTo,
                            $limitFrom,
                            Current::$sqlQuery,
                            $aliases,
                        );
                    } finally {
                        $this->export->unlockTables();
                    }
                } else {
                    $this->export->exportTable(
                        Current::$database,
                        Current::$table,
                        $exportPlugin,
                        $allrows,
                        $limitTo,
                        $limitFrom,
                        Current::$sqlQuery,
                        $aliases,
                    );
                }
            }

            if (! $exportPlugin->exportFooter()) {
                throw new ExportException('Failure during footer export.');
            }
        } catch (ExportException) {
            // Ignore
        }

        if ($saveOnServer && Current::$message !== null) {
            $location = $this->export->getPageLocationAndSaveMessage($exportType, Current::$message);
            $this->response->redirect($location);

            return $this->response->response();
        }

        /**
         * Send the dump as a file...
         */
        if (! OutputHandler::$asFile) {
            echo $this->export->getHtmlForDisplayedExportFooter($exportType, Current::$database, Current::$table);

            return $this->response->response();
        }

        // Convert the charset if required.
        $this->export->outputHandler->convertBufferCharset();

        // Compression needed?
        $this->export->outputHandler->compress($separateFiles !== '', $filename);

        if ($saveOnServer) {
            $message = $this->export->outputHandler->closeFile();
            $location = $this->export->getPageLocationAndSaveMessage($exportType, $message);
            $this->response->redirect($location);

            return $this->response->response();
        }

        return $this->responseFactory->createResponse()->write($this->export->outputHandler->getBuffer());
    }
}
