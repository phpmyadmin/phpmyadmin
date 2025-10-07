<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Partitioning\TablePartitionDefinition;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Util;

use function __;
use function count;
use function in_array;
use function strpos;
use function strrpos;
use function substr;
use function trim;

#[Route('/table/structure/partitioning', ['POST'])]
final class PartitioningController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly CreateAddField $createAddField,
        private readonly StructureController $structureController,
        private readonly PageSettings $pageSettings,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (isset($_POST['save_partitioning'])) {
            $this->dbi->selectDb(Current::$database);
            $this->updatePartitioning();

            return ($this->structureController)($request);
        }

        $this->pageSettings->init('TableStructure');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        $this->response->addScriptFiles(['table/structure.js']);

        $partitionDetails = null;
        if (! isset($_POST['partition_by'])) {
            $partitionDetails = $this->extractPartitionDetails();
        }

        $storageEngines = StorageEngine::getArray();

        if ($partitionDetails === null) {
            $partitionDetails = TablePartitionDefinition::getDetails();
        }

        $this->response->render('table/structure/partition_definition_form', [
            'db' => Current::$database,
            'table' => Current::$table,
            'partition_details' => $partitionDetails,
            'storage_engines' => $storageEngines,
        ]);

        return $this->response->response();
    }

    /**
     * Extracts partition details from CREATE TABLE statement
     *
     * @return array<string, array<int, array<string, mixed>>|bool|int|string>|null array of partition details
     */
    private function extractPartitionDetails(): array|null
    {
        $createTable = (new Table(Current::$table, Current::$database, $this->dbi))->showCreate();
        if ($createTable === '') {
            return null;
        }

        $parser = new Parser($createTable);
        /** @var CreateStatement $stmt */
        $stmt = $parser->statements[0];

        $partitionDetails = [];

        $partitionDetails['partition_by'] = '';
        $partitionDetails['partition_expr'] = '';
        $partitionDetails['partition_count'] = 0;

        if (! empty($stmt->partitionBy)) {
            $openPos = strpos($stmt->partitionBy, '(');
            $closePos = strrpos($stmt->partitionBy, ')');

            if ($openPos !== false && $closePos !== false) {
                $partitionDetails['partition_by'] = trim(substr($stmt->partitionBy, 0, $openPos));
                $partitionDetails['partition_expr'] = trim(substr(
                    $stmt->partitionBy,
                    $openPos + 1,
                    $closePos - ($openPos + 1),
                ));

                $count = $stmt->partitionsNum ?? count($stmt->partitions);

                $partitionDetails['partition_count'] = $count;
            }
        }

        $partitionDetails['subpartition_by'] = '';
        $partitionDetails['subpartition_expr'] = '';
        $partitionDetails['subpartition_count'] = 0;

        if (! empty($stmt->subpartitionBy)) {
            $openPos = strpos($stmt->subpartitionBy, '(');
            $closePos = strrpos($stmt->subpartitionBy, ')');

            if ($openPos !== false && $closePos !== false) {
                $partitionDetails['subpartition_by'] = trim(substr($stmt->subpartitionBy, 0, $openPos));
                $partitionDetails['subpartition_expr'] = trim(substr(
                    $stmt->subpartitionBy,
                    $openPos + 1,
                    $closePos - ($openPos + 1),
                ));

                $count = $stmt->subpartitionsNum ?? count($stmt->partitions[0]->subpartitions);

                $partitionDetails['subpartition_count'] = $count;
            }
        }

        // Only LIST and RANGE type parameters allow subpartitioning
        $partitionDetails['can_have_subpartitions'] = $partitionDetails['partition_count'] > 1
            && in_array($partitionDetails['partition_by'], ['RANGE', 'RANGE COLUMNS', 'LIST', 'LIST COLUMNS'], true);

        // Values are specified only for LIST and RANGE type partitions
        $partitionDetails['value_enabled'] = isset($partitionDetails['partition_by'])
            && in_array($partitionDetails['partition_by'], ['RANGE', 'RANGE COLUMNS', 'LIST', 'LIST COLUMNS'], true);

        $partitionDetails['partitions'] = [];

        for ($i = 0, $iMax = $partitionDetails['partition_count']; $i < $iMax; $i++) {
            if (! isset($stmt->partitions[$i])) {
                $partitionDetails['partitions'][$i] = [
                    'name' => 'p' . $i,
                    'value_type' => '',
                    'value' => '',
                    'engine' => '',
                    'comment' => '',
                    'data_directory' => '',
                    'index_directory' => '',
                    'max_rows' => '',
                    'min_rows' => '',
                    'tablespace' => '',
                    'node_group' => '',
                ];
            } else {
                $p = $stmt->partitions[$i];
                $type = $p->type;
                $expr = trim((string) $p->expr, '()');
                if ($expr === 'MAXVALUE') {
                    $type .= ' MAXVALUE';
                    $expr = '';
                }

                $partitionDetails['partitions'][$i] = [
                    'name' => $p->name,
                    'value_type' => $type,
                    'value' => $expr,
                    'engine' => $p->options->get('ENGINE', true),
                    'comment' => trim((string) $p->options->get('COMMENT', true), "'"),
                    'data_directory' => trim((string) $p->options->get('DATA DIRECTORY', true), "'"),
                    'index_directory' => trim((string) $p->options->get('INDEX_DIRECTORY', true), "'"),
                    'max_rows' => $p->options->get('MAX_ROWS', true),
                    'min_rows' => $p->options->get('MIN_ROWS', true),
                    'tablespace' => $p->options->get('TABLESPACE', true),
                    'node_group' => $p->options->get('NODEGROUP', true),
                ];
            }

            $partition =& $partitionDetails['partitions'][$i];
            $partition['prefix'] = 'partitions[' . $i . ']';

            if ($partitionDetails['subpartition_count'] <= 1) {
                continue;
            }

            $partition['subpartition_count'] = $partitionDetails['subpartition_count'];
            $partition['subpartitions'] = [];

            for ($j = 0, $jMax = $partitionDetails['subpartition_count']; $j < $jMax; $j++) {
                if (! isset($stmt->partitions[$i]->subpartitions[$j])) {
                    $partition['subpartitions'][$j] = [
                        'name' => $partition['name'] . '_s' . $j,
                        'engine' => '',
                        'comment' => '',
                        'data_directory' => '',
                        'index_directory' => '',
                        'max_rows' => '',
                        'min_rows' => '',
                        'tablespace' => '',
                        'node_group' => '',
                    ];
                } else {
                    $sp = $stmt->partitions[$i]->subpartitions[$j];
                    $partition['subpartitions'][$j] = [
                        'name' => $sp->name,
                        'engine' => $sp->options->get('ENGINE', true),
                        'comment' => trim((string) $sp->options->get('COMMENT', true), "'"),
                        'data_directory' => trim((string) $sp->options->get('DATA DIRECTORY', true), "'"),
                        'index_directory' => trim((string) $sp->options->get('INDEX_DIRECTORY', true), "'"),
                        'max_rows' => $sp->options->get('MAX_ROWS', true),
                        'min_rows' => $sp->options->get('MIN_ROWS', true),
                        'tablespace' => $sp->options->get('TABLESPACE', true),
                        'node_group' => $sp->options->get('NODEGROUP', true),
                    ];
                }

                $subpartition =& $partition['subpartitions'][$j];
                $subpartition['prefix'] = 'partitions[' . $i . ']'
                    . '[subpartitions][' . $j . ']';
            }
        }

        return $partitionDetails;
    }

    private function updatePartitioning(): void
    {
        $sqlQuery = 'ALTER TABLE ' . Util::backquote(Current::$table) . ' '
            . $this->createAddField->getPartitionsDefinition();

        // Execute alter query
        $result = $this->dbi->tryQuery($sqlQuery);

        if ($result === false) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(
                'message',
                Message::rawError(
                    __('Query error') . ':<br>' . $this->dbi->getError(),
                ),
            );

            return;
        }

        $message = Message::success(
            __('Table %1$s has been altered successfully.'),
        );
        $message->addParam(Current::$table);
        $this->response->addHTML(
            Generator::getMessage($message, $sqlQuery, MessageType::Success),
        );
    }
}
