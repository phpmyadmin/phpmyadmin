<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\SqlParser\Context;

use function __;
use function _ngettext;
use function count;
use function implode;
use function is_array;
use function sprintf;
use function trim;

final class ReservedWordCheckController extends AbstractController
{
    public function __invoke(): void
    {
        if ($GLOBALS['cfg']['ReservedWordDisableWarning'] !== false) {
            $this->response->setRequestStatus(false);

            return;
        }

        if (! isset($_POST['field_name']) || ! is_array($_POST['field_name'])) {
            $this->response->setRequestStatus(false);
            $this->response->setHttpResponseCode(400);
            $this->response->addJSON('message', __('Missing parameter:') . ' field_name');

            return;
        }

        $columns_names = $_POST['field_name'];
        $reserved_keywords_names = [];
        foreach ($columns_names as $column) {
            if (! Context::isKeyword(trim($column), true)) {
                continue;
            }

            $reserved_keywords_names[] = trim($column);
        }

        if (Context::isKeyword(trim($this->table), true)) {
            $reserved_keywords_names[] = trim($this->table);
        }

        if (count($reserved_keywords_names) === 0) {
            $this->response->setRequestStatus(false);
        }

        $this->response->addJSON(
            'message',
            sprintf(
                _ngettext(
                    'The name \'%s\' is a MySQL reserved keyword.',
                    'The names \'%s\' are MySQL reserved keywords.',
                    count($reserved_keywords_names)
                ),
                implode(',', $reserved_keywords_names)
            )
        );
    }
}
