<?php
/**
 * Azure AD OAuth2 authentication helper for Kusto
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal\Kusto;

use PhpMyAdmin\Dbal\ConnectionException;

use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function http_build_query;
use function is_string;
use function json_decode;
use function time;

use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;

/**
 * Handles Azure AD client-credentials OAuth2 flow to obtain bearer tokens for Kusto.
 */
final class KustoAuth
{
    /**
     * Obtain an OAuth2 access token using client credentials grant.
     *
     * @param string $tenantId  Azure AD tenant ID
     * @param string $clientId  Application (client) ID
     * @param string $clientSecret Application secret
     * @param string $clusterUri  Kusto cluster URI (used as the resource scope)
     *
     * @return array{access_token: string, expires_in: int}
     *
     * @throws ConnectionException
     */
    public static function getAccessToken(
        string $tenantId,
        string $clientId,
        string $clientSecret,
        string $clusterUri,
    ): array {
        $tokenEndpoint = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';

        $postFields = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => $clusterUri . '/.default',
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno !== 0 || ! is_string($response)) {
            throw new ConnectionException(
                'Failed to obtain Kusto access token: ' . $curlError,
                $curlErrno,
            );
        }

        /** @var array{access_token?: string, expires_in?: int, error?: string, error_description?: string}|null $data */
        $data = json_decode($response, true);

        if ($data === null || ! isset($data['access_token'])) {
            $errorMsg = $data['error_description'] ?? $data['error'] ?? 'Unknown authentication error';

            throw new ConnectionException(
                'Kusto authentication failed: ' . $errorMsg,
                0,
            );
        }

        return [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
        ];
    }

    /**
     * Obtain a bearer token using username/password (ROPC flow).
     * NOTE: ROPC is discouraged by Microsoft for production use but included
     * for compatibility with phpMyAdmin's "cookie" and "config" auth modes.
     *
     * @param string $tenantId   Azure AD tenant ID
     * @param string $clientId   Application (client) ID
     * @param string $username   User's email / UPN
     * @param string $password   User's password
     * @param string $clusterUri Kusto cluster URI
     *
     * @return array{access_token: string, expires_in: int}
     *
     * @throws ConnectionException
     */
    public static function getAccessTokenByPassword(
        string $tenantId,
        string $clientId,
        string $username,
        string $password,
        string $clusterUri,
    ): array {
        $tokenEndpoint = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';

        $postFields = http_build_query([
            'grant_type' => 'password',
            'client_id' => $clientId,
            'username' => $username,
            'password' => $password,
            'scope' => $clusterUri . '/.default',
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno !== 0 || ! is_string($response)) {
            throw new ConnectionException(
                'Failed to obtain Kusto access token via password: ' . $curlError,
                $curlErrno,
            );
        }

        /** @var array{access_token?: string, expires_in?: int, error?: string, error_description?: string}|null $data */
        $data = json_decode($response, true);

        if ($data === null || ! isset($data['access_token'])) {
            $errorMsg = $data['error_description'] ?? $data['error'] ?? 'Unknown authentication error';

            throw new ConnectionException(
                'Kusto password authentication failed: ' . $errorMsg,
                0,
            );
        }

        return [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'] ?? 3600,
        ];
    }

    /**
     * Build a bearer token Authorization header value.
     */
    public static function bearerHeader(string $accessToken): string
    {
        return 'Bearer ' . $accessToken;
    }
}
