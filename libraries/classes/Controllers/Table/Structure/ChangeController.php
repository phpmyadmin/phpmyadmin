<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;

use function __;
use function count;
use function is_array;

final class ChangeController extends AbstractController
{
    /** @var Relation */
    private $relation;

    /** @var Transformations */
    private $transformations;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        Relation $relation,
        Transformations $transformations,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->relation = $relation;
        $this->transformations = $transformations;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        if (isset($_GET['change_column'])) {
            $this->displayHtmlForColumnChange(null);

            return;
        }

        $selected = $_POST['selected_fld'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $this->displayHtmlForColumnChange($selected);
    }

    /**
     * Displays HTML for changing one or more columns
     *
     * @param array|null $selected the selected columns
     */
    private function displayHtmlForColumnChange(?array $selected): void
    {
        global $action, $num_fields;

        // Column metadata reports the effective collation, including one inherited from the table.
        $inheritedCollations = self::getInheritedCollations(
            $this->dbi->getTable($this->db, $this->table)->showCreate()
        );

        if (empty($selected)) {
            $selected[] = $_REQUEST['field'];
            $selected_cnt = 1;
        } else { // from a multiple submit
            $selected_cnt = count($selected);
        }

        /**
         * @todo optimize in case of multiple fields to modify
         */
        $fields_meta = [];
        for ($i = 0; $i < $selected_cnt; $i++) {
            $value = $this->dbi->getColumn($this->db, $this->table, $selected[$i], true);
            if (count($value) === 0) {
                $message = Message::error(
                    __('Failed to get description of column %s!')
                );
                $message->addParam($selected[$i]);
                $this->response->addHTML($message->getDisplay());
            } else {
                if (isset($inheritedCollations[$selected[$i]])) {
                    $value['Collation'] = null;
                }

                $fields_meta[] = $value;
            }
        }

        $num_fields = count($fields_meta);

        $action = Url::getFromRoute('/table/structure/save');

        /**
         * Form for changing properties.
         */
        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js', 'indexes.js']);

        $templateData = ColumnsDefinition::displayForm(
            $this->transformations,
            $this->relation,
            $this->dbi,
            $action,
            $num_fields,
            null,
            $selected,
            $fields_meta
        );

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }

    /** @return array<string, true> */
    private static function getInheritedCollations(string $createTable): array
    {
        if ($createTable === '') {
            return [];
        }

        $parser = new Parser($createTable);
        if ($parser->errors !== []) {
            return [];
        }

        $statement = $parser->statements[0] ?? null;
        if (! $statement instanceof CreateStatement || ! is_array($statement->fields)) {
            return [];
        }

        $inheritedCollations = [];
        foreach ($statement->fields as $field) {
            if ($field->name === null || $field->type === null) {
                continue;
            }

            $options = $field->type->options;
            if (
                $options->has('COLLATE') !== false
                || $options->has('CHARACTER SET') !== false
                || $options->has('CHARSET') !== false
            ) {
                continue;
            }

            $inheritedCollations[$field->name] = true;
        }

        return $inheritedCollations;
    }
}
