<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;

final readonly class DeleteController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private TemplateModel $model,
        private Relation $relation,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $templateId = (int) $request->getParsedBodyParamAsStringOrNull('templateId');

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;
        if ($exportTemplatesFeature === null) {
            return $this->response->response();
        }

        $result = $this->model->delete(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $this->config->selectedServer['user'],
            $templateId,
        );

        if ($result !== '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return $this->response->response();
        }

        $this->response->setRequestStatus(true);

        return $this->response->response();
    }
}
