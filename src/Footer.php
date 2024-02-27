<?php
/**
 * Used to render the footer of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Routing\Routing;
use Traversable;

use function basename;
use function file_exists;
use function in_array;
use function is_array;
use function is_object;
use function is_scalar;
use function json_encode;
use function json_last_error;

/**
 * Class used to output the footer
 */
class Footer
{
    /**
     * Scripts instance
     */
    private Scripts $scripts;
    /**
     * Whether we are servicing an ajax request.
     */
    private bool $isAjax = false;
    /**
     * Whether to only close the BODY and HTML tags
     * or also include scripts, errors and links
     */
    private bool $isMinimal = false;
    /**
     * Whether to display anything
     */
    private bool $isEnabled = true;

    private Relation $relation;

    public function __construct(private readonly Template $template, private readonly Config $config)
    {
        $this->scripts = new Scripts($this->template);
        $this->relation = new Relation(DatabaseInterface::getInstance());
    }

    /**
     * @return array<string, string>
     * @psalm-return array{revision: string, revisionUrl: string, branch: string, branchUrl: string}|[]
     */
    private function getGitRevisionInfo(): array
    {
        $info = [];

        if (@file_exists(ROOT_PATH . 'revision-info.php')) {
            /** @psalm-suppress MissingFile,UnresolvableInclude */
            $info = include ROOT_PATH . 'revision-info.php';
        }

        return is_array($info) ? $info : [];
    }

    /**
     * Remove recursions and iterator objects from an object
     *
     * @param mixed   $object Object to clean
     * @param mixed[] $stack  Stack used to keep track of recursion, need not be passed for the first time
     *
     * @return mixed Reference passed object
     */
    private static function removeRecursion(mixed &$object, array $stack = []): mixed
    {
        if ((is_object($object) || is_array($object)) && $object) {
            if ($object instanceof Traversable) {
                $object = '***ITERATOR***';
            } elseif (! in_array($object, $stack, true)) {
                $stack[] = $object;
                // @phpstan-ignore-next-line
                foreach ($object as &$subObject) {
                    self::removeRecursion($subObject, $stack);
                }
            } else {
                $object = '***RECURSION***';
            }
        }

        return $object;
    }

    /**
     * Renders the debug messages
     */
    public function getDebugMessage(): string
    {
        $retval = '\'false\'';
        if (
            $this->config->settings['DBG']['sql'] && empty($_REQUEST['no_debug']) && ! empty($_SESSION['debug'])
        ) {
            // Remove recursions and iterators from $_SESSION['debug']
            self::removeRecursion($_SESSION['debug']);

            $retval = (string) json_encode($_SESSION['debug']);
            $_SESSION['debug'] = [];

            return json_last_error() !== 0 ? '\'false\'' : $retval;
        }

        $_SESSION['debug'] = [];

        return $retval;
    }

    /**
     * Returns the url of the current page
     */
    public function getSelfUrl(): string
    {
        $params = [];
        $params['route'] = Routing::$route;

        if (Current::$database !== '') {
            $params['db'] = Current::$database;
        }

        if (Current::$table !== '') {
            $params['table'] = Current::$table;
        }

        $params['server'] = Current::$server;

        if (isset($_REQUEST['single_table'])) {
            $params['single_table'] = $_REQUEST['single_table'];
        }

        return basename(Core::getEnv('SCRIPT_NAME')) . Url::getCommonRaw($params);
    }

    /**
     * Renders the link to open a new page
     */
    public function getErrorMessages(): string
    {
        $retval = '';
        $errorHandler = ErrorHandler::getInstance();
        if ($errorHandler->hasDisplayErrors()) {
            $retval .= $errorHandler->getDispErrors();
        }

        /**
         * Report php errors
         */
        $errorHandler->reportErrors();

        return $retval;
    }

    /**
     * Saves query in history
     */
    private function setHistory(): void
    {
        if (
            (
                isset($_REQUEST['no_history'])
                && is_scalar($_REQUEST['no_history'])
                && (string) $_REQUEST['no_history'] !== ''
            )
            || ! empty($GLOBALS['error_message'])
            || empty($GLOBALS['sql_query'])
            || ! DatabaseInterface::getInstance()->isConnected()
        ) {
            return;
        }

        $this->relation->setHistory(
            Current::$database,
            Current::$table,
            $this->config->selectedServer['user'],
            $GLOBALS['sql_query'],
        );
    }

    /**
     * Disables the rendering of the footer
     */
    public function disable(): void
    {
        $this->isEnabled = false;
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     */
    public function setAjax(bool $isAjax): void
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Turn on minimal display mode
     */
    public function setMinimal(): void
    {
        $this->isMinimal = true;
    }

    /**
     * Returns the Scripts object
     *
     * @return Scripts object
     */
    public function getScripts(): Scripts
    {
        return $this->scripts;
    }

    /**
     * Renders the footer
     */
    public function getDisplay(): string
    {
        $this->setHistory();
        if ($this->isEnabled) {
            if (! $this->isAjax && ! $this->isMinimal) {
                if (Core::getEnv('SCRIPT_NAME') !== '') {
                    $url = $this->getSelfUrl();
                }

                $this->scripts->addCode('window.Console.debugSqlInfo = ' . $this->getDebugMessage() . ';');
                $errorMessages = $this->getErrorMessages();
                $scripts = $this->scripts->getDisplay();

                if ($this->config->settings['DBG']['demo']) {
                    $gitRevisionInfo = $this->getGitRevisionInfo();
                }

                $footer = Config::renderFooter();
            }

            return $this->template->render('footer', [
                'is_ajax' => $this->isAjax,
                'is_minimal' => $this->isMinimal,
                'self_url' => $url ?? null,
                'error_messages' => $errorMessages ?? '',
                'scripts' => $scripts ?? '',
                'is_demo' => $this->config->settings['DBG']['demo'],
                'git_revision_info' => $gitRevisionInfo ?? [],
                'footer' => $footer ?? '',
            ]);
        }

        return '';
    }
}
