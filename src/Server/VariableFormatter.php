<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function implode;
use function is_numeric;
use function trim;

/**
 * Formats server variable values for display.
 */
final readonly class VariableFormatter
{
    public function __construct(private Template $template)
    {
    }

    /**
     * @param string     $name  variable name
     * @param int|string $value variable value
     *
     * @return array{int|string, bool} formatted string and bool if string is HTML formatted
     */
    public function format(string $name, int|string $value): array
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
