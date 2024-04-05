<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function htmlspecialchars;

final class EmptyFormController extends AbstractController
{
    public function __invoke(ServerRequest $request): Response|null
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if ($selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return null;
        }

        $fullQuery = '';
        $urlParams = ['db' => Current::$database];

        foreach ($selected as $selectedValue) {
            $fullQuery .= 'TRUNCATE ';
            $fullQuery .= Util::backquote(htmlspecialchars($selectedValue)) . ';<br>';
            $urlParams['selected'][] = $selectedValue;
        }

        $this->response->render('database/structure/empty_form', [
            'url_params' => $urlParams,
            'full_query' => $fullQuery,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);

        return null;
    }
}
