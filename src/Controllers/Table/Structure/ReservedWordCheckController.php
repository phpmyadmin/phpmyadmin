<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Context;

use function _ngettext;
use function count;
use function implode;
use function sprintf;
use function trim;

final readonly class ReservedWordCheckController implements InvocableController
{
    public function __construct(private ResponseRenderer $response, private Config $config)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->config->settings['ReservedWordDisableWarning'] !== false) {
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $columnsNames = $request->getParsedBodyParam('field_name');
        $reservedKeywordsNames = [];
        foreach ($columnsNames as $column) {
            if (! Context::isKeyword(trim($column), true)) {
                continue;
            }

            $reservedKeywordsNames[] = trim($column);
        }

        if (Context::isKeyword(trim(Current::$table), true)) {
            $reservedKeywordsNames[] = trim(Current::$table);
        }

        if ($reservedKeywordsNames === []) {
            $this->response->setRequestStatus(false);
        }

        $this->response->addJSON(
            'message',
            sprintf(
                _ngettext(
                    'The name \'%s\' is a MySQL reserved keyword.',
                    'The names \'%s\' are MySQL reserved keywords.',
                    count($reservedKeywordsNames),
                ),
                implode(',', $reservedKeywordsNames),
            ),
        );

        return $this->response->response();
    }
}
