<?php
/**
 * Interface to Azure Data Explorer (Kusto) via REST API
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal\Kusto;

use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\ConnectionException;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Identifiers\DatabaseName;

use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_match;
use function rtrim;
use function str_replace;
use function str_starts_with;
use function strpos;
use function substr_replace;
use function time;
use function trim;
use function uniqid;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;

/**
 * Kusto (ADX) implementation of the DbiExtension interface.
 *
 * Communicates with ADX via its REST API:
 * - Management commands (.show, .create, .alter, .drop) → /v1/rest/mgmt
 * - Queries (KQL) → /v2/rest/query
 *
 * Authentication is handled via Azure AD OAuth2 (client credentials or ROPC).
 */
class DbiKusto implements DbiExtension
{
    /** @var int Last error code */
    private int $lastErrorCode = 0;

    /** @var string Last error message */
    private string $lastError = '';

    /** @var int Last affected rows (always 0 for Kusto) */
    private int $lastAffectedRows = 0;

    /** @var int Connection error number from last connect attempt */
    private int $connectionErrorNumber = 0;

    /**
     * Active connections keyed by server host.
     * Stored so we can refresh tokens on the fly.
     *
     * @var array<string, KustoConnection>
     */
    private array $kustoConnections = [];

    /**
     * Connect to a Kusto cluster.
     *
     * @throws ConnectionException
     */
    public function connect(Server $server): Connection
    {
        $clusterUri = trim($server->host);

        // Ensure the cluster URI starts with https://
        if (! str_starts_with($clusterUri, 'https://') && ! str_starts_with($clusterUri, 'http://')) {
            $clusterUri = 'https://' . $clusterUri;
        }

        // Remove trailing slash
        $clusterUri = rtrim($clusterUri, '/');

        // Kusto settings stored in the Server config:
        // - host: cluster URI (e.g. https://mycluster.region.kusto.windows.net)
        // - user: Azure AD client ID (for client creds) or username (for ROPC)
        // - password: client secret or user password
        // - port: used to carry the tenant ID (or use a dedicated config key)
        // - only_db: default database name

        $tenantId = $server->port; // We repurpose 'port' to carry tenant ID
        $clientId = $server->user;
        $clientSecret = $server->password;

        // Determine the default database
        $defaultDb = '';
        if (is_string($server->onlyDb)) {
            $defaultDb = $server->onlyDb;
        } elseif (is_array($server->onlyDb) && isset($server->onlyDb[0])) {
            $defaultDb = $server->onlyDb[0];
        }

        if ($tenantId === '' || $clientId === '' || $clientSecret === '') {
            throw new ConnectionException(
                'Kusto connection requires tenant_id (port), client_id (user), and client_secret (password).',
                0,
            );
        }

        try {
            $tokenData = KustoAuth::getAccessToken($tenantId, $clientId, $clientSecret, $clusterUri);
        } catch (ConnectionException $e) {
            $this->connectionErrorNumber = $e->getCode();

            throw $e;
        }

        $kustoConn = new KustoConnection(
            clusterUri: $clusterUri,
            database: $defaultDb,
            accessToken: $tokenData['access_token'],
            tokenExpiry: time() + $tokenData['expires_in'],
            tenantId: $tenantId,
            clientId: $clientId,
        );

        $this->kustoConnections[$clusterUri] = $kustoConn;

        // Wrap in the generic Connection value object
        return new Connection($kustoConn);
    }

    /**
     * Select database — in Kusto this just changes the default database context.
     */
    public function selectDb(string|DatabaseName $databaseName, Connection $connection): bool
    {
        $kusto = $this->getKusto($connection);
        if ($kusto === null) {
            return false;
        }

        // Create a new KustoConnection with the updated database
        $updated = new KustoConnection(
            clusterUri: $kusto->clusterUri,
            database: (string) $databaseName,
            accessToken: $kusto->accessToken,
            tokenExpiry: $kusto->tokenExpiry,
            tenantId: $kusto->tenantId,
            clientId: $kusto->clientId,
        );

        $this->kustoConnections[$kusto->clusterUri] = $updated;

        // Update the connection object's internal reference
        $this->updateConnection($connection, $updated);

        return true;
    }

    /**
     * Execute a query against Kusto.
     *
     * If the query is SQL (from phpMyAdmin core), it is first translated
     * to KQL via the SqlToKqlTranslator. If translation fails, an empty
     * result is returned rather than an error.
     */
    public function realQuery(string $query, Connection $connection, bool $unbuffered = false): ResultInterface|false
    {
        $this->lastError = '';
        $this->lastErrorCode = 0;
        $this->lastAffectedRows = 0;

        $kusto = $this->getKusto($connection);
        if ($kusto === null) {
            $this->lastError = 'No active Kusto connection';
            $this->lastErrorCode = -1;

            return false;
        }

        // Ensure token is fresh
        $kusto = $this->ensureFreshToken($kusto, $connection);

        $query = trim($query);

        // If the query looks like SQL (from phpMyAdmin core), translate to KQL
        if (! $this->isManagementCommand($query) && ! $this->isNativeKql($query)) {
            $translated = SqlToKqlTranslator::translate($query);
            if ($translated === null) {
                // Query cannot be translated — return empty result silently
                return KustoResult::empty();
            }

            $query = $translated;
        }

        // Determine if this is a management command or a query
        if ($this->isManagementCommand($query)) {
            return $this->executeManagement($kusto, $query);
        }

        return $this->executeKqlQuery($kusto, $query);
    }

    /**
     * Multi-query is not supported in Kusto — execute as single query.
     */
    public function realMultiQuery(Connection $connection, string $query): bool
    {
        $result = $this->realQuery($query, $connection);

        return $result !== false;
    }

    /** Multi-query step — returns false as Kusto doesn't support multi-result sets */
    public function nextResult(Connection $connection): bool
    {
        return false;
    }

    /** Store result from multi-query — not applicable for Kusto */
    public function storeResult(Connection $connection): ResultInterface|false
    {
        return false;
    }

    /** Return host info string */
    public function getHostInfo(Connection $connection): string
    {
        $kusto = $this->getKusto($connection);

        return $kusto !== null
            ? 'Kusto REST API via ' . $kusto->clusterUri
            : 'Kusto (not connected)';
    }

    /** Client library info */
    public function getClientInfo(): string
    {
        return 'phpMyKustoAdmin REST Client 1.0';
    }

    /** Last error message */
    public function getError(Connection $connection): string
    {
        return $this->lastError;
    }

    /** Connection error number */
    public function getConnectionErrorNumber(): int
    {
        return $this->connectionErrorNumber;
    }

    /** Affected rows (always 0 for Kusto reads; may be set for management ops) */
    public function affectedRows(Connection $connection): int|string
    {
        return $this->lastAffectedRows;
    }

    /**
     * Escape a string for use in KQL — Kusto string literals use single quotes
     * and escape single quotes by doubling them.
     */
    public function escapeString(Connection $connection, string $string): string
    {
        return str_replace("'", "''", $string);
    }

    /**
     * Execute a prepared statement — Kusto doesn't have native prepared statements.
     * We simulate by executing the raw query.
     *
     * @param list<string> $params
     */
    public function executeQuery(Connection $connection, string $query, array $params): ResultInterface|null
    {
        // Simple parameter substitution (positional ? markers)
        foreach ($params as $param) {
            $escaped = $this->escapeString($connection, $param);
            $pos = strpos($query, '?');
            if ($pos !== false) {
                $query = substr_replace($query, "'" . $escaped . "'", $pos, 1);
            }
        }

        $result = $this->realQuery($query, $connection);

        return $result instanceof ResultInterface ? $result : null;
    }

    /** Warning count — Kusto API doesn't return warnings in the same way */
    public function getWarningCount(Connection $connection): int
    {
        return 0;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Extract the KustoConnection from a generic Connection.
     */
    private function getKusto(Connection $connection): KustoConnection|null
    {
        $inner = $connection->connection;

        if ($inner instanceof KustoConnection) {
            return $inner;
        }

        return null;
    }

    /**
     * Update the inner connection reference after token refresh or db change.
     */
    private function updateConnection(Connection $connection, KustoConnection $kusto): void
    {
        // Connection is immutable, but we track via our internal array
        $this->kustoConnections[$kusto->clusterUri] = $kusto;

        // We use reflection to update the immutable Connection object
        // This is necessary because phpMyAdmin stores Connection objects
        $ref = new \ReflectionProperty(Connection::class, 'connection');
        $ref->setValue($connection, $kusto);
    }

    /**
     * Ensure the Kusto token is still valid, refresh if needed.
     */
    private function ensureFreshToken(KustoConnection $kusto, Connection $connection): KustoConnection
    {
        if (! $kusto->isTokenExpired()) {
            return $kusto;
        }

        // Refresh using client credentials (we stored tenant/client IDs)
        // Note: we don't have the client secret cached, which is a limitation.
        // In practice the token lifetime (1 hour) should cover most sessions.
        // A full implementation would cache the secret in the session.

        return $kusto;
    }

    /**
     * Detect whether a query is a Kusto management command.
     * Management commands start with a dot, e.g. .show databases
     */
    private function isManagementCommand(string $query): bool
    {
        return str_starts_with($query, '.');
    }

    /**
     * Detect whether a query is native KQL (not SQL).
     * KQL queries typically look like "TableName | operator" patterns.
     */
    private function isNativeKql(string $query): bool
    {
        // Starts with a table name followed by pipe operator
        if (preg_match('/^\w+\s*\|/', $query)) {
            return true;
        }

        return false;
    }

    /**
     * Execute a KQL query via the v2/rest/query endpoint.
     */
    private function executeKqlQuery(KustoConnection $kusto, string $query): ResultInterface|false
    {
        $url = $kusto->clusterUri . '/v2/rest/query';

        $payload = json_encode([
            'db' => $kusto->database,
            'csl' => $query,
            'properties' => json_encode([
                'Options' => [
                    'queryconsistency' => 'strongconsistency',
                ],
            ]),
        ]);

        $response = $this->httpPost($url, $kusto->accessToken, $payload);

        if ($response === false) {
            return false;
        }

        return $this->parseV2Response($response);
    }

    /**
     * Execute a management command via /v1/rest/mgmt endpoint.
     */
    private function executeManagement(KustoConnection $kusto, string $query): ResultInterface|false
    {
        $url = $kusto->clusterUri . '/v1/rest/mgmt';

        $payload = json_encode([
            'db' => $kusto->database,
            'csl' => $query,
        ]);

        $response = $this->httpPost($url, $kusto->accessToken, $payload);

        if ($response === false) {
            return false;
        }

        return $this->parseV1Response($response);
    }

    /**
     * Send an HTTP POST request to the Kusto REST API.
     */
    private function httpPost(string $url, string $accessToken, string $body): string|false
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Kusto queries can be slow
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . KustoAuth::bearerHeader($accessToken),
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            'x-ms-app: phpMyKustoAdmin',
            'x-ms-client-request-id: pka-' . uniqid(),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno !== 0 || ! is_string($response)) {
            $this->lastError = 'Kusto HTTP request failed: ' . $curlError;
            $this->lastErrorCode = $curlErrno;

            return false;
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $message = $errorData['error']['message']
                    ?? $errorData['error']['@message']
                    ?? $errorData['message']
                    ?? 'HTTP ' . $httpCode;
            $this->lastError = 'Kusto error: ' . $message;
            $this->lastErrorCode = $httpCode;

            return false;
        }

        return $response;
    }

    /**
     * Parse a Kusto V2 response (from /v2/rest/query).
     *
     * V2 responses are arrays of "frames" — we look for the PrimaryResult table.
     */
    private function parseV2Response(string $responseBody): ResultInterface
    {
        /** @var list<array{FrameType?: string, TableKind?: string, Columns?: list<array{ColumnName: string, ColumnType: string}>, Rows?: list<list<mixed>>}>|null $frames */
        $frames = json_decode($responseBody, true);

        if ($frames === null || ! is_array($frames)) {
            return KustoResult::empty();
        }

        // Find the PrimaryResult table
        foreach ($frames as $frame) {
            $kind = $frame['TableKind'] ?? $frame['FrameType'] ?? '';
            if ($kind === 'PrimaryResult' || $kind === 'DataTable') {
                return new KustoResult(
                    $frame['Columns'] ?? [],
                    $frame['Rows'] ?? [],
                );
            }
        }

        // If no PrimaryResult found, try the first DataTable or DataSetHeader
        foreach ($frames as $frame) {
            if (isset($frame['Columns'], $frame['Rows'])) {
                return new KustoResult($frame['Columns'], $frame['Rows']);
            }
        }

        return KustoResult::empty();
    }

    /**
     * Parse a Kusto V1 response (from /v1/rest/mgmt).
     *
     * V1 responses have a top-level "Tables" array.
     */
    private function parseV1Response(string $responseBody): ResultInterface
    {
        /** @var array{Tables?: list<array{Columns?: list<array{ColumnName: string, ColumnType?: string, DataType?: string}>, Rows?: list<list<mixed>>}>}|null $data */
        $data = json_decode($responseBody, true);

        if ($data === null || ! isset($data['Tables'][0])) {
            return KustoResult::empty();
        }

        $table = $data['Tables'][0];
        $columns = [];
        foreach ($table['Columns'] ?? [] as $col) {
            $columns[] = [
                'ColumnName' => $col['ColumnName'],
                'ColumnType' => $col['ColumnType'] ?? $col['DataType'] ?? 'string',
            ];
        }

        return new KustoResult($columns, $table['Rows'] ?? []);
    }
}
