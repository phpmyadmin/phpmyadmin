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
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;

use function is_array;

#[Route('/export/template/create', ['POST'])]
final readonly class CreateController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Template $template,
        private TemplateModel $model,
        private Relation $relation,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $exportType = $request->getParsedBodyParamAsString('exportType', '');
        $templateName = $request->getParsedBodyParamAsString('templateName', '');
        $templateData = $request->getParsedBodyParamAsString('templateData', '');
        $templateId = $request->getParsedBodyParamAsStringOrNull('template_id');

        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;
        if ($exportTemplatesFeature === null) {
            return $this->response->response();
        }

        $template = ExportTemplate::fromArray([
            'username' => $this->config->selectedServer['user'],
            'exportType' => $exportType,
            'name' => $templateName,
            'data' => $templateData,
        ]);
        $result = $this->model->create(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $template,
        );

        if ($result !== '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return $this->response->response();
        }

        $templates = $this->model->getAll(
            $exportTemplatesFeature->database,
            $exportTemplatesFeature->exportTemplates,
            $template->getUsername(),
            $template->getExportType(),
        );

        $this->response->setRequestStatus(true);
        $this->response->addJSON(
            'data',
            $this->template->render('export/template_options', [
                'templates' => is_array($templates) ? $templates : [],
                'selected_template' => $templateId,
            ]),
        );

        return $this->response->response();
    }
}
