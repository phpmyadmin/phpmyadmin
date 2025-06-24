<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Table\UiProperty;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;

use function __;
use function count;
use function implode;
use function in_array;
use function is_array;
use function sprintf;
use function str_contains;
use function strlen;

final class SaveController implements InvocableController
{
    private Table $tableObj;

    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Relation $relation,
        private readonly Transformations $transformations,
        private readonly DatabaseInterface $dbi,
        private readonly StructureController $structureController,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
        private readonly Config $config,
    ) {
        $this->tableObj = $this->dbi->getTable(Current::$database, Current::$table);
    }

    public function __invoke(ServerRequest $request): Response
    {
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $regenerate = $this->updateColumns($userPrivileges);
        if (! $regenerate) {
            // continue to show the table's structure
            unset($_POST['selected']);
        }

        return ($this->structureController)($request);
    }

    /**
     * Update the table's structure based on $_REQUEST
     *
     * @return bool true if error occurred
     */
    private function updateColumns(UserPrivileges $userPrivileges): bool
    {
        $errUrl = Url::getFromRoute('/table/structure', ['db' => Current::$database, 'table' => Current::$table]);
        $regenerate = false;
        $fieldCnt = count($_POST['field_name'] ?? []);
        $changes = [];
        $adjustPrivileges = [];
        $columnsWithIndex = $this->dbi
            ->getTable(Current::$database, Current::$table)
            ->getColumnsWithIndex(Index::PRIMARY | Index::UNIQUE);
        for ($i = 0; $i < $fieldCnt; $i++) {
            if (! $this->columnNeedsAlterTable($i)) {
                continue;
            }

            $changes[] = 'CHANGE ' . Table::generateAlter(
                Util::getValueByKey($_POST, ['field_orig', $i], ''),
                $_POST['field_name'][$i],
                $_POST['field_type'][$i],
                $_POST['field_length'][$i],
                $_POST['field_attribute'][$i],
                Util::getValueByKey($_POST, ['field_collation', $i], ''),
                Util::getValueByKey($_POST, ['field_null', $i], 'NO'),
                $_POST['field_default_type'][$i],
                $_POST['field_default_value'][$i],
                Util::getValueByKey($_POST, ['field_extra', $i], ''),
                Util::getValueByKey($_POST, ['field_comments', $i], ''),
                Util::getValueByKey($_POST, ['field_virtuality', $i], ''),
                Util::getValueByKey($_POST, ['field_expression', $i], ''),
                Util::getValueByKey($_POST, ['field_move_to', $i], ''),
                $columnsWithIndex,
            );

            // find the remembered sort expression
            $sortedCol = $this->tableObj->getUiProp(UiProperty::SortedColumn);
            // if the old column name is part of the remembered sort expression
            if (str_contains((string) $sortedCol, Util::backquote($_POST['field_orig'][$i]))) {
                // delete the whole remembered sort expression
                $this->tableObj->removeUiProp(UiProperty::SortedColumn);
            }

            if (
                empty($_POST['field_adjust_privileges'][$i])
                || $_POST['field_orig'][$i] == $_POST['field_name'][$i]
            ) {
                continue;
            }

            $adjustPrivileges[$_POST['field_orig'][$i]] = $_POST['field_name'][$i];
        }

        if ($changes !== [] || isset($_POST['preview_sql'])) {
            // Builds the primary keys statements and updates the table
            $keyQuery = '';
            /**
             * this is a little bit more complex
             *
             * @todo if someone selects A_I when altering a column we need to check:
             *  - no other column with A_I
             *  - the column has an index, if not create one
             */

            // To allow replication, we first select the db to use
            // and then run queries on this db.
            if (! $this->dbi->selectDb(Current::$database)) {
                Generator::mysqlDie(
                    $this->dbi->getError(),
                    'USE ' . Util::backquote(Current::$database) . ';',
                    false,
                    $errUrl,
                );
            }

            $sqlQuery = 'ALTER TABLE ' . Util::backquote(Current::$table) . ' ';
            $sqlQuery .= implode(', ', $changes) . $keyQuery;
            if (isset($_POST['online_transaction'])) {
                $sqlQuery .= ', ALGORITHM=INPLACE, LOCK=NONE';
            }

            $sqlQuery .= ';';

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL($changes !== [] ? $sqlQuery : '');

                $this->response->callExit();
            }

            $columnsWithIndex = $this->dbi
                ->getTable(Current::$database, Current::$table)
                ->getColumnsWithIndex(Index::PRIMARY | Index::UNIQUE | Index::INDEX | Index::SPATIAL | Index::FULLTEXT);

            $changedToBlob = [];
            // While changing the Column Collation
            // First change to BLOB, MEDIUMBLOB, or LONGBLOB (depending on the original field type)
            /** @infection-ignore-all */
            for ($i = 0; $i < $fieldCnt; $i++) {
                if (
                    isset($_POST['field_collation'][$i], $_POST['field_collation_orig'][$i])
                    && $_POST['field_collation'][$i] !== $_POST['field_collation_orig'][$i]
                    && ! in_array($_POST['field_orig'][$i], $columnsWithIndex)
                ) {
                    $blobType = match ($_POST['field_type_orig'][$i]) {
                        'MEDIUMTEXT' => 'MEDIUMBLOB',
                        'LONGTEXT' => 'LONGBLOB',
                        default => 'BLOB',
                    };

                    $secondaryQuery = 'ALTER TABLE ' . Util::backquote(Current::$table)
                        . ' CHANGE ' . Util::backquote($_POST['field_orig'][$i])
                        . ' ' . Util::backquote($_POST['field_orig'][$i])
                        . ' ' . $blobType;

                    if (isset($_POST['field_virtuality'][$i], $_POST['field_expression'][$i])) {
                        if ($_POST['field_virtuality'][$i]) {
                            $secondaryQuery .= ' AS (' . $_POST['field_expression'][$i] . ') '
                                . $_POST['field_virtuality'][$i];
                        }
                    }

                    $secondaryQuery .= ';';

                    $this->dbi->query($secondaryQuery);
                    $changedToBlob[$i] = true;
                } else {
                    $changedToBlob[$i] = false;
                }
            }

            // Checking if query has quoted current_timestamp function in default value
            $sqlQuery = $this->hasCurrentTimestampFunction($sqlQuery) ? $this->replaceQuotes($sqlQuery) : $sqlQuery;

            // Then make the requested changes
            $result = $this->dbi->tryQuery($sqlQuery);

            if ($result !== false) {
                $changedPrivileges = $this->adjustColumnPrivileges($userPrivileges, $adjustPrivileges);

                if ($changedPrivileges) {
                    $message = Message::success(
                        __(
                            'Table %1$s has been altered successfully. Privileges have been adjusted.',
                        ),
                    );
                } else {
                    $message = Message::success(
                        __('Table %1$s has been altered successfully.'),
                    );
                }

                $message->addParam(Current::$table);

                $this->response->addHTML(
                    Generator::getMessage($message, $sqlQuery, MessageType::Success),
                );
            } else {
                // An error happened while inserting/updating a table definition

                // Save the Original Error
                $origError = $this->dbi->getError();
                $changesRevert = [];

                // Change back to Original Collation and data type
                for ($i = 0; $i < $fieldCnt; $i++) {
                    if (! $changedToBlob[$i]) {
                        continue;
                    }

                    $changesRevert[] = 'CHANGE ' . Table::generateAlter(
                        Util::getValueByKey($_POST, ['field_orig', $i], ''),
                        $_POST['field_name'][$i],
                        $_POST['field_type_orig'][$i],
                        $_POST['field_length_orig'][$i],
                        $_POST['field_attribute_orig'][$i],
                        Util::getValueByKey($_POST, ['field_collation_orig', $i], ''),
                        Util::getValueByKey($_POST, ['field_null_orig', $i], 'NO'),
                        $_POST['field_default_type_orig'][$i],
                        $_POST['field_default_value_orig'][$i],
                        Util::getValueByKey($_POST, ['field_extra_orig', $i], ''),
                        Util::getValueByKey($_POST, ['field_comments_orig', $i], ''),
                        Util::getValueByKey($_POST, ['field_virtuality_orig', $i], ''),
                        Util::getValueByKey($_POST, ['field_expression_orig', $i], ''),
                        Util::getValueByKey($_POST, ['field_move_to_orig', $i], ''),
                    );
                }

                $revertQuery = 'ALTER TABLE ' . Util::backquote(Current::$table)
                    . ' ';
                $revertQuery .= implode(', ', $changesRevert);
                $revertQuery .= ';';

                // Column reverted back to original
                $this->dbi->query($revertQuery);

                $this->response->setRequestStatus(false);
                $message = Message::rawError(
                    __('Query error') . ':<br>' . $origError,
                );
                $this->response->addHTML(
                    Generator::getMessage($message, $sqlQuery, MessageType::Error),
                );
                $regenerate = true;
            }
        }

        // update field names in relation
        if (isset($_POST['field_orig']) && is_array($_POST['field_orig'])) {
            foreach ($_POST['field_orig'] as $fieldindex => $fieldcontent) {
                if ($_POST['field_name'][$fieldindex] == $fieldcontent) {
                    continue;
                }

                $this->relation->renameField(
                    Current::$database,
                    Current::$table,
                    $fieldcontent,
                    $_POST['field_name'][$fieldindex],
                );
            }
        }

        // update mime types
        if (
            isset($_POST['field_mimetype'])
            && is_array($_POST['field_mimetype'])
            && $this->config->settings['BrowseMIME']
        ) {
            foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                if (! isset($_POST['field_name'][$fieldindex]) || strlen($_POST['field_name'][$fieldindex]) <= 0) {
                    continue;
                }

                $this->transformations->setMime(
                    Current::$database,
                    Current::$table,
                    $_POST['field_name'][$fieldindex],
                    $mimetype,
                    $_POST['field_transformation'][$fieldindex],
                    $_POST['field_transformation_options'][$fieldindex],
                    $_POST['field_input_transformation'][$fieldindex],
                    $_POST['field_input_transformation_options'][$fieldindex],
                );
            }
        }

        return $regenerate;
    }

    /**
     * Verifies if some elements of a column have changed
     *
     * @param int $i column index in the request
     */
    private function columnNeedsAlterTable(int $i): bool
    {
        // these two fields are checkboxes so might not be part of the
        // request; therefore we define them to avoid notices below
        if (! isset($_POST['field_null'][$i])) {
            $_POST['field_null'][$i] = 'NO';
        }

        if (! isset($_POST['field_extra'][$i])) {
            $_POST['field_extra'][$i] = '';
        }

        // field_name does not follow the convention (corresponds to field_orig)
        if ($_POST['field_name'][$i] != $_POST['field_orig'][$i]) {
            return true;
        }

        $fields = [
            'field_attribute',
            'field_collation',
            'field_comments',
            'field_default_value',
            'field_default_type',
            'field_extra',
            'field_length',
            'field_null',
            'field_type',
            'field_virtuality',
        ];
        foreach ($fields as $field) {
            if ($_POST[$field][$i] != $_POST[$field . '_orig'][$i]) {
                return true;
            }
        }

        return ! empty($_POST['field_move_to'][$i]);
    }

    /**
     * Adjusts the Privileges for all the columns whose names have changed
     *
     * @param mixed[] $adjustPrivileges assoc array of old col names mapped to new
     *                                 cols
     */
    private function adjustColumnPrivileges(UserPrivileges $userPrivileges, array $adjustPrivileges): bool
    {
        $changed = false;

        if ($userPrivileges->column && $userPrivileges->isReload) {
            $this->dbi->selectDb('mysql');

            // For Column specific privileges
            foreach ($adjustPrivileges as $oldCol => $newCol) {
                $this->dbi->query(
                    sprintf(
                        'UPDATE %s SET Column_name = "%s"
                        WHERE Db = "%s"
                        AND Table_name = "%s"
                        AND Column_name = "%s";',
                        Util::backquote('columns_priv'),
                        $newCol,
                        Current::$database,
                        Current::$table,
                        $oldCol,
                    ),
                );

                // i.e. if atleast one column privileges adjusted
                $changed = true;
            }

            if ($changed) {
                // Finally FLUSH the new privileges
                $this->dbi->tryQuery('FLUSH PRIVILEGES;');
            }
        }

        return $changed;
    }

    /**
     * Checks if a query has DEFAULT CURRENT_TIMESTAMP function
     *
     * @param string $query SQL query to check
     *
     * @return bool true if query has DEFAULT CURRENT_TIMESTAMP function
     */
    private function hasCurrentTimestampFunction(string $query): bool
    {
        preg_match("/DEFAULT\s+'current_timestamp\((\d+)\)'/i", $query, $matches);

        return !empty($matches[0]);
    }

    /**
     * @param string $query SQL query to check
     *
     * @return int|null precision argument value or null if not found
     */
    private function getCurrentTimestampArgumentValue(string $query) {
        preg_match("/DEFAULT\s+'current_timestamp\((\d+)\)'/i", $query, $matches);
    }

    /**
     * Replaces DEFAULT 'current_timestamp(n)' with DEFAULT CURRENT_TIMESTAMP(n)
     * in the given query, where n is the precision argument value.
     *
     * @param string $query SQL query to modify
     *
     * @return string modified SQL query
     */
    private function replaceQuotes(string $query): string
    {
        $new_query = $query;

        while ($this->hasCurrentTimestampFunction($new_query)) {
            $val = $this->getCurrentTimestampArgumentValue($new_query);

            if ($val) {
                $sub_query = "DEFAULT CURRENT_TIMESTAMP($val)";

                $new_query = preg_replace(
                    "/DEFAULT\s+'current_timestamp\((\d+)\)'/",
                    $sub_query,
                    $new_query,
                    1
                );
            }
        }

        return $new_query;
    }
}
