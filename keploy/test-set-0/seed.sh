#!/usr/bin/env bash
# Seed script for the Keploy "mysql-crud" test-set.
#
# Drives a MySQL-focused CRUD flow through phpMyAdmin's HTTP interface while
# `keploy record` captures the requests as test cases and the resulting MySQL
# wire traffic as mocks:
#
#   home → CREATE DATABASE → CREATE TABLE → INSERT ×3 → SELECT → UPDATE →
#   SELECT (read-back) → DELETE → browse table → database structure →
#   DROP TABLE → DROP DATABASE
#
# All SQL goes through the real /import route (AJAX, JSON responses). The
# CSRF token is minted by the first request; the PHP session lives on the
# bind mount (see Dockerfile) so the recorded cookie+token stay valid when
# Keploy replays the suite.
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
JAR="$(mktemp)"
trap 'rm -f "$JAR"' EXIT

# Wait until phpMyAdmin answers.
for _ in $(seq 1 90); do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL/index.php?route=/" || true)
    [ "$code" = "200" ] && break
    sleep 2
done
[ "${code:-}" = "200" ] || { echo "app never became ready" >&2; exit 1; }

# 1. Home page — mints the session and CSRF token.
home_html=$(curl -s -c "$JAR" "$BASE_URL/index.php?route=/&server=1")
TOKEN=$(printf '%s' "$home_html" | grep -o 'name="token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
[ -n "$TOKEN" ] || { echo "could not extract CSRF token" >&2; exit 1; }
echo "session minted, token=$TOKEN"
sleep 1

# Run one SQL statement through the /import route (AJAX → JSON response).
sql() { # sql <db> <query>
    echo ">> [$1] $2"
    curl -s -b "$JAR" -c "$JAR" \
        --data-urlencode "db=$1" \
        --data-urlencode "table=" \
        --data-urlencode "sql_query=$2" \
        --data-urlencode "server=1" \
        --data-urlencode "token=$TOKEN" \
        --data-urlencode "ajax_request=true" \
        "$BASE_URL/index.php?route=/import" >/dev/null
    sleep 1
}

sql ""            "CREATE DATABASE keploy_demo"
sql "keploy_demo" "CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(64) NOT NULL UNIQUE, email VARCHAR(128) NOT NULL)"
sql "keploy_demo" "INSERT INTO users (username, email) VALUES ('alice', 'alice@example.com')"
sql "keploy_demo" "INSERT INTO users (username, email) VALUES ('bob', 'bob@example.com')"
sql "keploy_demo" "INSERT INTO users (username, email) VALUES ('carol', 'carol@example.com')"
sql "keploy_demo" "SELECT id, username, email FROM users WHERE username = 'bob'"
sql "keploy_demo" "UPDATE users SET email = 'robert@example.com' WHERE username = 'bob'"
sql "keploy_demo" "SELECT id, username, email FROM users WHERE username = 'bob'"
sql "keploy_demo" "DELETE FROM users WHERE username = 'carol'"

# Read-side routes (GET, AJAX): table browse and database structure.
echo ">> browse keploy_demo.users"
curl -s -b "$JAR" -c "$JAR" \
    "$BASE_URL/index.php?route=/sql&server=1&db=keploy_demo&table=users&ajax_request=true&ajax_page_request=true" >/dev/null
sleep 1
echo ">> database structure keploy_demo"
curl -s -b "$JAR" -c "$JAR" \
    "$BASE_URL/index.php?route=/database/structure&server=1&db=keploy_demo&ajax_request=true&ajax_page_request=true" >/dev/null
sleep 1

sql "keploy_demo" "DROP TABLE users"
sql ""            "DROP DATABASE keploy_demo"

echo "seed complete"
