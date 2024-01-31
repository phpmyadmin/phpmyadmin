<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function htmlspecialchars;

final class DropFormController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if ($selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
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

        $this->render('database/structure/drop_form', [
            'url_params' => $urlParams,
            'full_query' => $fullQuery,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);
    }
}
