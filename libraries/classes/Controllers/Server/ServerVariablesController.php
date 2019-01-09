<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PhpMyAdmin\Controllers\Server\ServerVariablesController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Common;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Williamdes\MariaDBMySQLKBS\Search as KBSearch;
use Williamdes\MariaDBMySQLKBS\KBException;

/**
 * Handles viewing and editing server variables
 *
 * @package PhpMyAdmin\Controllers
 */
class ServerVariablesController extends Controller
{

    /**
     * Constructs ServerVariablesController
     *
     * @param \PhpMyAdmin\Response          $response Response object
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface object
     */
    public function __construct($response, $dbi)
    {
        parent::__construct($response, $dbi);
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        $response = Response::getInstance();
        if ($response->isAjax()
            && isset($_GET['type'])
            && $_GET['type'] === 'getval'
        ) {
            $this->getValueAction();
            return;
        }

        if ($response->isAjax()
            && isset($_POST['type'])
            && $_POST['type'] === 'setval'
        ) {
            $this->setValueAction();
            return;
        }

        include ROOT_PATH . 'libraries/server_common.inc.php';

        $header   = $this->response->getHeader();
        $scripts  = $header->getScripts();
        $scripts->addFile('server_variables.js');

        /**
         * Displays the sub-page heading
         */
        $this->response->addHTML(
            $this->template->render('server/sub_page_header', [
                'type' => 'variables',
                'link' => 'server_system_variables',
            ])
        );

        /**
         * Sends the queries and buffers the results
         */
        $serverVarsResult = $this->dbi->tryQuery('SHOW SESSION VARIABLES;');

        if ($serverVarsResult !== false) {
            $serverVarsSession = [];
            while ($arr = $this->dbi->fetchRow($serverVarsResult)) {
                $serverVarsSession[$arr[0]] = $arr[1];
            }
            $this->dbi->freeResult($serverVarsResult);

            $serverVars = $this->dbi->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);

            /**
             * Link templates
            */
            $this->response->addHtml($this->_getHtmlForLinkTemplates());

            /**
             * Displays the page
            */
            $this->response->addHtml(
                $this->_getHtmlForServerVariables($serverVars, $serverVarsSession)
            );
        } else {
            /**
             * Display the error message
             */
            $this->response->addHTML(
                Message::error(
                    sprintf(
                        __(
                            'Not enough privilege to view server variables and '
                            . 'settings. %s'
                        ),
                        Util::linkToVarDocumentation(
                            'show_compatibility_56',
                            $this->dbi->isMariaDB()
                        )
                    )
                )->getDisplay()
            );
        }
    }

    /**
     * Handle the AJAX request for a single variable value
     *
     * @return void
     */
    public function getValueAction()
    {
        // Send with correct charset
        header('Content-Type: text/html; charset=UTF-8');
        // Do not use double quotes inside the query to avoid a problem
        // when server is running in ANSI_QUOTES sql_mode
        $varValue = $this->dbi->fetchSingleRow(
            'SHOW GLOBAL VARIABLES WHERE Variable_name=\''
            . $this->dbi->escapeString($_GET['varName']) . '\';',
            'NUM'
        );

        try {
            $type = KBSearch::getVariableType($_GET['varName']);
            if ($type === 'byte') {
                $this->response->addJSON(
                    'message',
                    implode(
                        ' ',
                        Util::formatByteDown($varValue[1], 3, 3)
                    )
                );
            } else {
                throw new KBException("Not a type=byte");
            }
        } catch (KBException $e) {
            $this->response->addJSON(
                'message',
                $varValue[1]
            );
        }
    }

    /**
     * Handle the AJAX request for setting value for a single variable
     *
     * @return void
     */
    public function setValueAction()
    {
        $value = $_POST['varValue'];
        $matches = [];
        try {
            $type = KBSearch::getVariableType($_POST['varName']);
            if ($type === 'byte' && preg_match(
                '/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i',
                $value,
                $matches
            )) {
                $exp = [
                    'kb' => 1,
                    'kib' => 1,
                    'mb' => 2,
                    'mib' => 2,
                    'gb' => 3,
                    'gib' => 3,
                ];
                $value = floatval($matches[1]) * pow(
                    1024,
                    $exp[mb_strtolower($matches[3])]
                );
            } else {
                throw new KBException("Not a type=byte or regex not matching");
            }
        } catch (KBException $e) {
            $value = $this->dbi->escapeString($value);
        }

        if (! is_numeric($value)) {
            $value = "'" . $value . "'";
        }

        if (! preg_match("/[^a-zA-Z0-9_]+/", $_POST['varName'])
            && $this->dbi->query(
                'SET GLOBAL ' . $_POST['varName'] . ' = ' . $value
            )
        ) {
            // Some values are rounded down etc.
            $varValue = $this->dbi->fetchSingleRow(
                'SHOW GLOBAL VARIABLES WHERE Variable_name="'
                . $this->dbi->escapeString($_POST['varName'])
                . '";',
                'NUM'
            );
            list($formattedValue, $isHtmlFormatted) = $this->_formatVariable(
                $_POST['varName'],
                $varValue[1]
            );

            if ($isHtmlFormatted == false) {
                $this->response->addJSON(
                    'variable',
                    htmlspecialchars(
                        $formattedValue
                    )
                );
            } else {
                $this->response->addJSON(
                    'variable',
                    $formattedValue
                );
            }
        } else {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'error',
                __('Setting variable failed')
            );
        }
    }

    /**
     * Format Variable
     *
     * @param string  $name  variable name
     * @param integer $value variable value
     *
     * @return array formatted string and bool if string is HTML formatted
     */
    private function _formatVariable($name, $value)
    {
        $isHtmlFormatted = false;
        $formattedValue = $value;

        if (is_numeric($value)) {
            try {
                $type = KBSearch::getVariableType($name);
                if ($type === 'byte') {
                    $isHtmlFormatted = true;
                    $formattedValue = '<abbr title="'
                        . htmlspecialchars(Util::formatNumber($value, 0)) . '">'
                        . htmlspecialchars(
                            implode(' ', Util::formatByteDown($value, 3, 3))
                        )
                        . '</abbr>';
                } else {
                    throw new KBException("Not a type=byte or regex not matching");
                }
            } catch (KBException $e) {
                $formattedValue = Util::formatNumber($value, 0);
            }
        }

        return [
            $formattedValue,
            $isHtmlFormatted,
        ];
    }

    /**
     * Prints link templates
     *
     * @return string
     */
    private function _getHtmlForLinkTemplates()
    {
        $url = 'server_variables.php' . Url::getCommon();
        return $this->template->render('server/variables/link_template', ['url' => $url]);
    }

    /**
     * Prints Html for Server Variables
     *
     * @param array $serverVars        global variables
     * @param array $serverVarsSession session variables
     *
     * @return string
     */
    private function _getHtmlForServerVariables(array $serverVars, array $serverVarsSession)
    {
        // filter
        $filterValue = ! empty($_REQUEST['filter']) ? $_REQUEST['filter'] : '';
        $output = $this->template->render('filter', ['filter_value' => $filterValue]);

        $output .= '<div class="responsivetable">';
        $output .= '<table id="serverVariables" class="width100 data filteredData noclick">';
        $output .= $this->template->render('server/variables/variable_table_head');
        $output .= '<tbody>';

        $output .= $this->_getHtmlForServerVariablesItems(
            $serverVars,
            $serverVarsSession
        );

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';

        return $output;
    }


    /**
     * Prints Html for Server Variables Items
     *
     * @param array $serverVars        global variables
     * @param array $serverVarsSession session variables
     *
     * @return string
     */
    private function _getHtmlForServerVariablesItems(
        array $serverVars,
        array $serverVarsSession
    ) {
        // list of static (i.e. non-editable) system variables
        $static_variables = KBSearch::getStaticVariables();

        $output = '';
        foreach ($serverVars as $name => $value) {
            $has_session_value = isset($serverVarsSession[$name])
                && $serverVarsSession[$name] != $value;
            $row_class = ($has_session_value ? ' diffSession' : '');
            $docLink   = Util::linkToVarDocumentation(
                $name,
                $this->dbi->isMariaDB(),
                str_replace('_', '&nbsp;', $name)
            );

            list($formattedValue, $isHtmlFormatted) = $this->_formatVariable($name, $value);
            if ($has_session_value) {
                list($sessionFormattedValue, $sessionIsHtmlFormatted) = $this->_formatVariable(
                    $name,
                    $serverVarsSession[$name]
                );
            }

            $output .= $this->template->render('server/variables/variable_row', [
                'row_class' => $row_class,
                'editable' => ! in_array(
                    strtolower($name),
                    $static_variables
                ),
                'doc_link' => $docLink,
                'name' => $name,
                'value' => $formattedValue,
                'is_superuser' => $this->dbi->isSuperuser(),
                'is_html_formatted' => $isHtmlFormatted,
                'has_session_value' => $has_session_value,
                'session_value' => isset($sessionFormattedValue)?$sessionFormattedValue:null,
                'session_is_html_formated' => isset($sessionIsHtmlFormatted)?$sessionIsHtmlFormatted:null,
            ]);
        }

        return $output;
    }
}
