<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\Export;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

final class ExportTemplateController extends AbstractController
{
    /** @var Export */
    private $export;

    /** @var Relation */
    private $relation;

    /**
     * @param Response          $response
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, $dbi, Template $template, Export $export, Relation $relation)
    {
        parent::__construct($response, $dbi, $template);
        $this->export = $export;
        $this->relation = $relation;
    }

    public function create(): void
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $templateTable = Util::backquote($cfgRelation['db']) . '.'
            . Util::backquote($cfgRelation['export_templates']);
        $user = $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']);

        $query = 'INSERT INTO ' . $templateTable . '('
            . ' `username`, `export_type`,'
            . ' `template_name`, `template_data`'
            . ') VALUES ('
            . "'" . $user . "', "
            . "'" . $this->dbi->escapeString($_POST['exportType'])
            . "', '" . $this->dbi->escapeString($_POST['templateName'])
            . "', '" . $this->dbi->escapeString($_POST['templateData'])
            . "');";

        $result = $this->relation->queryAsControlUser($query, false);

        if (! $result) {
            $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);

            return;
        }

        $this->response->setRequestStatus(true);

        $this->response->addJSON(
            'data',
            $this->export->getOptionsForTemplates($_POST['exportType'])
        );

        $this->dbi->freeResult($result);
    }

    public function delete(): void
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $id = '';
        if (isset($_POST['templateId'])) {
            $id = $this->dbi->escapeString($_POST['templateId']);
        }

        $templateTable = Util::backquote($cfgRelation['db']) . '.'
            . Util::backquote($cfgRelation['export_templates']);
        $user = $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']);

        $query = 'DELETE FROM ' . $templateTable
            . ' WHERE `id` = ' . $id . " AND `username` = '" . $user . "'";

        $result = $this->relation->queryAsControlUser($query, false);

        if (! $result) {
            $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);

            return;
        }

        $this->response->setRequestStatus(true);

        $this->dbi->freeResult($result);
    }

    public function load(): void
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $id = '';
        if (isset($_POST['templateId'])) {
            $id = $this->dbi->escapeString($_POST['templateId']);
        }

        $templateTable = Util::backquote($cfgRelation['db']) . '.'
            . Util::backquote($cfgRelation['export_templates']);
        $user = $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']);

        $query = 'SELECT `template_data` FROM ' . $templateTable
            . ' WHERE `id` = ' . $id . " AND `username` = '" . $user . "'";

        $result = $this->relation->queryAsControlUser($query, false);

        if (! $result) {
            $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);

            return;
        }

        $this->response->setRequestStatus(true);

        $data = null;
        while ($row = $this->dbi->fetchAssoc($result, DatabaseInterface::CONNECT_CONTROL)) {
            $data = $row['template_data'];
        }
        $this->response->addJSON('data', $data);

        $this->dbi->freeResult($result);
    }

    public function update(): void
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['exporttemplateswork']) {
            return;
        }

        $id = '';
        if (isset($_POST['templateId'])) {
            $id = $this->dbi->escapeString($_POST['templateId']);
        }

        $templateTable = Util::backquote($cfgRelation['db']) . '.'
            . Util::backquote($cfgRelation['export_templates']);
        $user = $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']);

        $query = 'UPDATE ' . $templateTable . ' SET `template_data` = '
            . "'" . $this->dbi->escapeString($_POST['templateData']) . "'"
            . ' WHERE `id` = ' . $id . " AND `username` = '" . $user . "'";

        $result = $this->relation->queryAsControlUser($query, false);

        if (! $result) {
            $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);

            return;
        }

        $this->response->setRequestStatus(true);

        $this->dbi->freeResult($result);
    }
}
