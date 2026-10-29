<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Variables;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\VariableFormatter;

use function __;
use function htmlspecialchars;
use function is_array;
use function is_string;
use function mb_strtolower;
use function preg_match;

#[Route('/server/variables/set/{name}', ['POST'])]
final readonly class SetVariableController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private VariableFormatter $variableFormatter,
        private DatabaseInterface $dbi,
    ) {
    }

    /**
     * Handle the AJAX request for setting value for a single variable
     */
    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $value = $request->getParsedBodyParamAsString('varValue', '');
        $variableName = $this->getName($request->getAttribute('routeVars'));
        $matches = [];
        $variableType = ServerVariablesProvider::getImplementation()->getVariableType($variableName);

        if (
            $variableType === 'byte' && preg_match(
                '/^\s*(\d+(\.\d+)?)\s*(mb|kb|mib|kib|gb|gib)\s*$/i',
                $value,
                $matches,
            ) === 1
        ) {
            $exp = ['kb' => 1, 'kib' => 1, 'mb' => 2, 'mib' => 2, 'gb' => 3, 'gib' => 3];
            $value = (float) $matches[1] * 1024 ** $exp[mb_strtolower($matches[3])];
        } elseif ($variableType === 'integer') {
            $value = (int) $value;
        } else {
            $value = $this->dbi->quoteString($value);
        }

        $json = [];
        if (preg_match('/[^a-zA-Z0-9_]+/', $variableName) !== 1) {
            $this->dbi->query('SET GLOBAL ' . $variableName . ' = ' . $value);
            // Some values are rounded down etc.
            $varValue = $this->dbi->fetchSingleRow(
                'SHOW GLOBAL VARIABLES WHERE Variable_name='
                    . $this->dbi->quoteString($variableName)
                    . ';',
                DatabaseInterface::FETCH_NUM,
            );
            [$formattedValue, $isHtmlFormatted] = $this->variableFormatter->format($variableName, $varValue[1] ?? '');

            $json['variable'] = $isHtmlFormatted === false ? htmlspecialchars($formattedValue) : $formattedValue;
        } else {
            $this->response->setRequestStatus(false);
            $json['error'] = __('Setting variable failed');
        }

        $this->response->addJSON($json);

        return $this->response->response();
    }

    private function getName(mixed $routeVars): string
    {
        if (is_array($routeVars) && isset($routeVars['name']) && is_string($routeVars['name'])) {
            return $routeVars['name'];
        }

        return '';
    }
}
