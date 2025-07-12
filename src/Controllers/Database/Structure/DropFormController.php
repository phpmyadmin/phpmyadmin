<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function htmlspecialchars;

#[Route('/database/structure/drop-form', ['POST'])]
final class DropFormController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly DatabaseInterface $dbi)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if ($selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return $this->response->response();
        }

        $fullQueryViews = '';
        $fullQuery = '';

        foreach ($selected as $selectedValue) {
            if ($this->dbi->getTable(Current::$database, $selectedValue)->isView()) {
                $fullQueryViews .= ($fullQueryViews === '' ? 'DROP VIEW ' : ', ')
                    . Util::backquote(htmlspecialchars($selectedValue));
            } else {
                $fullQuery .= ($fullQuery === '' ? 'DROP TABLE ' : ', ')
                    . Util::backquote(htmlspecialchars($selectedValue));
            }
        }

        if ($fullQuery !== '') {
            $fullQuery .= ';<br>' . "\n";
        }

        if ($fullQueryViews !== '') {
            $fullQuery .= $fullQueryViews . ';<br>' . "\n";
        }

        $urlParams = ['db' => Current::$database];
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }

        $this->response->render('database/structure/drop_form', [
            'url_params' => $urlParams,
            'full_query' => $fullQuery,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);

        return $this->response->response();
    }
}
