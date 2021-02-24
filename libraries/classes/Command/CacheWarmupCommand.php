<?php

declare(strict_types=1);

namespace PhpMyAdmin\Command;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Routing;
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
use function is_file;
use function sprintf;

final class CacheWarmupCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'cache:warmup';

    protected function configure(): void
    {
        $this->setDescription('Warms up the Twig templates cache');
        $this->addOption('twig', null, null, 'Warm up twig templates cache.');
        $this->addOption('routing', null, null, 'Warm up routing cache.');
        $this->setHelp('The <info>%command.name%</info> command warms up the cache of the Twig templates.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('twig') === true && $input->getOption('routing') === true) {
            $output->writeln('Please specify --twig or --routing');

            return 1;
        }

        if ($input->getOption('twig') === true) {
            return $this->warmUpTwigCache($output);
        }

        if ($input->getOption('routing') === true) {
            return $this->warmUpRoutingCache($output);
        }

        $output->writeln('Warming up all caches.', OutputInterface::VERBOSITY_VERBOSE);
        $twigCode = $this->warmUptwigCache($output);
        if ($twigCode !== 0) {
            $output->writeln('Twig cache generation had an error.');

            return $twigCode;
        }
        $routingCode = $this->warmUpTwigCache($output);
        if ($routingCode !== 0) {
            $output->writeln('Routing cache generation had an error.');

            return $twigCode;
        }
        $output->writeln('Warm up of all caches done.', OutputInterface::VERBOSITY_VERBOSE);

        return 0;
    }

    private function warmUpRoutingCache(OutputInterface $output): int
    {
        $output->writeln('Warming up the routing cache', OutputInterface::VERBOSITY_VERBOSE);
        Routing::getDispatcher();

        if (is_file(Routing::ROUTES_CACHE_FILE)) {
            $output->writeln('Warm up done.', OutputInterface::VERBOSITY_VERBOSE);

            return 0;
        }
        $output->writeln(
            sprintf(
                'Warm up did not work, the folder "%s" is probably not writable.',
                CACHE_DIR
            ),
            OutputInterface::VERBOSITY_NORMAL
        );

        return 1;
    }

    private function warmUpTwigCache(OutputInterface $output): int
    {
        global $cfg, $PMA_Config, $dbi;

        $output->writeln('Warming up the twig cache', OutputInterface::VERBOSITY_VERBOSE);
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

        $output->writeln('Searching for files...', OutputInterface::VERBOSITY_VERY_VERBOSE);

        $replacements = [];
        $templates = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tplDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $output->writeln('Warming templates', OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach ($templates as $file) {
            // Skip test files
            if (strpos($file->getPathname(), '/test/') !== false) {
                continue;
            }
            // force compilation
            if (! $file->isFile() || $file->getExtension() !== 'twig') {
                continue;
            }

            $name = str_replace($tplDir . '/', '', $file->getPathname());
            $output->writeln('Loading: ' . $name, OutputInterface::VERBOSITY_DEBUG);
            if (Environment::MAJOR_VERSION === 3) {
                $template = $twig->loadTemplate($twig->getTemplateClass($name), $name);
            } else {// @phpstan-ignore-line Twig 2
                $template = $twig->loadTemplate($name);// @phpstan-ignore-line Twig 2
            }

            // Generate line map
            $cacheFilename = $twigCache->generateKey($name, $twig->getTemplateClass($name));
            $template_file = 'templates/' . $name;
            $cache_file = str_replace($tmpDir, 'twig-templates', $cacheFilename);
            $replacements[$cache_file] = [$template_file, $template->getDebugInfo()];
        }

        $output->writeln('Writing replacements...', OutputInterface::VERBOSITY_VERY_VERBOSE);
        // Store replacements in JSON
        $handle = fopen($tmpDir . '/replace.json', 'w');
        if ($handle === false) {
            return 1;
        }

        fwrite($handle, (string) json_encode($replacements));
        fclose($handle);
        $output->writeln('Warm up done.', OutputInterface::VERBOSITY_VERBOSE);

        return 0;
    }
}
