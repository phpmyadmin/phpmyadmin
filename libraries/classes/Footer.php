<?php
/**
 * Used to render the footer of PMA's pages
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use Traversable;
use function basename;
use function file_exists;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_object;
use function json_encode;
use function json_last_error;
use function sprintf;
use function strlen;

/**
 * Class used to output the footer
 */
class Footer
{
    /**
     * Scripts instance
     *
     * @access private
     * @var Scripts
     */
    private $scripts;
    /**
     * Whether we are servicing an ajax request.
     *
     * @access private
     * @var bool
     */
    private $isAjax;
    /**
     * Whether to only close the BODY and HTML tags
     * or also include scripts, errors and links
     *
     * @access private
     * @var bool
     */
    private $isMinimal;
    /**
     * Whether to display anything
     *
     * @access private
     * @var bool
     */
    private $isEnabled;

    /** @var Relation */
    private $relation;

    /** @var Template */
    private $template;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        global $dbi;

        $this->template = new Template();
        $this->isEnabled = true;
        $this->scripts = new Scripts();
        $this->isMinimal = false;
        $this->relation = new Relation($dbi);
    }

    /**
     * Returns the message for demo server to error messages
     */
    private function getDemoMessage(): string
    {
        $message = '<a href="/">' . __('phpMyAdmin Demo Server') . '</a>: ';
        if (@file_exists(ROOT_PATH . 'revision-info.php')) {
            $revision = '';
            $fullrevision = '';
            $repobase = '';
            $repobranchbase = '';
            $branch = '';
            include ROOT_PATH . 'revision-info.php';
            $message .= sprintf(
                __('Currently running Git revision %1$s from the %2$s branch.'),
                '<a target="_blank" rel="noopener noreferrer" href="'
                . htmlspecialchars($repobase . $fullrevision) . '">'
                . htmlspecialchars($revision) . '</a>',
                '<a target="_blank" rel="noopener noreferrer" href="'
                . htmlspecialchars($repobranchbase . $branch) . '">'
                . htmlspecialchars($branch) . '</a>'
            );
        } else {
            $message .= __('Git information missing!');
        }

        return Message::notice($message)->getDisplay();
    }

    /**
     * Remove recursions and iterator objects from an object
     *
     * @param object|array $object Object to clean
     * @param array        $stack  Stack used to keep track of recursion,
     *                             need not be passed for the first time
     *
     * @return object Reference passed object
     */
    private static function removeRecursion(&$object, array $stack = [])
    {
        if ((is_object($object) || is_array($object)) && $object) {
            if ($object instanceof Traversable) {
                $object = '***ITERATOR***';
            } elseif (! in_array($object, $stack, true)) {
                $stack[] = $object;
                foreach ($object as &$subobject) {
                    self::removeRecursion($subobject, $stack);
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
        $retval = '\'null\'';
        if ($GLOBALS['cfg']['DBG']['sql']
            && empty($_REQUEST['no_debug'])
            && ! empty($_SESSION['debug'])
        ) {
            // Remove recursions and iterators from $_SESSION['debug']
            self::removeRecursion($_SESSION['debug']);

            $retval = json_encode($_SESSION['debug']);
            $_SESSION['debug'] = [];

            return json_last_error() ? '\'false\'' : $retval;
        }
        $_SESSION['debug'] = [];

        return $retval;
    }

    /**
     * Returns the url of the current page
     */
    public function getSelfUrl(): string
    {
        global $route, $db, $table, $server;

        $params = [];
        if (isset($route)) {
            $params['route'] = $route;
        }
        if (isset($db) && strlen($db) > 0) {
            $params['db'] = $db;
        }
        if (isset($table) && strlen($table) > 0) {
            $params['table'] = $table;
        }
        $params['server'] = $server;

        // needed for server privileges tabs
        if (isset($_GET['viewing_mode'])
            && in_array($_GET['viewing_mode'], ['server', 'db', 'table'])
        ) {
            $params['viewing_mode'] = $_GET['viewing_mode'];
        }
        /*
         * @todo    coming from /server/privileges, here $db is not set,
         *          add the following condition below when that is fixed
         *          && $_GET['checkprivsdb'] == $db
         */
        if (isset($_GET['checkprivsdb'])
        ) {
            $params['checkprivsdb'] = $_GET['checkprivsdb'];
        }
        /*
         * @todo    coming from /server/privileges, here $table is not set,
         *          add the following condition below when that is fixed
         *          && $_REQUEST['checkprivstable'] == $table
         */
        if (isset($_GET['checkprivstable'])
        ) {
            $params['checkprivstable'] = $_GET['checkprivstable'];
        }
        if (isset($_REQUEST['single_table'])
            && in_array($_REQUEST['single_table'], [true, false])
        ) {
            $params['single_table'] = $_REQUEST['single_table'];
        }

        return basename(Core::getenv('SCRIPT_NAME')) . Url::getCommonRaw($params);
    }

    /**
     * Renders the link to open a new page
     *
     * @param string $url The url of the page
     */
    private function getSelfLink(string $url): string
    {
        $retval  = '';
        $retval .= '<div id="selflink" class="print_ignore">';
        $retval .= '<a href="' . htmlspecialchars($url) . '"'
            . ' title="' . __('Open new phpMyAdmin window') . '" target="_blank" rel="noopener noreferrer">';
        if (Util::showIcons('TabsMode')) {
            $retval .= Html\Generator::getImage(
                'window-new',
                __('Open new phpMyAdmin window')
            );
        } else {
            $retval .=  __('Open new phpMyAdmin window');
        }
        $retval .= '</a>';
        $retval .= '</div>';

        return $retval;
    }

    /**
     * Renders the link to open a new page
     */
    public function getErrorMessages(): string
    {
        $retval = '';
        if ($GLOBALS['error_handler']->hasDisplayErrors()) {
            $retval .= $GLOBALS['error_handler']->getDispErrors();
        }

        /**
         * Report php errors
         */
        $GLOBALS['error_handler']->reportErrors();

        return $retval;
    }

    /**
     * Saves query in history
     */
    private function setHistory(): void
    {
        global $dbi;

        if (Core::isValid($_REQUEST['no_history'])
            || ! empty($GLOBALS['error_message'])
            || empty($GLOBALS['sql_query'])
            || ! isset($dbi)
            || ! $dbi->isConnected()
        ) {
            return;
        }

        $this->relation->setHistory(
            Core::ifSetOr($GLOBALS['db'], ''),
            Core::ifSetOr($GLOBALS['table'], ''),
            $GLOBALS['cfg']['Server']['user'],
            $GLOBALS['sql_query']
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
                if (Core::getenv('SCRIPT_NAME')
                    && empty($_POST)
                    && ! $this->isAjax
                ) {
                    $url = $this->getSelfUrl();
                    $header = Response::getInstance()->getHeader();
                    $scripts = $header->getScripts()->getFiles();
                    $menuHash = $header->getMenu()->getHash();
                    // prime the client-side cache
                    $this->scripts->addCode(
                        sprintf(
                            'if (! (history && history.pushState)) '
                            . 'MicroHistory.primer = {'
                            . ' url: "%s",'
                            . ' scripts: %s,'
                            . ' menuHash: "%s"'
                            . '};',
                            Sanitize::escapeJsString($url),
                            json_encode($scripts),
                            Sanitize::escapeJsString($menuHash)
                        )
                    );
                }
                if (Core::getenv('SCRIPT_NAME')
                    && ! $this->isAjax
                ) {
                    $url = $this->getSelfUrl();
                    $selfLink = $this->getSelfLink($url);
                }
                $this->scripts->addCode(
                    'var debugSQLInfo = ' . $this->getDebugMessage() . ';'
                );

                $errorMessages = $this->getErrorMessages();
                $scripts = $this->scripts->getDisplay();

                if ($GLOBALS['cfg']['DBG']['demo']) {
                    $demoMessage = $this->getDemoMessage();
                }

                $footer = Config::renderFooter();
            }

            return $this->template->render('footer', [
                'is_ajax' => $this->isAjax,
                'is_minimal' => $this->isMinimal,
                'self_link' => $selfLink ?? '',
                'error_messages' => $errorMessages ?? '',
                'scripts' => $scripts ?? '',
                'is_demo' => $GLOBALS['cfg']['DBG']['demo'],
                'demo_message' => $demoMessage ?? '',
                'footer' => $footer ?? '',
            ]);
        }

        return '';
    }
}
