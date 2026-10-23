<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Exceptions\MismatchedSessionId;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function __;
use function assert;
use function hash_equals;
use function session_id;

/**
 * Check whether user supplied token is valid, if not remove any possibly
 * dangerous stuff from request.
 *
 * Check for token mismatch only if the Request method is POST.
 * GET Requests would never have token and therefore checking
 * mismatch does not make sense.
 */
final readonly class TokenRequestParamChecking implements MiddlewareInterface
{
    public function __construct(private ResponseRenderer $responseRenderer)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);

        if ($request->isPost()) {
            $result = $this->checkTokenRequestParam($request);
            if ($result === false) {
                if ($request->isAjax()) {
                    // There is no point in even attempting to process an ajax request if there is a token mismatch
                    $this->responseRenderer->setRequestStatus(false);
                    $this->responseRenderer->addJSON('message', Message::error(__('Error: Token mismatch')));

                    return $this->responseRenderer->response();
                }

                /**
                 * We don't allow any POST operation parameters if the token is mismatched
                 * or is not provided. The request is also sent to the home page, so that
                 * no controller acts on a request that may come from another site.
                 */
                $_REQUEST = $_POST = $_GET = $_COOKIE = [];
                $request = $request->withParsedBody([])
                    ->withQueryParams([])
                    ->withCookieParams([])
                    ->withAttribute('route', '/');
            }
        }

        return $handler->handle($request);
    }

    public function checkTokenRequestParam(ServerRequest $request): bool
    {
        $token = $request->getParsedBodyParamAsString('token', '');
        if ($token !== '' && hash_equals(Session::getToken(), $token)) {
            return true;
        }

        // Warn in case the mismatch is result of failed setting of session cookie
        if ($request->hasBodyParam('set_session') && $request->getParsedBodyParam('set_session') !== session_id()) {
            throw new MismatchedSessionId(
                __(
                    'Failed to set session cookie. Maybe you are using HTTP instead of HTTPS to access phpMyAdmin.',
                ),
            );
        }

        return false;
    }
}
