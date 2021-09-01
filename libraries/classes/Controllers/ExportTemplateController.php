<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Relation;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function is_array;
use function is_string;

final class ExportTemplateController extends AbstractController
{
    /** @var TemplateModel */
    private $model;

    /** @var Relation */
    private $relation;

    /**
     * @param ResponseRenderer $response
     */
    public function __construct(
        $response,
        Template $template,
        TemplateModel $model,
        Relation $relation
    ) {
        parent::__construct($response, $template);
        $this->model = $model;
        $this->relation = $relation;
    }

    public function create(ServerRequest $request): void
    {
        global $cfg;

        /** @var string $exportType */
        $exportType = $request->getParsedBodyParam('exportType', '');
        /** @var string $templateName */
        $templateName = $request->getParsedBodyParam('templateName', '');
        /** @var string $templateData */
        $templateData = $request->getParsedBodyParam('templateData', '');
        /** @var string|null $templateId */
        $templateId = $request->getParsedBodyParam('template_id');

        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $template = ExportTemplate::fromArray([
            'username' => $cfg['Server']['user'],
            'exportType' => $exportType,
            'name' => $templateName,
            'data' => $templateData,
        ]);
        $result = $this->model->create($cfgRelation['db'], $cfgRelation['export_templates'], $template);

        if (is_string($result)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $templates = $this->model->getAll(
            $cfgRelation['db'],
            $cfgRelation['export_templates'],
            $template->getUsername(),
            $template->getExportType()
        );

        $this->response->setRequestStatus(true);
        $this->response->addJSON(
            'data',
            $this->template->render('export/template_options', [
                'templates' => is_array($templates) ? $templates : [],
                'selected_template' => $templateId,
            ])
        );
    }

    public function delete(ServerRequest $request): void
    {
        global $cfg;

        $templateId = (int) $request->getParsedBodyParam('templateId');
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $result = $this->model->delete(
            $cfgRelation['db'],
            $cfgRelation['export_templates'],
            $cfg['Server']['user'],
            $templateId
        );

        if (is_string($result)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $this->response->setRequestStatus(true);
    }

    public function load(ServerRequest $request): void
    {
        global $cfg;

        $templateId = (int) $request->getParsedBodyParam('templateId');
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $template = $this->model->load(
            $cfgRelation['db'],
            $cfgRelation['export_templates'],
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

    public function update(ServerRequest $request): void
    {
        global $cfg;

        $templateId = (int) $request->getParsedBodyParam('templateId');
        /** @var string $templateData */
        $templateData = $request->getParsedBodyParam('templateData', '');
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $template = ExportTemplate::fromArray([
            'id' => $templateId,
            'username' => $cfg['Server']['user'],
            'data' => $templateData,
        ]);
        $result = $this->model->update($cfgRelation['db'], $cfgRelation['export_templates'], $template);

        if (is_string($result)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $this->response->setRequestStatus(true);
    }
}
