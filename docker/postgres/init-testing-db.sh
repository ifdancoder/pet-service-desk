#!/bin/sh
set -e

# POSTGRES_DB only creates one database on first boot. Tests run against
# their own database so RefreshDatabase never touches dev data.
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE supportflow_testing OWNER $POSTGRES_USER;
EOSQL
