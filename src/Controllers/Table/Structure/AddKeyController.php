<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;
use function in_array;

#[Route('/table/structure/add-key', ['POST'])]
final class AddKeyController extends AbstractIndexController implements InvocableController
{
    public function __invoke(ServerRequest $request): Response
    {
        ResponseRenderer::$reload = true;

        $keyType = $this->getKeyType($request->getParsedBodyParamAsStringOrNull('key_type'));
        if ($keyType === '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('Invalid request parameter.'));

            return $this->response->response();
        }

        return $this->handleIndexCreation($request, $keyType);
    }

    /** @psalm-return  'FULLTEXT'|'INDEX'|'PRIMARY'|'SPATIAL'|'UNIQUE'|'' */
    private function getKeyType(string|null $keyType): string
    {
        return in_array($keyType, ['FULLTEXT', 'INDEX', 'PRIMARY', 'SPATIAL', 'UNIQUE'], true) ? $keyType : '';
    }
}
