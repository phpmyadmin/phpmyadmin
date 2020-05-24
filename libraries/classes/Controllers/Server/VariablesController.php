<?php
/**
 * Holds the PhpMyAdmin\Controllers\Server\VariablesController
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Util;
use Williamdes\MariaDBMySQLKBS\KBException;
use Williamdes\MariaDBMySQLKBS\Search as KBSearch;
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
    public function index(): void
    {
        $params = ['filter' => $_GET['filter'] ?? null];

        Common::server();

        $filterValue = ! empty($params['filter']) ? $params['filter'] : '';

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('server/variables.js');

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
            $staticVariables = KBSearch::getStaticVariables();

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
                    [
                        $sessionFormattedValue,
                    ] = $this->formatVariable(
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
            'is_superuser' => $this->dbi->isSuperuser(),
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

        $json = [];
        try {
            $type = KBSearch::getVariableType($params['name']);
            if ($type === 'byte') {
                $json['message'] = implode(
                    ' ',
                    Util::formatByteDown($varValue[1], 3, 3)
                );
            } else {
                throw new KBException('Not a type=byte');
            }
        } catch (KBException $e) {
            $json['message'] = $varValue[1];
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

        $value = $params['varValue'];
        $matches = [];
        try {
            $type = KBSearch::getVariableType($params['varName']);
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
                $value = (float) $matches[1] * pow(
                    1024,
                    $exp[mb_strtolower($matches[3])]
                );
            } else {
                throw new KBException('Not a type=byte or regex not matching');
            }
        } catch (KBException $e) {
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
            try {
                $type = KBSearch::getVariableType($name);
                if ($type === 'byte') {
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
                    throw new KBException('Not a type=byte or regex not matching');
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
}
