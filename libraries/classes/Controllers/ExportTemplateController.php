<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Export\Template as ExportTemplate;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
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
     * @param Response $response
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

    public function create(): void
    {
        global $cfg;

        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $template = ExportTemplate::fromArray([
            'username' => $cfg['Server']['user'],
            'exportType' => $_POST['exportType'] ?? '',
            'name' => $_POST['templateName'] ?? '',
            'data' => $_POST['templateData'] ?? '',
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
                'selected_template' => $_POST['template_id'] ?? null,
            ])
        );
    }

    public function delete(): void
    {
        global $cfg;

        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $result = $this->model->delete(
            $cfgRelation['db'],
            $cfgRelation['export_templates'],
            $cfg['Server']['user'],
            (int) $_POST['templateId']
        );

        if (is_string($result)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $result);

            return;
        }

        $this->response->setRequestStatus(true);
    }

    public function load(): void
    {
        global $cfg;

        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $template = $this->model->load(
            $cfgRelation['db'],
            $cfgRelation['export_templates'],
            $cfg['Server']['user'],
            (int) $_POST['templateId']
        );

        if (! $template instanceof ExportTemplate) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $template);

            return;
        }

        $this->response->setRequestStatus(true);
        $this->response->addJSON('data', $template->getData());
    }

    public function update(): void
    {
        global $cfg;

        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $template = ExportTemplate::fromArray([
            'id' => (int) $_POST['templateId'],
            'username' => $cfg['Server']['user'],
            'data' => $_POST['templateData'],
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
