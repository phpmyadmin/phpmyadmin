<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Twig\AssetExtension;
use PhpMyAdmin\Twig\CoreExtension;
use PhpMyAdmin\Twig\Extensions\Node\TransNode;
use PhpMyAdmin\Twig\FlashMessagesExtension;
use PhpMyAdmin\Twig\I18nExtension;
use PhpMyAdmin\Twig\MessageExtension;
use PhpMyAdmin\Twig\SanitizeExtension;
use PhpMyAdmin\Twig\TransformationsExtension;
use PhpMyAdmin\Twig\UrlExtension;
use PhpMyAdmin\Twig\UtilExtension;
use RuntimeException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;
use Twig\TemplateWrapper;

/**
 * Handle front end templating
 */
class Template
{
    /**
     * Twig environment
     */
    protected static Environment|null $twig = null;

    public const TEMPLATES_FOLDER = ROOT_PATH . 'resources/templates';

    private Config $config;

    public function __construct(Config|null $config = null)
    {
        $this->config = $config ?? Config::getInstance();
    }

    public static function getTwigEnvironment(string|null $cacheDir, bool $isDevEnv): Environment
    {
        /* Twig expects false when cache is not configured */
        if ($cacheDir === null) {
            $cacheDir = false;
        }

        $loader = new FilesystemLoader(self::TEMPLATES_FOLDER);
        $twig = new Environment($loader, ['auto_reload' => true, 'cache' => $cacheDir]);

        $twig->addRuntimeLoader(new ContainerRuntimeLoader(ContainerBuilder::getContainer()));

        if ($isDevEnv) {
            $twig->enableDebug();
            $twig->enableStrictVariables();
            $twig->addExtension(new DebugExtension());
            // This will enable debug for the extension to print lines
            // It is used in po file lines re-mapping
            TransNode::$enableAddDebugInfo = true;
        } else {
            $twig->disableDebug();
            $twig->disableStrictVariables();
            TransNode::$enableAddDebugInfo = false;
        }

        $twig->addExtension(new AssetExtension());
        $twig->addExtension(new CoreExtension());
        $twig->addExtension(new FlashMessagesExtension());
        $twig->addExtension(new I18nExtension());
        $twig->addExtension(new MessageExtension());
        $twig->addExtension(new SanitizeExtension());
        $twig->addExtension(new TransformationsExtension());
        $twig->addExtension(new UrlExtension());
        $twig->addExtension(new UtilExtension());

        return $twig;
    }

    /**
     * Loads a template.
     *
     * @param string $templateName Template path name
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function load(string $templateName): TemplateWrapper
    {
        if (static::$twig === null) {
            $isDevEnv = $this->config->config->environment === 'development';
            static::$twig = self::getTwigEnvironment(CACHE_DIR . 'twig', $isDevEnv);
        }

        try {
            return static::$twig->load($templateName . '.twig');
        } catch (RuntimeException) { // @phpstan-ignore-line thrown by Twig\Cache\FilesystemCache
            /* Retry with disabled cache */
            static::$twig->setCache(false);

            return static::$twig->load($templateName . '.twig');
        }
    }

    /**
     * @param string  $template Template path name
     * @param mixed[] $data     Associative array of template variables
     *
     * @throws Throwable
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $template, array $data = []): string
    {
        return $this->load($template)->render($data);
    }

    public function disableCache(): void
    {
        if (static::$twig === null) {
            static::$twig = self::getTwigEnvironment(null, false);
        }

        static::$twig->setCache(false);
    }
}
