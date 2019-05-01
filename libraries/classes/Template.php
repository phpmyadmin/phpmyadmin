<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Template class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Twig\CharsetsExtension;
use PhpMyAdmin\Twig\CoreExtension;
use PhpMyAdmin\Twig\I18nExtension;
use PhpMyAdmin\Twig\MessageExtension;
use PhpMyAdmin\Twig\PluginsExtension;
use PhpMyAdmin\Twig\RelationExtension;
use PhpMyAdmin\Twig\SanitizeExtension;
use PhpMyAdmin\Twig\ServerPrivilegesExtension;
use PhpMyAdmin\Twig\StorageEngineExtension;
use PhpMyAdmin\Twig\TableExtension;
use PhpMyAdmin\Twig\TrackerExtension;
use PhpMyAdmin\Twig\TransformationsExtension;
use PhpMyAdmin\Twig\UrlExtension;
use PhpMyAdmin\Twig\UtilExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class Template
 *
 * Handle front end templating
 *
 * @package PhpMyAdmin
 */
class Template
{
    /**
     * Twig environment
     * @var Environment
     */
    protected static $twig;

    /**
     * @var string
     */
    public const BASE_PATH = 'templates/';

    /**
     * Template constructor
     */
    public function __construct()
    {
        /** @var \PhpMyAdmin\Config $config */
        $config = $GLOBALS['PMA_Config'];
        if (is_null($this::$twig)) {
            $loader = new FilesystemLoader(static::BASE_PATH);
            $cache_dir = $config->getTempDir('twig');
            /* Twig expects false when cache is not configured */
            if (is_null($cache_dir)) {
                $cache_dir = false;
            }
            $twig = new Environment($loader, [
                'auto_reload' => true,
                'cache' => $cache_dir,
                'debug' => false,
            ]);
            $twig->addExtension(new CharsetsExtension());
            $twig->addExtension(new CoreExtension());
            $twig->addExtension(new I18nExtension());
            $twig->addExtension(new MessageExtension());
            $twig->addExtension(new PluginsExtension());
            $twig->addExtension(new RelationExtension());
            $twig->addExtension(new SanitizeExtension());
            $twig->addExtension(new ServerPrivilegesExtension());
            $twig->addExtension(new StorageEngineExtension());
            $twig->addExtension(new TableExtension());
            $twig->addExtension(new TrackerExtension());
            $twig->addExtension(new TransformationsExtension());
            $twig->addExtension(new UrlExtension());
            $twig->addExtension(new UtilExtension());
            $this::$twig = $twig;
        }
    }

    /**
     * Loads a template.
     *
     * @param string $templateName Template path name
     *
     * @return \Twig_TemplateWrapper
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function load(string $templateName): \Twig_TemplateWrapper
    {
        try {
            $template = $this::$twig->load($templateName . '.twig');
        } catch (\RuntimeException $e) {
            /* Retry with disabled cache */
            $this::$twig->setCache(false);
            $template = $this::$twig->load($templateName . '.twig');
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
     * @return string
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render(string $template, array $data = []): string
    {
        return $this->load($template)->render($data);
    }
}
