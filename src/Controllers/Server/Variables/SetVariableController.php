<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Variables;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function mb_strtolower;
use function preg_match;
use function trim;

final class SetVariableController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly DatabaseInterface $dbi,
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
        } elseif (
            $variableType === 'integer'
        ) {
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
            [$formattedValue, $isHtmlFormatted] = $this->formatVariable($variableName, $varValue[1]);

            $json['variable'] = $isHtmlFormatted === false ? htmlspecialchars($formattedValue) : $formattedValue;
        } else {
            $this->response->setRequestStatus(false);
            $json['error'] = __('Setting variable failed');
        }

        $this->response->addJSON($json);

        return $this->response->response();
    }

    /**
     * Format Variable
     *
     * @param string     $name  variable name
     * @param int|string $value variable value
     *
     * @return array{int|string, bool} formatted string and bool if string is HTML formatted
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

    private function getName(mixed $routeVars): string
    {
        if (is_array($routeVars) && isset($routeVars['name']) && is_string($routeVars['name'])) {
            return $routeVars['name'];
        }

        return '';
    }
}
