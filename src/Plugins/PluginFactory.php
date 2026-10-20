<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use FilesystemIterator;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Transformations;
use SplFileInfo;
use Throwable;

use function class_exists;
use function is_subclass_of;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function sprintf;
use function str_starts_with;
use function strcasecmp;
use function usort;

use const ROOT_PATH;

final readonly class PluginFactory
{
    public function __construct(
        private Relation $relation,
        private OutputHandler $outputHandler,
        private Transformations $transformations,
        private DatabaseInterface $dbi,
        private Config $config,
        private Import $import,
    ) {
    }

    public function getExportPlugin(
        string $format,
        ExportType $exportType = ExportType::Raw,
        bool $singleTable = false,
    ): ExportPlugin|null {
        $pluginFormat = mb_strtoupper($format[0]) . mb_strtolower(mb_substr($format, 1));
        $class = sprintf('PhpMyAdmin\\Plugins\\Export\\Export%s', $pluginFormat);
        if (! class_exists($class) || ! is_subclass_of($class, ExportPlugin::class)) {
            return null;
        }

        return new $class(
            $this->relation,
            $this->outputHandler,
            $this->transformations,
            $this->dbi,
            $this->config,
            $exportType,
            $singleTable,
        );
    }

    public function getSchemaPlugin(string $format): SchemaPlugin|null
    {
        $pluginFormat = mb_strtoupper($format[0]) . mb_strtolower(mb_substr($format, 1));
        $class = sprintf('PhpMyAdmin\\Plugins\\Schema\\Schema%s', $pluginFormat);
        if (! class_exists($class) || ! is_subclass_of($class, SchemaPlugin::class)) {
            return null;
        }

        return new $class();
    }

    /**
     * @return ExportPlugin[]
     * @psalm-return list<ExportPlugin>
     */
    public function getAllExportPlugins(ExportType $type, bool $singleTable): array
    {
        $plugins = [];
        foreach (self::getAvailableClasses('Export') as $class) {
            if (! is_subclass_of($class, ExportPlugin::class)) {
                continue;
            }

            $plugins[] = new $class(
                $this->relation,
                $this->outputHandler,
                $this->transformations,
                $this->dbi,
                $this->config,
                $type,
                $singleTable,
            );
        }

        return self::sort($plugins);
    }

    /**
     * @return ImportPlugin[]
     * @psalm-return list<ImportPlugin>
     */
    public function getAllImportPlugins(): array
    {
        $plugins = [];
        foreach (self::getAvailableClasses('Import') as $class) {
            if (! is_subclass_of($class, ImportPlugin::class)) {
                continue;
            }

            $plugins[] = new $class($this->import, $this->dbi, $this->config);
        }

        return self::sort($plugins);
    }

    /**
     * @return SchemaPlugin[]
     * @psalm-return list<SchemaPlugin>
     */
    public function getAllSchemaPlugins(): array
    {
        $plugins = [];
        foreach (self::getAvailableClasses('Schema') as $class) {
            if (! is_subclass_of($class, SchemaPlugin::class)) {
                continue;
            }

            $plugins[] = new $class();
        }

        return self::sort($plugins);
    }

    /**
     * @psalm-param 'Export'|'Import'|'Schema' $type
     *
     * @return class-string<Plugin>[]
     */
    private static function getAvailableClasses(string $type): array
    {
        try {
            $files = new FilesystemIterator(ROOT_PATH . 'src/Plugins/' . $type);
        } catch (Throwable) {
            return [];
        }

        $classes = [];

        /** @var SplFileInfo $fileInfo */
        foreach ($files as $fileInfo) {
            if (! $fileInfo->isReadable() || ! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            if (! str_starts_with($fileInfo->getFilename(), $type)) {
                continue;
            }

            $class = sprintf('PhpMyAdmin\\Plugins\\%s\\%s', $type, $fileInfo->getBasename('.php'));
            if (! class_exists($class) || ! is_subclass_of($class, Plugin::class) || ! $class::isAvailable()) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * @param list<T> $plugins
     * @psalm-param list<T> $plugins
     *
     * @return list<T>
     *
     * @template T of Plugin
     */
    private static function sort(array $plugins): array
    {
        usort($plugins, static fn (Plugin $plugin1, Plugin $plugin2): int => strcasecmp(
            $plugin1->getProperties()->getText(),
            $plugin2->getProperties()->getText(),
        ));

        return $plugins;
    }
}
