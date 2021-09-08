<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\AbstractController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function count;
use function mb_strlen;
use function mb_substr;

final class ReplacePrefixController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var StructureController */
    private $structureController;

    /**
     * @param ResponseRenderer  $response
     * @param string            $db
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, $dbi, StructureController $structureController)
    {
        parent::__construct($response, $template, $db);
        $this->dbi = $dbi;
        $this->structureController = $structureController;
    }

    public function __invoke(): void
    {
        global $db, $message, $sql_query;

        $selected = $_POST['selected'] ?? [];
        $fromPrefix = $_POST['from_prefix'] ?? '';
        $toPrefix = $_POST['to_prefix'] ?? '';

        $sql_query = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $current = $selected[$i];
            $subFromPrefix = mb_substr($current, 0, mb_strlen((string) $fromPrefix));

            if ($subFromPrefix === $fromPrefix) {
                $newTableName = $toPrefix . mb_substr($current, mb_strlen((string) $fromPrefix));
            } else {
                $newTableName = $current;
            }

            $aQuery = 'ALTER TABLE ' . Util::backquote($selected[$i])
                . ' RENAME ' . Util::backquote($newTableName);

            $sql_query .= $aQuery . ';' . "\n";
            $this->dbi->selectDb($db);
            $this->dbi->query($aQuery);
        }

        $message = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        ($this->structureController)();
    }
}
