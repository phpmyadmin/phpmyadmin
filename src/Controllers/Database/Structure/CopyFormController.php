<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;

use function __;

final class CopyFormController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        /** @var string[] $selected */
        $selected = $request->getParsedBodyParam('selected_tbl', []);

        if ($selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $urlParams = ['db' => Current::$database];
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }

        $databasesList = DatabaseInterface::getInstance()->getDatabaseList();
        foreach ($databasesList as $key => $databaseName) {
            if ($databaseName == Current::$database) {
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
