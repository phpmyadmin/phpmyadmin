<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use function preg_match;
use function str_contains;

/**
 * Determines browser and version of the user
 */
class UserAgentParser
{
    private string $userBrowserAgent = '';
    private string $userBrowserVersion = '';
    private string $userPlatform = '';

    public function __construct(string $httpUserAgent)
    {
        $this->parseUserAgent($httpUserAgent);
        $this->setClientPlatform($httpUserAgent);
    }

    private function parseUserAgent(string $httpUserAgent): void
    {
        // (must check everything else before Mozilla)
        $isMozilla = preg_match('@Mozilla/([0-9]\.[0-9]{1,2})@', $httpUserAgent, $mozillaVersion) === 1;

        if (preg_match('@Opera(/| )([0-9]\.[0-9]{1,2})@', $httpUserAgent, $logVersion) === 1) {
            $this->userBrowserVersion = $logVersion[2];
            $this->userBrowserAgent = 'OPERA';
        } elseif (preg_match('@(MS)?IE ([0-9]{1,2}\.[0-9]{1,2})@', $httpUserAgent, $logVersion) === 1) {
            $this->userBrowserVersion = $logVersion[2];
            $this->userBrowserAgent = 'IE';
        } elseif (preg_match('@Trident/(7)\.0@', $httpUserAgent, $logVersion) === 1) {
            $this->userBrowserVersion = (string) ((int) $logVersion[1] + 4);
            $this->userBrowserAgent = 'IE';
        } elseif (preg_match('@OmniWeb/([0-9]{1,3})@', $httpUserAgent, $logVersion) === 1) {
            $this->userBrowserVersion = $logVersion[1];
            $this->userBrowserAgent = 'OMNIWEB';
            // Konqueror 2.2.2 says Konqueror/2.2.2
            // Konqueror 3.0.3 says Konqueror/3
        } elseif (preg_match('@(Konqueror/)(.*)(;)@', $httpUserAgent, $logVersion) === 1) {
            $this->userBrowserVersion = $logVersion[2];
            $this->userBrowserAgent = 'KONQUEROR';
            // must check Chrome before Safari
        } elseif ($isMozilla && preg_match('@Chrome/([0-9.]*)@', $httpUserAgent, $logVersion) === 1) {
            $this->userBrowserVersion = $logVersion[1];
            $this->userBrowserAgent = 'CHROME';
            // newer Safari
        } elseif ($isMozilla && preg_match('@Version/(.*) Safari@', $httpUserAgent, $logVersion) === 1) {
            $this->userBrowserVersion = $logVersion[1];
            $this->userBrowserAgent = 'SAFARI';
            // older Safari
        } elseif ($isMozilla && preg_match('@Safari/([0-9]*)@', $httpUserAgent, $logVersion) === 1) {
            $this->userBrowserVersion = $mozillaVersion[1] . '.' . $logVersion[1];
            $this->userBrowserAgent = 'SAFARI';
            // Firefox
        } elseif (
            ! str_contains($httpUserAgent, 'compatible')
            && preg_match('@Firefox/([\w.]+)@', $httpUserAgent, $logVersion) === 1
        ) {
            $this->userBrowserVersion = $logVersion[1];
            $this->userBrowserAgent = 'FIREFOX';
        } elseif (preg_match('@rv:1\.9(.*)Gecko@', $httpUserAgent) === 1) {
            $this->userBrowserVersion = '1.9';
            $this->userBrowserAgent = 'GECKO';
        } elseif ($isMozilla) {
            $this->userBrowserVersion = $mozillaVersion[1];
            $this->userBrowserAgent = 'MOZILLA';
        } else {
            $this->userBrowserVersion = '0';
            $this->userBrowserAgent = 'OTHER';
        }
    }

    private function setClientPlatform(string $userAgent): void
    {
        if (str_contains($userAgent, 'Win')) {
            $this->userPlatform = 'Win';
        } elseif (str_contains($userAgent, 'Mac')) {
            $this->userPlatform = 'Mac';
        } elseif (str_contains($userAgent, 'Linux')) {
            $this->userPlatform = 'Linux';
        } elseif (str_contains($userAgent, 'Unix')) {
            $this->userPlatform = 'Unix';
        } elseif (str_contains($userAgent, 'OS/2')) {
            $this->userPlatform = 'OS/2';
        } else {
            $this->userPlatform = 'Other';
        }
    }

    public function getUserBrowserAgent(): string
    {
        return $this->userBrowserAgent;
    }

    public function getUserBrowserVersion(): string
    {
        return $this->userBrowserVersion;
    }

    public function getClientPlatform(): string
    {
        return $this->userPlatform;
    }
}
