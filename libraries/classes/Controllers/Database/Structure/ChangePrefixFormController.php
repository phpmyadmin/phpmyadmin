<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;

use function __;

final class ChangePrefixFormController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $route = '/database/structure/replace-prefix';
        if ($request->getParsedBodyParam('submit_mult', '') === 'copy_tbl_change_prefix') {
            $route = '/database/structure/copy-table-with-prefix';
        }

        $urlParams = ['db' => $GLOBALS['db']];
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }

        $this->response->disable();
        $this->render('database/structure/change_prefix_form', ['route' => $route, 'url_params' => $urlParams]);
    }
}
