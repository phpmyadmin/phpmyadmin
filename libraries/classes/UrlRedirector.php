<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;
use function preg_match;

/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 */
final class UrlRedirector
{
    public static function redirect(string $url): never
    {
        // Load database service because services.php is not available here
        $GLOBALS['dbi'] = DatabaseInterface::load();
        $container = Core::getContainerBuilder();
        $container->set(DatabaseInterface::class, $GLOBALS['dbi']);

        // Only output the http headers
        $response = ResponseRenderer::getInstance();
        $response->getHeader()->sendHttpHeaders();
        $response->disable();

        if (
            $url === ''
            || ! preg_match('/^https:\/\/[^\n\r]*$/', $url)
            || ! Core::isAllowedDomain($url)
        ) {
            Core::sendHeaderLocation('./');

            exit;
        }

        /**
         * JavaScript redirection is necessary. Because if header() is used then web browser sometimes does not change
         * the HTTP_REFERER field and so with old URL as Referer, token also goes to external site.
         *
         * @var Template $template
         */
        $template = $container->get('template');
        echo $template->render('javascript/redirect', ['url' => $url]);
        // Display redirecting msg on screen.
        // Do not display the value of $_GET['url'] to avoid showing injected content
        echo __('Taking you to the target site.');

        exit;
    }
}
