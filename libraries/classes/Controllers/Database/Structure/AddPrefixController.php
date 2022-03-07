<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\AbstractController;

use function __;

final class AddPrefixController extends AbstractController
{
    public function __invoke(): void
    {
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $params = ['db' => $GLOBALS['db']];
        foreach ($selected as $selectedValue) {
            $params['selected'][] = $selectedValue;
        }

        $this->response->disable();
        $this->render('database/structure/add_prefix', ['url_params' => $params]);
    }
}
