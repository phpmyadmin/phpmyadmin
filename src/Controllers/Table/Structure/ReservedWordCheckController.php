<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\SqlParser\Context;

use function _ngettext;
use function count;
use function implode;
use function sprintf;
use function trim;

final class ReservedWordCheckController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        if (Config::getInstance()->settings['ReservedWordDisableWarning'] !== false) {
            $this->response->setRequestStatus(false);

            return;
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
    }
}
