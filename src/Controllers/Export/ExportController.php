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
use function time;

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

        if ($request->hasBodyParam('compression')) {
            Export::$compression = $request->getParsedBodyParamAsString('compression');
        }

        if ($request->hasBodyParam('knjenc')) {
            Export::$kanjiEncoding = $request->getParsedBodyParamAsString('knjenc');
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
            Export::$xkana = $request->getParsedBodyParamAsString('xkana');
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
        Export::$compression = '';
        Export::$saveOnServer = false;
        Export::$bufferNeeded = false;
        Export::$saveFilename = '';
        Export::$fileHandle = null;
        $filename = '';
        $separateFiles = '';

        // Is it a quick or custom export?
        $isQuickExport = $quickOrCustom === 'quick';

        if ($outputFormat === 'astext') {
            Export::$asFile = false;
        } else {
            Export::$asFile = true;
            if ($asSeparateFiles && $compressionParam === 'zip') {
                $separateFiles = $asSeparateFiles;
            }

            if (in_array($compressionParam, $compressionMethods, true)) {
                Export::$compression = $compressionParam;
                Export::$bufferNeeded = true;
            }

            if (($isQuickExport && $quickExportOnServer) || (! $isQuickExport && $onServerParam)) {
                // Will we save dump on server?
                Export::$saveOnServer = $this->config->settings['SaveDir'] !== '';
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
        // Start with empty buffer
        $this->export->dumpBuffer = '';
        $this->export->dumpBufferLength = 0;

        // Array of dump buffers - used in separate file exports
        $this->export->dumpBufferObjects = [];

        // We send fake headers to avoid browser timeout when buffering
        Export::$timeStart = time();

        Export::$outputKanjiConversion = Encoding::canConvertKanji();

        // Do we need to convert charset?
        Export::$outputCharsetConversion = Export::$asFile
            && Encoding::isSupported()
            && Current::$charset !== null && Current::$charset !== 'utf-8'
            && in_array(Current::$charset, Encoding::listEncodings(), true);

        // Use on the fly compression?
        Export::$onFlyCompression = $this->config->settings['CompressOnFly'] && Export::$compression === 'gzip';
        if (Export::$onFlyCompression) {
            Export::$memoryLimit = $this->export->getMemoryLimit();
        }

        // Generate filename and mime type if needed
        $mimeType = '';
        if (Export::$asFile) {
            $filenameTemplate = $request->getParsedBodyParamAsString('filename_template', '');

            if ((bool) $rememberTemplate) {
                $this->export->rememberFilename($this->config, $exportType, $filenameTemplate);
            }

            $filename = $this->export->getFinalFilename(
                $exportPlugin,
                Export::$compression,
                Sanitize::sanitizeFilename(Util::expandUserString($filenameTemplate), true),
            );

            $mimeType = $this->export->getMimeType($exportPlugin, Export::$compression);
        }

        // For raw query export, filename will be export.extension
        if ($exportType === ExportType::Raw) {
            $filename = $this->export->getFinalFilename($exportPlugin, Export::$compression, 'export');
        }

        // Open file on server if needed
        if (Export::$saveOnServer) {
            [Export::$saveFilename, $message, Export::$fileHandle] = $this->export->openFile($filename, $isQuickExport);

            // problem opening export file on server?
            if ($message !== null) {
                $location = $this->export->getPageLocationAndSaveMessage($exportType, $message);
                $this->response->redirect($location);

                return $this->response->response();
            }
        } elseif (Export::$asFile) {
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
            $this->export->dumpBuffer = '';
            $this->export->dumpBufferLength = 0;

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

        if (Export::$saveOnServer && Current::$message !== null) {
            $location = $this->export->getPageLocationAndSaveMessage($exportType, Current::$message);
            $this->response->redirect($location);

            return $this->response->response();
        }

        /**
         * Send the dump as a file...
         */
        if (! Export::$asFile) {
            echo $this->export->getHtmlForDisplayedExportFooter($exportType, Current::$database, Current::$table);

            return $this->response->response();
        }

        // Convert the charset if required.
        if (Export::$outputCharsetConversion) {
            $this->export->dumpBuffer = Encoding::convertString(
                'utf-8',
                Current::$charset ?? 'utf-8',
                $this->export->dumpBuffer,
            );
        }

        // Compression needed?
        if (Export::$compression !== '') {
            if ($separateFiles !== '') {
                $this->export->dumpBuffer = $this->export->compress(
                    $this->export->dumpBufferObjects,
                    Export::$compression,
                    $filename,
                );
            } else {
                $this->export->dumpBuffer = $this->export->compress(
                    $this->export->dumpBuffer,
                    Export::$compression,
                    $filename,
                );
            }
        }

        /* If we saved on server, we have to close file now */
        if (Export::$saveOnServer) {
            $message = $this->export->closeFile(Export::$fileHandle, $this->export->dumpBuffer, Export::$saveFilename);
            $location = $this->export->getPageLocationAndSaveMessage($exportType, $message);
            $this->response->redirect($location);

            return $this->response->response();
        }

        return $this->responseFactory->createResponse()->write($this->export->dumpBuffer);
    }
}
