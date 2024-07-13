<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;

use function is_string;
use function preg_match;

/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 */
final class UrlRedirector
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function redirect(mixed $urlParam): Response
    {
        $response = $this->responseFactory->createResponse();
        foreach ($this->response->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $url = is_string($urlParam) ? $urlParam : '';
        if (
            $url === ''
            || preg_match('/^https:\/\/[^\n\r]*$/', $url) !== 1
            || ! Core::isAllowedDomain($url)
        ) {
            $response = $response->withHeader('Location', $this->response->fixRelativeUrlForRedirect('./'));

            return $response->withStatus(StatusCodeInterface::STATUS_FOUND);
        }

        /**
         * JavaScript redirection is necessary. Because if header() is used then web browser sometimes does not change
         * the HTTP_REFERER field and so with old URL as Referer, token also goes to external site.
         */

        return $response->write($this->template->render('javascript/redirect', ['url' => $url]));
    }
}
