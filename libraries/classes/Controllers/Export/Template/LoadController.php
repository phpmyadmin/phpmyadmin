<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export\Template;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Relation;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class LoadController extends AbstractController
{
    /** @var TemplateModel */
    private $model;

    /** @var Relation */
    private $relation;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        TemplateModel $model,
        Relation $relation
    ) {
        parent::__construct($response, $template);
        $this->model = $model;
        $this->relation = $relation;
    }

    public function __invoke(ServerRequest $request): void
    {
        global $cfg;

        $templateId = (int) $request->getParsedBodyParam('templateId');
        $relationParameters = $this->relation->getRelationParameters();

        if (
            ! $relationParameters->hasExportTemplatesFeature()
            || $relationParameters->db === null
            || $relationParameters->exportTemplates === null
        ) {
            return;
        }

        $template = $this->model->load(
            $relationParameters->db,
            $relationParameters->exportTemplates,
            $cfg['Server']['user'],
            $templateId
        );

        if (! $template instanceof ExportTemplate) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $template);

            return;
        }

        $this->response->setRequestStatus(true);
        $this->response->addJSON('data', $template->getData());
    }
}
