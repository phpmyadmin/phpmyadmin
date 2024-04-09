<?php
/**
 * Represents the interface between the linter and the query editor.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Linter;
use PhpMyAdmin\ResponseRenderer;

use function header;
use function is_array;
use function json_encode;
use function sprintf;

/**
 * Represents the interface between the linter and the query editor.
 */
final class LintController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        if (! $request->isAjax()) {
            return null;
        }

        /**
         * The SQL query to be analyzed.
         *
         * This does not need to be checked again XSS or MySQL injections because it is
         * never executed, just parsed.
         *
         * The client, which will receive the JSON response will decode the message and
         * and any HTML fragments that are displayed to the user will be encoded anyway.
         *
         * @var string $sqlQuery
         */
        $sqlQuery = $request->getParsedBodyParam('sql_query', '');

        // Disabling standard response.
        $this->response->disable();

        foreach (Core::headerJSON() as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        $options = $request->getParsedBodyParam('options');
        if (is_array($options)) {
            if (! empty($options['routineEditor'])) {
                $sqlQuery = 'CREATE PROCEDURE `a`() ' . $sqlQuery;
            } elseif (! empty($options['triggerEditor'])) {
                $sqlQuery = 'CREATE TRIGGER `a` AFTER INSERT ON `b` FOR EACH ROW ' . $sqlQuery;
            } elseif (! empty($options['eventEditor'])) {
                $sqlQuery = 'CREATE EVENT `a` ON SCHEDULE EVERY MINUTE DO ' . $sqlQuery;
            }
        }

        echo json_encode(Linter::lint("DELIMITER $$\n" . $sqlQuery . "$$\nDELIMITER ;\n"));

        return null;
    }
}
