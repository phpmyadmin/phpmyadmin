<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Transformation;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function intval;
use function round;
use function str_replace;
use function stripos;
use function substr;

/**
 * Wrapper script for rendering transformations
 */
class WrapperController extends AbstractController
{
    /** @var Transformations */
    private $transformations;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Transformations $transformations,
        Relation $relation,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->transformations = $transformations;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $this->response->getHeader()->setIsTransformationWrapper(true);
        $GLOBALS['cn'] = $GLOBALS['cn'] ?? null;
        $GLOBALS['transform_key'] = $GLOBALS['transform_key'] ?? null;
        $GLOBALS['request_params'] = $GLOBALS['request_params'] ?? null;
        $GLOBALS['size_params'] = $GLOBALS['size_params'] ?? null;
        $GLOBALS['where_clause'] = $GLOBALS['where_clause'] ?? null;
        $GLOBALS['row'] = $GLOBALS['row'] ?? null;

        $GLOBALS['default_ct'] = $GLOBALS['default_ct'] ?? null;
        $GLOBALS['mime_map'] = $GLOBALS['mime_map'] ?? null;
        $GLOBALS['mime_options'] = $GLOBALS['mime_options'] ?? null;
        $GLOBALS['ct'] = $GLOBALS['ct'] ?? null;
        $GLOBALS['mime_type'] = $GLOBALS['mime_type'] ?? null;
        $GLOBALS['srcImage'] = $GLOBALS['srcImage'] ?? null;
        $GLOBALS['srcWidth'] = $GLOBALS['srcWidth'] ?? null;
        $GLOBALS['srcHeight'] = $GLOBALS['srcHeight'] ?? null;
        $GLOBALS['ratioWidth'] = $GLOBALS['ratioWidth'] ?? null;
        $GLOBALS['ratioHeight'] = $GLOBALS['ratioHeight'] ?? null;
        $GLOBALS['destWidth'] = $GLOBALS['destWidth'] ?? null;
        $GLOBALS['destHeight'] = $GLOBALS['destHeight'] ?? null;
        $GLOBALS['destImage'] = $GLOBALS['destImage'] ?? null;

        $relationParameters = $this->relation->getRelationParameters();

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table'], true);

        /**
         * Sets globals from $_REQUEST
         */
        $GLOBALS['request_params'] = [
            'cn',
            'ct',
            'sql_query',
            'transform_key',
            'where_clause',
        ];
        $GLOBALS['size_params'] = [
            'newHeight',
            'newWidth',
        ];
        foreach ($GLOBALS['request_params'] as $one_request_param) {
            if (! isset($_REQUEST[$one_request_param])) {
                continue;
            }

            if (in_array($one_request_param, $GLOBALS['size_params'])) {
                $GLOBALS[$one_request_param] = intval($_REQUEST[$one_request_param]);
                if ($GLOBALS[$one_request_param] > 2000) {
                    $GLOBALS[$one_request_param] = 2000;
                }
            } else {
                $GLOBALS[$one_request_param] = $_REQUEST[$one_request_param];
            }
        }

        /**
         * Get the list of the fields of the current table
         */
        $this->dbi->selectDb($GLOBALS['db']);
        if (isset($GLOBALS['where_clause'])) {
            if (! Core::checkSqlQuerySignature($GLOBALS['where_clause'], $_GET['where_clause_sign'] ?? '')) {
                /* l10n: In case a SQL query did not pass a security check  */
                Core::fatalError(__('There is an issue with your request.'));

                return;
            }

            $result = $this->dbi->query(
                'SELECT * FROM ' . Util::backquote($GLOBALS['table'])
                . ' WHERE ' . $GLOBALS['where_clause'] . ';'
            );
            $GLOBALS['row'] = $result->fetchAssoc();
        } else {
            $result = $this->dbi->query(
                'SELECT * FROM ' . Util::backquote($GLOBALS['table']) . ' LIMIT 1;'
            );
            $GLOBALS['row'] = $result->fetchAssoc();
        }

        // No row returned
        if ($GLOBALS['row'] === []) {
            return;
        }

        $GLOBALS['default_ct'] = 'application/octet-stream';

        if (
            $relationParameters->columnCommentsFeature !== null
            && $relationParameters->browserTransformationFeature !== null
        ) {
            $GLOBALS['mime_map'] = $this->transformations->getMime($GLOBALS['db'], $GLOBALS['table']) ?? [];

            $GLOBALS['mime_options'] = $this->transformations->getOptions(
                $GLOBALS['mime_map'][$GLOBALS['transform_key']]['transformation_options'] ?? ''
            );

            foreach ($GLOBALS['mime_options'] as $option) {
                if (substr($option, 0, 10) !== '; charset=') {
                    continue;
                }

                $GLOBALS['mime_options']['charset'] = $option;
            }
        }

        $this->response->getHeader()->sendHttpHeaders();

        // [MIME]
        if (isset($GLOBALS['ct']) && ! empty($GLOBALS['ct'])) {
            $GLOBALS['mime_type'] = $GLOBALS['ct'];
        } else {
            $GLOBALS['mime_type'] = (! empty($GLOBALS['mime_map'][$GLOBALS['transform_key']]['mimetype'])
                    ? str_replace('_', '/', $GLOBALS['mime_map'][$GLOBALS['transform_key']]['mimetype'])
                    : $GLOBALS['default_ct'])
                . ($GLOBALS['mime_options']['charset'] ?? '');
        }

        Core::downloadHeader($GLOBALS['cn'] ?? '', $GLOBALS['mime_type']);

        if (! isset($_REQUEST['resize'])) {
            if (stripos($GLOBALS['mime_type'], 'html') === false) {
                echo $GLOBALS['row'][$GLOBALS['transform_key']];
            } else {
                echo htmlspecialchars($GLOBALS['row'][$GLOBALS['transform_key']]);
            }
        } else {
            // if image_*__inline.inc.php finds that we can resize,
            // it sets the resize parameter to jpeg or png

            $GLOBALS['srcImage'] = ImageWrapper::fromString($GLOBALS['row'][$GLOBALS['transform_key']]);
            if ($GLOBALS['srcImage'] === null) {
                return;
            }

            $GLOBALS['srcWidth'] = $GLOBALS['srcImage']->width();
            $GLOBALS['srcHeight'] = $GLOBALS['srcImage']->height();

            // Check to see if the width > height or if width < height
            // if so adjust accordingly to make sure the image
            // stays smaller than the new width and new height

            $GLOBALS['ratioWidth'] = $GLOBALS['srcWidth'] / $_REQUEST['newWidth'];
            $GLOBALS['ratioHeight'] = $GLOBALS['srcHeight'] / $_REQUEST['newHeight'];

            if ($GLOBALS['ratioWidth'] < $GLOBALS['ratioHeight']) {
                $GLOBALS['destWidth'] = intval(round($GLOBALS['srcWidth'] / $GLOBALS['ratioHeight']));
                $GLOBALS['destHeight'] = intval($_REQUEST['newHeight']);
            } else {
                $GLOBALS['destWidth'] = intval($_REQUEST['newWidth']);
                $GLOBALS['destHeight'] = intval(round($GLOBALS['srcHeight'] / $GLOBALS['ratioWidth']));
            }

            if ($_REQUEST['resize']) {
                $GLOBALS['destImage'] = ImageWrapper::create($GLOBALS['destWidth'], $GLOBALS['destHeight']);
                if ($GLOBALS['destImage'] === null) {
                    $GLOBALS['srcImage']->destroy();

                    return;
                }

                // ImageCopyResized($destImage, $srcImage, 0, 0, 0, 0,
                // $destWidth, $destHeight, $srcWidth, $srcHeight);
                // better quality but slower:
                $GLOBALS['destImage']->copyResampled(
                    $GLOBALS['srcImage'],
                    0,
                    0,
                    0,
                    0,
                    $GLOBALS['destWidth'],
                    $GLOBALS['destHeight'],
                    $GLOBALS['srcWidth'],
                    $GLOBALS['srcHeight']
                );
                if ($_REQUEST['resize'] === 'jpeg') {
                    $GLOBALS['destImage']->jpeg(null, 75);
                }

                if ($_REQUEST['resize'] === 'png') {
                    $GLOBALS['destImage']->png();
                }

                $GLOBALS['destImage']->destroy();
            }

            $GLOBALS['srcImage']->destroy();
        }
    }
}
