# Azure Data Explorer (Kusto) Support

This document describes the **phpMyKustoAdmin** integration — Azure Data Explorer
(ADX / Kusto) support added on top of phpMyAdmin.

## Overview

phpMyKustoAdmin extends phpMyAdmin's database abstraction layer (`DbiExtension`)
with a Kusto REST API driver. This allows the phpMyAdmin web UI to browse, query,
and manage Azure Data Explorer databases and tables using Kusto Query Language
(KQL).

## Architecture

The integration follows phpMyAdmin's existing `DbiExtension` interface pattern:

```
src/Dbal/Kusto/
├── DbiKusto.php           # Main driver implementing DbiExtension
├── KustoConnection.php    # Connection value object (cluster URI + token)
├── KustoAuth.php          # Azure AD OAuth2 authentication (client creds & ROPC)
├── KustoResult.php        # ResultInterface implementation for Kusto responses
├── KustoFieldMetadata.php # Maps Kusto types to FieldMetadata for the UI
├── KqlGenerator.php       # KQL management command & query builder
└── SqlToKqlTranslator.php # Translates SQL from phpMyAdmin core to KQL
```

### How it works

1. **Auto-detection**: When a server's `host` contains `kusto.windows.net` (or
   `server_type` is set to `kusto`), `DatabaseInterface::getInstance()` creates
   a `DbiKusto` extension instead of the default `DbiMysqli`.

2. **Authentication**: `DbiKusto::connect()` uses Azure AD OAuth2 client
   credentials flow via `KustoAuth` to obtain a bearer token.

3. **SQL Translation**: phpMyAdmin's core code emits SQL (e.g.
   `SHOW DATABASES`, `SELECT * FROM table`). The `SqlToKqlTranslator`
   intercepts these and translates them to equivalent KQL management commands
   or queries.

4. **REST API**: Queries are sent to the Kusto REST API:
   - **Management commands** (`.show`, `.create`, etc.) → `POST /v1/rest/mgmt`
   - **KQL queries** → `POST /v2/rest/query`

5. **Result mapping**: JSON responses are parsed by `KustoResult` into the
   `ResultInterface` contract. Column types are mapped to MySQL-compatible
   `FieldMetadata` objects by `KustoFieldMetadata`.

## Configuration

### Prerequisites

- PHP 8.2+ with the `curl` extension enabled
- An Azure AD App Registration with:
  - A client secret
  - Permissions to your ADX cluster/database (e.g. "Viewer" or "Admin" role)

### config.inc.php setup

```php
$i++;
$cfg['Servers'][$i]['auth_type']    = 'config';
$cfg['Servers'][$i]['host']         = 'https://mycluster.westeurope.kusto.windows.net';
$cfg['Servers'][$i]['port']         = 'your-azure-ad-tenant-id';       // Tenant ID
$cfg['Servers'][$i]['user']         = 'your-app-client-id';            // Client ID
$cfg['Servers'][$i]['password']     = 'your-app-client-secret';        // Client Secret
$cfg['Servers'][$i]['only_db']      = 'MyDatabase';                    // Default database
$cfg['Servers'][$i]['verbose']      = 'My ADX Cluster';                // Display name
$cfg['Servers'][$i]['server_type']  = 'kusto';                         // Optional if host is *.kusto.windows.net
```

### Configuration mapping

| phpMyAdmin setting | Kusto meaning |
|---|---|
| `host` | Cluster URI (e.g. `https://mycluster.region.kusto.windows.net`) |
| `port` | Azure AD Tenant ID |
| `user` | Azure AD Application (Client) ID |
| `password` | Azure AD Client Secret |
| `only_db` | Default ADX database name |
| `verbose` | Display name in server selector |
| `server_type` | Set to `'kusto'` for explicit detection |

## Supported Operations

### Browsing
- List databases in a cluster
- List tables in a database
- Browse table data with pagination
- View table schema / columns

### Querying
- Execute native KQL queries in the SQL box
- Execute Kusto management commands (`.show`, `.create`, `.drop`, etc.)
- SQL queries from phpMyAdmin UI are auto-translated to KQL

### SQL-to-KQL Translation

The following SQL patterns are automatically translated:

| SQL | KQL Equivalent |
|---|---|
| `SHOW DATABASES` | `.show databases` |
| `SHOW TABLES FROM db` | `.show database db tables` |
| `SHOW COLUMNS FROM tbl` | `.show table tbl schema as cslschema` |
| `SELECT * FROM tbl LIMIT N` | `tbl \| take N` |
| `SELECT COUNT(*) FROM tbl` | `tbl \| count` |
| `SELECT cols FROM tbl WHERE ... ORDER BY ... LIMIT N` | `tbl \| where ... \| sort by ... \| take N \| project cols` |
| `DROP TABLE tbl` | `.drop table tbl ifexists` |
| `SHOW PROCESSLIST` | `.show queries` |
| `SHOW VARIABLES` | `.show version` |
| `INFORMATION_SCHEMA.TABLES` queries | `.show tables` |
| `INFORMATION_SCHEMA.COLUMNS` queries | `.show table X schema as cslschema` |

## Kusto Data Type Mapping

| Kusto Type | Mapped MySQL Type | FieldMetadata Type |
|---|---|---|
| `string` | `VARCHAR` | `TYPE_STRING` |
| `int` | `INT` | `TYPE_INT` |
| `long` | `BIGINT` | `TYPE_INT` |
| `real` / `decimal` | `DOUBLE` | `TYPE_REAL` |
| `bool` | `INT` | `TYPE_INT` |
| `datetime` | `DATETIME` | `TYPE_DATETIME` |
| `timespan` | `TIMESTAMP` | `TYPE_TIMESTAMP` |
| `guid` | `VARCHAR` | `TYPE_STRING` |
| `dynamic` | `BLOB` | `TYPE_BLOB` |

## Limitations

- **Read-heavy**: Kusto is an analytics engine, not a transactional database.
  INSERT/UPDATE/DELETE operations are not directly supported (use `.ingest inline`
  for data ingestion).
- **No prepared statements**: KQL does not support parameterised queries.
  Parameters are substituted inline with escaping.
- **No multi-result sets**: Kusto queries return a single result table.
- **Token refresh**: OAuth2 tokens expire after ~1 hour. For long sessions,
  you may need to reconnect.
- **Subset of phpMyAdmin features**: Some MySQL-specific features (indexes,
  triggers, views, stored procedures, replication, etc.) are not applicable
  to Kusto and will show empty/disabled in the UI.

## Development

### Running tests

```bash
composer test -- --filter=Kusto
```

### Key classes to modify

- `src/Dbal/Kusto/SqlToKqlTranslator.php` — Add more SQL→KQL translation rules
- `src/Dbal/Kusto/KqlGenerator.php` — Add more KQL command builders
- `src/Dbal/Kusto/DbiKusto.php` — Core driver logic
