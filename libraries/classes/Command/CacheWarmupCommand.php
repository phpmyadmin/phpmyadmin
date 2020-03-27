<?php

declare(strict_types=1);

namespace PhpMyAdmin\Command;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Twig\CoreExtension;
use PhpMyAdmin\Twig\I18nExtension;
use PhpMyAdmin\Twig\MessageExtension;
use PhpMyAdmin\Twig\PluginsExtension;
use PhpMyAdmin\Twig\RelationExtension;
use PhpMyAdmin\Twig\SanitizeExtension;
use PhpMyAdmin\Twig\TableExtension;
use PhpMyAdmin\Twig\TrackerExtension;
use PhpMyAdmin\Twig\TransformationsExtension;
use PhpMyAdmin\Twig\UrlExtension;
use PhpMyAdmin\Twig\UtilExtension;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Cache\CacheInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use function fclose;
use function fopen;
use function fwrite;
use function json_encode;
use function str_replace;
use function strpos;

final class CacheWarmupCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'cache:warmup';

    protected function configure(): void
    {
        $this->setDescription('Warms up the Twig templates cache');
        $this->setHelp('The <info>%command.name%</info> command warms up the cache of the Twig templates.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $cfg, $PMA_Config, $dbi;

        $cfg['environment'] = 'production';
        $PMA_Config = new Config(CONFIG_FILE);
        $PMA_Config->set('environment', $cfg['environment']);
        $dbi = new DatabaseInterface(new DbiDummy());
        $tplDir = ROOT_PATH . 'templates';
        $tmpDir = ROOT_PATH . 'twig-templates';

        $loader = new FilesystemLoader($tplDir);
        $twig = new Environment($loader, [
            'auto_reload' => true,
            'cache' => $tmpDir,
        ]);
        $twig->setExtensions([
            new CoreExtension(),
            new I18nExtension(),
            new MessageExtension(),
            new PluginsExtension(),
            new RelationExtension(),
            new SanitizeExtension(),
            new TableExtension(),
            new TrackerExtension(),
            new TransformationsExtension(),
            new UrlExtension(),
            new UtilExtension(),
        ]);

        /** @var CacheInterface $twigCache */
        $twigCache = $twig->getCache(false);

        $replacements = [];
        $templates = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tplDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($templates as $file) {
            // Skip test files
            if (strpos($file->getPathname(), '/test/') !== false) {
                continue;
            }
            // force compilation
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $name = str_replace($tplDir . '/', '', $file->getPathname());
                $template = $twig->loadTemplate($name);

                // Generate line map
                $cacheFilename = $twigCache->generateKey($name, $twig->getTemplateClass($name));
                $template_file = 'templates/' . $name;
                $cache_file = str_replace($tmpDir, 'twig-templates', $cacheFilename);
                $replacements[$cache_file] = [$template_file, $template->getDebugInfo()];
            }
        }

        // Store replacements in JSON
        $handle = fopen($tmpDir . '/replace.json', 'w');
        if ($handle === false) {
            return 1;
        }

        fwrite($handle, (string) json_encode($replacements));
        fclose($handle);

        return 0;
    }
}
