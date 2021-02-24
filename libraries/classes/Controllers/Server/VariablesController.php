<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function header;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_numeric;
use function mb_strtolower;
use function pow;
use function preg_match;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Handles viewing and editing server variables
 */
class VariablesController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $err_url;

        $params = ['filter' => $_GET['filter'] ?? null];
        $err_url = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $filterValue = ! empty($params['filter']) ? $params['filter'] : '';

        $this->addScriptFiles(['server/variables.js']);

        $variables = [];
        $serverVarsResult = $this->dbi->tryQuery('SHOW SESSION VARIABLES;');
        if ($serverVarsResult !== false) {
            $serverVarsSession = [];
            while ($arr = $this->dbi->fetchRow($serverVarsResult)) {
                $serverVarsSession[$arr[0]] = $arr[1];
            }
            $this->dbi->freeResult($serverVarsResult);

            $serverVars = $this->dbi->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);

            // list of static (i.e. non-editable) system variables
            $staticVariables = ServerVariablesProvider::getImplementation()->getStaticVariables();

            foreach ($serverVars as $name => $value) {
                $hasSessionValue = isset($serverVarsSession[$name])
                    && $serverVarsSession[$name] !== $value;
                $docLink = Generator::linkToVarDocumentation(
                    $name,
                    $this->dbi->isMariaDB(),
                    str_replace('_', '&nbsp;', $name)
                );

                [$formattedValue, $isEscaped] = $this->formatVariable($name, $value);
                if ($hasSessionValue) {
                    [$sessionFormattedValue] = $this->formatVariable(
                        $name,
                        $serverVarsSession[$name]
                    );
                }

                $variables[] = [
                    'name' => $name,
                    'is_editable' => ! in_array(strtolower($name), $staticVariables),
                    'doc_link' => $docLink,
                    'value' => $formattedValue,
                    'is_escaped' => $isEscaped,
                    'has_session_value' => $hasSessionValue,
                    'session_value' => $sessionFormattedValue ?? null,
                ];
            }
        }

        $this->render('server/variables/index', [
            'variables' => $variables,
            'filter_value' => $filterValue,
            'is_superuser' => $this->dbi->isSuperUser(),
            'is_mariadb' => $this->dbi->isMariaDB(),
        ]);
    }

    /**
     * Handle the AJAX request for a single variable value
     *
     * @param array $params Request parameters
     */
    public function getValue(array $params): void
    {
        if (! $this->response->isAjax()) {
            return;
        }

        // Send with correct charset
        header('Content-Type: text/html; charset=UTF-8');
        // Do not use double quotes inside the query to avoid a problem
        // when server is running in ANSI_QUOTES sql_mode
        $varValue = $this->dbi->fetchSingleRow(
            'SHOW GLOBAL VARIABLES WHERE Variable_name=\''
            . $this->dbi->escapeString($params['name']) . '\';',
            'NUM'
        );

        $json = [
            'message' => $varValue[1],
        ];

        $variableType = ServerVariablesProvider::getImplementation()->getVariableType($params['name']);

        if ($variableType === 'byte') {
            $json['message'] = implode(
                ' ',
                Util::formatByteDown($varValue[1], 3, 3)
            );
        }

        $this->response->addJSON($json);
    }

    /**
     * Handle the AJAX request for setting value for a single variable
     *
     * @param array $vars Request parameters
     */
    public function setValue(array $vars): void
    {
        $params = [
            'varName' => $vars['name'],
            'varValue' => $_POST['varValue'] ?? null,
        ];

        if (! $this->response->isAjax()) {
            return;
        }

        $value = (string) $params['varValue'];
        $variableName = (string) $params['varName'];
        $matches = [];
        $variableType = ServerVariablesProvider::getImplementation()->getVariableType($variableName);

        if ($variableType === 'byte' && preg_match(
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
            $value = (float) $matches[1] * pow(
                1024,
                $exp[mb_strtolower($matches[3])]
            );
        } else {
            $value = $this->dbi->escapeString($value);
        }

        if (! is_numeric($value)) {
            $value = "'" . $value . "'";
        }

        $json = [];
        if (! preg_match('/[^a-zA-Z0-9_]+/', $params['varName'])
            && $this->dbi->query(
                'SET GLOBAL ' . $params['varName'] . ' = ' . $value
            )
        ) {
            // Some values are rounded down etc.
            $varValue = $this->dbi->fetchSingleRow(
                'SHOW GLOBAL VARIABLES WHERE Variable_name="'
                . $this->dbi->escapeString($params['varName'])
                . '";',
                'NUM'
            );
            [$formattedValue, $isHtmlFormatted] = $this->formatVariable(
                $params['varName'],
                $varValue[1]
            );

            if ($isHtmlFormatted === false) {
                $json['variable'] = htmlspecialchars($formattedValue);
            } else {
                $json['variable'] = $formattedValue;
            }
        } else {
            $this->response->setRequestStatus(false);
            $json['error'] = __('Setting variable failed');
        }

        $this->response->addJSON($json);
    }

    /**
     * Format Variable
     *
     * @param string     $name  variable name
     * @param int|string $value variable value
     *
     * @return array formatted string and bool if string is HTML formatted
     */
    private function formatVariable($name, $value): array
    {
        $isHtmlFormatted = false;
        $formattedValue = $value;

        if (is_numeric($value)) {
            $variableType = ServerVariablesProvider::getImplementation()->getVariableType($name);

            if ($variableType === 'byte') {
                $isHtmlFormatted = true;
                $formattedValue = trim(
                    $this->template->render(
                        'server/variables/format_variable',
                        [
                            'valueTitle' => Util::formatNumber($value, 0),
                            'value' => implode(' ', Util::formatByteDown($value, 3, 3)),
                        ]
                    )
                );
            } else {
                $formattedValue = Util::formatNumber($value, 0);
            }
        }

        return [
            $formattedValue,
            $isHtmlFormatted,
        ];
    }
}
