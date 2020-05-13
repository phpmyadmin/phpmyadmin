<?php
/**
 * hold PhpMyAdmin\Template class
 */
declare(strict_types=1);

namespace PhpMyAdmin;

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
use RuntimeException;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;
use const E_USER_WARNING;
use function sprintf;
use function trigger_error;

/**
 * Handle front end templating
 */
class Template
{
    /**
     * Twig environment
     *
     * @var Environment
     */
    protected static $twig;

    /**
     * @var string
     */
    public const BASE_PATH = 'templates/';

    public function __construct()
    {
        global $cfg;

        /** @var Config|null $config */
        $config = $GLOBALS['PMA_Config'];
        if (static::$twig === null) {
            $loader = new FilesystemLoader(self::BASE_PATH);
            $cache_dir = $config !== null ? $config->getTempDir('twig') : null;
            /* Twig expects false when cache is not configured */
            if ($cache_dir === null) {
                $cache_dir = false;
            }
            $twig = new Environment($loader, [
                'auto_reload' => true,
                'cache' => $cache_dir,
            ]);
            if ($cfg['environment'] === 'development') {
                $twig->enableDebug();
                $twig->addExtension(new DebugExtension());
            }
            $twig->addExtension(new CoreExtension());
            $twig->addExtension(new I18nExtension());
            $twig->addExtension(new MessageExtension());
            $twig->addExtension(new PluginsExtension());
            $twig->addExtension(new RelationExtension());
            $twig->addExtension(new SanitizeExtension());
            $twig->addExtension(new TableExtension());
            $twig->addExtension(new TrackerExtension());
            $twig->addExtension(new TransformationsExtension());
            $twig->addExtension(new UrlExtension());
            $twig->addExtension(new UtilExtension());
            static::$twig = $twig;
        }
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
    public function load(string $templateName): TemplateWrapper
    {
        try {
            $template = static::$twig->load($templateName . '.twig');
        } catch (RuntimeException $e) {
            /* Retry with disabled cache */
            static::$twig->setCache(false);
            $template = static::$twig->load($templateName . '.twig');
            /*
             * The trigger error is intentionally after second load
             * to avoid triggering error when disabling cache does not
             * solve it.
             */
            trigger_error(
                sprintf(
                    __('Error while working with template cache: %s'),
                    $e->getMessage()
                ),
                E_USER_WARNING
            );
        }

        return $template;
    }

    /**
     * @param string $template Template path name
     * @param array  $data     Associative array of template variables
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function render(string $template, array $data = []): string
    {
        return $this->load($template)->render($data);
    }
}
