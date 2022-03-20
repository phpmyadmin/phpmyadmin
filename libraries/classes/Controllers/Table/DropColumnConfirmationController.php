<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\InvalidIdentifierName;
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
        $fields = $request->getParsedBodyParam('selected_fld');
        try {
            $db = DatabaseName::fromValue($request->getParsedBodyParam('db'));
            $table = TableName::fromValue($request->getParsedBodyParam('table'));
            Assert::allStringNotEmpty($fields);
        } catch (InvalidIdentifierName $exception) {
            $this->sendErrorResponse($exception->getMessage());

            return;
        } catch (InvalidArgumentException $exception) {
            $this->sendErrorResponse(__('No column selected.'));

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
