<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Message;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function count;

final class DropColumnController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var FlashMessages */
    private $flash;

    /** @var RelationCleanup */
    private $relationCleanup;

    /**
     * @param Response          $response
     * @param string            $db       Database name
     * @param string            $table    Table name
     * @param DatabaseInterface $dbi
     */
    public function __construct(
        $response,
        Template $template,
        $db,
        $table,
        $dbi,
        FlashMessages $flash,
        RelationCleanup $relationCleanup
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
        $this->flash = $flash;
        $this->relationCleanup = $relationCleanup;
    }

    public function process(): void
    {
        $selected = $_POST['selected'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $selectedCount = count($selected);
        if (($_POST['mult_btn'] ?? '') === __('Yes')) {
            $i = 1;
            $statement = 'ALTER TABLE ' . Util::backquote($this->table);

            foreach ($selected as $field) {
                $this->relationCleanup->column($this->db, $this->table, $field);
                $statement .= ' DROP ' . Util::backquote($field);
                $statement .= $i++ === $selectedCount ? ';' : ',';
            }

            $this->dbi->selectDb($this->db);
            $result = $this->dbi->tryQuery($statement);

            if (! $result) {
                $message = Message::error((string) $this->dbi->getError());
            }
        } else {
            $message = Message::success(__('No change'));
        }

        if (empty($message)) {
            $message = Message::success(
                _ngettext(
                    '%1$d column has been dropped successfully.',
                    '%1$d columns have been dropped successfully.',
                    $selectedCount
                )
            );
            $message->addParam($selectedCount);
        }

        $this->flash->addMessage($message->isError() ? 'danger' : 'success', $message->getMessage());
        $this->redirect('/table/structure', ['db' => $this->db, 'table' => $this->table]);
    }
}
