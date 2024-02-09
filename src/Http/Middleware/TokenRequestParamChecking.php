<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Sanitize;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function __;
use function hash_equals;
use function is_scalar;
use function session_id;
use function trigger_error;

use const E_USER_ERROR;

/**
 * Check whether user supplied token is valid, if not remove any possibly
 * dangerous stuff from request.
 *
 * Check for token mismatch only if the Request method is POST.
 * GET Requests would never have token and therefore checking
 * mismatch does not make sense.
 */
final class TokenRequestParamChecking implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->checkTokenRequestParam();

        return $handler->handle($request);
    }

    public function checkTokenRequestParam(): void
    {
        $GLOBALS['token_mismatch'] = true;
        $GLOBALS['token_provided'] = false;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        if (isset($_POST['token']) && is_scalar($_POST['token']) && (string) $_POST['token'] !== '') {
            $GLOBALS['token_provided'] = true;
            $GLOBALS['token_mismatch'] = ! @hash_equals($_SESSION[' PMA_token '], (string) $_POST['token']);
        }

        if (! $GLOBALS['token_mismatch']) {
            return;
        }

        // Warn in case the mismatch is result of failed setting of session cookie
        if (isset($_POST['set_session']) && $_POST['set_session'] !== session_id()) {
            trigger_error(
                __(
                    'Failed to set session cookie. Maybe you are using HTTP instead of HTTPS to access phpMyAdmin.',
                ),
                E_USER_ERROR,
            );
        }

        /**
         * We don't allow any POST operation parameters if the token is mismatched
         * or is not provided.
         */
        $allowList = ['ajax_request'];
        Sanitize::removeRequestVars($allowList);
    }
}
