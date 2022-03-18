<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;

final class DropColumnConfirmationController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        try {
            $db = DatabaseName::fromValue($request->getParsedBodyParam('db'));
            $table = TableName::fromValue($request->getParsedBodyParam('table'));
        } catch (InvalidArgumentException $exception) {
            $this->response->setHttpResponseCode(400);
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('Table not found.'));

            return;
        }

        $fields = $request->getParsedBodyParam('selected_fld');
        try {
            Assert::allStringNotEmpty($fields);
        } catch (InvalidArgumentException $exception) {
            $this->response->setHttpResponseCode(400);
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        DbTableExists::check($db->getName(), $table->getName());

        $this->render('table/structure/drop_confirm', [
            'db' => $db->getName(),
            'table' => $table->getName(),
            'fields' => $fields,
        ]);
    }
}
