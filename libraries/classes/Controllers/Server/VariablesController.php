<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\VariablesController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Util;
use Williamdes\MariaDBMySQLKBS\KBException;
use Williamdes\MariaDBMySQLKBS\Search as KBSearch;

/**
 * Handles viewing and editing server variables
 *
 * @package PhpMyAdmin\Controllers
 */
class VariablesController extends AbstractController
{
    /**
     * Index action
     *
     * @param array $params Request parameters
     *
     * @return string
     */
    public function index(array $params): string
    {
        include ROOT_PATH . 'libraries/server_common.inc.php';

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
                $docLink = Util::linkToVarDocumentation(
                    $name,
                    $this->dbi->isMariaDB(),
                    str_replace('_', '&nbsp;', $name)
                );

                list($formattedValue, $isEscaped) = $this->formatVariable($name, $value);
                if ($hasSessionValue) {
                    list($sessionFormattedValue, ) = $this->formatVariable(
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

        return $this->template->render('server/variables/index', [
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
     *
     * @return array
     */
    public function getValue(array $params): array
    {
        // Send with correct charset
        header('Content-Type: text/html; charset=UTF-8');
        // Do not use double quotes inside the query to avoid a problem
        // when server is running in ANSI_QUOTES sql_mode
        $varValue = $this->dbi->fetchSingleRow(
            'SHOW GLOBAL VARIABLES WHERE Variable_name=\''
            . $this->dbi->escapeString($params['varName']) . '\';',
            'NUM'
        );

        $json = [];
        try {
            $type = KBSearch::getVariableType($params['varName']);
            if ($type === 'byte') {
                $json['message'] = implode(
                    ' ',
                    Util::formatByteDown($varValue[1], 3, 3)
                );
            } else {
                throw new KBException("Not a type=byte");
            }
        } catch (KBException $e) {
            $json['message'] = $varValue[1];
        }

        return $json;
    }

    /**
     * Handle the AJAX request for setting value for a single variable
     *
     * @param array $params Request parameters
     *
     * @return array
     */
    public function setValue(array $params): array
    {
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

        $json = [];
        if (! preg_match("/[^a-zA-Z0-9_]+/", $params['varName'])
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
            list($formattedValue, $isHtmlFormatted) = $this->formatVariable(
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

        return $json;
    }

    /**
     * Format Variable
     *
     * @param string  $name  variable name
     * @param integer $value variable value
     *
     * @return array formatted string and bool if string is HTML formatted
     */
    private function formatVariable($name, $value)
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
}
