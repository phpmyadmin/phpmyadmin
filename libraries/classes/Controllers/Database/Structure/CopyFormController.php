<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;

use function __;

final class CopyFormController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $urlParams = ['db' => $GLOBALS['db']];
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }

        $databasesList = $GLOBALS['dbi']->getDatabaseList();
        foreach ($databasesList as $key => $databaseName) {
            if ($databaseName == $GLOBALS['db']) {
                $databasesList->offsetUnset($key);
                break;
            }
        }

        $this->response->disable();
        $this->render('database/structure/copy_form', [
            'url_params' => $urlParams,
            'options' => $databasesList->getList(),
        ]);
    }
}
