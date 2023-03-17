<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Transformation;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\InvalidIdentifierName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function intval;
use function is_numeric;
use function is_string;
use function round;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strtolower;

/**
 * Wrapper script for rendering transformations
 */
class WrapperController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Transformations $transformations,
        private Relation $relation,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->response->getHeader()->setIsTransformationWrapper(true);

        try {
            $db = DatabaseName::fromValue($request->getParam('db'));
            $table = TableName::fromValue($request->getParam('table'));
        } catch (InvalidIdentifierName) {
            return;
        }

        DbTableExists::check($db->getName(), $table->getName(), true);
        $this->dbi->selectDb($db);

        $query = $this->getQuery($table, $request->getParam('where_clause'), $request->getParam('where_clause_sign'));
        if ($query === null) {
            $this->response->setRequestStatus(false);
            /* l10n: In case a SQL query did not pass a security check  */
            $this->response->addHTML(Message::error(__('There is an issue with your request.'))->getDisplay());

            return;
        }

        $row = $this->dbi->query($query)->fetchAssoc();
        if ($row === []) {
            return;
        }

        $transformKey = $request->getParam('transform_key');
        if (
            ! is_string($transformKey) || $transformKey === ''
            || ! isset($row[$transformKey]) || $row[$transformKey] === ''
        ) {
            return;
        }

        $mediaTypeMap = [];
        $mediaTypeOptions = [];
        $relationParameters = $this->relation->getRelationParameters();
        if (
            $relationParameters->columnCommentsFeature !== null
            && $relationParameters->browserTransformationFeature !== null
        ) {
            $mediaTypeMap = $this->transformations->getMime($db->getName(), $table->getName()) ?? [];
            $mediaTypeOptions = $this->transformations->getOptions(
                $mediaTypeMap[$transformKey]['transformation_options'] ?? '',
            );

            foreach ($mediaTypeOptions as $option) {
                if (! str_starts_with($option, '; charset=')) {
                    continue;
                }

                $mediaTypeOptions['charset'] = $option;
            }
        }

        $this->response->getHeader()->sendHttpHeaders();

        /** @psalm-suppress MixedAssignment */
        $contentType = $request->getParam('ct');
        if (is_string($contentType) && $contentType !== '') {
            $contentMediaType = $contentType;
        } else {
            $contentMediaType = 'application/octet-stream';
            if (! empty($mediaTypeMap[$transformKey]['mimetype'])) {
                $contentMediaType = str_replace('_', '/', $mediaTypeMap[$transformKey]['mimetype']);
            }

            $contentMediaType .= $mediaTypeOptions['charset'] ?? '';
        }

        /** @psalm-suppress MixedAssignment */
        $contentName = $request->getParam('cn');
        $contentName = is_string($contentName) ? $contentName : '';

        Core::downloadHeader($contentName, $contentMediaType);

        $resize = $request->getParam('resize');
        if ($resize !== 'jpeg' && $resize !== 'png') {
            if (str_contains(strtolower($contentMediaType), 'html')) {
                echo htmlspecialchars($row[$transformKey]);

                return;
            }

            echo $row[$transformKey];

            return;
        }

        $srcImage = ImageWrapper::fromString($row[$transformKey]);
        if ($srcImage === null) {
            return;
        }

        $newHeight = $this->formatSize($request->getParam('newHeight'));
        $newWidth = $this->formatSize($request->getParam('newWidth'));

        $srcWidth = $srcImage->width();
        $srcHeight = $srcImage->height();

        $ratioWidth = $srcWidth / $newWidth;
        $ratioHeight = $srcHeight / $newHeight;

        /**
         * Check to see if the width > height or if width < height
         * if so adjust accordingly to make sure the image
         * stays smaller than the new width and new height
         */
        if ($ratioWidth < $ratioHeight) {
            $destWidth = intval(round($srcWidth / $ratioHeight));
            $destHeight = $newHeight;
        } else {
            $destWidth = $newWidth;
            $destHeight = intval(round($srcHeight / $ratioWidth));
        }

        $destImage = ImageWrapper::create($destWidth, $destHeight);
        if ($destImage === null) {
            return;
        }

        $destImage->copyResampled($srcImage, 0, 0, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);

        if ($resize === 'jpeg') {
            $destImage->jpeg(null, 75);
        } else {
            $destImage->png();
        }
    }

    private function formatSize(mixed $size): int
    {
        if (! is_numeric($size) || $size < 2) {
            return 1;
        }

        if ($size >= 2000) {
            return 2000;
        }

        return (int) $size;
    }

    private function getQuery(TableName $table, mixed $whereClause, mixed $whereClauseSign): string|null
    {
        if ($whereClause === null) {
            return sprintf('SELECT * FROM %s LIMIT 1;', Util::backquote($table));
        }

        if (
            ! is_string($whereClause) || $whereClause === ''
            || ! is_string($whereClauseSign) || $whereClauseSign === ''
            || ! Core::checkSqlQuerySignature($whereClause, $whereClauseSign)
        ) {
            return null;
        }

        return sprintf('SELECT * FROM %s WHERE %s;', Util::backquote($table), $whereClause);
    }
}
