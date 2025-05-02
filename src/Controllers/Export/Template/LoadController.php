<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;

final readonly class LoadController implements InvocableController
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

        $template = $this->model->load(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $this->config->selectedServer['user'],
            $templateId,
        );

        if (! $template instanceof ExportTemplate) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $template);

            return $this->response->response();
        }

        $this->response->setRequestStatus(true);
        $this->response->addJSON('data', $template->getData());

        return $this->response->response();
    }
}
