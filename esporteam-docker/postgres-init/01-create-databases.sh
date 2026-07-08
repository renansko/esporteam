#!/bin/sh
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE esporteam_back;
    CREATE DATABASE esporteam_workspace;
EOSQL

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "esporteam_back" <<-EOSQL
    CREATE SCHEMA auth;
EOSQL
