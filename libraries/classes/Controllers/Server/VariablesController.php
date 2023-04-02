<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function implode;
use function in_array;
use function is_numeric;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Handles viewing and editing server variables
 */
class VariablesController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $filterValue = $request->getQueryParam('filter', '');

        $this->addScriptFiles(['server/variables.js']);

        $variables = [];
        $serverVarsResult = $this->dbi->tryQuery('SHOW SESSION VARIABLES;');
        if ($serverVarsResult !== false) {
            /** @var array<string, string> $serverVarsSession */
            $serverVarsSession = $serverVarsResult->fetchAllKeyPair();

            unset($serverVarsResult);

            /** @var array<string, string> $serverVars */
            $serverVars = $this->dbi->fetchResult('SHOW GLOBAL VARIABLES;', 0, 1);

            // list of static (i.e. non-editable) system variables
            $staticVariables = ServerVariablesProvider::getImplementation()->getStaticVariables();

            foreach ($serverVars as $name => $value) {
                $hasSessionValue = isset($serverVarsSession[$name])
                    && $serverVarsSession[$name] !== $value;
                $docLink = Generator::linkToVarDocumentation(
                    $name,
                    $this->dbi->isMariaDB(),
                    str_replace('_', '&nbsp;', $name),
                );

                [$formattedValue, $isEscaped] = $this->formatVariable($name, $value);
                if ($hasSessionValue) {
                    [$sessionFormattedValue] = $this->formatVariable($name, $serverVarsSession[$name]);
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
     * Format Variable
     *
     * @param string     $name  variable name
     * @param int|string $value variable value
     *
     * @return mixed[] formatted string and bool if string is HTML formatted
     */
    private function formatVariable(string $name, int|string $value): array
    {
        $isHtmlFormatted = false;
        $formattedValue = $value;

        if (is_numeric($value)) {
            $variableType = ServerVariablesProvider::getImplementation()->getVariableType($name);

            if ($variableType === 'byte') {
                $isHtmlFormatted = true;
                /** @var string[] $bytes */
                $bytes = Util::formatByteDown($value, 3, 3);
                $formattedValue = trim(
                    $this->template->render(
                        'server/variables/format_variable',
                        ['valueTitle' => Util::formatNumber($value, 0), 'value' => implode(' ', $bytes)],
                    ),
                );
            } else {
                $formattedValue = Util::formatNumber($value, 0);
            }
        }

        return [$formattedValue, $isHtmlFormatted];
    }
}
