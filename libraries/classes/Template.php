<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Template class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Twig\CharsetsExtension;
use PhpMyAdmin\Twig\CoreExtension;
use PhpMyAdmin\Twig\I18nExtension;
use PhpMyAdmin\Twig\MessageExtension;
use PhpMyAdmin\Twig\PhpFunctionsExtension;
use PhpMyAdmin\Twig\PluginsExtension;
use PhpMyAdmin\Twig\RelationExtension;
use PhpMyAdmin\Twig\SanitizeExtension;
use PhpMyAdmin\Twig\ServerPrivilegesExtension;
use PhpMyAdmin\Twig\UrlExtension;
use PhpMyAdmin\Twig\UtilExtension;
use Twig_Environment;
use Twig_Loader_Filesystem;

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
     * Name of the template
     */
    protected $name = null;

    /**
     * Twig environment
     */
    protected $twig;

    const BASE_PATH = 'templates/';

    /**
     * Template constructor
     *
     * @param string $name            Template name
     */
    protected function __construct($name)
    {
        static $twig = null;

        $this->name = $name;

        if (is_null($twig)) {
            $loader = new Twig_Loader_Filesystem(static::BASE_PATH);
            $cache_dir = $GLOBALS['PMA_Config']->getTempDir('twig');
            /* Twig expects false when cache is not configured */
            if (is_null($cache_dir)) {
                $cache_dir = false;
            }
            $twig = new Twig_Environment($loader, array(
                'auto_reload' => true,
                'cache' => $cache_dir,
                'debug' => false,
            ));
            $twig->addExtension(new CharsetsExtension());
            $twig->addExtension(new CoreExtension());
            $twig->addExtension(new I18nExtension());
            $twig->addExtension(new MessageExtension());
            $twig->addExtension(new PhpFunctionsExtension());
            $twig->addExtension(new PluginsExtension());
            $twig->addExtension(new RelationExtension());
            $twig->addExtension(new SanitizeExtension());
            $twig->addExtension(new ServerPrivilegesExtension());
            $twig->addExtension(new UrlExtension());
            $twig->addExtension(new UtilExtension());
        }
        $this->twig = $twig;
    }

    /**
     * Template getter
     *
     * @param string $name            Template name
     *
     * @return Template
     */
    public static function get($name)
    {
        return new Template($name);
    }

    /**
     * Render template
     *
     * @param array $data            Variables to be provided to the template
     *
     * @return string
     */
    public function render(array $data = array())
    {
        $template = static::BASE_PATH . $this->name;

        if (file_exists($template . '.twig')) {
            try {
                $template = $this->twig->load($this->name . '.twig');
            } catch (\RuntimeException $e) {
                /* Retry with disabled cache */
                $this->twig->setCache(false);
                $template = $this->twig->load($this->name . '.twig');
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
            return $template->render($data);
        }

        $template = $template . '.phtml';
        try {
            extract($data);
            ob_start();
            if (@file_exists($template)) {
                include $template;
            } else {
                throw new \LogicException(
                    'The template "' . $template . '" not found.'
                );
            }
            $content = ob_get_clean();

            return $content;
        } catch (\LogicException $e) {
            ob_end_clean();
            throw new \LogicException($e->getMessage());
        }
    }
}
