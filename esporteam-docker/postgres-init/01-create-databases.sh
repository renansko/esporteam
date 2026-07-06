#!/bin/sh
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE esporteam_back;
    CREATE DATABASE esporteam_workspace;
    CREATE DATABASE esporteam_auth;
EOSQL
