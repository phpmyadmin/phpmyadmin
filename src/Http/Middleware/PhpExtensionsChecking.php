<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Core;
use PhpMyAdmin\Exceptions\MissingExtensionException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Template;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function __;
use function function_exists;
use function sprintf;

final class PhpExtensionsChecking implements MiddlewareInterface
{
    public function __construct(private readonly Template $template, private readonly ResponseFactory $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->checkRequiredPhpExtensions();
        } catch (MissingExtensionException $exception) {
            // Disables template caching because the cache directory is not known yet.
            $this->template->disableCache();
            $output = $this->template->render('error/generic', [
                'lang' => 'en',
                'dir' => 'ltr',
                'error_message' => $exception->getMessage(),
            ]);
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
            $response->getBody()->write($output);

            return $response;
        }

        return $handler->handle($request);
    }

    /**
     * Checks that required PHP extensions are there.
     */
    private function checkRequiredPhpExtensions(): void
    {
        /**
         * Warning about mbstring.
         */
        if (! function_exists('mb_detect_encoding')) {
            Core::warnMissingExtension('mbstring');
        }

        /**
         * We really need this one!
         */
        if (! function_exists('preg_replace')) {
            Core::warnMissingExtension('pcre', true);
        }

        /**
         * JSON is required in several places.
         */
        if (! function_exists('json_encode')) {
            Core::warnMissingExtension('json', true);
        }

        /**
         * ctype is required for Twig.
         */
        if (! function_exists('ctype_alpha')) {
            Core::warnMissingExtension('ctype', true);
        }

        if (! function_exists('mysqli_connect')) {
            $moreInfo = sprintf(__('See %sour documentation%s for more information.'), '[doc@faqmysql]', '[/doc]');
            Core::warnMissingExtension('mysqli', true, $moreInfo);
        }

        if (! function_exists('session_name')) {
            Core::warnMissingExtension('session', true);
        }

        /**
         * hash is required for cookie authentication.
         */
        if (function_exists('hash_hmac')) {
            return;
        }

        Core::warnMissingExtension('hash', true);
    }
}
