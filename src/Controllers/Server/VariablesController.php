<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\VariableFormatter;

use function in_array;
use function str_replace;
use function strtolower;

/**
 * Handles viewing and editing server variables
 */
#[Route('/server/variables', ['GET'])]
final readonly class VariablesController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private VariableFormatter $variableFormatter,
        private DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $filterValue = $request->getQueryParam('filter', '');

        $this->response->addScriptFiles(['server/variables.js']);

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

                [$formattedValue, $isEscaped] = $this->variableFormatter->format($name, $value);
                if ($hasSessionValue) {
                    [$sessionFormattedValue] = $this->variableFormatter->format($name, $serverVarsSession[$name]);
                }

                $variables[] = [
                    'name' => $name,
                    'is_editable' => ! in_array(strtolower($name), $staticVariables, true),
                    'doc_link' => $docLink,
                    'value' => $formattedValue,
                    'is_escaped' => $isEscaped,
                    'has_session_value' => $hasSessionValue,
                    'session_value' => $sessionFormattedValue ?? null,
                ];
            }
        }

        $this->response->render('server/variables/index', [
            'variables' => $variables,
            'filter_value' => $filterValue,
            'is_superuser' => $this->dbi->isSuperUser(),
            'is_mariadb' => $this->dbi->isMariaDB(),
        ]);

        return $this->response->response();
    }
}
