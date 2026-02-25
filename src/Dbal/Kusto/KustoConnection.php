<?php
/**
 * Kusto (Azure Data Explorer) connection value object
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal\Kusto;

/**
 * Represents an active connection to an Azure Data Explorer (Kusto) cluster.
 *
 * @psalm-immutable
 */
final class KustoConnection
{
    public function __construct(
        /** The cluster URI, e.g. https://mycluster.region.kusto.windows.net */
        public readonly string $clusterUri,
        /** The default database name */
        public readonly string $database,
        /** The OAuth2 bearer token used for authentication */
        public readonly string $accessToken,
        /** When the access token expires (Unix timestamp) */
        public readonly int $tokenExpiry,
        /** The Azure AD tenant ID */
        public readonly string $tenantId,
        /** The Azure AD client/application ID */
        public readonly string $clientId,
    ) {
    }

    /** Check whether the access token has expired */
    public function isTokenExpired(): bool
    {
        return time() >= $this->tokenExpiry - 60; // 60-second buffer
    }

    /** Return a new instance with a refreshed token */
    public function withToken(string $accessToken, int $tokenExpiry): self
    {
        return new self(
            $this->clusterUri,
            $this->database,
            $accessToken,
            $tokenExpiry,
            $this->tenantId,
            $this->clientId,
        );
    }
}
