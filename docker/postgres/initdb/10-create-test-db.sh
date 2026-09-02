#!/bin/sh
# Creates the database the Pest suite runs against (phpunit.xml -> redesblog_testing).
# Only executed the first time the postgres-data volume is initialised.
set -e

psql -v ON_ERROR_STOP=1 -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" <<-EOSQL
    CREATE DATABASE redesblog_testing OWNER ${POSTGRES_USER};
EOSQL
