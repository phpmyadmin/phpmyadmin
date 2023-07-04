<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

use function __;

final class DropColumnConfirmationController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $fields = $request->getParsedBodyParam('selected_fld');
        try {
            $db = DatabaseName::from($request->getParsedBodyParam('db'));
            $table = TableName::from($request->getParsedBodyParam('table'));
            Assert::allStringNotEmpty($fields);
        } catch (InvalidIdentifier $exception) {
            $this->sendErrorResponse($exception->getMessage());

            return;
        } catch (InvalidArgumentException) {
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
