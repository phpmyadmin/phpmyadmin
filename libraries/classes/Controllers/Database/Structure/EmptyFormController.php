<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function htmlspecialchars;

final class EmptyFormController extends AbstractController
{
    public function __invoke(): void
    {
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $fullQuery = '';
        $urlParams = ['db' => $GLOBALS['db']];

        foreach ($selected as $selectedValue) {
            $fullQuery .= 'TRUNCATE ';
            $fullQuery .= Util::backquote(htmlspecialchars($selectedValue)) . ';<br>';
            $urlParams['selected'][] = $selectedValue;
        }

        $this->render('database/structure/empty_form', [
            'url_params' => $urlParams,
            'full_query' => $fullQuery,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);
    }
}
